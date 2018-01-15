ALTER TABLE `zxt_hotel_category` DROP INDEX `hid`;

CREATE INDEX `hid_pid` ON `zxt_hotel_category`(`hid`, `pid`) USING BTREE ;

ALTER TABLE `zxt_hotel_resource` DROP INDEX `hid_cid`;

CREATE INDEX `hid_cid_cat` ON `zxt_hotel_resource`(`hid`, `category_id`, `cat`) USING BTREE ;

ALTER TABLE `zxt_hotel` ADD COLUMN `main_type`  tinyint(2) NOT NULL DEFAULT 2 COMMENT '1酒店  2医院' AFTER `latitude`;

ALTER TABLE `zxt_hotel` ADD COLUMN `demo`  tinyint(2) NOT NULL DEFAULT 2 COMMENT '1演示  2实用' AFTER `main_type`;