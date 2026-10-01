@php
    $title = $data['title'] ?? 'Welcome to '.config('mail-builder.footer.company_name', config('app.name'));
    $subtitle = $data['subtitle'] ?? '';
    $badge = $data['badge'] ?? null;
    $heroImage = $data['hero_image'] ?? null;
    $buttonText = $data['button_text'] ?? null;
    $buttonUrl = $data['button_url'] ?? '#';
    $buttonStyle = $data['button_style'] ?? 'primary';
    $bgColor = $data['bg_color'] ?? ($theme['heading_color'] ?? '#0f172a');
    $textColor = $data['text_color'] ?? '#ffffff';
    $subtextColor = $data['subtext_color'] ?? '#cbd5e1';

    $btnBg = match ($buttonStyle) {
        'success' => '#16a34a',
        'dark' => '#0f172a',
        'light' => '#ffffff',
        default => ($theme['primary_color'] ?? '#2563eb'),
    };
    $btnTextColor = match ($buttonStyle) {
        'light' => '#0f172a',
        default => '#ffffff',
    };
@endphp

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $bgColor }}; color: {{ $textColor }};">
    @if(!empty($heroImage))
        <tr>
            <td align="center" style="padding: 0;">
                <img src="{{ $heroImage }}" alt="{{ $title }}" width="600" style="display: block; width: 100%; max-width: 600px; height: auto; border: 0;">
            </td>
        </tr>
    @endif
    <tr>
        <td align="center" style="padding: 48px 32px; text-align: center;" class="mobile-padding">
            @if(!empty($badge))
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 16px;">
                    <tr>
                        <td align="center" style="background-color: rgba(255,255,255,0.15); border-radius: 9999px; padding: 4px 14px;">
                            <span style="font-size: 11px; font-weight: 700; letter-spacing: 1px; color: {{ $textColor }}; text-transform: uppercase;">
                                {{ $badge }}
                            </span>
                        </td>
                    </tr>
                </table>
            @endif

            <h1 style="margin: 0; font-size: 30px; font-weight: 800; line-height: 1.25; color: {{ $textColor }}; letter-spacing: -0.5px;">
                {{ $title }}
            </h1>

            @if(!empty($subtitle))
                <p style="margin: 16px auto 0 auto; font-size: 16px; line-height: 1.6; color: {{ $subtextColor }}; max-width: 480px;">
                    {{ $subtitle }}
                </p>
            @endif

            @if(!empty($buttonText))
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin-top: 28px;">
                    <tr>
                        <td align="center" style="border-radius: 8px;" bgcolor="{{ $btnBg }}">
                            <!--[if mso]>
                            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $buttonUrl }}" style="height:44px;v-text-anchor:middle;width:220px;" arcsize="18%" stroke="f" fillcolor="{{ $btnBg }}">
                            <w:anchorlock/>
                            <center style="color:{{ $btnTextColor }};font-family:sans-serif;font-size:15px;font-weight:bold;">{{ $buttonText }}</center>
                            </v:roundrect>
                            <![endif]-->
                            <a href="{{ $buttonUrl }}" style="background-color: {{ $btnBg }}; border: 1px solid {{ $btnBg }}; border-radius: 8px; color: {{ $btnTextColor }}; display: inline-block; font-size: 15px; font-weight: 600; line-height: 44px; text-align: center; text-decoration: none; width: auto; padding: 0 28px; -webkit-text-size-adjust: none; mso-hide: all;">
                                {{ $buttonText }}
                            </a>
                        </td>
                    </tr>
                </table>
            @endif
        </td>
    </tr>
</table>
