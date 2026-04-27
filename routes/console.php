<?php

use App\Models\User;
use App\Models\Item;
use App\Models\Transaction;
use App\Mail\DailyTransactionDigest;
use App\Mail\DailyStockAlertDigest;
use App\Support\InventoryMail;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Daily Digest Email (jam 15:00) ───────────────────────────────
Artisan::command('notify:daily-digest', function () {
    $recipients = InventoryMail::adminNotificationRecipients();

    if ($recipients === []) {
        $this->warn('Tidak ada alamat admin. Skipping digest.');
        return 0;
    }

    $today = Carbon::today();
    $dateLabel = $today->translatedFormat('l, d F Y');
    $sent = 0;

    // ── 1. Transaksi Pending hari ini ──
    $pendingTransactions = Transaction::with(['item', 'user'])
        ->where('status', 'pending')
        ->whereDate('created_at', $today)
        ->latest()
        ->get();

    if ($pendingTransactions->isNotEmpty()) {
        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new DailyTransactionDigest($pendingTransactions, $dateLabel));
                $sent++;
            } catch (\Throwable $e) {
                Log::error('Gagal kirim digest transaksi', ['to' => $email, 'error' => $e->getMessage()]);
            }
        }
        $this->info("✅ Email digest transaksi ({$pendingTransactions->count()} transaksi) dikirim ke {$sent} penerima.");
    } else {
        $this->info('ℹ️  Tidak ada transaksi pending hari ini. Email transaksi tidak dikirim.');
    }

    // ── 2. Stock Alert ──
    $allItems = Item::all();
    $outOfStock = $allItems->filter(fn($item) => $item->current_stock === 0 && $item->min_stock > 0);
    $lowStock = $allItems->filter(fn($item) => $item->current_stock > 0 && $item->current_stock < $item->min_stock);

    if ($outOfStock->isNotEmpty() || $lowStock->isNotEmpty()) {
        $sentStock = 0;
        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new DailyStockAlertDigest($outOfStock, $lowStock, $dateLabel));
                $sentStock++;
            } catch (\Throwable $e) {
                Log::error('Gagal kirim digest stok', ['to' => $email, 'error' => $e->getMessage()]);
            }
        }
        $total = $outOfStock->count() + $lowStock->count();
        $this->info("✅ Email digest stok ({$total} barang: {$outOfStock->count()} habis, {$lowStock->count()} rendah) dikirim ke {$sentStock} penerima.");
    } else {
        $this->info('ℹ️  Semua stok aman. Email stok tidak dikirim.');
    }

    return 0;
})->purpose('Kirim email digest harian (transaksi pending + stock alert)');

// Schedule: jalankan setiap hari jam 15:00
Schedule::command('notify:daily-digest')->dailyAt('15:00');

// ─── Mail Test ────────────────────────────────────────────────────
Artisan::command('mail:test {email? : Alamat tujuan (default: email user admin pertama)}', function () {
    $email = $this->argument('email');
    if (!is_string($email) || trim($email) === '') {
        $admin = User::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->orderBy('id')
            ->first();
        if (!$admin) {
            $this->error('Tidak ada user admin dengan email. Jalankan: php artisan mail:test anda@email.com');

            return 1;
        }
        $email = $admin->email;
    }

    try {
        Mail::raw(
            'Ini email percobaan dari InvenTrack. Waktu server: ' . now()->toDateTimeString() . "\n\nJika Anda membaca ini, SMTP (mis. Brevo) sudah terkonfigurasi benar.",
            function ($message) use ($email) {
                $message->to($email)->subject('[InvenTrack] Tes pengiriman email');
            }
        );
    } catch (\Throwable $e) {
        $this->error('Gagal mengirim: ' . $e->getMessage());
        $this->line('Periksa MAIL_MAILER=smtp, MAIL_HOST, MAIL_PORT, MAIL_USERNAME (email Brevo), MAIL_PASSWORD (kunci SMTP Brevo — bukan API key), MAIL_FROM_ADDRESS (pengirim terverifikasi di Brevo).');
        $this->line('Lihat log: storage/logs/laravel.log');

        return 1;
    }

    $this->info("Email tes terkirim ke {$email}. Periksa inbox dan folder spam.");

    return 0;
})->purpose('Uji koneksi SMTP / pengiriman email (Brevo, dll.)');
