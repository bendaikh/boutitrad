@props(['label', 'value', 'color' => 'brand', 'icon' => null, 'compact' => false])

@php
$iconStyles = [
    'brand' => ['bg' => 'bg-brand-50 text-brand-600 dark:bg-brand-900/40 dark:text-brand-300', 'bar' => 'bg-brand-500'],
    'indigo' => ['bg' => 'bg-brand-50 text-brand-600 dark:bg-brand-900/40 dark:text-brand-300', 'bar' => 'bg-brand-500'],
    'blue' => ['bg' => 'bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-300', 'bar' => 'bg-blue-500'],
    'emerald' => ['bg' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300', 'bar' => 'bg-emerald-500'],
    'amber' => ['bg' => 'bg-amber-50 text-amber-600 dark:bg-amber-900/40 dark:text-amber-300', 'bar' => 'bg-amber-500'],
    'rose' => ['bg' => 'bg-red-50 text-red-600 dark:bg-red-900/40 dark:text-red-300', 'bar' => 'bg-red-500'],
    'cyan' => ['bg' => 'bg-teal-50 text-teal-600 dark:bg-teal-900/40 dark:text-teal-300', 'bar' => 'bg-teal-500'],
    'purple' => ['bg' => 'bg-purple-50 text-purple-600 dark:bg-purple-900/40 dark:text-purple-300', 'bar' => 'bg-purple-500'],
];
$style = $iconStyles[$color] ?? $iconStyles['brand'];
@endphp

<div class="admin-card overflow-hidden flex flex-col">
    <div class="{{ $compact ? 'p-4' : 'p-5' }} flex-1">
        <div class="flex items-start justify-between gap-2">
            @if($icon)
                <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 {{ $style['bg'] }}">
                    {!! $icon !!}
                </div>
            @else
                <div></div>
            @endif
            @if(isset($trend))
                <span class="text-xs font-semibold {{ $trendUp ?? true ? 'text-emerald-500' : 'text-red-500' }}">{{ $trend }}</span>
            @endif
        </div>
        <p class="{{ $compact ? 'text-[10px] mt-3' : 'text-xs mt-4' }} text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider">{{ $label }}</p>
        <p class="{{ $compact ? 'text-xl' : 'text-2xl' }} font-bold text-slate-800 dark:text-slate-100 mt-1 truncate">{{ $value }}</p>
    </div>
    <div class="h-1 {{ $style['bar'] }}"></div>
</div>
