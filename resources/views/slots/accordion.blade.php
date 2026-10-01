@php
    $heading = (string) $slot->get('heading', 'Frequently Asked Questions');
    $subtitle = (string) $slot->get('subtitle', '');
    /** @var list<array{question?: string, answer?: string}> $items */
    $items = $slot->get('items', [
        ['question' => 'How does the free trial work?', 'answer' => 'You get 14 days of unrestricted access to all features. No credit card required.'],
        ['question' => 'Can I cancel or change plans anytime?', 'answer' => 'Yes, upgrade, downgrade, or cancel directly from your account settings at any moment.'],
    ]);
    $primaryColor = $theme['primary_color'] ?? '#2563EB';
@endphp

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 24px 0;">
    <tr>
        <td style="padding: 0;">
            @if($heading !== '')
                <h3 style="margin: 0 0 6px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 20px; font-weight: 700; color: #0F172A; text-align: center;">
                    {{ $heading }}
                </h3>
            @endif

            @if($subtitle !== '')
                <p style="margin: 0 0 20px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; color: #64748B; text-align: center;">
                    {{ $subtitle }}
                </p>
            @endif

            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                @foreach($items as $item)
                    @php
                        $q = (string) ($item['question'] ?? 'Question');
                        $a = (string) ($item['answer'] ?? '');
                    @endphp
                    <tr>
                        <td style="padding-bottom: 12px;">
                            <div style="background-color: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 8px; padding: 16px 20px;">
                                <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 15px; font-weight: 600; color: #1E293B; line-height: 1.4; display: flex; justify-content: space-between; align-items: center;">
                                    <span>{{ $q }}</span>
                                </div>
                                @if($a !== '')
                                    <div style="margin-top: 8px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 13px; color: #475569; line-height: 1.5; border-top: 1px solid #F1F5F9; padding-top: 8px;">
                                        {{ $a }}
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
</table>
