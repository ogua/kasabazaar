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

            Forms\Components\TextInput::make('name')
            ->label('Branch name')
            ->required()
            ->live(onBlur:true)
            ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state))),

            Forms\Components\Hidden::make('slug'),

            Forms\Components\TextInput::make('location')
            ->required(),
        ]);
    }

    protected function handleRegistration(array $data): Branch
    {
        $team = Branch::create($data);

        $schooladmin = User::where('role',"School_admin_".auth()->user()->school_id)
        ->where('school_id', auth()->user()->school_id)
        ->get();

        foreach ($schooladmin as $user) {
            $user->branches()->attach($team->id);
        }

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
