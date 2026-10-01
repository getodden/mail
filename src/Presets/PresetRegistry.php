<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Presets;

use InvalidArgumentException;

class PresetRegistry
{
    /**
     * @var array<string, PresetContract>
     */
    protected array $presets = [];

    public function __construct()
    {
        $this->registerDefaultPresets();
    }

    /**
     * Register standard built-in templates.
     */
    protected function registerDefaultPresets(): void
    {
        $this->register(new ProductAnnouncementPreset);
        $this->register(new NewsletterDigestPreset);
        $this->register(new WelcomeOnboardingPreset);
        $this->register(new TransactionalReceiptPreset);
    }

    /**
     * Register a new preset.
     */
    public function register(PresetContract $preset): self
    {
        $this->presets[$preset->key()] = $preset;

        return $this;
    }

    /**
     * Retrieve a preset by its key.
     */
    public function get(string $key): PresetContract
    {
        if (! isset($this->presets[$key])) {
            throw new InvalidArgumentException("Preset [{$key}] not found.");
        }

        return $this->presets[$key];
    }

    /**
     * Check if a preset key exists.
     */
    public function has(string $key): bool
    {
        return isset($this->presets[$key]);
    }

    /**
     * Return all registered presets.
     *
     * @return array<string, PresetContract>
     */
    public function all(): array
    {
        return $this->presets;
    }

    /**
     * Return presets as a key-value options array for selects.
     *
     * @return array<string, string>
     */
    public function toSelectOptions(): array
    {
        $options = [];
        foreach ($this->presets as $key => $preset) {
            $options[$key] = $preset->label();
        }

        return $options;
    }
}
