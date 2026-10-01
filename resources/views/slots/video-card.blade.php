@php
    $videoUrl = $data['video_url'] ?? 'https://www.youtube.com';
    $thumbnailUrl = $data['thumbnail_url'] ?? 'https://images.unsplash.com/photo-1536240478700-b869070f9279?auto=format&fit=crop&w=1200&q=80';
    $title = $data['title'] ?? 'Watch Product Tour & Feature Walkthrough';
    $subtitle = $data['subtitle'] ?? '3 min overview of the new platform capabilities';
    $badge = $data['badge'] ?? 'VIDEO DEMO';
    $borderRadius = $data['border_radius'] ?? '12px';
@endphp

<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: {{ $theme['content_background_color'] ?? '#ffffff' }};">
    <tr>
        <td style="padding: 24px 32px;" class="mobile-padding">
            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #0f172a; border-radius: {{ $borderRadius }}; overflow: hidden; border: 1px solid #1e293b;">
                <tr>
                    <td align="center" style="position: relative; padding: 48px 24px; background-image: url('{{ $thumbnailUrl }}'); background-size: cover; background-position: center; background-repeat: no-repeat;">
                        <!-- Dark overlay backdrop for readable text and play icon contrast -->
                        <div style="background-color: rgba(15, 23, 42, 0.7); padding: 32px 16px; border-radius: 8px;">
                            @if(!empty($badge))
                                <span style="display: inline-block; background-color: rgba(37, 99, 235, 0.9); color: #ffffff; font-size: 11px; font-weight: 700; letter-spacing: 1px; padding: 4px 10px; border-radius: 9999px; text-transform: uppercase; margin-bottom: 16px;">
                                    {{ $badge }}
                                </span>
                            @endif

                            <!-- Play Button Link -->
                            <div style="margin: 8px 0 16px 0;">
                                <a href="{{ $videoUrl }}" style="text-decoration: none; display: inline-block;">
                                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td align="center" style="width: 56px; height: 56px; background-color: #ffffff; border-radius: 50%; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
                                                <span style="display: block; width: 0; height: 0; border-top: 10px solid transparent; border-bottom: 10px solid transparent; border-left: 16px solid #2563eb; margin-left: 4px;"></span>
                                            </td>
                                        </tr>
                                    </table>
                                </a>
                            </div>

                            <h3 style="margin: 0 0 6px 0; font-size: 18px; font-weight: 700; color: #ffffff; line-height: 1.3;">
                                <a href="{{ $videoUrl }}" style="color: #ffffff; text-decoration: none;">
                                    {{ $title }}
                                </a>
                            </h3>

                            @if(!empty($subtitle))
                                <p style="margin: 0; font-size: 13px; color: #cbd5e1; line-height: 1.4;">
                                    {{ $subtitle }}
                                </p>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
