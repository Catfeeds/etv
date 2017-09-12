-- 内容管理 通用栏目等添加视频的海报图字段
ALTER TABLE `zxt_hotel_resource` ADD COLUMN `video_image`  varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '视频背景图' AFTER `upload_time`;
ALTER TABLE `zxt_topic_resource` ADD COLUMN `video_image`  varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '视频背景图' AFTER `image`;
-- 广告弹窗资源表
CREATE TABLE `zxt_hotel_adresource` (
`id`  int(11) NOT NULL AUTO_INCREMENT ,
`title`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`filepath`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`filepath_type`  tinyint(1) NOT NULL COMMENT '资源类型   1视频  2图片' ,
`audit_status`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '审核状态' ,
`status`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '启用禁用状态  0禁用 1启用' ,
`create_time`  datetime NOT NULL COMMENT '创建时间' ,
`update_time`  datetime NOT NULL COMMENT '修改时间' ,
`size`  float(7,3) NOT NULL DEFAULT 0.000 COMMENT '大小' ,
PRIMARY KEY (`id`)
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
;
-- 广告弹窗设置表
CREATE TABLE `zxt_hotel_adset` (
`id`  int(11) NOT NULL AUTO_INCREMENT ,
`hid`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`hour`  tinyint(2) NOT NULL ,
`minute`  tinyint(2) NOT NULL ,
`ad_type`  tinyint(2) NOT NULL COMMENT '广告弹窗类型   1视频弹窗  2图片方位弹窗  3走马灯方位弹窗' ,
`ad_position`  tinyint(2) NOT NULL DEFAULT 0 COMMENT '弹窗广告方位   默认值0为视频弹窗 1234分别为左上右上左下右下' ,
`can_quit`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否可中途退出   0不可  1可以' ,
`play_time`  int(5) NULL DEFAULT NULL COMMENT '播放可退出时间  单位秒' ,
`ad_word`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '弹窗广告类型为走马灯时 走马灯内容' ,
`status`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '启用禁用  ' ,
PRIMARY KEY (`id`),
INDEX `hid` (`hid`) USING BTREE 
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
;
-- 广告弹窗关联表
CREATE TABLE `zxt_hotel_adset_adresource` (
`id`  int(11) NOT NULL AUTO_INCREMENT ,
`hid`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`adset_id`  int(11) NOT NULL COMMENT '广告设置列表ID' ,
`adresource_id`  int(11) NOT NULL COMMENT '酒店弹窗广告列表ID' ,
`sort`  int(3) NOT NULL COMMENT '排序' ,
PRIMARY KEY (`id`),
INDEX `adsettable_id` (`adset_id`) USING BTREE 
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci
;
