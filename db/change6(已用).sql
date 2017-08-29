ALTER TABLE `zxt_device` ADD COLUMN `room_remark`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '房间备注' AFTER `sleep_imageid`;

-- 添加索引
CREATE INDEX `runtime` ON `zxt_device_log`(`runtime`) USING BTREE ; 