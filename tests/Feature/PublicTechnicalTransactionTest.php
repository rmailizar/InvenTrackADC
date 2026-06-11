<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicTechnicalTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_goods_receipt_creates_an_approved_technical_transaction(): void
    {
        $item = $this->technicalItem();

        $response = $this->post(route('public.teknik.transactions.store'), [
            'date' => '2026-06-10',
            'type' => 'in',
            'item_id' => $item->id,
            'quantity' => 5,
            'ship_unloader' => ['2', '1'],
            'description' => 'Public receipt',
        ]);

        $response->assertRedirect(route('public.stuff-request', ['bidang' => 'teknik']));
        $this->assertDatabaseHas('transactions', [
            'item_id' => $item->id,
            'user_id' => null,
            'bidang' => 'teknik',
            'type' => 'in',
            'quantity' => 5,
            'ship_unloader' => '1,2',
            'status' => 'approved',
        ]);
    }

    public function test_public_goods_issue_rejects_quantity_above_stock(): void
    {
        $item = $this->technicalItem();
        $user = User::factory()->create();

        Transaction::create([
            'item_id' => $item->id,
            'user_id' => $user->id,
            'bidang' => 'teknik',
            'date' => '2026-06-10',
            'type' => 'in',
            'quantity' => 3,
            'ship_unloader' => '1',
            'status' => 'approved',
        ]);

        $response = $this->from(route('public.stuff-request', ['bidang' => 'teknik']))
            ->post(route('public.teknik.transactions.store'), [
                'date' => '2026-06-10',
                'type' => 'out',
                'item_id' => $item->id,
                'quantity' => 4,
                'ship_unloader' => ['1'],
            ]);

        $response->assertRedirect(route('public.stuff-request', ['bidang' => 'teknik']));
        $response->assertSessionHasErrors('quantity');
        $this->assertDatabaseMissing('transactions', [
            'item_id' => $item->id,
            'type' => 'out',
        ]);
    }

    public function test_public_transaction_rejects_non_technical_item(): void
    {
        $item = Item::create([
            'name' => 'Barang Umum',
            'category' => 'ATK',
            'unit' => 'Pcs',
            'bidang' => 'umum',
            'min_stock' => 0,
        ]);

        $response = $this->post(route('public.teknik.transactions.store'), [
            'date' => '2026-06-10',
            'type' => 'in',
            'item_id' => $item->id,
            'quantity' => 1,
            'ship_unloader' => ['1'],
        ]);

        $response->assertSessionHasErrors('item_id');
        $this->assertDatabaseMissing('transactions', ['item_id' => $item->id]);
    }

    private function technicalItem(): Item
    {
        return Item::create([
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
    }
}
