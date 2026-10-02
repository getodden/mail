<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Presets;

use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Enums\SlotType;

class WelcomeOnboardingPreset implements PresetContract
{
    public function key(): string
    {
        return 'welcome_onboarding';
    }

    public function label(): string
    {
        return '👋 Welcome & Customer Onboarding Journey';
    }

    public function description(): string
    {
        return 'Warm onboarding sequence welcoming new users with account activation, 3 key setup steps, and concierge support.';
    }

    public function category(): string
    {
        return 'onboarding';
    }

    public function document(): EmailDocument
    {
        $doc = new EmailDocument(
            subject: 'Welcome to {{app_name}}, {{contact.first_name}}! Let’s get you set up 🎉',
            previewText: '3 simple steps to get the most out of your new workspace.',
        );

        $doc->append(SlotType::Header, [
            'brand_name' => 'Acme',
            'tagline' => 'Customer Success',
            'show_date' => false,
        ]);

        $doc->append(SlotType::Hero, [
            'title' => 'Welcome to Acme! 🎉',
            'subtitle' => 'We are thrilled to partner with you. Your workspace is ready and primed for maximum sales velocity.',
            'button_text' => 'Launch Your Workspace',
            'button_url' => 'https://example.com/app',
            'bg_color' => '#1e1b4b',
            'text_color' => '#ffffff',
            'badge' => 'QUICK START',
        ]);

        $doc->append(SlotType::Features, [
            'heading' => '3 steps to complete your setup in under 5 minutes:',
            'items' => [
                [
                    'icon' => '1️⃣',
                    'title' => 'Import your existing contacts and companies',
                    'text' => 'Bring over your CSV or connect directly via our one-click migration wizard.',
                ],
                [
                    'icon' => '2️⃣',
                    'title' => 'Customize your deal stages and pipelines',
                    'text' => 'Tailor pipeline phases and probability percentages to match your exact sales process.',
                ],
                [
                    'icon' => '3️⃣',
                    'title' => 'Invite your sales and marketing teammates',
                    'text' => 'Collaborate seamlessly with unified activity feeds and automated task reminders.',
                ],
            ],
        ]);

        $doc->append(SlotType::Button, [
            'text' => 'Complete Workspace Setup →',
            'url' => 'https://example.com/app/onboarding',
            'align' => 'center',
            'style' => 'success',
        ]);

        $doc->append(SlotType::BodyText, [
            'content' => '<p style="text-align: center; color: #64748b; font-size: 14px;">Need assistance? Just reply to this email directly or book time with your dedicated onboarding specialist.</p>',
        ]);

        $doc->append(SlotType::Footer, [
            'company_name' => 'Acme Customer Success Team',
            'address' => '548 Market St, San Francisco, CA',
            'unsubscribe_url' => '{{unsubscribe_url}}',
        ]);

        return $doc;
    }
}
