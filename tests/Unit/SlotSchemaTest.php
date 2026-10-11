<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Unit;

use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Data\EmailSlot;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\Schema\FieldType;
use Odden\MailBuilder\Schema\SlotField;
use Odden\MailBuilder\Schema\SlotSchema;
use Odden\MailBuilder\Schema\SlotSchemaRegistry;
use Odden\MailBuilder\Tests\TestCase;

class SlotSchemaTest extends TestCase
{
    public function test_every_slot_type_is_described(): void
    {
        $described = array_map(fn (SlotType $type): string => $type->value, SlotSchemaRegistry::types());
        $all = array_map(fn (SlotType $type): string => $type->value, SlotType::cases());

        $this->assertEqualsCanonicalizing($all, $described);
        $this->assertSame($described, array_values(array_unique($described)), 'A type is listed twice.');
    }

    public function test_every_type_has_a_schema_that_belongs_to_it(): void
    {
        foreach (SlotType::cases() as $type) {
            $this->assertTrue(SlotSchemaRegistry::has($type), $type->value);

            $schema = SlotSchemaRegistry::for($type);

            $this->assertInstanceOf(SlotSchema::class, $schema, $type->value);
            $this->assertSame($type, $schema->type);
        }
    }

    public function test_field_keys_are_unique_within_a_slot_and_selects_have_options(): void
    {
        foreach (SlotSchemaRegistry::all() as $schema) {
            $keys = array_map(fn (SlotField $field): string => $field->key, $schema->fields);

            $this->assertSame($keys, array_values(array_unique($keys)), "{$schema->type->value} repeats a field key");

            foreach ($schema->fields as $field) {
                if ($field->type === FieldType::Select) {
                    $this->assertNotEmpty($field->options, "{$schema->type->value}.{$field->key} is a select with no options");
                }

                if ($field->type === FieldType::Items) {
                    $this->assertNotEmpty($field->items, "{$schema->type->value}.{$field->key} is a list with no item fields");
                }
            }
        }
    }

    public function test_every_field_is_read_by_the_slots_view(): void
    {
        foreach (SlotSchemaRegistry::all() as $schema) {
            $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/slots/'.str_replace('_', '-', $schema->type->value).'.blade.php');

            $this->assertIsString($view);

            foreach ($schema->fields as $field) {
                $this->assertMatchesRegularExpression("/['\"]{$field->key}['\"]/", $view, "The {$schema->type->value} view never reads [{$field->key}], so the schema has drifted from it.");

                foreach ($field->items as $item) {
                    $this->assertMatchesRegularExpression("/['\"]{$item->key}['\"]/", $view, "The {$schema->type->value} view never reads the item field [{$item->key}].");
                }
            }
        }
    }

    public function test_a_new_slot_built_from_the_schema_defaults_compiles(): void
    {
        $compiler = app(EmailSlotCompiler::class);

        foreach (SlotSchemaRegistry::all() as $schema) {
            $data = $schema->defaults();

            // Required fields with no default need a value before the slot can say anything.
            foreach ($schema->fields as $field) {
                if ($field->required && ! array_key_exists($field->key, $data)) {
                    $data[$field->key] = $field->type === FieldType::RichText ? '<p>Hello</p>' : 'Example';
                }
            }

            $html = $compiler->compileSlot(EmailSlot::fromArray(['type' => $schema->type->value, 'data' => $data]));

            $this->assertNotSame('', trim($html), $schema->type->value);
        }
    }

    public function test_defaults_that_depend_on_config_are_resolved_when_asked(): void
    {
        config(['mail-builder.footer.company_name' => 'Acme Inc.']);

        $this->assertSame('Acme Inc.', SlotSchemaRegistry::for(SlotType::Header)?->defaults()['brand_name']);
        $this->assertSame('Acme Inc.', SlotSchemaRegistry::for(SlotType::Footer)?->defaults()['company_name']);

        config(['mail-builder.footer.company_name' => 'Other Co.']);

        $this->assertSame('Other Co.', SlotSchemaRegistry::for(SlotType::Header)?->defaults()['brand_name']);
    }

    public function test_the_schema_exports_as_json_an_editor_can_read(): void
    {
        $json = json_encode(SlotSchemaRegistry::toArray(), JSON_THROW_ON_ERROR);
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(SlotSchemaRegistry::VERSION, $decoded['version']);
        $this->assertCount(count(SlotType::cases()), $decoded['slots']);

        $hero = collect($decoded['slots'])->firstWhere('type', 'hero');
        $this->assertSame('Hero Headline & Banner', $hero['label']);
        $this->assertSame('sparkles', $hero['icon']);

        $title = collect($hero['fields'])->firstWhere('key', 'title');
        $this->assertSame('text', $title['type']);
        $this->assertTrue($title['required']);
        $this->assertTrue($title['full_width']);

        $style = collect($hero['fields'])->firstWhere('key', 'button_style');
        $this->assertSame('primary', $style['default']);
        $this->assertSame('primary', $style['options'][0]['value']);

        $features = collect($decoded['slots'])->firstWhere('type', 'features');
        $items = collect($features['fields'])->firstWhere('key', 'items');
        $this->assertSame('items', $items['type']);
        $this->assertSame(['icon', 'title', 'text'], array_column($items['items'], 'key'));
        $this->assertSame(2, $items['default_items']);
    }

    public function test_every_slot_type_has_a_label_in_the_export(): void
    {
        $labels = SlotSchemaRegistry::toArray()['labels'];

        $this->assertCount(count(SlotType::cases()), $labels);
        $this->assertSame('Customer Quote & Testimonial', $labels['testimonial']);
        $this->assertSame(SlotType::Hero->label(), $labels['hero']);
    }

    public function test_a_list_of_strings_is_a_tags_field_with_array_defaults(): void
    {
        $table = SlotSchemaRegistry::for(SlotType::DataTable);
        $headers = collect($table?->fields)->firstWhere('key', 'headers');
        $rows = collect($table?->fields)->firstWhere('key', 'rows');

        $this->assertSame(FieldType::Tags, $headers?->type);
        $this->assertSame(['Feature', 'Starter', 'Professional', 'Enterprise'], $table?->defaults()['headers']);
        $this->assertSame('cells', $rows?->items[0]->key);
        $this->assertSame(FieldType::Tags, $rows?->items[0]->type);

        $pricing = SlotSchemaRegistry::for(SlotType::PricingGrid);
        $tiers = collect($pricing?->fields)->firstWhere('key', 'tiers');

        $this->assertSame(FieldType::Tags, collect($tiers?->items)->firstWhere('key', 'features')?->type);
    }

    public function test_select_defaults_are_one_of_their_options(): void
    {
        foreach (SlotSchemaRegistry::all() as $schema) {
            foreach ($schema->fields as $field) {
                $fields = [$field, ...$field->items];

                foreach ($fields as $candidate) {
                    $default = $candidate->resolveDefault();

                    if ($candidate->type !== FieldType::Select || $default === null) {
                        continue;
                    }

                    $values = array_map(fn (array $option): string => (string) $option['value'], $candidate->options);

                    $this->assertContains((string) $default, $values, "{$schema->type->value}.{$candidate->key} defaults to a value it does not offer.");
                }
            }
        }
    }

    public function test_advanced_fields_are_marked_and_kept_out_of_the_basic_form(): void
    {
        $hero = SlotSchemaRegistry::for(SlotType::Hero);

        $this->assertNotNull($hero);
        $this->assertContains('hero_image', array_map(fn (SlotField $f): string => $f->key, $hero->fields));
        $this->assertNotContains('hero_image', array_map(fn (SlotField $f): string => $f->key, $hero->basicFields()));
    }
}
