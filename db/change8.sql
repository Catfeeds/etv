ALTER TABLE `zxt_appstore` ADD COLUMN `app_type`  tinyint(1) NOT NULL DEFAULT 2 COMMENT 'app类型 1系统应用  2Appstore应用' AFTER `id`;

ALTER TABLE `zxt_appstore` ADD COLUMN `app_version`  varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '系统应用版本号' AFTER `app_name`;

ALTER TABLE `zxt_appstore` MODIFY COLUMN `status`  tinyint(1) NOT NULL COMMENT '状态  0禁用   1启用  2删除' AFTER `app_pic`;

CREATE UNIQUE INDEX `version_and_package` ON `zxt_appstore`(`app_version`, `app_package`) USING BTREE ;

CREATE TABLE `zxt_device_update_result` (
`unique_flag`  char(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '唯一标识' ,
`mac`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`uplist_id`  int(6) NOT NULL COMMENT '升级包列表ID' ,
`result`  tinyint(1) NOT NULL COMMENT '升级状态  1成功  0失败' ,
PRIMARY KEY (`unique_flag`),
UNIQUE INDEX `appstore_flag` (`unique_flag`) USING BTREE ,
INDEX `mac_listid` (`mac`, `uplist_id`) USING BTREE 
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
;