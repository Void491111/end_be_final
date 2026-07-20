<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tombol Edit cuma muncul buat order QR (butuh transisi status).
            // Order kasir langsung completed = immutable, gak ada yg diedit.
            EditAction::make()
                ->visible(fn ($record) => $record->source === 'customer_qr'),
        ];
    }
}