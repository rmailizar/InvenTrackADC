<?php

use App\Mail\DailyStockAlertDigest;
use App\Mail\DailyTransactionDigest;
use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
use App\Support\InventoryMail;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notify:daily-digest', function () {
    $bidang = 'umum';
    $recipients = InventoryMail::adminNotificationRecipients($bidang);

    if ($recipients === []) {
        $this->warn('Tidak ada alamat admin bidang umum. Skipping digest.');
        return 0;
    }

    $today = Carbon::today();
    $dateLabel = $today->translatedFormat('l, d F Y');
    $sent = 0;

    $pendingTransactions = Transaction::with(['item', 'user'])
        ->where('bidang', $bidang)
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
                Log::error('Gagal kirim digest transaksi umum', ['to' => $email, 'error' => $e->getMessage()]);
            }
        }
        $this->info("Email digest transaksi umum ({$pendingTransactions->count()} transaksi) dikirim ke {$sent} penerima.");
    } else {
        $this->info('Tidak ada transaksi pending umum hari ini. Email transaksi tidak dikirim.');
    }

    $allItems = Item::where('bidang', $bidang)->get();
    $outOfStock = $allItems->filter(fn($item) => $item->current_stock === 0 && $item->min_stock > 0);
    $lowStock = $allItems->filter(fn($item) => $item->current_stock > 0 && $item->current_stock < $item->min_stock);

    if ($outOfStock->isNotEmpty() || $lowStock->isNotEmpty()) {
        $sentStock = 0;
        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new DailyStockAlertDigest($outOfStock, $lowStock, $dateLabel));
                $sentStock++;
            } catch (\Throwable $e) {
                Log::error('Gagal kirim digest stok umum', ['to' => $email, 'error' => $e->getMessage()]);
            }
        }
        $total = $outOfStock->count() + $lowStock->count();
        $this->info("Email digest stok umum ({$total} barang: {$outOfStock->count()} habis, {$lowStock->count()} rendah) dikirim ke {$sentStock} penerima.");
    } else {
        $this->info('Semua stok umum aman. Email stok tidak dikirim.');
    }

    return 0;
})->purpose('Kirim email digest harian admin umum (transaksi pending + stock alert)');

Schedule::command('notify:daily-digest')
    ->dailyAt('15:00')
    ->timezone(config('app.timezone', 'Asia/Jakarta'))
    ->withoutOverlapping();

Artisan::command('mail:test {email? : Alamat tujuan (default: email user admin umum pertama)}', function () {
    $email = $this->argument('email');
    if (!is_string($email) || trim($email) === '') {
        $admin = User::query()
            ->where('role', 'admin')
            ->where('bidang', 'umum')
            ->whereNotNull('email')
            ->orderBy('id')
            ->first();

        if (!$admin) {
            $this->error('Tidak ada user admin umum dengan email. Jalankan: php artisan mail:test anda@email.com');

            return 1;
        }

        $email = $admin->email;
    }

    try {
        Mail::raw(
            'Ini email percobaan dari ' . InventoryMail::appName() . '. Waktu server: ' . now()->toDateTimeString() . "\n\nJika Anda membaca ini, SMTP sudah terkonfigurasi benar.",
            function ($message) use ($email) {
                $message->to($email)->subject('[' . InventoryMail::appName() . '] Tes pengiriman email');
            }
        );
    } catch (\Throwable $e) {
        $this->error('Gagal mengirim: ' . $e->getMessage());
        $this->line('Periksa MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, dan MAIL_FROM_ADDRESS.');
        $this->line('Lihat log: storage/logs/laravel.log');

        return 1;
    }

    $this->info("Email tes terkirim ke {$email}. Periksa inbox dan folder spam.");

    return 0;
})->purpose('Uji koneksi SMTP / pengiriman email');
