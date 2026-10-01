@php
    $quote = $data['quote'] ?? '';
    $author = $data['author'] ?? '';
    $role = $data['role'] ?? '';
    $company = $data['company'] ?? '';
    $avatarUrl = $data['avatar_url'] ?? null;
    $rating = (int) ($data['rating'] ?? 5);
    $bgColor = $data['bg_color'] ?? ($theme['content_background_color'] ?? '#ffffff');
    $accentColor = $data['accent_color'] ?? ($theme['primary_color'] ?? '#2563eb');
@endphp

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $bgColor }};">
    <tr>
        <td style="padding: 24px 32px;" class="mobile-padding">
            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border-left: 4px solid {{ $accentColor }}; border-radius: 6px;">
                <tr>
                    <td style="padding: 20px 24px;">
                        @if($rating > 0)
                            <div style="font-size: 14px; color: #f59e0b; margin-bottom: 8px; letter-spacing: 2px;">
                                {{ str_repeat('★', min(5, max(1, $rating))) }}
                            </div>
                        @endif

                        <p style="margin: 0 0 14px 0; font-size: 15px; line-height: 1.6; font-style: italic; color: {{ $theme['text_color'] ?? '#334155' }};">
                            “{{ $quote }}”
                        </p>

                        <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                @if(!empty($avatarUrl))
                                    <td valign="middle" style="padding-right: 12px;">
                                        <img src="{{ $avatarUrl }}" alt="{{ $author }}" width="36" height="36" style="display: block; border-radius: 9999px; width: 36px; height: 36px; object-fit: cover;">
                                    </td>
                                @endif
                                <td valign="middle">
                                    <span style="font-size: 14px; font-weight: 700; color: {{ $theme['heading_color'] ?? '#0f172a' }};">
                                        {{ $author }}
                                    </span>
                                    @if(!empty($role) || !empty($company))
                                        <span style="display: block; font-size: 12px; color: #64748b; margin-top: 1px;">
                                            {{ implode(', ', array_filter([$role, $company])) }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
