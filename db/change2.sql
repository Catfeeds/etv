CREATE TABLE `zxt_hotel_chglist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hid` varchar(64) CHARACTER SET latin1 NOT NULL,
  `phid` varchar(64) CHARACTER SET latin1 NOT NULL COMMENT '集团HID',
  `chg_cid` int(11) NOT NULL COMMENT '集团栏目列表ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
CREATE TABLE `zxt_hotel_chg_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hid` varchar(64) NOT NULL COMMENT '酒店编号',
  `name` varchar(128) NOT NULL COMMENT '酒店通用栏目名称',
  `pid` int(11) NOT NULL COMMENT '父级ID  顶级为0',
  `modeldefineid` tinyint(2) NOT NULL COMMENT '栏目模型类型',
  `langcodeid` tinyint(2) NOT NULL COMMENT '语言类型',
  `sort` int(4) NOT NULL COMMENT '酒店通用栏目排序',
  `filepath` varchar(128) DEFAULT NULL COMMENT '图片地址',
  `intro` varchar(512) NOT NULL COMMENT '简介',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '栏目开启状态 0关闭  1开启',
  `size` float(7,3) NOT NULL DEFAULT '0.000' COMMENT '此资源的大小',
  `all_size` float(7,3) DEFAULT NULL COMMENT '该栏目下所有资源大小总和',
  PRIMARY KEY (`id`),
  KEY `hid` (`hid`) USING BTREE,
  KEY `pid` (`pid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
CREATE TABLE `zxt_hotel_chg_resource` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hid` varchar(64) NOT NULL,
  `cid` int(11) NOT NULL COMMENT '酒店通用栏目列表ID',
  `title` varchar(128) NOT NULL COMMENT '资源标题',
  `intro` varchar(512) NOT NULL COMMENT '资源描述',
  `sort` int(4) NOT NULL DEFAULT '0' COMMENT '资源排序',
  `filepath` varchar(128) NOT NULL COMMENT '资源地址',
  `file_type` tinyint(2) NOT NULL COMMENT '资源类型  1视频  2图片',
  `icon` varchar(128) NOT NULL COMMENT '二维码地址（可能用于显示资源图片）',
  `price` float(6,2) DEFAULT NULL COMMENT '价格（暂且为空）',
  `upload_time` datetime NOT NULL COMMENT '资源上传时间',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '开启关闭状态 0关闭 1开启',
  `audit_status` tinyint(2) NOT NULL COMMENT '审核状态',
  `audit_time` datetime DEFAULT NULL COMMENT '审核时间',
  `size` float(7,3) NOT NULL COMMENT '资源大小',
  PRIMARY KEY (`id`),
  KEY `hid` (`hid`) USING BTREE,
  KEY `cid` (`cid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
CREATE TABLE `zxt_hotel_allresource` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `hid` varchar(64) NOT NULL COMMENT '酒店编号',
  `name` varchar(64) NOT NULL COMMENT '文件名称（用文件名做文件名称）',
  `type` tinyint(2) NOT NULL COMMENT '资源类型  1图片  2视频',
  `timeunix` int(10) NOT NULL COMMENT '文件创建时间戳',
  `time` datetime NOT NULL COMMENT '文件创建时间',
  `web_upload_file` varchar(64) NOT NULL COMMENT '文件存储路径',
  PRIMARY KEY (`id`),
  KEY `hid` (`hid`) USING BTREE,
  KEY `file_name` (`name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
ALTER TABLE `zxt_hotel` MODIFY COLUMN `space`  float(11,3) NULL DEFAULT 500.000 COMMENT '设备容量' AFTER `update_time`;
ALTER TABLE `zxt_hotel_category` MODIFY COLUMN `size`  float(11,3) NULL DEFAULT 0.000 AFTER `status`;
ALTER TABLE `zxt_hotel_resource` MODIFY COLUMN `size`  float(11,3) NULL DEFAULT 0.000 AFTER `status`;
ALTER TABLE `zxt_hotel_volume` ADD COLUMN `chg_size`  float(11,3) NULL DEFAULT 0.000 COMMENT '关联栏目所用容量' AFTER `devicebg_size`;
ALTER TABLE `zxt_hotel_volume` DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;
ALTER TABLE `zxt_topic_resource` MODIFY COLUMN `size`  float(11,3) NULL DEFAULT 0.000 AFTER `audit_time`;
ALTER TABLE `zxt_audit_log` MODIFY COLUMN `resource_type`  tinyint(4) NOT NULL COMMENT '资源类型 1:内容审核     2:专题审核  3:广告审核  4:系统软件审核   5:路由固件审核    6:应用软件审核   7:APK审核  8:集团通用栏目审核   11:内容发布   12:专题发布     13:广告发布   14:系统软件发布     15:路由固件发布     16:应用软件发布    17APK发布  18:集团通用栏目审核' AFTER `type`;
CREATE INDEX `pid` ON `zxt_hotel_chg_category`(`pid`) USING BTREE ;
CREATE INDEX `hid` ON `zxt_hotel_chg_resource`(`hid`) USING BTREE ;
CREATE INDEX `cid` ON `zxt_hotel_chg_resource`(`cid`) USING BTREE ;