<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Tests\Feature;

use Odden\MailBuilder\Compilers\EmailSlotCompiler;
use Odden\MailBuilder\Data\EmailDocument;
use Odden\MailBuilder\Data\EmailSlot;
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\MailBuilder;
use Odden\MailBuilder\Tests\TestCase;

class EmailSlotCompilerTest extends TestCase
{
    public function test_compiles_full_document_with_outlook_and_responsive_boilerplate(): void
    {
        $doc = new EmailDocument(
            subject: 'Monthly Digest',
            previewText: 'Exclusive updates inside',
        );

        $doc->append(SlotType::Header, [
            'brand_name' => 'Focal',
            'tagline' => 'Intelligence Platform',
        ]);

        $doc->append(SlotType::Hero, [
            'title' => 'Revolutionizing RevOps',
            'subtitle' => 'Modern pipelines engineered for velocity.',
            'button_text' => 'Read Full Story',
            'button_url' => 'https://focal.test/story',
        ]);

        $compiler = app(EmailSlotCompiler::class);
        $html = $compiler->compileDocument($doc);

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('xmlns:v="urn:schemas-microsoft-com:vml"', $html);
        $this->assertStringContainsString('Exclusive updates inside', $html);
        $this->assertStringContainsString('Monthly Digest', $html);
        $this->assertStringContainsString('Focal', $html);
        $this->assertStringContainsString('Revolutionizing RevOps', $html);
        $this->assertStringContainsString('Read Full Story', $html);
        $this->assertStringContainsString('max-width: 600px', $html);
    }

    public function test_compiles_vml_bulletproof_button(): void
    {
        $slot = new EmailSlot(SlotType::Button, [
            'text' => 'Claim Your Offer',
            'url' => 'https://focal.test/offer',
            'style' => 'success',
            'align' => 'center',
        ]);

        $compiler = app(EmailSlotCompiler::class);
        $html = $compiler->compileSlot($slot);

        $this->assertStringContainsString('<!--[if mso]>', $html);
        $this->assertStringContainsString('<v:roundrect', $html);
        $this->assertStringContainsString('href="https://focal.test/offer"', $html);
        $this->assertStringContainsString('Claim Your Offer', $html);
    }

    public function test_compiles_two_column_stackable_card_grid(): void
    {
        $slot = new EmailSlot(SlotType::TwoColumn, [
            'left_title' => 'Feature One',
            'left_body' => 'High speed execution',
            'right_title' => 'Feature Two',
            'right_body' => 'Instant alerts',
        ]);

        $compiler = app(EmailSlotCompiler::class);
        $html = $compiler->compileSlot($slot);

        $this->assertStringContainsString('class="stack-column"', $html);
        $this->assertStringContainsString('Feature One', $html);
        $this->assertStringContainsString('Feature Two', $html);
        $this->assertStringContainsString('High speed execution', $html);
    }

    public function test_compiles_all_slot_types_without_errors(): void
    {
        $slots = [
            ['type' => 'header', 'data' => ['brand_name' => 'Focal Test']],
            ['type' => 'hero', 'data' => ['title' => 'Hero Banner']],
            ['type' => 'body_text', 'data' => ['content' => '<p>Paragraph</p>']],
            ['type' => 'button', 'data' => ['text' => 'CTA', 'url' => '#']],
            ['type' => 'two_column', 'data' => ['left_title' => 'L', 'left_body' => 'LB', 'right_title' => 'R', 'right_body' => 'RB']],
            ['type' => 'features', 'data' => ['heading' => 'Highlights', 'items' => [['icon' => '🔥', 'title' => 'Fast', 'text' => 'Instant']]]],
            ['type' => 'testimonial', 'data' => ['quote' => 'Great product!', 'author' => 'Jane Doe', 'role' => 'CTO', 'company' => 'Pied Piper']],
            ['type' => 'stat_box', 'data' => ['heading' => 'KPIs', 'stats' => [['value' => '99.9%', 'label' => 'Uptime']]]],
            ['type' => 'divider', 'data' => ['height' => 32, 'show_line' => true]],
            ['type' => 'social_links', 'data' => ['links' => [['network' => 'GitHub', 'url' => 'https://github.com']]]],
            ['type' => 'footer', 'data' => ['company_name' => 'Focal Test Inc.', 'address' => '123 Test St']],
            ['type' => 'html', 'data' => ['html' => '<div>Custom HTML block</div>']],
            ['type' => 'image_banner', 'data' => ['image_url' => 'https://focal.test/banner.png', 'alt_text' => 'Banner Alt']],
            ['type' => 'video_card', 'data' => ['video_url' => 'https://youtube.com/watch?v=123', 'title' => 'Product Demo Video']],
            ['type' => 'pricing_grid', 'data' => ['heading' => 'Plans', 'tiers' => [['name' => 'Pro', 'price' => '$99', 'frequency' => '/mo', 'features' => ['Feature A'], 'button_text' => 'Buy', 'button_url' => '#', 'is_popular' => true]]]],
        ];

        $html = MailBuilder::compile($slots, ['subject' => 'Full Slot Test']);

        $this->assertStringContainsString('Focal Test', $html);
        $this->assertStringContainsString('Hero Banner', $html);
        $this->assertStringContainsString('Paragraph', $html);
        $this->assertStringContainsString('Great product!', $html);
        $this->assertStringContainsString('99.9%', $html);
        $this->assertStringContainsString('Custom HTML block', $html);
        $this->assertStringContainsString('Banner Alt', $html);
        $this->assertStringContainsString('Product Demo Video', $html);
        $this->assertStringContainsString('Pro', $html);
        $this->assertStringContainsString('$99', $html);
    }

    public function test_compiler_inlines_css_automatically(): void
    {
        $slots = [
            ['type' => 'body_text', 'data' => ['content' => '<p>Important text</p>']],
        ];

        $html = MailBuilder::compile($slots, [
            'subject' => 'Inline Test',
            'inline_css' => true,
        ]);

        // Paragraph tags should receive inlined line-height/margin styling from layout
        $this->assertStringContainsString('margin-bottom: 16px', $html);
        $this->assertStringContainsString('line-height: 1.6', $html);
    }

    public function test_compiler_filters_slots_by_conditional_visibility(): void
    {
        $customerSlot = new EmailSlot(
            SlotType::Hero,
            ['title' => 'VIP Customer Exclusive'],
            ['field' => 'contact.lifecycle_stage', 'operator' => 'equals', 'value' => 'customer']
        );

        $generalSlot = new EmailSlot(
            SlotType::Hero,
            ['title' => 'General Welcome'],
        );

        $doc = new EmailDocument(slots: [$customerSlot, $generalSlot]);
        /** @var EmailSlotCompiler $compiler */
        $compiler = app(EmailSlotCompiler::class);

        // Scenario 1: Recipient is a customer
        $htmlCustomer = $compiler->compileDocument($doc, [
            'context' => ['contact' => ['lifecycle_stage' => 'customer']],
        ]);
        $this->assertStringContainsString('VIP Customer Exclusive', $htmlCustomer);
        $this->assertStringContainsString('General Welcome', $htmlCustomer);

        // Scenario 2: Recipient is a lead
        $htmlLead = $compiler->compileDocument($doc, [
            'context' => ['contact' => ['lifecycle_stage' => 'lead']],
        ]);
        $this->assertStringNotContainsString('VIP Customer Exclusive', $htmlLead);
        $this->assertStringContainsString('General Welcome', $htmlLead);
    }

    public function test_compiler_interpolates_merge_tags_with_recipient_context(): void
    {
        $slots = [
            ['type' => 'hero', 'data' => ['title' => 'Welcome {{contact.first_name}} to {{company.name}}!']],
        ];

        $html = MailBuilder::compile($slots, [
            'interpolate' => true,
            'context' => [
                'contact' => ['first_name' => 'Darren'],
                'company' => ['name' => 'Focal HQ'],
            ],
        ]);

        $this->assertStringContainsString('Welcome Darren to Focal HQ!', $html);
    }
}
