<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Feature;

use Filament\Schemas\Components\Component;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Odden\MailBuilder\Audit\EmailPreFlightAuditor;
use Odden\MailBuilder\Compilers\AmpEmailCompiler;
use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Compilers\PlainTextExtractor;
use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Data\EmailSlot;
use Odden\MailBuilder\Editor\PreviewRenderer;
use Odden\MailBuilder\Enums\SlotGroup;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\Filament\Components\EmailSlotBuilder;
use Odden\MailBuilder\Schema\CustomSlotType;
use Odden\MailBuilder\Schema\SlotRegistry;
use Odden\MailBuilder\Schema\SlotSchemaRegistry;
use Odden\MailBuilder\Tests\Support\PlainTextSpotlightSlot;
use Odden\MailBuilder\Tests\Support\SpotlightSlot;
use Odden\MailBuilder\Tests\TestCase;
use ReflectionProperty;

class CustomSlotTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['view']->addNamespace('test-slots', __DIR__.'/../views');
        $this->registry()->flush();
        $this->registry()->register(new SpotlightSlot);
    }

    protected function tearDown(): void
    {
        $this->registry()->flush();

        parent::tearDown();
    }

    public function test_a_registered_slot_can_be_looked_up(): void
    {
        $this->assertTrue($this->registry()->has('spotlight'));
        $this->assertSame(['spotlight'], $this->registry()->keys());
        $this->assertInstanceOf(SpotlightSlot::class, $this->registry()->get('spotlight'));
        $this->assertNull($this->registry()->get('missing'));
        $this->assertCount(1, $this->registry()->all());
    }

    public function test_flush_forgets_every_registered_slot(): void
    {
        $this->registry()->flush();

        $this->assertSame([], $this->registry()->keys());
        $this->assertFalse($this->registry()->has('spotlight'));
    }

    public function test_registering_a_built_in_key_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->registry()->register($this->slotWithKey('hero'));
    }

    public function test_registering_a_key_twice_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->registry()->register(new SpotlightSlot);
    }

    public function test_registering_an_invalid_key_throws(): void
    {
        foreach (['Spotlight', '1spot', 'spot-light', 'spot light', ''] as $key) {
            try {
                $this->registry()->register($this->slotWithKey($key));
                $this->fail("Key [{$key}] was accepted.");
            } catch (InvalidArgumentException) {
                $this->assertFalse($this->registry()->has($key));
            }
        }
    }

    public function test_an_email_slot_round_trips_through_an_array(): void
    {
        $slot = EmailSlot::fromArray(['type' => 'spotlight', 'data' => ['headline' => 'Pinot']]);

        $this->assertInstanceOf(SpotlightSlot::class, $slot->type);
        $this->assertSame('spotlight', $slot->key());
        $this->assertSame('test-slots::spotlight', $slot->viewName());
        $this->assertSame(['type' => 'spotlight', 'data' => ['headline' => 'Pinot']], $slot->toArray());
        $this->assertEquals($slot, EmailSlot::fromArray($slot->toArray()));
    }

    public function test_built_in_slots_keep_their_enum_type(): void
    {
        $slot = EmailSlot::fromArray(['type' => 'hero', 'data' => []]);

        $this->assertSame(SlotType::Hero, $slot->type);
        $this->assertSame('hero', $slot->key());
    }

    public function test_an_unknown_key_still_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EmailSlot::fromArray(['type' => 'nonexistent', 'data' => []]);
    }

    public function test_a_document_appends_and_restores_a_custom_slot(): void
    {
        $document = (new EmailDocument)->append(new SpotlightSlot, ['headline' => 'Syrah']);

        $restored = EmailDocument::fromArray($document->toArray());

        $this->assertSame('spotlight', $restored->slots[0]->key());
    }

    public function test_a_custom_slot_compiles_through_its_view_with_the_theme(): void
    {
        $slot = EmailSlot::fromArray(['type' => 'spotlight', 'data' => ['headline' => 'Riesling']]);

        $html = app(EmailSlotCompiler::class)->compileSlot($slot, ['primary_color' => '#123456']);

        $this->assertStringContainsString('Riesling', $html);
        $this->assertStringContainsString('data-slot-key="spotlight"', $html);
        $this->assertStringContainsString('data-theme-primary="#123456"', $html);
    }

    public function test_a_custom_slot_compiles_inside_a_document(): void
    {
        $html = app(EmailSlotCompiler::class)->compileSlots([
            ['type' => 'spotlight', 'data' => ['headline' => 'Malbec']],
        ]);

        $this->assertStringContainsString('Malbec', $html);
    }

    public function test_a_visibility_rule_hides_a_custom_slot(): void
    {
        $slots = [[
            'type' => 'spotlight',
            'data' => ['headline' => 'VIP only'],
            'visibility' => ['field' => 'tier', 'operator' => 'equals', 'value' => 'vip'],
        ]];
        $document = EmailDocument::fromArray(['slots' => $slots]);
        $compiler = app(EmailSlotCompiler::class);

        $this->assertStringNotContainsString('VIP only', $compiler->compileDocument($document, ['context' => ['tier' => 'basic']]));
        $this->assertStringContainsString('VIP only', $compiler->compileDocument($document, ['context' => ['tier' => 'vip']]));
    }

    public function test_the_schema_export_includes_the_custom_slot(): void
    {
        $export = SlotSchemaRegistry::toArray();

        $exported = collect($export['slots'])->firstWhere('type', 'spotlight');

        $this->assertSame('Product Spotlight', $exported['label']);
        $this->assertSame('Spotlight', $exported['short_label']);
        $this->assertSame('Highlights one product.', $exported['description']);
        $this->assertSame(SlotGroup::Commerce->value, $exported['group']);
        $this->assertTrue($exported['supports_visibility']);
        $this->assertSame(['headline', 'blurb'], array_column($exported['fields'], 'key'));
        $this->assertSame('Product Spotlight', $export['labels']['spotlight']);
        $this->assertSame('spotlight', $export['slots'][array_key_last($export['slots'])]['type']);
        $this->assertSame(array_keys((SlotSchemaRegistry::for(SlotType::Hero))->toArray()), array_keys($exported));
    }

    public function test_the_schema_export_is_unchanged_without_custom_slots(): void
    {
        $this->registry()->flush();

        $export = SlotSchemaRegistry::toArray();

        $this->assertCount(count(SlotType::cases()), $export['slots']);
        $this->assertCount(count(SlotType::cases()), $export['labels']);
    }

    public function test_the_schema_registry_resolves_any_key(): void
    {
        $this->assertSame('spotlight', SlotSchemaRegistry::forKey('spotlight')?->key());
        $this->assertSame('hero', SlotSchemaRegistry::forKey('hero')?->key());
        $this->assertNull(SlotSchemaRegistry::forKey('nonexistent'));
    }

    public function test_the_preview_accepts_a_registered_key_and_rejects_an_unknown_one(): void
    {
        $renderer = app(PreviewRenderer::class);

        $this->assertStringContainsString('Gamay', $renderer->render(['slots' => [['type' => 'spotlight', 'data' => ['headline' => 'Gamay']]]]));

        $this->expectException(ValidationException::class);
        $renderer->render(['slots' => [['type' => 'nonexistent', 'data' => []]]]);
    }

    public function test_plain_text_falls_back_to_the_stripped_html(): void
    {
        $slot = EmailSlot::fromArray(['type' => 'spotlight', 'data' => ['headline' => 'Tempranillo', 'blurb' => 'Bold and dark.']]);

        $text = app(PlainTextExtractor::class)->extractFromSlot($slot);

        $this->assertStringContainsString('Tempranillo', $text);
        $this->assertStringContainsString('Bold and dark.', $text);
        $this->assertStringNotContainsString('<', $text);
    }

    public function test_plain_text_uses_the_slot_when_it_provides_its_own(): void
    {
        $this->registry()->register(new PlainTextSpotlightSlot);
        $slot = EmailSlot::fromArray(['type' => 'plain_spotlight', 'data' => ['headline' => 'Nebbiolo']]);

        $this->assertSame('SPOTLIGHT: Nebbiolo', app(PlainTextExtractor::class)->extractFromSlot($slot));
    }

    public function test_amp_and_the_auditor_handle_a_custom_slot(): void
    {
        $document = EmailDocument::fromArray(['slots' => [
            ['type' => 'spotlight', 'data' => ['headline' => 'Grenache']],
        ]]);

        $this->assertStringContainsString('⚡4email', app(AmpEmailCompiler::class)->compile($document));
        $this->assertNotEmpty(app(EmailPreFlightAuditor::class)->audit($document, ['subject' => 'Hello'])->checks);
    }

    public function test_the_filament_builder_lists_the_custom_slot(): void
    {
        $children = (new ReflectionProperty(Component::class, 'childComponents'))->getValue(EmailSlotBuilder::make());
        $names = array_map(fn (Component $block): string => $block->getName(), $children['default'] ?? $children);

        $this->assertContains('spotlight', $names);
    }

    private function registry(): SlotRegistry
    {
        return app(SlotRegistry::class);
    }

    private function slotWithKey(string $key): CustomSlotType
    {
        return new class($key) extends SpotlightSlot
        {
            public function __construct(private readonly string $customKey) {}

            public function key(): string
            {
                return $this->customKey;
            }
        };
    }
}
