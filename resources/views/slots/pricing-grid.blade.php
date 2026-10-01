@php
    $heading = $data['heading'] ?? 'Select the Plan That Fits Your Velocity';
    $subtitle = $data['subtitle'] ?? 'Transparent pricing designed to scale with your team.';
    $tiers = $data['tiers'] ?? [
        [
            'name' => 'Starter',
            'price' => '$49',
            'frequency' => '/ month',
            'features' => ['Up to 5,000 contacts', 'Standard email slots', 'Shared domain sending'],
            'button_text' => 'Get Started',
            'button_url' => 'https://example.com/signup?plan=starter',
            'is_popular' => false,
        ],
        [
            'name' => 'Growth',
            'price' => '$149',
            'frequency' => '/ month',
            'features' => ['Up to 25,000 contacts', 'A/B testing engine', 'Dedicated IP sending', 'Custom object schemas'],
            'button_text' => 'Upgrade to Growth',
            'button_url' => 'https://example.com/signup?plan=growth',
            'is_popular' => true,
        ],
    ];
@endphp

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $theme['content_background_color'] ?? '#ffffff' }};">
    <tr>
        <td style="padding: 32px 24px;" class="mobile-padding">
            @if(!empty($heading))
                <h2 style="margin: 0 0 6px 0; font-size: 20px; font-weight: 700; color: #0f172a; text-align: center;">
                    {{ $heading }}
                </h2>
            @endif
            @if(!empty($subtitle))
                <p style="margin: 0 0 28px 0; font-size: 14px; color: #64748b; text-align: center;">
                    {{ $subtitle }}
                </p>
            @endif

            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    @foreach($tiers as $tier)
                        @php
                            $isPopular = !empty($tier['is_popular']);
                            $cardBg = $isPopular ? '#f8fafc' : '#ffffff';
                            $borderColor = $isPopular ? ($theme['primary_color'] ?? '#2563eb') : '#e2e8f0';
                            $btnBg = $isPopular ? ($theme['primary_color'] ?? '#2563eb') : '#0f172a';
                        @endphp
                        <td class="stack-column" width="{{ count($tiers) > 0 ? round(100 / count($tiers)) : 50 }}%" valign="top" style="padding: 8px;">
                            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $cardBg }}; border: 2px solid {{ $borderColor }}; border-radius: 12px; overflow: hidden;">
                                @if($isPopular)
                                    <tr>
                                        <td align="center" style="background-color: {{ $borderColor }}; padding: 4px; color: #ffffff; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">
                                            Most Popular
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td style="padding: 24px;">
                                        <div style="font-size: 16px; font-weight: bold; color: #0f172a; margin-bottom: 8px;">
                                            {{ $tier['name'] ?? 'Plan' }}
                                        </div>
                                        <div style="margin-bottom: 16px;">
                                            <span style="font-size: 28px; font-weight: 800; color: #0f172a;">{{ $tier['price'] ?? '$0' }}</span>
                                            <span style="font-size: 12px; color: #64748b;">{{ $tier['frequency'] ?? '/mo' }}</span>
                                        </div>

                                        @if(!empty($tier['features']) && is_array($tier['features']))
                                            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
                                                @foreach($tier['features'] as $feature)
                                                    <tr>
                                                        <td width="20" valign="top" style="padding: 4px 0; color: #10b981; font-weight: bold; font-size: 14px;">✓</td>
                                                        <td style="padding: 4px 0 4px 4px; font-size: 13px; color: #334155; line-height: 1.4;">{{ $feature }}</td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        @endif

                                        <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" style="border-radius: 6px;" bgcolor="{{ $btnBg }}">
                                                    <a href="{{ $tier['button_url'] ?? '#' }}" style="background-color: {{ $btnBg }}; color: #ffffff; display: block; font-size: 14px; font-weight: 600; line-height: 40px; text-align: center; text-decoration: none; border-radius: 6px;">
                                                        {{ $tier['button_text'] ?? 'Select Plan' }}
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    @endforeach
                </tr>
            </table>
        </td>
    </tr>
</table>
