<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
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
            ->modifyQueryUsing(fn (Builder $query) => $query->with('items'))
            ->columns([
                TextColumn::make('queue_number')->label('Queue #')->searchable()->weight('bold')->sortable(),
                TextColumn::make('created_at')->label('Time')->dateTime('d M, H:i')->sortable(),
                TextColumn::make('order_type')->label('Type')->badge()
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
                TextColumn::make('user.name')->label('Cashier')->toggleable(),
                TextColumn::make('total')->money('IDR')->sortable(),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'completed' => 'success',
                    'voided' => 'danger',
                    default => 'gray',
                }),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'completed' => 'Completed',
                    'voided' => 'Voided',
                ]),
                SelectFilter::make('order_type')->label('Type')->options([
                    'dine_in' => 'Dine In',
                    'takeaway' => 'Takeaway',
                ]),
                Filter::make('today')->label('Today only')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('void')
                    ->label('Void')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Void this order?')
                    ->modalDescription('Voided orders akan di-exclude dari revenue. Action ini ga bisa di-undo.')
                    ->schema([
                        Textarea::make('voided_reason')->label('Alasan void')->required()->rows(3),
                    ])
                    ->action(function (Order $record, array $data) {
                        $record->update([
                            'status' => 'voided',
                            'voided_at' => now(),
                            'voided_reason' => $data['voided_reason'],
                        ]);
                        Notification::make()->title('Order voided')->success()->send();
                    })
                    ->visible(fn (Order $record) => $record->status === 'completed'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}