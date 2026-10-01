@php
    $brandName = $data['brand_name'] ?? config('mail-builder.footer.company_name', config('app.name'));
    $tagline = $data['tagline'] ?? null;
    $logoUrl = $data['logo_url'] ?? null;
    $logoHeight = $data['logo_height'] ?? '32';
    $showDate = (bool) ($data['show_date'] ?? false);
    $webViewUrl = $data['web_view_url'] ?? null;
    $bgColor = $data['bg_color'] ?? '#ffffff';
    $textColor = $data['text_color'] ?? ($theme['text_color'] ?? '#64748b');
@endphp

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $bgColor }}; border-bottom: 1px solid {{ $theme['border_color'] ?? '#e2e8f0' }};">
    <tr>
        <td style="padding: 20px 32px;" class="mobile-padding">
            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="left" valign="middle">
                        @if(!empty($logoUrl))
                            <img src="{{ $logoUrl }}" alt="{{ $brandName }}" height="{{ $logoHeight }}" style="display: block; border: 0; outline: none; text-decoration: none; height: {{ $logoHeight }}px; max-width: 100%;">
                        @else
                            <span style="font-size: 20px; font-weight: 800; letter-spacing: -0.5px; color: {{ $theme['heading_color'] ?? '#0f172a' }};">
                                {{ $brandName }}
                            </span>
                        @endif
                        @if(!empty($tagline))
                            <span style="display: block; font-size: 12px; color: {{ $textColor }}; margin-top: 2px;">
                                {{ $tagline }}
                            </span>
                        @endif
                    </td>
                    <td align="right" valign="middle" style="font-size: 12px; color: {{ $textColor }};">
                        @if($showDate)
                            <span>{{ date('M j, Y') }}</span>
                        @endif
                        @if(!empty($webViewUrl))
                            <div style="margin-top: 4px;">
                                <a href="{{ $webViewUrl }}" style="color: {{ $theme['primary_color'] ?? '#2563eb' }}; text-decoration: underline; font-size: 12px;">View in browser</a>
                            </div>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
