<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentResource;
use App\Models\ShipmentMedia;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class UploadShipmentEvidence extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithRecord;

    protected static string $resource = ShipmentResource::class;

    protected static string $view = 'filament.resources.shipment-resource.pages.upload-shipment-evidence';

    protected static ?string $title = 'Upload Evidence';

    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('media_stage')
                    ->label('Stage')
                    ->options(ShipmentMedia::STAGES)
                    ->default('pickup')
                    ->required()
                    ->native(false),

                Forms\Components\FileUpload::make('media_files')
                    ->label('Upload Images / Videos')
                    ->multiple()
                    ->directory('shipment-media')
                    ->acceptedFileTypes(['image/*', 'video/*'])
                    ->maxSize(51200)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('media_caption')
                    ->label('Caption (optional)')
                    ->placeholder('Describe these files...'),
            ])
            ->statePath('data');
    }

    public function uploadEvidence(): void
    {
        $data = $this->form->getState();

        if (! empty($data['media_files'])) {
            foreach ($data['media_files'] as $filePath) {
                $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $type = in_array($extension, ['mp4', 'mov', 'avi', 'webm', 'mkv']) ? 'video' : 'image';

                ShipmentMedia::create([
                    'shipment_id' => $this->record->id,
                    'type' => $type,
                    'file_path' => $filePath,
                    'stage' => $data['media_stage'],
                    'caption' => $data['media_caption'] ?? null,
                    'uploaded_by' => auth()->id(),
                ]);
            }

            Notification::make()
                ->title('Evidence Uploaded')
                ->body(count($data['media_files']).' file(s) uploaded successfully.')
                ->success()
                ->send();
        }

        $this->redirect(ShipmentResource::getUrl('print-label', ['record' => $this->record]));
    }

    public function skip(): void
    {
        $this->redirect(ShipmentResource::getUrl('print-label', ['record' => $this->record]));
    }
}
