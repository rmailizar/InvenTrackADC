<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ======================
        // USERS (ANTI DUPLIKAT)
        // ======================
        $admin = User::firstOrCreate(
            ['email' => 'admin@inventory.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'no_hp' => '081234567890',
                'account_status' => 'approved',
            ]
        );

        $manager = User::firstOrCreate(
            ['email' => 'manager@inventory.com'],
            [
                'name' => 'Manager Gudang',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'no_hp' => '081234567891',
                'account_status' => 'approved',
            ]
        );

        $staff1 = User::firstOrCreate(
            ['email' => 'budi@inventory.com'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'no_hp' => '081234567892',
                'account_status' => 'approved',
            ]
        );

        $staff2 = User::firstOrCreate(
            ['email' => 'siti@inventory.com'],
            [
                'name' => 'Siti Nurhaliza',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'no_hp' => '081234567893',
                'account_status' => 'approved',
            ]
        );

        User::firstOrCreate(
            ['email' => 'andi@inventory.com'],
            [
                'name' => 'Andi Prasetyo',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'no_hp' => '081234567894',
                'account_status' => 'pending',
            ]
        );

        // ======================
        // ITEMS (ANTI DUPLIKAT)
        // ======================
        $items = [
            ['name' => 'Kertas HVS A4 70gr', 'category' => 'ATK', 'unit' => 'Rim', 'min_stock' => 10],
            ['name' => 'Tinta Printer HP Black', 'category' => 'ATK', 'unit' => 'Botol', 'min_stock' => 5],
            ['name' => 'Pulpen Pilot BPS-GP', 'category' => 'ATK', 'unit' => 'Lusin', 'min_stock' => 3],
            ['name' => 'Spidol Snowman Board Marker', 'category' => 'ATK', 'unit' => 'Pcs', 'min_stock' => 10],
            ['name' => 'Map Plastik L Folder', 'category' => 'ATK', 'unit' => 'Pcs', 'min_stock' => 20],
            ['name' => 'Monitor LED 24 inch', 'category' => 'Elektronik', 'unit' => 'Unit', 'min_stock' => 2],
            ['name' => 'Keyboard Logitech K120', 'category' => 'Elektronik', 'unit' => 'Unit', 'min_stock' => 5],
            ['name' => 'Mouse Wireless Logitech', 'category' => 'Elektronik', 'unit' => 'Unit', 'min_stock' => 5],
            ['name' => 'Kabel LAN Cat6 305m', 'category' => 'Elektronik', 'unit' => 'Box', 'min_stock' => 2],
            ['name' => 'Meja Kerja 120x60cm', 'category' => 'Furnitur', 'unit' => 'Unit', 'min_stock' => 2],
            ['name' => 'Kursi Kantor Ergonomis', 'category' => 'Furnitur', 'unit' => 'Unit', 'min_stock' => 3],
            ['name' => 'Rak Besi Serbaguna', 'category' => 'Furnitur', 'unit' => 'Unit', 'min_stock' => 2],
            ['name' => 'Sabun Cuci Tangan 500ml', 'category' => 'Kebersihan', 'unit' => 'Botol', 'min_stock' => 10],
            ['name' => 'Tisu Gulung Besar', 'category' => 'Kebersihan', 'unit' => 'Roll', 'min_stock' => 15],
            ['name' => 'Sapu Lantai', 'category' => 'Kebersihan', 'unit' => 'Pcs', 'min_stock' => 5],
        ];

        foreach ($items as $itemData) {
            Item::firstOrCreate(
                ['name' => $itemData['name']],
                $itemData
            );
        }

        // ======================
        // TRANSAKSI (OPSIONAL ANTI DUPLIKAT)
        // ======================
        $allItems = Item::all();
        $staffUsers = [$staff1, $staff2];
        $now = Carbon::now();

        for ($month = 5; $month >= 0; $month--) {
            $date = $now->copy()->subMonths($month);

            foreach ($allItems->random(rand(5, 10)) as $item) {
                $trxDate = $date->copy()->addDays(rand(1, 28));

                Transaction::firstOrCreate([
                    'item_id' => $item->id,
                    'date' => $trxDate->format('Y-m-d'), // ✅ FIX
                    'type' => 'masuk',
                ], [
                    'user_id' => $staffUsers[array_rand($staffUsers)]->id,
                    'quantity' => rand(10, 100),
                    'description' => 'Pengadaan rutin bulan ' . $date->translatedFormat('F Y'),
                    'status' => 'approved',
                    'approved_by' => $admin->id,
                    'approved_at' => $trxDate->copy()->addDays(rand(1, 5)),
                ]);
            }

            foreach ($allItems->random(rand(3, 7)) as $item) {
                $trxDate = $date->copy()->addDays(rand(1, 28));

                Transaction::firstOrCreate([
                    'item_id' => $item->id,
                    'date' => $trxDate->format('Y-m-d'), // ✅ FIX
                    'type' => 'keluar',
                ], [
                    'user_id' => $staffUsers[array_rand($staffUsers)]->id,
                    'quantity' => rand(2, 30),
                    'description' => 'Pemakaian operasional bulan ' . $date->translatedFormat('F Y'),
                    'status' => 'approved',
                    'approved_by' => $admin->id,
                    'approved_at' => $trxDate->copy()->addDays(rand(1, 5)),
                ]);
            }
        }
    }
}