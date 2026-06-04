<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_teknik_goods_issue_store_redirects_back_to_goods_issue(): void
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
            'min_stock' => 0,
        ]);

        Transaction::create([
            'item_id' => $item->id,
            'user_id' => $user->id,
            'bidang' => 'teknik',
            'no_normalisasi' => $item->no_normalisasi,
            'lokasi' => $item->lokasi,
            'volume' => $item->volume,
            'ship_unloader' => '1',
            'date' => '2026-06-04',
            'type' => 'in',
            'quantity' => 5,
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('transactions.store'), [
            'date' => '2026-06-04',
            'type' => 'out',
            'item_id' => $item->id,
            'quantity' => 2,
            'ship_unloader' => ['1'],
            'description' => 'Goods issue teknik',
        ]);

        $response->assertRedirect(route('transactions.index', ['type' => 'out']));

        $this->assertDatabaseHas('transactions', [
            'item_id' => $item->id,
            'user_id' => $user->id,
            'bidang' => 'teknik',
            'type' => 'out',
            'quantity' => 2,
            'status' => 'approved',
        ]);
    }
}
