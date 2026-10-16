<?php
declare(strict_types=1);

namespace Plugins\MagixFiles\src;

/*
 * This file is part of MAGIX CMS.
 * Copyright (C) 2008 - 2026 Gerits Aurelien (Magix CMS).
 * License: GPLv3
 */

use App\Backend\Controller\BaseController;
use Plugins\MagixFiles\db\FilesDb;
use Magepattern\Component\HTTP\Request;
use Magepattern\Component\Tool\SmartyTool;
use Magepattern\Component\Tool\FormTool;
use App\Component\Cache\CacheManager;
use App\Component\File\FileUploadTool;

class BackendController extends BaseController
{
    public function run(): void
    {
        // 1. Définition des dossiers
        $pluginDir = ROOT_DIR . 'plugins' . DS . 'MagixFiles' . DS;

        // 2. Enregistrement du dossier des templates
        SmartyTool::addTemplateDir('admin', $pluginDir . 'views' . DS . 'admin');
        // 3. Chargement du fichier de config avec le chemin ABSOLU
        // On récupère la langue actuelle (par défaut 'fr' si non définie)
        $locale = $_SESSION['locale'] ?? 'fr';
        $confPath = $pluginDir . 'i18n' . DS . 'admin' . DS . $locale . '.conf';

        if (file_exists($confPath)) {
            $this->view->configLoad($confPath);
        } else {
            // Optionnel : Loguer si le fichier de langue est manquant
            error_log("Fichier de conf manquant : " . $confPath);
        }

        $action = $_GET['action'] ?? 'index';

        if (method_exists($this, $action)) {
            $this->$action();
        } else {
            $this->index();
        }
    }

    private function index(): void
    {
        $this->view->display('index.tpl');
    }

    // ==========================================
    // MÉTHODES DU WIDGET (AJAX)
    // ==========================================

    public function loadList(): void
    {
        if (ob_get_length()) ob_clean();

        $itemType = $_GET['module'] ?? '';
        $itemId   = (int)($_GET['id_module'] ?? 0);

        if (empty($itemType) || $itemId === 0) {
            echo '<div class="alert alert-warning">Paramètres du module manquants.</div>';
            return;
        }

        $db = new FilesDb();
        $files = $db->fetchFilesByItem($itemType, $itemId);

        // 1. Formatage et Traduction des valeurs (Lignes du tableau)
        foreach ($files as &$f) {
            $f['file_ext_badge'] = strtoupper($f['file_ext']);
            $f['file_size_human'] = $this->formatBytes((int)$f['file_size']);

            // On traduit "public", "private" ou "draft" via le fichier .conf
            $rawVis = $f['visibility_file'];
            $translatedVis = $this->view->getConfigVars($rawVis);
            $f['visibility_file'] = $translatedVis ? $translatedVis : ucfirst($rawVis);
        }

        // 2. Traduction des Titres des Colonnes (En-têtes du tableau)
        // On récupère la traduction, et on laisse un "Fallback" en français au cas où le .conf n'existe pas
        $columns = [
            'title_file'      => ['title' => $this->view->getConfigVars('title_file') ?: 'Titre du fichier', 'type' => 'text', 'class' => 'fw-bold text-dark'],
            'file_ext_badge'  => ['title' => $this->view->getConfigVars('format') ?: 'Format', 'type' => 'text', 'class' => 'text-center font-monospace small'],
            'file_size_human' => ['title' => $this->view->getConfigVars('weight') ?: 'Poids', 'type' => 'text', 'class' => 'text-center text-muted small'],
            'visibility_file' => ['title' => $this->view->getConfigVars('visibility') ?: 'Visibilité', 'type' => 'text', 'class' => 'text-center'],
            'downloads_count' => ['title' => $this->view->getConfigVars('downloads') ?: 'Téléch.', 'type' => 'text', 'class' => 'text-center small']
        ];

        $this->view->assign([
            'files'        => $files,
            'ajax_columns' => $columns,
            'item_type'    => $itemType,
            'item_id'      => $itemId,
            'hashtoken'    => $this->session->getToken()
        ]);

        $this->view->display('ajax/manager.tpl');
    }

    public function upload(): void
    {
        if (ob_get_length()) ob_clean();

        $token = Request::isPost('hashtoken') ? $_POST['hashtoken'] : '';
        if (!$this->session->validateToken($token)) {
            $this->jsonResponse(false, 'Session expirée.');
        }

        $itemType    = FormTool::simpleClean($_POST['module_mf'] ?? '');
        $itemId      = (int)($_POST['id_module'] ?? 0);
        $visibility  = FormTool::simpleClean($_POST['file_visibility'] ?? 'private');
        $customTitle = FormTool::simpleClean($_POST['file_title'] ?? '');

        if (empty($itemType) || $itemId === 0) {
            $this->jsonResponse(false, 'Les références du module sont obligatoires.');
        }

        if (!in_array($visibility, ['public', 'private', 'draft'], true)) {
            $visibility = 'private';
        }

        $finalTitle = !empty($customTitle)
            ? $customTitle
            : ucwords(str_replace(['_', '-'], ' ', pathinfo($_FILES['file']['name'] ?? 'Document', PATHINFO_FILENAME)));

        $uploadTool = new FileUploadTool();
        // Stockage dans /upload/magixfiles/{itemType}/{itemId}/ avec protection .htaccess
        $uploadResult = $uploadTool->singleFileUpload(
            'upload',
            ['magixfiles', $itemType, (string)$itemId],
            [
                'name'        => $finalTitle,
                'protect_dir' => true // Bloque l'accès HTTP direct pour protéger les fichiers privés
            ]
        );

        if (!$uploadResult['status']) {
            $this->jsonResponse(false, $uploadResult['msg']);
        }

        $db = new FilesDb();
        $inserted = $db->insertFile([
            'item_type'       => $itemType,
            'item_id'         => $itemId,
            'title_file'      => $finalTitle,
            'file_name'       => $uploadResult['file'],
            'file_ext'        => $uploadResult['ext'],
            'file_size'       => $uploadResult['size'],
            'visibility_file' => $visibility
        ]);

        if ($inserted) {
            CacheManager::clearFrontend('magixfiles');
            $this->jsonResponse(true, 'Fichier transféré et lié avec succès.');
        } else {
            $uploadTool->removeLocalFile($uploadResult['full_path']);
            $this->jsonResponse(false, 'Erreur lors de l\'enregistrement en base de données.');
        }
    }

    public function delete(): void
    {
        if (ob_get_length()) ob_clean();

        $token = Request::isPost('hashtoken') ? $_POST['hashtoken'] : '';
        if (!$this->session->validateToken($token)) {
            $this->jsonResponse(false, 'Session expirée.');
        }

        $idFile = (int)($_POST['id_file_item'] ?? 0);

        if ($idFile > 0) {
            $db = new FilesDb();
            $file = $db->getFileById($idFile);

            if ($file) {
                $filePath = ROOT_DIR . 'upload' . DS . 'magixfiles' . DS . $file['item_type'] . DS . $file['item_id'] . DS . $file['file_name'];
                $uploadTool = new FileUploadTool();
                $uploadTool->removeLocalFile($filePath);

                if ($db->deleteFile($idFile)) {
                    CacheManager::clearFrontend('magixfiles');
                    $this->jsonResponse(true, 'Fichier supprimé avec succès.');
                }
            }
        }
        $this->jsonResponse(false, 'Impossible de supprimer ce fichier.');
    }

    public function reorder(): void
    {
        if (ob_get_length()) ob_clean();

        $token = Request::isPost('hashtoken') ? $_POST['hashtoken'] : '';
        if (!$this->session->validateToken($token)) {
            $this->jsonResponse(false, 'Session expirée.');
        }

        $orderedIds = $_POST['ids'] ?? [];

        if (!empty($orderedIds) && is_array($orderedIds)) {
            $db = new FilesDb();
            if ($db->reorderFiles($orderedIds)) {
                CacheManager::clearFrontend('magixfiles');
                $this->jsonResponse(true, 'Ordre mis à jour.');
            }
        }
        $this->jsonResponse(false, 'Erreur lors du tri.');
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, ',', ' ') . ' Mo';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 0, ',', ' ') . ' Ko';
        }
        return $bytes . ' o';
    }
}