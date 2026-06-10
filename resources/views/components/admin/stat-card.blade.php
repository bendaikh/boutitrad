@props(['label', 'value', 'color' => 'indigo', 'icon' => null, 'compact' => false])

@php
$colors = [
    'indigo' => 'bg-indigo-50 text-indigo-600',
    'emerald' => 'bg-emerald-50 text-emerald-600',
    'amber' => 'bg-amber-50 text-amber-600',
    'rose' => 'bg-rose-50 text-rose-600',
    'cyan' => 'bg-cyan-50 text-cyan-600',
    'purple' => 'bg-purple-50 text-purple-600',
    'blue' => 'bg-blue-50 text-blue-600',
];
@endphp

<div class="bg-white rounded-lg border border-slate-200 {{ $compact ? 'p-3' : 'p-5' }} shadow-sm">
    <div class="flex items-center justify-between gap-2">
        <div class="min-w-0">
            <p class="{{ $compact ? 'text-xs' : 'text-sm' }} text-slate-500 font-medium truncate">{{ $label }}</p>
            <p class="{{ $compact ? 'text-lg' : 'text-2xl' }} font-bold text-slate-900 mt-0.5 truncate">{{ $value }}</p>
        </div>
        @if($icon)
            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 {{ $colors[$color] ?? $colors['indigo'] }}">
                {!! $icon !!}
            </div>
        @endif
    </div>
</div>
