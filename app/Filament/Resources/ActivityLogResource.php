<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\Activity;
use App\Models\Branch;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

/**
 * The audit trail: who did what, when, from where, and what changed. Read-only.
 */
class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';

    protected static ?string $navigationGroup = 'Audit & Security';

    protected static ?string $navigationLabel = 'Activity Log';

    protected static ?string $modelLabel = 'Activity';

    protected static ?string $pluralModelLabel = 'Activity Log';

    protected static ?string $slug = 'activity-log';

    protected static bool $isScopedToTenant = false;

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['causer', 'impersonator', 'branch']))
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('d M Y, H:i:s')
                    ->description(fn (Activity $record): string => $record->created_at?->diffForHumans() ?? '')
                    ->sortable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Who')
                    ->getStateUsing(fn (Activity $record): string => $record->actorLabel())
                    ->description(fn (Activity $record): ?string => $record->impersonator ? 'impersonated by '.$record->impersonator->name : null)
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('event')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Str::headline($state ?? 'event'))
                    ->color(fn (Activity $record): string => $record->eventColor()),
                Tables\Columns\TextColumn::make('description')
                    ->label('What')
                    ->wrap()
                    ->limit(140)
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_label')
                    ->label('Record')
                    ->description(fn (Activity $record): ?string => $record->subjectTypeLabel())
                    ->url(fn (Activity $record): ?string => self::subjectUrl($record))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('log_name')
                    ->label('Module')
                    ->formatStateUsing(fn (?string $state): string => Str::headline($state ?? ''))
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('risk')
                    ->label('Risk')
                    ->icon(fn (string $state): ?string => $state === Activity::RISK_HIGH ? 'heroicon-o-exclamation-triangle' : null)
                    ->color('danger')
                    ->tooltip(fn (string $state): ?string => $state === Activity::RISK_HIGH ? 'High-risk change' : null)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('channel')
                    ->formatStateUsing(fn (Activity $record): string => $record->channelLabel())
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('causer')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => User::query()
                                ->where(fn (Builder $q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->all())
                            ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['user_id'] ?? null,
                        fn (Builder $q, $userId) => $q->where('causer_type', (new User)->getMorphClass())->where('causer_id', $userId),
                    ))
                    ->indicateUsing(fn (array $data): ?string => filled($data['user_id'] ?? null) ? 'User: '.User::find($data['user_id'])?->name : null),
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Module')
                    ->multiple()
                    ->options(fn (): array => self::distinctOptions('log_name')),
                Tables\Filters\SelectFilter::make('event')
                    ->multiple()
                    ->options(fn (): array => self::distinctOptions('event')),
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Record type')
                    ->multiple()
                    ->options(fn (): array => collect(self::distinctOptions('subject_type'))
                        ->mapWithKeys(fn ($label, $type) => [$type => Str::headline(class_basename($type))])
                        ->sort()
                        ->all()),
                Tables\Filters\SelectFilter::make('channel')
                    ->options([
                        'admin' => 'Admin panel',
                        'client' => 'Client portal',
                        'investor' => 'Investor portal',
                        'api' => 'Mobile app / API',
                        'webhook' => 'Webhook',
                        'queue' => 'Background job',
                        'console' => 'Scheduled / console',
                        'web' => 'Website',
                    ]),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(fn (): array => Branch::query()->orderBy('name')->pluck('name', 'id')->all()),
                Tables\Filters\Filter::make('high_risk')
                    ->label('High-risk changes only')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('risk', Activity::RISK_HIGH)),
                Tables\Filters\Filter::make('impersonated')
                    ->label('Done while impersonating')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('impersonator_id')),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('From'),
                        Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators[] = 'From '.\Illuminate\Support\Carbon::parse($data['from'])->format('d M Y');
                        }

                        if ($data['until'] ?? null) {
                            $indicators[] = 'Until '.\Illuminate\Support\Carbon::parse($data['until'])->format('d M Y');
                        }

                        return $indicators;
                    }),
            ])
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->paginated([25, 50, 100]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Change')
                ->schema([
                    Infolists\Components\ViewEntry::make('entry')
                        ->hiddenLabel()
                        ->view('filament.audit.infolist-entry'),
                ]),
            Infolists\Components\Section::make('Where it came from')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('actor')
                        ->label('Done by')
                        ->getStateUsing(fn (Activity $record): string => $record->actorLabel()),
                    Infolists\Components\TextEntry::make('impersonator.name')
                        ->label('Impersonated by')
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('channel')
                        ->formatStateUsing(fn (Activity $record): string => $record->channelLabel()),
                    Infolists\Components\TextEntry::make('source')->placeholder('—'),
                    Infolists\Components\TextEntry::make('branch.name')->label('Branch')->placeholder('—'),
                    Infolists\Components\TextEntry::make('created_at')->label('When')->dateTime('d M Y, H:i:s'),
                    Infolists\Components\TextEntry::make('ip_address')->label('IP address')->placeholder('—'),
                    Infolists\Components\TextEntry::make('http_method')->label('Method')->placeholder('—'),
                    Infolists\Components\TextEntry::make('route_name')->label('Route')->placeholder('—'),
                    Infolists\Components\TextEntry::make('url')->label('Page / URL')->placeholder('—')->columnSpanFull()->copyable(),
                    Infolists\Components\TextEntry::make('user_agent')->label('Device / browser')->placeholder('—')->columnSpanFull(),
                ]),
            Infolists\Components\Section::make('Other changes in the same action')
                ->description('Everything recorded by the same click, request or background job.')
                ->collapsible()
                ->visible(fn (Activity $record): bool => filled($record->batch_uuid))
                ->schema([
                    Infolists\Components\ViewEntry::make('batch')
                        ->hiddenLabel()
                        ->view('filament.audit.batch'),
                ]),
        ]);
    }

    /**
     * Where the changed record can be opened in the admin panel, when it has a page.
     */
    public static function subjectUrl(Activity $activity): ?string
    {
        if (blank($activity->subject_type) || blank($activity->subject_id) || $activity->event === 'deleted') {
            return null;
        }

        try {
            $resource = Filament::getModelResource($activity->subject_type);

            if ($resource === null) {
                return null;
            }

            foreach (['view', 'edit'] as $page) {
                if ($resource::hasPage($page)) {
                    return $resource::getUrl($page, ['record' => $activity->subject_id]);
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private static function distinctOptions(string $column): array
    {
        return Activity::query()
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->mapWithKeys(fn ($value) => [$value => Str::headline((string) $value)])
            ->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
}
