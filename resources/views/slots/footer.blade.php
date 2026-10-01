@php
    $companyName = $data['company_name'] ?? config('mail-builder.footer.company_name', config('app.name'));
    $address = $data['address'] ?? config('mail-builder.footer.address', '');
    $unsubscribeUrl = $data['unsubscribe_url'] ?? '{{unsubscribe_url}}';
    $preferencesUrl = $data['preferences_url'] ?? null;
    $notice = $data['notice'] ?? null;
    $copyrightYear = $data['copyright_year'] ?? date('Y');
@endphp

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border-top: 1px solid {{ $theme['border_color'] ?? '#e2e8f0' }};">
    <tr>
        <td align="center" style="padding: 28px 32px; font-size: 12px; line-height: 1.6; color: #94a3b8; text-align: center;" class="mobile-padding">
            @if(!empty($notice))
                <div style="margin-bottom: 12px; color: #64748b;">
                    {{ $notice }}
                </div>
            @endif

            <div style="font-weight: 600; color: #64748b;">
                &copy; {{ $copyrightYear }} {{ $companyName }}. All rights reserved.
            </div>

            @if(!empty($address))
                <div style="margin-top: 4px;">
                    {{ $address }}
                </div>
            @endif

            <div style="margin-top: 14px;">
                <a href="{{ $unsubscribeUrl }}" style="color: #64748b; text-decoration: underline;">
                    {{ config('mail-builder.footer.unsubscribe_text', 'Unsubscribe or manage your email preferences') }}
                </a>
                @if(!empty($preferencesUrl))
                    &nbsp;&middot;&nbsp;
                    <a href="{{ $preferencesUrl }}" style="color: #64748b; text-decoration: underline;">
                        Preference Center
                    </a>
                @endif
            </div>
        </td>
    </tr>
</table>
