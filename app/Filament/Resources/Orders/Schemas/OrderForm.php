<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('status')
                ->label('Status Pesanan')
                ->options([
                    'pending_payment' => 'Menunggu Pembayaran',
                    'paid' => 'Sudah Dibayar',
                    'preparing' => 'Sedang Dibuat',
                    'completed' => 'Selesai',
                    'voided' => 'Dibatalkan',
                    'expired' => 'Kadaluarsa',
                ])
                ->required()
                ->native(false),
        ]);
    }
}