<?php

declare(strict_types=1);

namespace Odden\MailBuilder\MergeTags;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class MergeTagInterpolator
{
    /**
     * Interpolate merge tags and conditional blocks within a given string using context.
     *
     * With $escape, a value that comes from the context is HTML-escaped where it is put into the text, so a value that is
     * not markup (a name, a note, an order reference a customer typed) cannot add markup or a link to an email. A tag
     * written with the `raw` filter ({{ order_table | raw }}) is the template author saying the value is markup they
     * trust; fallback text from a `default` filter is the author's own and is not escaped. Without $escape (plain text,
     * subjects, and callers that escape their own values) nothing is escaped, as before.
     *
     * @param  array<string, mixed>  $context
     */
    public function interpolate(string $content, array $context = [], bool $escape = false): string
    {
        // 1. Process conditional logic blocks: {% if condition %}...{% else %}...{% endif %}
        $content = $this->interpolateConditionals($content, $context);

        // 2. Process variable tags with pipe filters: {{ tag | filter1 | filter2:arg }}
        return (string) preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_\.]+)(?:\s*\|\s*([^}]+))?\s*\}\}/',
            function (array $matches) use ($context, $escape): string {
                $tag = trim($matches[1]);
                $pipeFilters = isset($matches[2]) ? trim($matches[2]) : null;

                $raw = Arr::get($context, $tag);
                $value = ($raw !== null && is_scalar($raw)) ? (string) $raw : null;

                // Whether the text being built is the context's (to be escaped) or the template author's own.
                $fromContext = $value !== null;
                $trusted = false;

                if ($pipeFilters !== null && $pipeFilters !== '') {
                    $filters = $this->parseFilters($pipeFilters);

                    foreach ($filters as $filter) {
                        $name = $filter['name'];
                        $args = $filter['args'];

                        if ($name === 'raw') {
                            $trusted = true;
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

                // If no value provided, leave tag intact
                return $matches[0];
            },
            $content
        );
    }

    /**
     * Evaluate conditional blocks within the template content.
     *
     * @param  array<string, mixed>  $context
     */
    protected function interpolateConditionals(string $content, array $context): string
    {
        $pattern = '/\{%\s*if\s+([a-zA-Z0-9_\.]+)\s*%\}(.*?)(?:\{%\s*else\s*%\}(.*?))?\{%\s*endif\s*%\}/s';

        return (string) preg_replace_callback($pattern, function (array $matches) use ($context): string {
            $key = trim($matches[1]);
            $ifBranch = $matches[2];
            $elseBranch = $matches[3] ?? '';

            $val = Arr::get($context, $key);
            $isTruthy = ! empty($val);

            return $isTruthy ? $ifBranch : $elseBranch;
        }, $content);
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
