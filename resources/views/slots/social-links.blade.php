@php
    $links = $data['links'] ?? [];
    $align = $data['align'] ?? 'center';
    $bgColor = $data['bg_color'] ?? ($theme['content_background_color'] ?? '#ffffff');
@endphp

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $bgColor }};">
    <tr>
        <td align="{{ $align }}" style="padding: 20px 32px;" class="mobile-padding">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    @foreach($links as $link)
                        @php
                            $network = $link['network'] ?? 'Link';
                            $url = $link['url'] ?? '#';
                        @endphp
                        <td style="padding: 0 8px;">
                            <a href="{{ $url }}" style="display: inline-block; font-size: 13px; font-weight: 600; color: {{ $theme['primary_color'] ?? '#2563eb' }}; text-decoration: none; padding: 6px 12px; background-color: #f1f5f9; border-radius: 6px;">
                                {{ $network }}
                            </a>
                        </td>
                    @endforeach
                </tr>
            </table>
        </td>
    </tr>
</table>
