@php
    $leftTitle = $data['left_title'] ?? '';
    $leftBody = $data['left_body'] ?? '';
    $leftImage = $data['left_image'] ?? null;
    $leftBtnText = $data['left_button_text'] ?? null;
    $leftBtnUrl = $data['left_button_url'] ?? '#';

    $rightTitle = $data['right_title'] ?? '';
    $rightBody = $data['right_body'] ?? '';
    $rightImage = $data['right_image'] ?? null;
    $rightBtnText = $data['right_button_text'] ?? null;
    $rightBtnUrl = $data['right_button_url'] ?? '#';

    $bgColor = $data['bg_color'] ?? ($theme['content_background_color'] ?? '#ffffff');
    $reverse = (bool) ($data['reverse_stack_on_mobile'] ?? false);
@endphp

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $bgColor }};">
    <tr>
        <td style="padding: 24px 20px; direction: {{ $reverse ? 'rtl' : 'ltr' }};" class="mobile-padding" dir="{{ $reverse ? 'rtl' : 'ltr' }}">
            <!--[if mso]>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
            <td width="280" valign="top">
            <![endif]-->
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="48%" align="{{ $reverse ? 'right' : 'left' }}" class="stack-column" dir="ltr" style="max-width: 270px; margin-bottom: 16px; direction: ltr;">
                <tr>
                    <td style="padding: 16px; background-color: #f8fafc; border: 1px solid {{ $theme['border_color'] ?? '#e2e8f0' }}; border-radius: 8px;">
                        @if(!empty($leftImage))
                            <img src="{{ $leftImage }}" alt="{{ $leftTitle }}" style="display: block; width: 100%; height: auto; border-radius: 4px; margin-bottom: 12px; border: 0;">
                        @endif
                        @if(!empty($leftTitle))
                            <h3 style="margin: 0 0 8px 0; font-size: 17px; font-weight: 700; color: {{ $theme['heading_color'] ?? '#0f172a' }};">
                                {{ $leftTitle }}
                            </h3>
                        @endif
                        @if(!empty($leftBody))
                            <p style="margin: 0; font-size: 14px; line-height: 1.55; color: {{ $theme['text_color'] ?? '#475569' }};">
                                {!! nl2br(e($leftBody)) !!}
                            </p>
                        @endif
                        @if(!empty($leftBtnText))
                            <div style="margin-top: 14px;">
                                <a href="{{ $leftBtnUrl }}" style="display: inline-block; font-size: 13px; font-weight: 600; color: {{ $theme['primary_color'] ?? '#2563eb' }}; text-decoration: none;">
                                    {{ $leftBtnText }} &rarr;
                                </a>
                            </div>
                        @endif
                    </td>
                </tr>
            </table>
            <!--[if mso]>
            </td>
            <td width="280" valign="top">
            <![endif]-->
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="48%" align="{{ $reverse ? 'left' : 'right' }}" class="stack-column" dir="ltr" style="max-width: 270px; margin-bottom: 16px; direction: ltr;">
                <tr>
                    <td style="padding: 16px; background-color: #f8fafc; border: 1px solid {{ $theme['border_color'] ?? '#e2e8f0' }}; border-radius: 8px;">
                        @if(!empty($rightImage))
                            <img src="{{ $rightImage }}" alt="{{ $rightTitle }}" style="display: block; width: 100%; height: auto; border-radius: 4px; margin-bottom: 12px; border: 0;">
                        @endif
                        @if(!empty($rightTitle))
                            <h3 style="margin: 0 0 8px 0; font-size: 17px; font-weight: 700; color: {{ $theme['heading_color'] ?? '#0f172a' }};">
                                {{ $rightTitle }}
                            </h3>
                        @endif
                        @if(!empty($rightBody))
                            <p style="margin: 0; font-size: 14px; line-height: 1.55; color: {{ $theme['text_color'] ?? '#475569' }};">
                                {!! nl2br(e($rightBody)) !!}
                            </p>
                        @endif
                        @if(!empty($rightBtnText))
                            <div style="margin-top: 14px;">
                                <a href="{{ $rightBtnUrl }}" style="display: inline-block; font-size: 13px; font-weight: 600; color: {{ $theme['primary_color'] ?? '#2563eb' }}; text-decoration: none;">
                                    {{ $rightBtnText }} &rarr;
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
