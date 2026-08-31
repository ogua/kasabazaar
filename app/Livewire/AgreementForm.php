<?php

namespace App\Livewire;

use App\Models\Shipment;
use App\Models\User;
use App\Services\ShipmentPaymentService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Contracts\View\View;
use Livewire\Component;

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

        $amount = $shipment->outstanding_balance > 0
            ? $shipment->outstanding_balance
            : (float) $shipment->total;

        $url = app(ShipmentPaymentService::class)->initialize(
            $shipment,
            (float) $amount,
            route('paid-successfully'),
        );

        return redirect()->to($url);
    }

    public function render(): View
    {
        return view('livewire.agreement-form');
    }
}
