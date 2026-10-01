@php
    $badge = (string) $slot->get('badge', '⚡ LIMITED TIME OFFER');
    $heading = (string) $slot->get('heading', 'Hurry, Offer Ends Soon!');
    $subtext = (string) $slot->get('subtext', 'Unlock 20% off all annual subscriptions before the promotion concludes.');
    $deadlineText = (string) $slot->get('deadline_text', 'Offer expires Midnight Sunday');
    $buttonText = (string) $slot->get('button_text', 'Claim Your Discount');
    $buttonUrl = (string) $slot->get('button_url', 'https://example.com/promo');
    $primaryColor = $theme['primary_color'] ?? '#DC2626'; // Urgent crimson/red default
@endphp

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 24px 0;">
    <tr>
        <td align="center" style="padding: 32px 24px; background: linear-gradient(135deg, #FFF1F2 0%, #FFE4E6 100%); background-color: #FFF1F2; border: 2px dashed #FDA4AF; border-radius: 12px;">
            @if($badge !== '')
                <span style="display: inline-block; padding: 4px 12px; background-color: #BE123C; color: #FFFFFF; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 11px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; border-radius: 9999px; margin-bottom: 12px;">
                    {{ $badge }}
                </span>
            @endif

            <h3 style="margin: 0 0 8px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 22px; font-weight: 800; color: #881337; line-height: 1.25;">
                {{ $heading }}
            </h3>

            @if($subtext !== '')
                <p style="margin: 0 0 20px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; color: #4C0519; line-height: 1.5; max-width: 480px;">
                    {{ $subtext }}
                </p>
            @endif

            <!-- Stylized Countdown Blocks -->
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto 20px auto;">
                <tr>
                    <td align="center" style="padding: 0 6px;">
                        <div style="background-color: #FFFFFF; border: 1px solid #FECDD3; border-radius: 8px; padding: 8px 12px; min-width: 50px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <span style="display: block; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 20px; font-weight: 800; color: #9F1239; line-height: 1;">48</span>
                            <span style="display: block; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 9px; font-weight: 700; color: #94A3B8; text-transform: uppercase; margin-top: 4px;">HRS</span>
                        </div>
                    </td>
                    <td align="center" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 20px; font-weight: 700; color: #FDA4AF; vertical-align: middle;">:</td>
                    <td align="center" style="padding: 0 6px;">
                        <div style="background-color: #FFFFFF; border: 1px solid #FECDD3; border-radius: 8px; padding: 8px 12px; min-width: 50px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <span style="display: block; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 20px; font-weight: 800; color: #9F1239; line-height: 1;">00</span>
                            <span style="display: block; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 9px; font-weight: 700; color: #94A3B8; text-transform: uppercase; margin-top: 4px;">MIN</span>
                        </div>
                    </td>
                    <td align="center" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 20px; font-weight: 700; color: #FDA4AF; vertical-align: middle;">:</td>
                    <td align="center" style="padding: 0 6px;">
                        <div style="background-color: #FFFFFF; border: 1px solid #FECDD3; border-radius: 8px; padding: 8px 12px; min-width: 50px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <span style="display: block; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 20px; font-weight: 800; color: #9F1239; line-height: 1;">00</span>
                            <span style="display: block; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 9px; font-weight: 700; color: #94A3B8; text-transform: uppercase; margin-top: 4px;">SEC</span>
                        </div>
                    </td>
                </tr>
            </table>

            @if($deadlineText !== '')
                <p style="margin: 0 0 16px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 12px; font-weight: 600; color: #9F1239;">
                    ⏳ {{ $deadlineText }}
                </p>
            @endif

            @if($buttonText !== '' && $buttonUrl !== '')
                <div>
                    <a href="{{ $buttonUrl }}" target="_blank" style="display: inline-block; padding: 12px 28px; background-color: #BE123C; color: #FFFFFF; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; font-weight: 700; text-decoration: none; border-radius: 8px; box-shadow: 0 2px 4px rgba(190, 18, 60, 0.25);">
                        {{ $buttonText }} →
                    </a>
                </div>
            @endif
        </td>
    </tr>
</table>
