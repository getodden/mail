<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Email Layout Settings
    |--------------------------------------------------------------------------
    |
    | Default styling parameters applied across all rendered email slot templates.
    |
    */
    'defaults' => [
        'container_width' => 600,
        'font_family' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif",
        'background_color' => '#f8fafc',
        'content_background_color' => '#ffffff',
        'text_color' => '#334155',
        'heading_color' => '#0f172a',
        'primary_color' => '#2563eb',
        'border_color' => '#e2e8f0',
        'border_radius' => '8px',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Organization & Unsubscribe Information
    |--------------------------------------------------------------------------
    |
    | Used in the footer slot when dynamic values are omitted.
    |
    */
    'footer' => [
        'company_name' => env('MAIL_BUILDER_COMPANY_NAME', env('APP_NAME', 'Laravel')),
        'address' => env('MAIL_BUILDER_ADDRESS', ''),
        'unsubscribe_text' => 'Unsubscribe or manage your email preferences',
    ],

    /*
    |--------------------------------------------------------------------------
    | Drag-and-Drop Editor
    |--------------------------------------------------------------------------
    |
    | The editor (the <odden-mail-editor> element) loads the slot schema and renders its preview through two routes.
    | They are off by default: turn them on, and put your own authentication and authorization in the middleware.
    | Anyone who can reach them can render email HTML, so never leave them public.
    |
    */
    'editor' => [
        'routes' => [
            'enabled' => (bool) env('MAIL_BUILDER_EDITOR_ROUTES', false),
            'prefix' => env('MAIL_BUILDER_EDITOR_PREFIX', 'mail-builder/editor'),
            'middleware' => ['web'],
        ],
        // The most slots a preview request may render.
        'max_slots' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feed Blocks
    |--------------------------------------------------------------------------
    |
    | The RSS and dynamic feed blocks fetch JSON from a URL stored in the email, when the email is compiled. The address
    | the host resolves to must be a public one: private, loopback and cloud metadata addresses are refused, redirects are
    | not followed, and a response larger than max_bytes is ignored. A refused feed falls back to the block's own items.
    | List a host in allowed_hosts to let it through, for a feed you serve from your own network.
    |
    */
    'feeds' => [
        'allowed_hosts' => [],
        'max_bytes' => 1_048_576,
    ],
];
