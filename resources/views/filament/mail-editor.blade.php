@php
    $key = $getKey();
    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    {{-- wire:ignore: the editor draws its own DOM, which Livewire must not morph. --}}
    <div
        wire:ignore
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('odden-mail-editor', 'getodden/mail') }}"
        x-data="oddenMailEditorField({
            state: $wire.$entangle(@js($statePath)),
            key: @js($key),
            schema: @js(\Odden\MailBuilder\Schema\SlotSchemaRegistry::toArray()),
            disabled: @js($isDisabled()),
        })"
    >
        <odden-mail-editor x-ref="editor"></odden-mail-editor>
    </div>
</x-dynamic-component>
