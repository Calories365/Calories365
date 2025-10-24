<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->label('Email address')->searchable(),
                TextColumn::make('premium_until')->dateTime()->sortable(),
                TextColumn::make('email_verified_at')->dateTime()->sortable(),
                TextColumn::make('calories_limit')->numeric()->sortable(),
                TextColumn::make('two_factor_confirmed_at')->dateTime()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('telegram_id')->numeric()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('makePremium')
                    ->label('Сделать премиум')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->premium_until = now()->addMonth();
                        $record->save();
                    }),
            ])

            ->toolbarActions([
                BulkAction::make('delete')
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => dd($records))
            ])
            ->selectable();
    }
}
