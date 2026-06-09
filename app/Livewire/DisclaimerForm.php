<?php

namespace App\Livewire;

use App\Models\Shipment;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;

class DisclaimerForm extends Component implements HasForms
{
    use InteractsWithForms;

    public $record;

    public ?array $data = [];

    public function mount($record): void
    {
        //dd($record);
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Checkbox::make('disclaima_aggreed')
                ->required()
                ->label('I agree to this Disclaimer'),
            ])
            ->statePath('data');
    }

    public function save()
    {
        $data = $this->form->getState();

        $shipment = Shipment::where('id',$this->record)->first();
        $shipment->is_disclaimer_agreed = $data['disclaima_aggreed'];
        $shipment->save();


         return redirect()->route('make-payment-agreement',['record' => $this->record]);

        $this->record->update($data);
    }

    public function render(): View
    {
        return view('livewire.disclaimer-form');
    }
}
