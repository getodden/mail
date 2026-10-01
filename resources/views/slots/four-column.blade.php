@php
    $heading = (string) $slot->get('heading', 'Trusted by Modern Revenue Teams');
    /** @var list<array{image_url?: string, label?: string, url?: string}> $items */
    $items = $slot->get('items', [
        ['label' => 'Acme Corp', 'image_url' => 'https://dummyimage.com/120x40/e2e8f0/64748b&text=Acme'],
        ['label' => 'Starlight AI', 'image_url' => 'https://dummyimage.com/120x40/e2e8f0/64748b&text=Starlight'],
        ['label' => 'HyperScale', 'image_url' => 'https://dummyimage.com/120x40/e2e8f0/64748b&text=HyperScale'],
        ['label' => 'OmniCloud', 'image_url' => 'https://dummyimage.com/120x40/e2e8f0/64748b&text=OmniCloud'],
    ]);
@endphp

<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 24px 0;">
    <tr>
        <td style="padding: 0; text-align: center;">
            @if($heading !== '')
                <p style="margin: 0 0 16px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: #94A3B8;">
                    {{ $heading }}
                </p>
            @endif

            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    @foreach($items as $item)
                        @php
                            $label = (string) ($item['label'] ?? '');
                            $img = (string) ($item['image_url'] ?? '');
                            $url = (string) ($item['url'] ?? '');
                        @endphp
                        <td width="25%" align="center" valign="middle" style="padding: 8px 6px;">
                            @if($url !== '')
                                <a href="{{ $url }}" target="_blank" style="text-decoration: none;">
                            @endif
                                @if($img !== '')
                                    <img src="{{ $img }}" alt="{{ $label }}" width="110" style="display: block; max-width: 100%; height: auto; max-height: 40px; border: 0; filter: grayscale(100%); opacity: 0.8;">
                                @else
                                    <span style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 13px; font-weight: 700; color: #64748B;">
                                        {{ $label }}
                                    </span>
                                @endif
                            @if($url !== '')
                                </a>
                            @endif
                        </td>
                    @endforeach
                </tr>
            </table>
        </td>
    </tr>
</table>
