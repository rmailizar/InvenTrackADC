<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicalShipUnloaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_goods_issue_sets_master_ship_unloader_to_form_input(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'bidang' => 'teknik',
            'account_status' => 'approved',
        ]);

        $item = Item::create([
            'name' => 'Kabel LAN',
            'category' => 'Network',
            'component' => 'Cable',
            'unit' => 'Box',
            'bidang' => 'teknik',
            'no_normalisasi' => 'SU-01-LAN-001',
            'lokasi' => 'Gudang Teknik',
            'volume' => 10,
            'ship_unloader' => '1,2,3,4',
            'min_stock' => 0,
        ]);

        Transaction::create([
            'item_id' => $item->id,
            'user_id' => $user->id,
            'bidang' => 'teknik',
            'no_normalisasi' => $item->no_normalisasi,
            'lokasi' => $item->lokasi,
            'volume' => $item->volume,
            'ship_unloader' => '1,2,3,4',
            'date' => '2026-06-01',
            'type' => 'in',
            'quantity' => 10,
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'date' => '2026-06-04',
            'type' => 'out',
            'item_id' => $item->id,
            'quantity' => 2,
            'ship_unloader' => ['1', '4'],
            'description' => 'Goods issue teknik',
        ]);

        $response->assertRedirect(route('transactions.index', ['type' => 'out']));

        $item->refresh();

        $this->assertSame('1,4', $item->ship_unloader);
        $this->assertSame('1,4', $item->stock_ship_unloader);
        $this->assertDatabaseHas('transactions', [
            'item_id' => $item->id,
            'type' => 'out',
            'ship_unloader' => '1,4',
            'status' => 'approved',
        ]);
    }

    public function test_goods_receipt_sets_master_ship_unloader_to_form_input(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'bidang' => 'teknik',
            'account_status' => 'approved',
        ]);

        $item = Item::create([
            'name' => 'Switch 24 Port',
            'category' => 'Network',
            'component' => 'Switch',
            'unit' => 'Unit',
            'bidang' => 'teknik',
            'no_normalisasi' => 'SU-02-SW24-002',
            'lokasi' => 'Gudang Teknik',
            'volume' => 5,
            'min_stock' => 0,
        ]);

        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'date' => '2026-06-04',
            'type' => 'in',
            'item_id' => $item->id,
            'quantity' => 3,
            'ship_unloader' => ['2', '3'],
            'description' => 'Goods receipt teknik',
        ]);

        $response->assertRedirect(route('transactions.index', ['type' => 'in']));

        $item->refresh();

        $this->assertSame('2,3', $item->ship_unloader);
        $this->assertSame('2,3', $item->stock_ship_unloader);
    }
}
