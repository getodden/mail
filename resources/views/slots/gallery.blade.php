@php
    $heading = (string) ($data['heading'] ?? '');
    $perRow = (int) ($data['columns'] ?? 3) === 2 ? 2 : 3;
    $images = array_slice(array_values(array_filter(is_array($data['images'] ?? null) ? $data['images'] : [], fn ($image): bool => is_array($image) && ! empty($image['image_url']))), 0, 12);
    $rows = array_chunk($images, $perRow);
    $cellWidth = $perRow === 2 ? 250 : 160;
    $columnWidth = $perRow === 2 ? '48%' : '31%';
    $headingColor = $theme['heading_color'] ?? '#0f172a';
    $textColor = $theme['text_color'] ?? '#64748b';
    $fontFamily = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
@endphp

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $theme['content_background_color'] ?? '#ffffff' }};">
    <tr>
        <td style="padding: 24px 32px;" class="mobile-padding">
            @if($heading !== '')
                <h3 style="margin: 0 0 16px 0; font-family: {{ $fontFamily }}; font-size: 20px; font-weight: 700; color: {{ $headingColor }}; text-align: center;">
                    {{ $heading }}
                </h3>
            @endif

            @foreach($rows as $row)
                <!--[if mso]>
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                <![endif]-->
                @foreach($row as $image)
                    @php
                        $imageUrl = (string) $image['image_url'];
                        $alt = (string) ($image['alt'] ?? '');
                        $caption = (string) ($image['caption'] ?? '');
                        $linkUrl = (string) ($image['link_url'] ?? '');
                    @endphp
                    <!--[if mso]><td width="{{ $cellWidth }}" valign="top" style="padding: 6px;"><![endif]-->
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="{{ $columnWidth }}" align="left" class="stack-column" style="max-width: {{ $cellWidth }}px; margin-bottom: 16px; margin-right: 2%;">
                        <tr>
                            <td style="font-family: {{ $fontFamily }};">
                                @if($linkUrl !== '')
                                    <a href="{{ $linkUrl }}" style="text-decoration: none;"><img src="{{ $imageUrl }}" alt="{{ $alt }}" width="{{ $cellWidth }}" style="display: block; width: 100%; height: auto; border-radius: 4px; border: 0;"></a>
                                @else
                                    <img src="{{ $imageUrl }}" alt="{{ $alt }}" width="{{ $cellWidth }}" style="display: block; width: 100%; height: auto; border-radius: 4px; border: 0;">
                                @endif
                                @if($caption !== '')
                                    <p style="margin: 8px 0 0 0; font-size: 13px; line-height: 1.5; color: {{ $textColor }};">
                                        {{ $caption }}
                                    </p>
                                @endif
                            </td>
                        </tr>
                    </table>
                    <!--[if mso]></td><![endif]-->
                @endforeach
                <!--[if mso]>
                </tr>
                </table>
                <![endif]-->
                <div style="clear: both; font-size: 0; line-height: 0;">&nbsp;</div>
            @endforeach
        </td>
    </tr>
</table>
