ALTER TABLE `zxt_hotel` ADD COLUMN `ad_space`  float(11,3) NULL DEFAULT 10240.000 AFTER `space`;

ALTER TABLE `zxt_hotel` ADD COLUMN `carousel_space`  float(11,3) NULL DEFAULT 19456.000 AFTER `ad_space`;

ALTER TABLE `zxt_hotel_adresource` MODIFY COLUMN `size`  float(9,3) NOT NULL DEFAULT 0.000 COMMENT '大小' AFTER `update_time`;

CREATE TABLE `zxt_hotel_carousel_resource` (
`id`  int(11) NOT NULL AUTO_INCREMENT ,
`hid`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '酒店编号' ,
`cid`  int(11) NOT NULL COMMENT '资源对应栏目ID' ,
`ctype`  varchar(16) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '资源对应栏目类别 videohotel  videochg' ,
`title`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '资源标题' ,
`intro`  varchar(256) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '资源介绍' ,
`sort`  int(4) NOT NULL COMMENT '资源排序' ,
`filepath`  varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '资源路径' ,
`file_type`  tinyint(1) NOT NULL COMMENT '资源类型' ,
`video_image`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
`upload_time`  timestamp NOT NULL ON UPDATE CURRENT_TIMESTAMP ,
`audit_status`  tinyint(2) NOT NULL DEFAULT 0 ,
`audit_time`  datetime NULL DEFAULT NULL COMMENT '审核时间' ,
`status`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '启用禁用状态' ,
`size`  float(7,3) NOT NULL COMMENT '资源大小' ,
PRIMARY KEY (`id`),
INDEX `hid_cid` (`hid`, `cid`) USING BTREE 
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
;

ALTER TABLE `zxt_hotel_volume` ADD COLUMN `popupad_size`  float(11,3) NULL DEFAULT 0.000 COMMENT '弹窗广告大小' AFTER `chg_size`;

ALTER TABLE `zxt_hotel_volume` ADD COLUMN `carousel_size`  float(11,3) NULL DEFAULT 0.000 COMMENT '轮播容量大小' AFTER `popupad_size`;

ALTER TABLE `zxt_hotel_adset` MODIFY COLUMN `ad_word`  varchar(301) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '弹窗广告类型为走马灯时 走马灯内容' AFTER `play_time`;

ALTER TABLE `zxt_audit_log` MODIFY COLUMN `resource_type`  tinyint(4) NOT NULL COMMENT '资源类型 1:内容审核     2:专题审核  3:广告审核  4:系统软件审核   5:路由固件审核    6:应用软件审核   7:APK审核  8:集团通用栏目审核  9:广告弹窗审核  10:SD卡资源审核  11:内容发布   12:专题发布     13:广告发布   14:系统软件发布     15:路由固件发布     16:应用软件发布    17APK发布  18:集团通用栏目发布  19:广告弹窗发布  20:SD卡资源发布' AFTER `type`;

CREATE INDEX `hid_cid` ON `zxt_hotel_resource`(`hid`, `category_id`) USING BTREE ;

ALTER TABLE `zxt_hotel_resource` DROP INDEX `hid`;

CREATE TABLE `zxt_hotel_sd_allresource` (
`id`  int(11) NOT NULL AUTO_INCREMENT ,
`hid`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`name`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`type`  tinyint(2) NOT NULL ,
`mold`  varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`timeunix`  int(10) NOT NULL ,
`time`  datetime NOT NULL ,
`web_upload_file`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
PRIMARY KEY (`id`)
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
;