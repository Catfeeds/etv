ALTER TABLE `zxt_hotel_topic` ADD COLUMN `topic_id`  int(11) NOT NULL AFTER `hid`;
ALTER TABLE `zxt_hotel_topic` DROP COLUMN `topic_list`;
ALTER TABLE `zxt_topic_category` MODIFY COLUMN `groupid`  int(6) NOT NULL COMMENT '栏目分组ID' AFTER `id`;
ALTER TABLE `zxt_topic_category` MODIFY COLUMN `sort`  int(11) NOT NULL COMMENT '栏目排序' AFTER `groupid`;
ALTER TABLE `zxt_topic_category` MODIFY COLUMN `status`  int(2) NOT NULL DEFAULT 0 COMMENT '状态' AFTER `sort`;
ALTER TABLE `zxt_topic_category` ADD COLUMN `icon`  varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '图标地址' AFTER `langcodeid`;
ALTER TABLE `zxt_topic_category` ADD COLUMN `size`  float(5,3) NULL DEFAULT 0.000 COMMENT '图标大小' AFTER `icon`;
ALTER TABLE `zxt_topic_group` MODIFY COLUMN `status`  tinyint(1) NOT NULL DEFAULT 0 AFTER `id`;
ALTER TABLE `zxt_topic_group` ADD COLUMN `icon`  varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '图标地址' AFTER `intro`;
ALTER TABLE `zxt_topic_group` ADD COLUMN `size`  float(5,3) NULL DEFAULT 0.000 COMMENT '图标大小' AFTER `icon`;
ALTER TABLE `zxt_hotel_topic` DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;

ALTER TABLE `zxt_device` ADD COLUMN `wifi_order`  tinyint(2) NULL DEFAULT 1 COMMENT '平台设置wifi状态   0关闭  1开启' AFTER `wifi_status`;