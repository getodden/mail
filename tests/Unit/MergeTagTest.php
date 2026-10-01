<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Tests\Unit;

use DoPHP\MailBuilder\MailBuilder;
use DoPHP\MailBuilder\MergeTags\MergeTagInterpolator;
use DoPHP\MailBuilder\MergeTags\MergeTagRegistry;
use DoPHP\MailBuilder\Tests\TestCase;

class MergeTagTest extends TestCase
{
    public function test_interpolator_replaces_tokens_with_context_values(): void
    {
        $interpolator = new MergeTagInterpolator;

        $content = 'Hello {{contact.first_name}} {{contact.last_name}}, welcome to {{company.name}}!';
        $context = [
            'contact' => [
                'first_name' => 'John',
                'last_name' => 'Connor',
            ],
            'company' => [
                'name' => 'Resistance HQ',
            ],
        ];

        $result = $interpolator->interpolate($content, $context);

        $this->assertSame('Hello John Connor, welcome to Resistance HQ!', $result);
    }

    public function test_interpolator_leaves_unmatched_tokens_when_missing(): void
    {
        $interpolator = new MergeTagInterpolator;

        $content = 'Hi {{contact.first_name}}, code is {{unknown.token}}';
        $result = $interpolator->interpolate($content, ['contact' => ['first_name' => 'Sarah']]);

        $this->assertSame('Hi Sarah, code is {{unknown.token}}', $result);
    }

    public function test_registry_provides_built_in_system_tags(): void
    {
        /** @var MergeTagRegistry $registry */
        $registry = app(MergeTagRegistry::class);

        $this->assertSame(['System & Legal'], array_keys($registry->all()));
        $this->assertArrayHasKey('{{unsubscribe_url}}', $registry->flattened());
        $this->assertSame(date('Y'), $registry->sampleContext()['current_year']);
    }

    public function test_registered_groups_are_listed_before_built_ins_and_feed_sample_context(): void
    {
        /** @var MergeTagRegistry $registry */
        $registry = app(MergeTagRegistry::class);

        $registry->register('Customer', ['{{customer.first_name}}' => 'Customer first name'], ['customer' => ['first_name' => 'Alex']])
            ->register('Customer', ['{{customer.plan}}' => 'Subscription plan'], ['customer' => ['plan' => 'Pro']]);

        $this->assertSame(['Customer', 'System & Legal'], array_keys($registry->all()));
        $this->assertSame(['{{customer.first_name}}', '{{customer.plan}}'], array_keys($registry->all()['Customer']));
        $this->assertArrayHasKey('{{customer.plan}}', $registry->flattened());
        $this->assertSame(['first_name' => 'Alex', 'plan' => 'Pro'], $registry->sampleContext()['customer']);
    }

    public function test_mail_builder_facade_exposes_merge_tags_and_interpolation(): void
    {
        MailBuilder::mergeTags()->register('Customer', ['{{customer.first_name}}' => 'Customer first name'], ['customer' => ['first_name' => 'Alex']]);

        $sample = MailBuilder::interpolate('Hi {{customer.first_name}}, (c) {{current_year}}');

        $this->assertSame('Hi Alex, (c) '.date('Y'), $sample);
    }

    public function test_interpolator_supports_default_fallback_values(): void
    {
        $interpolator = new MergeTagInterpolator;

        $content = 'Hello {{contact.nickname | default("there")}}, from {{company.name|default:"Our Team"}}!';
        $result = $interpolator->interpolate($content, ['company' => ['name' => 'Focal HQ']]);

        $this->assertSame('Hello there, from Focal HQ!', $result);
    }

    public function test_interpolator_supports_formatting_filters(): void
    {
        $interpolator = new MergeTagInterpolator;

        $content = '{{contact.first_name | upper}} - {{contact.city | title}} - Total: {{deal.amount | currency}}';
        $context = [
            'contact' => ['first_name' => 'alex', 'city' => 'san francisco'],
            'deal' => ['amount' => '12500'],
        ];

        $result = $interpolator->interpolate($content, $context);
        $this->assertSame('ALEX - San Francisco - Total: $12,500.00', $result);
    }
}
