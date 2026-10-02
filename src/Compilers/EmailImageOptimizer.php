<?php

declare(strict_types=1);

namespace Odden\MailBuilder\Compilers;

class EmailImageOptimizer
{
    /**
     * Optimize image tags within rendered email HTML for Outlook compatibility and Retina sharpness.
     */
    public static function optimize(string $html): string
    {
        return preg_replace_callback('/<img\b([^>]*)>/i', function ($matches) {
            $attrs = $matches[1];

            // 1. Ensure border="0" is present
            if (! preg_match('/\bborder\s*=/i', $attrs)) {
                $attrs .= ' border="0"';
            }

            // 2. Parse inline style
            $style = '';
            if (preg_match('/\bstyle\s*=\s*["\']([^"\']*)["\']/i', $attrs, $styleMatch)) {
                $style = trim($styleMatch[1]);
            }

            // Ensure baseline Outlook styles
            $requiredStyles = [
                '-ms-interpolation-mode' => 'bicubic',
                'outline' => 'none',
                'text-decoration' => 'none',
            ];

            // Add display: block if not already styled as display
            if (! preg_match('/\bdisplay\s*:/i', $style)) {
                $requiredStyles['display'] = 'block';
            }

            foreach ($requiredStyles as $prop => $val) {
                if (! preg_match('/\b'.preg_quote($prop, '/').'\s*:/i', $style)) {
                    $style = rtrim($style, '; ').(empty($style) ? '' : '; ')."{$prop}: {$val};";
                }
            }

            // 3. Extract width & height from style if missing from HTML attributes
            $hasWidthAttr = (bool) preg_match('/\bwidth\s*=\s*["\']?(\d+)%?["\']?/i', $attrs, $widthAttrMatch);
            $hasHeightAttr = (bool) preg_match('/\bheight\s*=\s*["\']?(\d+)%?["\']?/i', $attrs, $heightAttrMatch);

            $styleWidth = null;
            if (preg_match('/\bwidth\s*:\s*(\d+)px/i', $style, $wMatch)) {
                $styleWidth = (int) $wMatch[1];
            }

            $styleHeight = null;
            if (preg_match('/\bheight\s*:\s*(\d+)px/i', $style, $hMatch)) {
                $styleHeight = (int) $hMatch[1];
            }

            // 4. Check for Retina indicator
            $isRetina = (bool) preg_match('/\bdata-retina\s*=\s*["\']?(true|2x|yes|1)["\']?/i', $attrs);

            if ($isRetina && $hasWidthAttr) {
                $halfWidth = (int) round(((int) $widthAttrMatch[1]) / 2);
                if ($halfWidth > 0) {
                    $attrs = preg_replace('/\bwidth\s*=\s*["\']?\d+%?["\']?/i', 'width="'.$halfWidth.'"', $attrs) ?? $attrs;
                }
                if ($hasHeightAttr) {
                    $halfHeight = (int) round(((int) $heightAttrMatch[1]) / 2);
                    if ($halfHeight > 0) {
                        $attrs = preg_replace('/\bheight\s*=\s*["\']?\d+%?["\']?/i', 'height="'.$halfHeight.'"', $attrs) ?? $attrs;
                    }
                }
            } else {
                if (! $hasWidthAttr && $styleWidth !== null) {
                    $attrs .= ' width="'.$styleWidth.'"';
                }
                if (! $hasHeightAttr && $styleHeight !== null) {
                    $attrs .= ' height="'.$styleHeight.'"';
                }
            }

            // 5. Ensure accessibility alt / role
            if (! preg_match('/\balt\s*=/i', $attrs)) {
                $attrs .= ' alt="" role="presentation"';
            } elseif (preg_match('/\balt\s*=\s*["\']\s*["\']/i', $attrs) && ! preg_match('/\brole\s*=/i', $attrs)) {
                $attrs .= ' role="presentation"';
            }

            // Replace or update style attribute
            if (preg_match('/\bstyle\s*=\s*["\'][^"\']*["\']/i', $attrs)) {
                $attrs = preg_replace('/\bstyle\s*=\s*["\'][^"\']*["\']/i', 'style="'.htmlspecialchars($style, ENT_QUOTES).'"', $attrs) ?? $attrs;
            } else {
                $attrs .= ' style="'.htmlspecialchars($style, ENT_QUOTES).'"';
            }

            return '<img '.trim($attrs).'>';
        }, $html) ?? $html;
    }
}
