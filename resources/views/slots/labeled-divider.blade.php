@php
    $label = $data['label'] ?? 'OR';
    $bgColor = $data['bg_color'] ?? ($theme['content_background_color'] ?? '#ffffff');
    $badgeBg = $data['badge_bg'] ?? '#f1f5f9';
    $textColor = $data['text_color'] ?? ($theme['text_color'] ?? '#64748b');
    $lineColor = $data['line_color'] ?? ($theme['border_color'] ?? '#e2e8f0');
    $paddingY = $data['padding_y'] ?? 24;
@endphp

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $bgColor }};">
    <tr>
        <td style="padding: {{ $paddingY }}px 20px;">
            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="border-bottom: 1px solid {{ $lineColor }}; line-height: 1px; font-size: 1px;">&nbsp;</td>
                    <td align="center" style="width: 1%; white-space: nowrap; padding: 0 14px;">
                        <span style="display: inline-block; background-color: {{ $badgeBg }}; color: {{ $textColor }}; font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; padding: 3px 12px; border-radius: 9999px; border: 1px solid {{ $lineColor }};">
                            {{ $label }}
                        </span>
                    </td>
                    <td style="border-bottom: 1px solid {{ $lineColor }}; line-height: 1px; font-size: 1px;">&nbsp;</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
