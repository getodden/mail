<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

final class RawHtmlSanitizer
{
    private const ELEMENTS = [
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'div', 'span', 'p', 'br', 'hr', 'a', 'img',
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

    public static function sanitize(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        [$protected, $mergeTags] = MergeTagGuard::protect($html);

        return MergeTagGuard::restore(self::sanitizer($mergeTags)->sanitize($protected), $mergeTags, quoteSafeInTags: true);
    }

    /**
     * @param  array<string, string>  $mergeTags
     */
    private static function sanitizer(array $mergeTags): HtmlSanitizer
    {
        $config = (new HtmlSanitizerConfig)
            ->defaultAction(HtmlSanitizerAction::Drop)
            ->allowLinkSchemes(SafeUrl::linkSchemes())
            ->allowMediaSchemes(self::MEDIA_SCHEMES)
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->withMaxInputLength(-1)
            ->withAttributeSanitizer(new StyleAttributeSanitizer($mergeTags));

        foreach (self::ELEMENTS as $element) {
            $config = $config->allowElement($element, self::GLOBAL_ATTRIBUTES);
        }

        foreach (self::UNWRAPPED_ELEMENTS as $element) {
            $config = $config->blockElement($element);
        }

        return new HtmlSanitizer($config
            ->allowElement('a', [...self::GLOBAL_ATTRIBUTES, 'href', 'target'])
            ->allowElement('img', [...self::GLOBAL_ATTRIBUTES, 'src'])
            ->allowElement('font', [...self::GLOBAL_ATTRIBUTES, 'color', 'face', 'size']));
    }
}
