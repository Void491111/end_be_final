<?php

namespace App\Filament\Resources\CafeTables;

use App\Filament\Resources\CafeTables\Pages\CreateCafeTable;
use App\Filament\Resources\CafeTables\Pages\EditCafeTable;
use App\Filament\Resources\CafeTables\Pages\ListCafeTables;
use App\Models\Table as CafeTable;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CafeTableResource extends Resource
{
    protected static ?string $model = CafeTable::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?string $navigationLabel = 'Meja & QR';

    protected static ?string $modelLabel = 'Meja';

    protected static ?string $pluralModelLabel = 'Meja';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->label('Kode Meja')
                ->helperText('Contoh: T01, T02. Kode ini di-encode ke QR.')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(20),

            TextInput::make('name')
                ->label('Nama Tampilan')
                ->helperText('Contoh: Meja 1, Meja VIP, Meja Outdoor.')
                ->required()
                ->maxLength(100),

            Toggle::make('is_active')
                ->label('Aktif')
                ->helperText('Non-aktifkan kalau meja lagi tidak dipakai.')
                ->default(true),
        ]);
    }

    /**
     * Bungkus QR mentah jadi kartu ber-branding siap cetak (table tent).
     * QR di-embed sebagai <image> data-URI biar aman lintas renderer.
     */
    protected static function qrCardSvg(CafeTable $record, string $qrSvg, string $url): string
    {
        $qrDataUri = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);

        $code = htmlspecialchars($record->code, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $name = htmlspecialchars($record->name ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8');
        $safeUrl = htmlspecialchars($url, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="440" height="680" viewBox="0 0 440 680" font-family="'Poppins','Segoe UI',system-ui,sans-serif">
  <defs>
    <linearGradient id="amber" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#F59E0B"/>
      <stop offset="1" stop-color="#D97706"/>
    </linearGradient>
    <filter id="soft" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="6" stdDeviation="12" flood-color="#000000" flood-opacity="0.10"/>
    </filter>
  </defs>

  <!-- Card -->
  <rect x="16" y="16" width="408" height="648" rx="26" fill="#FFFFFF" stroke="#F1E7D6" stroke-width="2" filter="url(#soft)"/>

  <!-- Coffee cup (centered) -->
  <g transform="translate(198,50)">
    <rect x="0" y="6" width="44" height="34" rx="9" fill="url(#amber)"/>
    <path d="M44 12 h7 a11 11 0 0 1 0 22 h-5" fill="none" stroke="#D97706" stroke-width="5" stroke-linecap="round"/>
    <path d="M12 -2 q5 -7 0 -14" fill="none" stroke="#F59E0B" stroke-width="4" stroke-linecap="round"/>
    <path d="M26 -2 q5 -7 0 -14" fill="none" stroke="#F59E0B" stroke-width="4" stroke-linecap="round"/>
  </g>
  <text x="220" y="128" text-anchor="middle" fill="#B45309" font-size="27" font-weight="700" letter-spacing="1">MOOISTE CAFE</text>
  <text x="220" y="150" text-anchor="middle" fill="#9A8C74" font-size="11" font-weight="600" letter-spacing="3">SELF-ORDER · SCAN &amp; PESAN</text>
  <line x1="150" y1="168" x2="290" y2="168" stroke="#F0E4D2" stroke-width="2"/>

  <!-- Table identity -->
  <text x="220" y="208" text-anchor="middle" fill="#B45309" font-size="14" font-weight="700" letter-spacing="6">MEJA</text>
  <text x="220" y="274" text-anchor="middle" fill="#1F2937" font-size="66" font-weight="800">{$code}</text>
  <text x="220" y="302" text-anchor="middle" fill="#6B7280" font-size="17" font-weight="500">{$name}</text>

  <!-- QR panel -->
  <rect x="95" y="326" width="250" height="250" rx="22" fill="#FFFFFF" stroke="#EFE7DA" stroke-width="2" filter="url(#soft)"/>
  <image x="110" y="341" width="220" height="220" href="{$qrDataUri}" xlink:href="{$qrDataUri}"/>

  <!-- Instruction -->
  <text x="220" y="612" text-anchor="middle" fill="#1F2937" font-size="17" font-weight="700">Scan untuk pesan &amp; bayar dari meja</text>

  <!-- Steps -->
  <g font-size="12" font-weight="600">
    <g transform="translate(118,634)"><circle r="11" fill="url(#amber)"/><text x="0" y="4" text-anchor="middle" fill="#FFFFFF">1</text><text x="19" y="4" fill="#6B7280">Scan</text></g>
    <g transform="translate(210,634)"><circle r="11" fill="url(#amber)"/><text x="0" y="4" text-anchor="middle" fill="#FFFFFF">2</text><text x="19" y="4" fill="#6B7280">Pilih menu</text></g>
    <g transform="translate(322,634)"><circle r="11" fill="url(#amber)"/><text x="0" y="4" text-anchor="middle" fill="#FFFFFF">3</text><text x="19" y="4" fill="#6B7280">Bayar</text></g>
  </g>
</svg>
SVG;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('qr_generated_at')
                    ->label('QR Terakhir Dibuat')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Belum pernah')
                    ->sortable(),

                TextColumn::make('orders_count')
                    ->label('Total Pesanan')
                    ->counts('orders')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('code')
            ->recordActions([
                Action::make('downloadQr')
                    ->label('Download QR')
                    ->icon(Heroicon::OutlinedQrCode)
                    ->color('success')
                    ->action(function (CafeTable $record) {
                        $url = $record->order_url;

                        $qrSvg = QrCode::format('svg')
                            ->size(220)
                            ->margin(1)
                            ->errorCorrection('H')
                            ->color(31, 41, 55)
                            ->generate($url);

                        $record->update(['qr_generated_at' => now()]);

                        $card = static::qrCardSvg($record, $qrSvg, $url);
                        $filename = 'qr-meja-' . $record->code . '.svg';

                        return response()->streamDownload(
                            fn () => print($card),
                            $filename,
                            ['Content-Type' => 'image/svg+xml']
                        );
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCafeTables::route('/'),
            'create' => CreateCafeTable::route('/create'),
            'edit' => EditCafeTable::route('/{record}/edit'),
        ];
    }
}