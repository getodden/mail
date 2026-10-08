<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Compilers\PlainTextExtractor;
use Odden\MailBuilder\Enums\SlotType;

/**
 * @implements Arrayable<string, mixed>
 */
class EmailDocument implements Arrayable, Jsonable, JsonSerializable
{
    /**
     * @param  list<EmailSlot>  $slots
     * @param  array<string, mixed>  $theme
     */
    public function __construct(
        public array $slots = [],
        public ?string $subject = null,
        public ?string $previewText = null,
        public array $theme = [],
    ) {}

    /**
     * Build an EmailDocument from an array representation.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $rawSlots = $payload['slots'] ?? [];
        $slots = [];

        if (is_array($rawSlots)) {
            foreach ($rawSlots as $slotData) {
                if (is_array($slotData)) {
                    $slots[] = EmailSlot::fromArray($slotData);
                }
            }
        }

        /** @var array<string, mixed> $theme */
        $theme = isset($payload['theme']) && is_array($payload['theme']) ? $payload['theme'] : [];

        return new self(
            slots: $slots,
            subject: isset($payload['subject']) ? (string) $payload['subject'] : null,
            previewText: isset($payload['preview_text']) ? (string) $payload['preview_text'] : null,
            theme: $theme,
        );
    }

    /**
     * Build an EmailDocument from a JSON string.
     */
    public static function fromJson(string $json): self
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true) ?? [];

        return self::fromArray($decoded);
    }

    /**
     * Add an EmailSlot instance.
     */
    public function addSlot(EmailSlot $slot): self
    {
        $this->slots[] = $slot;

        return $this;
    }

    /**
     * Conveniently append a slot by type and data.
     *
     * @param  array<string, mixed>  $data
     */
    public function append(SlotType $type, array $data = []): self
    {
        $this->slots[] = new EmailSlot($type, $data);

        return $this;
    }

    /**
     * Set a custom theme property.
     */
    public function setTheme(string $key, mixed $value): self
    {
        $this->theme[$key] = $value;

        return $this;
    }

    /**
     * Compile document into bulletproof responsive HTML.
     */
    public function compileHtml(?EmailSlotCompiler $compiler = null): string
    {
        $compiler ??= app(EmailSlotCompiler::class);

        return $compiler->compileDocument($this);
    }

    /**
     * Extract clean plain-text fallback content.
     */
    public function extractPlainText(?PlainTextExtractor $extractor = null): string
    {
        $extractor ??= app(PlainTextExtractor::class);

        return $extractor->extractFromDocument($this);
    }

    /**
     * Convert document into an array.
     *
     * @return array{
     *     subject: string|null,
     *     preview_text: string|null,
     *     theme: array<string, mixed>,
     *     slots: list<array{type: string, data: array<string, mixed>, visibility?: array<string, mixed>}>
     * }
     */
    public function toArray(): array
    {
        return [
            'subject' => $this->subject,
            'preview_text' => $this->previewText,
            'theme' => $this->theme,
            'slots' => array_map(fn (EmailSlot $slot): array => $slot->toArray(), $this->slots),
        ];
    }

    /**
     * Serialize into JSON.
     *
     * @param  int  $options
     */
    public function toJson($options = 0): string
    {
        return (string) json_encode($this->toArray(), $options);
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
