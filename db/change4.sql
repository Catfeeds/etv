 -- 添加职能人员管理表
CREATE TABLE `zxt_hotel_member` (
`id`  int(11) NOT NULL AUTO_INCREMENT ,
`hid`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`department_id`  int(11) NOT NULL COMMENT '部门列表ID' ,
`identifier_m`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '员工\\工作 编号' ,
`name`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '姓名' ,
`sex`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '性别  0保密  1男  2女' ,
`filepath`  varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '图片或视频介绍' ,
`filepath_type`  tinyint(1) NULL DEFAULT NULL COMMENT '图片1  视频2' ,
`sort`  int(11) NOT NULL ,
`intro`  varchar(2048) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ,
`status`  tinyint(2) NOT NULL DEFAULT 0 ,
PRIMARY KEY (`id`)
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;
-- 添加职能部门管理表
CREATE TABLE `zxt_hotel_department` (
`id`  int(11) NOT NULL AUTO_INCREMENT ,
`hid`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
`identifier_d`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '部门编号' ,
`title`  varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '酒店\\医院内部门名称' ,
`intro`  varchar(512) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '酒店\\医院内部门介绍' ,
`sort`  int(3) NULL DEFAULT NULL ,
`status`  tinyint(1) NOT NULL DEFAULT 0 ,
PRIMARY KEY (`id`),
INDEX `hid_and_identifier_m` (`hid`, `identifier_d`) USING BTREE 
)
ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;