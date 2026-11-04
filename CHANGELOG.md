# Changelog

All notable changes to `getodden/mail` are recorded here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - 2026-11-04

Version 3.0.0 is a major release because it changes what the compiler keeps from raw HTML and rich text, rejects some merge tag structures that 2.x rendered, and changes public types. If you only compile slots you built yourself, most emails render the same. If tenant users paste HTML, write `{% if %}` blocks in text fields, or you subclass the compiler, read [Upgrade notes](#upgrade-notes) first.

### Added

- Open slot registry. Implement `Odden\MailBuilder\Schema\CustomSlotType` (`key()`, `label()`, `shortLabel()`, `description()`, `group()`, `view()`, `schema()`) and register it with the `Odden\MailBuilder\Schema\SlotRegistry` singleton. A registered slot compiles, exports, validates, previews, appears in `SlotSchemaRegistry::toArray()`, and gets a block in the Filament `EmailSlotBuilder`.
- `SlotRegistry` methods `register()`, `get()`, `has()`, `keys()`, `all()` and `flush()`. `register()` throws `InvalidArgumentException` for a key that does not match `/^[a-z][a-z0-9_]*$/`, that collides with a built-in slot type, that is already registered, or whose schema was built for a different key.
- `Odden\MailBuilder\Schema\ProvidesPlainText`. A custom slot that implements `plainText(array $data): string` controls its own plain-text output. Without it, the plain-text fallback extracts text from the slot's compiled HTML.
- `EmailSlot::$type` and `EmailDocument::append()` accept `SlotType|CustomSlotType`. `EmailSlot::fromArray()` resolves a custom key through the registry. New helpers `EmailSlot::key()` and `EmailSlot::viewName()` return the slot's key and view for either kind of type.
- `SlotSchema::$type` is `SlotType|CustomSlotType`, with a new `SlotSchema::key()` helper.
- `SlotSchemaRegistry::forKey(string $key): ?SlotSchema` looks up the schema for a built-in or custom key.
- `SlotField` takes a `rawHtml` argument. A custom slot that prints a field unescaped declares it with `FieldType::RichText` or `rawHtml: true` so the field goes through the sanitizer.
- Built-in `event` slot (`SlotType::Event`), an event card with title, date, time, location, description, and a registration button.
- Built-in `gallery` slot (`SlotType::Gallery`), a two or three column image grid with captions and links that stacks on mobile and renders at most 12 images. With these two, the package has 31 built-in slot types.
- Footer slot `warning` field, a textarea for a required notice. It renders under the copyright line and appears in the plain-text fallback.
- `Odden\MailBuilder\Support\SafeUrl` checks URLs against a scheme allowlist. `SafeUrl::for($url, $forImage)` returns the URL or `#` (an empty string for images), `SafeUrl::linkSchemes()` returns the configured list, and `SafeUrl::decodeEntities()` decodes entities the way the scrubber does.
- `Odden\MailBuilder\Support\SafeColor` validates color values. `SafeColor::for()` checks one value and `SafeColor::theme()` checks every theme key that ends in `_color`.
- `Odden\MailBuilder\Support\HtmlUrlScrubber::scrub()` rewrites unsafe URLs in the final HTML.
- `Odden\MailBuilder\Support\RawHtmlSanitizer::sanitize($html, $profile)`, an allowlist sanitizer built on `symfony/html-sanitizer`, with two profiles. `RawHtmlSanitizer::PROFILE_HTML` (`'html'`, the default) allows table elements. `RawHtmlSanitizer::PROFILE_RICH` (`'rich'`) unwraps them and keeps their text.
- `Odden\MailBuilder\Support\MergeTagGuard` keeps `{{ }}` and `{% %}` tags intact while HTML is parsed or inlined.
- `Odden\MailBuilder\Support\BlockMergeTags`. `validate($text)` returns the list of problem codes (`unbalanced_opener`, `unbalanced_closer`, `mismatched_closer`, `nested_block`, `unknown_block_tag`), `assertValid()` throws on them, and `strip()` removes block tags with the content they wrap.
- `Odden\MailBuilder\Support\SlotDataSanitizer` cleans a slot's data from its schema, for built-in and custom slots.
- `Odden\MailBuilder\Support\StyleAttributeSanitizer` filters `style` attributes inside the raw sanitizer.
- `Odden\MailBuilder\Exceptions\UnscrubbableHtmlException` (a `RuntimeException`). `HtmlUrlScrubber::scrub()` throws it when the regex engine fails, instead of returning unscrubbed HTML.
- `Odden\MailBuilder\Exceptions\RawHtmlTooLargeException` (an `InvalidArgumentException`). A raw HTML or rich text field over `raw_html_max_bytes` throws it before any parsing.
- `Odden\MailBuilder\Exceptions\DocumentTooLargeException` (an `InvalidArgumentException`). `EmailSlotCompiler::compileDocument()` throws it when the slots of one email pass `max_document_bytes`.
- `Odden\MailBuilder\Exceptions\InvalidMergeTagStructureException` (an `InvalidArgumentException`) with a `field` property, a `problems` list, and `descriptions()` for plain-English messages.
- Config key `url_schemes`, the link schemes that survive sanitizing. Default: `http`, `https`, `mailto`, `tel`, `sms`, `webcal`, `webcals`, `whatsapp`, `skype`, `geo`, `itms`, `itms-apps`.
- Config key `raw_html_max_bytes`, the largest one raw HTML or rich text field may be. Default: `100000`.
- Config key `max_document_bytes`, the largest the slots of one email may add up to. Default: `1500000`.
- Composer dependency `symfony/html-sanitizer` at `^7.2 || ^8.0`.
- `allowRaw` argument (default `false`) on `MailBuilder::interpolate()`, `MergeTagRegistry::interpolate()` and `MergeTagInterpolator::interpolate()`.
- `fetch_feeds` option (default `true`) on `EmailSlotCompiler::compileDocument()`.
- `$options` parameter (third, default `[]`) on `EmailSlotCompiler::compileSlot()`.
- Editor preview validation errors for oversized fields (on `slots.{index}.data`), oversized documents (on `slots`), and invalid block merge tags (on `slots.{index}.data.{field}`, `subject` or `preview_text`).
- A README "Security" section that documents the sanitizer rules, and a README "Upgrading from 2.x" pointer to this file.

### Changed

- The raw `html` slot and every rich text field (such as `body_text`) now pass through `RawHtmlSanitizer`. The sanitizer writes canonical HTML, so quoting, entities, and `<tbody>` can differ from what the author pasted. See [Security](#security) for what it drops.
- `EmailSlot::$type` is typed `SlotType|CustomSlotType`. Code that reads `$slot->type->value` or calls `$slot->type->viewName()` must use `$slot->key()` and `$slot->viewName()`.
- `SlotType` has two new cases, `Event` and `Gallery`. A `match` over every `SlotType` case needs two new arms.
- `SlotSchema::$type` is `SlotType|CustomSlotType`. Use `SlotSchema::key()` instead of `$schema->type->value`.
- `SlotSchemaRegistry::toArray()` includes registered custom slots in `slots` and `labels`.
- Footer `unsubscribe_url` is now a `url` field instead of a `text` field. An unsupported scheme becomes `#`, and a merge tag such as `{{unsubscribe_url}}` passes unchanged.
- The `rating_bar` slot's `base_url` is now a `url` field instead of a `text` field.
- Block merge tags in text, textarea, tags, and rich text fields (nested `items` fields included), and in the email's subject and preview text, must be balanced and not nested. A block is `if`, `for`, `unless`, `each`, or `with`, in `{% %}` or `{{#...}}` form, with an optional `else`. Any other `{% %}` tag is rejected. The compiler throws `InvalidMergeTagStructureException` and renders nothing.
- `{% if %}` blocks in text now evaluate with a stack and fail closed. A block that never closes hides everything to the end of the text, a block with two `{% else %}` tags is hidden, a stray `{% else %}` or `{% endif %}` is dropped, a condition that is not a plain context key is false, and tags match without regard to case.
- `{{ tag | raw }}` is honored only for a template string the host app wrote. `TemplateMailable` ignores it for an `EmailDocument` or slot arrays, and `MailBuilder::interpolate()` and `MergeTagInterpolator::interpolate()` ignore it unless you pass `allowRaw: true`.
- `EmailSlotCompiler::compileDocument()` with `interpolate: true` now escapes interpolated values. Before, it did not.
- `EmailSlotCompiler::compileDocument()` runs `HtmlUrlScrubber::scrub()` on its output, after merge tags, CSS inlining, and image optimization.
- `TemplateMailable` runs `HtmlUrlScrubber::scrub()` again after it interpolates merge tags, for slot templates and for HTML string templates.
- `AmpEmailCompiler` sanitizes slot data, passes the general slot's `content` through the `rich` profile, validates theme colors, and scrubs its output.
- The CSS inliner no longer sees merge tags. `EmailSlotCompiler` swaps each `{{ }}` and `{% %}` tag for a token before `CssToInlineStyles` runs and restores it after, so DOMDocument cannot rewrite a tag inside an attribute or a style.
- `PlainTextExtractor` and `TemplatePackageExporter` read the same sanitized slot data as the HTML. The plain-text fallback prints a link only when it passes the URL rules, and prints only the link text otherwise.
- The exporter escapes the subject, font family, and slot type it writes into MJML, checks `button_url` and `url` with `SafeUrl`, checks theme colors with `SafeColor`, runs `body_text` through the `rich` profile, and scrubs the ZIP's `index.html`.
- `CidImageEmbedder` blanks the `src` of an SVG or any `data:` image outside `png`, `jpeg`, `jpg`, `gif`, `webp`, `avif`, and `bmp`, instead of attaching it.
- Slot data is sanitized from the slot's schema before every view renders. Invalid color fields are dropped so the view default applies. A number field is cast to `int` or `float`, and a non-numeric value is dropped. A select value that is not one of the field's options is dropped. A list (`items` or `tags`) field that is not an array is dropped.
- Theme keys that end in `_color` are validated. An invalid value is removed so the default applies.
- `EmailSlotCompiler::compileSlot()` gained a third parameter, `array $options = []`.
- `EmailSlotCompiler` has two new protected methods, `inlineCss()` and `maxDocumentBytes()`.
- The Gmail action JSON-LD hex-encodes `<`, `>`, `&`, `'`, and `"`.
- The `video_card` slot percent-encodes quotes, parentheses, backslashes, and whitespace in the thumbnail URL it puts in CSS.
- `SchemaBlockFactory` falls back to the `Squares2x2` icon when a custom slot names a Heroicon that does not exist, instead of throwing.
- `.github/workflows/tests.yml` runs only by hand (`workflow_dispatch`). Run the tests locally.
- README: the slot count is 31, the audit example uses `hasBlockingIssues()`, `$audit->checks` and `$audit->score`, and the editor image is an animation (`docs/editor.gif`, excluded from the installed package).

### Security

- Raw HTML is sanitized with an allowlist instead of being printed. Both profiles drop `script`, `style`, `svg`, `math`, `iframe`, `object`, `embed`, `meta`, `link`, `base`, `title`, `textarea`, and `select` together with their content, drop event handler attributes, `srcdoc`, `xlink:href`, `id`, and `name`, and drop HTML comments.
- Links keep only schemes from `url_schemes`. Images keep only `http`, `https`, `cid`, a relative path, or a base64 `data:` image of type `png`, `jpeg`, `jpg`, `gif`, or `webp`. SVG never passes.
- `style` attributes lose every declaration that contains `expression(`, `javascript:`, `behavior`, `-moz-binding`, `@import`, `image-set(` and similar image functions, a backslash escape, or a comment. A `url()` survives only in `background` and `background-image`, and only as a single `http` or `https` literal.
- `SafeUrl` allows only the schemes in `url_schemes`. `javascript:`, `vbscript:`, and `data:text/html` links become `#`. Relative paths, fragments, protocol-relative URLs, merge tags, and a host with a port (`example.com:8080/x`) pass unchanged. `SafeUrl` falls back to the default list when no container is bound.
- `HtmlUrlScrubber` rewrites unsafe `href`, `xlink:href`, `src`, `action`, `formaction`, `background`, `poster`, and `data` attributes in the final HTML, quoted or not, inside conditional comments and VML tags too. It drops `srcset` and every `on*` attribute, and it fails closed with `UnscrubbableHtmlException`.
- `SafeColor` accepts only hex colors, `rgb()`, `rgba()`, `hsl()`, `hsla()`, and plain color keywords. This stops a color value from injecting more CSS declarations.
- Merge tags are protected from rewriting by the HTML parser and the CSS inliner. Inside a tag's markup, a `{{ }}` tag survives only when it matches a strict `{{ path | filter }}` shape, so a tag cannot close an attribute or open another tag.
- Conditionals fail closed. Malformed `{% if %}` structure hides content and never shows it to the wrong audience.
- Block merge tags (`{% %}` and `{{#...}}`) are removed from the `html` slot together with the content they wrap, because an HTML parser moves text out of tables and block evaluation deletes text across tags.
- The editor preview does not fetch `feed_url` feeds. The server no longer makes a request for content a browser sent.
- The raw HTML size cap and the document size cap bound the memory a hostile document can use.

### Removed

- Markup that the raw `html` slot and rich text fields used to print as written. The sanitizer removes `<style>` blocks, `<script>`, forms and inputs, iframes, SVG, MSO conditional comments (`<!--[if mso]>`), VML (`<v:roundrect>`, `<o:p>`), web font `<link>` tags, `id` and `name` attributes, and every `url()` in CSS except an `http` or `https` background.
- Table elements in rich text fields. The `rich` profile unwraps `table`, `thead`, `tbody`, `tfoot`, `tr`, `td`, and `th` and keeps their text.
- Block merge tags in the `html` slot.
- Honoring `{{ tag | raw }}` for document and slot content.
- Unbalanced or nested block merge tags in text fields, and any `{% %}` tag that is not a block tag. These are now rejected.
- Unsafe link schemes. Such links become `#`.
- Remote feed fetches from the editor preview.
- Automatic CI runs on push and pull request.

### Upgrade notes

Work through this checklist for each application that uses `getodden/mail` 2.x.

1. **Confirm the platform.** Requirements are unchanged. PHP `^8.3`, Laravel `^12.0 || ^13.0`, and the `ext-dom` and `ext-libxml` extensions stay the same.
2. **Update the package.** Run `composer require getodden/mail:^3.0`. Composer also installs `symfony/html-sanitizer` (`^7.2 || ^8.0`). If your application pins an older `symfony/html-sanitizer` or `symfony/*` set, resolve that conflict.
3. **Review config.** Defaults merge in without republishing. If you published `config/mail-builder.php`, add `url_schemes`, `raw_html_max_bytes` (`100000`), and `max_document_bytes` (`1500000`). Add your own app link schemes (such as `myapp`) to `url_schemes`. Raise a limit only if you must. Large emails are better built from slots than pasted into one field.
4. **Find tenant content that the sanitizer changes.** Compile a sample of stored emails with 2.x and 3.x and compare. Look first at emails that use the `html` slot or `body_text`. Anything that relied on style blocks, MSO conditional comments, VML, web font links, forms, SVG, `id` or `name` attributes, or non-`http(s)` CSS `url()` loses that markup. Move Outlook-specific layout into built-in slots, and inline CSS before you paste it.
5. **Remove block merge tags from `html` slots.** The slot drops them with the content they wrap. Use the slot's visibility rules for audience targeting, or the `order_receipt` slot for lists of items.
6. **Fix block merge tags in text fields.** A text, textarea, tags, or rich text field, the subject, and the preview text must have balanced `{% if %}`, `{% for %}`, `{% unless %}`, `{% each %}`, or `{% with %}` blocks that are not nested, and no other `{% %}` tag. Search stored content for `{%` and `{{#`. Use `BlockMergeTags::validate($text)` in your own forms to return the problem codes.
7. **Check `raw` usage.** If a document or slot array relied on `{{ tag | raw }}`, the value is now escaped. Pass an HTML string to `TemplateMailable` for a template you wrote and trust, or build the markup into a slot. If you call `MailBuilder::interpolate()` or `MergeTagInterpolator::interpolate()` and rely on `raw`, pass `allowRaw: true`, and never do that for tenant-authored HTML. If you call `compileDocument(..., ['interpolate' => true])`, expect escaped values.
8. **Review footers and rating bars.** A footer `unsubscribe_url` or rating bar `base_url` with an unsupported scheme now becomes `#`. A merge tag still passes.
9. **Update code that reads slot types.** Replace `$slot->type->value` with `$slot->key()` and `$slot->type->viewName()` with `$slot->viewName()`. Replace `$schema->type->value` with `$schema->key()`. If you `match` over `SlotType`, add arms for `SlotType::Event` and `SlotType::Gallery`. Handle `CustomSlotType` anywhere you type-hint `SlotType` for an `EmailSlot` type.
10. **Update subclasses.** If you extend `EmailSlotCompiler`, change any `compileSlot()` override to `compileSlot(EmailSlot $slot, array $theme = [], array $options = []): string`, and rename any of your own methods called `inlineCss` or `maxDocumentBytes`. If you override `interpolate()` on `MergeTagRegistry` or `MergeTagInterpolator`, add the `bool $allowRaw = false` parameter.
11. **Register custom slots, if you have them.** Implement `Odden\MailBuilder\Schema\CustomSlotType` and register an instance with `app(SlotRegistry::class)->register(new YourSlot)` in a service provider's `boot()` method. The key must match `/^[a-z][a-z0-9_]*$/` and must not collide with a built-in key. Build the schema with `new SlotSchema($this, ...)` so its key matches. Declare any field your view prints unescaped as `FieldType::RichText` or `rawHtml: true`. Implement `ProvidesPlainText` if you want to control the plain-text output.
12. **Handle the new exceptions where you compile, preview, or send.**
    - `InvalidMergeTagStructureException`, `RawHtmlTooLargeException`, and `DocumentTooLargeException` extend `InvalidArgumentException`. Catch them where you accept tenant input, show `descriptions()` or `getMessage()` to the author, and do not send the email. `InvalidMergeTagStructureException::$field` names the offending field.
    - `UnscrubbableHtmlException` extends `RuntimeException`. Treat it as a failed send and do not send the email unscrubbed. Call `HtmlUrlScrubber::scrub()` yourself on any HTML you interpolate outside `TemplateMailable`.
13. **Check the footer and gallery.** Add the footer `warning` text if you need a required notice. The new `event` and `gallery` slots are available in the Filament builder and the drag-and-drop editor with no extra setup.
14. **Check the preview iframe and click tracking.** Use `sandbox` without `allow-scripts` on any iframe that shows compiled HTML. Validate redirect targets at your click-tracking endpoint.
15. **Rerun the tests.** Rerun your own tests for emails that use the `html` slot, `body_text`, conditional blocks, `raw` tags, and the plain-text fallback. In this package's own suite, run `composer test`, `php -d pcre.jit=0 vendor/bin/pest`, `composer analyse`, `composer lint`, and `npm run test:js`.
16. **Check the PHP 8.3 sanitizer path.** On PHP 8.3, `symfony/html-sanitizer` uses the Masterminds HTML5 parser instead of PHP 8.4's native parser. The package's test suite runs on one PHP version, so test your own sanitized content on the PHP version you deploy.

[3.0.0]: https://github.com/getodden/mail/compare/v2.5.3...v3.0.0
