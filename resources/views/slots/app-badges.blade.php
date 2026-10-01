@php
    $heading = $data['heading'] ?? '';
    $subtitle = $data['subtitle'] ?? '';
    $appStoreUrl = $data['app_store_url'] ?? '#';
    $googlePlayUrl = $data['google_play_url'] ?? '#';
    $align = $data['align'] ?? 'center';
    $bgColor = $data['bg_color'] ?? ($theme['content_background_color'] ?? '#ffffff');
@endphp

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $bgColor }};">
    <tr>
        <td align="{{ $align }}" style="padding: 24px 20px; text-align: {{ $align }};" class="mobile-padding">
            @if(!empty($heading))
                <h3 style="margin: 0 0 6px 0; font-size: 18px; font-weight: 700; color: {{ $theme['heading_color'] ?? '#0f172a' }};">
                    {{ $heading }}
                </h3>
            @endif

            @if(!empty($subtitle))
                <p style="margin: 0 0 16px 0; font-size: 14px; color: {{ $theme['text_color'] ?? '#64748b' }};">
                    {{ $subtitle }}
                </p>
            @endif

            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="display: inline-block;">
                <tr>
                    @if(!empty($appStoreUrl))
                        <td align="center" style="padding: 6px 8px;">
                            <a href="{{ $appStoreUrl }}" target="_blank" style="display: inline-block; text-decoration: none;">
                                <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="background-color: #000000; border-radius: 7px; padding: 6px 14px; min-width: 140px;">
                                    <tr>
                                        <td style="font-size: 20px; color: #ffffff; padding-right: 8px; vertical-align: middle;">
                                            
                                        </td>
                                        <td align="left" style="vertical-align: middle;">
                                            <div style="font-size: 9px; line-height: 10px; color: #cbd5e1; text-transform: uppercase; font-weight: 500;">Download on the</div>
                                            <div style="font-size: 14px; line-height: 16px; color: #ffffff; font-weight: 700;">App Store</div>
                                        </td>
                                    </tr>
                                </table>
                            </a>
                        </td>
                    @endif

                    @if(!empty($googlePlayUrl))
                        <td align="center" style="padding: 6px 8px;">
                            <a href="{{ $googlePlayUrl }}" target="_blank" style="display: inline-block; text-decoration: none;">
                                <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="background-color: #000000; border-radius: 7px; padding: 6px 14px; min-width: 140px;">
                                    <tr>
                                        <td style="font-size: 18px; color: #34d399; padding-right: 8px; vertical-align: middle;">
                                            ▶
                                        </td>
                                        <td align="left" style="vertical-align: middle;">
                                            <div style="font-size: 9px; line-height: 10px; color: #cbd5e1; text-transform: uppercase; font-weight: 500;">GET IT ON</div>
                                            <div style="font-size: 14px; line-height: 16px; color: #ffffff; font-weight: 700;">Google Play</div>
                                        </td>
                                    </tr>
                                </table>
                            </a>
                        </td>
                    @endif
                </tr>
            </table>
        </td>
    </tr>
</table>
