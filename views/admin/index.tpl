{*
 * This file is part of MAGIX CMS.
 * Copyright (C) 2008 - 2026 Gerits Aurelien (Magix CMS).
 * License: GPLv3
 *}
{extends file="layout.tpl"}

{block name='head:title'}MagixFiles - Gestionnaire de fichiers{/block}

{block name='article'}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-paperclip me-2 text-primary"></i> Plugin MagixFiles
        </h1>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="m-0 fw-bold text-primary">Fonctionnement du module de fichiers joints</h6>
        </div>

        <div class="card-body p-4">
            <div class="alert alert-info mb-4">
                <i class="bi bi-info-circle me-2"></i> Ce plugin ne nécessite aucune clé d'API externe. Les fichiers sont stockés et protégés localement dans le dossier <code>/upload/magixfiles/</code>.
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border h-100">
                        <h6 class="fw-bold text-dark"><i class="bi bi-globe2 text-primary me-2"></i>Fichiers Publics</h6>
                        <p class="small text-muted mb-0">
                            Accessibles librement par tous les visiteurs sur les fiches produits, pages CMS, actualités ou catégories activées.
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border h-100">
                        <h6 class="fw-bold text-success"><i class="bi bi-shield-lock me-2"></i>Fichiers Privés (E-commerce)</h6>
                        <p class="small text-muted mb-0">
                            Protégés par un <code>.htaccess</code> et accessibles uniquement aux clients connectés ayant acheté le produit via <strong>Magixcartpay</strong> (statut de commande <code>paid</code>).
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
{/block}