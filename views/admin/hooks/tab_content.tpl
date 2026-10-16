<div class="tab-pane fade" id="magix-files-pane" role="tabpanel" aria-labelledby="magix-files-tab" tabindex="0">
    <div class="card shadow-sm border-0 mt-3">
        <div class="card-body">

            {* VUE 1 : LA ZONE AJAX (Liste) *}
            <div id="magix-files-app" data-module="{$mf_module}" data-id="{$mf_id_module}">
                <div class="text-center py-5 text-muted">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <p>Chargement des fichiers...</p>
                </div>
            </div>

            {* VUE 2 : LE FORMULAIRE D'UPLOAD *}
            <div id="mf_view_form" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                    <h5 class="mb-0 text-primary" id="mf_form_title">
                        <i class="bi bi-cloud-arrow-up me-2"></i>Uploader un fichier (Document ou Audio)
                    </h5>
                    <div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="mfApp.showList()">
                            <i class="bi bi-arrow-left"></i> Retour à la liste
                        </button>
                    </div>
                </div>

                <form id="mf_form_element" enctype="multipart/form-data">
                    <input type="hidden" id="mf_id_file_item" name="id_file_item" value="0">

                    {* Champ Titre Optionnel *}
                    <div class="mb-4">
                        <label for="mf_file_title" class="form-label fw-bold"><i class="bi bi-type-h1 me-2"></i>Titre du fichier</label>
                        <input type="text" class="form-control" name="file_title" id="mf_file_title" placeholder="Ex: Guide pratique PDF, Tableau Excel ou Audio MP3">
                        <div class="form-text text-muted">
                            Laissez vide pour utiliser automatiquement le nom du fichier envoyé.
                        </div>
                    </div>

                    {* Fichier Source *}
                    <div class="bg-light p-4 rounded border mb-4">
                        <label class="form-label fw-bold"><i class="bi bi-file-earmark-arrow-up me-2"></i>Fichier source</label>
                        <input class="form-control form-control-lg" type="file" name="file" id="mf_file_input" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.odt,.ods,.zip,.mp3,.wav,.ogg">
                        <div class="form-text text-muted mt-2">
                            Formats acceptés : PDF, Word (DOC/DOCX), Excel (XLS/XLSX/CSV), PowerPoint, ZIP, Audio (MP3, WAV, OGG).
                        </div>
                    </div>

                    {* Visibilité *}
                    <div class="mb-4">
                        <label class="form-label fw-bold"><i class="bi bi-shield-lock me-2"></i>Niveau de visibilité</label>
                        <div class="d-flex gap-4 mt-2 p-3 border rounded bg-white">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="file_visibility" id="mf_vis_private" value="private" checked>
                                <label class="form-check-label fw-medium text-success" for="mf_vis_private">Privé (Tunnel de vente / Achat requis)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="file_visibility" id="mf_vis_public" value="public">
                                <label class="form-check-label text-dark" for="mf_vis_public">Public (Téléchargement libre)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="file_visibility" id="mf_vis_draft" value="draft">
                                <label class="form-check-label text-muted" for="mf_vis_draft">Brouillon</label>
                            </div>
                        </div>
                    </div>
                </form>

                <hr class="my-4">
                <div class="d-flex justify-content-end align-items-center">
                    <div id="mf_loading_indicator" class="text-primary me-4 fw-bold" style="display: none;">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Upload et sécurisation du fichier en cours...
                    </div>

                    <button type="button" class="btn btn-secondary me-2 px-4" id="mf_btn_cancel" onclick="mfApp.showList()">Annuler</button>
                    <button type="button" class="btn btn-primary px-5" id="mf_btn_save" onclick="mfApp.save()">
                        <i class="bi bi-cloud-upload me-2"></i> Transférer
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

{block name="javascripts" append}
    <script>
        {literal}
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof MagixAjaxManager !== 'undefined') {

                // Initialisation avec préfixe "mf" et suffixe "file_item"
                window.mfApp = new MagixAjaxManager(
                    'magix-files-app',
                    'magix-files-tab',
                    'MagixFiles',
                    'mf',
                    'file_item'
                );

                // Surcharge de addItem pour vider les champs
                const originalAddItem = window.mfApp.addItem.bind(window.mfApp);
                window.mfApp.addItem = function() {
                    originalAddItem();
                    document.getElementById('mf_file_input').value = '';
                    document.getElementById('mf_file_title').value = '';
                };

                // Surcharge de save() pour gérer l'upload et cibler l'action "upload"
                window.mfApp.save = function() {
                    const formElement = document.getElementById('mf_form_element');
                    const fileInput = document.getElementById('mf_file_input');

                    if (!fileInput.files.length) {
                        MagixToast.error("Veuillez sélectionner un fichier à transférer.");
                        return;
                    }

                    // Vérification stricte du poids du fichier côté client (200 Mo)
                    const maxFileSize = 200 * 1024 * 1024;
                    if (fileInput.files[0].size > maxFileSize) {
                        MagixToast.error("Le fichier est trop volumineux. La limite est de 200 Mo.");
                        fileInput.value = '';
                        return;
                    }

                    // UI : Activation du loader
                    const btnSave = document.getElementById('mf_btn_save');
                    const btnCancel = document.getElementById('mf_btn_cancel');
                    const loader = document.getElementById('mf_loading_indicator');

                    btnSave.disabled = true;
                    btnCancel.disabled = true;
                    loader.style.display = 'block';

                    const formData = new FormData(formElement);
                    const tokenInput = document.getElementById('mf_hashtoken');

                    formData.append('hashtoken', tokenInput.value);
                    formData.append('module_mf', this.container.dataset.module);
                    formData.append('id_module', this.container.dataset.id);

                    fetch(`index.php?controller=${this.controllerName}&action=upload`, {
                        method: 'POST',
                        body: formData
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status || data.success) {
                                MagixToast.success(data.message || 'Fichier uploadé avec succès.');
                                this.showList();
                                this.loadList();
                            } else {
                                MagixToast.error(data.message || 'Erreur lors de la sauvegarde.');
                            }
                        })
                        .catch(error => {
                            MagixToast.error("Une erreur réseau est survenue lors de l'upload.");
                        })
                        .finally(() => {
                            btnSave.disabled = false;
                            btnCancel.disabled = false;
                            loader.style.display = 'none';
                            fileInput.value = '';
                        });
                };
            }
        });
        {/literal}
    </script>
{/block}