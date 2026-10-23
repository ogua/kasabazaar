<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmTrackingPaymentPhoneRequest;
use App\Http\Requests\StoreTrackingPaymentRequest;
use App\Models\Shipment;
use App\Models\ShipmentPaymentAttempt;
use App\Services\PublicShipmentTracking;
use App\Services\ShipmentPaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * Paying a shipment balance from the public tracking page, in USD through Paystack.
 *
 * The tracking page never shows payment state to someone who merely holds a
 * number, so the balance is only revealed after the visitor proves a link to the
 * shipment: the sender's or a receiver's phone number on file. Payments are
 * reached by the random tracking number only, never the sequential reference.
 * Anyone holding a payment reference can open its receipt to verify it.
 */
class TrackingPaymentController extends Controller
{
    private const SESSION_KEY = 'tracking_payment';

    private const CONFIRMATION_MINUTES = 30;

    private const MAX_PHONE_ATTEMPTS = 5;

    public function create(string $trackingNumber, PublicShipmentTracking $tracking, ShipmentPaymentService $payments): View
    {
        $shipment = $this->payableShipment($trackingNumber, $tracking);
        $confirmed = $this->confirmedPhone($shipment) !== null;
        $balance = $confirmed ? round($shipment->outstanding_balance, 2) : null;

        return view('site.tracking-pay', [
            'shipment' => $shipment,
            'trackingNumber' => $shipment->tracking_number,
            'confirmed' => $confirmed,
            'balance' => $balance,
            'charge' => $balance > 0 ? $payments->chargeFor((float) old('amount', $balance)) : null,
            'feePercent' => (float) config('services.paystack.transaction_fee_percent', 0),
            'total' => $confirmed ? (float) $shipment->total : null,
            'paid' => $confirmed ? $shipment->amount_paid : null,
            'currency' => $currency = config('services.paystack.shipment_currency', 'USD'),
            'exchangeRate' => $confirmed && $currency !== 'USD' ? $payments->exchangeRateFor($shipment) : null,
        ]);
    }

    public function confirmPhone(ConfirmTrackingPaymentPhoneRequest $request, string $trackingNumber, PublicShipmentTracking $tracking): RedirectResponse
    {
        $shipment = $this->payableShipment($trackingNumber, $tracking);
        $limiterKey = 'tracking-payment-phone:'.$shipment->id;

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_PHONE_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($limiterKey) / 60);

            return back()->withErrors(['phone' => "Too many attempts. Please try again in {$minutes} minute(s), or call us."]);
        }

        if (! $this->phoneMatches($shipment, $request->validated('phone'))) {
            RateLimiter::hit($limiterKey, 15 * 60);

            return back()->withInput()->withErrors([
                'phone' => 'That number doesn’t match our records for this shipment. Use the sender’s or receiver’s number as given when the shipment was booked.',
            ]);
        }

        RateLimiter::clear($limiterKey);
        $request->session()->put(self::SESSION_KEY.'.'.$shipment->id, [
            'phone' => $request->validated('phone'),
            'at' => now()->timestamp,
        ]);

        return redirect()->route('tracking-payment.create', $shipment->tracking_number);
    }

    public function store(
        StoreTrackingPaymentRequest $request,
        string $trackingNumber,
        PublicShipmentTracking $tracking,
        ShipmentPaymentService $payments,
    ): RedirectResponse {
        $shipment = $this->payableShipment($trackingNumber, $tracking);
        $phone = $this->confirmedPhone($shipment);

        if ($phone === null) {
            return redirect()->route('tracking-payment.create', $shipment->tracking_number)
                ->withErrors(['phone' => 'Please confirm your phone number again to continue.']);
        }

        $balance = round($shipment->outstanding_balance, 2);

        if ($balance <= 0) {
            return redirect()->route('tracking-payment.create', $shipment->tracking_number);
        }

        $amount = round((float) $request->validated('amount'), 2);

        if ($amount > $balance) {
            return back()->withInput()->withErrors([
                'amount' => 'You can pay at most $'.number_format($balance, 2).' — the outstanding balance.',
            ]);
        }

        try {
            $attempt = $payments->start(
                $shipment,
                $amount,
                route('tracking-payment.callback'),
                'tracking_page',
                $request->validated('email'),
                $phone,
            );
        } catch (\Throwable $e) {
            logger()->error('Tracking page payment init failed for '.$shipment->id.': '.$e->getMessage());

            return back()->withInput()->withErrors([
                'amount' => 'We couldn’t start the payment just now. Please try again in a few minutes, or call us.',
            ]);
        }

        return redirect()->away($attempt->authorizationUrl);
    }

    /**
     * Paystack sends the payer back here. The webhook may already have recorded the
     * payment; verifying again is harmless.
     */
    public function callback(Request $request, ShipmentPaymentService $payments): RedirectResponse
    {
        $reference = (string) ($request->query('reference') ?? $request->query('trxref') ?? '');
        $attempt = $reference !== '' ? ShipmentPaymentAttempt::where('reference', $reference)->first() : null;

        if (! $attempt) {
            return redirect()->route('tracking-payment.verify')
                ->withErrors(['reference' => 'We couldn’t find that payment. Check the reference on your Paystack receipt.']);
        }

        $payments->refresh($attempt);

        return redirect()->route('tracking-payment.show', $attempt->reference);
    }

    public function verify(Request $request): View|RedirectResponse
    {
        $reference = strtoupper(trim((string) $request->query('reference', '')));

        if ($reference === '') {
            return view('site.tracking-payment-verify', ['reference' => '']);
        }

        if (ShipmentPaymentAttempt::where('reference', $reference)->exists()) {
            return redirect()->route('tracking-payment.show', $reference);
        }

        return view('site.tracking-payment-verify', ['reference' => $reference])
            ->withErrors(['reference' => 'No payment was found with that reference. It starts with KRP- and is in your receipt email and SMS.']);
    }

    /**
     * The receipt, laid out like the shipping invoice.
     */
    public function show(string $reference, ShipmentPaymentService $payments): View
    {
        return view('pdf.tracking-payment-receipt', $this->receiptData($reference, $payments));
    }

    public function pdf(string $reference, ShipmentPaymentService $payments): Response
    {
        return Pdf::loadView('pdf.tracking-payment-receipt', [...$this->receiptData($reference, $payments), 'forPdf' => true])
            ->setPaper('a4', 'portrait')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'defaultFont' => 'sans-serif'])
            ->download('receipt-'.$reference.'.pdf');
    }

    /**
     * @return array{attempt: ShipmentPaymentAttempt, shipment: Shipment, balance: float, paidBefore: float, maskedPhone: ?string}
     */
    private function receiptData(string $reference, ShipmentPaymentService $payments): array
    {
        $attempt = ShipmentPaymentAttempt::where('reference', $reference)->firstOrFail();
        $attempt = $payments->refresh($attempt)->load('shipment.client', 'payment');
        $shipment = $attempt->shipment;

        $phoneDigits = preg_replace('/\D+/', '', (string) $attempt->payer_phone);

        return [
            'attempt' => $attempt,
            'shipment' => $shipment,
            'balance' => round($shipment->outstanding_balance, 2),
            // What had been paid before this payment, so the totals read like the invoice.
            'paidBefore' => round(max(0, $shipment->amount_paid - ($attempt->isPaid() ? (float) $attempt->amount_usd : 0)), 2),
            'maskedPhone' => $phoneDigits !== '' ? str_repeat('•', max(0, strlen($phoneDigits) - 3)).substr($phoneDigits, -3) : null,
        ];
    }

    /**
     * A shipment that can be paid from the tracking page: found by its tracking
     * number exactly, and not cancelled.
     */
    private function payableShipment(string $trackingNumber, PublicShipmentTracking $tracking): Shipment
    {
        $shipment = Shipment::where('tracking_number', $trackingNumber)->first();

        abort_if(
            ! $shipment || ! $tracking->lookedUpByTrackingNumber($shipment, $trackingNumber) || $tracking->rawStatus($shipment) === 'cancelled',
            404
        );

        return $shipment;
    }

    /**
     * The phone number the visitor confirmed for this shipment in this session, if
     * the confirmation is still fresh.
     */
    private function confirmedPhone(Shipment $shipment): ?string
    {
        $confirmation = session(self::SESSION_KEY.'.'.$shipment->id);

        if (! is_array($confirmation) || now()->timestamp - ($confirmation['at'] ?? 0) > self::CONFIRMATION_MINUTES * 60) {
            return null;
        }

        return $confirmation['phone'] ?? null;
    }

    /**
     * Whether the number matches the sender's or a receiver's phone on file. Compared
     * on the last nine digits so +233 24…, 024… and 24… all match the same number.
     */
    private function phoneMatches(Shipment $shipment, string $phone): bool
    {
        $given = self::lastNineDigits($phone);

        if (strlen($given) < 9) {
            return false;
        }

        $onFile = collect([$shipment->client?->phone])
            ->merge($shipment->receivers()->pluck('receiver_phone'))
            ->map(fn (?string $number): string => self::lastNineDigits((string) $number))
            ->filter(fn (string $digits): bool => strlen($digits) === 9);

        return $onFile->contains(fn (string $digits): bool => hash_equals($digits, $given));
    }

    private static function lastNineDigits(string $phone): string
    {
        return substr(preg_replace('/\D+/', '', $phone), -9);
    }
}
