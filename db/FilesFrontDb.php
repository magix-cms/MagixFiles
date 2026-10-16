<?php
declare(strict_types=1);

namespace Plugins\MagixFiles\db;

/*
 * This file is part of MAGIX CMS.
 * Copyright (C) 2008 - 2026 Gerits Aurelien (Magix CMS).
 * License: GPLv3
 */

use App\Frontend\Db\BaseDb;
use Magepattern\Component\Database\QueryBuilder;

class FilesFrontDb extends BaseDb
{
    /**
     * Récupère les fichiers strictement "public" pour le Layout Dynamique.
     */
    public function getPublicFiles(string $itemType, int $itemId): array
    {
        $cache = $this->getSqlCache();
        $qb = new QueryBuilder();

        $qb->select([
            'id_file_item',
            'title_file',
            'file_ext',
            'file_size',
            'visibility_file'
        ])
            ->from('mc_files_item')
            ->where('item_type = :item_type', ['item_type' => $itemType])
            ->where('item_id = :item_id', ['item_id' => $itemId])
            ->where("visibility_file = 'public'")
            ->orderBy('order_file', 'ASC');

        $cacheKey = $cache->generateKey($qb->getSql(), $qb->getParams(), 'magixfiles');

        if (($data = $cache->get($cacheKey)) !== null) {
            return $data;
        }

        $results = $this->executeAll($qb) ?: [];
        $cache->set($cacheKey, $results, 86400);

        return $results;
    }

    /**
     * Récupère les fichiers strictement "private" pour les clients ayant acheté le produit.
     */
    public function getPrivateFiles(string $itemType, int $itemId): array
    {
        $cache = $this->getSqlCache();
        $qb = new QueryBuilder();

        $qb->select([
            'id_file_item',
            'title_file',
            'file_ext',
            'file_size',
            'visibility_file'
        ])
            ->from('mc_files_item')
            ->where('item_type = :item_type', ['item_type' => $itemType])
            ->where('item_id = :item_id', ['item_id' => $itemId])
            ->where("visibility_file = 'private'")
            ->orderBy('order_file', 'ASC');

        $cacheKey = $cache->generateKey($qb->getSql(), $qb->getParams(), 'magixfiles');

        if (($data = $cache->get($cacheKey)) !== null) {
            return $data;
        }

        $results = $this->executeAll($qb) ?: [];
        $cache->set($cacheKey, $results, 86400);

        return $results;
    }

    /**
     * Récupère un fichier complet par son ID (pour le téléchargement/streaming sécurisé).
     */
    public function getFileById(int $idFile): array|false
    {
        $qb = new QueryBuilder();
        $qb->select('*')
            ->from('mc_files_item')
            ->where('id_file_item = :id', ['id' => $idFile])
            ->limit(1);

        return $this->executeRow($qb);
    }

    /**
     * Incrémente le compteur de téléchargements.
     */
    public function incrementDownloadCount(int $idFile): void
    {
        $file = $this->getFileById($idFile);
        if ($file) {
            $qb = new QueryBuilder();
            $qb->update('mc_files_item', ['downloads_count' => ((int)$file['downloads_count'] + 1)])
                ->where('id_file_item = :id', ['id' => $idFile]);
            $this->executeUpdate($qb);
        }
    }
}