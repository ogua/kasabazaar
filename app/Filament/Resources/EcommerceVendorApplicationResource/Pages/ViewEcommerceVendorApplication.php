<?php

namespace App\Filament\Resources\EcommerceVendorApplicationResource\Pages;

use App\Enums\EcommerceVendorApplicationStatus;
use App\Filament\Resources\EcommerceVendorApplicationResource;
use App\Models\EcommerceVendorApplication;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;

class ViewEcommerceVendorApplication extends ViewRecord
{
    protected static string $resource = EcommerceVendorApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === EcommerceVendorApplicationStatus::Pending)
                ->form([
                    Forms\Components\Textarea::make('review_notes')->label('Notes')->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->review(EcommerceVendorApplicationStatus::Approved, $data['review_notes'] ?? null);
                })
                ->successNotificationTitle('Application approved'),

            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === EcommerceVendorApplicationStatus::Pending)
                ->form([
                    Forms\Components\Textarea::make('review_notes')->label('Reason')->required()->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->review(EcommerceVendorApplicationStatus::Rejected, $data['review_notes']);
                })
                ->successNotificationTitle('Application rejected'),
        ];
    }

    private function review(EcommerceVendorApplicationStatus $status, ?string $notes): void
    {
        $this->record->update([
            'status' => $status->value,
            'review_notes' => $notes,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $this->refreshFormData(['status', 'review_notes', 'reviewed_by', 'reviewed_at']);
    }

    protected function resolveRecord(int|string $key): EcommerceVendorApplication
    {
        return EcommerceVendorApplication::with('reviewedBy')->findOrFail($key);
    }
}
