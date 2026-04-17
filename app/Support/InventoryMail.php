<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

final class InventoryMail
{
    /**
     * Alamat unik untuk notifikasi admin (role admin + opsional INVENTORY_ALERT_MAIL).
     *
     * @return list<string>
     */
    public static function adminNotificationRecipients(): array
    {
        /** @var Collection<int, string> $fromDb */
        $fromDb = User::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->pluck('email');

        $emails = $fromDb
            ->map(fn (string $e) => strtolower(trim($e)))
            ->filter(fn (string $e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL));

        $extra = config('inventory.alert_mail');
        if (is_string($extra)) {
            $extra = strtolower(trim($extra));
            if ($extra !== '' && filter_var($extra, FILTER_VALIDATE_EMAIL)) {
                $emails->push($extra);
            }
        }

        return $emails->unique()->values()->all();
    }
}
