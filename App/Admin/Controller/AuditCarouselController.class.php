<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 审核管理--SD卡视频资源审核
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
class AuditCarouselController extends ComController {
    public function _map(){
        $data = session(session_id());
        $hotel = 0;
        if(!empty($_REQUEST['hid'])) {
            if($_REQUEST['hid'] != 'hid-1gg'){ //自定义查询所有酒店
                $map['hid'] = $_REQUEST['hid'];
                $vo=M('hotel')->getByHid($_REQUEST['hid']);
                $hotelid=$vo['id'];
                $data['content_hid'] = $_REQUEST['hid'];
                session(session_id(),$data);
            }else{
                $map = array();
                $data['content_hid'] = null;
                session(session_id(),$data);
            }
        }else{
            $data = session(session_id());
            if(!empty($data['content_hid'])){
                $map['hid'] = $data['content_hid'];
                $vo=M('hotel')->getByHid($data['content_hid']);
                $hotelid=$vo['id'];
            }
        }

        $map = array();
        if (!empty($_REQUEST['keyword'])) { 
            $title = I('get.title','','strip_tags');
            $map['title']=array("like","%".$title."%");
        }
        if (is_numeric($_REQUEST['status']) && $_REQUEST['status'] != '-1') { 
            $status = I('request.status','','strip_tags');
            $map['audit_status']= $status;
            $this->assign('status',$status);
        }else{
            $map['audit_status'] = array('in',array(0,1,2));
            $this->assign('status','-1');
        }
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
        $model = D('hotel_carousel_resource');
        $map = $this->_map();
        $hid_condition = $this->isHotelMenber();
        if ($hid_condition) {            
            if (empty($map['hid'])) {
                $map['hid'] = $hid_condition;
            }else{
                if (!in_array($map['hid'], $hid_condition['1'])) {
                    $map['hid'] = $hid_condition;
                }
            }
        }
        $list = $this->_list($model, $map, 10 ,"audit_status asc,id desc");
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
        $model = D("hotel_carousel_resource");
        $map['id'] = array("in",$_REQUEST['ids']);
        $data = array('audit_time'=>date("Y-m-d H:i:s"),'audit_status'=>$audit_status);
        $result = $model->where($map)->setField($data);
        if($result === false){
            $this->error("修改状态失败");
        }else{
            if($audit_status == 1){
               addAuditlog("SD卡视频资源审核未通过,数据ID为：".$idsstr,$resouce_type=10,$type=1);
            }elseif($audit_status == 2){
               addAuditlog("SD卡视频资源审核通过,数据ID为：".$idsstr,$resouce_type=20,$type=1);
            }
            $this->success("修改状态成功");
        }
    }
}