<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Support;

use InvalidArgumentException;
use Odden\MailBuilder\Exceptions\RawHtmlTooLargeException;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

final class RawHtmlSanitizer
{
    private const TABLE_ELEMENTS = ['table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th'];

    private const TABLE_TAG = '/<\/?(?:table|thead|tbody|tfoot|tr|td|th|caption|colgroup|col)(?=[\s\/>])[^<>]*+>/i';

    private const ELEMENTS = [
        'div', 'span', 'p', 'br', 'hr', 'a', 'img',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'strong', 'b', 'em', 'i', 'u', 's', 'small',
        'sup', 'sub', 'blockquote', 'center', 'font', 'pre', 'code',
    ];

    private const UNWRAPPED_ELEMENTS = [
        'form', 'label', 'button', 'fieldset', 'legend', 'section', 'article', 'header', 'footer', 'main', 'nav', 'aside',
        'figure', 'figcaption', 'details', 'summary', 'address', 'dl', 'dt', 'dd', 'caption', 'colgroup', 'col', 'abbr',
        'cite', 'del', 'ins', 'mark', 'q', 'big', 'tt', 'strike', 'dfn', 'kbd', 'samp', 'var', 'time', 'picture', 'html', 'body',
    ];

    private const GLOBAL_ATTRIBUTES = ['style', 'class', 'width', 'height', 'align', 'valign', 'bgcolor', 'border', 'cellpadding', 'cellspacing', 'colspan', 'rowspan', 'role', 'dir', 'lang', 'alt', 'title'];

    private const MEDIA_SCHEMES = ['http', 'https', 'cid'];

    public const DEFAULT_MAX_BYTES = 100000;

    public const PROFILE_HTML = 'html';

    public const PROFILE_RICH = 'rich';

    public static function sanitize(string $html, string $profile = self::PROFILE_HTML): string
    {
        $rich = match ($profile) {
            self::PROFILE_RICH => true,
            self::PROFILE_HTML => false,
            default => throw new InvalidArgumentException("Unknown sanitizer profile [{$profile}]."),
        };

        if (trim($html) === '') {
            return '';
        }

        $limit = self::maxBytes();

        if (strlen($html) > $limit) {
            throw RawHtmlTooLargeException::exceeding(strlen($html), $limit);
        }

        $html = $rich ? (string) preg_replace(self::TABLE_TAG, '', $html) : BlockMergeTags::strip($html);

        [$protected, $mergeTags] = MergeTagGuard::protect($html);
        $sanitized = MergeTagGuard::restoreInMarkup(self::sanitizer($mergeTags, $rich, strlen($protected))->sanitize($protected), $mergeTags);

        return $rich ? $sanitized : BlockMergeTags::strip($sanitized);
    }

    public static function maxBytes(): int
    {
        $configured = PackageConfig::get('mail-builder.raw_html_max_bytes', self::DEFAULT_MAX_BYTES);

        return is_numeric($configured) ? max(1, (int) $configured) : self::DEFAULT_MAX_BYTES;
    }

    /**
     * @param  array<string, string>  $mergeTags
     */
    private static function sanitizer(array $mergeTags, bool $rich, int $inputLength): HtmlSanitizer
    {
        $config = (new HtmlSanitizerConfig)
            ->defaultAction(HtmlSanitizerAction::Drop)
            ->allowLinkSchemes(SafeUrl::linkSchemes())
            ->allowMediaSchemes(self::MEDIA_SCHEMES)
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->withMaxInputLength(max(self::maxBytes(), $inputLength))
            ->withAttributeSanitizer(new StyleAttributeSanitizer($mergeTags));

        foreach ([...self::ELEMENTS, ...($rich ? [] : self::TABLE_ELEMENTS)] as $element) {
            $config = $config->allowElement($element, self::GLOBAL_ATTRIBUTES);
        }

        foreach ([...self::UNWRAPPED_ELEMENTS, ...($rich ? self::TABLE_ELEMENTS : [])] as $element) {
            $config = $config->blockElement($element);
        }

        return new HtmlSanitizer($config
            ->allowElement('a', [...self::GLOBAL_ATTRIBUTES, 'href', 'target'])
            ->allowElement('img', [...self::GLOBAL_ATTRIBUTES, 'src'])
            ->allowElement('font', [...self::GLOBAL_ATTRIBUTES, 'color', 'face', 'size']));
    }
}
