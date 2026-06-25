@props([
    'title',
    'section',
    'showDateFilter' => false,
    'dateFrom' => null,
    'dateTo' => null,
])

@php
    $exportParams = $showDateFilter
        ? array_filter(['sales_from' => $dateFrom, 'sales_to' => $dateTo], fn ($v) => filled($v))
        : [];
    $hasDateFilter = filled($dateFrom) || filled($dateTo);
@endphp

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h3 class="admin-section-title">{{ $title }}</h3>
    <div class="flex flex-wrap items-end gap-2">
        @if($showDateFilter)
            <form method="GET" action="{{ route('reports.index') }}#ventes" class="flex flex-wrap items-end gap-2">
                <div class="flex flex-col">
                    <label for="sales_from" class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-0.5">De</label>
                    <input type="date" id="sales_from" name="sales_from" value="{{ $dateFrom }}" class="form-input text-xs py-1.5">
                </div>
                <div class="flex flex-col">
                    <label for="sales_to" class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-0.5">À</label>
                    <input type="date" id="sales_to" name="sales_to" value="{{ $dateTo }}" class="form-input text-xs py-1.5">
                </div>
                <button type="submit" class="px-4 py-1.5 btn-dark text-sm whitespace-nowrap shrink-0">Filtrer</button>
                <a
                    href="{{ route('reports.index') }}#ventes"
                    @class([
                        'inline-flex items-center justify-center w-8 h-8 shrink-0 rounded-lg border transition-colors',
                        'border-slate-300 dark:border-slate-500 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-red-600 dark:hover:text-red-400 hover:border-red-300 dark:hover:border-red-600 hover:bg-red-50 dark:hover:bg-red-900/20' => $hasDateFilter,
                        'border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800/60 text-slate-300 dark:text-slate-600 pointer-events-none' => ! $hasDateFilter,
                    ])
                    title="Annuler le filtre"
                    aria-label="Annuler le filtre"
                    @if(! $hasDateFilter) aria-disabled="true" @endif
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </a>
            </form>
        @endif
        <x-admin.action-btn
            :href="route('reports.section.print', array_merge(['section' => $section], $exportParams))"
            target="_blank"
            icon="print"
            label="Imprimer"
            variant="muted"
        />
        <x-admin.action-btn
            :href="route('reports.section.export.pdf', array_merge(['section' => $section], $exportParams))"
            icon="print"
            label="Exporter PDF"
            variant="default"
        />
    </div>
</div>
