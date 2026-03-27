<?php

namespace App\Mail;

use App\Models\MonitoredSite;
use App\Models\Scan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScoreDropAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MonitoredSite $site,
        public Scan $scan,
        public int $previousScore,
        public int $currentScore,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Security score dropped for {$this->site->domain}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.score-drop',
        );
    }
}
