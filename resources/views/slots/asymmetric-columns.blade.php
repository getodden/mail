@php
    $ratio = (string) $slot->get('ratio', '30_70'); // '30_70' or '70_30'
    $leftWidth = $ratio === '30_70' ? '32%' : '64%';
    $rightWidth = $ratio === '30_70' ? '64%' : '32%';
    $msoLeftWidth = $ratio === '30_70' ? '180' : '380';
    $msoRightWidth = $ratio === '30_70' ? '380' : '180';

    $leftImage = (string) $slot->get('left_image', '');
    $leftTitle = (string) $slot->get('left_title', 'Sidebar Callout');
    $leftBody = (string) $slot->get('left_body', 'Key highlight or author biographical details.');
    $leftBg = (string) $slot->get('left_bg_color', '#F8FAFC');

    $rightTitle = (string) $slot->get('right_title', 'Featured Event / Main Story');
    $rightBody = (string) $slot->get('right_body', 'In-depth description, schedule itinerary, or keynote topic breakdown.');
    $rightBtnText = (string) $slot->get('right_button_text', 'RSVP & Register');
    $rightBtnUrl = (string) $slot->get('right_button_url', '#');
    $rightBg = (string) $slot->get('right_bg_color', '#FFFFFF');

    $primaryColor = $theme['primary_color'] ?? '#2563EB';
    $headingColor = $theme['heading_color'] ?? '#0F172A';
    $textColor = $theme['text_color'] ?? '#475569';
    $borderColor = $theme['border_color'] ?? '#E2E8F0';
@endphp

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 24px 0;">
    <tr>
        <td style="padding: 0;">
            <!--[if mso]>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
            <td width="{{ $msoLeftWidth }}" valign="top" style="padding-right: 12px;">
            <![endif]-->
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="{{ $leftWidth }}" align="left" class="stack-column" style="margin-bottom: 16px;">
                <tr>
                    <td style="padding: 20px; background-color: {{ $leftBg }}; border: 1px solid {{ $borderColor }}; border-radius: 8px;">
                        @if($leftImage !== '')
                            <img src="{{ $leftImage }}" alt="{{ $leftTitle }}" style="display: block; width: 100%; height: auto; border-radius: 6px; margin-bottom: 12px; border: 0;">
                        @endif
                        @if($leftTitle !== '')
                            <h4 style="margin: 0 0 6px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 15px; font-weight: 700; color: {{ $headingColor }};">
                                {{ $leftTitle }}
                            </h4>
                        @endif
                        <p style="margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 13px; line-height: 1.5; color: {{ $textColor }};">
                            {!! nl2br(e($leftBody)) !!}
                        </p>
                    </td>
                </tr>
            </table>
            <!--[if mso]>
            </td>
            <td width="{{ $msoRightWidth }}" valign="top" style="padding-left: 12px;">
            <![endif]-->
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="{{ $rightWidth }}" align="right" class="stack-column" style="margin-bottom: 16px;">
                <tr>
                    <td style="padding: 20px; background-color: {{ $rightBg }}; border: 1px solid {{ $borderColor }}; border-radius: 8px;">
                        @if($rightTitle !== '')
                            <h3 style="margin: 0 0 10px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 18px; font-weight: 700; color: {{ $headingColor }};">
                                {{ $rightTitle }}
                            </h3>
                        @endif
                        <p style="margin: 0 0 16px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; line-height: 1.6; color: {{ $textColor }};">
                            {!! nl2br(e($rightBody)) !!}
                        </p>
                        @if($rightBtnText !== '')
                            <div>
                                <a href="{{ $rightBtnUrl }}" style="display: inline-block; padding: 10px 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 13px; font-weight: 600; color: #FFFFFF; background-color: {{ $primaryColor }}; border-radius: 6px; text-decoration: none;">
                                    {{ $rightBtnText }}
                                </a>
                            </div>
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
