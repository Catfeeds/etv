<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 发布管理--广告弹窗发布
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class PushAdPopupController extends ComController {
    public function _map(){
        $map = array();
        if (!empty($_REQUEST['keyword'])) { 
            $title = I('get.title','','strip_tags');
            $map['title']=array("like","%".$title."%");
        }
        if (is_numeric($_REQUEST['status'])) { 
            $status = I('get.status','','strip_tags');
            $map['audit_status']= $status;
            $this->assign('status',$status);
        }else{
            $map['audit_status'] = array('in',array(2,3,4));
        }
        return $map;
    }
    public function index(){
        $model = D('hotel_adresource');
        $map = $this->_map();
        $list = $this->_list($model, $map, 10 ,"audit_status asc,update_time desc");
        $this -> assign("list",$list);
        $this->display();
    }
    public function push(){
        $this->changepushStatus(4);
    }
    public function unpush(){
        $this->changepushStatus(3);
    }
    public function changepushStatus($audit_status){
        if(empty($_REQUEST['ids'])){
            $this->error("至少选择一条记录");
            die();
        }
        $idsstr = implode(',', $_REQUEST['ids']);
        $model = D("hotel_adresource");
        $map['id'] = array("in",$_REQUEST['ids']);
        $data = array('audit_time'=>time(),'audit_status'=>$audit_status);
        $result = $model->where($map)->setField($data);
        if($result === false){
            $this->error("修改状态失败");
        }else{
            if($audit_status == 1){
               addAuditlog("广告弹窗发布未通过,数据ID为：".$idsstr,$resouce_type=19,$type=2);
            }elseif($audit_status == 2){
               addAuditlog("广告弹窗发布通过,数据ID为：".$idsstr,$resouce_type=19,$type=2);
            }
            $this->success("修改状态成功");
        }
    }

 }