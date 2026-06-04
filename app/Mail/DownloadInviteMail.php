<?php

namespace App\Mail;

use App\Models\Cms\Download;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DownloadInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Download $download,
        public string $url,
        public ?string $messageText = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Download: :name', ['name' => $this->download->name]));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.downloads.invite');
    }
}
