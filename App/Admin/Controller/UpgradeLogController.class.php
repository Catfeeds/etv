<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 设备升级日志控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
class UpgradeLogController extends ComController {
    //查询
    public function _map(){
        $map = array();
        $hotelid=0;
        if(!empty($_GET['hid'])) {
            $map['hid'] = $_GET['hid'];
            $vo=M('hotel')->getByHid($_GET['hid']);
            $hotelid=$vo['id'];
        }
        $category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
        $category = $tree->get_tree(0,$str,$hotelid);
        $this->assign('pHotel',$category);

        if (!empty($_GET['room'])) {
            $map['room'] = array("LIKE","%{$_GET['room']}%");
        }
        if (!empty($_GET['mac'])) {
            $map['mac'] = array("LIKE","%{$_GET['mac']}%");
        }
        if(!empty($_GET['status'])){
            $map['status'] = $_GET['status'];
        }
        return $map;
    }
    public function index(){
        $model = M("upgrade_log");
        $map = $this->_map();
        $list = $this->_list($model,$map);
        $this -> assign("list",$list);
        $this -> display();
    }
    //删除设备升级日志
    public function delete(){
        $model = M('upgrade_log');
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
            addlog('删除设备升级日志表数据，数据ID：'.$ids);
            $this->success('恭喜，删除成功！');
        }else{
            $this->error('删除失败，参数错误！');
        }
    }
    //删除所有设备升级日志
    public function deleteall(){
        $Dao = M();
        $Dao->execute("truncate table `zxt_upgrade_log`");
        addlog('清空设备升级日志表所有数据');
        $this->success('恭喜，操作成功！');
    }

}