@php
    $heading = (string) $slot->get('heading', 'Recommended For You');
    $subtitle = (string) $slot->get('subtitle', 'Curated selections based on your interests.');
    /** @var list<array{title?: string, description?: string, price?: string, image_url?: string, button_url?: string, button_text?: string}> $items */
    $items = $slot->get('items', [
        [
            'title' => 'Enterprise Analytics Suite',
            'description' => 'Real-time multi-touch attribution & closed-loop funnel analytics.',
            'price' => '$299 / mo',
            'image_url' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=400&q=80',
            'button_url' => 'https://example.com/products/analytics',
            'button_text' => 'Explore Suite',
        ],
        [
            'title' => 'Audience Signal Engine',
            'description' => 'Automated account-based scoring and dynamic visitor stitching.',
            'price' => '$199 / mo',
            'image_url' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=400&q=80',
            'button_url' => 'https://example.com/products/signals',
            'button_text' => 'Explore Engine',
        ],
    ]);
    $primaryColor = $theme['primary_color'] ?? '#2563EB';
@endphp

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 28px 0;">
    <tr>
        <td style="padding: 0;">
            @if($heading !== '')
                <h3 style="margin: 0 0 6px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 20px; font-weight: 700; color: #0F172A; text-align: center;">
                    {{ $heading }}
                </h3>
            @endif

            @if($subtitle !== '')
                <p style="margin: 0 0 24px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; color: #64748B; text-align: center;">
                    {{ $subtitle }}
                </p>
            @endif

            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                @foreach($items as $item)
                    @php
                        $title = (string) ($item['title'] ?? 'Item');
                        $desc = (string) ($item['description'] ?? '');
                        $price = (string) ($item['price'] ?? '');
                        $img = (string) ($item['image_url'] ?? '');
                        $btnUrl = (string) ($item['button_url'] ?? '#');
                        $btnText = (string) ($item['button_text'] ?? 'Learn More');
                    @endphp
                    <tr>
                        <td style="padding-bottom: 16px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 10px; overflow: hidden;">
                                <tr>
                                    @if($img !== '')
                                        <td width="140" valign="top" style="padding: 0; width: 140px;" class="stack-column">
                                            <img src="{{ $img }}" alt="{{ $title }}" width="140" style="display: block; width: 100%; height: 100%; object-fit: cover; max-height: 140px; border: 0;">
                                        </td>
                                    @endif
                                    <td valign="middle" style="padding: 16px 20px;">
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td>
                                                    <h4 style="margin: 0 0 4px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 16px; font-weight: 700; color: #0F172A;">
                                                        {{ $title }}
                                                    </h4>
                                                    @if($desc !== '')
                                                        <p style="margin: 0 0 10px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 13px; color: #64748B; line-height: 1.4;">
                                                            {{ $desc }}
                                                        </p>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                                        <tr>
                                                            @if($price !== '')
                                                                <td align="left" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 15px; font-weight: 800; color: #0F172A;">
                                                                    {{ $price }}
                                                                </td>
                                                            @endif
                                                            <td align="right">
                                                                <a href="{{ $btnUrl }}" target="_blank" style="display: inline-block; padding: 7px 16px; background-color: {{ $primaryColor }}; color: #FFFFFF; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 12px; font-weight: 700; text-decoration: none; border-radius: 6px;">
                                                                    {{ $btnText }} →
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
</table>
