<?php

namespace App\Livewire;

use Filament\Forms;
use Livewire\Component;
use App\Models\Shipment;
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
    public ?string $token = null;
    public ?string $existingFeedbackId = null;

    public function mount(?string $token = null): void
    {
        $this->token = $token;

        $defaults = [
            'feedback_on' => 'Rose Shipment',
            'category' => 'Delivery Speed',
            'rating' => 5,
            'type' => 'feedback',
        ];

        if ($token) {
            $feedback = CustomerFeedback::where('complaint_token', $token)->first();
            if ($feedback) {
                $this->existingFeedbackId = $feedback->id;
                $defaults['type'] = 'complaint';
                $defaults['feedback_on'] = $feedback->feedback_on;

                if ($feedback->customer_name !== 'Pending') {
                    $defaults['customer_name'] = $feedback->customer_name;
                }
                $defaults['customer_email'] = $feedback->customer_email ?? '';
                $defaults['customer_phone'] = $feedback->customer_phone ?? '';

                if ($feedback->shipment) {
                    $defaults['invoice_number'] = $feedback->shipment->shipping_reference ?? '';
                    $defaults['tracking_number'] = $feedback->shipment->tracking_number ?? '';
                }
            }
        }

        $this->form->fill($defaults);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Type')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Submission Type')
                            ->options(CustomerFeedback::TYPES)
                            ->default('feedback')
                            ->required()
                            ->native(false)
                            ->disabled(fn () => !empty($this->token)),
                    ])
                    ->columns(2),

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
                        Forms\Components\TextInput::make('tracking_number')
                            ->label('Tracking Number')
                            ->placeholder('Enter tracking number to link to shipment (optional)')
                            ->maxLength(255),
                    ])->columns(3),

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
                            ->maxLength(50)
                            ->placeholder('Enter your phone number'),
                    ])->columns(3),

                Forms\Components\Section::make('Your Feedback')
                    ->description('Share your experience with us')
                    ->schema([
                        Forms\Components\Select::make('category')
                            ->label('Feedback Category')
                            ->options(CustomerFeedback::CATEGORIES)
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

        // Try to link to shipment via tracking number
        if (!empty($data['tracking_number'])) {
            $shipment = Shipment::where('tracking_number', $data['tracking_number'])
                ->orWhere('shipping_reference', $data['tracking_number'])
                ->first();
            if ($shipment) {
                $data['shipment_id'] = $shipment->id;
            }
            unset($data['tracking_number']);
        }

        if ($this->existingFeedbackId) {
            // Update existing complaint record
            $feedback = CustomerFeedback::find($this->existingFeedbackId);
            if ($feedback) {
                $feedback->update($data);
            }
        } else {
            CustomerFeedback::create($data);
        }

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
        $this->existingFeedbackId = null;
        $this->token = null;
        $this->form->fill([
            'feedback_on' => 'Rose Shipment',
            'category' => 'Delivery Speed',
            'rating' => '5',
            'type' => 'feedback',
        ]);
    }

    public function render(): View
    {
        return view('livewire.customer-feedaback');
    }
}
