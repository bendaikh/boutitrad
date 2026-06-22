@props(['title', 'section'])

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h3 class="admin-section-title">{{ $title }}</h3>
    <div class="flex flex-wrap items-center gap-2">
        <x-admin.action-btn
            :href="route('reports.section.print', $section)"
            target="_blank"
            icon="print"
            label="Imprimer"
            variant="muted"
        />
        <x-admin.action-btn
            :href="route('reports.section.export.pdf', $section)"
            icon="print"
            label="Exporter PDF"
            variant="default"
        />
    </div>
</div>
