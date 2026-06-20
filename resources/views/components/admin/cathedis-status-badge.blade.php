@props(['order', 'label' => null, 'compact' => false])

@php
    $colors = [
        'gray' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
        'blue' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
        'indigo' => 'bg-brand-100 text-brand-700 dark:bg-brand-900/50 dark:text-brand-300',
        'yellow' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300',
        'cyan' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/50 dark:text-cyan-300',
        'green' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
        'red' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
        'orange' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300',
    ];
    $badgeClass = $colors[$order->cathedisStatusColor()] ?? $colors['gray'];
    $syncTitle = $order->cathedis_status_synced_at
        ? 'Synchronisé le '.$order->cathedis_status_synced_at->format('d/m/Y H:i')
        : null;
@endphp

@if($order->hasCathedisTracking())
    @if($compact)
        @if($order->cathedisStatusDisplay())
            <span @class(['inline-flex max-w-[12rem] items-center px-2.5 py-0.5 rounded-full text-xs font-medium', $badgeClass]) @if($syncTitle) title="{{ $syncTitle }}" @endif>
                {{ $order->cathedisStatusDisplay() }}
            </span>
        @else
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                Non synchronisé
            </span>
        @endif
    @else
        <div {{ $attributes->merge(['class' => 'inline-flex flex-col items-start gap-0.5']) }}>
            @if($label)
                <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $label }}</span>
            @endif
            @if($order->cathedisStatusDisplay())
                <span @class(['inline-flex max-w-full items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-left', $badgeClass])>
                    {{ $order->cathedisStatusDisplay() }}
                </span>
                @if($order->cathedis_status_synced_at)
                    <span class="text-[10px] text-slate-400">{{ $order->cathedis_status_synced_at->format('d/m/Y H:i') }}</span>
                @endif
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                    Non synchronisé
                </span>
            @endif
        </div>
    @endif
@else
    <span {{ $attributes->merge(['class' => 'text-slate-400 text-xs']) }}>—</span>
@endif
