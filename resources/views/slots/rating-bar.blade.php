@php
    $question = (string) $slot->get('question', 'How likely are you to recommend us to a friend or colleague?');
    $scaleType = (string) $slot->get('scale_type', '10'); // '10' (0-10) or '5' (1-5)
    $lowLabel = (string) $slot->get('low_label', 'Not at all likely');
    $highLabel = (string) $slot->get('high_label', 'Extremely likely');
    $baseUrl = (string) $slot->get('base_url', '/feedback/nps/{{nps_token}}');
    $primaryColor = $theme['primary_color'] ?? '#2563EB';

    $numbers = $scaleType === '5' ? [1, 2, 3, 4, 5] : [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
    $cellWidth = round(100 / count($numbers), 2);
@endphp

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 24px 0;">
    <tr>
        <td align="center" style="padding: 24px; background-color: #F8FAFC; border-radius: 12px; border: 1px solid #E2E8F0;">
            <p style="margin: 0 0 16px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 16px; font-weight: 600; color: #1E293B; line-height: 1.4;">
                {{ $question }}
            </p>

            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 520px;">
                <tr>
                    @foreach($numbers as $num)
                        @php
                            $targetUrl = rtrim($baseUrl, '/') . '/' . $num;
                        @endphp
                        <td align="center" style="padding: 2px;">
                            <a href="{{ $targetUrl }}" target="_blank" style="display: block; width: 34px; height: 34px; line-height: 34px; text-align: center; background-color: #FFFFFF; border: 1px solid #CBD5E1; border-radius: 6px; color: #0F172A; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 13px; font-weight: 700; text-decoration: none;">
                                {{ $num }}
                            </a>
                        </td>
                    @endforeach
                </tr>
            </table>

            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 520px; margin-top: 8px;">
                <tr>
                    <td align="left" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; color: #64748B; font-weight: 500;">
                        ← {{ $lowLabel }}
                    </td>
                    <td align="right" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 11px; color: #64748B; font-weight: 500;">
                        {{ $highLabel }} →
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
