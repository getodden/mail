<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Presets;

use DoPHP\MailBuilder\Data\EmailDocument;
use DoPHP\MailBuilder\Enums\SlotType;

class TransactionalReceiptPreset implements PresetContract
{
    public function key(): string
    {
        return 'transactional_receipt';
    }

    public function label(): string
    {
        return '🧾 Order Confirmation & Billing Receipt';
    }

    public function description(): string
    {
        return 'Clean transactional layout for invoices, payment receipts, plan renewals, and order summaries.';
    }

    public function category(): string
    {
        return 'transactional';
    }

    public function document(): EmailDocument
    {
        $doc = new EmailDocument(
            subject: 'Your Acme Receipt [INV-2026-0891]',
            previewText: 'Thank you for your payment. Here is your transaction breakdown.',
        );

        $doc->append(SlotType::Header, [
            'brand_name' => 'Acme Billing',
            'tagline' => 'Receipt #INV-2026-0891',
            'show_date' => true,
        ]);

        $doc->append(SlotType::StatBox, [
            'heading' => 'Payment Successful',
            'stats' => [
                ['value' => '$149.00', 'label' => 'Total Paid (USD)', 'change' => 'Visa ending 4242'],
                ['value' => 'Active', 'label' => 'Acme Growth Plan', 'change' => 'Renews Nov 2026'],
            ],
        ]);

        $doc->append(SlotType::BodyText, [
            'content' => '<p>Hi {{contact.first_name}},</p><p>We have processed your monthly subscription charge. You can review your updated invoices, change payment methods, or download PDF receipts from your billing portal at any time.</p>',
        ]);

        $doc->append(SlotType::Button, [
            'text' => 'Download Official PDF Invoice',
            'url' => 'https://example.com/billing/invoices/INV-2026-0891.pdf',
            'align' => 'center',
            'style' => 'dark',
        ]);

        $doc->append(SlotType::Footer, [
            'company_name' => 'Acme Inc. - Accounts Receivable',
            'address' => '548 Market St, San Francisco, CA',
            'unsubscribe_url' => '{{unsubscribe_url}}',
        ]);

        return $doc;
    }
}
