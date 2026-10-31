<?php

namespace App\Filament\Resources;

use App\Enums\AppPlatform;
use App\Enums\MobileApp;
use App\Filament\Resources\AppVersionResource\Pages;
use App\Models\AppVersion;
use App\Services\AppUpdatePolicy;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

/**
 * Store releases the mobile apps check against (GET /api/v1/app/update-check).
 * One row per app, platform and release. Older builds see an "update available"
 * prompt; builds below the floor (a forced release or a minimum supported
 * version) are blocked until they update.
 */
class AppVersionResource extends Resource
{
    protected static ?string $model = AppVersion::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Mobile App Releases';

    protected static ?string $modelLabel = 'app release';

    protected static ?int $navigationSort = 98;

    protected static ?string $recordTitleAttribute = 'version';

    protected static bool $isScopedToTenant = false;

    private const VERSION_PATTERN = '/^\d+\.\d+\.\d+$/';

    public static function form(Form $form): Form
    {
        $scopeToAppAndPlatform = fn (Unique $rule, Get $get): Unique => $rule
            ->where('app', self::enumValue($get('app')))
            ->where('platform', self::enumValue($get('platform')));

        return $form
            ->schema([
                Forms\Components\Section::make('Build')
                    ->schema([
                        Forms\Components\Select::make('app')
                            ->options(MobileApp::class)
                            ->default(MobileApp::Client)
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('platform')
                            ->options(AppPlatform::class)
                            ->default(AppPlatform::Android)
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('version')
                            ->placeholder('1.0.8')
                            ->helperText('Must match expo.version in the app\'s app.json.')
                            ->required()
                            ->maxLength(20)
                            ->regex(self::VERSION_PATTERN)
                            ->validationMessages(['regex' => 'Use major.minor.patch, e.g. 1.0.8.'])
                            ->unique(ignoreRecord: true, modifyRuleUsing: $scopeToAppAndPlatform),
                        Forms\Components\TextInput::make('version_code')
                            ->label('Version code')
                            ->helperText('Increasing whole number; the highest active one is the release apps compare against.')
                            ->required()
                            ->integer()
                            ->minValue(1)
                            ->default(fn (): int => (int) AppVersion::query()->max('version_code') + 1)
                            ->unique(ignoreRecord: true, modifyRuleUsing: $scopeToAppAndPlatform),
                        Forms\Components\TextInput::make('minimum_supported_version')
                            ->label('Minimum supported version')
                            ->placeholder('1.0.0')
                            ->helperText('Builds older than this are blocked until they update. Leave blank for no floor.')
                            ->maxLength(20)
                            ->regex(self::VERSION_PATTERN)
                            ->validationMessages(['regex' => 'Use major.minor.patch, e.g. 1.0.0.'])
                            ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                $version = AppUpdatePolicy::normalize($get('version'));

                                if ($value && $version && version_compare(AppUpdatePolicy::normalize($value) ?? '0.0.0', $version, '>')) {
                                    $fail('The minimum supported version cannot be newer than this release.');
                                }
                            }),
                        Forms\Components\DateTimePicker::make('released_at')
                            ->seconds(false)
                            ->default(now()),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Rollout')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Publish this only once the build is live in the store. Inactive releases are ignored.')
                            ->default(true),
                        Forms\Components\Toggle::make('is_forced_update')
                            ->label('Force update')
                            ->helperText('Every device older than this release is blocked until it updates, even after a newer unforced release.')
                            ->default(false),
                        Forms\Components\TextInput::make('store_url')
                            ->label('Store URL')
                            ->placeholder('https://play.google.com/store/apps/details?id=…')
                            ->helperText('Where the Update button sends users. Leave blank to use the default store listing (config/app_updates.php).')
                            ->url()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('release_notes')
                            ->label('Update message')
                            ->helperText('Shown in the update prompt.')
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('app')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('platform')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('version')
                    ->weight('bold')
                    ->searchable(),
                Tables\Columns\TextColumn::make('version_code')
                    ->label('Code')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('minimum_supported_version')
                    ->label('Minimum')
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('is_forced_update')
                    ->label('Forced')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('gray'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('released_at')
                    ->dateTime('j M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('By')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('app')
                    ->options(MobileApp::class),
                Tables\Filters\SelectFilter::make('platform')
                    ->options(AppPlatform::class),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\Action::make('pull')
                    ->label('Pull release')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Apps stop being offered this release; the next newest active release takes its place.')
                    ->visible(fn (AppVersion $record): bool => $record->is_active)
                    ->authorize(fn (AppVersion $record): bool => auth()->user()->can('update', $record))
                    ->action(function (AppVersion $record): void {
                        $record->update(['is_active' => false]);

                        Notification::make()->title('Release pulled')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('version_code', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppVersions::route('/'),
            'create' => Pages\CreateAppVersion::route('/create'),
            'edit' => Pages\EditAppVersion::route('/{record}/edit'),
        ];
    }

    /**
     * $get() on an enum Select returns the enum once hydrated and a string before.
     */
    private static function enumValue(mixed $state): ?string
    {
        return $state instanceof \BackedEnum ? (string) $state->value : $state;
    }
}
