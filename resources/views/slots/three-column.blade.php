@php
    $heading = (string) $slot->get('heading', '');
    $subtitle = (string) $slot->get('subtitle', '');
    /** @var list<array{image_url?: string, title?: string, description?: string, button_text?: string, button_url?: string}> $columns */
    $columns = $slot->get('columns', [
        [
            'title' => 'Product Analytics',
            'description' => 'Real-time funnel conversion metrics and event tracing.',
            'button_text' => 'Learn More',
            'button_url' => '#',
        ],
        [
            'title' => 'Audience Engine',
            'description' => 'Behavioral segmentation and automated lead scoring.',
            'button_text' => 'Learn More',
            'button_url' => '#',
        ],
        [
            'title' => 'Revenue Operations',
            'description' => 'Closed-loop attribution and pipeline forecasting.',
            'button_text' => 'Learn More',
            'button_url' => '#',
        ],
    ]);
    $primaryColor = $theme['primary_color'] ?? '#2563EB';
    $headingColor = $theme['heading_color'] ?? '#0F172A';
    $textColor = $theme['text_color'] ?? '#475569';
    $borderColor = $theme['border_color'] ?? '#E2E8F0';
@endphp

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 24px 0;">
    <tr>
        <td style="padding: 0;">
            @if($heading !== '')
                <h3 style="margin: 0 0 6px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 20px; font-weight: 700; color: {{ $headingColor }}; text-align: center;">
                    {{ $heading }}
                </h3>
            @endif

            @if($subtitle !== '')
                <p style="margin: 0 0 20px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; color: #64748B; text-align: center;">
                    {{ $subtitle }}
                </p>
            @endif

            <!--[if mso]>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
            @foreach($columns as $col)
                <td width="190" valign="top" style="padding: 6px;">
            <![endif]-->
            @foreach($columns as $col)
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="31%" align="left" class="stack-column" style="max-width: 190px; margin-bottom: 16px; margin-right: 2%;">
                    <tr>
                        <td style="padding: 16px; background-color: #F8FAFC; border: 1px solid {{ $borderColor }}; border-radius: 8px; text-align: left;">
                            @if(!empty($col['image_url']))
                                <img src="{{ $col['image_url'] }}" alt="{{ $col['title'] ?? '' }}" style="display: block; width: 100%; height: auto; border-radius: 4px; margin-bottom: 12px; border: 0;">
                            @endif
                            <h4 style="margin: 0 0 8px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 15px; font-weight: 700; color: {{ $headingColor }};">
                                {{ $col['title'] ?? 'Feature' }}
                            </h4>
                            @if(!empty($col['description']))
                                <p style="margin: 0 0 12px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 13px; line-height: 1.5; color: {{ $textColor }};">
                                    {{ $col['description'] }}
                                </p>
                            @endif
                            @if(!empty($col['button_text']))
                                <div>
                                    <a href="{{ $col['button_url'] ?? '#' }}" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 12px; font-weight: 600; color: {{ $primaryColor }}; text-decoration: none;">
                                        {{ $col['button_text'] }} &rarr;
                                    </a>
                                </div>
                            @endif
                        </td>
                    </tr>
                </table>
            @endforeach
            <!--[if mso]>
                </td>
            @endforeach
            </tr>
            </table>
            <![endif]-->
        </td>
    </tr>
</table>
