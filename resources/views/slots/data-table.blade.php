@php
    $heading = $data['heading'] ?? '';
    $subtitle = $data['subtitle'] ?? '';
    $headers = $data['headers'] ?? ['Feature', 'Starter', 'Professional', 'Enterprise'];
    $rows = $data['rows'] ?? [];
    $bgColor = $data['bg_color'] ?? ($theme['content_background_color'] ?? '#ffffff');
    $borderColor = $theme['border_color'] ?? '#e2e8f0';
@endphp

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $bgColor }};">
    <tr>
        <td style="padding: 24px 20px;" class="mobile-padding">
            @if(!empty($heading))
                <h3 style="margin: 0 0 6px 0; font-size: 18px; font-weight: 700; color: {{ $theme['heading_color'] ?? '#0f172a' }};">
                    {{ $heading }}
                </h3>
            @endif

            @if(!empty($subtitle))
                <p style="margin: 0 0 16px 0; font-size: 14px; color: {{ $theme['text_color'] ?? '#64748b' }};">
                    {{ $subtitle }}
                </p>
            @endif

            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse; border: 1px solid {{ $borderColor }}; border-radius: 6px; overflow: hidden; font-size: 13px;">
                @if(!empty($headers))
                    <thead>
                        <tr style="background-color: #f8fafc; border-bottom: 2px solid {{ $borderColor }};">
                            @foreach($headers as $th)
                                <th align="left" style="padding: 10px 12px; font-weight: 700; color: {{ $theme['heading_color'] ?? '#0f172a' }};">
                                    {{ $th }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                @endif
                <tbody>
                    @foreach($rows as $idx => $row)
                        @php
                            $cells = is_array($row) ? ($row['cells'] ?? $row) : [];
                            $rowBg = ($idx % 2 === 1) ? '#f8fafc' : '#ffffff';
                        @endphp
                        <tr style="background-color: {{ $rowBg }}; border-bottom: 1px solid {{ $borderColor }};">
                            @foreach($cells as $cellIdx => $cell)
                                <td style="padding: 10px 12px; color: {{ $cellIdx === 0 ? ($theme['heading_color'] ?? '#0f172a') : ($theme['text_color'] ?? '#475569') }}; font-weight: {{ $cellIdx === 0 ? '600' : 'normal' }};">
                                    {{ $cell }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </td>
    </tr>
</table>
