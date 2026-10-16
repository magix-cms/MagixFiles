CREATE TABLE IF NOT EXISTS `mc_files_item` (
    `id_file_item` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `item_type` VARCHAR(50) NOT NULL DEFAULT 'product',
    `item_id` INT(11) UNSIGNED NOT NULL,
    `title_file` VARCHAR(150) NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_ext` VARCHAR(15) NOT NULL,
    `file_size` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `visibility_file` ENUM('public','private','draft') NOT NULL DEFAULT 'public',
    `order_file` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `downloads_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `date_register` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_file_item`),
    KEY `idx_item` (`item_type`, `item_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;