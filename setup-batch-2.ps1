# =========================================================
# Mooiste Cafe — Batch 2 Auto Setup
# =========================================================

$ErrorActionPreference = "Stop"

Write-Host "`n>>> [1/8] Verifying project root..." -ForegroundColor Cyan
if (!(Test-Path "artisan")) {
    Write-Host "ERROR: Bukan folder Laravel. Pindah ke root project dulu." -ForegroundColor Red
    exit 1
}

Write-Host "`n>>> [2/8] Creating directories..." -ForegroundColor Cyan
New-Item -ItemType Directory -Force -Path "app\Filament\Resources\CafeTables\Pages" | Out-Null
Write-Host "OK folders created" -ForegroundColor Green

Write-Host "`n>>> [3/8] Writing CafeTableResource.php..." -ForegroundColor Cyan
$cafeTableResource = @'
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
                            ->size(400)
                            ->margin(2)
                            ->errorCorrection('H')
                            ->generate($url);

                        $record->update(['qr_generated_at' => now()]);

                        $filename = 'qr-' . $record->code . '.svg';

                        return response()->streamDownload(
                            fn () => print($qrSvg),
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
'@
Set-Content -Path "app\Filament\Resources\CafeTables\CafeTableResource.php" -Value $cafeTableResource -Encoding UTF8
Write-Host "OK CafeTableResource.php" -ForegroundColor Green

Write-Host "`n>>> [4/8] Writing Page files..." -ForegroundColor Cyan

$listPage = @'
<?php

namespace App\Filament\Resources\CafeTables\Pages;

use App\Filament\Resources\CafeTables\CafeTableResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCafeTables extends ListRecords
{
    protected static string $resource = CafeTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah Meja'),
        ];
    }
}
'@
Set-Content -Path "app\Filament\Resources\CafeTables\Pages\ListCafeTables.php" -Value $listPage -Encoding UTF8
Write-Host "OK ListCafeTables.php" -ForegroundColor Green

$createPage = @'
<?php

namespace App\Filament\Resources\CafeTables\Pages;

use App\Filament\Resources\CafeTables\CafeTableResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCafeTable extends CreateRecord
{
    protected static string $resource = CafeTableResource::class;
}
'@
Set-Content -Path "app\Filament\Resources\CafeTables\Pages\CreateCafeTable.php" -Value $createPage -Encoding UTF8
Write-Host "OK CreateCafeTable.php" -ForegroundColor Green

$editPage = @'
<?php

namespace App\Filament\Resources\CafeTables\Pages;

use App\Filament\Resources\CafeTables\CafeTableResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCafeTable extends EditRecord
{
    protected static string $resource = CafeTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
'@
Set-Content -Path "app\Filament\Resources\CafeTables\Pages\EditCafeTable.php" -Value $editPage -Encoding UTF8
Write-Host "OK EditCafeTable.php" -ForegroundColor Green

Write-Host "`n>>> [5/8] Writing TableSeeder.php..." -ForegroundColor Cyan
$tableSeeder = @'
<?php

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $code = 'T' . str_pad($i, 2, '0', STR_PAD_LEFT);

            Table::updateOrCreate(
                ['code' => $code],
                [
                    'name' => 'Meja ' . $i,
                    'is_active' => true,
                ]
            );
        }
    }
}
'@
Set-Content -Path "database\seeders\TableSeeder.php" -Value $tableSeeder -Encoding UTF8
Write-Host "OK TableSeeder.php" -ForegroundColor Green

Write-Host "`n>>> [6/8] Replacing OrderController.php..." -ForegroundColor Cyan
$orderController = @'
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_type' => ['required', Rule::in(['dine_in', 'takeaway'])],
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|exists:menus,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $subtotal = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $menu = Menu::find($item['menu_id']);

                if (! $menu->is_available) {
                    abort(422, "{$menu->name} sedang tidak tersedia.");
                }

                $itemSubtotal = $menu->price * $item['quantity'];
                $subtotal += $itemSubtotal;

                $itemsData[] = [
                    'menu_id' => $menu->id,
                    'menu_name_snapshot' => $menu->name,
                    'price_snapshot' => $menu->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $itemSubtotal,
                ];
            }

            $tax = $subtotal * 0.10;
            $total = $subtotal + $tax;

            $order = Order::create([
                'queue_number' => Order::generateQueueNumber(),
                'user_id' => $request->user()->id,
                'source' => 'cashier',
                'table_id' => null,
                'order_type' => $validated['order_type'],
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'payment_method' => 'cash',
                'payment_status' => 'settlement',
                'paid_at' => now(),
                'status' => 'completed',
            ]);

            $order->items()->createMany($itemsData);

            return response()->json($order->load('items', 'user:id,name'), 201);
        });
    }

    public function index(Request $request)
    {
        $query = Order::with(['items', 'user:id,name', 'table:id,code,name'])
            ->orderBy('created_at', 'desc');

        if ($period = $request->input('period')) {
            $this->applyPeriodFilter($query, $period);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($source = $request->input('source')) {
            $query->where('source', $source);
        }

        if ($search = $request->input('search')) {
            $query->where('queue_number', 'like', "%{$search}%");
        }

        return $query->paginate(20);
    }

    public function show(Order $order)
    {
        return $order->load(['items', 'user:id,name', 'table:id,code,name']);
    }

    public function void(Request $request, Order $order)
    {
        $validated = $request->validate([
            'voided_reason' => 'required|string|min:3',
        ]);

        if (in_array($order->status, ['voided', 'expired'])) {
            abort(422, 'Order sudah tidak aktif.');
        }

        $order->update([
            'status' => 'voided',
            'voided_at' => now(),
            'voided_reason' => $validated['voided_reason'],
        ]);

        return response()->json($order->fresh('items'));
    }

    public function stats(Request $request)
    {
        $query = Order::query();

        if ($period = $request->input('period', 'today')) {
            $this->applyPeriodFilter($query, $period);
        }

        $completed = (clone $query)->where('status', 'completed');
        $voided = (clone $query)->where('status', 'voided');

        return response()->json([
            'total_orders' => $completed->count(),
            'total_revenue' => (float) $completed->sum('total'),
            'avg_order' => (float) ($completed->avg('total') ?? 0),
            'voided_count' => $voided->count(),
            'voided_amount' => (float) $voided->sum('total'),
        ]);
    }

    private function applyPeriodFilter($query, string $period): void
    {
        match ($period) {
            'today' => $query->whereDate('created_at', today()),
            '7d' => $query->where('created_at', '>=', now()->subDays(7)),
            '30d' => $query->where('created_at', '>=', now()->subDays(30)),
            '90d' => $query->where('created_at', '>=', now()->subDays(90)),
            default => null,
        };
    }
}
'@
Set-Content -Path "app\Http\Controllers\Api\OrderController.php" -Value $orderController -Encoding UTF8
Write-Host "OK OrderController.php" -ForegroundColor Green

Write-Host "`n>>> [7/8] Installing simple-qrcode package..." -ForegroundColor Cyan
composer require simplesoftwareio/simple-qrcode

Write-Host "`n>>> [8/8] Running seeder + clearing cache..." -ForegroundColor Cyan
php artisan db:seed --class=TableSeeder
php artisan filament:optimize-clear
composer dump-autoload

Write-Host "`n=========================================================" -ForegroundColor Green
Write-Host "  DONE! Batch 2 setup selesai." -ForegroundColor Green
Write-Host "=========================================================" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "  1. Restart 'php artisan serve' (Ctrl+C, gas lagi)"
Write-Host "  2. Buka http://127.0.0.1:8000/alif — login"
Write-Host "  3. Cek sidebar: menu 'Meja & QR' harus muncul"
Write-Host "  4. Klik menu itu — harus muncul 10 meja T01-T10"
Write-Host "  5. Klik 'Download QR' - SVG ke-download"
Write-Host ""