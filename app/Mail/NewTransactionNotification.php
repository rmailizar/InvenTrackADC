<?php

namespace App\Mail;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewTransactionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Transaction $transaction;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction->load(['item', 'user']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[InvenTrack] Transaksi Baru Menunggu Approval',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-transaction',
        );
    }
}
