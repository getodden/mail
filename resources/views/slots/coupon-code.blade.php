@php
    $headline = $data['headline'] ?? 'Special Discount Voucher';
    $description = $data['description'] ?? 'Use this exclusive promo code at checkout to claim your savings.';
    $code = $data['code'] ?? 'SAVE20';
    $discountBadge = $data['discount_badge'] ?? '20% OFF';
    $expiresAt = $data['expires_at'] ?? null;
    $buttonText = $data['button_text'] ?? 'Claim Offer';
    $buttonUrl = $data['button_url'] ?? '#';
    $cardBg = $data['card_background'] ?? '#f8fafc';
    $borderColor = $data['border_color'] ?? '#94a3b8';
    $codeBg = $data['code_background'] ?? '#ffffff';
    $primaryColor = $theme['primary_color'] ?? '#2563eb';
    $headingColor = $theme['heading_color'] ?? '#0f172a';
    $textColor = $theme['text_color'] ?? '#334155';
@endphp

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: {{ $data['background_color'] ?? 'transparent' }};">
    <tr>
        <td align="center" style="padding: 24px 20px;" class="mobile-padding">
            <!--[if mso]>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="540" align="center">
            <tr>
            <td>
            <![endif]-->
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 540px; margin: 0 auto; background-color: {{ $cardBg }}; border: 2px dashed {{ $borderColor }}; border-radius: 12px; overflow: hidden;">
                <tr>
                    <td align="center" style="padding: 32px 28px;">
                        @if(!empty($discountBadge))
                            <span style="display: inline-block; background-color: #fef2f2; color: #dc2626; font-size: 13px; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase; padding: 4px 14px; border-radius: 9999px; border: 1px solid #fee2e2; margin-bottom: 12px;">
                                {{ $discountBadge }}
                            </span>
                        @endif

                        <h2 style="margin: 0 0 10px 0; font-size: 22px; font-weight: 800; color: {{ $headingColor }}; letter-spacing: -0.5px; line-height: 1.3;">
                            {{ $headline }}
                        </h2>

                        @if(!empty($description))
                            <p style="margin: 0 0 20px 0; font-size: 14px; color: {{ $textColor }}; line-height: 1.5; max-width: 440px;">
                                {{ $description }}
                            </p>
                        @endif

                        <!-- Promo Code Copy Box -->
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" align="center" style="margin: 0 auto 20px auto; background-color: {{ $codeBg }}; border: 1px solid #cbd5e1; border-radius: 8px;">
                            <tr>
                                <td align="center" style="padding: 12px 28px;">
                                    <span style="font-family: 'Courier New', Courier, monospace; font-size: 22px; font-weight: 800; letter-spacing: 3px; color: {{ $primaryColor }};">
                                        {{ $code }}
                                    </span>
                                </td>
                            </tr>
                        </table>

                        @if(!empty($expiresAt))
                            <p style="margin: 0 0 20px 0; font-size: 12px; color: #64748b; font-style: italic;">
                                Offer expires: {{ $expiresAt }}
                            </p>
                        @endif

                        @if(!empty($buttonUrl) && $buttonUrl !== '#')
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" align="center">
                                <tr>
                                    <td align="center" style="border-radius: 8px; background-color: {{ $primaryColor }};">
                                        <!--[if mso]>
                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $buttonUrl }}" style="height:44px;v-text-anchor:middle;width:200px;" arcsize="18%" stroke="f" fillcolor="{{ $primaryColor }}">
                                        <w:anchorlock/>
                                        <center style="color:#ffffff;font-family:sans-serif;font-size:14px;font-weight:bold;">{{ $buttonText }}</center>
                                        </v:roundrect>
                                        <![endif]-->
                                        <a href="{{ $buttonUrl }}" target="_blank" style="display: inline-block; padding: 12px 28px; font-size: 14px; font-weight: 700; color: #ffffff; text-decoration: none; border-radius: 8px; text-align: center; -webkit-text-size-adjust: none; mso-hide: all;">
                                            {{ $buttonText }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        @endif
                    </td>
                </tr>
            </table>
            <!--[if mso]>
            </td>
            </tr>
            </table>
            <![endif]-->
        </td>
    </tr>
</table>
