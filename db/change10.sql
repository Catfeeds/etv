CREATE TABLE `zxt_device_sleep` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mac` varchar(32) NOT NULL,
  `sleep_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '休眠状态  1开启  0关闭',
  `sleep_time_start` varchar(10) NOT NULL DEFAULT '00:00' COMMENT '休眠开启时间',
  `sleep_time_end` varchar(10) NOT NULL DEFAULT '23:59',
  `sleep_marked_word` varchar(128) NOT NULL DEFAULT ' ',
  `sleep_countdown_time` int(11) NOT NULL DEFAULT '1',
  `sleep_imageid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

