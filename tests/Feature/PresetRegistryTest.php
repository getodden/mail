<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Feature;

use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\MailBuilder;
use Odden\MailBuilder\Presets\PresetContract;
use Odden\MailBuilder\Presets\PresetRegistry;
use Odden\MailBuilder\Tests\TestCase;

class PresetRegistryTest extends TestCase
{
    public function test_default_presets_are_registered(): void
    {
        $registry = app(PresetRegistry::class);

        $this->assertTrue($registry->has('product_announcement'));
        $this->assertTrue($registry->has('newsletter_digest'));
        $this->assertTrue($registry->has('welcome_onboarding'));
        $this->assertTrue($registry->has('transactional_receipt'));

        $options = $registry->toSelectOptions();
        $this->assertArrayHasKey('product_announcement', $options);
        $this->assertArrayHasKey('newsletter_digest', $options);
    }

    public function test_all_default_presets_compile_to_valid_html_and_text(): void
    {
        $registry = app(PresetRegistry::class);

        foreach ($registry->all() as $key => $preset) {
            $doc = $preset->document();
            $this->assertInstanceOf(EmailDocument::class, $doc);
            $this->assertNotEmpty($doc->slots, "Preset [{$key}] must have slots");

            $html = MailBuilder::compile($doc);
            $this->assertStringContainsString('<!DOCTYPE html>', $html);
            $this->assertStringContainsString('max-width: 600px', $html);

            $text = MailBuilder::plainText($doc);
            $this->assertNotEmpty($text);
        }
    }

    public function test_can_register_custom_preset(): void
    {
        $registry = app(PresetRegistry::class);

        $custom = new class implements PresetContract
        {
            public function key(): string
            {
                return 'custom_promo';
            }

            public function label(): string
            {
                return 'Flash Promo';
            }

            public function description(): string
            {
                return '24-hour flash sale layout';
            }

            public function category(): string
            {
                return 'promotional';
            }

            public function document(): EmailDocument
            {
                $doc = new EmailDocument(subject: 'Flash Sale!');
                $doc->append(SlotType::Hero, ['title' => '50% Off Everything Today Only']);

                return $doc;
            }
        };

        $registry->register($custom);

        $this->assertTrue($registry->has('custom_promo'));
        $this->assertSame('Flash Promo', $registry->get('custom_promo')->label());
    }
}
