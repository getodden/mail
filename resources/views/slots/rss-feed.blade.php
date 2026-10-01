@php
    $heading = (string) $slot->get('heading', 'Latest Articles & Updates');
    $subtitle = (string) $slot->get('subtitle', 'Stay up to date with our newest insights and publications.');
    /** @var list<array{title?: string, published_at?: string, summary?: string, url?: string, author?: string}> $items */
    $items = $slot->get('items', [
        [
            'title' => 'Designing Resilient Cross-Client Email Systems',
            'published_at' => 'Oct 12, 2026',
            'summary' => 'Deep dive into HTML table layouts, MSO conditional comments, and modern client quirks.',
            'url' => 'https://example.com/blog/resilient-emails',
            'author' => 'Engineering Team',
        ],
        [
            'title' => 'Scaling Transactional Deliverability to Millions',
            'published_at' => 'Oct 05, 2026',
            'summary' => 'Best practices for automated IP warming, SPF/DKIM verification, and feedback loop routing.',
            'url' => 'https://example.com/blog/scaling-deliverability',
            'author' => 'Platform Ops',
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
                        $title = (string) ($item['title'] ?? 'New Article');
                        $date = (string) ($item['published_at'] ?? '');
                        $summary = (string) ($item['summary'] ?? '');
                        $url = (string) ($item['url'] ?? '#');
                        $author = (string) ($item['author'] ?? '');
                    @endphp
                    <tr>
                        <td style="padding-bottom: 20px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F8FAFC; border-left: 4px solid {{ $primaryColor }}; border-radius: 4px; padding: 16px 20px;">
                                <tr>
                                    <td>
                                        @if($date !== '' || $author !== '')
                                            <p style="margin: 0 0 6px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 12px; font-weight: 600; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.5px;">
                                                {{ $date }}@if($date !== '' && $author !== '') &bull; @endif{{ $author }}
                                            </p>
                                        @endif
                                        <h4 style="margin: 0 0 8px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 16px; font-weight: 700; line-height: 1.4;">
                                            <a href="{{ $url }}" target="_blank" style="color: #0F172A; text-decoration: none;">
                                                {{ $title }}
                                            </a>
                                        </h4>
                                        @if($summary !== '')
                                            <p style="margin: 0 0 12px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; line-height: 1.5; color: #475569;">
                                                {{ $summary }}
                                            </p>
                                        @endif
                                        <div>
                                            <a href="{{ $url }}" target="_blank" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 13px; font-weight: 600; color: {{ $primaryColor }}; text-decoration: none;">
                                                Read Article &rarr;
                                            </a>
                                        </div>
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
