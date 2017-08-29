<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 审核管理--专题审核
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class AuditTopicResourceController extends ComController {
    public function _map(){
        $map = array();
        $resMap=array();
        if (!empty($_REQUEST['keyword'])) {
            $title = trim(I('get.keyword','','strip_tags'));
            $map['title']=array("like","%".$title."%");
        }
        if (is_numeric($_REQUEST['status'])) { 
            $status = I('get.status','','strip_tags');
            $map['audit_status']= $status;
            $this->assign('status',$status);
        }else{
            $map['audit_status'] = array('in',array(0,1,2));
        }
        if (!empty($_REQUEST['groupid'])) {
            $map_gid['groupid'] = $_REQUEST['groupid'];
            $this->assign("groupId",$_REQUEST['groupid']);
            $TopicCategory = D("TopicCategory");
            $list=$TopicCategory->where($map_gid)->select();
            if(!empty($list)){
                foreach ($list as $key => $n) {
                    $Ids[] = $n['id'];
                }
                $map['cid'] = array("in",$Ids);
            }
        }else{
            $this->assign("groupId",'');
        }
        return $map;
    }
    public function index(){
        $model = D("TopicResource");
        $map = $this->_map();
        $list = $this->_list($model, $map, 10 ,"audit_status,cid asc,sort asc");
        $this -> assign("list",$list);
        $this->display();
    }
    public function _before_index(){
        $this->_assign_list();
    }
    public function _assign_list(){
        $TopicGroup = D ('TopicGroup');
        $groupList = $TopicGroup->field ('id,title')->order('id asc')->select();
        $this->assign('groupList', $groupList);
    }
    public function audit(){
        $this->changeAuditStatus(2);
    }
    public function unaudit(){
        $this->changeAuditStatus(1);
    }
    public function changeAuditStatus($audit_status){
        if(empty($_REQUEST['ids'])){
            $this->error("至少选择一条记录");
            die();
        }
        $idsstr = implode(',', $_REQUEST['ids']);
        $model = D("TopicResource");
        $map['id'] = array("in",$_REQUEST['ids']);
        $data = array('audit_time'=>time(),'audit_status'=>$audit_status);
        $result = $model->where($map)->setField($data);
        if($result === false){
            $this->error("修改状态失败");
        }else{
            if($audit_status == 1){
               addAuditlog("专题审核未通过,数据ID为：".$idsstr,$resouce_type=2,$type=1);
            }elseif($audit_status == 2){
               addAuditlog("专题审核通过,数据ID为：".$idsstr,$resouce_type=2,$type=1);
            }
            $this->success("修改状态成功");
        }
    }
}