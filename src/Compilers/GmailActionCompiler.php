<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Compilers;

class GmailActionCompiler
{
    /**
     * @var list<array<string, mixed>>
     */
    protected array $actions = [];

    /**
     * Create a new GmailActionCompiler instance.
     */
    public static function make(): self
    {
        return new self;
    }

    /**
     * Add a ViewAction (e.g. View Order, Track Package, View Invoice).
     */
    public function viewAction(string $name, string $url, ?string $description = null): self
    {
        $action = [
            '@type' => 'ViewAction',
            'name' => $name,
            'url' => $url,
        ];

        if ($description !== null) {
            $action['description'] = $description;
        }

        $this->actions[] = $action;

        return $this;
    }

    /**
     * Add a ConfirmAction (e.g. Confirm RSVP, Approve Request).
     */
    public function confirmAction(string $name, string $handlerUrl, ?string $description = null): self
    {
        $action = [
            '@type' => 'ConfirmAction',
            'name' => $name,
            'handler' => [
                '@type' => 'HttpActionHandler',
                'url' => $handlerUrl,
                'method' => 'http://schema.org/HttpRequestMethod/POST',
            ],
        ];

        if ($description !== null) {
            $action['description'] = $description;
        }

        $this->actions[] = $action;

        return $this;
    }

    /**
     * Add a SaveAction (e.g. Save Discount, Claim Coupon).
     */
    public function saveAction(string $name, string $url, ?string $description = null): self
    {
        $action = [
            '@type' => 'SaveAction',
            'name' => $name,
            'handler' => [
                '@type' => 'HttpActionHandler',
                'url' => $url,
                'method' => 'http://schema.org/HttpRequestMethod/POST',
            ],
        ];

        if ($description !== null) {
            $action['description'] = $description;
        }

        $this->actions[] = $action;

        return $this;
    }

    /**
     * Add a ReviewAction (e.g. Rate Product, Leave Review).
     */
    public function reviewAction(string $name, string $reviewUrl, ?string $description = null): self
    {
        $action = [
            '@type' => 'ReviewAction',
            'name' => $name,
            'review' => [
                '@type' => 'Review',
                'url' => $reviewUrl,
            ],
        ];

        if ($description !== null) {
            $action['description'] = $description;
        }

        $this->actions[] = $action;

        return $this;
    }

    /**
     * Add raw action array.
     *
     * @param  array<string, mixed>  $action
     */
    public function addAction(array $action): self
    {
        $this->actions[] = $action;

        return $this;
    }

    /**
     * Check if there are any actions configured.
     */
    public function hasActions(): bool
    {
        return ! empty($this->actions);
    }

    /**
     * Get the actions as an array.
     *
     * @return list<array<string, mixed>>
     */
    public function toArray(): array
    {
        return $this->actions;
    }

    /**
     * Render the JSON-LD script tag to be inserted in the email <head>.
     */
    public function toScript(): string
    {
        if (empty($this->actions)) {
            return '';
        }

        $payload = [
            '@context' => 'http://schema.org',
            '@type' => 'EmailMessage',
            'potentialAction' => count($this->actions) === 1 ? $this->actions[0] : $this->actions,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        if ($json === false) {
            return '';
        }

        return "<script type=\"application/ld+json\">\n{$json}\n</script>";
    }
}
