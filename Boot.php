<?php
declare(strict_types=1);

namespace Plugins\MagixFiles;

/*
 * This file is part of MAGIX CMS.
 * Copyright (C) 2008 - 2026 Gerits Aurelien (Magix CMS).
 * License: GPLv3
 */

use App\Component\Hook\HookManager;
use App\Component\Db\PluginDb;
use Magepattern\Component\Tool\SmartyTool;
use Plugins\MagixFiles\src\FrontendController;

class Boot
{
    private array $targetModules = [
        'product'  => 'id_product',
        'pages'    => 'id_pages',
        'category' => 'id_cat',
        'news'     => 'id_news',
        'about'    => 'id_about'
    ];

    public function register(): void
    {
        // 1. Coupe-circuit global via la base de données
        $pluginDb = new PluginDb();
        $activeTargets = $pluginDb->getPluginTargets('MagixFiles');

        if (empty($activeTargets)) {
            return;
        }

        // ==========================================
        // 1. HOOKS BACKEND (Administration conditionnelle)
        // ==========================================
        foreach ($this->targetModules as $module => $idKey) {

            if (isset($activeTargets[$module]) && $activeTargets[$module] == 1) {

                HookManager::register("{$module}_edit_tab", 'MagixFiles', function(array $params) use ($module) {
                    $smarty = SmartyTool::getInstance('admin');
                    $smarty->assign('mf_module', $module);
                    $file = ROOT_DIR . 'plugins' . DS . 'MagixFiles' . DS . 'views' . DS . 'admin' . DS . 'hooks' . DS . 'tab_button.tpl';
                    return $smarty->templateExists($file) ? $smarty->fetch($file) : '';
                });

                HookManager::register("{$module}_edit_content", 'MagixFiles', function(array $params) use ($module, $idKey) {
                    $smarty = SmartyTool::getInstance('admin');
                    $idModule = $params[$idKey] ?? 0;
                    $smarty->assign([
                        'mf_module'    => $module,
                        'mf_id_module' => $idModule
                    ]);
                    $file = ROOT_DIR . 'plugins' . DS . 'MagixFiles' . DS . 'views' . DS . 'admin' . DS . 'hooks' . DS . 'tab_content.tpl';
                    return $smarty->templateExists($file) ? $smarty->fetch($file) : '';
                });
            }
        }

        // ==========================================
        // 2. HOOK FRONTEND PRIVÉ (Pour le bloc {if $user_owns_product})
        // ==========================================
        if (!empty($activeTargets['product'])) {
            HookManager::register('displayProductPrivateFiles', 'MagixFiles', function(array $params) {
                return FrontendController::renderPrivateFiles($params);
            });
        }
    }
}