@php
    $columns = (int) ($data['columns'] ?? 2);
    if (! in_array($columns, [1, 2, 3], true)) {
        $columns = 2;
    }
    
    $products = $data['products'] ?? [];
    if (empty($products)) {
        $products = [
            [
                'title' => 'Sample Product Name',
                'description' => 'High quality premium product built for performance and durability.',
                'price' => '$49.00',
                'compare_at_price' => '$79.00',
                'savings_badge' => 'Save 38%',
                'image_url' => 'https://via.placeholder.com/600x400?text=Product+Image',
                'button_text' => 'Buy Now',
                'button_url' => '#',
                'stock_badge' => 'In Stock',
            ],
            [
                'title' => 'Complementary Accessory',
                'description' => 'Essential companion add-on to elevate your daily workflow experience.',
                'price' => '$29.00',
                'compare_at_price' => '$39.00',
                'savings_badge' => 'Save 25%',
                'image_url' => 'https://via.placeholder.com/600x400?text=Accessory+Image',
                'button_text' => 'Shop Now',
                'button_url' => '#',
                'stock_badge' => 'Only 3 left',
            ]
        ];
    }

    $cardBg = $data['card_background'] ?? '#ffffff';
    $cardBorder = $data['card_border'] ?? '#e2e8f0';
    $buttonBg = $data['button_background'] ?? ($theme['primary_color'] ?? '#2563eb');
    $buttonTextCol = $data['button_color'] ?? '#ffffff';
    $showBadges = (bool) ($data['show_badges'] ?? true);
    
    $colPercent = match($columns) {
        1 => '100%',
        2 => '50%',
        3 => '33.333%',
    };

    $msoWidth = match($columns) {
        1 => 600,
        2 => 300,
        3 => 200,
    };
@endphp

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: {{ $data['background_color'] ?? 'transparent' }};">
    @if(!empty($data['section_title']))
    <tr>
        <td align="center" style="padding: 24px 20px 12px 20px;">
            <h2 style="margin: 0 0 6px 0; font-size: 24px; font-weight: 700; color: {{ $theme['heading_color'] ?? '#0f172a' }};">
                {{ $data['section_title'] }}
            </h2>
            @if(!empty($data['section_subtitle']))
                <p style="margin: 0; font-size: 14px; color: {{ $theme['muted_text_color'] ?? '#64748b' }}; line-height: 1.5;">
                    {{ $data['section_subtitle'] }}
                </p>
            @endif
        </td>
    </tr>
    @endif
    <tr>
        <td style="padding: 12px 10px 24px 10px;">
            <!--[if mso]>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
            <![endif]-->

            @foreach($products as $index => $item)
                @if($index > 0 && ($index % $columns === 0))
                    <!--[if mso]>
                    </tr>
                    <tr>
                    <![endif]-->
                @endif

                <!--[if mso]>
                <td width="{{ $msoWidth }}" valign="top" style="padding: 8px;">
                <![endif]-->
                
                <div class="stack-column" style="display: inline-block; vertical-align: top; width: 100%; max-width: {{ $colPercent }}; box-sizing: border-box; padding: 8px;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: {{ $cardBg }}; border: 1px solid {{ $cardBorder }}; border-radius: 8px; overflow: hidden; height: 100%;">
                        @if(!empty($item['image_url']))
                        <tr>
                            <td align="center" style="padding: 0; position: relative;">
                                @if(!empty($item['button_url']))
                                    <a href="{{ $item['button_url'] }}" target="_blank" style="display: block; text-decoration: none;">
                                @endif
                                <img src="{{ $item['image_url'] }}" alt="{{ $item['title'] ?? 'Product' }}" width="100%" style="display: block; width: 100%; max-width: 100%; height: auto; border: 0; outline: none;">
                                @if(!empty($item['button_url']))
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td style="padding: 16px;">
                                @if($showBadges && (!empty($item['savings_badge']) || !empty($item['stock_badge'])))
                                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 8px;">
                                    <tr>
                                        @if(!empty($item['savings_badge']))
                                        <td align="left">
                                            <span style="display: inline-block; background-color: #fef2f2; color: #dc2626; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 2px 8px; border-radius: 4px; border: 1px solid #fee2e2;">
                                                {{ $item['savings_badge'] }}
                                            </span>
                                        </td>
                                        @endif
                                        @if(!empty($item['stock_badge']))
                                        <td align="right">
                                            <span style="display: inline-block; background-color: #f0fdf4; color: #16a34a; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 4px; border: 1px solid #dcfce7;">
                                                {{ $item['stock_badge'] }}
                                            </span>
                                        </td>
                                        @endif
                                    </tr>
                                </table>
                                @endif

                                <h3 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 600; color: {{ $theme['heading_color'] ?? '#0f172a' }}; line-height: 1.3;">
                                    {{ $item['title'] ?? '' }}
                                </h3>

                                @if(!empty($item['description']))
                                    <p style="margin: 0 0 12px 0; font-size: 13px; color: {{ $theme['text_color'] ?? '#64748b' }}; line-height: 1.4;">
                                        {{ Str::limit($item['description'], 90) }}
                                    </p>
                                @endif

                                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 14px;">
                                    <tr>
                                        <td valign="middle">
                                            <span style="font-size: 18px; font-weight: 700; color: {{ $theme['heading_color'] ?? '#0f172a' }};">
                                                {{ $item['price'] ?? '' }}
                                            </span>
                                            @if(!empty($item['compare_at_price']))
                                                <span style="font-size: 13px; color: #94a3b8; text-decoration: line-through; margin-left: 6px;">
                                                    {{ $item['compare_at_price'] }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>

                                @if(!empty($item['button_url']))
                                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td align="center" style="border-radius: 6px; background-color: {{ $buttonBg }};">
                                            <a href="{{ $item['button_url'] }}" target="_blank" style="display: block; padding: 10px 16px; font-size: 13px; font-weight: 600; color: {{ $buttonTextCol }}; text-decoration: none; border-radius: 6px; text-align: center;">
                                                {{ $item['button_text'] ?? 'Buy Now' }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>

                <!--[if mso]>
                </td>
                <![endif]-->
            @endforeach

            <!--[if mso]>
            </tr>
            </table>
            <![endif]-->
        </td>
    </tr>
</table>
