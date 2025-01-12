<?php

namespace App\Filament\Pages\Auth;

use App\Models\Branch;
use App\Models\Team;
use App\Models\User;
use App\Services\StartUpService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Support\Str;

class RegisterBranch extends RegisterTenant
{

    public static function getLabel(): string
    {
        return 'Register branch';
    }

    public function form(Form $form): Form
    {
        return $form
        ->schema([

            Forms\Components\Section::make('')
                ->description('')
                ->schema([
                    Forms\Components\TextInput::make('name')
                    ->label('Branch name')
                    ->required()
                    ->live(onBlur:true)
                    ->columnSpan(2)
                    ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state))),

                    Forms\Components\Hidden::make('slug'),

                    Forms\Components\TextInput::make('email'),

                    Forms\Components\TextInput::make('phone'),

                    Forms\Components\TextInput::make('country'),

                    Forms\Components\TextInput::make('state')
                    ->required(),

                    Forms\Components\Textarea::make('address')
                    ->columnSpan(2)
                    ->required(),

                ])
                ->columns(2),


        ]);
    }

    protected function handleRegistration(array $data): Branch
    {
        $team = Branch::create($data);

        $user = User::where('id',auth()->user()->id)->first();

        $user->branches()->attach($team->id);

        return $team;

        //assign this role for all admin.
        $adminUsers = User::whereHas('roles',function($query){
            $query->where('name','super_admin');
        })
        ->get();

        foreach ($adminUsers as $admin) {
            $admin->branches()->attach($team->id);
        }

        return $team;
    }
}
