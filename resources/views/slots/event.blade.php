@php
    $title = (string) ($data['title'] ?? '');
    $date = (string) ($data['date'] ?? '');
    $time = (string) ($data['time'] ?? '');
    $location = (string) ($data['location'] ?? '');
    $description = (string) ($data['description'] ?? '');
    $buttonText = (string) ($data['button_text'] ?? '');
    $buttonUrl = (string) ($data['button_url'] ?? '');
    $bgColor = $data['bg_color'] ?? '#f8fafc';
    $primaryColor = $theme['primary_color'] ?? '#2563eb';
    $headingColor = $theme['heading_color'] ?? '#0f172a';
    $textColor = $theme['text_color'] ?? '#475569';
    $borderColor = $theme['border_color'] ?? '#e2e8f0';
    $fontFamily = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
    $when = trim($date.($date !== '' && $time !== '' ? ' · ' : '').$time);
@endphp

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $theme['content_background_color'] ?? '#ffffff' }};">
    <tr>
        <td style="padding: 24px 32px;" class="mobile-padding">
            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $bgColor }}; border: 1px solid {{ $borderColor }}; border-radius: 8px;">
                <tr>
                    <td style="padding: 24px; font-family: {{ $fontFamily }};" class="mobile-padding">
                        @if($when !== '')
                            <div style="margin: 0 0 8px 0; font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: {{ $primaryColor }};">
                                {{ $when }}
                            </div>
                        @endif

                        <h3 style="margin: 0 0 8px 0; font-size: 20px; font-weight: 700; line-height: 1.3; color: {{ $headingColor }};">
                            {{ $title }}
                        </h3>

                        @if($location !== '')
                            <div style="margin: 0 0 12px 0; font-size: 14px; line-height: 1.5; color: {{ $textColor }};">
                                {{ $location }}
                            </div>
                        @endif

                        @if($description !== '')
                            <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: {{ $textColor }};">
                                {{ $description }}
                            </p>
                        @endif

                        @if($buttonText !== '' && $buttonUrl !== '')
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="border-radius: 8px;" bgcolor="{{ $primaryColor }}">
                                        <!--[if mso]>
                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $buttonUrl }}" style="height:44px;v-text-anchor:middle;width:200px;" arcsize="18%" stroke="f" fillcolor="{{ $primaryColor }}">
                                        <w:anchorlock/>
                                        <center style="color:#ffffff;font-family:sans-serif;font-size:15px;font-weight:bold;">{{ $buttonText }}</center>
                                        </v:roundrect>
                                        <![endif]-->
                                        <a href="{{ $buttonUrl }}" style="background-color: {{ $primaryColor }}; border-radius: 8px; color: #ffffff; display: inline-block; font-size: 15px; font-weight: 600; line-height: 44px; text-align: center; text-decoration: none; min-width: 160px; padding: 0 28px; -webkit-text-size-adjust: none; mso-hide: all;">
                                            {{ $buttonText }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
