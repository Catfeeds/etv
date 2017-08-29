<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 审核日志控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class AuditLogController extends ComController {
    public function _map(){
        $map = array();
        if(!empty($_POST['resource_type'])){
            $map['resource_type'] = $_POST['resource_type'];
            $this->assign('resource_type',$_POST['resource_type']);
        }
        $map['type'] = 1;//审核
        return $map;
    }
    //审核日志 类型为1
    public function index(){
        $model = D("AuditLog");
        $map = $this->_map();
        $list = $this->_list($model,$map);
        $this -> assign("list",$list);
        $this -> display();
    }
}