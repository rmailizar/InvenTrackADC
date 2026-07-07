<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemDeleteRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_item_does_not_delete_transactions(): void
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

        $transaction = Transaction::create([
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

        // Assert transaction exists
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
        ]);

        // Delete the item via destroy route (requires authentication)
        $response = $this->actingAs($user)->delete(route('items.destroy', $item));
        $response->assertRedirect(route('items.index'));

        // Assert item is soft-deleted
        $this->assertSoftDeleted('items', [
            'id' => $item->id,
        ]);

        // Assert transaction still exists
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
        ]);

        // Assert item can be loaded withTrashed via transaction relation
        $loadedTransaction = Transaction::find($transaction->id);
        $this->assertNotNull($loadedTransaction->item);
        $this->assertEquals('Kabel LAN', $loadedTransaction->item->name);
    }
}
