<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Filament\Components;

use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Support\Icons\Heroicon;
use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Compilers\PlainTextExtractor;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\Presets\PresetRegistry;

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
        return SchemaBlockFactory::make(SlotType::Header);
    }

    /**
     * Hero Block Definition.
     */
    public static function getHeroBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::Hero);
    }

    /**
     * Body Text Block Definition.
     */
    public static function getBodyTextBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::BodyText);
    }

    /**
     * Button Block Definition.
     */
    public static function getButtonBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::Button);
    }

    /**
     * Two Column Block Definition.
     */
    public static function getTwoColumnBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::TwoColumn);
    }

    /**
     * Features Block Definition.
     */
    public static function getFeaturesBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::Features);
    }

    /**
     * Testimonial Block Definition.
     */
    public static function getTestimonialBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::Testimonial);
    }

    /**
     * Stat Box Block Definition.
     */
    public static function getStatBoxBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::StatBox);
    }

    /**
     * Divider Block Definition.
     */
    public static function getDividerBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::Divider);
    }

    /**
     * Social Links Block Definition.
     */
    public static function getSocialLinksBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::SocialLinks);
    }

    /**
     * Footer Block Definition.
     */
    public static function getFooterBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::Footer);
    }

    /**
     * Custom Raw HTML Block Definition.
     */
    public static function getHtmlBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::Html);
    }

    /**
     * Image Banner Block Definition.
     */
    public static function getImageBannerBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::ImageBanner);
    }

    /**
     * Video Card Block Definition.
     */
    public static function getVideoCardBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::VideoCard);
    }

    /**
     * Pricing Grid Block Definition.
     */
    public static function getPricingGridBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::PricingGrid);
    }

    /**
     * 1-Click NPS / Rating Scale Block Definition.
     */
    public static function getRatingBarBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::RatingBar);
    }

    /**
     * Countdown Timer Urgency Block Definition.
     */
    public static function getCountdownTimerBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::CountdownTimer);
    }

    /**
     * Accordion / FAQ Block Definition.
     */
    public static function getAccordionBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::Accordion);
    }

    /**
     * Dynamic Content / Product Recommendations Feed.
     */
    public static function getDynamicFeedBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::DynamicFeed);
    }

    /**
     * Transactional Order Receipt / Invoice Summary Block.
     */
    public static function getOrderReceiptBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::OrderReceipt);
    }

    /**
     * RSS Syndication Feed Block.
     */
    public static function getRssFeedBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::RssFeed);
    }

    /**
     * Three-Column Product & Article Showcase Grid.
     */
    public static function getThreeColumnBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::ThreeColumn);
    }

    /**
     * Four-Column Logo / Partner Trust Wall.
     */
    public static function getFourColumnBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::FourColumn);
    }

    /**
     * Asymmetric Split Layout (1/3 + 2/3 or 2/3 + 1/3).
     */
    public static function getAsymmetricColumnsBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::AsymmetricColumns);
    }

    /**
     * Product Catalog & Abandoned Cart Grid.
     */
    public static function getProductCatalogBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::ProductCatalog);
    }

    /**
     * Promo Code & Discount Voucher Card.
     */
    public static function getCouponCodeBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::CouponCode);
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
        return SchemaBlockFactory::make(SlotType::AppBadges);
    }

    /**
     * Data & Comparison Table Block Definition.
     */
    public static function getDataTableBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::DataTable);
    }

    /**
     * Labeled Section Divider Block Definition.
     */
    public static function getLabeledDividerBlock(): Block
    {
        return SchemaBlockFactory::make(SlotType::LabeledDivider);
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
