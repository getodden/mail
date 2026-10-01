@php
    $imageUrl = $data['image_url'] ?? 'https://images.unsplash.com/photo-1557804506-669a67965ba0?auto=format&fit=crop&w=1200&q=80';
    $altText = $data['alt_text'] ?? 'Promotional Banner Image';
    $linkUrl = $data['link_url'] ?? null;
    $caption = $data['caption'] ?? null;
    $fullWidth = (bool) ($data['full_width'] ?? false);
    $borderRadius = $data['border_radius'] ?? '8px';
    $padding = $fullWidth ? '0' : '24px 32px';
@endphp

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $theme['content_background_color'] ?? '#ffffff' }};">
    <tr>
        <td align="center" style="padding: {{ $padding }};" class="mobile-padding">
            @if(!empty($linkUrl))
                <a href="{{ $linkUrl }}" style="text-decoration: none; display: block;">
            @endif

            <img
                src="{{ $imageUrl }}"
                alt="{{ $altText }}"
                border="0"
                style="display: block; width: 100%; max-width: 100%; height: auto; border-radius: {{ $borderRadius }}; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic;"
                class="mobile-full-width"
            />

            @if(!empty($linkUrl))
                </a>
            @endif

            @if(!empty($caption))
                <p style="margin: 8px 0 0 0; font-size: 12px; line-height: 1.4; color: #64748b; text-align: center;">
                    {{ $caption }}
                </p>
            @endif
        </td>
    </tr>
</table>
