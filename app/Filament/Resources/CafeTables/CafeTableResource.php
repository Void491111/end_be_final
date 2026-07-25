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
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="600" height="920" viewBox="0 0 600 920" font-family="'Poppins','Segoe UI',system-ui,sans-serif">
  <defs>
    <linearGradient id="amber" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#F59E0B"/>
      <stop offset="1" stop-color="#D97706"/>
    </linearGradient>
    <filter id="soft" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="8" stdDeviation="14" flood-color="#000000" flood-opacity="0.10"/>
    </filter>
  </defs>

  <!-- Card -->
  <rect x="20" y="20" width="560" height="880" rx="34" fill="#FFFFFF" stroke="#F1E7D6" stroke-width="2" filter="url(#soft)"/>

  <!-- Header band -->
  <rect x="44" y="46" width="512" height="128" rx="24" fill="url(#amber)"/>
  <g transform="translate(96,86)">
    <rect x="0" y="6" width="44" height="34" rx="9" fill="#FFFFFF"/>
    <path d="M44 12 h7 a11 11 0 0 1 0 22 h-5" fill="none" stroke="#FFFFFF" stroke-width="5" stroke-linecap="round"/>
    <path d="M12 -2 q5 -7 0 -14" fill="none" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round" opacity="0.85"/>
    <path d="M26 -2 q5 -7 0 -14" fill="none" stroke="#FFFFFF" stroke-width="4" stroke-linecap="round" opacity="0.85"/>
  </g>
  <text x="176" y="104" fill="#FFFFFF" font-size="34" font-weight="700" letter-spacing="1">MOOISTE CAFE</text>
  <text x="176" y="136" fill="#FFF7ED" font-size="16" font-weight="600" letter-spacing="3">SELF-ORDER · SCAN &amp; PESAN</text>

  <!-- Table identity -->
  <text x="300" y="232" text-anchor="middle" fill="#B45309" font-size="18" font-weight="700" letter-spacing="6">MEJA</text>
  <text x="300" y="322" text-anchor="middle" fill="#1F2937" font-size="88" font-weight="800" letter-spacing="1">{$code}</text>
  <text x="300" y="356" text-anchor="middle" fill="#6B7280" font-size="22" font-weight="500">{$name}</text>

  <!-- QR panel -->
  <rect x="100" y="384" width="400" height="400" rx="30" fill="#FFFFFF" stroke="#EFE7DA" stroke-width="2" filter="url(#soft)"/>
  <image x="120" y="404" width="360" height="360" href="{$qrDataUri}" xlink:href="{$qrDataUri}"/>

  <!-- Instruction -->
  <text x="300" y="824" text-anchor="middle" fill="#1F2937" font-size="23" font-weight="700">Scan untuk pesan &amp; bayar dari meja</text>

  <!-- Steps -->
  <g font-size="15" font-weight="600">
    <g transform="translate(150,852)">
      <circle cx="0" cy="0" r="13" fill="url(#amber)"/>
      <text x="0" y="5" text-anchor="middle" fill="#FFFFFF">1</text>
      <text x="24" y="5" fill="#6B7280">Scan</text>
    </g>
    <g transform="translate(275,852)">
      <circle cx="0" cy="0" r="13" fill="url(#amber)"/>
      <text x="0" y="5" text-anchor="middle" fill="#FFFFFF">2</text>
      <text x="24" y="5" fill="#6B7280">Pilih menu</text>
    </g>
    <g transform="translate(430,852)">
      <circle cx="0" cy="0" r="13" fill="url(#amber)"/>
      <text x="0" y="5" text-anchor="middle" fill="#FFFFFF">3</text>
      <text x="24" y="5" fill="#6B7280">Bayar</text>
    </g>
  </g>

  <!-- Footer url -->
  <text x="300" y="890" text-anchor="middle" fill="#B8B0A2" font-size="12">{$safeUrl}</text>
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
                            ->size(360)
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