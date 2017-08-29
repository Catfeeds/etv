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
        if (!empty($_POST['begindate'])&& !empty($_POST['enddate'])) {
            $begindate = strtotime($_POST['begindate'].' 00:00:00');
            $enddate = strtotime($_POST['enddate'].' 23:59:59');
            $map['runtime'] = array("BETWEEN",array($begindate,$enddate));
        }elseif (empty($_POST['begindate'])&& !empty($_POST['enddate'])) {
            $begindate = strtotime($_POST['enddate'].' 00:00:00');
            $enddate = strtotime($_POST['enddate'].' 23:59:59');
            $map['runtime'] = array("BETWEEN",array($begindate,$enddate));
        }elseif (!empty($_POST['begindate'])&& empty($_POST['enddate'])) {
            $begindate = strtotime($_POST['begindate'].' 00:00:00');
            $enddate = strtotime($_POST['begindate'].' 23:59:59');
            $map['runtime'] = array("BETWEEN",array($begindate,$enddate));
        }
        if ($_POST['status']>=0 && $_POST['status']!=null ) {
           $map['status'] = array("like","%".$_REQUEST['status']."%");
        }
        if (!empty($_POST['search_type']) ) {
            if($_POST['search_type'] == 'hid'){
                $hotelname = I('post.keyword','','strip_tags');
                $hid = $this->getHidByHotelname($hotelname);
                if(!empty($hid)){
                    $map['hid'] = array('in',$hid);
                }else{
                    $map = false;
                }       
            }elseif($_POST['search_type'] == 'cityname'){
                $cityname = I('post.keyword','','strip_tags');
                $hid = $this->getHidByCityName($cityname);
                if(!empty($hid)){
                    $map['hid'] = array('in',$hid);
                }else{
                    $map = false;
                } 
            }else{
                $map[$_POST['search_type']] = array("like","%".trim(I('post.keyword','','strip_tags'))."%");
            }
        }
        return $map;
    }
    public function index(){
        $model = D(CONTROLLER_NAME);
        $map = $this->_map();
        if($map !== false){
            $list = $this->_list($model,$map);
        }else{
            $list = '';
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