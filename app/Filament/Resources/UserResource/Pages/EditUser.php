<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Actions\AuditHistoryAction;
use App\Filament\Resources\UserResource;
use App\Services\Audit\AccessChangeRecorder;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /** @var array{roles: list<string>, permissions: list<string>, branches: list<string>} */
    protected array $accessBeforeSave = ['roles' => [], 'permissions' => [], 'branches' => []];

    protected function getHeaderActions(): array
    {
        return [
            AuditHistoryAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * The roles and branches selects sync their relationships directly (no events), so the
     * change is diffed here for the audit trail.
     */
    protected function beforeSave(): void
    {
        $this->accessBeforeSave = AccessChangeRecorder::snapshot($this->record);
    }

    protected function afterSave(): void
    {
        AccessChangeRecorder::record($this->record, $this->accessBeforeSave);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
