<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DailyStockAlertDigest extends Mailable
{
    use Queueable, SerializesModels;

    public Collection $outOfStockItems;  // stok = 0
    public Collection $lowStockItems;    // 0 < stok < min_stok
    public string $dateLabel;

    public function __construct(Collection $outOfStockItems, Collection $lowStockItems, string $dateLabel)
    {
        $this->outOfStockItems = $outOfStockItems;
        $this->lowStockItems = $lowStockItems;
        $this->dateLabel = $dateLabel;
    }

    public function envelope(): Envelope
    {
        $totalItems = $this->outOfStockItems->count() + $this->lowStockItems->count();
        return new Envelope(
            subject: "[InvenTrack] Peringatan Stok ({$totalItems} barang) - " . $this->dateLabel,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-stock-alert-digest',
        );
    }
}
