<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Presets;

use DoPHP\MailBuilder\Data\EmailDocument;
use DoPHP\MailBuilder\Enums\SlotType;

class NewsletterDigestPreset implements PresetContract
{
    public function key(): string
    {
        return 'newsletter_digest';
    }

    public function label(): string
    {
        return '📰 Editorial Newsletter & Weekly Digest';
    }

    public function description(): string
    {
        return 'Clean publication format for weekly digests, industry insights, dual-column story highlights, and curated links.';
    }

    public function category(): string
    {
        return 'newsletter';
    }

    public function document(): EmailDocument
    {
        $doc = new EmailDocument(
            subject: 'The Weekly Signal #42: Modern GTM Playbooks',
            previewText: 'Key insights on revenue architecture, outbound benchmarks, and retention tactics.',
        );

        $doc->append(SlotType::Header, [
            'brand_name' => 'The Weekly Signal',
            'tagline' => 'Curated by Acme Engineering',
            'show_date' => true,
        ]);

        $doc->append(SlotType::Hero, [
            'title' => 'Issue #42: The Death of the Clunky CRM',
            'subtitle' => 'Why high-velocity teams are abandoning legacy enterprise suites for lean, developer-first platforms.',
            'button_text' => 'Read Full Analysis (7 min)',
            'button_url' => 'https://example.com/blog/issue-42',
            'bg_color' => '#1e293b',
            'text_color' => '#ffffff',
        ]);

        $doc->append(SlotType::BodyText, [
            'content' => '<p>Hi {{contact.first_name}},</p><p>Welcome to this week’s digest! In today’s briefing, we break down how top growth engineers are structuring their data models and why latency in lead assignment is costing sales teams up to 30% in win rates.</p>',
        ]);

        $doc->append(SlotType::TwoColumn, [
            'left_title' => '⚡ Speed to Lead Benchmarks',
            'left_body' => 'Responding to an inbound MQL in under 5 minutes increases conversion odds by 9x compared to waiting 30 minutes.',
            'left_button_text' => 'Read Study',
            'left_button_url' => 'https://example.com/research/speed',
            'right_title' => '📊 Unified Timeline Analytics',
            'right_body' => 'How tying page visits, form fills, and email opens to one contact record eliminates sales blind spots.',
            'right_button_text' => 'See Diagram',
            'right_button_url' => 'https://example.com/research/timeline',
        ]);

        $doc->append(SlotType::StatBox, [
            'heading' => 'Industry Metric of the Week',
            'stats' => [
                ['value' => '68%', 'label' => 'of reps miss quota due to manual data entry', 'change' => '-14% with Acme'],
                ['value' => '4.2x', 'label' => 'faster deal cycle velocity with automated sequences', 'change' => '+320%'],
            ],
        ]);

        $doc->append(SlotType::Divider, [
            'height' => 24,
            'show_line' => true,
        ]);

        $doc->append(SlotType::Footer, [
            'company_name' => 'The Weekly Signal by Acme',
            'address' => '548 Market St, San Francisco, CA',
            'unsubscribe_url' => '{{unsubscribe_url}}',
        ]);

        return $doc;
    }
}
