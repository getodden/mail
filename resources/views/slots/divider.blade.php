@php
    $height = (int) ($data['height'] ?? 24);
    $showLine = (bool) ($data['show_line'] ?? true);
    $lineColor = $data['line_color'] ?? ($theme['border_color'] ?? '#e2e8f0');
    $lineStyle = $data['line_style'] ?? 'solid';
    $bgColor = $data['bg_color'] ?? ($theme['content_background_color'] ?? '#ffffff');
@endphp

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $bgColor }};">
    <tr>
        <td style="padding: {{ (int) ($height / 2) }}px 32px;" class="mobile-padding">
            @if($showLine)
                <div style="border-top: 1px {{ $lineStyle }} {{ $lineColor }}; height: 1px; line-height: 1px; font-size: 1px;">&nbsp;</div>
            @else
                <div style="height: {{ $height }}px; line-height: {{ $height }}px; font-size: 1px;">&nbsp;</div>
            @endif
        </td>
    </tr>
</table>
