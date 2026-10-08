# Laravel Mail Builder (`getodden/mail`)

[![tests](https://github.com/getodden/mail/actions/workflows/tests.yml/badge.svg)](https://github.com/getodden/mail/actions/workflows/tests.yml)

A modular, Outlook-bulletproof email building, auditing, and compilation engine for Laravel applications.

Requires PHP 8.3+ and Laravel 12 or 13. Maintained by [CaskStack, LLC](https://odden.io).

---

## Key Features

- **29 Modular Responsive Slots:** Pre-built responsive components that collapse smoothly to 100% width on mobile devices, with Microsoft Outlook MSO conditional table compatibility.
- **Universal Compilation Engine:** Compile modular slots or structured `EmailDocument` instances into inlined responsive HTML (`EmailSlotCompiler`) or interactive AMP for Email (`AmpEmailCompiler`).
- **Liquid-Style Personalization:** Fast token interpolation supporting uppercase, lowercase, capitalize, date formatting, currency, number, pluralize, default values, and inline conditionals (`{% if condition %}...{% else %}...{% endif %}`).
- **Deliverability & Spam Auditor:** Automatic pre-flight auditing calculating spam trigger scores, missing unsubscribe headers, image-to-text ratios, dark mode color inversion contrast, and live DNS deliverability checks (SPF, DMARC, BIMI).
- **Multi-Device Screenshot Simulation:** Adapter interface for testing email rendering across Outlook Windows (120 DPI), Apple Mail iOS 17 Dark Mode, macOS Sonoma, and Gmail Android.
- **Theme & Typography Engine:** Built-in design system presets (*Corporate Slate*, *Midnight Indigo*, *Emerald SaaS*, *Warm Sunset*, *Monochrome Minimal*) with automatic Google Fonts web font imports and Outlook fallback font stacks.
- **Bidirectional Ingestion:** Export templates to HTML, Plain Text, MJML, or ZIP packages—and reverse-parse external raw HTML or standard MJML into native modular slots.
- **WCAG 2.1 AA Contrast Auditor:** Exact mathematical relative luminance algorithm evaluating text and button contrast compliance.
- **Enterprise Transport:** Send via `TemplateMailable` with RFC 8058 1-click unsubscribe headers, custom attachments, and automatic CID (Content-ID) inline image embedding.
- **Filament Visual Builder:** Full Filament builder schema component with audience targeting and conditional visibility rules.
- **Drag-and-Drop Editor:** A framework-free web component with a palette, a live preview rendered by the real compiler, undo and redo, and keyboard and screen reader support for every action.

---

## Installation

```bash
composer require getodden/mail
```

Publish configuration and views (optional):

```bash
php artisan vendor:publish --tag=mail-builder-config
php artisan vendor:publish --tag=mail-builder-views
```

### Configuration

`config/mail-builder.php` sets the default layout theme (width, fonts, colors) and the footer identity used when a slot doesn't provide one:

```env
MAIL_BUILDER_COMPANY_NAME="Acme Inc."   # defaults to APP_NAME
MAIL_BUILDER_ADDRESS="123 Example St, Springfield"
```

CAN-SPAM and similar laws require a physical mailing address in marketing email, so set `MAIL_BUILDER_ADDRESS` or pass `address` to each footer slot.

---

## Quick Start

### 1. Building and Compiling an Email

```php
use Odden\MailBuilder\MailBuilder;
use Odden\MailBuilder\Enums\SlotType;

// Fluent EmailDocument API
$doc = MailBuilder::document(
    subject: 'Welcome to the Platform, {{ contact.first_name }}!',
    previewText: 'Get started with your new account in 3 easy steps.'
);

$doc->append(SlotType::Header, [
    'brand_name' => 'Acme Corp',
    'logo_url' => 'https://example.com/logo.png',
]);

$doc->append(SlotType::Hero, [
    'title' => 'Accelerate Your Workflow',
    'subtitle' => 'Everything you need to deliver world-class projects.',
    'button_text' => 'Get Started',
    'button_url' => 'https://example.com/onboarding',
]);

$doc->append(SlotType::TwoColumn, [
    'left_title' => 'Cloud Infrastructure',
    'left_body' => 'Deploy high-availability services across 30+ edge regions.',
    'right_title' => 'RevOps Analytics',
    'right_body' => 'Real-time pipeline tracking and automated revenue forecasting.',
    'reverse_stack_on_mobile' => true,
]);

$doc->append(SlotType::AppBadges, [
    'heading' => 'Download Our Mobile App',
    'app_store_url' => 'https://apps.apple.com/app/acme',
    'google_play_url' => 'https://play.google.com/store/apps/acme',
]);

$doc->append(SlotType::Footer, [
    'company_name' => 'Acme Inc.',
    'address' => '548 Market St, San Francisco, CA',
    'unsubscribe_url' => '{{ unsubscribe_url }}',
]);

// Compile to responsive inlined HTML
$html = MailBuilder::compile($doc);

// Extract plain text fallback
$plainText = MailBuilder::plainText($doc);
```

---

### 2. Personalization & Merge Tags

Personalization supports dot-notation object paths, Liquid filters, and inline conditionals:

```php
$template = "Hello {{ contact.first_name | capitalize }}! Member since {{ contact.created_at | date: 'F Y' }}. Balance: {{ account.balance | currency: 'USD' }}. {% if contact.is_vip %}Your VIP bonus is ready.{% else %}Upgrade today.{% endif %}";

$interpolated = MailBuilder::interpolate($template, [
    'contact' => [
        'first_name' => 'alexandra',
        'created_at' => '2026-03-15 10:00:00',
        'is_vip' => true,
    ],
    'account' => [
        'balance' => 1250.00,
    ],
]);
```

Built-in tags are `{{unsubscribe_url}}`, `{{current_year}}`, and `{{web_view_url}}`. Register your application's own tags so they appear in the Filament builder's tag picker and in previews:

```php
use Odden\MailBuilder\MergeTags\MergeTagRegistry;

// In a service provider's register() method
$this->callAfterResolving(MergeTagRegistry::class, function (MergeTagRegistry $registry): void {
    $registry->register('Customer', [
        '{{customer.first_name}}' => 'Customer first name',
        '{{customer.plan}}' => 'Subscription plan',
    ], [
        // Sample values used when previewing templates
        'customer' => ['first_name' => 'Alex', 'plan' => 'Pro'],
    ]);
});
```

Available Filters:
- `capitalize`, `title`, `lower`, `upper`, `trim`
- `date: 'Format'` (PHP `date()` formatting)
- `currency: 'USD'`
- `number: 2` (decimal places)
- `pluralize: 'item', 'items'`
- `truncate: 50`
- `default: 'fallback value'`

---

### 3. Ingestion (HTML & MJML Import)

Import external templates into editable modular slots:

```php
// Ingest raw HTML
$docFromHtml = MailBuilder::importHtml($rawHtml, 'Imported Newsletter');

// Ingest standard MJML
$docFromMjml = MailBuilder::importMjml($mjmlCode, 'Imported Campaign');

// Compile imported documents directly
$compiledHtml = MailBuilder::compile($docFromMjml);
```

---

### 4. Deliverability, Performance & Accessibility Audits

```php
// 1. Run Pre-flight Deliverability & Spam Audit
$audit = MailBuilder::audit($html);
if (! $audit->passes) {
    // Inspect $audit->critical_issues, $audit->warnings, and $audit->spam_score
}

// 2. WCAG 2.1 AA/AAA Color Contrast Audit
$contrast = MailBuilder::wcagContrast('#2563eb', '#ffffff');
// Returns: ['ratio' => 4.56, 'aa_normal' => true, 'rating' => 'AA']

$themeAudit = MailBuilder::auditContrast($themeArray);

// 3. Render Performance Benchmark
$benchmark = MailBuilder::benchmarkRender($html);
// Returns: dom_nodes_count, nested_table_depth, 3G/4G/5G download times

// 4. Sender Domain DNS Validation
$dns = MailBuilder::validateDns('yourdomain.com');
// Checks: SPF, DMARC (p=reject/quarantine), and BIMI
```

---

### 5. Sending Emails via `TemplateMailable`

```php
use Odden\MailBuilder\Mail\TemplateMailable;
use Illuminate\Support\Facades\Mail;

Mail::to('user@example.com')->send(
    new TemplateMailable(
        template: $doc,
        data: [
            'contact' => ['first_name' => 'Sarah'],
            'unsubscribe_url' => 'https://example.com/unsubscribe/token',
        ],
        subjectLine: 'Exclusive Offer for {{ contact.first_name }}',
        embedCidImages: true // Embeds local & base64 images as MIME CID parts
    )
);
```

---

### 6. Filament Visual Builder Integration

Requires `filament/forms` ^5.0. To integrate the modular slot builder into any Filament Resource:

```php
use Odden\MailBuilder\Filament\Components\EmailSlotBuilder;

public static function form(Schema $schema): Schema
{
    return $schema->components([
        EmailSlotBuilder::make('slots'),
    ]);
}
```

---

### 7. Slot schema (for editors)

Each slot type can describe itself: which fields its `data` takes, how to label and edit them, and the default of each. The Filament form is generated from this description, and a drag-and-drop editor can read it as JSON, so the two always agree with the slot's view.

```php
use Odden\MailBuilder\Enums\SlotType;
use Odden\MailBuilder\Schema\SlotSchemaRegistry;

$schema = SlotSchemaRegistry::for(SlotType::Hero);   // null for a type that is not described yet
$schema->defaults();                                  // the data of a new hero slot
$schema->basicFields();                               // the fields of the basic form

return response()->json(SlotSchemaRegistry::toArray()); // everything an editor needs, with a "version"
```

A field has a `key`, a `type` (`text`, `textarea`, `rich_text`, `url`, `image`, `color`, `select`, `toggle`, `number`, `items`), a `label`, `required`, a `default`, and, where they apply, `placeholder`, `help`, `options` and the item fields of a list. Fields marked `advanced` are read by the slot's view but kept out of the basic form: an editor shows them as style options.

Eight types are described so far: header, hero, body text, button, image banner, features, divider and footer. The rest are rendered by their views and edited with their Filament blocks as before, and are described one at a time. Tests check that every described field is read by its slot's view, so the description cannot drift from what is rendered.

### 8. Drag-and-drop editor

An editor for the slots, built as a web component with no framework: `<odden-mail-editor>`. It shows the email as it will be sent, with a palette of blocks, a structure list, and a settings panel for the selected block. Drag a block from the palette onto the email, or drag a block to move it. Every action also has a button and a keyboard shortcut, and changes are announced to screen readers. Undo and redo (Ctrl or Cmd + Z, Shift + Z) cover everything.

The editor edits the slot document as data and never touches HTML. The server renders the preview with the same compiler that builds the email, so what you see is what is sent, and the preview cannot be broken by editing. Only the first eight slot types are editable so far (see the slot schema above). A slot of another type in the document is kept as it is: you can move, duplicate and delete it, and its preview is rendered.

**1. Turn on the routes.** The editor needs two routes, a slot schema and a preview. They are off by default, because anyone who can reach them can render email HTML. Switch them on in `config/mail-builder.php` and set your own middleware:

```php
'editor' => [
    'routes' => [
        'enabled' => true,
        'prefix' => 'admin/mail-editor',
        'middleware' => ['web', 'auth'],   // your authentication and authorization
    ],
],
```

**2. Publish the editor files** (plain JavaScript modules and one stylesheet, no build step):

```bash
php artisan vendor:publish --tag=mail-builder-assets
```

**3. Use the element:**

```html
<script type="module" src="/vendor/mail-builder/editor/odden-mail-editor.js"></script>

<odden-mail-editor id="editor"
    schema-url="/admin/mail-editor/schema"
    preview-url="/admin/mail-editor/preview"></odden-mail-editor>

<script type="module">
    const editor = document.getElementById('editor');

    editor.value = { subject: 'News', slots: [{ type: 'hero', data: { title: 'Hello' } }] };
    editor.addEventListener('change', (event) => console.log(event.detail));   // the new document
</script>
```

- `value` is the document, in the shape `EmailDocument::fromArray()` takes: `{subject?, preview_text?, theme?, slots: [{type, data, visibility?}]}`. Keys other than `slots` are kept untouched.
- The `change` event carries the new document. With a `name` attribute the element also keeps a hidden input in sync with the JSON, for a plain form post.
- The page needs `<meta name="csrf-token">` for Laravel's CSRF check; the editor sends it as `X-CSRF-TOKEN`.
- To talk to the server some other way, set `editor.adapter = { loadSchema(), preview(document) }`.

The preview is rendered into a sandboxed iframe with scripts disabled, so a slot that holds HTML cannot run code in your admin page.

To try it while developing the package: `vendor/bin/testbench serve`, then open the home page.

### 9. The editor as a Filament field

`MailEditor` puts the drag-and-drop editor in a Filament form. It is a drop-in for `EmailSlotBuilder::make('slots')`: the state is the same list of slots (`{type, data}`), so it saves to the same column.

```php
use Odden\MailBuilder\Filament\Components\MailEditor;

MailEditor::make('slots')
    ->label('Email content')
    ->theme(['container_width' => 640]);   // optional: theme for the preview and the compiled email
```

- **No routes and no extra setup for the preview.** The field renders the preview itself, through Livewire, so it runs under the panel's own authentication and authorization. (The routes in section 8 are for use outside Filament.)
- **Publish the assets** with Filament's own command, the first time and after an update: `php artisan filament:assets`.
- **State from either shape.** A column holding Filament's keyed Builder items or a plain list both load, and the field saves a plain list. Slots of a type that is not editable yet are kept as they are.
- **It follows the form.** When the form changes the state itself, for example an action that applies a preset with `$set('slots', ...)`, the editor shows the new content.
- A disabled field is shown but cannot be edited.

## Supported Slot Types (29 Total)

| Slot Type | Key | Description |
| :--- | :--- | :--- |
| **Header & Logo** | `header` | Branding bar with logo, web-view link, and optional date |
| **Hero Banner** | `hero` | Headline, badge pill, subtext, and bulletproof CTA button |
| **Text & Content** | `body_text` | Editorial rich text with merge tags and alignments |
| **CTA Button** | `button` | Bulletproof Outlook VML action button |
| **2-Column Grid** | `two_column` | Side-by-side cards with reverse mobile stacking (`rtl`) |
| **3-Column Grid** | `three_column` | Three responsive feature cards |
| **4-Column Wall** | `four_column` | Partner logo trust wall or compact icon links |
| **Asymmetric Split** | `asymmetric_columns` | 1/3 + 2/3 editorial sidebar split |
| **Feature List** | `features` | Bulleted list with icons, bold titles, and descriptions |
| **Testimonial** | `testimonial` | Quote callout with star rating and author attribution |
| **Stat Box** | `stat_box` | Key metric numbers and percentage badges |
| **Divider Line** | `divider` | Horizontal rule or spacer with configurable height |
| **Labeled Divider** | `labeled_divider` | Horizontal line with centered text badge (e.g. "OR") |
| **Social Links** | `social_links` | Multi-channel social icon bar |
| **App Badges** | `app_badges` | Official Apple App Store and Google Play buttons |
| **Data Table** | `data_table` | Multi-column comparison table with zebra striping |
| **Image Banner** | `image_banner` | Responsive banner image with alt text and link |
| **Video Card** | `video_card` | Thumbnail with play button overlay linking to video |
| **Pricing Grid** | `pricing_grid` | Multi-tier pricing cards with featured plan highlight |
| **Rating Scale** | `rating_bar` | 1-Click NPS or CSAT survey buttons (1-10 or 1-5 scale) |
| **Countdown Urgency** | `countdown_timer` | Urgency banner with deadline and offer CTA |
| **Accordion / FAQ** | `accordion` | Stacked Q&A cards for policies and instructions |
| **Dynamic Feed** | `dynamic_feed` | Product recommendation grid with live JSON hydration |
| **Order Receipt** | `order_receipt` | Itemized invoice summary with taxes and subtotals |
| **RSS Article Feed** | `rss_feed` | Syndicated blog digest with publication dates |
| **Product Catalog** | `product_catalog` | Ecommerce cards with compare-at pricing and buy CTAs |
| **Coupon Code** | `coupon_code` | Promo voucher card with dashed border and expiry |
| **Footer & Compliance**| `footer` | CAN-SPAM / GDPR address and unsubscribe link |
| **Custom Raw HTML** | `html` | Bespoke HTML code component |

---

## Testing

```bash
composer install
composer test      # Pest, via Orchestra Testbench
composer analyse   # PHPStan level 8 with Larastan
npm run test:js    # the editor's model and history (Node's built-in test runner)
```

---

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
