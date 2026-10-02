<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Audit;

class PreFlightCheck
{
    /**
     * @param  'pass'|'warning'|'fail'  $status
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $status,
        public readonly string $message,
        public readonly ?string $recommendation = null,
    ) {}

    public function isPassing(): bool
    {
        return $this->status === 'pass';
    }

    public function isWarning(): bool
    {
        return $this->status === 'warning';
    }

    public function isFailing(): bool
    {
        return $this->status === 'fail';
    }

    /**
     * @return array{id: string, title: string, status: string, message: string, recommendation: string|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'message' => $this->message,
            'recommendation' => $this->recommendation,
        ];
    }
}
