ALTER TABLE `zxt_hotel_category` DROP INDEX `hid`;

CREATE INDEX `hid_pid` ON `zxt_hotel_category`(`hid`, `pid`) USING BTREE ;

ALTER TABLE `zxt_hotel_resource` DROP INDEX `hid_cid`;

CREATE INDEX `hid_cid_cat` ON `zxt_hotel_resource`(`hid`, `category_id`, `cat`) USING BTREE ;

-- 休眠背景图
ALTER TABLE `zxt_device_mac_image` ADD COLUMN `image_default`  tinyint(1) NOT NULL DEFAULT 0 AFTER `image_size`;
CREATE INDEX `sleep_image` ON `zxt_device`(`sleep_imageid`) USING BTREE ;

-- 修改字符大小
-- 权限添加索引
ALTER TABLE `zxt_auth_rule` MODIFY COLUMN `icon`  varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `title`;
CREATE INDEX `islink` ON `zxt_auth_rule`(`islink`, `o`, `id`, `pid`, `title`, `name`, `icon`) USING BTREE ;