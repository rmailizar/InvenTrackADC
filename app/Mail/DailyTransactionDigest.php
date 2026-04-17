<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

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
            subject: '[InvenTrack] Ringkasan Transaksi Pending - ' . $this->dateLabel,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-transaction-digest',
        );
    }
}
