<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('queue_number'),
                TextEntry::make('user_id')
                    ->numeric(),
                TextEntry::make('order_type'),
                TextEntry::make('subtotal')
                    ->numeric(),
                TextEntry::make('tax')
                    ->numeric(),
                TextEntry::make('total')
                    ->numeric(),
                TextEntry::make('payment_method'),
                TextEntry::make('status'),
                TextEntry::make('voided_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('voided_reason')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
