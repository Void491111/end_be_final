<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->minLength(8)
                    ->maxLength(255)
                    ->helperText(fn (string $operation): string => 
                        $operation === 'edit' ? 'Kosongin kalo ga mau ganti password' : 'Min 8 karakter'
                    ),
                Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'cashier' => 'Cashier',
                    ])
                    ->default('cashier')
                    ->required()
                    ->native(false),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Disable buat suspend akun tanpa hapus'),
            ]);
    }
}