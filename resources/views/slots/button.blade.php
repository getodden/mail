@php
    use Odden\MailBuilder\Enums\ButtonStyle;

    $text = $data['button_text'] ?? $data['text'] ?? 'Click Here';
    $url = $data['button_url'] ?? $data['url'] ?? '#';
    $align = $data['align'] ?? 'center';
    $styleKey = $data['style'] ?? 'primary';
    $styleEnum = ButtonStyle::tryFrom($styleKey) ?? ButtonStyle::Primary;

    $bgColor = $data['bg_color'] ?? ($styleKey === 'primary' ? ($theme['primary_color'] ?? '#2563eb') : $styleEnum->backgroundColor());
    $textColor = $data['text_color'] ?? $styleEnum->textColor();
    $borderColor = $data['border_color'] ?? $styleEnum->borderColor();
    $borderRadius = $data['border_radius'] ?? '8px';
    $paddingY = $data['padding_y'] ?? 24;
@endphp

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $theme['content_background_color'] ?? '#ffffff' }};">
    <tr>
        <td align="{{ $align }}" style="padding: {{ $paddingY }}px 32px;" class="mobile-padding">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center" style="border-radius: {{ $borderRadius }};" bgcolor="{{ $bgColor }}">
                        <!--[if mso]>
                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $url }}" style="height:44px;v-text-anchor:middle;width:220px;" arcsize="18%" stroke="{{ $styleKey === 'outline' ? 't' : 'f' }}" strokecolor="{{ $borderColor }}" fillcolor="{{ $bgColor }}">
                        <w:anchorlock/>
                        <center style="color:{{ $textColor }};font-family:sans-serif;font-size:15px;font-weight:bold;">{{ $text }}</center>
                        </v:roundrect>
                        <![endif]-->
                        <a href="{{ $url }}" style="background-color: {{ $bgColor }}; border: 1px solid {{ $borderColor }}; border-radius: {{ $borderRadius }}; color: {{ $textColor }}; display: inline-block; font-size: 15px; font-weight: 600; line-height: 44px; text-align: center; text-decoration: none; width: auto; min-width: 160px; padding: 0 28px; -webkit-text-size-adjust: none; mso-hide: all;">
                            {{ $text }}
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
