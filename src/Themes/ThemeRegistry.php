<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Themes;

class ThemeRegistry
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $themes = [
        'corporate_slate' => [
            'name' => 'Corporate Slate',
            'primary_color' => '#2563eb',
            'background_color' => '#f8fafc',
            'content_background_color' => '#ffffff',
            'heading_color' => '#0f172a',
            'text_color' => '#334155',
            'border_color' => '#e2e8f0',
            'border_radius' => '8px',
            'container_width' => 600,
            'font_family' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif",
        ],
        'midnight_indigo' => [
            'name' => 'Midnight Indigo',
            'primary_color' => '#6366f1',
            'background_color' => '#0f172a',
            'content_background_color' => '#1e293b',
            'heading_color' => '#ffffff',
            'text_color' => '#cbd5e1',
            'border_color' => '#334155',
            'border_radius' => '12px',
            'container_width' => 600,
            'font_family' => "'Inter', -apple-system, BlinkMacSystemFont, sans-serif",
        ],
        'emerald_saas' => [
            'name' => 'Emerald SaaS',
            'primary_color' => '#059669',
            'background_color' => '#f0fdf4',
            'content_background_color' => '#ffffff',
            'heading_color' => '#064e3b',
            'text_color' => '#166534',
            'border_color' => '#bbf7d0',
            'border_radius' => '10px',
            'container_width' => 600,
            'font_family' => "'Inter', -apple-system, sans-serif",
        ],
        'warm_sunset' => [
            'name' => 'Warm Sunset',
            'primary_color' => '#ea580c',
            'background_color' => '#fff7ed',
            'content_background_color' => '#ffffff',
            'heading_color' => '#7c2d12',
            'text_color' => '#431407',
            'border_color' => '#fed7aa',
            'border_radius' => '16px',
            'container_width' => 600,
            'font_family' => "'Merriweather', Georgia, serif",
        ],
        'monochrome' => [
            'name' => 'Monochrome Minimal',
            'primary_color' => '#000000',
            'background_color' => '#f4f4f5',
            'content_background_color' => '#ffffff',
            'heading_color' => '#18181b',
            'text_color' => '#3f3f46',
            'border_color' => '#e4e4e7',
            'border_radius' => '0px',
            'container_width' => 600,
            'font_family' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
        ],
    ];

    /**
     * Get all registered theme palettes.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->themes;
    }

    /**
     * Get a specific theme by key.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $key): ?array
    {
        return $this->themes[$key] ?? null;
    }

    /**
     * Register or override a theme.
     *
     * @param  array<string, mixed>  $config
     */
    public function register(string $key, array $config): void
    {
        $this->themes[$key] = $config;
    }

    /**
     * Get key => name options array for select fields.
     *
     * @return array<string, string>
     */
    public function toSelectOptions(): array
    {
        $options = [];
        foreach ($this->themes as $key => $theme) {
            $options[$key] = (string) ($theme['name'] ?? ucfirst($key));
        }

        return $options;
    }

    /**
     * Resolve a theme configuration with optional overrides.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function apply(string $key = 'corporate_slate', array $overrides = []): array
    {
        $base = $this->get($key) ?? $this->themes['corporate_slate'];

        return array_merge($base, $overrides);
    }
}
