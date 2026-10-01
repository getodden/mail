@php
    $heading = $data['heading'] ?? null;
    $stats = $data['stats'] ?? [];
    $bgColor = $data['bg_color'] ?? '#f1f5f9';
    $statCount = count($stats);
    $columnWidth = $statCount > 0 ? (int) floor(100 / $statCount) : 100;
@endphp

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $bgColor }};">
    <tr>
        <td style="padding: 28px 32px; text-align: center;" class="mobile-padding">
            @if(!empty($heading))
                <div style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 20px;">
                    {{ $heading }}
                </div>
            @endif

            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    @foreach($stats as $stat)
                        @php
                            $value = $stat['value'] ?? '0';
                            $label = $stat['label'] ?? '';
                            $change = $stat['change'] ?? null;
                        @endphp
                        <td align="center" valign="top" width="{{ $columnWidth }}%" class="stack-column" style="padding: 8px 12px;">
                            <div style="font-size: 32px; font-weight: 800; line-height: 1.1; color: {{ $theme['primary_color'] ?? '#2563eb' }}; letter-spacing: -0.5px;">
                                {{ $value }}
                            </div>
                            <div style="font-size: 13px; font-weight: 600; color: {{ $theme['heading_color'] ?? '#0f172a' }}; margin-top: 6px;">
                                {{ $label }}
                            </div>
                            @if(!empty($change))
                                <div style="display: inline-block; font-size: 11px; font-weight: 700; color: #16a34a; background-color: #dcfce7; padding: 2px 8px; border-radius: 9999px; margin-top: 6px;">
                                    {{ $change }}
                                </div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            </table>
        </td>
    </tr>
</table>
