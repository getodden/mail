<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Schema;

use Odden\MailBuilder\Enums\ButtonStyle;
use Odden\MailBuilder\Enums\SlotType;

/**
 * The schemas of the slot types that have been described so far.
 *
 * A type without a schema (returned as null) is not yet editable in the drag-and-drop editor: it is still rendered
 * by its view and edited with its Filament block. Types are described one at a time.
 */
final class SlotSchemaRegistry
{
    /** Bumped when the shape of the exported schema changes. */
    public const VERSION = 1;

    /**
     * The slot types that have a schema, in the order an editor lists them.
     *
     * @return array<int, SlotType>
     */
    public static function types(): array
    {
        return [
            SlotType::Header,
            SlotType::Hero,
            SlotType::BodyText,
            SlotType::Button,
            SlotType::ImageBanner,
            SlotType::Features,
            SlotType::Divider,
            SlotType::Footer,
        ];
    }

    public static function has(SlotType $type): bool
    {
        return in_array($type, self::types(), true);
    }

    public static function for(SlotType $type): ?SlotSchema
    {
        return match ($type) {
            SlotType::Header => self::header(),
            SlotType::Hero => self::hero(),
            SlotType::BodyText => self::bodyText(),
            SlotType::Button => self::button(),
            SlotType::ImageBanner => self::imageBanner(),
            SlotType::Features => self::features(),
            SlotType::Divider => self::divider(),
            SlotType::Footer => self::footer(),
            default => null,
        };
    }

    /**
     * @return array<int, SlotSchema>
     */
    public static function all(): array
    {
        return array_map(fn (SlotType $type): SlotSchema => self::for($type) ?? throw new \LogicException("No schema for [{$type->value}]."), self::types());
    }

    /**
     * Everything an editor needs, ready to be sent as JSON.
     *
     * `labels` names every slot type, also the ones with no schema yet, so an editor can show a block it cannot edit.
     *
     * @return array{version: int, slots: array<int, array<string, mixed>>, labels: array<string, string>}
     */
    public static function toArray(): array
    {
        $labels = [];

        foreach (SlotType::cases() as $type) {
            $labels[$type->value] = $type->label();
        }

        return [
            'version' => self::VERSION,
            'slots' => array_map(fn (SlotSchema $schema): array => $schema->toArray(), self::all()),
            'labels' => $labels,
        ];
    }

    private static function companyName(): string
    {
        return (string) config('mail-builder.footer.company_name', config('app.name'));
    }

    private static function header(): SlotSchema
    {
        return new SlotSchema(SlotType::Header, 'globe-alt', [
            new SlotField('brand_name', FieldType::Text, 'Brand / Company Name', required: true, default: fn (): string => self::companyName()),
            new SlotField('tagline', FieldType::Text, 'Tagline or Category', placeholder: 'e.g. Next-Gen Marketing Engine'),
            new SlotField('logo_url', FieldType::Image, 'Logo Image URL', placeholder: 'https://example.com/logo.png'),
            new SlotField('show_date', FieldType::Toggle, 'Display Current Date', default: false),
            new SlotField('web_view_url', FieldType::Url, 'Web View Link URL', placeholder: 'https://example.com/emails/view/{{campaign.id}}'),
            new SlotField('logo_height', FieldType::Number, 'Logo Height (px)', default: 32, advanced: true),
            new SlotField('bg_color', FieldType::Color, 'Background Color', default: '#ffffff', advanced: true),
            new SlotField('text_color', FieldType::Color, 'Text Color', advanced: true),
        ], columns: 2);
    }

    private static function hero(): SlotSchema
    {
        return new SlotSchema(SlotType::Hero, 'sparkles', [
            new SlotField('title', FieldType::Text, 'Hero Headline', required: true, placeholder: 'e.g. Announcing Something Big 🚀', fullWidth: true),
            new SlotField('subtitle', FieldType::Textarea, 'Hero Subtitle / Description', rows: 2, fullWidth: true),
            new SlotField('badge', FieldType::Text, 'Badge Pill (Optional)', placeholder: 'e.g. NEW FEATURE'),
            new SlotField('button_style', FieldType::Select, 'Button Color Theme', default: 'primary', options: [
                ['value' => 'primary', 'label' => 'Primary Brand Color'],
                ['value' => 'success', 'label' => 'Success Green'],
                ['value' => 'dark', 'label' => 'Deep Navy / Dark'],
                ['value' => 'light', 'label' => 'Clean White'],
            ]),
            new SlotField('button_text', FieldType::Text, 'Hero CTA Button Text', placeholder: 'e.g. Explore Now'),
            new SlotField('button_url', FieldType::Url, 'Hero CTA Destination URL', placeholder: 'https://...'),
            new SlotField('bg_color', FieldType::Color, 'Background Color', default: '#0f172a'),
            new SlotField('text_color', FieldType::Color, 'Headline Text Color', default: '#ffffff'),
            new SlotField('hero_image', FieldType::Image, 'Hero Image URL', advanced: true),
            new SlotField('subtext_color', FieldType::Color, 'Subtitle Text Color', default: '#cbd5e1', advanced: true),
        ], columns: 2);
    }

    private static function bodyText(): SlotSchema
    {
        return new SlotSchema(SlotType::BodyText, 'document-text', [
            new SlotField('content', FieldType::RichText, 'Content Body', required: true, help: 'Supports merge tags: {{contact.first_name}}, {{company.name}}, etc.', fullWidth: true),
            new SlotField('align', FieldType::Select, 'Text Alignment', default: 'left', options: [
                ['value' => 'left', 'label' => 'Left'],
                ['value' => 'center', 'label' => 'Center'],
                ['value' => 'right', 'label' => 'Right'],
            ]),
            new SlotField('bg_color', FieldType::Color, 'Background Color', advanced: true),
            new SlotField('padding_top', FieldType::Number, 'Top Padding (px)', default: 24, advanced: true),
            new SlotField('padding_bottom', FieldType::Number, 'Bottom Padding (px)', default: 24, advanced: true),
        ], supportsVisibility: true);
    }

    private static function button(): SlotSchema
    {
        return new SlotSchema(SlotType::Button, 'cursor-arrow-rays', [
            new SlotField('text', FieldType::Text, 'Button Label', required: true, default: 'Get Started Now'),
            new SlotField('url', FieldType::Url, 'Target URL', required: true, default: 'https://example.com'),
            new SlotField('style', FieldType::Select, 'Button Color Theme', default: ButtonStyle::Primary->value, options: [
                ['value' => ButtonStyle::Primary->value, 'label' => 'Primary Brand Blue'],
                ['value' => ButtonStyle::Success->value, 'label' => 'Success Green'],
                ['value' => ButtonStyle::Dark->value, 'label' => 'Dark Charcoal'],
                ['value' => ButtonStyle::Secondary->value, 'label' => 'Slate Gray'],
                ['value' => ButtonStyle::Danger->value, 'label' => 'Crimson Danger'],
                ['value' => ButtonStyle::Outline->value, 'label' => 'Clean Outline Border'],
            ]),
            new SlotField('align', FieldType::Select, 'Button Placement', default: 'center', options: [
                ['value' => 'center', 'label' => 'Center (Recommended)'],
                ['value' => 'left', 'label' => 'Left Aligned'],
                ['value' => 'right', 'label' => 'Right Aligned'],
            ]),
            new SlotField('bg_color', FieldType::Color, 'Background Color', advanced: true),
            new SlotField('text_color', FieldType::Color, 'Text Color', advanced: true),
            new SlotField('border_color', FieldType::Color, 'Border Color', advanced: true),
            new SlotField('border_radius', FieldType::Text, 'Corner Radius', default: '8px', advanced: true),
            new SlotField('padding_y', FieldType::Number, 'Vertical Padding (px)', default: 24, advanced: true),
        ], columns: 2, supportsVisibility: true);
    }

    private static function imageBanner(): SlotSchema
    {
        return new SlotSchema(SlotType::ImageBanner, 'photo', [
            new SlotField('image_url', FieldType::Image, 'Image URL', required: true, placeholder: 'https://images.unsplash.com/...', fullWidth: true),
            new SlotField('alt_text', FieldType::Text, 'Accessibility Alt Text', required: true, placeholder: 'Describe image for screen readers and deliverability'),
            new SlotField('link_url', FieldType::Url, 'Destination Link URL (Optional)', placeholder: 'https://...'),
            new SlotField('caption', FieldType::Text, 'Caption Text (Optional)', placeholder: 'Small caption below image'),
            new SlotField('border_radius', FieldType::Select, 'Corner Style', default: '8px', options: [
                ['value' => '0px', 'label' => 'Sharp (0px)'],
                ['value' => '4px', 'label' => 'Subtle (4px)'],
                ['value' => '8px', 'label' => 'Rounded (8px)'],
                ['value' => '16px', 'label' => 'Pill / Soft (16px)'],
            ]),
            new SlotField('full_width', FieldType::Toggle, 'Edge-to-Edge Full Width (No Inset Margin)', default: false),
        ], columns: 2, supportsVisibility: true);
    }

    private static function features(): SlotSchema
    {
        return new SlotSchema(SlotType::Features, 'check-circle', [
            new SlotField('heading', FieldType::Text, 'Features Section Heading', placeholder: 'Why choose us?'),
            new SlotField('items', FieldType::Items, 'Feature Items', fullWidth: true, defaultItems: 2, itemColumns: 3, items: [
                new SlotField('icon', FieldType::Text, 'Icon / Emoji', required: true, default: '⚡'),
                new SlotField('title', FieldType::Text, 'Item Title', required: true),
                new SlotField('text', FieldType::Textarea, 'Description', required: true, rows: 2),
            ]),
            new SlotField('bg_color', FieldType::Color, 'Background Color', default: '#f8fafc', advanced: true),
        ]);
    }

    private static function divider(): SlotSchema
    {
        return new SlotSchema(SlotType::Divider, 'minus', [
            new SlotField('height', FieldType::Select, 'Spacer Height', default: 24, options: [
                ['value' => 16, 'label' => 'Small (16px)'],
                ['value' => 24, 'label' => 'Medium (24px)'],
                ['value' => 36, 'label' => 'Large (36px)'],
                ['value' => 48, 'label' => 'Extra Large (48px)'],
            ]),
            new SlotField('show_line', FieldType::Toggle, 'Draw Horizontal Divider Line', default: true),
            new SlotField('line_color', FieldType::Color, 'Line Color', advanced: true),
            new SlotField('line_style', FieldType::Select, 'Line Style', default: 'solid', advanced: true, options: [
                ['value' => 'solid', 'label' => 'Solid'],
                ['value' => 'dashed', 'label' => 'Dashed'],
                ['value' => 'dotted', 'label' => 'Dotted'],
            ]),
            new SlotField('bg_color', FieldType::Color, 'Background Color', advanced: true),
        ], columns: 2);
    }

    private static function footer(): SlotSchema
    {
        return new SlotSchema(SlotType::Footer, 'information-circle', [
            new SlotField('company_name', FieldType::Text, 'Legal Entity / Company Name', required: true, default: fn (): string => self::companyName()),
            new SlotField('address', FieldType::Text, 'Physical Mailing Address', required: true, placeholder: '123 Example St, Springfield'),
            new SlotField('notice', FieldType::Text, 'Permission Notice', placeholder: 'You are receiving this because you signed up on example.com'),
            new SlotField('unsubscribe_url', FieldType::Text, 'Unsubscribe URL / Merge Tag', required: true, default: '{{unsubscribe_url}}'),
            new SlotField('preferences_url', FieldType::Url, 'Preferences URL', advanced: true),
            new SlotField('copyright_year', FieldType::Number, 'Copyright Year', advanced: true),
        ], columns: 2);
    }
}
