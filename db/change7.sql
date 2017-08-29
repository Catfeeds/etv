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
PRIMARY KEY (`id`)
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
;

ALTER TABLE `zxt_hotel_volume` ADD COLUMN `popupad_size`  float(11,3) NULL DEFAULT 0.000 COMMENT '弹窗广告大小' AFTER `chg_size`;

ALTER TABLE `zxt_hotel_volume` ADD COLUMN `carousel_size`  float(11,3) NULL DEFAULT 0.000 COMMENT '轮播容量大小' AFTER `popupad_size`;

ALTER TABLE `zxt_hotel_adset` MODIFY COLUMN `ad_word`  varchar(301) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '弹窗广告类型为走马灯时 走马灯内容' AFTER `play_time`;