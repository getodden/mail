<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Enums;

enum SlotType: string
{
    case Header = 'header';
    case Hero = 'hero';
    case BodyText = 'body_text';
    case Button = 'button';
    case TwoColumn = 'two_column';
    case Features = 'features';
    case Testimonial = 'testimonial';
    case StatBox = 'stat_box';
    case Divider = 'divider';
    case SocialLinks = 'social_links';
    case Footer = 'footer';
    case Html = 'html';
    case ImageBanner = 'image_banner';
    case VideoCard = 'video_card';
    case PricingGrid = 'pricing_grid';
    case RatingBar = 'rating_bar';
    case CountdownTimer = 'countdown_timer';
    case Accordion = 'accordion';
    case DynamicFeed = 'dynamic_feed';
    case OrderReceipt = 'order_receipt';
    case RssFeed = 'rss_feed';
    case ThreeColumn = 'three_column';
    case FourColumn = 'four_column';
    case AsymmetricColumns = 'asymmetric_columns';
    case ProductCatalog = 'product_catalog';
    case CouponCode = 'coupon_code';
    case AppBadges = 'app_badges';
    case DataTable = 'data_table';
    case LabeledDivider = 'labeled_divider';

    public function label(): string
    {
        return match ($this) {
            self::Header => 'Header & Logo',
            self::Hero => 'Hero Headline & Banner',
            self::BodyText => 'Text & Content',
            self::Button => 'Call to Action Button',
            self::TwoColumn => '2-Column Card Grid',
            self::Features => 'Feature Highlights',
            self::Testimonial => 'Customer Quote & Testimonial',
            self::StatBox => 'Key Metrics Callout',
            self::Divider => 'Spacer / Divider Line',
            self::SocialLinks => 'Social Media Links',
            self::Footer => 'Footer & Compliance',
            self::Html => 'Custom Raw HTML',
            self::ImageBanner => 'Responsive Image Banner',
            self::VideoCard => 'Video Preview Thumbnail',
            self::PricingGrid => 'Pricing & Tier Comparison',
            self::RatingBar => '1-Click NPS / Rating Scale',
            self::CountdownTimer => 'Urgency & Countdown Banner',
            self::Accordion => 'Accordion / FAQ Cards',
            self::DynamicFeed => 'Dynamic Product / Content Feed',
            self::OrderReceipt => 'Itemized Receipt & Invoice',
            self::RssFeed => 'Live Blog & RSS Article Feed',
            self::ThreeColumn => '3-Column Product & Article Grid',
            self::FourColumn => '4-Column Logo / Partner Wall',
            self::AsymmetricColumns => 'Asymmetric Sidebar & Content Split',
            self::ProductCatalog => 'Product Catalog & Cart Grid',
            self::CouponCode => 'Promo Code & Voucher Card',
            self::AppBadges => 'App Store & Google Play Badges',
            self::DataTable => 'Data & Comparison Table',
            self::LabeledDivider => 'Labeled Section Divider',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Header => 'Top branding bar with logo, web-view link, and optional date.',
            self::Hero => 'Bold high-converting banner with headline, subcopy, and action button.',
            self::BodyText => 'Editorial text block with rich formatting and merge tag support.',
            self::Button => 'Bulletproof VML Outlook-safe CTA button with custom styling.',
            self::TwoColumn => 'Side-by-side layout that automatically collapses to 100% on mobile.',
            self::Features => 'Multi-item feature list with icons, bold titles, and descriptions.',
            self::Testimonial => 'Proof element with stylized quote box, avatar, author, and company.',
            self::StatBox => 'Prominent performance numbers and percentage change callouts.',
            self::Divider => 'Horizontal visual rule or whitespace separator.',
            self::SocialLinks => 'Clean icon bar with links to your social channels.',
            self::Footer => 'CAN-SPAM / GDPR compliant footer with unsubscribe link and company address.',
            self::Html => 'Direct raw HTML snippet for bespoke code components.',
            self::ImageBanner => 'Edge-to-edge or padded responsive image with link destination and caption.',
            self::VideoCard => 'Video thumbnail preview with overlay play button linking to YouTube/Loom/Vimeo.',
            self::PricingGrid => 'Multi-tier pricing or product card comparison with feature bullets and CTAs.',
            self::RatingBar => 'Embedded 1-click survey buttons (1-10 or 1-5 scale) directly within the email.',
            self::CountdownTimer => 'Event or promotional countdown banner with urgency badge and target date.',
            self::Accordion => 'Clean stacked question & answer cards for FAQs, policy terms, and details.',
            self::DynamicFeed => 'Iterative repeatable grid looping over dynamic products or cart recommendations.',
            self::OrderReceipt => 'Transactional multi-line order summary table with subtotals, tax, and order number.',
            self::RssFeed => 'Automated RSS feed articles digest with headlines, dates, and read links.',
            self::ThreeColumn => 'Three responsive columns with images, titles, and buttons that stack on mobile.',
            self::FourColumn => 'Four compact icon or brand logo columns for customer proof and social trust.',
            self::AsymmetricColumns => 'Two unequal columns (1/3 + 2/3 or 2/3 + 1/3) for sidebar bios and event schedules.',
            self::ProductCatalog => 'Display responsive product cards with pricing, compare price discount badge, and buy buttons.',
            self::CouponCode => 'High-converting discount voucher with styled promo code box, dashed coupon border, and expiry.',
            self::AppBadges => 'Mobile application download badges for Apple App Store and Google Play Store.',
            self::DataTable => 'Structured multi-column data comparison table with headers and zebra striping.',
            self::LabeledDivider => 'Visual separator rule with centered text label or pill.',
        };
    }

    public function viewName(): string
    {
        return match ($this) {
            self::Header => 'mail-builder::slots.header',
            self::Hero => 'mail-builder::slots.hero',
            self::BodyText => 'mail-builder::slots.body-text',
            self::Button => 'mail-builder::slots.button',
            self::TwoColumn => 'mail-builder::slots.two-column',
            self::Features => 'mail-builder::slots.features',
            self::Testimonial => 'mail-builder::slots.testimonial',
            self::StatBox => 'mail-builder::slots.stat-box',
            self::Divider => 'mail-builder::slots.divider',
            self::SocialLinks => 'mail-builder::slots.social-links',
            self::Footer => 'mail-builder::slots.footer',
            self::Html => 'mail-builder::slots.html',
            self::ImageBanner => 'mail-builder::slots.image-banner',
            self::VideoCard => 'mail-builder::slots.video-card',
            self::PricingGrid => 'mail-builder::slots.pricing-grid',
            self::RatingBar => 'mail-builder::slots.rating-bar',
            self::CountdownTimer => 'mail-builder::slots.countdown-timer',
            self::Accordion => 'mail-builder::slots.accordion',
            self::DynamicFeed => 'mail-builder::slots.dynamic-feed',
            self::OrderReceipt => 'mail-builder::slots.order-receipt',
            self::RssFeed => 'mail-builder::slots.rss-feed',
            self::ThreeColumn => 'mail-builder::slots.three-column',
            self::FourColumn => 'mail-builder::slots.four-column',
            self::AsymmetricColumns => 'mail-builder::slots.asymmetric-columns',
            self::ProductCatalog => 'mail-builder::slots.product-catalog',
            self::CouponCode => 'mail-builder::slots.coupon-code',
            self::AppBadges => 'mail-builder::slots.app-badges',
            self::DataTable => 'mail-builder::slots.data-table',
            self::LabeledDivider => 'mail-builder::slots.labeled-divider',
        };
    }
}
