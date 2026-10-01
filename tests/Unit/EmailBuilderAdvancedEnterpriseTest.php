<?php

declare(strict_types=1);

use DoPHP\MailBuilder\Audit\DevicePreviewService;
use DoPHP\MailBuilder\Audit\DnsDeliverabilityValidator;
use DoPHP\MailBuilder\Audit\ScreenshotProviderInterface;
use DoPHP\MailBuilder\Compilers\EmailImageOptimizer;
use DoPHP\MailBuilder\Compilers\GmailActionCompiler;
use DoPHP\MailBuilder\Enums\SlotType;
use DoPHP\MailBuilder\Mail\TemplateMailable;
use DoPHP\MailBuilder\MailBuilder;
use DoPHP\MailBuilder\Themes\FontManager;

it('generates Schema.org JSON-LD scripts for Gmail Quick Actions', function () {
    $compiler = GmailActionCompiler::make()
        ->viewAction('View Order', 'https://example.com/orders/123')
        ->confirmAction('Approve Expense', 'https://example.com/api/approve/456');

    expect($compiler->hasActions())->toBeTrue();
    expect($compiler->toArray())->toHaveCount(2);

    $script = $compiler->toScript();
    expect($script)
        ->toContain('<script type="application/ld+json">')
        ->toContain('http://schema.org')
        ->toContain('ViewAction')
        ->toContain('https://example.com/orders/123')
        ->toContain('ConfirmAction')
        ->toContain('https://example.com/api/approve/456');
});

it('injects Gmail Quick Actions into email head during compilation', function () {
    $html = MailBuilder::compile([
        [
            'type' => 'body_text',
            'data' => ['content' => 'Your subscription has been renewed.'],
        ],
    ], [
        'gmail_action' => [
            '@type' => 'ViewAction',
            'name' => 'View Invoice',
            'url' => 'https://example.com/invoices/999',
        ],
    ]);

    expect($html)
        ->toContain('<script type="application/ld+json">')
        ->toContain('ViewAction')
        ->toContain('https://example.com/invoices/999')
        ->toContain('Your subscription has been renewed.');
});

it('optimizes images for Outlook MSO and Retina high-DPI displays', function () {
    $rawHtml = '<div><img src="https://example.com/hero.png" style="width: 300px; height: 150px"></div>';
    $optimized = EmailImageOptimizer::optimize($rawHtml);

    expect($optimized)
        ->toContain('width="300"')
        ->toContain('height="150"')
        ->toContain('border="0"')
        ->toContain('-ms-interpolation-mode: bicubic')
        ->toContain('display: block')
        ->toContain('alt="" role="presentation"');

    // Test Retina halving
    $retinaHtml = '<div><img src="https://example.com/retina.png" width="600" height="400" data-retina="true"></div>';
    $retinaOptimized = EmailImageOptimizer::optimize($retinaHtml);

    expect($retinaOptimized)
        ->toContain('width="300"')
        ->toContain('height="200"');
});

it('simulates inbox envelope and evaluates character limits and cutoffs', function () {
    $simulation = MailBuilder::simulateEnvelope(
        subject: 'Special 50% Off Flash Sale for Our VIP Customers Today Only!',
        previewText: 'Claim your exclusive early-bird savings on all gear before midnight.',
        fromName: 'Acme Store',
        fromEmail: 'sales@acme.com'
    );

    expect($simulation)
        ->toHaveKey('subject')
        ->toHaveKey('subject_length')
        ->toHaveKey('is_subject_truncated_mobile', true)
        ->toHaveKey('previews')
        ->toHaveKey('bimi')
        ->toHaveKey('recommendations');

    expect($simulation['previews']['mobile']['subject'])
        ->toContain('...');

    expect($simulation['bimi']['ready'])->toBeTrue();
    expect($simulation['bimi']['domain'])->toBe('acme.com');

    // Test free webmail detection
    $freeWebmailSim = MailBuilder::simulateEnvelope(
        subject: 'Hey there',
        fromEmail: 'person@gmail.com'
    );
    expect($freeWebmailSim['bimi']['is_free_provider'])->toBeTrue();
    expect($freeWebmailSim['recommendations'])->toContain('Sender domain (gmail.com) is a free webmail provider and cannot authenticate with DMARC/BIMI brand logos.');
});

it('renders the product catalog & cart grid slot in responsive HTML and plain text', function () {
    $slotData = [
        'section_title' => 'Recommended For You',
        'columns' => 2,
        'products' => [
            [
                'title' => 'Ergonomic Mechanical Keyboard',
                'description' => 'Precision switches with sound dampening and RGB lighting.',
                'price' => '$149.00',
                'compare_at_price' => '$199.00',
                'savings_badge' => 'Save 25%',
                'stock_badge' => 'In Stock',
                'image_url' => 'https://example.com/keyboard.jpg',
                'button_text' => 'Buy Keyboard',
                'button_url' => 'https://example.com/shop/keyboard',
            ],
            [
                'title' => 'Precision Wireless Mouse',
                'description' => 'Ultralight gaming mouse with 26K DPI sensor.',
                'price' => '$79.00',
                'compare_at_price' => '$99.00',
                'savings_badge' => 'Save 20%',
                'stock_badge' => 'Only 2 Left',
                'image_url' => 'https://example.com/mouse.jpg',
                'button_text' => 'Buy Mouse',
                'button_url' => 'https://example.com/shop/mouse',
            ],
        ],
    ];

    $html = MailBuilder::compile([
        [
            'type' => SlotType::ProductCatalog->value,
            'data' => $slotData,
        ],
    ]);

    expect($html)
        ->toContain('Recommended For You')
        ->toContain('Ergonomic Mechanical Keyboard')
        ->toContain('$149.00')
        ->toContain('$199.00')
        ->toContain('Save 25%')
        ->toContain('Buy Keyboard')
        ->toContain('Precision Wireless Mouse')
        ->toContain('$79.00');

    // Plain text extraction
    $plainText = MailBuilder::plainText([
        [
            'type' => SlotType::ProductCatalog->value,
            'data' => $slotData,
        ],
    ]);

    expect($plainText)
        ->toContain('=== Recommended For You ===')
        ->toContain('Ergonomic Mechanical Keyboard - $149.00 (was $199.00) [Save 25%] (In Stock)')
        ->toContain('>> Buy Keyboard: https://example.com/shop/keyboard')
        ->toContain('Precision Wireless Mouse - $79.00 (was $99.00) [Save 20%] (Only 2 Left)')
        ->toContain('>> Buy Mouse: https://example.com/shop/mouse');
});

it('supports Liquid-style transform filters and inline conditional blocks', function () {
    $context = [
        'contact' => [
            'first_name' => 'alexandra',
            'created_at' => '2026-03-15 10:00:00',
            'is_vip' => true,
        ],
        'order' => [
            'amount' => 149.50,
            'items_count' => 1,
        ],
    ];

    $template = "Hello {{ contact.first_name | capitalize }}! Member since {{ contact.created_at | date: 'F Y' }}. Total: {{ order.amount | currency: 'EUR' }} for {{ order.items_count }} {{ order.items_count | pluralize: 'item', 'items' }}. {% if contact.is_vip %}Your VIP bonus is ready.{% else %}Upgrade to VIP today.{% endif %}";

    $result = MailBuilder::interpolate($template, $context);

    expect($result)
        ->toContain('Hello Alexandra!')
        ->toContain('Member since March 2026')
        ->toContain('149.50 EUR')
        ->toContain('1 item')
        ->toContain('Your VIP bonus is ready.');

    expect($result)->not->toContain('Upgrade to VIP today.');

    // Test falsy branch
    $context['contact']['is_vip'] = false;
    $context['order']['items_count'] = 3;
    $result2 = MailBuilder::interpolate($template, $context);

    expect($result2)
        ->toContain('Upgrade to VIP today.')
        ->toContain('3 items');
});

it('validates SPF, DMARC, and BIMI DNS records with mock resolver', function () {
    DnsDeliverabilityValidator::setDnsResolver(function (string $hostname, int $type): array {
        if ($hostname === 'acme.com') {
            return [
                ['txt' => 'v=spf1 include:_spf.google.com ~all'],
            ];
        }
        if ($hostname === '_dmarc.acme.com') {
            return [
                ['txt' => 'v=DMARC1; p=reject; rua=mailto:dmarc@acme.com'],
            ];
        }
        if ($hostname === 'default._bimi.acme.com') {
            return [
                ['txt' => 'v=BIMI1; l=https://acme.com/bimi.svg; a=https://acme.com/cert.pem'],
            ];
        }

        return [];
    });

    $result = MailBuilder::validateDns('acme.com');

    expect($result)
        ->toHaveKey('overall_status', 'optimal')
        ->toHaveKey('domain', 'acme.com');

    expect($result['spf']['found'])->toBeTrue();
    expect($result['dmarc']['enforced'])->toBeTrue();
    expect($result['dmarc']['policy'])->toBe('reject');
    expect($result['bimi']['ready'])->toBeTrue();
    expect($result['bimi']['logo_url'])->toBe('https://acme.com/bimi.svg');

    // Test domain missing DMARC
    DnsDeliverabilityValidator::setDnsResolver(function (string $hostname, int $type): array {
        return [];
    });

    $unconfigured = MailBuilder::validateDns('unconfigured.com');
    expect($unconfigured['overall_status'])->toBe('critical');
    expect($unconfigured['spf']['found'])->toBeFalse();
    expect($unconfigured['dmarc']['found'])->toBeFalse();
    expect($unconfigured['recommendations'])->not->toBeEmpty();

    // Reset resolver
    DnsDeliverabilityValidator::setDnsResolver(null);
});

it('simulates dark mode inversion and audits transparent logo visibility risks', function () {
    $html = '<!DOCTYPE html><html><head><meta name="color-scheme" content="light dark"></head><body style="background-color: #ffffff; color: #0f172a;"><img src="https://example.com/brand-logo.png" alt="Company Logo"></body></html>';

    $simulation = MailBuilder::simulateDarkMode($html);

    expect($simulation)
        ->toHaveKey('dark_html')
        ->toHaveKey('warnings')
        ->toHaveKey('is_dark_mode_ready');

    expect($simulation['dark_html'])
        ->toContain('background-color: #1a1a1a')
        ->toContain('color: #f8fafc');

    expect($simulation['warnings'])
        ->toContain('Detected transparent logo graphic in email. Verify that dark text or black outlines inside the logo remain legible when inverted against a dark background.');
});

it('renders the coupon code & voucher slot in responsive HTML and plain text', function () {
    $slotData = [
        'headline' => 'Exclusive Summer VIP Voucher',
        'code' => 'VIP-SUMMER-50',
        'discount_badge' => '50% OFF',
        'expires_at' => 'October 31, 2026',
        'button_text' => 'Redeem Now',
        'button_url' => 'https://example.com/redeem?code=VIP-SUMMER-50',
        'description' => 'Use this exclusive single-use voucher at checkout.',
    ];

    $html = MailBuilder::compile([
        [
            'type' => SlotType::CouponCode->value,
            'data' => $slotData,
        ],
    ]);

    expect($html)
        ->toContain('Exclusive Summer VIP Voucher')
        ->toContain('VIP-SUMMER-50')
        ->toContain('50% OFF')
        ->toContain('October 31, 2026')
        ->toContain('Redeem Now')
        ->toContain('https://example.com/redeem?code=VIP-SUMMER-50');

    // Plain text extraction
    $plainText = MailBuilder::plainText([
        [
            'type' => SlotType::CouponCode->value,
            'data' => $slotData,
        ],
    ]);

    expect($plainText)
        ->toContain('=== Exclusive Summer VIP Voucher [50% OFF] ===')
        ->toContain('PROMO CODE: VIP-SUMMER-50')
        ->toContain('Expires: October 31, 2026')
        ->toContain('>> Redeem Now: https://example.com/redeem?code=VIP-SUMMER-50');
});

it('inspects plain text differences and warns about missing merge tags', function () {
    $slots = [
        [
            'type' => 'body_text',
            'data' => ['content' => 'Hello {{ contact.first_name }}! Here is your custom access key: {{ access_token }}.'],
        ],
    ];

    // Case 1: In sync
    $inSyncText = 'Hello {{ contact.first_name }}! Here is your custom access key: {{ access_token }}.';
    $report1 = MailBuilder::diffPlainText($slots, $inSyncText);

    expect($report1['is_in_sync'])->toBeTrue();
    expect($report1['missing_merge_tags'])->toBeEmpty();

    // Case 2: Out of sync with missing token
    $outOfSyncText = 'Hello customer! Please visit our site for your key.';
    $report2 = MailBuilder::diffPlainText($slots, $outOfSyncText);

    expect($report2['is_in_sync'])->toBeFalse();
    expect($report2['missing_merge_tags'])->toContain('contact.first_name')
        ->toContain('access_token');
    expect($report2['recommendations'])->not->toBeEmpty();
});

it('generates multi-device screenshot preview URLs via DevicePreviewService', function () {
    $html = '<html><body><h1>Device Rendering Test</h1></body></html>';

    $previews = MailBuilder::devicePreviews($html, ['outlook_windows', 'apple_mail_ios', 'gmail_android']);

    expect($previews)
        ->toHaveKey('outlook_windows')
        ->toHaveKey('apple_mail_ios')
        ->toHaveKey('gmail_android');

    expect($previews['outlook_windows']['status'])->toBe('ready');
    expect($previews['outlook_windows']['url'])->toContain('/mail-builder/preview-device/outlook_windows-');

    // Test custom provider injection
    $mockProvider = new class implements ScreenshotProviderInterface
    {
        public function render(string $html, array $devices = []): array
        {
            return [
                'custom_client' => [
                    'status' => 'ready',
                    'url' => 'https://litmus-mock.example.com/screenshot.png',
                    'device_name' => 'Custom Litmus Driver',
                ],
            ];
        }
    };

    DevicePreviewService::setProvider($mockProvider);
    $customPreviews = MailBuilder::devicePreviews($html);
    expect($customPreviews)->toHaveKey('custom_client');
    expect($customPreviews['custom_client']['url'])->toBe('https://litmus-mock.example.com/screenshot.png');

    // Reset provider
    DevicePreviewService::setProvider(null);
});

it('manages theme presets and resolves configuration overrides via ThemeRegistry', function () {
    $registry = MailBuilder::themes();

    expect($registry->all())->toHaveKeys(['corporate_slate', 'midnight_indigo', 'emerald_saas', 'warm_sunset', 'monochrome']);

    $options = $registry->toSelectOptions();
    expect($options)->toHaveKey('corporate_slate', 'Corporate Slate')
        ->toHaveKey('midnight_indigo', 'Midnight Indigo');

    $applied = $registry->apply('corporate_slate', [
        'primary_color' => '#ff0055',
    ]);

    expect($applied['primary_color'])->toBe('#ff0055')
        ->and($applied['background_color'])->toBe('#f8fafc');

    // Register custom theme
    $registry->register('cyberpunk', [
        'name' => 'Cyberpunk Neon',
        'primary_color' => '#00ffcc',
    ]);
    $cyberpunk = $registry->get('cyberpunk');
    expect($cyberpunk)->toBeArray()
        ->and($cyberpunk['name'] ?? null)->toBe('Cyberpunk Neon');
});

it('handles web font imports and MSO font fallbacks via FontManager', function () {
    $interUrl = FontManager::getGoogleFontImportUrl("'Inter', sans-serif");
    expect($interUrl)->toContain('fonts.googleapis.com/css2?family=Inter:');

    $merriweatherUrl = FontManager::getGoogleFontImportUrl('Merriweather, serif');
    expect($merriweatherUrl)->toContain('fonts.googleapis.com/css2?family=Merriweather:');

    $unknownUrl = FontManager::getGoogleFontImportUrl('System-UI, sans-serif');
    expect($unknownUrl)->toBeNull();

    $msoFallback = FontManager::getMsoFallback("'Inter', sans-serif");
    expect($msoFallback)->toBe('Arial, Helvetica, sans-serif');

    $serifFallback = FontManager::getMsoFallback('Playfair Display, serif');
    expect($serifFallback)->toContain("Georgia, 'Times New Roman', serif");

    // Test head injection in compiled document
    $doc = MailBuilder::document(
        subject: 'Typography Test',
        theme: ['font_family' => "'Inter', sans-serif"]
    );
    $html = MailBuilder::compile($doc);

    expect($html)
        ->toContain('<!-- Web Font Import -->')
        ->toContain('fonts.googleapis.com/css2?family=Inter')
        ->toContain('<!--[if mso]>')
        ->toContain('font-family: Arial, Helvetica, sans-serif !important;');
});

it('audits render performance and benchmarks DOM nodes and nested table depth', function () {
    $simpleHtml = '<html><body><table><tr><td><p>Hello world</p></td></tr></table></body></html>';
    $benchmark = MailBuilder::benchmarkRender($simpleHtml);

    expect($benchmark)
        ->toHaveKey('dom_nodes_count')
        ->toHaveKey('dom_status', 'optimal')
        ->toHaveKey('nested_table_depth', 1)
        ->toHaveKey('table_depth_status', 'optimal')
        ->toHaveKey('image_count', 0)
        ->toHaveKey('html_size_kb')
        ->toHaveKey('estimated_download_ms')
        ->toHaveKey('recommendations');

    expect($benchmark['estimated_download_ms'])
        ->toHaveKeys(['slow_3g', 'fast_4g', 'wifi_5g']);

    // Deeply nested tables
    $nestedHtml = '<table><tr><td><table><tr><td><table><tr><td><table><tr><td><table><tr><td><table><tr><td><table><tr><td><table><tr><td><table><tr><td>Deep</td></tr></table></td></tr></table></td></tr></table></td></tr></table></td></tr></table></td></tr></table></td></tr></table></td></tr></table></td></tr></table>';
    $deepBenchmark = MailBuilder::benchmarkRender($nestedHtml);

    expect($deepBenchmark['nested_table_depth'])->toBe(9);
    expect($deepBenchmark['table_depth_status'])->toBe('critical');
    expect($deepBenchmark['recommendations'])->not->toBeEmpty();
});

it('supports reverse column stacking on mobile for two-column slots', function () {
    $slotData = [
        'left_title' => 'First Left Feature',
        'left_body' => 'This is left column text.',
        'right_title' => 'Second Right Feature',
        'right_body' => 'This is right column text.',
        'reverse_stack_on_mobile' => true,
    ];

    $html = MailBuilder::compile([
        [
            'type' => SlotType::TwoColumn->value,
            'data' => $slotData,
        ],
    ]);

    expect($html)
        ->toContain('dir="rtl"')
        ->toContain('direction: rtl;')
        ->toContain('align="right"')
        ->toContain('First Left Feature')
        ->toContain('align="left"')
        ->toContain('Second Right Feature');
});

it('renders app download badges slot in responsive HTML and plain text', function () {
    $slotData = [
        'heading' => 'Download Focal Mobile',
        'subtitle' => 'Available on iOS and Android',
        'app_store_url' => 'https://apps.apple.com/app/focal',
        'google_play_url' => 'https://play.google.com/store/apps/focal',
    ];

    $html = MailBuilder::compile([
        ['type' => SlotType::AppBadges->value, 'data' => $slotData],
    ]);

    expect($html)
        ->toContain('Download Focal Mobile')
        ->toContain('Available on iOS and Android')
        ->toContain('https://apps.apple.com/app/focal')
        ->toContain('https://play.google.com/store/apps/focal')
        ->toContain('App Store')
        ->toContain('Google Play');

    $plainText = MailBuilder::plainText([
        ['type' => SlotType::AppBadges->value, 'data' => $slotData],
    ]);

    expect($plainText)
        ->toContain('=== Download Focal Mobile ===')
        ->toContain('Download on Apple App Store: https://apps.apple.com/app/focal')
        ->toContain('Get it on Google Play: https://play.google.com/store/apps/focal');
});

it('renders data comparison table slot in responsive HTML and plain text', function () {
    $slotData = [
        'heading' => 'Feature Comparison',
        'headers' => ['Capability', 'Starter', 'Enterprise'],
        'rows' => [
            ['cells' => ['Custom Domains', '1', 'Unlimited']],
            ['cells' => ['SSO / SAML', 'No', 'Yes']],
        ],
    ];

    $html = MailBuilder::compile([
        ['type' => SlotType::DataTable->value, 'data' => $slotData],
    ]);

    expect($html)
        ->toContain('Feature Comparison')
        ->toContain('Capability')
        ->toContain('Custom Domains')
        ->toContain('Unlimited')
        ->toContain('SSO / SAML');

    $plainText = MailBuilder::plainText([
        ['type' => SlotType::DataTable->value, 'data' => $slotData],
    ]);

    expect($plainText)
        ->toContain('=== Feature Comparison ===')
        ->toContain('Capability | Starter | Enterprise')
        ->toContain('Custom Domains | 1 | Unlimited')
        ->toContain('SSO / SAML | No | Yes');
});

it('renders labeled divider slot in responsive HTML and plain text', function () {
    $slotData = [
        'label' => 'OR CONTINUE WITH',
        'badge_bg' => '#f8fafc',
    ];

    $html = MailBuilder::compile([
        ['type' => SlotType::LabeledDivider->value, 'data' => $slotData],
    ]);

    expect($html)
        ->toContain('OR CONTINUE WITH')
        ->toContain('background-color: #f8fafc');

    $plainText = MailBuilder::plainText([
        ['type' => SlotType::LabeledDivider->value, 'data' => $slotData],
    ]);

    expect($plainText)->toBe('--- OR CONTINUE WITH ---');
});

it('parses raw HTML into modular slots via HtmlToSlotsParser', function () {
    $rawHtml = '<div><h1>Main Hero Announcement</h1><p>This is a paragraph of text explaining the update.</p><a href="https://example.com/learn">Learn More</a><hr><img src="https://example.com/banner.png" alt="Promo Banner"></div>';

    $doc = MailBuilder::importHtml($rawHtml, 'Imported Email');

    expect($doc->subject)->toBe('Imported Email');
    expect($doc->slots)->not->toBeEmpty();

    $types = array_map(fn ($s) => $s->type->value, $doc->slots);
    expect($types)->toContain('hero')
        ->toContain('body_text')
        ->toContain('button')
        ->toContain('divider')
        ->toContain('image_banner');

    $compiled = MailBuilder::compile($doc);
    expect($compiled)
        ->toContain('Main Hero Announcement')
        ->toContain('Learn More')
        ->toContain('https://example.com/banner.png');
});

it('parses MJML markup into modular slots via MjmlToSlotsParser', function () {
    $mjml = '<mjml><mj-head><mj-title>MJML Campaign</mj-title><mj-preview>Special preview</mj-preview></mj-head><mj-body><mj-section><mj-column><mj-text>Welcome to our newsletter</mj-text><mj-button href="https://example.com/join">Join Free</mj-button><mj-image src="https://example.com/header.jpg" alt="Header Graphic" /></mj-column></mj-section></mj-body></mjml>';

    $doc = MailBuilder::importMjml($mjml);

    expect($doc->subject)->toBe('MJML Campaign');
    expect($doc->previewText)->toBe('Special preview');

    $types = array_map(fn ($s) => $s->type->value, $doc->slots);
    expect($types)->toContain('body_text')
        ->toContain('button')
        ->toContain('image_banner');

    expect($doc->slots[1]->get('url'))->toBe('https://example.com/join');
    expect($doc->slots[1]->get('text'))->toBe('Join Free');
});

it('audits color contrast using WCAG 2.1 relative luminance math', function () {
    // High contrast black on white
    $blackOnWhite = MailBuilder::wcagContrast('#000000', '#ffffff');
    expect($blackOnWhite['ratio'])->toBe(21.0);
    expect($blackOnWhite['aa_normal'])->toBeTrue();
    expect($blackOnWhite['rating'])->toBe('AAA');

    // Very low contrast light gray on white
    $lowContrast = MailBuilder::wcagContrast('#e2e8f0', '#ffffff');
    expect($lowContrast['ratio'])->toBeLessThan(2.0);
    expect($lowContrast['aa_normal'])->toBeFalse();
    expect($lowContrast['rating'])->toBe('Fail');

    // Audit theme
    $audit = MailBuilder::auditContrast([
        'text_color' => '#0f172a',
        'heading_color' => '#000000',
        'primary_color' => '#2563eb',
        'content_background_color' => '#ffffff',
    ]);

    expect($audit['score'])->toBeGreaterThanOrEqual(60);
    expect($audit['pairs'])->toHaveKey('body_text_on_card');
    expect($audit['pairs']['body_text_on_card']['passes_aa'])->toBeTrue();
});

it('compiles documents with RTL direction for Arabic and Hebrew layouts', function () {
    $doc = MailBuilder::document(
        subject: 'Arabic Newsletter',
        theme: [
            'direction' => 'rtl',
            'lang' => 'ar',
        ]
    );

    $html = MailBuilder::compile($doc);

    expect($html)
        ->toContain('dir="rtl"')
        ->toContain('direction: rtl;')
        ->toContain('lang="ar"');
});

it('extracts embedded images into CID inline attachments via CidImageEmbedder', function () {
    $fakePngBase64 = base64_encode('fake-png-binary-data');
    $htmlWithBase64 = "<html><body><img src=\"data:image/png;base64,{$fakePngBase64}\" alt=\"Embedded Logo\"><p>Hello world</p></body></html>";

    $result = MailBuilder::embedCidImages($htmlWithBase64);

    expect($result['html'])
        ->toContain('src="cid:img_');

    expect($result['html'])->not->toContain('data:image/png;base64');

    expect($result['attachments'])->toHaveCount(1);
    expect($result['attachments'][0]['mime'])->toBe('image/png');
    expect($result['attachments'][0]['data'])->toBe('fake-png-binary-data');
    expect($result['attachments'][0]['cid'])->toStartWith('img_');

    // Test with TemplateMailable embedCidImages flag
    $mailable = new TemplateMailable(
        template: $htmlWithBase64,
        embedCidImages: true
    );

    expect($mailable->compiledHtml)->toContain('src="cid:img_');
    expect($mailable->customAttachments)->not->toBeEmpty();
});
