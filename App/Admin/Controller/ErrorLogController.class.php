<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 设备错误日志控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
class ErrorLogController extends ComController {
    //查询
    public function _map(){
        $map = array();
        $data = session(session_id());
        $hotelid=0;
        if(!empty($_POST['hid'])) {
            $map['hid'] = $_POST['hid'];
            $vo=M('hotel')->getByHid($_POST['hid']);
            $hotelid=$vo['id'];
            if ($_POST['hid'] != 'hid-1gg') { //自定义查询所有酒店
                $map['hid'] = $_POST['hid'];
                $this->assign('hid',$_POST['hid']);
                $vo=M('hotel')->getByHid($_POST['hid']);
                $data['content_hid'] = $_POST['hid'];
                session(session_id(),$data);
            }else{
                $map = array();
                $data['content_hid'] = null;
                session(session_id(),$data);
            }
        }else{
            if(!empty($data['content_hid'])){
                $map['hid'] = $data['content_hid'];
                $vo=M('hotel')->getByHid($data['content_hid']);
                $hotelid=$vo['id'];
            }
        }
        $category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
        $category = $tree->get_tree(0,$str,$hotelid);
        $this->assign('pHotel',$category);

        if (!empty($_POST['room'])) {
            $map['room'] = array("LIKE","%{$_POST['room']}%");
        }
        if (!empty($_POST['mac'])) {
            $map['mac'] = array("LIKE","%{$_POST['mac']}%");
        }
        return $map;
    }
    public function index(){
        $model = M("device_error_log");
        $map = $this->_map();
        $list = $this->_list($model,$map);
        $this -> assign("list",$list);
        $this -> assign("vo",$list[0]);
        $this -> display();
    }
    public function detail(){
        $model = M("device_error_log");
        $list = $model->getById($_REQUEST['id']);
        $list['time'] =date("Y-m-d H:i:s",$list['time']);
        $vo=M('hotel')->getByHid($list['hid']);
        $list['hotelname'] =$vo['hotelname'];
        echo json_encode($list);
    }
    //删除设备错误日志
    public function delete(){
        $model = M('device_error_log');
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
            addlog('删除设备错误日志表数据，数据ID：'.$ids);
            $this->success('恭喜，删除成功！');
        }else{
            $this->error('删除失败，参数错误！');
        }
    }
    //删除所有设备错误日志
    public function deleteall(){
        $Dao = M();
        $Dao->execute("truncate table `zxt_device_error_log`");
        addlog('清空设备错误日志表所有数据');
        $this->success('恭喜，操作成功！');
    }

}