<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 审核管理--内容审核
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
class AuditHotelResourceController extends ComController {
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
            $map['audit_status'] = array('in',array(0,1,2));
        }
        $hotelid=0;
        if(!empty($_GET['hid'])) {
            $map['hid'] = $_GET['hid'];
            $vo=M('hotel')->getByHid($_GET['hid']);
            $hotelid=$vo['id'];
        }
        $category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>";
        $category = $tree->get_tree(0,$str,$hotelid);
        $this->assign('pHotel',$category);
        return $map;
    }
    public function index(){
        $model = D('HotelResource');
        $map = $this->_map();
        $list = $this->_list($model, $map, 10 ,"audit_status asc,upload_time desc,sort asc");
        $this -> assign("list",$list);
        $this->display();
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
        $model = D("HotelResource");
        $map['id'] = array("in",$_REQUEST['ids']);
        $data = array('audit_time'=>time(),'audit_status'=>$audit_status);
        $result = $model->where($map)->setField($data);
        if($result === false){
            $this->error("修改状态失败");
        }else{
            if($audit_status == 1){
               addAuditlog("内容审核未通过,数据ID为：".$idsstr,$resouce_type=1,$type=1);
            }elseif($audit_status == 2){
               addAuditlog("内容审核通过,数据ID为：".$idsstr,$resouce_type=1,$type=1);
            }
            $this->success("修改状态成功");
        }
    }
}