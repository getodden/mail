<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Presets;

use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Enums\SlotType;

class ProductAnnouncementPreset implements PresetContract
{
    public function key(): string
    {
        return 'product_announcement';
    }

    public function label(): string
    {
        return '🚀 Product Launch & Feature Announcement';
    }

    public function description(): string
    {
        return 'Modern, high-impact release announcement featuring hero header, 3-point feature grid, customer testimonial, and bold CTA.';
    }

    public function category(): string
    {
        return 'marketing';
    }

    public function document(): EmailDocument
    {
        $doc = new EmailDocument(
            subject: 'Introducing {{app_name}} 2.0: The Next-Gen Revenue Platform 🚀',
            previewText: 'Explore what is new in our biggest release yet.',
            theme: [
                'primary_color' => '#2563eb',
            ],
        );

        $doc->append(SlotType::Header, [
            'brand_name' => 'Acme',
            'tagline' => 'Next-Gen CRM & Marketing',
            'show_date' => true,
        ]);

        $doc->append(SlotType::Hero, [
            'title' => 'Say Hello to Acme 2.0',
            'subtitle' => 'Built from the ground up for modern go-to-market teams who demand speed, clarity, and zero complexity.',
            'button_text' => 'Explore the Live Demo',
            'button_url' => 'https://example.com/demo',
            'bg_color' => '#0f172a',
            'text_color' => '#ffffff',
            'badge' => 'JUST LAUNCHED',
        ]);

        $doc->append(SlotType::Features, [
            'heading' => 'What makes Acme 2.0 different?',
            'items' => [
                [
                    'icon' => '⚡',
                    'title' => 'Sub-Second Pipeline Velocity',
                    'text' => 'Track your complete sales and marketing funnel in real-time without sluggish sync delays.',
                ],
                [
                    'icon' => '🎯',
                    'title' => 'Predictive Behavioral Scoring',
                    'text' => 'Score MQLs with machine learning and automated time-decay models that keep sales focused on hot leads.',
                ],
                [
                    'icon' => '✉️',
                    'title' => 'Modular Blade & MJML Email Engine',
                    'text' => 'Design bulletproof Outlook-tested responsive marketing campaigns in seconds.',
                ],
            ],
        ]);

        $doc->append(SlotType::Testimonial, [
            'quote' => 'Switching to Acme allowed us to sunset 4 different point solutions and improved our SDR response rate by 40% in our first month.',
            'author' => 'Sarah Connor',
            'role' => 'VP of Revenue Operations',
            'company' => 'Acrobatics Cloud',
            'rating' => 5,
        ]);

        $doc->append(SlotType::Button, [
            'text' => 'Start Your 14-Day Free Trial',
            'url' => 'https://example.com/signup',
            'align' => 'center',
            'style' => 'primary',
        ]);

        $doc->append(SlotType::SocialLinks, [
            'links' => [
                ['network' => 'Twitter', 'url' => 'https://twitter.com/acme'],
                ['network' => 'LinkedIn', 'url' => 'https://linkedin.com/company/acme'],
                ['network' => 'GitHub', 'url' => 'https://github.com/acme'],
            ],
        ]);

        $doc->append(SlotType::Footer, [
            'company_name' => 'Acme Technologies Inc.',
            'address' => '548 Market St, Suite 100, San Francisco, CA 94104',
            'unsubscribe_url' => '{{unsubscribe_url}}',
        ]);

        return $doc;
    }
}
