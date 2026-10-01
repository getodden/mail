@php
    $heading = $data['heading'] ?? null;
    $items = $data['items'] ?? [];
    $bgColor = $data['bg_color'] ?? '#f8fafc';
@endphp

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $bgColor }};">
    <tr>
        <td style="padding: 32px 32px;" class="mobile-padding">
            @if(!empty($heading))
                <h3 style="margin: 0 0 20px 0; font-size: 18px; font-weight: 700; color: {{ $theme['heading_color'] ?? '#0f172a' }};">
                    {{ $heading }}
                </h3>
            @endif

            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                @foreach($items as $item)
                    @php
                        $icon = $item['icon'] ?? '⚡';
                        $itemTitle = $item['title'] ?? '';
                        $itemText = $item['text'] ?? '';
                    @endphp
                    <tr>
                        <td width="36" valign="top" style="padding: 10px 16px 14px 0; font-size: 20px; line-height: 1;">
                            {{ $icon }}
                        </td>
                        <td valign="top" style="padding: 10px 0 14px 0;">
                            @if(!empty($itemTitle))
                                <strong style="display: block; font-size: 15px; font-weight: 700; color: {{ $theme['heading_color'] ?? '#0f172a' }}; margin-bottom: 4px;">
                                    {{ $itemTitle }}
                                </strong>
                            @endif
                            <span style="font-size: 14px; line-height: 1.5; color: {{ $theme['text_color'] ?? '#64748b' }};">
                                {{ $itemText }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
</table>
