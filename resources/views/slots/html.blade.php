@php
    $rawHtml = $data['html'] ?? '';
    $bgColor = $data['bg_color'] ?? ($theme['content_background_color'] ?? '#ffffff');
@endphp

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $bgColor }};">
    <tr>
        <td style="padding: 0;">
            {!! $rawHtml !!}
        </td>
    </tr>
</table>
