<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Mail;

use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\MailBuilder;
use Odden\MailBuilder\Transport\CidImageEmbedder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class TemplateMailable extends Mailable
{
    use Queueable, SerializesModels;

    public string $compiledHtml;

    public string $compiledPlainText;

    /**
     * @param  EmailDocument|list<array<string, mixed>>|string  $template
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $customAttachments
     */
    public function __construct(
        EmailDocument|array|string $template,
        public array $data = [],
        public ?string $subjectLine = null,
        public ?string $fromEmail = null,
        public ?string $fromName = null,
        public ?string $replyToEmail = null,
        public ?string $listUnsubscribeUrl = null,
        public array $customAttachments = [],
        public bool $embedCidImages = false,
    ) {
        $subject = $this->subjectLine;

        if ($template instanceof EmailDocument) {
            $html = MailBuilder::compile($template);
            $plain = MailBuilder::plainText($template);
            $subject = $subject ?? $template->subject ?? 'Important Update';
        } elseif (is_array($template)) {
            $html = MailBuilder::compile($template, ['subject' => $subject ?? 'Important Update']);
            $plain = MailBuilder::plainText($template);
        } else {
            $html = $template;
            $plain = MailBuilder::plainText($template);
        }

        // Interpolate merge tags
        $this->compiledHtml = MailBuilder::interpolate($html, $this->data);
        $this->compiledPlainText = MailBuilder::interpolate($plain, $this->data);
        $this->subjectLine = $subject !== null ? MailBuilder::interpolate($subject, $this->data) : 'Important Update';

        if ($this->embedCidImages) {
            $embedResult = CidImageEmbedder::embed($this->compiledHtml);
            $this->compiledHtml = $embedResult['html'];
            foreach ($embedResult['attachments'] as $cidAtt) {
                $this->customAttachments[] = [
                    'name' => $cidAtt['name'],
                    'data' => $cidAtt['data'],
                    'mime' => $cidAtt['mime'],
                ];
            }
        }
    }

    public function envelope(): Envelope
    {
        $from = null;
        if (! empty($this->fromEmail)) {
            $from = new Address($this->fromEmail, $this->fromName ?? config('mail.from.name'));
        }

        $replyTo = ! empty($this->replyToEmail) ? [new Address($this->replyToEmail)] : [];

        return new Envelope(
            from: $from,
            replyTo: $replyTo,
            subject: $this->subjectLine,
        );
    }

    public function headers(): Headers
    {
        $unsub = $this->listUnsubscribeUrl ?? ($this->data['unsubscribe_url'] ?? null);

        if (! empty($unsub) && is_string($unsub)) {
            return new Headers(
                text: [
                    'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
                    'List-Unsubscribe' => "<{$unsub}>",
                ]
            );
        }

        return new Headers;
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->compiledHtml,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $built = [];
        foreach ($this->customAttachments as $att) {
            $name = (string) ($att['name'] ?? 'attachment');
            $mime = isset($att['mime']) ? (string) $att['mime'] : null;

            if (! empty($att['path']) && is_string($att['path'])) {
                $item = Attachment::fromPath($att['path'])->as($name);
                if ($mime !== null) {
                    $item->withMime($mime);
                }
                $built[] = $item;
            } elseif (! empty($att['data'])) {
                $data = is_string($att['data']) ? $att['data'] : '';
                $isBase64 = (bool) ($att['is_base64'] ?? false);
                $content = $isBase64 ? (base64_decode($data) ?: $data) : $data;

                $item = Attachment::fromData(fn () => $content, $name);
                if ($mime !== null) {
                    $item->withMime($mime);
                }
                $built[] = $item;
            }
        }

        return $built;
    }
}
