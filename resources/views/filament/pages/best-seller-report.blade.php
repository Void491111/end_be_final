<x-filament-panels::page>
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Menu yang paling banyak dipesan. Data ini yang jadi dasar fitur rekomendasi otomatis.
        </p>
        <select wire:model.live="period"
                class="rounded-md border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
            <option value="all">Semua Waktu</option>
            <option value="30d">30 Hari Terakhir</option>
            <option value="7d">7 Hari Terakhir</option>
        </select>
    </div>

    @if(count($bestSellers) === 0)
        <div class="rounded-lg bg-gray-50 dark:bg-gray-900 p-8 text-center">
            <p class="text-gray-500">Belum ada data pesanan untuk periode ini.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Rank</th>
                        <th class="px-4 py-3 text-left font-semibold">Menu</th>
                        <th class="px-4 py-3 text-left font-semibold">Kategori</th>
                        <th class="px-4 py-3 text-right font-semibold">Total Terjual</th>
                        <th class="px-4 py-3 text-right font-semibold">Jumlah Order</th>
                        <th class="px-4 py-3 text-right font-semibold">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($bestSellers as $index => $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                            <td class="px-4 py-3">
                                @if($index < 3)
                                    <span class="inline-flex items-center gap-1 rounded-md bg-gradient-to-r from-amber-500 to-orange-500 px-2 py-0.5 text-xs font-bold text-white shadow-sm">
                                        🔥 #{{ $index + 1 }}
                                    </span>
                                @else
                                    <span class="text-gray-500">#{{ $index + 1 }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-semibold">{{ $item['name'] }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $item['category_name'] }}</td>
                            <td class="px-4 py-3 text-right font-mono font-semibold">{{ $item['total_qty'] }}×</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $item['order_count'] }}</td>
                            <td class="px-4 py-3 text-right font-mono">
                                Rp{{ number_format($item['total_revenue'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>