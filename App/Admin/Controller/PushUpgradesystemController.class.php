<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 发布管理--系统软件发布
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class PushUpgradesystemController extends ComController {
    public function index(){
        $model = M("UpgradeSystem");
        $map['audit_status'] = array('in','2,3,4');
        $list = $this->_list($model,$map,'10',"audit_status asc,upload_time desc");
        $this -> assign("list",$list);
        $this->display();
    }
    public function detail(){
        $ids = $_REQUEST['ids'];
        if(count($ids)!=1){
            $this->error('每次只能查询一个升级包对应的详情');
            die();
        }
        $model = D("UpgradeSystem");
        $map['id'] = array('in',$ids);
        $list = $model->where($map)->find();
        if ($list['maclist'] == 'all') {
            $list['maclist'] = '所有Mac';
        }
        $this->assign('list',$list);
        $this->display();
    }
    public function audit(){
        $this->changeAuditStatus(4);
    }
    public function unaudit(){
        $this->changeAuditStatus(3);
    }
    public function changeAuditStatus($audit_status){
        if(empty($_REQUEST['ids'])){
            $this->error("至少选择一条记录");
            die();
        }
        $idsstr = implode(',', $_REQUEST['ids']);
        $model = D("UpgradeSystem");
        $map['id'] = array("in",$_REQUEST['ids']);
        $data = array('audit_time'=>time(),'audit_status'=>$audit_status);
        $result = $model->where($map)->setField($data);
        if($result === false){
            $this->error("修改状态失败");
        }else{
            if($audit_status == 3){
               addAuditlog("系统软件发布未通过,数据ID为：".$idsstr,$resouce_type=14,$type=2);
            }elseif($audit_status == 4){
               addAuditlog("系统软件发布通过,数据ID为：".$idsstr,$resouce_type=14,$type=2);
            }
            $this->success("修改状态成功");
        }
    }
}