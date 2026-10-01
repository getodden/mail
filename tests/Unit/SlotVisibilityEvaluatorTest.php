<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Tests\Unit;

use DoPHP\MailBuilder\Conditions\SlotVisibilityEvaluator;
use DoPHP\MailBuilder\Data\EmailSlot;
use DoPHP\MailBuilder\Enums\SlotType;
use DoPHP\MailBuilder\Tests\TestCase;

class SlotVisibilityEvaluatorTest extends TestCase
{
    protected SlotVisibilityEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new SlotVisibilityEvaluator;
    }

    public function test_slot_without_visibility_rule_is_always_visible(): void
    {
        $slot = new EmailSlot(SlotType::Hero, ['title' => 'Universal Banner']);
        $this->assertTrue($this->evaluator->isVisible($slot, []));
        $this->assertTrue($this->evaluator->isVisible($slot, ['contact' => ['lifecycle_stage' => 'lead']]));
    }

    public function test_equals_operator(): void
    {
        $slot = new EmailSlot(
            SlotType::Button,
            [
                'text' => 'Upgrade to Pro',
                'visibility' => [
                    'field' => 'contact.lifecycle_stage',
                    'operator' => 'equals',
                    'value' => 'lead',
                ],
            ]
        );

        $this->assertTrue($this->evaluator->isVisible($slot, ['contact' => ['lifecycle_stage' => 'lead']]));
        $this->assertFalse($this->evaluator->isVisible($slot, ['contact' => ['lifecycle_stage' => 'customer']]));
    }

    public function test_not_equals_operator(): void
    {
        $slot = new EmailSlot(
            SlotType::PricingGrid,
            [
                'heading' => 'Plans',
                'visibility' => [
                    'field' => 'contact.lifecycle_stage',
                    'operator' => 'not_equals',
                    'value' => 'customer',
                ],
            ]
        );

        $this->assertTrue($this->evaluator->isVisible($slot, ['contact' => ['lifecycle_stage' => 'lead']]));
        $this->assertFalse($this->evaluator->isVisible($slot, ['contact' => ['lifecycle_stage' => 'customer']]));
    }

    public function test_contains_operator(): void
    {
        $slot = new EmailSlot(
            SlotType::Hero,
            [
                'title' => 'Tech Leaders Offer',
                'visibility' => [
                    'field' => 'contact.job_title',
                    'operator' => 'contains',
                    'value' => 'Engineer',
                ],
            ]
        );

        $this->assertTrue($this->evaluator->isVisible($slot, ['contact' => ['job_title' => 'Senior Software Engineer']]));
        $this->assertFalse($this->evaluator->isVisible($slot, ['contact' => ['job_title' => 'Marketing Specialist']]));
    }

    public function test_is_not_empty_and_is_empty_operators(): void
    {
        $slotNotEmpty = new EmailSlot(
            SlotType::StatBox,
            [
                'visibility' => [
                    'field' => 'company.industry',
                    'operator' => 'is_not_empty',
                ],
            ]
        );

        $this->assertTrue($this->evaluator->isVisible($slotNotEmpty, ['company' => ['industry' => 'SaaS']]));
        $this->assertFalse($this->evaluator->isVisible($slotNotEmpty, ['company' => ['industry' => '']]));

        $slotEmpty = new EmailSlot(
            SlotType::StatBox,
            [
                'visibility' => [
                    'field' => 'company.industry',
                    'operator' => 'is_empty',
                ],
            ]
        );

        $this->assertTrue($this->evaluator->isVisible($slotEmpty, ['company' => ['industry' => '']]));
        $this->assertFalse($this->evaluator->isVisible($slotEmpty, ['company' => ['industry' => 'Healthcare']]));
    }

    public function test_filter_slots_removes_ineligible_blocks(): void
    {
        $slots = [
            [
                'type' => SlotType::Hero->value,
                'data' => ['title' => 'Everyone Sees This'],
            ],
            [
                'type' => SlotType::Button->value,
                'data' => [
                    'text' => 'VIP CTA',
                    'visibility' => [
                        'field' => 'contact.tier',
                        'operator' => 'equals',
                        'value' => 'enterprise',
                    ],
                ],
            ],
        ];

        $contextRegular = ['contact' => ['tier' => 'standard']];
        $filteredRegular = $this->evaluator->filterSlots($slots, $contextRegular);
        $this->assertCount(1, $filteredRegular);
        $this->assertSame('Everyone Sees This', $filteredRegular[0]['data']['title']);

        $contextVip = ['contact' => ['tier' => 'enterprise']];
        $filteredVip = $this->evaluator->filterSlots($slots, $contextVip);
        $this->assertCount(2, $filteredVip);
    }
}
