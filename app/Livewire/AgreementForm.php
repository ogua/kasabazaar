<?php

namespace App\Livewire;

use App\Filament\Client\Pages\PageSuccessfully;
use Filament\Forms;
use App\Models\User;
use Livewire\Component;
use App\Models\Shipment;
use Filament\Forms\Form;
use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Matscode\Paystack\Transaction;

class AgreementForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public $record;

    public function mount($record): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([Forms\Components\Checkbox::make('disclaima_aggreed')->required()->label('I have read and agree to the terms and conditions above')])
            ->statePath('data')
            ->model(User::class);
    }

    public function create()
    {
        $data = $this->form->getState();

        if (empty($data['disclaima_aggreed'])) {
            return;
        }

        $shipment = Shipment::where('id', $this->record)->first();
        $shipment->is_agreement_agreed = $data['disclaima_aggreed'];
        $shipment->save();

        $secretKey = env('PAYSTACK_SECRET_KEY');
        // creating the transaction object
        $Transaction = new Transaction($secretKey);

        $amount = $shipment->total;

        $response = (object) $Transaction
            ->setCallbackUrl(route('paid-successfully'))
            ->setEmail($shipment->client?->email)
            ->setAmount($amount)
            ->setMetadata([
                'fullname' => $shipment->client?->name,
                'phone' => $shipment->client?->phone,
                'email' => $shipment->client?->email,
                'reference' => $shipment->shipping_reference,
                'shipment_id' => $shipment->id,
            ])
            ->initialize();

        return redirect()->to($response->authorizationUrl);
    }

    public function render(): View
    {
        return view('livewire.agreement-form');
    }
}
