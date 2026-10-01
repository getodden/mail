<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\MergeTags;

class MergeTagRegistry
{
    /**
     * Application-registered merge tag groups: group => [tag => description].
     *
     * @var array<string, array<string, string>>
     */
    protected array $groups = [];

    /**
     * Sample values for registered tags, keyed by context path.
     *
     * @var array<string, mixed>
     */
    protected array $samples = [];

    public function __construct(
        protected MergeTagInterpolator $interpolator
    ) {}

    /**
     * Register a group of merge tags for editor pickers and previews.
     *
     * Call this from a service provider, e.g. via callAfterResolving(MergeTagRegistry::class, ...).
     * Registering an existing group adds to it.
     *
     * @param  array<string, string>  $tags  Tag => description, e.g. ['{{contact.first_name}}' => 'Recipient first name']
     * @param  array<string, mixed>  $samples  Preview values keyed by context path, e.g. ['contact' => ['first_name' => 'Alex']]
     */
    public function register(string $group, array $tags, array $samples = []): static
    {
        $this->groups[$group] = array_merge($this->groups[$group] ?? [], $tags);
        $this->samples = array_replace_recursive($this->samples, $samples);

        return $this;
    }

    /**
     * Get all supported merge tags grouped by category, built-in tags last.
     *
     * @return array<string, array<string, string>>
     */
    public function all(): array
    {
        return array_merge($this->groups, [
            'System & Legal' => [
                '{{unsubscribe_url}}' => 'One-click unsubscribe or subscription preference link',
                '{{current_year}}' => 'Current calendar year',
                '{{web_view_url}}' => 'Browser-rendered version of this email campaign',
            ],
        ]);
    }

    /**
     * Get a flattened map of tag => description.
     *
     * @return array<string, string>
     */
    public function flattened(): array
    {
        $flattened = [];
        foreach ($this->all() as $tags) {
            foreach ($tags as $tag => $label) {
                $flattened[$tag] = $label;
            }
        }

        return $flattened;
    }

    /**
     * Generate a realistic dummy dataset for previewing template merge tags.
     *
     * @return array<string, mixed>
     */
    public function sampleContext(): array
    {
        return array_replace_recursive([
            'unsubscribe_url' => 'https://example.com/unsubscribe?token=sample_token_xyz',
            'current_year' => (string) date('Y'),
            'web_view_url' => 'https://example.com/emails/view/sample-preview-id',
        ], $this->samples);
    }

    /**
     * Interpolate merge tags within a given string using the provided context or sample data.
     *
     * @param  array<string, mixed>|null  $context
     */
    public function interpolate(string $content, ?array $context = null): string
    {
        $context ??= $this->sampleContext();

        return $this->interpolator->interpolate($content, $context);
    }
}
