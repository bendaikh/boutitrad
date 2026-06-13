<div class="flex flex-wrap items-center gap-2">
    <x-admin.action-btn
        type="submit"
        icon="save"
        label="Valider"
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
        icon="print"
        label="Imprimer"
        x-bind:disabled="!selectedId"
        @click="selectedId && window.open(printUrl + '/' + selectedId + '/print', '_blank')"
    />
    <x-admin.action-btn
        type="button"
        icon="delete"
        label="Supprimer"
        variant="danger"
        x-bind:disabled="!selectedId"
        @click="deleteProduct()"
    />
</div>
