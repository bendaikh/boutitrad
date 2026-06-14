@props([
    'icon' => 'save',
    'label' => '',
    'variant' => 'default',
    'href' => null,
    'target' => null,
])

@php
    $variants = [
        'primary' => 'bg-brand-600 text-white hover:bg-brand-700 dark:bg-brand-500 dark:hover:bg-brand-600 border-transparent',
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600 border-transparent',
        'info' => 'bg-sky-600 text-white hover:bg-sky-700 dark:bg-sky-500 dark:hover:bg-sky-600 border-transparent',
        'muted' => 'bg-slate-500 text-white hover:bg-slate-600 dark:bg-slate-600 dark:hover:bg-slate-500 border-transparent',
        'default' => 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700',
        'danger' => 'bg-white dark:bg-slate-800 text-red-600 dark:text-red-400 border-red-200 dark:border-red-800 hover:bg-red-50 dark:hover:bg-red-900/30',
        'danger-solid' => 'bg-red-600 text-white hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 border-transparent',
    ];
    $class = $variants[$variant] ?? $variants['default'];
    $iconOnly = trim((string) $label) === '';
    $tagAttrs = [
        'class' => 'inline-flex items-center '.($iconOnly ? 'px-2.5' : 'gap-1.5 px-3').' py-2 rounded-lg text-sm font-medium border transition-colors disabled:opacity-40 disabled:pointer-events-none '.$class,
    ];
    if ($href) {
        $tagAttrs['href'] = $href;
        if ($target) {
            $tagAttrs['target'] = $target;
        }
    } else {
        $tagAttrs['type'] = $attributes->get('type', 'button');
    }
@endphp

@php
    $mergedAttrs = $attributes->except('type')->merge($tagAttrs);
@endphp

@if($href)
    <a {{ $mergedAttrs }}>
@else
    <button {{ $mergedAttrs }}>
@endif
    @switch($icon)
        @case('save')
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            @break
        @case('edit')
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            @break
        @case('delete')
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            @break
        @case('print')
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            @break
        @case('plus')
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            @break
        @case('cancel')
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            @break
    @endswitch
    @if(!$iconOnly)
        <span class="notranslate" translate="no">{{ $label }}</span>
    @endif
@if($href)
    </a>
@else
    </button>
@endif
