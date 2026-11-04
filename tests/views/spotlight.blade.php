<div class="spotlight" data-theme-primary="{{ $theme['primary_color'] ?? 'none' }}" data-slot-key="{{ $slot->key() }}">
    <h2>{{ $data['headline'] ?? 'Untitled' }}</h2>
    <p>{{ $data['blurb'] ?? '' }}</p>
</div>
