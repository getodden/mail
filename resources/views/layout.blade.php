@php
    $dir = $direction ?? ($theme['direction'] ?? 'ltr');
    $lang = $theme['lang'] ?? 'en';
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}" dir="{{ $dir }}" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>{{ $subject ?? '' }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        :root {
            color-scheme: light dark;
            supported-color-schemes: light dark;
        }
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; min-width: 100%; background-color: {{ $theme['background_color'] ?? '#f8fafc' }}; font-family: {{ $theme['font_family'] ?? "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif" }}; color: {{ $theme['text_color'] ?? '#334155' }}; }
        a { color: {{ $theme['primary_color'] ?? '#2563eb' }}; text-decoration: underline; }
        p { margin-top: 0; margin-bottom: 16px; line-height: 1.6; }
        h1, h2, h3, h4, h5, h6 { color: {{ $theme['heading_color'] ?? '#0f172a' }}; margin-top: 0; }
        
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                max-width: 100% !important;
            }
            .stack-column {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                direction: ltr !important;
                box-sizing: border-box !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
            .stack-column-center {
                text-align: center !important;
            }
            .mobile-padding {
                padding-left: 16px !important;
                padding-right: 16px !important;
            }
            .mobile-full-width {
                width: 100% !important;
                display: block !important;
            }
        }
    </style>
</head>
<body dir="{{ $dir }}" style="direction: {{ $dir }}; margin: 0; padding: 24px 0; background-color: {{ $theme['background_color'] ?? '#f8fafc' }}; font-family: {{ $theme['font_family'] ?? "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif" }}; color: {{ $theme['text_color'] ?? '#334155' }};">
    @if(!empty($previewText))
        <!-- Preheader preview text with non-breaking whitespace padding -->
        <div style="display: none; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all;">
            {{ $previewText }}
            &#847; &zwnj; &nbsp; &#8199; &shy; &#847; &zwnj; &nbsp; &#8199; &shy; &#847; &zwnj; &nbsp; &#8199; &shy;
            &#847; &zwnj; &nbsp; &#8199; &shy; &#847; &zwnj; &nbsp; &#8199; &shy; &#847; &zwnj; &nbsp; &#8199; &shy;
        </div>
    @endif

    <center style="width: 100%; background-color: {{ $theme['background_color'] ?? '#f8fafc' }};">
        <!--[if mso]>
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="{{ $theme['container_width'] ?? 600 }}" align="center">
        <tr>
        <td>
        <![endif]-->
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="email-container" dir="{{ $dir }}" style="direction: {{ $dir }}; max-width: {{ $theme['container_width'] ?? 600 }}px; margin: 0 auto; background-color: {{ $theme['content_background_color'] ?? '#ffffff' }}; border-radius: {{ $theme['border_radius'] ?? '8px' }}; overflow: hidden; border: 1px solid {{ $theme['border_color'] ?? '#e2e8f0' }};">
            <tr>
                <td style="padding: 0;">
                    {!! $content !!}
                </td>
            </tr>
        </table>
        <!--[if mso]>
        </td>
        </tr>
        </table>
        <![endif]-->
    </center>
</body>
</html>
