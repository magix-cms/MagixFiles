<input type="hidden" id="mf_hashtoken" value="{$hashtoken}">
<input type="hidden" id="mf_module" value="{$item_type}">
<input type="hidden" id="mf_id_module" value="{$item_id}">

<div id="mf_view_list">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0 text-gray-800"><i class="bi bi-folder2-open me-2 text-primary"></i>Fichiers joints (Documents & Audio)</h5>
        <button type="button" class="btn btn-primary btn-sm" onclick="mfApp.addItem()">
            <i class="bi bi-upload me-1"></i> Ajouter un fichier
        </button>
    </div>

    {include file="components/ajax-table.tpl"
    data=$files
    id_key="id_file_item"
    columns=$ajax_columns
    sortable=true
    delete_action="mfApp.deleteItem"
    empty_msg="Aucun fichier n'a été lié à cet élément."}
</div>