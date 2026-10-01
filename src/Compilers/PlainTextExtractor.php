<?php

declare(strict_types=1);

namespace DoPHP\MailBuilder\Compilers;

use DoPHP\MailBuilder\Data\EmailDocument;
use DoPHP\MailBuilder\Data\EmailSlot;
use DoPHP\MailBuilder\Enums\SlotType;

class PlainTextExtractor
{
    /**
     * Extract plain text from an EmailDocument.
     */
    public function extractFromDocument(EmailDocument $document): string
    {
        $lines = [];

        if ($document->previewText !== null && $document->previewText !== '') {
            $lines[] = $document->previewText;
            $lines[] = '';
        }

        foreach ($document->slots as $slot) {
            $slotText = $this->extractFromSlot($slot);
            if ($slotText !== '') {
                $lines[] = $slotText;
                $lines[] = '';
            }
        }

        return trim(implode("\n", $lines));
    }

    /**
     * Extract plain text from an individual slot.
     */
    public function extractFromSlot(EmailSlot $slot): string
    {
        return match ($slot->type) {
            SlotType::Header => $this->extractHeader($slot),
            SlotType::Hero => $this->extractHero($slot),
            SlotType::BodyText => $this->extractBodyText($slot),
            SlotType::Button => $this->extractButton($slot),
            SlotType::TwoColumn => $this->extractTwoColumn($slot),
            SlotType::Features => $this->extractFeatures($slot),
            SlotType::Testimonial => $this->extractTestimonial($slot),
            SlotType::StatBox => $this->extractStatBox($slot),
            SlotType::Divider => "----------------------------------------\n",
            SlotType::SocialLinks => $this->extractSocialLinks($slot),
            SlotType::Footer => $this->extractFooter($slot),
            SlotType::Html => $this->extractRawHtml($slot),
            SlotType::ImageBanner => $this->extractImageBanner($slot),
            SlotType::VideoCard => $this->extractVideoCard($slot),
            SlotType::PricingGrid => $this->extractPricingGrid($slot),
            SlotType::RatingBar => $this->extractRatingBar($slot),
            SlotType::CountdownTimer => $this->extractCountdownTimer($slot),
            SlotType::Accordion => $this->extractAccordion($slot),
            SlotType::DynamicFeed => $this->extractDynamicFeed($slot),
            SlotType::OrderReceipt => $this->extractOrderReceipt($slot),
            SlotType::RssFeed => $this->extractRssFeed($slot),
            SlotType::ThreeColumn => $this->extractThreeColumn($slot),
            SlotType::FourColumn => $this->extractFourColumn($slot),
            SlotType::AsymmetricColumns => $this->extractAsymmetricColumns($slot),
            SlotType::ProductCatalog => $this->extractProductCatalog($slot),
            SlotType::CouponCode => $this->extractCouponCode($slot),
            SlotType::AppBadges => $this->extractAppBadges($slot),
            SlotType::DataTable => $this->extractDataTable($slot),
            SlotType::LabeledDivider => $this->extractLabeledDivider($slot),
        };
    }

    /**
     * @param  list<array<string, mixed>>  $slots
     */
    public function extractFromSlotArray(array $slots): string
    {
        $document = EmailDocument::fromArray(['slots' => $slots]);

        return $this->extractFromDocument($document);
    }

    /**
     * Extract plain text from raw HTML email string.
     */
    public function extractFromHtml(string $html): string
    {
        // Replace links <a href="url">text</a> with "text (url)"
        $text = (string) preg_replace_callback('/<a[^>]+href=([\'"])(.*?)\1[^>]*>(.*?)<\/a>/is', function (array $matches): string {
            $url = $matches[2];
            $label = trim(strip_tags($matches[3]));

            return $label !== '' && $label !== $url ? "{$label} ({$url})" : $url;
        }, $html);

        // Convert <br>, <p>, <h1>-<h6>, <div>, <tr>, <li> into newlines
        $text = (string) preg_replace('/<(?:br|p|div|tr|h[1-6])[^>]*>/i', "\n", $text);
        $text = (string) preg_replace('/<\/li>/i', "\n", $text);
        $text = (string) preg_replace('/<li[^>]*>/i', ' * ', $text);

        // Strip remaining HTML tags
        $text = strip_tags($text);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Collapse multiple blank lines
        $text = (string) preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }

    protected function extractHeader(EmailSlot $slot): string
    {
        $brandName = (string) $slot->get('brand_name', '');
        $tagline = (string) $slot->get('tagline', '');

        $out = $brandName;
        if ($tagline !== '') {
            $out .= " - {$tagline}";
        }

        return $out;
    }

    protected function extractHero(EmailSlot $slot): string
    {
        $title = (string) $slot->get('title', '');
        $subtitle = (string) $slot->get('subtitle', '');
        $buttonText = (string) $slot->get('button_text', '');
        $buttonUrl = (string) $slot->get('button_url', '');

        $lines = array_filter([$title, $subtitle]);

        if ($buttonText !== '' && $buttonUrl !== '') {
            $lines[] = ">> {$buttonText}: {$buttonUrl}";
        }

        return implode("\n", $lines);
    }

    protected function extractBodyText(EmailSlot $slot): string
    {
        $content = (string) $slot->get('content', '');

        return $this->extractFromHtml($content);
    }

    protected function extractButton(EmailSlot $slot): string
    {
        $text = (string) $slot->get('text', 'Click Here');
        $url = (string) $slot->get('url', '#');

        return ">> {$text}: {$url}";
    }

    protected function extractTwoColumn(EmailSlot $slot): string
    {
        $leftTitle = (string) $slot->get('left_title', '');
        $leftBody = $this->extractFromHtml((string) $slot->get('left_body', ''));
        $rightTitle = (string) $slot->get('right_title', '');
        $rightBody = $this->extractFromHtml((string) $slot->get('right_body', ''));

        $lines = [];
        if ($leftTitle !== '' || $leftBody !== '') {
            $lines[] = "[ {$leftTitle} ]\n{$leftBody}";
        }
        if ($rightTitle !== '' || $rightBody !== '') {
            $lines[] = "[ {$rightTitle} ]\n{$rightBody}";
        }

        return implode("\n\n", $lines);
    }

    protected function extractFeatures(EmailSlot $slot): string
    {
        /** @var list<array{icon?: string, title?: string, text?: string}> $items */
        $items = $slot->get('items', []);
        $lines = [];

        foreach ($items as $item) {
            $icon = (string) ($item['icon'] ?? '•');
            $title = (string) ($item['title'] ?? '');
            $text = (string) ($item['text'] ?? '');
            $lines[] = "{$icon} {$title}: {$text}";
        }

        return implode("\n", $lines);
    }

    protected function extractTestimonial(EmailSlot $slot): string
    {
        $quote = (string) $slot->get('quote', '');
        $author = (string) $slot->get('author', '');
        $role = (string) $slot->get('role', '');
        $company = (string) $slot->get('company', '');

        $details = implode(', ', array_filter([$role, $company]));
        $attribution = $author.($details !== '' ? " ({$details})" : '');

        return "“{$quote}”\n-- {$attribution}";
    }

    protected function extractStatBox(EmailSlot $slot): string
    {
        /** @var list<array{value?: string, label?: string, change?: string}> $stats */
        $stats = $slot->get('stats', []);
        $lines = [];

        foreach ($stats as $stat) {
            $val = (string) ($stat['value'] ?? '');
            $label = (string) ($stat['label'] ?? '');
            $change = (string) ($stat['change'] ?? '');
            $line = "{$val} - {$label}";
            if ($change !== '') {
                $line .= " ({$change})";
            }
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    protected function extractSocialLinks(EmailSlot $slot): string
    {
        /** @var list<array{network?: string, url?: string}> $links */
        $links = $slot->get('links', []);
        $lines = [];

        foreach ($links as $link) {
            $net = (string) ($link['network'] ?? 'Link');
            $url = (string) ($link['url'] ?? '');
            if ($url !== '') {
                $lines[] = "{$net}: {$url}";
            }
        }

        return implode(' | ', $lines);
    }

    protected function extractFooter(EmailSlot $slot): string
    {
        $company = (string) $slot->get('company_name', config('mail-builder.footer.company_name', config('app.name')));
        $address = (string) $slot->get('address', '');
        $unsubscribeUrl = (string) $slot->get('unsubscribe_url', '{{unsubscribe_url}}');

        $lines = ['(C) '.date('Y')." {$company}."];
        if ($address !== '') {
            $lines[] = $address;
        }
        $lines[] = "Unsubscribe: {$unsubscribeUrl}";

        return implode("\n", $lines);
    }

    protected function extractRawHtml(EmailSlot $slot): string
    {
        return $this->extractFromHtml((string) $slot->get('html', ''));
    }

    protected function extractImageBanner(EmailSlot $slot): string
    {
        $alt = (string) $slot->get('alt_text', 'Image');
        $link = (string) $slot->get('link_url', '');
        $caption = (string) $slot->get('caption', '');

        $lines = [];
        $header = $link !== '' ? "[Image: {$alt}] ({$link})" : "[Image: {$alt}]";
        $lines[] = $header;

        if ($caption !== '') {
            $lines[] = $caption;
        }

        return implode("\n", $lines);
    }

    protected function extractVideoCard(EmailSlot $slot): string
    {
        $title = (string) $slot->get('title', 'Video');
        $videoUrl = (string) $slot->get('video_url', '');
        $subtitle = (string) $slot->get('subtitle', '');

        $lines = [];
        $lines[] = "[Video: {$title}] ({$videoUrl})";
        if ($subtitle !== '') {
            $lines[] = $subtitle;
        }

        return implode("\n", $lines);
    }

    protected function extractPricingGrid(EmailSlot $slot): string
    {
        $heading = (string) $slot->get('heading', '');
        $subtitle = (string) $slot->get('subtitle', '');
        /** @var list<array{name?: string, price?: string, frequency?: string, button_text?: string, button_url?: string}> $tiers */
        $tiers = $slot->get('tiers', []);

        $lines = [];
        if ($heading !== '') {
            $lines[] = $heading;
        }
        if ($subtitle !== '') {
            $lines[] = $subtitle;
        }

        foreach ($tiers as $tier) {
            $name = (string) ($tier['name'] ?? 'Plan');
            $price = (string) ($tier['price'] ?? '');
            $frequency = (string) ($tier['frequency'] ?? '');
            $btnText = (string) ($tier['button_text'] ?? 'Choose Plan');
            $btnUrl = (string) ($tier['button_url'] ?? '');

            $line = "• {$name}: {$price} {$frequency}";
            if ($btnUrl !== '') {
                $line .= " - {$btnText} ({$btnUrl})";
            }
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    protected function extractRatingBar(EmailSlot $slot): string
    {
        $question = (string) $slot->get('question', 'How likely are you to recommend us?');
        $scale = (string) $slot->get('scale_type', '10');
        $baseUrl = (string) $slot->get('base_url', '');

        $lines = [];
        $lines[] = "[Rating Scale {$scale}]: {$question}";
        if ($baseUrl !== '') {
            $lines[] = "Submit score: {$baseUrl}";
        }

        return implode("\n", $lines);
    }

    protected function extractCountdownTimer(EmailSlot $slot): string
    {
        $heading = (string) $slot->get('heading', 'Limited Time Offer');
        $subtext = (string) $slot->get('subtext', '');
        $deadline = (string) $slot->get('deadline_text', '');
        $btnText = (string) $slot->get('button_text', '');
        $btnUrl = (string) $slot->get('button_url', '');

        $lines = [];
        $lines[] = "⚡ {$heading}";
        if ($deadline !== '') {
            $lines[] = "Expires: {$deadline}";
        }
        if ($subtext !== '') {
            $lines[] = $subtext;
        }
        if ($btnText !== '' && $btnUrl !== '') {
            $lines[] = "{$btnText} ({$btnUrl})";
        }

        return implode("\n", $lines);
    }

    protected function extractAccordion(EmailSlot $slot): string
    {
        $heading = (string) $slot->get('heading', 'Frequently Asked Questions');
        $subtitle = (string) $slot->get('subtitle', '');
        /** @var list<array{question?: string, answer?: string}> $items */
        $items = $slot->get('items', []);

        $lines = [];
        if ($heading !== '') {
            $lines[] = "=== {$heading} ===";
        }
        if ($subtitle !== '') {
            $lines[] = $subtitle;
        }

        foreach ($items as $item) {
            $q = (string) ($item['question'] ?? 'Question');
            $a = (string) ($item['answer'] ?? '');
            $lines[] = "Q: {$q}";
            if ($a !== '') {
                $lines[] = "A: {$a}";
            }
            $lines[] = '';
        }

        return trim(implode("\n", $lines));
    }

    protected function extractDynamicFeed(EmailSlot $slot): string
    {
        $heading = (string) $slot->get('heading', 'Recommended For You');
        $subtitle = (string) $slot->get('subtitle', '');
        /** @var list<array{title?: string, description?: string, price?: string, button_url?: string, button_text?: string}> $items */
        $items = $slot->get('items', []);

        $lines = [];
        if ($heading !== '') {
            $lines[] = "=== {$heading} ===";
        }
        if ($subtitle !== '') {
            $lines[] = $subtitle;
        }

        foreach ($items as $item) {
            $title = (string) ($item['title'] ?? 'Item');
            $price = (string) ($item['price'] ?? '');
            $desc = (string) ($item['description'] ?? '');
            $url = (string) ($item['button_url'] ?? '');

            $itemLine = "* {$title}";
            if ($price !== '') {
                $itemLine .= " - {$price}";
            }
            $lines[] = $itemLine;
            if ($desc !== '') {
                $lines[] = "  {$desc}";
            }
            if ($url !== '') {
                $lines[] = "  Link: {$url}";
            }
            $lines[] = '';
        }

        return trim(implode("\n", $lines));
    }

    protected function extractOrderReceipt(EmailSlot $slot): string
    {
        $orderNumber = (string) $slot->get('order_number', '1001');
        $orderDate = (string) $slot->get('order_date', '');
        $status = (string) $slot->get('status', 'Confirmed');
        $currency = (string) $slot->get('currency', '$');
        $subtotal = (string) $slot->get('subtotal', '0.00');
        $tax = (string) $slot->get('tax', '0.00');
        $shipping = (string) $slot->get('shipping', '0.00');
        $total = (string) $slot->get('total', '0.00');
        /** @var list<array{name?: string, quantity?: int|string, price?: string, variant?: string}> $items */
        $items = $slot->get('items', []);

        $lines = [];
        $lines[] = "=== ORDER RECEIPT #{$orderNumber} ===";
        if ($orderDate !== '') {
            $lines[] = "Date: {$orderDate}";
        }
        $lines[] = "Status: {$status}";
        $lines[] = '----------------------------------------';

        foreach ($items as $item) {
            $name = (string) ($item['name'] ?? 'Product');
            $qty = (string) ($item['quantity'] ?? '1');
            $price = (string) ($item['price'] ?? '0.00');
            $variant = (string) ($item['variant'] ?? '');

            $itemText = "* {$name} (x{$qty}) - {$currency}{$price}";
            if ($variant !== '') {
                $itemText .= " [{$variant}]";
            }
            $lines[] = $itemText;
        }

        $lines[] = '----------------------------------------';
        $lines[] = "Subtotal: {$currency}{$subtotal}";
        $lines[] = "Shipping: {$currency}{$shipping}";
        $lines[] = "Tax: {$currency}{$tax}";
        $lines[] = "Total: {$currency}{$total}";

        return trim(implode("\n", $lines));
    }

    protected function extractRssFeed(EmailSlot $slot): string
    {
        $heading = (string) $slot->get('heading', 'Latest Articles');
        $subtitle = (string) $slot->get('subtitle', '');
        /** @var list<array{title?: string, published_at?: string, summary?: string, url?: string, author?: string}> $items */
        $items = $slot->get('items', []);

        $lines = [];
        if ($heading !== '') {
            $lines[] = "=== {$heading} ===";
        }
        if ($subtitle !== '') {
            $lines[] = $subtitle;
        }

        foreach ($items as $item) {
            $title = (string) ($item['title'] ?? 'Article');
            $date = (string) ($item['published_at'] ?? '');
            $summary = (string) ($item['summary'] ?? '');
            $url = (string) ($item['url'] ?? '');

            $line = "* {$title}";
            if ($date !== '') {
                $line .= " ({$date})";
            }
            $lines[] = $line;
            if ($summary !== '') {
                $lines[] = "  {$summary}";
            }
            if ($url !== '') {
                $lines[] = "  Read: {$url}";
            }
            $lines[] = '';
        }

        return trim(implode("\n", $lines));
    }

    protected function extractThreeColumn(EmailSlot $slot): string
    {
        $heading = (string) $slot->get('heading', '');
        $subtitle = (string) $slot->get('subtitle', '');
        /** @var list<array{title?: string, description?: string, button_text?: string, button_url?: string}> $columns */
        $columns = $slot->get('columns', []);

        $lines = [];
        if ($heading !== '') {
            $lines[] = "=== {$heading} ===";
        }
        if ($subtitle !== '') {
            $lines[] = $subtitle;
        }

        foreach ($columns as $col) {
            $title = (string) ($col['title'] ?? 'Feature');
            $desc = (string) ($col['description'] ?? '');
            $btnText = (string) ($col['button_text'] ?? '');
            $btnUrl = (string) ($col['button_url'] ?? '');

            $lines[] = "* {$title}";
            if ($desc !== '') {
                $lines[] = "  {$desc}";
            }
            if ($btnText !== '' && $btnUrl !== '') {
                $lines[] = "  CTA: {$btnText} ({$btnUrl})";
            }
            $lines[] = '';
        }

        return trim(implode("\n", $lines));
    }

    protected function extractFourColumn(EmailSlot $slot): string
    {
        $heading = (string) $slot->get('heading', '');
        /** @var list<array{label?: string, url?: string}> $items */
        $items = $slot->get('items', []);

        $lines = [];
        if ($heading !== '') {
            $lines[] = "=== {$heading} ===";
        }

        $labels = [];
        foreach ($items as $item) {
            $label = (string) ($item['label'] ?? '');
            if ($label !== '') {
                $labels[] = $label;
            }
        }

        if (! empty($labels)) {
            $lines[] = implode(' | ', $labels);
        }

        return trim(implode("\n", $lines));
    }

    protected function extractAsymmetricColumns(EmailSlot $slot): string
    {
        $leftTitle = (string) $slot->get('left_title', '');
        $leftBody = (string) $slot->get('left_body', '');
        $rightTitle = (string) $slot->get('right_title', '');
        $rightBody = (string) $slot->get('right_body', '');
        $rightBtnText = (string) $slot->get('right_button_text', '');
        $rightBtnUrl = (string) $slot->get('right_button_url', '');

        $lines = [];
        if ($leftTitle !== '') {
            $lines[] = "[{$leftTitle}]";
        }
        if ($leftBody !== '') {
            $lines[] = $leftBody;
        }
        $lines[] = '---';
        if ($rightTitle !== '') {
            $lines[] = "[{$rightTitle}]";
        }
        if ($rightBody !== '') {
            $lines[] = $rightBody;
        }
        if ($rightBtnText !== '' && $rightBtnUrl !== '') {
            $lines[] = ">> {$rightBtnText}: {$rightBtnUrl}";
        }

        return trim(implode("\n", $lines));
    }

    protected function extractProductCatalog(EmailSlot $slot): string
    {
        $sectionTitle = (string) $slot->get('section_title', '');
        $sectionSubtitle = (string) $slot->get('section_subtitle', '');
        /** @var list<array<string, mixed>> $products */
        $products = (array) $slot->get('products', []);

        $lines = [];
        if ($sectionTitle !== '') {
            $lines[] = "=== {$sectionTitle} ===";
        }
        if ($sectionSubtitle !== '') {
            $lines[] = $sectionSubtitle;
            $lines[] = '';
        }

        foreach ($products as $product) {
            $title = (string) ($product['title'] ?? '');
            $price = (string) ($product['price'] ?? '');
            $comparePrice = (string) ($product['compare_at_price'] ?? '');
            $savings = (string) ($product['savings_badge'] ?? '');
            $stock = (string) ($product['stock_badge'] ?? '');
            $btnText = (string) ($product['button_text'] ?? 'Buy Now');
            $btnUrl = (string) ($product['button_url'] ?? '');
            $desc = (string) ($product['description'] ?? '');

            if ($title !== '') {
                $priceLine = "* {$title} - {$price}";
                if ($comparePrice !== '') {
                    $priceLine .= " (was {$comparePrice})";
                }
                if ($savings !== '') {
                    $priceLine .= " [{$savings}]";
                }
                if ($stock !== '') {
                    $priceLine .= " ({$stock})";
                }
                $lines[] = $priceLine;

                if ($desc !== '') {
                    $lines[] = "  {$desc}";
                }
                if ($btnUrl !== '') {
                    $lines[] = "  >> {$btnText}: {$btnUrl}";
                }
                $lines[] = '';
            }
        }

        return trim(implode("\n", $lines));
    }

    protected function extractCouponCode(EmailSlot $slot): string
    {
        $headline = (string) $slot->get('headline', 'Special Discount Voucher');
        $description = (string) $slot->get('description', '');
        $code = (string) $slot->get('code', 'SAVE20');
        $discount = (string) $slot->get('discount_badge', '');
        $expires = (string) $slot->get('expires_at', '');
        $btnText = (string) $slot->get('button_text', 'Claim Offer');
        $btnUrl = (string) $slot->get('button_url', '');

        $lines = [];
        $header = "=== {$headline}";
        if ($discount !== '') {
            $header .= " [{$discount}]";
        }
        $header .= ' ===';
        $lines[] = $header;

        if ($description !== '') {
            $lines[] = $description;
        }

        $lines[] = "PROMO CODE: {$code}";

        if ($expires !== '') {
            $lines[] = "Expires: {$expires}";
        }

        if ($btnUrl !== '' && $btnUrl !== '#') {
            $lines[] = ">> {$btnText}: {$btnUrl}";
        }

        return trim(implode("\n", $lines));
    }

    protected function extractAppBadges(EmailSlot $slot): string
    {
        $heading = (string) $slot->get('heading', '');
        $subtitle = (string) $slot->get('subtitle', '');
        $appStore = (string) $slot->get('app_store_url', '');
        $googlePlay = (string) $slot->get('google_play_url', '');

        $lines = [];
        if ($heading !== '') {
            $lines[] = "=== {$heading} ===";
        }
        if ($subtitle !== '') {
            $lines[] = $subtitle;
        }
        if ($appStore !== '' && $appStore !== '#') {
            $lines[] = "Download on Apple App Store: {$appStore}";
        }
        if ($googlePlay !== '' && $googlePlay !== '#') {
            $lines[] = "Get it on Google Play: {$googlePlay}";
        }

        return trim(implode("\n", $lines));
    }

    protected function extractDataTable(EmailSlot $slot): string
    {
        $heading = (string) $slot->get('heading', '');
        /** @var list<string> $headers */
        $headers = (array) $slot->get('headers', []);
        /** @var list<array<string, mixed>|list<string>> $rows */
        $rows = (array) $slot->get('rows', []);

        $lines = [];
        if ($heading !== '') {
            $lines[] = "=== {$heading} ===";
        }
        if (! empty($headers)) {
            $lines[] = implode(' | ', $headers);
            $lines[] = str_repeat('-', 40);
        }
        foreach ($rows as $row) {
            $cells = isset($row['cells']) && is_array($row['cells']) ? $row['cells'] : $row;
            $lines[] = implode(' | ', array_map('strval', $cells));
        }

        return trim(implode("\n", $lines));
    }

    protected function extractLabeledDivider(EmailSlot $slot): string
    {
        $label = (string) $slot->get('label', '---');

        return "--- {$label} ---";
    }
}
