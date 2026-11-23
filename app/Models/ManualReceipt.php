<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Number;

/**
 * A receipt issued by hand for money the company has already received — typically payments
 * made before this system went live (2019 onwards) that clients now want paperwork for.
 *
 * It is a document only: creating one never touches payments, incomes, the cashbook or any
 * balance, and nothing in the accounts reads this table.
 */
class ManualReceipt extends Model
{
    use Auditable, HasFactory, HasUuids, SoftDeletes;

    protected static string $auditLog = 'finance';

    /** @var list<string> */
    protected static array $auditExcept = ['last_sent_at', 'last_sent_to', 'times_sent'];

    /** @var list<string> */
    protected static array $auditHighRisk = ['amount', 'currency', 'paid_on', 'client_id'];

    public const RECEIPT_PREFIX = 'MR';

    /**
     * Suggestions for the payment method field; staff may type anything else.
     *
     * @var list<string>
     */
    public const PAYMENT_METHODS = [
        'Cash',
        'Mobile Money',
        'Bank Transfer',
        'Bank Deposit',
        'Card',
        'Cheque',
        'Zelle',
        'Cash App',
        'PayPal',
        'Western Union',
    ];

    /** @var array<string, string> */
    public const CURRENCIES = [
        'USD' => 'US Dollar (USD)',
        'GHS' => 'Ghana Cedi (GHS)',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_on' => 'date',
            'last_sent_at' => 'datetime',
            'times_sent' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ManualReceipt $receipt): void {
            if (blank($receipt->receipt_number)) {
                $receipt->receipt_number = static::nextReceiptNumber();
            }

            if (blank($receipt->issued_by) && auth()->check()) {
                $receipt->issued_by = auth()->id();
            }
        });
    }

    /**
     * MR-2026-00001, numbered per year of issue and never reused (deleted receipts keep theirs).
     */
    public static function nextReceiptNumber(): string
    {
        $prefix = static::RECEIPT_PREFIX.'-'.now()->format('Y').'-';

        $last = static::withTrashed()
            ->where('receipt_number', 'like', $prefix.'%')
            ->orderByDesc('receipt_number')
            ->value('receipt_number');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function auditLabel(): string
    {
        return 'Manual receipt '.$this->receipt_number;
    }

    public function currencySymbol(): string
    {
        return $this->currency === 'GHS' ? 'GH₵' : '$';
    }

    public function formattedAmount(): string
    {
        return $this->currencySymbol().number_format((float) $this->amount, 2);
    }

    /**
     * "One thousand two hundred US dollars and fifty cents" — printed under the amount.
     */
    public function amountInWords(): string
    {
        $amount = (float) $this->amount;
        $whole = (int) floor($amount);
        $minor = (int) round(($amount - $whole) * 100);

        [$major, $minorUnit] = $this->currency === 'GHS' ? ['Ghana cedis', 'pesewas'] : ['US dollars', 'cents'];

        $words = ucfirst(Number::spell($whole)).' '.$major;

        if ($minor > 0) {
            $words .= ' and '.Number::spell($minor).' '.$minorUnit;
        }

        return $words.' only';
    }
}
