<?php

namespace App\Mail;

use App\Models\Item;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockAlert extends Mailable
{
    use Queueable, SerializesModels;

    public Item $item;

    public function __construct(Item $item)
    {
        $this->item = $item;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[InvenTrack] Peringatan Stok Rendah: ' . $this->item->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.low-stock',
        );
    }
}
