<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use App\Support\InventoryMail;

class DailyTransactionDigest extends Mailable
{
    use Queueable, SerializesModels;

    public Collection $transactions;
    public string $dateLabel;

    public function __construct(Collection $transactions, string $dateLabel)
    {
        $this->transactions = $transactions;
        $this->dateLabel = $dateLabel;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[' . InventoryMail::appName() . '] Ringkasan Transaksi Pending Admin Umum - ' . $this->dateLabel,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-transaction-digest',
        );
    }
}
