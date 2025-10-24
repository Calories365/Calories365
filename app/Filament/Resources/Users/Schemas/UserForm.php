<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required(),

            TextInput::make('email')
                ->label('Email address')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),

            DateTimePicker::make('premium_until'),

            DateTimePicker::make('email_verified_at')
                ->disabled()
                ->dehydrated(false),

            TextInput::make('password')
                ->password()
                ->required()
                ->hiddenOn(\Filament\Support\Enums\Operation::Edit)
                ->dehydrated(fn ($state) => filled($state))
                ->dehydrateStateUsing(fn ($state) => \Illuminate\Support\Facades\Hash::make($state)),
            TextInput::make('calories_limit')
                ->numeric(),

            Textarea::make('two_factor_secret')
                ->columnSpanFull()
                ->disabled()
                ->dehydrated(false),

            Textarea::make('two_factor_recovery_codes')
                ->columnSpanFull()
                ->disabled()
                ->dehydrated(false),

            DateTimePicker::make('two_factor_confirmed_at')
                ->disabled()
                ->dehydrated(false),

            TextInput::make('telegram_id')
                ->tel()
                ->numeric(),

            TextInput::make('token'),
        ]);
    }
}
