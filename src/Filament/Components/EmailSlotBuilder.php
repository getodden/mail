<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Filament\Components;

use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Compilers\PlainTextExtractor;
use Odden\MailBuilder\Enums\ButtonStyle;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\Presets\PresetRegistry;
use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Support\Icons\Heroicon;

class EmailSlotBuilder
{
    /**
     * Create a pre-configured Filament Builder for responsive email slots.
     */
    public static function make(string $name = 'slots'): Builder
    {
        return Builder::make($name)
            ->label('Email Visual Slots')
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->blocks([
                self::getHeaderBlock(),
                self::getHeroBlock(),
                self::getBodyTextBlock(),
                self::getButtonBlock(),
                self::getTwoColumnBlock(),
                self::getFeaturesBlock(),
                self::getTestimonialBlock(),
                self::getStatBoxBlock(),
                self::getDividerBlock(),
                self::getSocialLinksBlock(),
                self::getFooterBlock(),
                self::getImageBannerBlock(),
                self::getVideoCardBlock(),
                self::getPricingGridBlock(),
                self::getRatingBarBlock(),
                self::getCountdownTimerBlock(),
                self::getAccordionBlock(),
                self::getDynamicFeedBlock(),
                self::getOrderReceiptBlock(),
                self::getRssFeedBlock(),
                self::getThreeColumnBlock(),
                self::getFourColumnBlock(),
                self::getAsymmetricColumnsBlock(),
                self::getProductCatalogBlock(),
                self::getCouponCodeBlock(),
                self::getAppBadgesBlock(),
                self::getDataTableBlock(),
                self::getLabeledDividerBlock(),
                self::getHtmlBlock(),
            ]);
    }

    /**
     * Header Block Definition.
     */
    public static function getHeaderBlock(): Block
    {
        return Block::make(SlotType::Header->value)
            ->label(SlotType::Header->label())
            ->icon(Heroicon::GlobeAlt)
            ->schema([
                TextInput::make('brand_name')
                    ->label('Brand / Company Name')
                    ->default(fn (): string => (string) config('mail-builder.footer.company_name', config('app.name')))
                    ->required(),
                TextInput::make('tagline')
                    ->label('Tagline or Category')
                    ->placeholder('e.g. Next-Gen Marketing Engine'),
                TextInput::make('logo_url')
                    ->label('Logo Image URL')
                    ->placeholder('https://example.com/logo.png'),
                Toggle::make('show_date')
                    ->label('Display Current Date')
                    ->default(false),
                TextInput::make('web_view_url')
                    ->label('Web View Link URL')
                    ->placeholder('https://example.com/emails/view/{{campaign.id}}'),
            ])
            ->columns(2);
    }

    /**
     * Hero Block Definition.
     */
    public static function getHeroBlock(): Block
    {
        return Block::make(SlotType::Hero->value)
            ->label(SlotType::Hero->label())
            ->icon(Heroicon::Sparkles)
            ->schema([
                TextInput::make('title')
                    ->label('Hero Headline')
                    ->placeholder('e.g. Announcing Something Big 🚀')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('subtitle')
                    ->label('Hero Subtitle / Description')
                    ->rows(2)
                    ->columnSpanFull(),
                TextInput::make('badge')
                    ->label('Badge Pill (Optional)')
                    ->placeholder('e.g. NEW FEATURE'),
                Select::make('button_style')
                    ->label('Button Color Theme')
                    ->options([
                        'primary' => 'Primary Brand Color',
                        'success' => 'Success Green',
                        'dark' => 'Deep Navy / Dark',
                        'light' => 'Clean White',
                    ])
                    ->default('primary'),
                TextInput::make('button_text')
                    ->label('Hero CTA Button Text')
                    ->placeholder('e.g. Explore Now'),
                TextInput::make('button_url')
                    ->label('Hero CTA Destination URL')
                    ->placeholder('https://...'),
                ColorPicker::make('bg_color')
                    ->label('Background Color')
                    ->default('#0f172a'),
                ColorPicker::make('text_color')
                    ->label('Headline Text Color')
                    ->default('#ffffff'),
            ])
            ->columns(2);
    }

    /**
     * Body Text Block Definition.
     */
    public static function getBodyTextBlock(): Block
    {
        return Block::make(SlotType::BodyText->value)
            ->label(SlotType::BodyText->label())
            ->icon(Heroicon::DocumentText)
            ->schema([
                RichEditor::make('content')
                    ->label('Content Body')
                    ->helperText('Supports merge tags: {{contact.first_name}}, {{company.name}}, etc.')
                    ->required()
                    ->columnSpanFull(),
                Select::make('align')
                    ->label('Text Alignment')
                    ->options([
                        'left' => 'Left',
                        'center' => 'Center',
                        'right' => 'Right',
                    ])
                    ->default('left'),
                self::getVisibilityFieldset()->columnSpanFull(),
            ]);
    }

    /**
     * Button Block Definition.
     */
    public static function getButtonBlock(): Block
    {
        return Block::make(SlotType::Button->value)
            ->label(SlotType::Button->label())
            ->icon(Heroicon::CursorArrowRays)
            ->schema([
                TextInput::make('text')
                    ->label('Button Label')
                    ->default('Get Started Now')
                    ->required(),
                TextInput::make('url')
                    ->label('Target URL')
                    ->default('https://example.com')
                    ->required(),
                Select::make('style')
                    ->label('Button Color Theme')
                    ->options([
                        ButtonStyle::Primary->value => 'Primary Brand Blue',
                        ButtonStyle::Success->value => 'Success Green',
                        ButtonStyle::Dark->value => 'Dark Charcoal',
                        ButtonStyle::Secondary->value => 'Slate Gray',
                        ButtonStyle::Danger->value => 'Crimson Danger',
                        ButtonStyle::Outline->value => 'Clean Outline Border',
                    ])
                    ->default(ButtonStyle::Primary->value),
                Select::make('align')
                    ->label('Button Placement')
                    ->options([
                        'center' => 'Center (Recommended)',
                        'left' => 'Left Aligned',
                        'right' => 'Right Aligned',
                    ])
                    ->default('center'),
                self::getVisibilityFieldset()->columnSpanFull(),
            ])
            ->columns(2);
    }

    /**
     * Two Column Block Definition.
     */
    public static function getTwoColumnBlock(): Block
    {
        return Block::make(SlotType::TwoColumn->value)
            ->label(SlotType::TwoColumn->label())
            ->icon(Heroicon::ViewColumns)
            ->schema([
                TextInput::make('left_title')
                    ->label('Left Column Title')
                    ->placeholder('Feature or Topic A')
                    ->required(),
                TextInput::make('right_title')
                    ->label('Right Column Title')
                    ->placeholder('Feature or Topic B')
                    ->required(),
                Textarea::make('left_body')
                    ->label('Left Column Content')
                    ->rows(3)
                    ->required(),
                Textarea::make('right_body')
                    ->label('Right Column Content')
                    ->rows(3)
                    ->required(),
                TextInput::make('left_button_text')
                    ->label('Left Link Label')
                    ->placeholder('Learn More'),
                TextInput::make('right_button_text')
                    ->label('Right Link Label')
                    ->placeholder('Learn More'),
                TextInput::make('left_button_url')
                    ->label('Left Destination URL')
                    ->placeholder('https://...'),
                TextInput::make('right_button_url')
                    ->label('Right Destination URL')
                    ->placeholder('https://...'),
                Toggle::make('reverse_stack_on_mobile')
                    ->label('Reverse Column Stack on Mobile')
                    ->default(false),
                self::getVisibilityFieldset()->columnSpanFull(),
            ])
            ->columns(2);
    }

    /**
     * Features Block Definition.
     */
    public static function getFeaturesBlock(): Block
    {
        return Block::make(SlotType::Features->value)
            ->label(SlotType::Features->label())
            ->icon(Heroicon::CheckCircle)
            ->schema([
                TextInput::make('heading')
                    ->label('Features Section Heading')
                    ->placeholder('Why choose us?'),
                Repeater::make('items')
                    ->label('Feature Items')
                    ->schema([
                        TextInput::make('icon')
                            ->label('Icon / Emoji')
                            ->default('⚡')
                            ->required(),
                        TextInput::make('title')
                            ->label('Item Title')
                            ->required(),
                        Textarea::make('text')
                            ->label('Description')
                            ->rows(2)
                            ->required(),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->defaultItems(2)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Testimonial Block Definition.
     */
    public static function getTestimonialBlock(): Block
    {
        return Block::make(SlotType::Testimonial->value)
            ->label(SlotType::Testimonial->label())
            ->icon(Heroicon::ChatBubbleLeftRight)
            ->schema([
                Textarea::make('quote')
                    ->label('Customer Quote')
                    ->rows(3)
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('author')
                    ->label('Author Name')
                    ->required(),
                TextInput::make('role')
                    ->label('Job Role / Title')
                    ->placeholder('e.g. VP of Growth'),
                TextInput::make('company')
                    ->label('Company Name')
                    ->placeholder('e.g. Acme Corp'),
                Select::make('rating')
                    ->label('Star Rating')
                    ->options([
                        5 => '★★★★★ (5 Stars)',
                        4 => '★★★★☆ (4 Stars)',
                        3 => '★★★☆☆ (3 Stars)',
                    ])
                    ->default(5),
            ])
            ->columns(2);
    }

    /**
     * Stat Box Block Definition.
     */
    public static function getStatBoxBlock(): Block
    {
        return Block::make(SlotType::StatBox->value)
            ->label(SlotType::StatBox->label())
            ->icon(Heroicon::ChartBar)
            ->schema([
                TextInput::make('heading')
                    ->label('Section Heading (Optional)')
                    ->placeholder('e.g. Platform Highlights'),
                Repeater::make('stats')
                    ->label('Key Metrics')
                    ->schema([
                        TextInput::make('value')
                            ->label('Metric / Number')
                            ->placeholder('e.g. 99.9% or +45%')
                            ->required(),
                        TextInput::make('label')
                            ->label('Label')
                            ->placeholder('e.g. Uptime SLA')
                            ->required(),
                        TextInput::make('change')
                            ->label('Badge / Pill (Optional)')
                            ->placeholder('e.g. +14% YoY'),
                    ])
                    ->columns(3)
                    ->defaultItems(2)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Divider Block Definition.
     */
    public static function getDividerBlock(): Block
    {
        return Block::make(SlotType::Divider->value)
            ->label(SlotType::Divider->label())
            ->icon(Heroicon::Minus)
            ->schema([
                Select::make('height')
                    ->label('Spacer Height')
                    ->options([
                        16 => 'Small (16px)',
                        24 => 'Medium (24px)',
                        36 => 'Large (36px)',
                        48 => 'Extra Large (48px)',
                    ])
                    ->default(24),
                Toggle::make('show_line')
                    ->label('Draw Horizontal Divider Line')
                    ->default(true),
            ])
            ->columns(2);
    }

    /**
     * Social Links Block Definition.
     */
    public static function getSocialLinksBlock(): Block
    {
        return Block::make(SlotType::SocialLinks->value)
            ->label(SlotType::SocialLinks->label())
            ->icon(Heroicon::Share)
            ->schema([
                Repeater::make('links')
                    ->label('Social Profiles')
                    ->schema([
                        Select::make('network')
                            ->label('Network')
                            ->options([
                                'Twitter' => 'Twitter / X',
                                'LinkedIn' => 'LinkedIn',
                                'GitHub' => 'GitHub',
                                'YouTube' => 'YouTube',
                                'Facebook' => 'Facebook',
                                'Website' => 'Website',
                            ])
                            ->default('Twitter')
                            ->required(),
                        TextInput::make('url')
                            ->label('Profile URL')
                            ->url()
                            ->required(),
                    ])
                    ->columns(2)
                    ->defaultItems(3)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Footer Block Definition.
     */
    public static function getFooterBlock(): Block
    {
        return Block::make(SlotType::Footer->value)
            ->label(SlotType::Footer->label())
            ->icon(Heroicon::InformationCircle)
            ->schema([
                TextInput::make('company_name')
                    ->label('Legal Entity / Company Name')
                    ->default(fn (): string => (string) config('mail-builder.footer.company_name', config('app.name')))
                    ->required(),
                TextInput::make('address')
                    ->label('Physical Mailing Address')
                    ->placeholder('123 Example St, Springfield')
                    ->required(),
                TextInput::make('notice')
                    ->label('Permission Notice')
                    ->placeholder('You are receiving this because you signed up on example.com'),
                TextInput::make('unsubscribe_url')
                    ->label('Unsubscribe URL / Merge Tag')
                    ->default('{{unsubscribe_url}}')
                    ->required(),
            ])
            ->columns(2);
    }

    /**
     * Custom Raw HTML Block Definition.
     */
    public static function getHtmlBlock(): Block
    {
        return Block::make(SlotType::Html->value)
            ->label(SlotType::Html->label())
            ->icon(Heroicon::CodeBracket)
            ->schema([
                Textarea::make('html')
                    ->label('Raw HTML Code')
                    ->rows(8)
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Image Banner Block Definition.
     */
    public static function getImageBannerBlock(): Block
    {
        return Block::make(SlotType::ImageBanner->value)
            ->label(SlotType::ImageBanner->label())
            ->icon(Heroicon::Photo)
            ->schema([
                TextInput::make('image_url')
                    ->label('Image URL')
                    ->placeholder('https://images.unsplash.com/...')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('alt_text')
                    ->label('Accessibility Alt Text')
                    ->placeholder('Describe image for screen readers and deliverability')
                    ->required(),
                TextInput::make('link_url')
                    ->label('Destination Link URL (Optional)')
                    ->placeholder('https://...'),
                TextInput::make('caption')
                    ->label('Caption Text (Optional)')
                    ->placeholder('Small caption below image'),
                Select::make('border_radius')
                    ->label('Corner Style')
                    ->options([
                        '0px' => 'Sharp (0px)',
                        '4px' => 'Subtle (4px)',
                        '8px' => 'Rounded (8px)',
                        '16px' => 'Pill / Soft (16px)',
                    ])
                    ->default('8px'),
                Toggle::make('full_width')
                    ->label('Edge-to-Edge Full Width (No Inset Margin)')
                    ->default(false),
                self::getVisibilityFieldset()->columnSpanFull(),
            ])
            ->columns(2);
    }

    /**
     * Video Card Block Definition.
     */
    public static function getVideoCardBlock(): Block
    {
        return Block::make(SlotType::VideoCard->value)
            ->label(SlotType::VideoCard->label())
            ->icon(Heroicon::PlayCircle)
            ->schema([
                TextInput::make('video_url')
                    ->label('Video Destination URL (YouTube, Vimeo, Loom)')
                    ->placeholder('https://www.youtube.com/watch?v=...')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('thumbnail_url')
                    ->label('Custom Thumbnail Image URL')
                    ->placeholder('https://images.unsplash.com/...'),
                TextInput::make('badge')
                    ->label('Badge Text')
                    ->default('VIDEO DEMO'),
                TextInput::make('title')
                    ->label('Video Title')
                    ->default('Watch Product Tour & Feature Walkthrough')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('subtitle')
                    ->label('Video Description / Duration')
                    ->rows(2)
                    ->placeholder('e.g. 3 min overview of platform features')
                    ->columnSpanFull(),
                self::getVisibilityFieldset()->columnSpanFull(),
            ])
            ->columns(2);
    }

    /**
     * Pricing Grid Block Definition.
     */
    public static function getPricingGridBlock(): Block
    {
        return Block::make(SlotType::PricingGrid->value)
            ->label(SlotType::PricingGrid->label())
            ->icon(Heroicon::CurrencyDollar)
            ->schema([
                TextInput::make('heading')
                    ->label('Section Heading')
                    ->placeholder('Simple, transparent pricing'),
                TextInput::make('subtitle')
                    ->label('Subtitle')
                    ->placeholder('Start free, upgrade as you grow'),
                Repeater::make('tiers')
                    ->label('Pricing Tiers')
                    ->schema([
                        TextInput::make('name')
                            ->label('Plan Name')
                            ->placeholder('Starter')
                            ->required(),
                        TextInput::make('price')
                            ->label('Price')
                            ->placeholder('$49')
                            ->required(),
                        TextInput::make('frequency')
                            ->label('Billing Frequency')
                            ->placeholder('/ month')
                            ->default('/ month'),
                        TextInput::make('button_text')
                            ->label('CTA Button Text')
                            ->default('Get Started')
                            ->required(),
                        TextInput::make('button_url')
                            ->label('CTA Destination URL')
                            ->default('https://example.com/signup')
                            ->required(),
                        Toggle::make('is_popular')
                            ->label('Highlight as Most Popular')
                            ->default(false),
                    ])
                    ->columns(3)
                    ->defaultItems(2)
                    ->columnSpanFull(),
                self::getVisibilityFieldset()->columnSpanFull(),
            ]);
    }

    /**
     * 1-Click NPS / Rating Scale Block Definition.
     */
    public static function getRatingBarBlock(): Block
    {
        return Block::make(SlotType::RatingBar->value)
            ->label(SlotType::RatingBar->label())
            ->icon(Heroicon::Star)
            ->schema([
                TextInput::make('question')
                    ->label('Survey Question Prompt')
                    ->default('How likely are you to recommend us to a colleague?')
                    ->required()
                    ->columnSpanFull(),
                Select::make('scale_type')
                    ->label('Rating Scale')
                    ->options([
                        '10' => '0 to 10 Scale (Industry Standard NPS)',
                        '5' => '1 to 5 Stars / CSAT Scale',
                    ])
                    ->default('10')
                    ->required(),
                TextInput::make('base_url')
                    ->label('Destination Rating Endpoint / Webhook')
                    ->default('/feedback/nps/{{nps_token}}')
                    ->helperText('Each button appends /{score} to this URL.')
                    ->required(),
                TextInput::make('low_label')
                    ->label('Lowest Score Meaning')
                    ->default('Not at all likely')
                    ->required(),
                TextInput::make('high_label')
                    ->label('Highest Score Meaning')
                    ->default('Extremely likely')
                    ->required(),
                self::getVisibilityFieldset()->columnSpanFull(),
            ])
            ->columns(2);
    }

    /**
     * Countdown Timer Urgency Block Definition.
     */
    public static function getCountdownTimerBlock(): Block
    {
        return Block::make(SlotType::CountdownTimer->value)
            ->label(SlotType::CountdownTimer->label())
            ->icon(Heroicon::Clock)
            ->schema([
                TextInput::make('badge')
                    ->label('Urgency Badge')
                    ->default('⚡ LIMITED TIME OFFER'),
                TextInput::make('deadline_text')
                    ->label('Deadline Description')
                    ->default('Offer expires Sunday at Midnight')
                    ->required(),
                TextInput::make('heading')
                    ->label('Headline')
                    ->default('Unlock 20% Off Your Annual Plan')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('subtext')
                    ->label('Promotional Subtext')
                    ->rows(2)
                    ->default('Lock in early-bird pricing before this promotional tier expires.')
                    ->columnSpanFull(),
                TextInput::make('button_text')
                    ->label('Action Button Label')
                    ->default('Claim Your Discount Now')
                    ->required(),
                TextInput::make('button_url')
                    ->label('Action Destination URL')
                    ->default('https://example.com/checkout?promo=FLASH20')
                    ->required(),
                self::getVisibilityFieldset()->columnSpanFull(),
            ])
            ->columns(2);
    }

    /**
     * Accordion / FAQ Block Definition.
     */
    public static function getAccordionBlock(): Block
    {
        return Block::make(SlotType::Accordion->value)
            ->label(SlotType::Accordion->label())
            ->icon(Heroicon::QuestionMarkCircle)
            ->schema([
                TextInput::make('heading')
                    ->label('Section Heading')
                    ->default('Frequently Asked Questions')
                    ->columnSpanFull(),
                TextInput::make('subtitle')
                    ->label('Section Subtitle')
                    ->placeholder('Quick answers to common questions')
                    ->columnSpanFull(),
                Repeater::make('items')
                    ->label('Questions & Answers')
                    ->schema([
                        TextInput::make('question')
                            ->label('Question')
                            ->placeholder('e.g. Can I upgrade my plan later?')
                            ->required(),
                        Textarea::make('answer')
                            ->label('Answer')
                            ->rows(2)
                            ->placeholder('e.g. Yes, you can change your subscription anytime in account billing.')
                            ->required(),
                    ])
                    ->columns(1)
                    ->defaultItems(2)
                    ->columnSpanFull(),
                self::getVisibilityFieldset()->columnSpanFull(),
            ]);
    }

    /**
     * Dynamic Content / Product Recommendations Feed.
     */
    public static function getDynamicFeedBlock(): Block
    {
        return Block::make(SlotType::DynamicFeed->value)
            ->label(SlotType::DynamicFeed->label())
            ->icon(Heroicon::Sparkles)
            ->schema([
                TextInput::make('heading')
                    ->label('Section Heading')
                    ->default('Recommended For You')
                    ->columnSpanFull(),
                TextInput::make('subtitle')
                    ->label('Subtitle')
                    ->placeholder('Curated recommendations based on your preferences')
                    ->columnSpanFull(),
                Repeater::make('items')
                    ->label('Dynamic Products / Content Items')
                    ->schema([
                        TextInput::make('title')
                            ->label('Item Title')
                            ->required(),
                        TextInput::make('price')
                            ->label('Price / Badge')
                            ->placeholder('e.g. $199 / mo'),
                        TextInput::make('image_url')
                            ->label('Thumbnail Image URL')
                            ->url(),
                        TextInput::make('button_url')
                            ->label('Target URL')
                            ->url()
                            ->required(),
                        TextInput::make('button_text')
                            ->label('Button CTA')
                            ->default('View Details'),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->defaultItems(2)
                    ->columnSpanFull(),
                self::getVisibilityFieldset()->columnSpanFull(),
            ]);
    }

    /**
     * Transactional Order Receipt / Invoice Summary Block.
     */
    public static function getOrderReceiptBlock(): Block
    {
        return Block::make(SlotType::OrderReceipt->value)
            ->label(SlotType::OrderReceipt->label())
            ->icon(Heroicon::ShoppingBag)
            ->schema([
                TextInput::make('order_number')
                    ->label('Order / Reference #')
                    ->default('1001')
                    ->required(),
                TextInput::make('order_date')
                    ->label('Order Date')
                    ->placeholder('e.g. October 24, 2026'),
                Select::make('status')
                    ->label('Order Status')
                    ->options([
                        'Confirmed' => 'Confirmed',
                        'Processing' => 'Processing',
                        'Shipped' => 'Shipped',
                        'Delivered' => 'Delivered',
                        'Refunded' => 'Refunded',
                    ])
                    ->default('Confirmed'),
                TextInput::make('currency')
                    ->label('Currency Symbol')
                    ->default('$')
                    ->maxLength(5),
                Repeater::make('items')
                    ->label('Purchased Items')
                    ->schema([
                        TextInput::make('name')
                            ->label('Product / Service Name')
                            ->required(),
                        TextInput::make('quantity')
                            ->label('Qty')
                            ->numeric()
                            ->default(1)
                            ->required(),
                        TextInput::make('price')
                            ->label('Unit Price')
                            ->default('0.00')
                            ->required(),
                        TextInput::make('variant')
                            ->label('Variant / SKU / Options')
                            ->placeholder('e.g. Size L / Dark Gray'),
                    ])
                    ->columns(4)
                    ->defaultItems(1)
                    ->columnSpanFull(),
                TextInput::make('subtotal')
                    ->label('Subtotal Amount')
                    ->default('0.00'),
                TextInput::make('shipping')
                    ->label('Shipping Fee')
                    ->default('0.00'),
                TextInput::make('tax')
                    ->label('Tax / VAT')
                    ->default('0.00'),
                TextInput::make('total')
                    ->label('Grand Total')
                    ->default('0.00')
                    ->required(),
                self::getVisibilityFieldset()->columnSpanFull(),
            ]);
    }

    /**
     * RSS Syndication Feed Block.
     */
    public static function getRssFeedBlock(): Block
    {
        return Block::make(SlotType::RssFeed->value)
            ->label(SlotType::RssFeed->label())
            ->icon(Heroicon::Rss)
            ->schema([
                TextInput::make('heading')
                    ->label('Feed Title')
                    ->default('Latest Articles & Updates')
                    ->columnSpanFull(),
                TextInput::make('subtitle')
                    ->label('Subtitle')
                    ->placeholder('Latest news from our RSS syndication feed')
                    ->columnSpanFull(),
                TextInput::make('feed_url')
                    ->label('Source RSS / Atom URL (Optional)')
                    ->url()
                    ->columnSpanFull(),
                Repeater::make('items')
                    ->label('Feed Articles')
                    ->schema([
                        TextInput::make('title')
                            ->label('Article Title')
                            ->required(),
                        TextInput::make('published_at')
                            ->label('Published Date')
                            ->placeholder('e.g. Oct 15, 2026'),
                        TextInput::make('author')
                            ->label('Author / Source')
                            ->placeholder('e.g. Editorial Desk'),
                        TextInput::make('url')
                            ->label('Article Link')
                            ->url()
                            ->required(),
                        Textarea::make('summary')
                            ->label('Article Summary')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->defaultItems(2)
                    ->columnSpanFull(),
                self::getVisibilityFieldset()->columnSpanFull(),
            ]);
    }

    /**
     * Three-Column Product & Article Showcase Grid.
     */
    public static function getThreeColumnBlock(): Block
    {
        return Block::make(SlotType::ThreeColumn->value)
            ->label(SlotType::ThreeColumn->label())
            ->icon(Heroicon::ViewColumns)
            ->schema([
                TextInput::make('heading')
                    ->label('Section Heading')
                    ->placeholder('e.g. Featured Highlights')
                    ->columnSpanFull(),
                TextInput::make('subtitle')
                    ->label('Subtitle')
                    ->placeholder('e.g. Explore our newest feature releases')
                    ->columnSpanFull(),
                Repeater::make('columns')
                    ->label('Three Columns')
                    ->schema([
                        TextInput::make('title')
                            ->label('Column Title')
                            ->required(),
                        TextInput::make('image_url')
                            ->label('Image URL')
                            ->url(),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('button_text')
                            ->label('CTA Button Text')
                            ->default('Learn More'),
                        TextInput::make('button_url')
                            ->label('Target Link')
                            ->url()
                            ->default('#'),
                    ])
                    ->columns(2)
                    ->defaultItems(3)
                    ->maxItems(3)
                    ->columnSpanFull(),
                self::getVisibilityFieldset()->columnSpanFull(),
            ]);
    }

    /**
     * Four-Column Logo / Partner Trust Wall.
     */
    public static function getFourColumnBlock(): Block
    {
        return Block::make(SlotType::FourColumn->value)
            ->label(SlotType::FourColumn->label())
            ->icon(Heroicon::Squares2x2)
            ->schema([
                TextInput::make('heading')
                    ->label('Header Label')
                    ->default('Trusted by Industry Leaders')
                    ->columnSpanFull(),
                Repeater::make('items')
                    ->label('Brand / Partner Logos')
                    ->schema([
                        TextInput::make('label')
                            ->label('Brand / Partner Name')
                            ->required(),
                        TextInput::make('image_url')
                            ->label('Logo Image URL (PNG/SVG/WebP)')
                            ->url(),
                        TextInput::make('url')
                            ->label('Website Link (Optional)')
                            ->url(),
                    ])
                    ->columns(3)
                    ->defaultItems(4)
                    ->columnSpanFull(),
                self::getVisibilityFieldset()->columnSpanFull(),
            ]);
    }

    /**
     * Asymmetric Split Layout (1/3 + 2/3 or 2/3 + 1/3).
     */
    public static function getAsymmetricColumnsBlock(): Block
    {
        return Block::make(SlotType::AsymmetricColumns->value)
            ->label(SlotType::AsymmetricColumns->label())
            ->icon(Heroicon::RectangleGroup)
            ->schema([
                Select::make('ratio')
                    ->label('Layout Distribution')
                    ->options([
                        '30_70' => '1/3 Left Sidebar + 2/3 Main Story',
                        '70_30' => '2/3 Main Story + 1/3 Right Sidebar',
                    ])
                    ->default('30_70')
                    ->required()
                    ->columnSpanFull(),
                Fieldset::make('Left Column Content')
                    ->schema([
                        TextInput::make('left_title')->label('Left Title'),
                        TextInput::make('left_image')->label('Left Image URL')->url(),
                        Textarea::make('left_body')->label('Left Copy')->rows(3)->columnSpanFull(),
                        ColorPicker::make('left_bg_color')->label('Background Color')->default('#F8FAFC'),
                    ])
                    ->columns(2),
                Fieldset::make('Right Column Content')
                    ->schema([
                        TextInput::make('right_title')->label('Right Title'),
                        TextInput::make('right_button_text')->label('CTA Button Text')->default('Read Story'),
                        TextInput::make('right_button_url')->label('CTA Button URL')->url()->default('#'),
                        ColorPicker::make('right_bg_color')->label('Background Color')->default('#FFFFFF'),
                        Textarea::make('right_body')->label('Right Copy')->rows(3)->columnSpanFull(),
                    ])
                    ->columns(2),
                self::getVisibilityFieldset()->columnSpanFull(),
            ]);
    }

    /**
     * Product Catalog & Abandoned Cart Grid.
     */
    public static function getProductCatalogBlock(): Block
    {
        return Block::make(SlotType::ProductCatalog->value)
            ->label(SlotType::ProductCatalog->label())
            ->icon(Heroicon::ShoppingBag)
            ->schema([
                TextInput::make('section_title')
                    ->label('Section Header')
                    ->placeholder('Recommended For You / Items in Your Cart')
                    ->columnSpanFull(),
                TextInput::make('section_subtitle')
                    ->label('Section Subtitle')
                    ->placeholder('Hand-picked recommendations based on your preferences')
                    ->columnSpanFull(),
                Select::make('columns')
                    ->label('Columns Count')
                    ->options([
                        1 => '1 Column (Large Featured Cards)',
                        2 => '2 Columns (Standard Grid)',
                        3 => '3 Columns (Compact Catalog)',
                    ])
                    ->default(2)
                    ->required(),
                Toggle::make('show_badges')
                    ->label('Show Discount & Stock Badges')
                    ->default(true),
                ColorPicker::make('card_background')
                    ->label('Card Background Color')
                    ->default('#ffffff'),
                ColorPicker::make('card_border')
                    ->label('Card Border Color')
                    ->default('#e2e8f0'),
                Repeater::make('products')
                    ->label('Products / Cart Line Items')
                    ->schema([
                        TextInput::make('title')
                            ->label('Product Title')
                            ->required(),
                        TextInput::make('price')
                            ->label('Current Price')
                            ->placeholder('$49.00')
                            ->required(),
                        TextInput::make('compare_at_price')
                            ->label('Compare-at / Original Price (Strikethrough)')
                            ->placeholder('$79.00'),
                        TextInput::make('savings_badge')
                            ->label('Discount Badge (e.g. Save 30%)')
                            ->placeholder('Save 30%'),
                        TextInput::make('stock_badge')
                            ->label('Stock Badge (e.g. In Stock / Low Stock)')
                            ->placeholder('In Stock'),
                        TextInput::make('image_url')
                            ->label('Product Image URL')
                            ->url()
                            ->required(),
                        TextInput::make('button_text')
                            ->label('Button CTA Label')
                            ->default('Buy Now')
                            ->required(),
                        TextInput::make('button_url')
                            ->label('Checkout / Product URL')
                            ->url()
                            ->required(),
                        Textarea::make('description')
                            ->label('Short Description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->defaultItems(2)
                    ->columnSpanFull(),
                self::getVisibilityFieldset()->columnSpanFull(),
            ]);
    }

    /**
     * Promo Code & Discount Voucher Card.
     */
    public static function getCouponCodeBlock(): Block
    {
        return Block::make(SlotType::CouponCode->value)
            ->label(SlotType::CouponCode->label())
            ->icon(Heroicon::Ticket)
            ->schema([
                TextInput::make('headline')
                    ->label('Voucher Headline')
                    ->default('Special Discount Voucher')
                    ->required(),
                TextInput::make('discount_badge')
                    ->label('Discount Badge (e.g. 20% OFF)')
                    ->default('20% OFF'),
                TextInput::make('code')
                    ->label('Promo / Coupon Code')
                    ->placeholder('SAVE20')
                    ->required(),
                TextInput::make('expires_at')
                    ->label('Expiration Notice')
                    ->placeholder('e.g. October 31, 2026 or In 3 Days'),
                TextInput::make('button_text')
                    ->label('Redeem CTA Button Text')
                    ->default('Claim Offer'),
                TextInput::make('button_url')
                    ->label('Redeem CTA URL')
                    ->url()
                    ->default('#'),
                Textarea::make('description')
                    ->label('Terms or Description')
                    ->default('Apply this promo code at checkout to claim your savings.')
                    ->rows(2)
                    ->columnSpanFull(),
                ColorPicker::make('card_background')
                    ->label('Voucher Background Color')
                    ->default('#f8fafc'),
                ColorPicker::make('border_color')
                    ->label('Dashed Border Color')
                    ->default('#94a3b8'),
                ColorPicker::make('code_background')
                    ->label('Code Box Background Color')
                    ->default('#ffffff'),
                self::getVisibilityFieldset()->columnSpanFull(),
            ])
            ->columns(2);
    }

    /**
     * Action to apply a pre-designed email preset.
     */
    public static function applyPresetAction(string $targetSlotField = 'slots'): Action
    {
        return Action::make('applyPreset')
            ->label('Apply Email Preset')
            ->icon(Heroicon::RectangleStack)
            ->color('gray')
            ->modalHeading('Select Email Layout Preset')
            ->modalDescription('Populate your visual editor with an expertly crafted, responsive email template.')
            ->schema([
                Select::make('preset_key')
                    ->label('Select Preset')
                    ->options(fn (): array => app(PresetRegistry::class)->toSelectOptions())
                    ->default('product_announcement')
                    ->required(),
            ])
            ->action(function (array $data, $livewire) use ($targetSlotField): void {
                $registry = app(PresetRegistry::class);
                $preset = $registry->get((string) $data['preset_key']);
                $document = $preset->document();

                $slotsData = array_map(function ($slot): array {
                    return [
                        'type' => $slot->type->value,
                        'data' => $slot->data,
                    ];
                }, $document->slots);

                if (is_object($livewire) && property_exists($livewire, 'form') && is_object($livewire->form) && method_exists($livewire->form, 'fill')) {
                    $livewire->form->fill([
                        $targetSlotField => $slotsData,
                        'subject' => $document->subject ?? '',
                        'preview_text' => $document->previewText ?? '',
                    ]);
                }

                $count = count($document->slots);
                Notification::make()
                    ->title('Preset Applied')
                    ->body("Applied [{$preset->label()}] with {$count} blocks.")
                    ->success()
                    ->send();
            });
    }

    /**
     * App Store & Google Play Badges Block Definition.
     */
    public static function getAppBadgesBlock(): Block
    {
        return Block::make(SlotType::AppBadges->value)
            ->label(SlotType::AppBadges->label())
            ->icon(Heroicon::DevicePhoneMobile)
            ->schema([
                TextInput::make('heading')
                    ->label('Section Heading')
                    ->placeholder('Download Our Mobile App')
                    ->columnSpanFull(),
                TextInput::make('subtitle')
                    ->label('Subtitle')
                    ->placeholder('Manage your account on the go')
                    ->columnSpanFull(),
                TextInput::make('app_store_url')
                    ->label('Apple App Store Link URL')
                    ->placeholder('https://apps.apple.com/app/...')
                    ->default('#'),
                TextInput::make('google_play_url')
                    ->label('Google Play Store Link URL')
                    ->placeholder('https://play.google.com/store/apps/...')
                    ->default('#'),
                Select::make('align')
                    ->label('Alignment')
                    ->options([
                        'center' => 'Center Aligned',
                        'left' => 'Left Aligned',
                    ])
                    ->default('center'),
                self::getVisibilityFieldset()->columnSpanFull(),
            ])
            ->columns(2);
    }

    /**
     * Data & Comparison Table Block Definition.
     */
    public static function getDataTableBlock(): Block
    {
        return Block::make(SlotType::DataTable->value)
            ->label(SlotType::DataTable->label())
            ->icon(Heroicon::TableCells)
            ->schema([
                TextInput::make('heading')
                    ->label('Table Heading (Optional)')
                    ->placeholder('Feature & Tier Comparison')
                    ->columnSpanFull(),
                TextInput::make('subtitle')
                    ->label('Table Subtitle (Optional)')
                    ->placeholder('Compare available features across plans')
                    ->columnSpanFull(),
                TagsInput::make('headers')
                    ->label('Column Header Names')
                    ->placeholder('Add column name (e.g. Feature, Starter, Pro)')
                    ->default(['Feature', 'Starter', 'Professional', 'Enterprise'])
                    ->columnSpanFull(),
                Repeater::make('rows')
                    ->label('Table Rows')
                    ->schema([
                        TagsInput::make('cells')
                            ->label('Row Cell Values (Matching Column Count)')
                            ->placeholder('Type cell value and press Enter')
                            ->required(),
                    ])
                    ->collapsible()
                    ->defaultItems(3)
                    ->columnSpanFull(),
                self::getVisibilityFieldset()->columnSpanFull(),
            ]);
    }

    /**
     * Labeled Section Divider Block Definition.
     */
    public static function getLabeledDividerBlock(): Block
    {
        return Block::make(SlotType::LabeledDivider->value)
            ->label(SlotType::LabeledDivider->label())
            ->icon(Heroicon::Minus)
            ->schema([
                TextInput::make('label')
                    ->label('Divider Center Label / Badge')
                    ->default('OR')
                    ->required(),
                ColorPicker::make('badge_bg')
                    ->label('Badge Background Color')
                    ->default('#f1f5f9'),
                ColorPicker::make('text_color')
                    ->label('Badge Text Color')
                    ->default('#64748b'),
                ColorPicker::make('line_color')
                    ->label('Divider Line Color')
                    ->default('#e2e8f0'),
                Select::make('padding_y')
                    ->label('Vertical Spacing')
                    ->options([
                        16 => 'Compact (16px)',
                        24 => 'Standard (24px)',
                        36 => 'Generous (36px)',
                    ])
                    ->default(24),
                self::getVisibilityFieldset()->columnSpanFull(),
            ])
            ->columns(2);
    }

    /**
     * Compile slots into bulletproof responsive HTML string.
     *
     * @param  list<array<string, mixed>>  $slots
     * @param  array<string, mixed>  $options
     */
    public static function compile(array $slots, array $options = []): string
    {
        return app(EmailSlotCompiler::class)->compileSlots($slots, $options);
    }

    /**
     * Extract plain text from slots.
     *
     * @param  list<array<string, mixed>>  $slots
     */
    public static function extractPlainText(array $slots): string
    {
        return app(PlainTextExtractor::class)->extractFromSlotArray($slots);
    }

    /**
     * Audience personalization and conditional visibility schema for visual slots.
     */
    public static function getVisibilityFieldset(): Fieldset
    {
        return Fieldset::make('Audience Personalization & Targeting')
            ->schema([
                Select::make('visibility.field')
                    ->label('Targeting Field')
                    ->placeholder('Show to all recipients (Default)')
                    ->options([
                        'contact.lifecycle_stage' => 'Contact Lifecycle Stage',
                        'contact.job_title' => 'Contact Job Title',
                        'company.industry' => 'Company Industry',
                    ]),
                Select::make('visibility.operator')
                    ->label('Condition')
                    ->options([
                        'equals' => 'Equals',
                        'not_equals' => 'Does Not Equal',
                        'contains' => 'Contains Text',
                        'is_not_empty' => 'Is Set / Not Empty',
                    ])
                    ->default('equals'),
                TextInput::make('visibility.value')
                    ->label('Target Value')
                    ->placeholder('e.g. customer or SaaS'),
            ])
            ->columns(3);
    }
}
