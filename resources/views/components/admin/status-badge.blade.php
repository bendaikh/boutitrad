@props(['status'])

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
$color = $colors[$status->color()] ?? $colors['gray'];
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $color }}">
    {{ $status->label() }}
</span>
