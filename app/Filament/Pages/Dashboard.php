<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;

class Dashboard extends BaseDashboard
{
    use HasFiltersAction;

    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->schema([
                    Select::make('period')
                        ->label('Periode')
                        ->options([
                            'today' => 'Hari Ini',
                            '7d'    => '7 Hari Terakhir',
                            '30d'   => '30 Hari Terakhir',
                            'all'   => 'Semua Waktu',
                        ])
                        ->default('today')
                        ->selectablePlaceholder(false),
                ]),
        ];
    }
}
