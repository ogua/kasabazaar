<?php

namespace App\Livewire;

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

    public function save(): void
    {
        $data = $this->form->getState();

        $this->record->update($data);
    }

    public function render(): View
    {
        return view('livewire.disclaimer-form');
    }
}
