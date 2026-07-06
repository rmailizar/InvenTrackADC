<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Determine the bidang context for Super Admin tab switching.
     * Returns 'umum' or 'teknik' when Super Admin uses ?sa_bidang=..., otherwise null.
     */
    protected function superAdminBidangContext(Request $request): ?string
    {
        $user = auth()->user();
        if (!$user?->isSuperAdmin()) {
            return null;
        }

        $bidang = $request->input('sa_bidang');
        if (in_array($bidang, ['umum', 'teknik'], true)) {
            return $bidang;
        }

        return 'umum'; // Default to 'umum' context for Super Admin to prevent mixed data
    }

    /**
     * Create a proxy user that mimics a specific bidang for visibleFor() scopes.
     * Only used when Super Admin selects a bidang tab.
     */
    protected function createBidangProxy(string $bidang): User
    {
        $real = auth()->user();
        $proxy = new User();
        $proxy->forceFill([
            'id' => $real->id,
            'role' => 'admin',
            'bidang' => $bidang,
            'name' => $real->name,
            'email' => $real->email,
        ]);
        $proxy->exists = true;

        return $proxy;
    }
}
