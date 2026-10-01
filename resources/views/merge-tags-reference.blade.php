@php
    $registry = app(\DoPHP\MailBuilder\MergeTags\MergeTagRegistry::class);
    $categories = $registry->all();
    $sampleContext = $registry->sampleContext();
@endphp

<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900" x-data="{ copied: null }">
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-4 dark:border-slate-800">
        <div>
            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <span>Personalization Tokens & Merge Tags</span>
                <span class="inline-flex items-center rounded-full bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 dark:bg-sky-950 dark:text-sky-300">
                    Live Dynamic Replacement
                </span>
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Click any tag below to copy it. Tokens automatically resolve to recipient data during campaign delivery.
            </p>
        </div>
        <div x-show="copied" x-transition.opacity class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2.5 py-1 rounded-md border border-emerald-200 dark:border-emerald-800">
            Copied <span x-text="copied"></span> to clipboard!
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($categories as $categoryName => $tags)
            <div class="rounded-lg border border-slate-100 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-800/40">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3 flex items-center gap-1.5">
                    @if ($categoryName === 'Contact')
                        <x-filament::icon icon="heroicon-m-user" class="w-3.5 h-3.5 text-sky-500" />
                    @elseif ($categoryName === 'Company')
                        <x-filament::icon icon="heroicon-m-building-office" class="w-3.5 h-3.5 text-indigo-500" />
                    @elseif ($categoryName === 'Sender / Owner')
                        <x-filament::icon icon="heroicon-m-paper-airplane" class="w-3.5 h-3.5 text-emerald-500" />
                    @else
                        <x-filament::icon icon="heroicon-m-shield-check" class="w-3.5 h-3.5 text-amber-500" />
                    @endif
                    <span>{{ $categoryName }}</span>
                </div>

                <div class="space-y-2">
                    @foreach ($tags as $tag => $description)
                        <div class="flex items-center justify-between text-xs bg-white dark:bg-slate-900/80 p-2 rounded border border-slate-200/80 dark:border-slate-700/60 hover:border-sky-300 dark:hover:border-sky-600 transition group">
                            <button
                                type="button"
                                @click="navigator.clipboard.writeText('{{ $tag }}'); copied = '{{ $tag }}'; setTimeout(() => copied = null, 2500)"
                                class="font-mono font-semibold text-sky-600 dark:text-sky-400 hover:text-sky-800 dark:hover:text-sky-200 text-left flex items-center gap-1.5"
                                title="Click to copy {{ $tag }}"
                            >
                                <span>{{ $tag }}</span>
                                <x-filament::icon icon="heroicon-m-clipboard-document" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition text-slate-400" />
                            </button>
                            <span class="text-slate-500 dark:text-slate-400 text-[11px] truncate max-w-[200px]" title="{{ $description }}">
                                {{ $description }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
