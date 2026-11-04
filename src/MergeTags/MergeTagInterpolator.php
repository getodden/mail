<?php

declare(strict_types=1);

namespace Odden\MailBuilder\MergeTags;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class MergeTagInterpolator
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function interpolate(string $content, array $context = [], bool $escape = false, bool $allowRaw = false): string
    {
        $content = $this->interpolateConditionals($content, $context);

        return (string) preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_\.]+)(?:\s*\|\s*([^}]+))?\s*\}\}/',
            function (array $matches) use ($context, $escape, $allowRaw): string {
                $tag = trim($matches[1]);
                $pipeFilters = isset($matches[2]) ? trim($matches[2]) : null;

                $raw = Arr::get($context, $tag);
                $value = ($raw !== null && is_scalar($raw)) ? (string) $raw : null;
                $fromContext = $value !== null;
                $trusted = false;

                if ($pipeFilters !== null && $pipeFilters !== '') {
                    $filters = $this->parseFilters($pipeFilters);

                    foreach ($filters as $filter) {
                        $name = $filter['name'];
                        $args = $filter['args'];

                        if ($name === 'raw') {
                            $trusted = $allowRaw;
                        } elseif ($name === 'default') {
                            $defaultFallback = $args[0] ?? '';
                            if ($value === null || $value === '') {
                                $value = $defaultFallback;
                                $fromContext = false;
                            }
                        } elseif ($value !== null) {
                            $value = $this->applyFilter($value, $name, $args);
                        }
                    }
                }

                if ($value !== null) {
                    return $escape && $fromContext && ! $trusted
                        ? htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                        : $value;
                }

                return $matches[0];
            },
            $content
        );
    }

    /**
     * Evaluate conditional blocks within the template content.
     *
     * An unclosed if hides everything from its tag to the end of the content, a block with a second else is hidden,
     * stray else and endif tags are dropped, and a condition that is not a plain context key is false.
     *
     * @param  array<string, mixed>  $context
     */
    protected function interpolateConditionals(string $content, array $context): string
    {
        if (! str_contains($content, '{%')) {
            return $content;
        }

        $found = preg_match_all('/\{%\s*(if|else|endif)\b([^%<>{}]*+)%\}/i', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        if ($found === false) {
            throw new RuntimeException('The conditionals could not be parsed: '.preg_last_error_msg().'.');
        }

        $output = '';
        $offset = 0;
        $active = true;
        $frames = [];

        foreach ($matches as $match) {
            [$tag, $start] = $match[0];
            $word = strtolower($match[1][0]);
            $key = trim($match[2][0]);

            if ($word !== 'if' && $key !== '') {
                continue;
            }

            if ($active) {
                $output .= substr($content, $offset, $start - $offset);
            }

            $offset = $start + strlen($tag);

            if ($word === 'if') {
                $holds = preg_match('/^[a-zA-Z0-9_\.]+$/', $key) === 1 && ! empty(Arr::get($context, $key));
                $frames[] = ['outer' => $active, 'holds' => $holds, 'else' => false, 'broken' => false, 'length' => strlen($output)];
                $active = $active && $holds;

                continue;
            }

            if ($frames === []) {
                continue;
            }

            $top = count($frames) - 1;

            if ($word === 'else') {
                if ($frames[$top]['else']) {
                    $frames[$top]['broken'] = true;
                    $active = false;
                } elseif (! $frames[$top]['broken']) {
                    $frames[$top]['else'] = true;
                    $active = $frames[$top]['outer'] && ! $frames[$top]['holds'];
                }

                continue;
            }

            $frame = array_pop($frames);

            if ($frame['broken']) {
                $output = substr($output, 0, $frame['length']);
            }

            $active = $frame['outer'];
        }

        if ($active) {
            $output .= substr($content, $offset);
        }

        if ($frames !== []) {
            $output = substr($output, 0, $frames[0]['length']);
        }

        return $output;
    }

    /**
     * Parse pipe-delimited filters into names and string arguments.
     *
     * @return list<array{name: string, args: list<string>}>
     */
    protected function parseFilters(string $pipeFilters): array
    {
        $result = [];
        $filterChunks = array_map('trim', explode('|', $pipeFilters));

        foreach ($filterChunks as $chunk) {
            if ($chunk === '') {
                continue;
            }

            if (preg_match('/^([a-zA-Z0-9_]+)\s*\((.*)\)$/', $chunk, $pMatch)) {
                $name = strtolower(trim($pMatch[1]));
                $rawArgs = trim($pMatch[2]);

                $args = [];
                $rawArgsList = str_getcsv($rawArgs);
                foreach ($rawArgsList as $arg) {
                    $args[] = trim((string) $arg, " \t\n\r\0\x0B'\"");
                }

                $result[] = ['name' => $name, 'args' => $args];
            } elseif (str_contains($chunk, ':')) {
                $parts = explode(':', $chunk, 2);
                $name = strtolower(trim($parts[0]));
                $rawArgs = trim($parts[1]);

                // Split arguments by comma while respecting quotes
                $args = [];
                $rawArgsList = str_getcsv($rawArgs);
                foreach ($rawArgsList as $arg) {
                    $args[] = trim((string) $arg, " \t\n\r\0\x0B'\"");
                }

                $result[] = ['name' => $name, 'args' => $args];
            } else {
                $result[] = ['name' => strtolower($chunk), 'args' => []];
            }
        }

        return $result;
    }

    /**
     * Apply a specific format filter to a scalar string value.
     *
     * @param  list<string>  $args
     */
    protected function applyFilter(string $value, string $filter, array $args = []): string
    {
        return match ($filter) {
            'upper' => strtoupper($value),
            'lower' => strtolower($value),
            'capitalize' => Str::ucfirst($value),
            'title' => Str::title($value),
            'trim' => trim($value),
            'date' => $this->filterDate($value, $args[0] ?? 'M j, Y'),
            'currency' => $this->filterCurrency($value, $args[0] ?? '$'),
            'number' => number_format((float) preg_replace('/[^\d.]/', '', $value), isset($args[0]) ? (int) $args[0] : 0),
            'pluralize' => $this->filterPluralize($value, $args[0] ?? '', $args[1] ?? ($args[0] ?? '').'s'),
            'truncate' => Str::limit($value, isset($args[0]) ? (int) $args[0] : 50),
            default => $value,
        };
    }

    protected function filterDate(string $value, string $format): string
    {
        try {
            return Carbon::parse($value)->format($format);
        } catch (\Throwable) {
            return $value;
        }
    }

    protected function filterCurrency(string $value, string $currencySymbol = '$'): string
    {
        $num = (float) preg_replace('/[^\d.]/', '', $value);

        if (strlen($currencySymbol) > 1 && ctype_alpha($currencySymbol)) {
            return number_format($num, 2).' '.strtoupper($currencySymbol);
        }

        return $currencySymbol.number_format($num, 2);
    }

    protected function filterPluralize(string $value, string $singular, string $plural): string
    {
        $count = (float) preg_replace('/[^\d.]/', '', $value);

        return $count == 1 ? $singular : $plural;
    }
}
