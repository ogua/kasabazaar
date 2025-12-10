<?php

namespace App\Livewire;

use Filament\Forms;
use Livewire\Component;
use Filament\Forms\Form;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Models\CustomerFeedback;
use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;

#[Layout('components.layouts.app')]
class CustomerFeedaback extends Component implements HasForms
{
    use InteractsWithForms, WithFileUploads;

    public ?array $data = [];
    public bool $submitted = false;

    public function mount(): void
    {
        $this->form->fill([
            'feedback_on' => 'Rose Shipment',
            'category' => 'Delivery Speed',
            'rating' => 5,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Service Information')
                    ->description('Tell us about which service you are providing feedback for')
                    ->schema([
                        Forms\Components\Select::make('feedback_on')
                            ->label('Service')
                            ->options([
                                'Rose Shipment' => 'Rose Shipment',
                                'NeoRide Africa' => 'NeoRide Africa',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Invoice/Reference Number')
                            ->placeholder('Enter your invoice or reference number (optional)')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Your Information')
                    ->description('Please provide your contact details')
                    ->schema([
                        Forms\Components\TextInput::make('customer_name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter your full name'),
                        Forms\Components\TextInput::make('customer_email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter your email address'),
                        Forms\Components\TextInput::make('customer_phone')
                            ->label('Phone Number')
                            //->tel()
                            //->required()
                            ->maxLength(50)
                            ->placeholder('Enter your phone number'),
                    ])->columns(3),

                Forms\Components\Section::make('Your Feedback')
                    ->description('Share your experience with us')
                    ->schema([
                        Forms\Components\Select::make('category')
                            ->label('Feedback Category')
                            ->options([
                                'Delivery Speed' => 'Delivery Speed',
                                'Item Condition' => 'Item Condition',
                                'Late Delivery' => 'Late Delivery',
                                'Wrong Address' => 'Wrong Address',
                                'Damaged Item' => 'Damaged Item',
                                'Damaged Packaging' => 'Damaged Packaging',
                                'Wrong Item Sent' => 'Wrong Item Sent',
                                'Missing Item' => 'Missing Item',
                                'Driver Conduct' => 'Driver Conduct',
                                'Ignored Instructions' => 'Ignored Instructions',
                                'Tricycle Maintenance' => 'Tricycle Maintenance',
                                'Driver Safety' => 'Driver Safety',
                                'Route Efficiency' => 'Route Efficiency',
                                'Traffic Handling' => 'Traffic Handling',
                                'Other' => 'Other',
                            ])
                            ->required()
                            ->searchable()
                            ->native(false),
                        Forms\Components\Radio::make('rating')
                            ->label('How would you rate your experience?')
                            ->options([
                                '1' => '1 - Very Poor',
                                '2' => '2 - Poor',
                                '3' => '3 - Average',
                                '4' => '4 - Good',
                                '5' => '5 - Excellent',
                            ])
                            ->required()
                            ->inline()
                            ->inlineLabel(false),
                        Forms\Components\Textarea::make('comment')
                            ->label('Your Comments')
                            ->placeholder('Please share your feedback, suggestions, or concerns...')
                            ->rows(5)
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('attachments')
                            ->label('Attachments (Optional)')
                            ->multiple()
                            ->maxFiles(5)
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120)
                            ->helperText('Upload up to 5 images or PDFs (max 5MB each)')
                            ->directory('feedback-attachments')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data')
            ->model(CustomerFeedback::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();
        $data['status'] = 'pending';

        CustomerFeedback::create($data);

        $this->submitted = true;

        Notification::make()
            ->title('Thank you for your feedback!')
            ->body('We have received your feedback and will review it shortly.')
            ->success()
            ->send();
    }

    public function submitAnother(): void
    {
        $this->submitted = false;
        $this->form->fill([
            'feedback_on' => 'Rose Shipment',
            'category' => 'Delivery Speed',
            'rating' => '5',
        ]);
    }

    public function render(): View
    {
        return view('livewire.customer-feedaback');
    }
}
