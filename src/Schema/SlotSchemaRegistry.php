<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Schema;

use Odden\MailBuilder\Enums\ButtonStyle;
use Odden\MailBuilder\Enums\SlotGroup;
use Odden\MailBuilder\Enums\SlotType;

/**
 * The schemas of the slot types: what each type's `data` takes and how an editor shows it.
 *
 * Every slot type is described, and a test fails when a type is added to the enum without one. An editor still keeps a
 * block of a type it does not know (a document from a newer version): it can be moved, duplicated and deleted, not edited.
 */
final class SlotSchemaRegistry
{
    /** Bumped when the shape of the exported schema changes. */
    public const VERSION = 3;

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
            SlotType::TwoColumn,
            SlotType::ThreeColumn,
            SlotType::FourColumn,
            SlotType::AsymmetricColumns,
            SlotType::Features,
            SlotType::Testimonial,
            SlotType::StatBox,
            SlotType::SocialLinks,
            SlotType::VideoCard,
            SlotType::PricingGrid,
            SlotType::ProductCatalog,
            SlotType::DynamicFeed,
            SlotType::RssFeed,
            SlotType::Accordion,
            SlotType::OrderReceipt,
            SlotType::DataTable,
            SlotType::CouponCode,
            SlotType::CountdownTimer,
            SlotType::RatingBar,
            SlotType::AppBadges,
            SlotType::Divider,
            SlotType::LabeledDivider,
            SlotType::Html,
            SlotType::Footer,
        ];
    }

    public static function has(SlotType $type): bool
    {
        return in_array($type, self::types(), true);
    }

    public static function for(SlotType $type): SlotSchema
    {
        return match ($type) {
            SlotType::Header => self::header(),
            SlotType::Hero => self::hero(),
            SlotType::BodyText => self::bodyText(),
            SlotType::Button => self::button(),
            SlotType::ImageBanner => self::imageBanner(),
            SlotType::TwoColumn => self::twoColumn(),
            SlotType::ThreeColumn => self::threeColumn(),
            SlotType::FourColumn => self::fourColumn(),
            SlotType::AsymmetricColumns => self::asymmetricColumns(),
            SlotType::Features => self::features(),
            SlotType::Testimonial => self::testimonial(),
            SlotType::StatBox => self::statBox(),
            SlotType::SocialLinks => self::socialLinks(),
            SlotType::VideoCard => self::videoCard(),
            SlotType::PricingGrid => self::pricingGrid(),
            SlotType::ProductCatalog => self::productCatalog(),
            SlotType::DynamicFeed => self::dynamicFeed(),
            SlotType::RssFeed => self::rssFeed(),
            SlotType::Accordion => self::accordion(),
            SlotType::OrderReceipt => self::orderReceipt(),
            SlotType::DataTable => self::dataTable(),
            SlotType::CouponCode => self::couponCode(),
            SlotType::CountdownTimer => self::countdownTimer(),
            SlotType::RatingBar => self::ratingBar(),
            SlotType::AppBadges => self::appBadges(),
            SlotType::Divider => self::divider(),
            SlotType::LabeledDivider => self::labeledDivider(),
            SlotType::Html => self::html(),
            SlotType::Footer => self::footer(),
        };
    }

    /**
     * @return array<int, SlotSchema>
     */
    public static function all(): array
    {
        return array_map(fn (SlotType $type): SlotSchema => self::for($type), self::types());
    }

    /**
     * Everything an editor needs, ready to be sent as JSON.
     *
     * `labels` names every slot type, also the ones with no schema yet, so an editor can show a block it cannot edit.
     *
     * @return array{version: int, groups: array<int, array{key: string, label: string}>, slots: array<int, array<string, mixed>>, labels: array<string, string>}
     */
    public static function toArray(): array
    {
        $labels = [];

        foreach (SlotType::cases() as $type) {
            $labels[$type->value] = $type->label();
        }

        return [
            'version' => self::VERSION,
            'groups' => array_map(fn (SlotGroup $group): array => ['key' => $group->value, 'label' => $group->label()], SlotGroup::cases()),
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

    private static function twoColumn(): SlotSchema
    {
        return new SlotSchema(SlotType::TwoColumn, 'view-columns', [
            new SlotField('left_title', FieldType::Text, 'Left Column Title', required: true, placeholder: 'Feature or Topic A'),
            new SlotField('right_title', FieldType::Text, 'Right Column Title', required: true, placeholder: 'Feature or Topic B'),
            new SlotField('left_body', FieldType::Textarea, 'Left Column Content', required: true, rows: 3),
            new SlotField('right_body', FieldType::Textarea, 'Right Column Content', required: true, rows: 3),
            new SlotField('left_image', FieldType::Image, 'Left Image URL', placeholder: 'https://...'),
            new SlotField('right_image', FieldType::Image, 'Right Image URL', placeholder: 'https://...'),
            new SlotField('left_button_text', FieldType::Text, 'Left Link Label', placeholder: 'Learn More'),
            new SlotField('right_button_text', FieldType::Text, 'Right Link Label', placeholder: 'Learn More'),
            new SlotField('left_button_url', FieldType::Url, 'Left Destination URL', placeholder: 'https://...'),
            new SlotField('right_button_url', FieldType::Url, 'Right Destination URL', placeholder: 'https://...'),
            new SlotField('reverse_stack_on_mobile', FieldType::Toggle, 'Reverse Column Stack on Mobile', default: false),
            new SlotField('bg_color', FieldType::Color, 'Background Color', advanced: true),
        ], columns: 2, supportsVisibility: true);
    }

    private static function threeColumn(): SlotSchema
    {
        return new SlotSchema(SlotType::ThreeColumn, 'squares-plus', [
            new SlotField('heading', FieldType::Text, 'Section Heading', placeholder: 'e.g. Featured Highlights', fullWidth: true),
            new SlotField('subtitle', FieldType::Text, 'Subtitle', placeholder: 'e.g. Explore our newest feature releases', fullWidth: true),
            new SlotField('columns', FieldType::Items, 'Three Columns', fullWidth: true, defaultItems: 3, itemColumns: 2, items: [
                new SlotField('title', FieldType::Text, 'Column Title', required: true),
                new SlotField('image_url', FieldType::Image, 'Image URL'),
                new SlotField('description', FieldType::Textarea, 'Description', rows: 2, fullWidth: true),
                new SlotField('button_text', FieldType::Text, 'CTA Button Text', default: 'Learn More'),
                new SlotField('button_url', FieldType::Url, 'Target Link', default: '#'),
            ]),
        ], supportsVisibility: true);
    }

    private static function fourColumn(): SlotSchema
    {
        return new SlotSchema(SlotType::FourColumn, 'squares-2x2', [
            new SlotField('heading', FieldType::Text, 'Header Label', default: 'Trusted by Industry Leaders', fullWidth: true),
            new SlotField('items', FieldType::Items, 'Brand / Partner Logos', fullWidth: true, defaultItems: 4, itemColumns: 3, items: [
                new SlotField('label', FieldType::Text, 'Brand / Partner Name', required: true),
                new SlotField('image_url', FieldType::Image, 'Logo Image URL (PNG/SVG/WebP)'),
                new SlotField('url', FieldType::Url, 'Website Link (Optional)'),
            ]),
        ], supportsVisibility: true);
    }

    private static function asymmetricColumns(): SlotSchema
    {
        return new SlotSchema(SlotType::AsymmetricColumns, 'rectangle-group', [
            new SlotField('ratio', FieldType::Select, 'Layout Distribution', required: true, default: '30_70', fullWidth: true, options: [
                ['value' => '30_70', 'label' => '1/3 Left Sidebar + 2/3 Main Story'],
                ['value' => '70_30', 'label' => '2/3 Main Story + 1/3 Right Sidebar'],
            ]),
            new SlotField('left_title', FieldType::Text, 'Left Title'),
            new SlotField('left_image', FieldType::Image, 'Left Image URL'),
            new SlotField('left_body', FieldType::Textarea, 'Left Copy', rows: 3, fullWidth: true),
            new SlotField('left_bg_color', FieldType::Color, 'Left Background Color', default: '#F8FAFC'),
            new SlotField('right_title', FieldType::Text, 'Right Title'),
            new SlotField('right_button_text', FieldType::Text, 'CTA Button Text', default: 'Read Story'),
            new SlotField('right_button_url', FieldType::Url, 'CTA Button URL', default: '#'),
            new SlotField('right_bg_color', FieldType::Color, 'Right Background Color', default: '#FFFFFF'),
            new SlotField('right_body', FieldType::Textarea, 'Right Copy', rows: 3, fullWidth: true),
        ], columns: 2, supportsVisibility: true);
    }

    private static function testimonial(): SlotSchema
    {
        return new SlotSchema(SlotType::Testimonial, 'chat-bubble-left-right', [
            new SlotField('quote', FieldType::Textarea, 'Customer Quote', required: true, rows: 3, fullWidth: true),
            new SlotField('author', FieldType::Text, 'Author Name', required: true),
            new SlotField('role', FieldType::Text, 'Job Role / Title', placeholder: 'e.g. VP of Growth'),
            new SlotField('company', FieldType::Text, 'Company Name', placeholder: 'e.g. Acme Corp'),
            new SlotField('rating', FieldType::Select, 'Star Rating', default: 5, options: [
                ['value' => 5, 'label' => '★★★★★ (5 Stars)'],
                ['value' => 4, 'label' => '★★★★☆ (4 Stars)'],
                ['value' => 3, 'label' => '★★★☆☆ (3 Stars)'],
            ]),
            new SlotField('avatar_url', FieldType::Image, 'Avatar Image URL', advanced: true),
            new SlotField('accent_color', FieldType::Color, 'Accent Color', advanced: true),
            new SlotField('bg_color', FieldType::Color, 'Background Color', advanced: true),
        ], columns: 2);
    }

    private static function statBox(): SlotSchema
    {
        return new SlotSchema(SlotType::StatBox, 'chart-bar', [
            new SlotField('heading', FieldType::Text, 'Section Heading (Optional)', placeholder: 'e.g. Platform Highlights'),
            new SlotField('stats', FieldType::Items, 'Key Metrics', fullWidth: true, defaultItems: 2, itemColumns: 3, items: [
                new SlotField('value', FieldType::Text, 'Metric / Number', required: true, placeholder: 'e.g. 99.9% or +45%'),
                new SlotField('label', FieldType::Text, 'Label', required: true, placeholder: 'e.g. Uptime SLA'),
                new SlotField('change', FieldType::Text, 'Badge / Pill (Optional)', placeholder: 'e.g. +14% YoY'),
            ]),
            new SlotField('bg_color', FieldType::Color, 'Background Color', default: '#f1f5f9', advanced: true),
        ]);
    }

    private static function socialLinks(): SlotSchema
    {
        return new SlotSchema(SlotType::SocialLinks, 'share', [
            new SlotField('links', FieldType::Items, 'Social Profiles', fullWidth: true, defaultItems: 3, itemColumns: 2, items: [
                new SlotField('network', FieldType::Select, 'Network', required: true, default: 'Twitter', options: [
                    ['value' => 'Twitter', 'label' => 'Twitter / X'],
                    ['value' => 'LinkedIn', 'label' => 'LinkedIn'],
                    ['value' => 'GitHub', 'label' => 'GitHub'],
                    ['value' => 'YouTube', 'label' => 'YouTube'],
                    ['value' => 'Facebook', 'label' => 'Facebook'],
                    ['value' => 'Website', 'label' => 'Website'],
                ]),
                new SlotField('url', FieldType::Url, 'Profile URL', required: true),
            ]),
            new SlotField('align', FieldType::Select, 'Alignment', default: 'center', advanced: true, options: [
                ['value' => 'center', 'label' => 'Center'],
                ['value' => 'left', 'label' => 'Left'],
                ['value' => 'right', 'label' => 'Right'],
            ]),
            new SlotField('bg_color', FieldType::Color, 'Background Color', advanced: true),
        ]);
    }

    private static function html(): SlotSchema
    {
        return new SlotSchema(SlotType::Html, 'code-bracket', [
            new SlotField('html', FieldType::Textarea, 'Raw HTML Code', required: true, rows: 8, fullWidth: true),
            new SlotField('bg_color', FieldType::Color, 'Background Color', advanced: true),
        ]);
    }

    private static function videoCard(): SlotSchema
    {
        return new SlotSchema(SlotType::VideoCard, 'play-circle', [
            new SlotField('video_url', FieldType::Url, 'Video Destination URL (YouTube, Vimeo, Loom)', required: true, placeholder: 'https://www.youtube.com/watch?v=...', fullWidth: true),
            new SlotField('thumbnail_url', FieldType::Image, 'Custom Thumbnail Image URL', placeholder: 'https://images.unsplash.com/...'),
            new SlotField('badge', FieldType::Text, 'Badge Text', default: 'VIDEO DEMO'),
            new SlotField('title', FieldType::Text, 'Video Title', required: true, default: 'Watch Product Tour & Feature Walkthrough', fullWidth: true),
            new SlotField('subtitle', FieldType::Textarea, 'Video Description / Duration', rows: 2, placeholder: 'e.g. 3 min overview of platform features', fullWidth: true),
            new SlotField('border_radius', FieldType::Select, 'Corner Style', default: '12px', advanced: true, options: [
                ['value' => '0px', 'label' => 'Sharp (0px)'],
                ['value' => '8px', 'label' => 'Rounded (8px)'],
                ['value' => '12px', 'label' => 'Soft (12px)'],
                ['value' => '20px', 'label' => 'Pill (20px)'],
            ]),
        ], columns: 2, supportsVisibility: true);
    }

    private static function pricingGrid(): SlotSchema
    {
        return new SlotSchema(SlotType::PricingGrid, 'currency-dollar', [
            new SlotField('heading', FieldType::Text, 'Section Heading', placeholder: 'Simple, transparent pricing'),
            new SlotField('subtitle', FieldType::Text, 'Subtitle', placeholder: 'Start free, upgrade as you grow'),
            new SlotField('tiers', FieldType::Items, 'Pricing Tiers', fullWidth: true, defaultItems: 2, itemColumns: 3, items: [
                new SlotField('name', FieldType::Text, 'Plan Name', required: true, placeholder: 'Starter'),
                new SlotField('price', FieldType::Text, 'Price', required: true, placeholder: '$49'),
                new SlotField('frequency', FieldType::Text, 'Billing Frequency', default: '/ month', placeholder: '/ month'),
                new SlotField('features', FieldType::Tags, 'Feature Bullets', help: 'One feature per line.', fullWidth: true),
                new SlotField('button_text', FieldType::Text, 'CTA Button Text', required: true, default: 'Get Started'),
                new SlotField('button_url', FieldType::Url, 'CTA Destination URL', required: true, default: 'https://example.com/signup'),
                new SlotField('is_popular', FieldType::Toggle, 'Highlight as Most Popular', default: false),
            ]),
        ], supportsVisibility: true);
    }

    private static function productCatalog(): SlotSchema
    {
        return new SlotSchema(SlotType::ProductCatalog, 'shopping-bag', [
            new SlotField('section_title', FieldType::Text, 'Section Header', placeholder: 'Recommended For You / Items in Your Cart', fullWidth: true),
            new SlotField('section_subtitle', FieldType::Text, 'Section Subtitle', placeholder: 'Hand-picked recommendations based on your preferences', fullWidth: true),
            new SlotField('columns', FieldType::Select, 'Columns Count', required: true, default: 2, options: [
                ['value' => 1, 'label' => '1 Column (Large Featured Cards)'],
                ['value' => 2, 'label' => '2 Columns (Standard Grid)'],
                ['value' => 3, 'label' => '3 Columns (Compact Catalog)'],
            ]),
            new SlotField('show_badges', FieldType::Toggle, 'Show Discount & Stock Badges', default: true),
            new SlotField('card_background', FieldType::Color, 'Card Background Color', default: '#ffffff'),
            new SlotField('card_border', FieldType::Color, 'Card Border Color', default: '#e2e8f0'),
            new SlotField('products', FieldType::Items, 'Products / Cart Line Items', fullWidth: true, defaultItems: 2, itemColumns: 2, items: [
                new SlotField('title', FieldType::Text, 'Product Title', required: true),
                new SlotField('price', FieldType::Text, 'Current Price', required: true, placeholder: '$49.00'),
                new SlotField('compare_at_price', FieldType::Text, 'Compare-at / Original Price (Strikethrough)', placeholder: '$79.00'),
                new SlotField('savings_badge', FieldType::Text, 'Discount Badge (e.g. Save 30%)', placeholder: 'Save 30%'),
                new SlotField('stock_badge', FieldType::Text, 'Stock Badge (e.g. In Stock / Low Stock)', placeholder: 'In Stock'),
                new SlotField('image_url', FieldType::Image, 'Product Image URL', required: true),
                new SlotField('button_text', FieldType::Text, 'Button CTA Label', required: true, default: 'Buy Now'),
                new SlotField('button_url', FieldType::Url, 'Checkout / Product URL', required: true),
                new SlotField('description', FieldType::Textarea, 'Short Description', rows: 2, fullWidth: true),
            ]),
            new SlotField('button_background', FieldType::Color, 'Button Background Color', advanced: true),
            new SlotField('button_color', FieldType::Color, 'Button Text Color', default: '#ffffff', advanced: true),
        ], columns: 2, supportsVisibility: true);
    }

    private static function dynamicFeed(): SlotSchema
    {
        return new SlotSchema(SlotType::DynamicFeed, 'arrow-path', [
            new SlotField('heading', FieldType::Text, 'Section Heading', default: 'Recommended For You', fullWidth: true),
            new SlotField('subtitle', FieldType::Text, 'Subtitle', placeholder: 'Curated recommendations based on your preferences', fullWidth: true),
            new SlotField('items', FieldType::Items, 'Dynamic Products / Content Items', fullWidth: true, defaultItems: 2, itemColumns: 2, items: [
                new SlotField('title', FieldType::Text, 'Item Title', required: true),
                new SlotField('price', FieldType::Text, 'Price / Badge', placeholder: 'e.g. $199 / mo'),
                new SlotField('image_url', FieldType::Image, 'Thumbnail Image URL'),
                new SlotField('button_url', FieldType::Url, 'Target URL', required: true),
                new SlotField('button_text', FieldType::Text, 'Button CTA', default: 'View Details'),
                new SlotField('description', FieldType::Textarea, 'Description', rows: 2, fullWidth: true),
            ]),
        ], supportsVisibility: true);
    }

    private static function rssFeed(): SlotSchema
    {
        return new SlotSchema(SlotType::RssFeed, 'rss', [
            new SlotField('heading', FieldType::Text, 'Feed Title', default: 'Latest Articles & Updates', fullWidth: true),
            new SlotField('subtitle', FieldType::Text, 'Subtitle', placeholder: 'Latest news from our RSS syndication feed', fullWidth: true),
            new SlotField('items', FieldType::Items, 'Feed Articles', fullWidth: true, defaultItems: 2, itemColumns: 2, items: [
                new SlotField('title', FieldType::Text, 'Article Title', required: true),
                new SlotField('published_at', FieldType::Text, 'Published Date', placeholder: 'e.g. Oct 15, 2026'),
                new SlotField('author', FieldType::Text, 'Author / Source', placeholder: 'e.g. Editorial Desk'),
                new SlotField('url', FieldType::Url, 'Article Link', required: true),
                new SlotField('summary', FieldType::Textarea, 'Article Summary', rows: 2, fullWidth: true),
            ]),
        ], supportsVisibility: true);
    }

    private static function accordion(): SlotSchema
    {
        return new SlotSchema(SlotType::Accordion, 'question-mark-circle', [
            new SlotField('heading', FieldType::Text, 'Section Heading', default: 'Frequently Asked Questions', fullWidth: true),
            new SlotField('subtitle', FieldType::Text, 'Section Subtitle', placeholder: 'Quick answers to common questions', fullWidth: true),
            new SlotField('items', FieldType::Items, 'Questions & Answers', fullWidth: true, defaultItems: 2, itemColumns: 1, items: [
                new SlotField('question', FieldType::Text, 'Question', required: true, placeholder: 'e.g. Can I upgrade my plan later?'),
                new SlotField('answer', FieldType::Textarea, 'Answer', required: true, rows: 2, placeholder: 'e.g. Yes, you can change your subscription anytime in account billing.'),
            ]),
        ], supportsVisibility: true);
    }

    private static function orderReceipt(): SlotSchema
    {
        return new SlotSchema(SlotType::OrderReceipt, 'receipt-percent', [
            new SlotField('order_number', FieldType::Text, 'Order / Reference #', required: true, default: '#1001'),
            new SlotField('order_date', FieldType::Text, 'Order Date', placeholder: 'e.g. October 24, 2026'),
            new SlotField('currency', FieldType::Text, 'Currency Symbol', default: '$'),
            new SlotField('items', FieldType::Items, 'Purchased Items', fullWidth: true, defaultItems: 1, itemColumns: 4, items: [
                new SlotField('item', FieldType::Text, 'Product / Service Name', required: true),
                new SlotField('qty', FieldType::Number, 'Qty', required: true, default: 1),
                new SlotField('unit_price', FieldType::Text, 'Unit Price', required: true, default: '0.00'),
                new SlotField('total', FieldType::Text, 'Line Total', required: true, default: '0.00'),
            ]),
            new SlotField('subtotal', FieldType::Text, 'Subtotal Amount', default: '0.00'),
            new SlotField('shipping', FieldType::Text, 'Shipping Fee', default: '0.00'),
            new SlotField('tax', FieldType::Text, 'Tax / VAT', default: '0.00'),
            new SlotField('grand_total', FieldType::Text, 'Grand Total', required: true, default: '0.00'),
            new SlotField('notes', FieldType::Textarea, 'Payment Notes (Optional)', rows: 2, fullWidth: true),
        ], supportsVisibility: true);
    }

    private static function dataTable(): SlotSchema
    {
        return new SlotSchema(SlotType::DataTable, 'table-cells', [
            new SlotField('heading', FieldType::Text, 'Table Heading (Optional)', placeholder: 'Feature & Tier Comparison', fullWidth: true),
            new SlotField('subtitle', FieldType::Text, 'Table Subtitle (Optional)', placeholder: 'Compare available features across plans', fullWidth: true),
            new SlotField('headers', FieldType::Tags, 'Column Header Names', help: 'One column name per line.', default: ['Feature', 'Starter', 'Professional', 'Enterprise'], fullWidth: true),
            new SlotField('rows', FieldType::Items, 'Table Rows', fullWidth: true, defaultItems: 3, items: [
                new SlotField('cells', FieldType::Tags, 'Row Cell Values (Matching Column Count)', help: 'One cell per line, in column order.', required: true, default: ['Feature name', 'Included', 'Included', 'Included']),
            ]),
            new SlotField('bg_color', FieldType::Color, 'Background Color', advanced: true),
        ], supportsVisibility: true);
    }

    private static function couponCode(): SlotSchema
    {
        return new SlotSchema(SlotType::CouponCode, 'ticket', [
            new SlotField('headline', FieldType::Text, 'Voucher Headline', required: true, default: 'Special Discount Voucher'),
            new SlotField('discount_badge', FieldType::Text, 'Discount Badge (e.g. 20% OFF)', default: '20% OFF'),
            new SlotField('code', FieldType::Text, 'Promo / Coupon Code', required: true, placeholder: 'SAVE20'),
            new SlotField('expires_at', FieldType::Text, 'Expiration Notice', placeholder: 'e.g. October 31, 2026 or In 3 Days'),
            new SlotField('button_text', FieldType::Text, 'Redeem CTA Button Text', default: 'Claim Offer'),
            new SlotField('button_url', FieldType::Url, 'Redeem CTA URL', default: '#'),
            new SlotField('description', FieldType::Textarea, 'Terms or Description', default: 'Apply this promo code at checkout to claim your savings.', rows: 2, fullWidth: true),
            new SlotField('card_background', FieldType::Color, 'Voucher Background Color', default: '#f8fafc'),
            new SlotField('border_color', FieldType::Color, 'Dashed Border Color', default: '#94a3b8'),
            new SlotField('code_background', FieldType::Color, 'Code Box Background Color', default: '#ffffff'),
        ], columns: 2, supportsVisibility: true);
    }

    private static function countdownTimer(): SlotSchema
    {
        return new SlotSchema(SlotType::CountdownTimer, 'clock', [
            new SlotField('badge', FieldType::Text, 'Urgency Badge', default: '⚡ LIMITED TIME OFFER'),
            new SlotField('deadline_text', FieldType::Text, 'Deadline Description', required: true, default: 'Offer expires Sunday at Midnight'),
            new SlotField('heading', FieldType::Text, 'Headline', required: true, default: 'Unlock 20% Off Your Annual Plan', fullWidth: true),
            new SlotField('subtext', FieldType::Textarea, 'Promotional Subtext', default: 'Lock in early-bird pricing before this promotional tier expires.', rows: 2, fullWidth: true),
            new SlotField('button_text', FieldType::Text, 'Action Button Label', required: true, default: 'Claim Your Discount Now'),
            new SlotField('button_url', FieldType::Url, 'Action Destination URL', required: true, default: 'https://example.com/checkout?promo=FLASH20'),
        ], columns: 2, supportsVisibility: true);
    }

    private static function ratingBar(): SlotSchema
    {
        return new SlotSchema(SlotType::RatingBar, 'star', [
            new SlotField('question', FieldType::Text, 'Survey Question Prompt', required: true, default: 'How likely are you to recommend us to a colleague?', fullWidth: true),
            new SlotField('scale_type', FieldType::Select, 'Rating Scale', required: true, default: '10', options: [
                ['value' => '10', 'label' => '0 to 10 Scale (Industry Standard NPS)'],
                ['value' => '5', 'label' => '1 to 5 Stars / CSAT Scale'],
            ]),
            new SlotField('base_url', FieldType::Text, 'Destination Rating Endpoint / Webhook', required: true, default: '/feedback/nps/{{nps_token}}', help: 'Each button appends /{score} to this URL.'),
            new SlotField('low_label', FieldType::Text, 'Lowest Score Meaning', required: true, default: 'Not at all likely'),
            new SlotField('high_label', FieldType::Text, 'Highest Score Meaning', required: true, default: 'Extremely likely'),
        ], columns: 2, supportsVisibility: true);
    }

    private static function appBadges(): SlotSchema
    {
        return new SlotSchema(SlotType::AppBadges, 'device-phone-mobile', [
            new SlotField('heading', FieldType::Text, 'Section Heading', placeholder: 'Download Our Mobile App', fullWidth: true),
            new SlotField('subtitle', FieldType::Text, 'Subtitle', placeholder: 'Manage your account on the go', fullWidth: true),
            new SlotField('app_store_url', FieldType::Url, 'Apple App Store Link URL', default: '#', placeholder: 'https://apps.apple.com/app/...'),
            new SlotField('google_play_url', FieldType::Url, 'Google Play Store Link URL', default: '#', placeholder: 'https://play.google.com/store/apps/...'),
            new SlotField('align', FieldType::Select, 'Alignment', default: 'center', options: [
                ['value' => 'center', 'label' => 'Center Aligned'],
                ['value' => 'left', 'label' => 'Left Aligned'],
            ]),
            new SlotField('bg_color', FieldType::Color, 'Background Color', advanced: true),
        ], columns: 2, supportsVisibility: true);
    }

    private static function labeledDivider(): SlotSchema
    {
        return new SlotSchema(SlotType::LabeledDivider, 'ellipsis-horizontal', [
            new SlotField('label', FieldType::Text, 'Divider Center Label / Badge', required: true, default: 'OR'),
            new SlotField('badge_bg', FieldType::Color, 'Badge Background Color', default: '#f1f5f9'),
            new SlotField('text_color', FieldType::Color, 'Badge Text Color', default: '#64748b'),
            new SlotField('line_color', FieldType::Color, 'Divider Line Color', default: '#e2e8f0'),
            new SlotField('padding_y', FieldType::Select, 'Vertical Spacing', default: 24, options: [
                ['value' => 16, 'label' => 'Compact (16px)'],
                ['value' => 24, 'label' => 'Standard (24px)'],
                ['value' => 36, 'label' => 'Generous (36px)'],
            ]),
            new SlotField('bg_color', FieldType::Color, 'Background Color', advanced: true),
        ], columns: 2, supportsVisibility: true);
    }
}
