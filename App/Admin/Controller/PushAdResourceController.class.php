<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 审核管理--广告审核
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class PushAdResourceController extends ComController {
    //查询
    public function _map(){
        $map = array();
        if (!empty($_GET['title'])) {
        	$title = trim(I('get.title','','strip_tags'));
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
        $model = M("ad");
        $map = $this->_map();
        $list = $this->_list($model,$map);
        $this -> assign("list",$list);
        $this->display();
    }
    public function push(){
        $this->changeAuditStatus(4);
    }
    public function unpush(){
        $this->changeAuditStatus(3);
    }
    public function changeAuditStatus($audit_status){
        if(empty($_REQUEST['ids'])){
            $this->error("至少选择一条记录");
            die();
        }
        $idsstr = implode(',', $_REQUEST['ids']);
        $model = D("Ad");
        $map['id'] = array("in",$_REQUEST['ids']);
        $data = array();
        $data['audit_status'] = $audit_status;
        $data['audit_time'] = time();
        if($audit_status == 4){
            $data['status'] = 1;
            $result = $model->where($map)->setField($data);
        }else{
            $data = array('audit_time'=>time(),'audit_status'=>$audit_status);
            $result = $model->where($map)->setField($data);
        }
        if($result === false){
            $this->error("修改状态失败");
        }else{
            if($audit_status == 3){
               addAuditlog("广告发布未通过,数据ID为：".$idsstr,$resouce_type=13,$type=2);
            }elseif($audit_status == 4){
               addAuditlog("广告发布通过,数据ID为：".$idsstr,$resouce_type=13,$type=2);
            }
            $this->success("修改状态成功");
        }
    }
}