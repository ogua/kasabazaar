<?php

namespace App\Filament\Resources;

use App\Enums\EcommerceVendorApplicationStatus;
use App\Filament\Resources\EcommerceVendorApplicationResource\Pages;
use App\Models\EcommerceVendorApplication;
use App\Services\VendorProvisioningService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class EcommerceVendorApplicationResource extends Resource
{
    protected static ?string $model = EcommerceVendorApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'E-Commerce';

    protected static ?int $navigationSort = 7;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Applicant')
                    ->schema([
                        Forms\Components\TextInput::make('business_name')->disabled(),
                        Forms\Components\TextInput::make('contact_name')->disabled(),
                        Forms\Components\TextInput::make('email')->disabled(),
                        Forms\Components\TextInput::make('phone')->disabled(),
                        Forms\Components\TextInput::make('product_category')->disabled(),
                        Forms\Components\Select::make('status')
                            ->options(EcommerceVendorApplicationStatus::class)
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('message')
                            ->label('Applicant Message')
                            ->disabled()
                            ->rows(3),
                        Forms\Components\Textarea::make('review_notes')
                            ->disabled()
                            ->rows(3),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Applicant')
                    ->schema([
                        Infolists\Components\TextEntry::make('business_name'),
                        Infolists\Components\TextEntry::make('contact_name'),
                        Infolists\Components\TextEntry::make('email')->copyable(),
                        Infolists\Components\TextEntry::make('phone')->placeholder('—'),
                        Infolists\Components\TextEntry::make('product_category')->placeholder('—'),
                        Infolists\Components\TextEntry::make('status')->badge(),
                        Infolists\Components\TextEntry::make('message')
                            ->label('Applicant Message')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('KYC Documents')
                    ->description('Stored on a private disk — not web-accessible by URL. Download to review.')
                    ->schema([
                        self::documentDownloadAction('business_certificate_path', 'Business Certificate'),
                        self::documentDownloadAction('ghana_card_front_path', 'Ghana Card (Front)'),
                        self::documentDownloadAction('ghana_card_back_path', 'Ghana Card (Back)'),
                        self::documentDownloadAction('proof_of_address_path', 'Proof of Address'),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Review')
                    ->schema([
                        Infolists\Components\TextEntry::make('review_notes')->placeholder('—')->columnSpanFull(),
                        Infolists\Components\TextEntry::make('reviewedBy.name')->label('Reviewed By')->placeholder('—'),
                        Infolists\Components\TextEntry::make('reviewed_at')->dateTime()->placeholder('—'),
                    ])
                    ->columns(2)
                    ->visible(fn (EcommerceVendorApplication $record): bool => $record->status !== EcommerceVendorApplicationStatus::Pending),
            ]);
    }

    private static function documentDownloadAction(string $field, string $label): Infolists\Components\Actions\Action
    {
        return Infolists\Components\Actions\Action::make("download_{$field}")
            ->label($label)
            ->icon('heroicon-m-arrow-down-tray')
            ->visible(fn (EcommerceVendorApplication $record): bool => filled($record->{$field}))
            ->action(fn (EcommerceVendorApplication $record) => Storage::disk('vendor_documents')->download($record->{$field}));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business_name')->searchable(),
                Tables\Columns\TextColumn::make('contact_name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('phone')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('Applied')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(EcommerceVendorApplicationStatus::class),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->visible(fn (EcommerceVendorApplication $record) => $record->status === EcommerceVendorApplicationStatus::Pending)
                        ->form([
                            Forms\Components\Textarea::make('review_notes')->label('Notes')->rows(3),
                        ])
                        ->action(fn (EcommerceVendorApplication $record, array $data) => self::approve($record, $data['review_notes'] ?? null)),

                    Tables\Actions\Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->visible(fn (EcommerceVendorApplication $record) => $record->status === EcommerceVendorApplicationStatus::Pending)
                        ->form([
                            Forms\Components\Textarea::make('review_notes')->label('Reason')->required()->rows(3),
                        ])
                        ->action(fn (EcommerceVendorApplication $record, array $data) => self::review($record, EcommerceVendorApplicationStatus::Rejected, $data['review_notes']))
                        ->successNotificationTitle('Application rejected'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private static function review(EcommerceVendorApplication $application, EcommerceVendorApplicationStatus $status, ?string $notes): void
    {
        $application->update([
            'status' => $status->value,
            'review_notes' => $notes,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
    }

    private static function approve(EcommerceVendorApplication $application, ?string $notes): void
    {
        try {
            app(VendorProvisioningService::class)->approve($application, $notes, auth()->id());

            Notification::make()
                ->title('Application approved')
                ->body('Vendor account provisioned and a password-reset link was emailed.')
                ->success()
                ->send();
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('Could not approve application')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', EcommerceVendorApplicationStatus::Pending)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEcommerceVendorApplications::route('/'),
            'view' => Pages\ViewEcommerceVendorApplication::route('/{record}'),
        ];
    }
}
