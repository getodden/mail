@php
    $content = $data['content'] ?? '';
    $align = $data['align'] ?? 'left';
    $bgColor = $data['bg_color'] ?? ($theme['content_background_color'] ?? '#ffffff');
    $paddingTop = $data['padding_top'] ?? 24;
    $paddingBottom = $data['padding_bottom'] ?? 24;
@endphp

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $bgColor }};">
    <tr>
        <td style="padding: {{ $paddingTop }}px 32px {{ $paddingBottom }}px 32px; font-size: 15px; line-height: 1.65; color: {{ $theme['text_color'] ?? '#334155' }}; text-align: {{ $align }};" class="mobile-padding">
            {!! $content !!}
        </td>
    </tr>
</table>
