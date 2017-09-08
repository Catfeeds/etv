CREATE TABLE `zxt_appstore` (
`id`  int(11) NOT NULL AUTO_INCREMENT ,
`app_name`  varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '名称' ,
`app_identifier`  varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'app标示  自动生成' ,
`md5_file`  varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'md5文件加密' ,
`app_package`  varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL ,
`app_introduce`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL ,
`app_file`  varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'app文件地址' ,
`app_uploadtime`  datetime NOT NULL COMMENT 'app上传时间' ,
`app_pic`  varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'app图片地址' ,
`status`  tinyint(1) NOT NULL COMMENT '状态  0禁用   1启用' ,
`audit_status`  tinyint(1) NOT NULL COMMENT '审核状态 0待审核 1审核不通过  2审核通过  3发布不通过  4发布通过' ,
`app_size`  float(7,2) NOT NULL COMMENT '文件大小' ,
`maclist`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '设备mac集合 ' ,
`audit_time`  datetime NULL DEFAULT NULL COMMENT '审核时间' ,
PRIMARY KEY (`id`)
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci
ROW_FORMAT=Compact
;

ALTER TABLE `zxt_audit_log` MODIFY COLUMN `resource_type`  tinyint(4) NOT NULL COMMENT '资源类型 1:内容审核     2:专题审核  3:广告审核  4:系统软件审核   5:路由固件审核    6:应用软件审核   7:APK审核    11:内容发布   12:专题发布     13:广告发布   14:系统软件发布     15:路由固件发布     16:应用软件发布    17APK发布' AFTER `type`;

CREATE TABLE `zxt_device_apk` (
`id`  int(11) NOT NULL AUTO_INCREMENT ,
`mac`  varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '设备列表ID' ,
`apk_id`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'apk列表id' ,
`install_apkid`  text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '已安装列表ID' ,
PRIMARY KEY (`id`),
UNIQUE INDEX `mac` (`mac`) USING BTREE 
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_general_ci
ROW_FORMAT=Compact
;