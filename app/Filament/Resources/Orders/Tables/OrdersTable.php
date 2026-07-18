<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['items', 'table', 'user']))
            ->columns([
                TextColumn::make('queue_number')
                    ->label('Queue #')
                    ->searchable()
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M, H:i')
                    ->sortable(),

                // Kolom baru: Sumber order (badge Kasir vs QR)
                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'customer_qr' ? 'QR Meja' : 'Kasir')
                    ->color(fn ($state) => $state === 'customer_qr' ? 'info' : 'gray'),

                // Kolom baru: Meja (critical buat peak hour)
                TextColumn::make('table.code')
                    ->label('Meja')
                    ->weight('bold')
                    ->badge()
                    ->color('primary')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('order_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'dine_in' ? 'Dine In' : 'Takeaway')
                    ->color(fn ($state) => $state === 'dine_in' ? 'info' : 'warning'),

                TextColumn::make('items_summary')
                    ->label('Menu')
                    ->getStateUsing(fn ($record) => $record->items
                        ->map(fn ($item) => "{$item->menu_name_snapshot} ×{$item->quantity}")
                        ->join(', '))
                    ->wrap()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->items
                        ->map(fn ($item) => "{$item->menu_name_snapshot} ×{$item->quantity}")
                        ->join(', ')),

                // Gabungan: Cashier name (buat cashier order) atau Customer name (buat QR order)
                TextColumn::make('operator_name')
                    ->label('Nama')
                    ->getStateUsing(fn (Order $record) => $record->source === 'customer_qr'
                        ? ($record->customer_name ?? '—')
                        : ($record->user?->name ?? '—'))
                    ->toggleable(),

                TextColumn::make('total')->money('IDR')->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending_payment' => 'Menunggu Bayar',
                        'paid' => 'Sudah Bayar',
                        'preparing' => 'Sedang Dibuat',
                        'completed' => 'Selesai',
                        'voided' => 'Dibatalkan',
                        'expired' => 'Kadaluarsa',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending_payment' => 'warning',
                        'paid' => 'info',
                        'preparing' => 'primary',
                        'completed' => 'success',
                        'voided' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                // Filter cepet: order yg butuh perhatian kasir (peak hour focus)
                Filter::make('needs_action')
                    ->label('Perlu Aksi')
                    ->query(fn (Builder $query): Builder => $query->whereIn('status', [
                        'pending_payment', 'paid', 'preparing',
                    ])),

                SelectFilter::make('source')
                    ->label('Sumber')
                    ->options([
                        'cashier' => 'Kasir',
                        'customer_qr' => 'QR Meja',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        'pending_payment' => 'Menunggu Bayar',
                        'paid' => 'Sudah Bayar',
                        'preparing' => 'Sedang Dibuat',
                        'completed' => 'Selesai',
                        'voided' => 'Dibatalkan',
                        'expired' => 'Kadaluarsa',
                    ]),

                SelectFilter::make('order_type')
                    ->label('Type')
                    ->options([
                        'dine_in' => 'Dine In',
                        'takeaway' => 'Takeaway',
                    ]),

                Filter::make('today')
                    ->label('Hari ini saja')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),
            ])
            ->recordUrl(fn ($record) => \App\Filament\Resources\Orders\OrderResource::getUrl('view', ['record' => $record]))
            ->defaultSort('created_at', 'desc');
    }
}