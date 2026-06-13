<div class="flex flex-wrap items-center gap-2 shrink-0 pb-0.5">
    <x-admin.action-btn
        type="submit"
        form="client-form"
        icon="save"
        label="Enregistrer"
        variant="success"
        x-bind:disabled="!formActive"
    />
    <x-admin.action-btn
        icon="edit"
        label="Modifier"
        x-bind:disabled="!selectedId"
        @click="selectedId && (window.location.href = editUrl + (editUrl.includes('?') ? '&' : '?') + 'edit=' + selectedId)"
    />
    <x-admin.action-btn
        icon="delete"
        label="Supprimer"
        variant="danger"
        x-bind:disabled="!selectedId"
        @click="if (selectedId && confirm('Supprimer ce client ?')) { document.getElementById('client-delete-form').submit(); }"
    />
    <x-admin.action-btn
        icon="print"
        label="Imprimer"
        x-bind:disabled="!selectedId"
        @click="selectedId && window.open(printUrl + '/' + selectedId + '/print', '_blank')"
    />
</div>
