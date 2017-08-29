<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 参数上传日志控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class DeviceParaLogController extends ComController {
    //查询
    public function _map(){
        $map = array();
        if (!empty($_GET['mac'])) {
            $map['mac'] = array("LIKE","%{$_GET['mac']}%");
        }
        return $map;
    }
    public function index(){
        $model = M("device_para_log");
        $map = $this->_map();
        $list = $this->_list($model,$map);
        $this -> assign("list",$list);
        $this -> assign("vo",$list[0]);
        $this -> display();
    }
   
    public function detail(){
        $model = M("device_para_log");
        $list = $model->getById($_REQUEST['id']);
        $list['boot_time'] =date("Y-m-d H:i:s",$list['boot_time']);
        $list['post_time'] =date("Y-m-d H:i:s",$list['post_time']);
        echo json_encode($list);
    }
    //删除参数上传日志
    public function delete(){
        $model = M('device_para_log');
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if(is_array($ids)){
            if(!$ids){
                $this->error('参数错误！');
            }
            foreach($ids as $k=>$v){
                $ids[$k] = intval($v);
            }
            $map['id']  = array('in',$ids);
        }else{
            $map['id']  = $ids;
        }
        if($model->where($map)->delete()){
            if(is_array($ids)){
                $ids = implode(',',$ids);
            }
            addlog('删除参数上传日志表数据，数据ID：'.$ids);
            $this->success('恭喜，删除成功！');
        }else{
            $this->error('删除失败，参数错误！');
        }
    }
    //删除所有参数上传日志
    public function deleteall(){
        $Dao = M();
        $Dao->execute("truncate table `zxt_device_para_log`");
        addlog('清空参数上传日志表所有数据');
        $this->success('恭喜，操作成功！');
    }

}