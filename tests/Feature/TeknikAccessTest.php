<?php

namespace Tests\Feature;

use App\Http\Middleware\RestrictTeknikAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeknikAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_teknik_user_can_only_open_allowed_pages_after_login(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'bidang' => 'teknik',
            'account_status' => 'approved',
        ]);

        $dashboardMiddleware = app('router')->getRoutes()->getByName('dashboard')->gatherMiddleware();
        $this->assertContains(RestrictTeknikAccess::class, $dashboardMiddleware);

        $this->actingAs($user)->get(route('items.index'))->assertOk();
        $this->actingAs($user)->get(route('transactions.index', ['type' => 'in']))->assertOk();
        $this->actingAs($user)->get(route('transactions.index', ['type' => 'out']))->assertOk();

        $this->actingAs($user)->get(route('stock.index'))->assertForbidden();
        $this->actingAs($user)->get(route('stock-requests.index'))->assertForbidden();
        $this->actingAs($user)->get(route('stuff-requests.index'))->assertForbidden();
        $this->actingAs($user)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('import.index'))->assertForbidden();
    }
}
