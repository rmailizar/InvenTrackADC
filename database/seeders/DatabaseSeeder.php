<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Users (admin, manager are auto-approved)
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@inventory.com',
            'password' => 'password',
            'role' => 'admin',
            'no_hp' => '081234567890',
            'account_status' => 'approved',
        ]);

        $manager = User::create([
            'name' => 'Manager Gudang',
            'email' => 'manager@inventory.com',
            'password' => 'password',
            'role' => 'manager',
            'no_hp' => '081234567891',
            'account_status' => 'approved',
        ]);

        $staff1 = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@inventory.com',
            'password' => 'password',
            'role' => 'staff',
            'no_hp' => '081234567892',
            'account_status' => 'approved',
        ]);

        $staff2 = User::create([
            'name' => 'Siti Nurhaliza',
            'email' => 'siti@inventory.com',
            'password' => 'password',
            'role' => 'staff',
            'no_hp' => '081234567893',
            'account_status' => 'approved',
        ]);

        // Create a pending user for testing
        User::create([
            'name' => 'Andi Prasetyo',
            'email' => 'andi@inventory.com',
            'password' => 'password',
            'role' => 'staff',
            'no_hp' => '081234567894',
            'account_status' => 'pending',
        ]);

        // Create Items
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
            Item::create($itemData);
        }

        // Create Sample Transactions (last 6 months)
        $allItems = Item::all();
        $staffUsers = [$staff1, $staff2];
        $now = Carbon::now();

        for ($month = 5; $month >= 0; $month--) {
            $date = $now->copy()->subMonths($month);

            foreach ($allItems->random(rand(5, 10)) as $item) {
                // Masuk transaction
                Transaction::create([
                    'item_id' => $item->id,
                    'user_id' => $staffUsers[array_rand($staffUsers)]->id,
                    'date' => $date->copy()->addDays(rand(1, 15)),
                    'type' => 'masuk',
                    'quantity' => rand(10, 100),
                    'description' => 'Pengadaan rutin bulan ' . $date->translatedFormat('F Y'),
                    'status' => 'approved',
                    'approved_by' => $admin->id,
                    'approved_at' => $date->copy()->addDays(rand(16, 20)),
                ]);
            }

            foreach ($allItems->random(rand(3, 7)) as $item) {
                // Keluar transaction
                Transaction::create([
                    'item_id' => $item->id,
                    'user_id' => $staffUsers[array_rand($staffUsers)]->id,
                    'date' => $date->copy()->addDays(rand(10, 25)),
                    'type' => 'keluar',
                    'quantity' => rand(2, 30),
                    'description' => 'Pemakaian operasional bulan ' . $date->translatedFormat('F Y'),
                    'status' => 'approved',
                    'approved_by' => $admin->id,
                    'approved_at' => $date->copy()->addDays(rand(20, 28)),
                ]);
            }
        }

        // Add pending transactions (same date for daily approval testing)
        $pendingDate = $now->copy()->subDays(1);
        for ($i = 0; $i < 4; $i++) {
            Transaction::create([
                'item_id' => $allItems->random()->id,
                'user_id' => $staffUsers[array_rand($staffUsers)]->id,
                'date' => $pendingDate,
                'type' => ['masuk', 'keluar'][rand(0, 1)],
                'quantity' => rand(5, 50),
                'description' => 'Menunggu persetujuan admin',
                'status' => 'pending',
            ]);
        }

        // Add pending transactions for today
        for ($i = 0; $i < 3; $i++) {
            Transaction::create([
                'item_id' => $allItems->random()->id,
                'user_id' => $staffUsers[array_rand($staffUsers)]->id,
                'date' => $now->copy(),
                'type' => ['masuk', 'keluar'][rand(0, 1)],
                'quantity' => rand(5, 30),
                'description' => 'Menunggu persetujuan admin hari ini',
                'status' => 'pending',
            ]);
        }
    }
}
