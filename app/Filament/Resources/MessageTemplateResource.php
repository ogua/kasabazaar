<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\MessageTemplate;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\MessageTemplateResource\Pages;

class MessageTemplateResource extends Resource
{
    protected static ?string $model = MessageTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Messaging';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Template Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Template Name')
                        ->required()
                        ->placeholder('e.g., Shipment Confirmation')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('subject')
                        ->label('Email Subject')
                        ->required()
                        ->placeholder('e.g., Your Shipment {{tracking_number}} Has Been Created')
                        ->helperText('You can use placeholders like {{tracking_number}}, {{client_name}}')
                        ->maxLength(255),

                    Forms\Components\Select::make('type')
                        ->label('Message Type')
                        ->options([
                            'email' => 'Email Only',
                            'sms' => 'SMS Only',
                            'both' => 'Both Email & SMS',
                        ])
                        ->default('email')
                        ->required()
                        ->native(false),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Inactive templates will not be available for use'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Message Body')
                ->schema([
                    Forms\Components\RichEditor::make('body')
                        ->label('Message Content')
                        ->required()
                        ->placeholder('Write your message here...')
                        ->helperText('Use placeholders to personalize the message')
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make('placeholders_info')
                        ->label('Available Placeholders')
                        ->content(function () {
                            $placeholders = MessageTemplate::$placeholders;
                            $html = '<div class="grid grid-cols-2 gap-2 text-sm">';
                            foreach ($placeholders as $placeholder => $description) {
                                $html .= "<div><code class='bg-gray-100 px-1 rounded'>{$placeholder}</code> - {$description}</div>";
                            }
                            $html .= '</div>';
                            return new \Illuminate\Support\HtmlString($html);
                        })
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Template Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Subject')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'email',
                        'success' => 'sms',
                        'warning' => 'both',
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'email' => 'Email',
                        'sms' => 'SMS',
                        'both' => 'Both',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessageTemplates::route('/'),
            'create' => Pages\CreateMessageTemplate::route('/create'),
            'edit' => Pages\EditMessageTemplate::route('/{record}/edit'),
        ];
    }
}
