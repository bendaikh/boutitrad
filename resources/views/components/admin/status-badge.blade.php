@props(['status'])

@php
$colors = [
    'gray' => 'bg-slate-100 text-slate-700',
    'blue' => 'bg-blue-100 text-blue-700',
    'indigo' => 'bg-indigo-100 text-indigo-700',
    'yellow' => 'bg-yellow-100 text-yellow-700',
    'cyan' => 'bg-cyan-100 text-cyan-700',
    'green' => 'bg-emerald-100 text-emerald-700',
    'red' => 'bg-red-100 text-red-700',
    'orange' => 'bg-orange-100 text-orange-700',
];
$color = $colors[$status->color()] ?? $colors['gray'];
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $color }}">
    {{ $status->label() }}
</span>
