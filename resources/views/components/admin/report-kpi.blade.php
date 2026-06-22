@props(['label', 'value', 'color' => 'brand', 'icon' => null])

@php
    $styles = [
        'amber' => [
            'badge' => 'bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-amber-500/30',
            'value' => 'text-amber-700 dark:text-amber-300',
        ],
        'brand' => [
            'badge' => 'bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-brand-500/30',
            'value' => 'text-brand-700 dark:text-brand-300',
        ],
        'rose' => [
            'badge' => 'bg-gradient-to-br from-rose-400 to-rose-600 text-white shadow-rose-500/30',
            'value' => 'text-rose-700 dark:text-rose-300',
        ],
        'emerald' => [
            'badge' => 'bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-emerald-500/30',
            'value' => 'text-emerald-700 dark:text-emerald-300',
        ],
        'blue' => [
            'badge' => 'bg-gradient-to-br from-blue-400 to-blue-600 text-white shadow-blue-500/30',
            'value' => 'text-blue-700 dark:text-blue-300',
        ],
    ];

    $style = $styles[$color] ?? $styles['brand'];
@endphp

<div {{ $attributes->merge(['class' => 'report-kpi-card']) }}>
    <div class="report-kpi-badge {{ $style['badge'] }}">
        @if($icon)
            {!! $icon !!}
        @else
            <span class="w-2.5 h-2.5 rounded-full bg-white/90"></span>
        @endif
    </div>
    <div class="min-w-0 flex-1">
        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 truncate">{{ $label }}</p>
        <p class="text-sm sm:text-base font-bold mt-0.5 leading-tight tabular-nums truncate {{ $style['value'] }}">{{ $value }}</p>
    </div>
</div>
