<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Audit;

class PreFlightAuditResult
{
    /**
     * @param  list<PreFlightCheck>  $checks
     * @param  'healthy'|'needs_attention'|'critical'  $status
     */
    public function __construct(
        public readonly int $score,
        public readonly string $status,
        public readonly int $htmlSizeBytes,
        public readonly array $checks = [],
    ) {}

    /**
     * Determine if there are any critical failing checks that should block campaign dispatch.
     */
    public function hasBlockingIssues(): bool
    {
        foreach ($this->checks as $check) {
            if ($check->isFailing()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Count passing checks.
     */
    public function passCount(): int
    {
        return count(array_filter($this->checks, fn (PreFlightCheck $c): bool => $c->isPassing()));
    }

    /**
     * Count warning checks.
     */
    public function warningCount(): int
    {
        return count(array_filter($this->checks, fn (PreFlightCheck $c): bool => $c->isWarning()));
    }

    /**
     * Count failing checks.
     */
    public function failCount(): int
    {
        return count(array_filter($this->checks, fn (PreFlightCheck $c): bool => $c->isFailing()));
    }

    /**
     * @return array{score: int, status: string, html_size_bytes: int, checks: list<array{id: string, title: string, status: string, message: string, recommendation: string|null}>}
     */
    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'status' => $this->status,
            'html_size_bytes' => $this->htmlSizeBytes,
            'checks' => array_map(fn (PreFlightCheck $c): array => $c->toArray(), $this->checks),
        ];
    }
}
