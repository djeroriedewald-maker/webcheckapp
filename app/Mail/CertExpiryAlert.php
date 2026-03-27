<?php

namespace App\Mail;

use App\Models\MonitoredSite;
use App\Models\Scan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CertExpiryAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MonitoredSite $site,
        public Scan $scan,
        public int $daysLeft,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "SSL certificate expiring in {$this->daysLeft} days — {$this->site->domain}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.cert-expiry',
        );
    }
}
