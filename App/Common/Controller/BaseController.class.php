<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues 
// +-------------------------------------------------------------------------
// | Description: 模块公共控制器
// +-------------------------------------------------------------------------

namespace Common\Controller;
use Think\Controller;

class BaseController extends Controller {
    public function _initialize(){
        C(setting());
    }
}