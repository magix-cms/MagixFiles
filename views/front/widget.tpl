{if !empty($mf_widget_data.items)}
    <div class="magix-files-widget my-3">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">
                    {if $mf_widget_data.visibility == 'private'}
                        <i class="bi bi-folder-check text-success me-2"></i> {#your_downloadable_files#}
                    {else}
                        <i class="bi bi-file-earmark-arrow-down text-primary me-2"></i> {#attached_documents#}
                    {/if}
                </h6>
            </div>
            <ul class="list-group list-group-flush">
                {foreach from=$mf_widget_data.items item=file}
                    <li class="list-group-item py-3 px-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div>
                                <span class="badge {if $mf_widget_data.visibility == 'private'}bg-success{else}bg-secondary{/if} text-uppercase me-2">
                                    {$file.file_ext}
                                </span>
                                <span class="fw-bold text-dark">{$file.title_file}</span>
                                <small class="text-muted ms-2">({($file.file_size / 1024)|number_format:0:',':' '} Ko)</small>
                            </div>

                            <div class="d-flex align-items-center gap-3">
                                {if in_array($file.file_ext, ['mp3', 'wav', 'ogg'])}
                                    <audio controls preload="none" {if $mf_widget_data.visibility == 'private'}controlsList="nodownload"{/if} style="height: 36px;">
                                        <source src="{$mf_widget_data.action_url}?action=stream&id={$file.id_file_item}" type="audio/mpeg">
                                    </audio>
                                {/if}

                                <a href="{$mf_widget_data.action_url}?action=download&id={$file.id_file_item}" class="btn btn-sm {if $mf_widget_data.visibility == 'private'}btn-primary{else}btn-outline-primary{/if} fw-bold">
                                    <i class="bi bi-download me-1"></i> {#download#}
                                </a>
                            </div>
                        </div>
                    </li>
                {/foreach}
            </ul>
        </div>
    </div>
{/if}