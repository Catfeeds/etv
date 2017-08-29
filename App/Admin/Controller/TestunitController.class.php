<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 测试控制器 - 用于给测试人员测试
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
class TestunitController extends ComController {

	public function showallresource(){
		$model = D("hotel_allresource");
		$list = $this->_list($model,array(),10,'hid');
		$this -> assign("list",$list);
		$this->display();
	}
}