@props([
    'minWidth' => null,
    'compact' => false,
])

@php
    $minWidthClass = $minWidth ? "min-w-[{$minWidth}]" : '';
    $tableClass = 'admin-table admin-table-scroll'.($compact ? ' admin-table-compact' : '');
@endphp

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm flex flex-col overflow-hidden min-h-0']) }}>
    @isset($header)
        <div class="shrink-0 px-5 py-4 border-b border-slate-100 dark:border-slate-700 font-semibold text-slate-800 dark:text-slate-100">
            {{ $header }}
        </div>
    @endisset

    <div class="flex-1 min-h-0 overflow-auto">
        <table class="{{ $tableClass }} {{ $minWidthClass }}">
            {{ $slot }}
        </table>
    </div>

    @isset($footer)
        <div class="shrink-0 px-5 py-3 border-t border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-900">
            {{ $footer }}
        </div>
    @endisset
</div>
