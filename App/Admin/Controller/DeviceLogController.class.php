<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 设备信息运行日志
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class DeviceLogController extends ComController {
    //查询
    public function _map(){
        $map = array();
        if (!empty($_GET['begindate'])&& !empty($_GET['enddate'])) {
            $begindate = strtotime($_GET['begindate'].' 00:00:00');
            $enddate = strtotime($_GET['enddate'].' 23:59:59');
            $map['runtime'] = array("BETWEEN",array($begindate,$enddate));
        }elseif (empty($_GET['begindate'])&& !empty($_GET['enddate'])) {
            $begindate = strtotime($_GET['enddate'].' 00:00:00');
            $enddate = strtotime($_GET['enddate'].' 23:59:59');
            $map['runtime'] = array("BETWEEN",array($begindate,$enddate));
        }elseif (!empty($_GET['begindate'])&& empty($_GET['enddate'])) {
            $begindate = strtotime($_GET['begindate'].' 00:00:00');
            $enddate = strtotime($_GET['begindate'].' 23:59:59');
            $map['runtime'] = array("BETWEEN",array($begindate,$enddate));
        }
        if ($_GET['status']>=0 && $_GET['status']!=null ) {
           $map['status'] = $_REQUEST['status'];
        }
        if (!empty($_GET['search_type']) ) {
            if($_GET['search_type'] == 'hid'){
                $hid = I('get.keyword','','strip_tags');
                if(!empty($hid)){
                    $map['hid'] = array('in',$hid);
                }
            }elseif($_GET['search_type'] == 'mac'){
                $mac = I('get.keyword','','strip_tags');
                if(!empty($mac)){
                    $map['mac'] = array('in',$mac);
                }
            }elseif($_GET['search_type'] == 'room'){
                $room = I('get.keyword','','strip_tags');
                if(!empty($room)){
                    $map['room'] = array('in',$room);
                }
            }
        }
        return $map;
    }
    public function index(){
        $model = D(CONTROLLER_NAME);
        $map = $this->_map();
        if(empty($map)){
            $count = $model->count();
            if(empty($_GET['p'])){
                $sql = "select * from zxt_device_log order by id desc limit 10";
            }else{
                $getpage = $_GET['p'];
                $idcount = ($getpage-1)*10;
                $sql = "select * from zxt_device_log WHERE id <= (select id from zxt_device_log order by id desc limit ".$idcount." ,1) ORDER BY id desc limit 0,10";
            }
            $Page = new\Think\Page($count,10,$_GET);
            $pageshow = $Page -> show();
            $this -> assign("page",$pageshow);//赋值分页输出
            $list = $model->query($sql);
        }else{
            $list = $this->_list($model,$map);
        }
        $this->assign('list',$list);
        $this->display();
    }

    //删除过去一周记录
    public function delete_week(){
        $time = time()-60*60*24*7;
        $map['runtime'] = array('ELT',$time);
        $result = D("device_log")->where($map)->delete();
        if($result!==false){
            $this->success('删除日志成功');
        }else{
            $this->error('删除日志失败');
        }
    }

    //删除过去一个月记录
    public function delete_month(){
        $time = time()-60*60*24*30;
        $map['runtime'] = array('ELT',$time);
        $result = D("device_log")->where($map)->delete();
        if($result!==false){
            $this->success('删除日志成功');
        }else{
            $this->error('删除日志失败');
        }
    }
 }