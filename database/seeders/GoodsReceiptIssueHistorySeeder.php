<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GoodsReceiptIssueHistorySeeder extends Seeder
{
    private const DESCRIPTION_PREFIX = '[DUMMY-GR-GI-2025-2026]';

    public function run(): void
    {
        DB::transaction(function () {
            $user = User::query()
                ->where('bidang', 'teknik')
                ->where('account_status', 'approved')
                ->whereIn('role', ['admin', 'manajer'])
                ->orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")
                ->first();

            if (!$user) {
                throw new RuntimeException('Seeder membutuhkan minimal satu admin atau manajer teknik yang aktif.');
            }

            $items = Item::query()
                ->where('bidang', 'teknik')
                ->orderBy('id')
                ->limit(5)
                ->get();

            if ($items->count() < 5) {
                throw new RuntimeException('Seeder membutuhkan minimal 5 barang teknik berbeda.');
            }

            Transaction::query()
                ->where('description', 'like', self::DESCRIPTION_PREFIX . '%')
                ->delete();

            $existingStocks = $items->mapWithKeys(fn(Item $item) => [
                $item->id => $this->approvedStock($item),
            ]);

            $rows = [];
            $monthIndex = 0;

            for ($year = 2025; $year <= 2026; $year++) {
                for ($month = 1; $month <= 12; $month++) {
                    foreach ($items as $itemIndex => $item) {
                        $issueQuantity = 6 + (($monthIndex + $itemIndex) % 5);
                        $receiptQuantity = $issueQuantity + 12 + (($monthIndex + $itemIndex) % 7);

                        if ($monthIndex === 0 && $existingStocks[$item->id] < 0) {
                            $receiptQuantity += abs($existingStocks[$item->id]);
                        }

                        $receiptDate = Carbon::create($year, $month, 5, 9);
                        $issueDate = Carbon::create($year, $month, 20, 14);
                        $shipUnloader = (string) (($itemIndex % 4) + 1);

                        $rows[] = $this->transactionRow(
                            $item,
                            $user,
                            $receiptDate,
                            'in',
                            $receiptQuantity,
                            $shipUnloader,
                            'Goods Receipt'
                        );
                        $rows[] = $this->transactionRow(
                            $item,
                            $user,
                            $issueDate,
                            'out',
                            $issueQuantity,
                            $shipUnloader,
                            'Goods Issued'
                        );
                    }

                    $monthIndex++;
                }
            }

            DB::table('transactions')->insert($rows);

            $negativeItems = $items
                ->filter(fn(Item $item) => $this->approvedStock($item) < 0)
                ->pluck('name');

            if ($negativeItems->isNotEmpty()) {
                throw new RuntimeException('Seeder dibatalkan karena stok minus: ' . $negativeItems->implode(', '));
            }
        });

        $this->command?->info('Berhasil membuat 240 transaksi dummy: 120 Goods Receipt dan 120 Goods Issued untuk Januari 2025 - Desember 2026.');
    }

    private function approvedStock(Item $item): int
    {
        $receipt = Transaction::query()
            ->where('item_id', $item->id)
            ->approved()
            ->masuk()
            ->sum('quantity');
        $issued = Transaction::query()
            ->where('item_id', $item->id)
            ->approved()
            ->keluar()
            ->sum('quantity');

        return (int) $receipt - (int) $issued;
    }

    private function transactionRow(
        Item $item,
        User $user,
        Carbon $date,
        string $type,
        int $quantity,
        string $shipUnloader,
        string $label
    ): array {
        return [
            'item_id' => $item->id,
            'user_id' => $user->id,
            'bidang' => 'teknik',
            'no_normalisasi' => $item->no_normalisasi,
            'lokasi' => $item->lokasi,
            'volume' => $item->volume,
            'ship_unloader' => $shipUnloader,
            'date' => $date->toDateString(),
            'type' => $type,
            'quantity' => $quantity,
            'price' => null,
            'description' => self::DESCRIPTION_PREFIX . ' ' . $label,
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => $date->copy()->addMinutes(30),
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }
}
