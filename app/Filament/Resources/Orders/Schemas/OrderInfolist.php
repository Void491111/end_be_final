<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('queue_number')->label('Queue #')->weight('bold'),
            TextEntry::make('status')->badge()->color(fn (string $state): string => match ($state) {
                'completed' => 'success',
                'voided' => 'danger',
                default => 'gray',
            }),
            TextEntry::make('order_type')->label('Type')->badge()
                ->formatStateUsing(fn ($state) => $state === 'dine_in' ? 'Dine In' : 'Takeaway'),
            TextEntry::make('user.name')->label('Cashier'),
            TextEntry::make('subtotal')->money('IDR'),
            TextEntry::make('tax')->money('IDR'),
            TextEntry::make('total')->money('IDR')->weight('bold'),
            TextEntry::make('payment_method')->label('Payment'),
            TextEntry::make('created_at')->dateTime('d M Y, H:i'),
            TextEntry::make('voided_at')->dateTime('d M Y, H:i')
                ->visible(fn ($record) => $record?->status === 'voided'),
            TextEntry::make('voided_reason')
                ->visible(fn ($record) => $record?->status === 'voided')
                ->columnSpanFull(),

            // Tambahan baru: daftar menu
            RepeatableEntry::make('items')
                ->label('Daftar Menu')
                ->columns(3)
                ->schema([
                    TextEntry::make('menu_name_snapshot')->label('Menu')->weight('semibold'),
                    TextEntry::make('quantity')->label('Qty')->prefix('× '),
                    TextEntry::make('subtotal')->label('Total')->money('IDR'),
                ])
                ->columnSpanFull(),
        ]);
    }
}