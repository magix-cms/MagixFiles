<?php
declare(strict_types=1);

namespace Plugins\MagixFiles\db;

/*
 * This file is part of MAGIX CMS.
 * Copyright (C) 2008 - 2026 Gerits Aurelien (Magix CMS).
 * License: GPLv3
 */

use App\Backend\Db\BaseDb;
use Magepattern\Component\Database\QueryBuilder;

class FilesDb extends BaseDb
{
    /**
     * Récupère tous les fichiers liés à un module spécifique.
     */
    public function fetchFilesByItem(string $itemType, int $itemId): array
    {
        $qb = new QueryBuilder();
        $qb->select('*')
            ->from('mc_files_item')
            ->where('item_type = :type AND item_id = :id', [
                'type' => $itemType,
                'id'   => $itemId
            ])
            ->orderBy('order_file', 'ASC');

        return $this->executeAll($qb) ?: [];
    }

    /**
     * Récupère les détails d'un fichier spécifique.
     */
    public function getFileById(int $idFile): array|false
    {
        $qb = new QueryBuilder();
        $qb->select('*')
            ->from('mc_files_item')
            ->where('id_file_item = :id', ['id' => $idFile]);

        return $this->executeRow($qb);
    }

    /**
     * Insère un nouveau fichier et gère l'ordre automatiquement.
     */
    public function insertFile(array $data): int|false
    {
        $qbCount = new QueryBuilder();
        $qbCount->select(['COUNT(id_file_item) as total'])
            ->from('mc_files_item')
            ->where('item_type = :type AND item_id = :id', [
                'type' => $data['item_type'],
                'id'   => $data['item_id']
            ]);

        $countResult = $this->executeRow($qbCount);
        $data['order_file'] = (int)($countResult['total'] ?? 0);

        $qb = new QueryBuilder();
        $qb->insert('mc_files_item', $data);

        return $this->executeInsert($qb) ? (int)$this->getLastInsertId() : false;
    }

    /**
     * Met à jour l'ordre des fichiers.
     */
    public function reorderFiles(array $fileIds): bool
    {
        $success = true;
        foreach ($fileIds as $index => $id) {
            $qb = new QueryBuilder();
            $qb->update('mc_files_item', ['order_file' => $index])
                ->where('id_file_item = :id', ['id' => (int)$id]);

            if (!$this->executeUpdate($qb)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * Supprime un fichier de la BDD.
     */
    public function deleteFile(int $idFile): bool
    {
        $qb = new QueryBuilder();
        $qb->delete('mc_files_item')->where('id_file_item = :id', ['id' => $idFile]);
        return $this->executeDelete($qb);
    }
}