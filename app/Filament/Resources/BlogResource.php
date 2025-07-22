<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Blog;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\BlogCategory;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\BlogResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BlogResource\RelationManagers;

class BlogResource extends Resource
{
    protected static ?string $model = Blog::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Website';

    protected static ?int $navigationSort = 2;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('')
                ->description('')
                ->schema([
                Forms\Components\FileUpload::make('img')
                ->label('Blog Image')
                //->image()
                ->required()
                ->maxSize(1024)
                ->columnSpanFull()
                ->helperText('Upload a blog image. Recommended size: 1024x768px'),

                Forms\Components\Select::make('cat_id')
                ->label('Category')
                    ->required()
                    ->options(BlogCategory::all()->pluck('cat', 'id'))
                    ->preload()
                    ->searchable(),

                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn($state,$set) => $set('slug', str($state)->slug())),
                
                Forms\Components\Hidden::make('slug'),
                                       
                
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('keywords')
                    ->columnSpanFull(),

                Forms\Components\RichEditor::make('content')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\Select::make('postedby')
                    ->options(User::all()->pluck('name', 'id'))
                    ->preload()
                    ->searchable(),

                Forms\Components\DateTimePicker::make('published_at')
                    ->required(),

                Forms\Components\Toggle::make('status')
                    ->label('Published')
                    ->required()
                    ->default(0)
                    ->live()
                    ->afterStateUpdated(fn($state, $set) => $set('published_at', $state ? now() : null)),
                ])
                ->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('img')
                ,

                Tables\Columns\TextColumn::make('category.cat')
                    ->label('Category')
                    ->searchable(),

                Tables\Columns\TextColumn::make('slug')
                    ->badge(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable(),


                Tables\Columns\TextColumn::make('user.name')
                    ->label('Posted By')
                    ->searchable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('status')
                    ->label('Published')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogs::route('/'),
            'create' => Pages\CreateBlog::route('/create'),
            'edit' => Pages\EditBlog::route('/{record}/edit'),
        ];
    }
}
