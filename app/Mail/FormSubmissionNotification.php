<?php

namespace App\Mail;

use App\Models\Cms\Form;
use App\Models\Cms\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FormSubmissionNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, string>  $answers
     */
    public function __construct(
        public Form $form,
        public ?FormSubmission $submission,
        public string $subjectLine,
        public string $body,
        public array $answers,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.forms.submission');
    }
}
