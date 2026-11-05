<?php
declare(strict_types=1);

namespace Plugins\MagixFiles\src;

/*
 * This file is part of MAGIX CMS.
 * Copyright (C) 2008 - 2026 Gerits Aurelien (Magix CMS).
 * License: GPLv3
 */

use App\Frontend\Controller\BaseController;
use Plugins\MagixFiles\db\FilesFrontDb;
use Magepattern\Component\Tool\SmartyTool;
use Magepattern\Component\HTTP\Session;
use App\Component\Db\PluginDb;

class FrontendController extends BaseController
{
    private static array $targetsCache = [];

    /**
     * Contrôleur de téléchargement et streaming sécurisé (/fr/magixfiles/?action=download&id=X)
     */
    public function run(): void
    {
        $action = $_GET['action'] ?? 'download';
        $idFile = (int)($_GET['id'] ?? 0);

        if ($idFile <= 0) {
            $this->denyAccess('Fichier introuvable.');
        }

        $db = new FilesFrontDb();
        $file = $db->getFileById($idFile);

        if (!$file || $file['visibility_file'] === 'draft') {
            $this->denyAccess('Ce fichier n\'est pas disponible.');
        }

        // Vérification stricte d'accès si le fichier est privé
        if ($file['visibility_file'] === 'private') {
            if (!self::userHasAccessToPrivateItem($file['item_type'], (int)$file['item_id'])) {
                $this->denyAccess('Accès réservé aux clients ayant acquis ce contenu.');
            }
        }

        $filePath = ROOT_DIR . 'upload' . DS . 'magixfiles' . DS . $file['item_type'] . DS . $file['item_id'] . DS . $file['file_name'];

        if (!file_exists($filePath) || !is_file($filePath)) {
            $this->denyAccess('Le fichier physique est introuvable sur le serveur.');
        }

        if ($action !== 'stream') {
            $db->incrementDownloadCount($idFile);
        }

        $this->serveFile($filePath, $file['title_file'] . '.' . $file['file_ext'], $action === 'stream');
    }

    /**
     * Vérifie si l'utilisateur connecté possède le produit via Magixcartpay
     * et libère immédiatement le verrou de session PHP pour ne pas bloquer la navigation.
     */
    /**
     * Vérifie si l'utilisateur connecté possède le produit via Magixcartpay
     * et libère immédiatement le verrou de session PHP pour ne pas bloquer la navigation.
     */
    public static function userHasAccessToPrivateItem(string $itemType, int $itemId): bool
    {
        $session = new Session(true, 'magix_sess_id');
        $session->start();
        $idAccount = (int)$session->get('id_account');

        // IMPORTANT : On libère immédiatement le verrou de session PHP
        // pour éviter que le streaming MP3 ne bloque les autres clics du client
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Si l'utilisateur n'est pas connecté, accès refusé d'office
        if ($idAccount <= 0) {
            return false;
        }

        // Vérification de l'achat si le module Magixcartpay est installé et que c'est un produit
        if ($itemType === 'product' && class_exists('\Plugins\Magixcartpay\db\MagixcartpayFrontDb')) {
            $cartDb = new \Plugins\Magixcartpay\db\MagixcartpayFrontDb();
            return $cartDb->hasPurchasedProduct($idAccount, $itemId);
        }

        // CORRECTION DE SÉCURITÉ (F1) : Fail-Closed
        // Si on arrive ici (ex: Magixcartpay non installé ou type != product),
        // on refuse l'accès par défaut pour éviter de distribuer des fichiers privés gratuitement.
        return false;
    }

    /**
     * Sert un fichier en téléchargement ou en streaming audio optimisé (Support HTTP 206 Range + Chunks)
     */
    private function serveFile(string $filePath, string $downloadName, bool $inlineStream = false): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $fileSize = filesize($filePath);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $filePath) ?: 'application/octet-stream';
        finfo_close($finfo);

        $disposition = $inlineStream ? 'inline' : 'attachment';
        $safeDownloadName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $downloadName);

        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disposition . '; filename="' . $safeDownloadName . '"');
        header('Accept-Ranges: bytes');
        header('Cache-Control: private, max-age=0, must-revalidate');

        $start = 0;
        $end = $fileSize - 1;

        // Gestion du HTTP_RANGE pour l'avance rapide du lecteur audio MP3 (requis par Safari/iOS et Chrome)
        if ($inlineStream && isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
            $start = (int)$matches[1];
            if ($matches[2] !== '') {
                $end = (int)$matches[2];
            }

            if ($start > $end || $start >= $fileSize || $end >= $fileSize) {
                http_response_code(416);
                header("Content-Range: bytes */{$fileSize}");
                exit;
            }

            http_response_code(206);
            header("Content-Range: bytes {$start}-{$end}/{$fileSize}");
        } else {
            http_response_code(200);
        }

        $length = $end - $start + 1;
        header('Content-Length: ' . $length);

        $fp = fopen($filePath, 'rb');
        if ($fp === false) {
            exit;
        }

        if ($start > 0) {
            fseek($fp, $start);
        }

        // Lecture par paquets de 8 Ko pour ne jamais saturer la mémoire RAM de PHP
        $chunkSize = 8192;
        $bytesRemaining = $length;

        while (!feof($fp) && $bytesRemaining > 0 && connection_status() === CONNECTION_NORMAL) {
            $readLength = min($chunkSize, $bytesRemaining);
            $buffer = fread($fp, $readLength);
            if ($buffer === false) {
                break;
            }
            echo $buffer;
            flush();
            $bytesRemaining -= strlen($buffer);
        }

        fclose($fp);
        exit;
    }

    private function denyAccess(string $message): void
    {
        http_response_code(403);
        die($message);
    }

    // ==========================================
    // 1. LAYOUT DYNAMIQUE (FICHIERS PUBLICS)
    // ==========================================

    public static function renderWidget(array $params = []): string
    {
        if (empty(self::$targetsCache)) {
            $pluginDb = new PluginDb();
            self::$targetsCache = $pluginDb->getPluginTargets('MagixFiles');
        }

        if (empty(self::$targetsCache)) return '';

        $hookName = $params['name'] ?? '';

        if ($hookName === 'displayPageBottom') {
            if (empty(self::$targetsCache['pages'])) return '';
            return self::processRender($params, 'pages', 'id_pages', 'public');
        }
        if ($hookName === 'displayAboutBottom') {
            if (empty(self::$targetsCache['about'])) return '';
            return self::processRender($params, 'about', 'id_about', 'public');
        }
        if ($hookName === 'displayProductExtraContent') {
            if (empty(self::$targetsCache['product'])) return '';
            return self::processRender($params, 'product', 'id_product', 'public');
        }
        if ($hookName === 'displayCategoryBottom') {
            if (empty(self::$targetsCache['category'])) return '';
            return self::processRender($params, 'category', 'id_cat', 'public');
        }
        if ($hookName === 'displayNewsBottom') {
            if (empty(self::$targetsCache['news'])) return '';
            return self::processRender($params, 'news', 'id_news', 'public');
        }

        return '';
    }

    // ==========================================
    // 2. HOOK MANUEL (FICHIERS PRIVÉS DÉBLOQUÉS)
    // ==========================================

    public static function renderPrivateFiles(array $params = []): string
    {
        if (empty(self::$targetsCache)) {
            $pluginDb = new PluginDb();
            self::$targetsCache = $pluginDb->getPluginTargets('MagixFiles');
        }

        if (empty(self::$targetsCache['product'])) return '';

        return self::processRender($params, 'product', 'id_product', 'private');
    }

    /**
     * Méthode métier de rendu (Public et Privé)
     */
    private static function processRender(array $params, string $module, string $idKey, string $visibility = 'public'): string
    {
        try {
            $view = SmartyTool::getInstance('front');

            $langData = $view->getTemplateVars('current_lang') ?: $view->getTemplateVars('lang') ?: ['id_lang' => 1, 'iso_lang' => 'fr'];
            $idLang = (int)($langData['id_lang'] ?? 1);
            $iso = strtolower((string)($langData['iso_lang'] ?? 'fr'));

            $id = (int)($params[$idKey] ?? 0);
            if ($id === 0) return '';

            $db = new FilesFrontDb();
            $items = ($visibility === 'private')
                ? $db->getPrivateFiles($module, $id)
                : $db->getPublicFiles($module, $id);

            if (empty($items)) return '';

            $template = ROOT_DIR . 'plugins' . DS . 'MagixFiles' . DS . 'views' . DS . 'front' . DS . 'widget.tpl';
            if (!file_exists($template)) return '';

            $baseUrl = rtrim((string)($view->getTemplateVars('site_url') ?: $view->getTemplateVars('base_url')), '/');
            $actionBaseUrl = $baseUrl . '/' . $iso . '/magixfiles';

            // 1. Assignation des variables à Smarty
            $view->assign('mf_widget_data', [
                'module'     => $module,
                'visibility' => $visibility,
                'items'      => $items,
                'action_url' => $actionBaseUrl
            ]);

            // 2. Cache ID distinct entre public et privé
            $cacheId = 'magixfiles_' . $visibility . '_' . $module . '_' . $id . '_' . $idLang;

            // 3. Rendu avec l'identifiant de cache
            return $view->fetch($template, $cacheId);

        } catch (\Throwable $e) {
            return "";
        }
    }
}