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
class PushHotelResourceController extends ComController {
    public function _map(){
        $map = array();
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

        if (!empty($_REQUEST['keyword'])) { 
            $title = trim(I('request.keyword','','strip_tags'));
            $map['title']=array("like","%".$title."%");
        }
        if (is_numeric($_REQUEST['status']) && $_REQUEST['status'] != '-1') { 
            $status = I('request.status','','strip_tags');
            $map['audit_status']= $status;
            $this->assign('status',$status);
        }else{
            $map['audit_status'] = array('in',array(2,3,4));
            $this->assign('status','-1');
        }
        
        $this->assign('hid',$map['hid']);
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
        $list = $this->_list($model, $map, 10 ,"audit_status asc,upload_time desc,sort asc");
        $this -> assign("list",$list);
        $this->display();
    }
    
    /**
     * [内容发布通过]
     */
    public function push(){
        $this->changeAuditStatus(4);
    }

    /**
     * [内容发布不通过]
     */
    public function unpush(){
        $this->changeAuditStatus(3);
    }

    /**
     * [内容发布流程处理]
     */
    public function changeAuditStatus($audit_status){

        if(empty($_REQUEST['ids'])){
            $this->error("至少选择一条记录");
            die();
        }
        $idsstr = implode(',', $_REQUEST['ids']);
        $model = D("HotelResource");
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

            //获取需更新hid并更新hid对应的栏目资源json
            $hidlist = $model->where($map)->group('hid')->field('hid')->select();
            foreach ($hidlist as $key => $value) {
                $this->updatejson_hotelresource($value);
            }
            if($audit_status == 3){
               addAuditlog("内容发布未通过,数据ID为：".$idsstr,$resouce_type=11,$type=2);
            }elseif($audit_status == 4){
               addAuditlog("内容发布通过,数据ID为：".$idsstr,$resouce_type=11,$type=2);
            }
            $this->success("修改状态成功");
        }
    }

    /**
     * [更新栏目资源的json方法]
     * @param  [array] $map [hid]
     */
    private function updatejson_hotelresource($map){
        $map['cat'] = 'content';
        $map['status'] = 1;
        $map['audit_status'] = 4;
        $field = "id,hid,category_id as cid,title,intro,sort,filepath,type as file_type,video_image as icon";
        $list = D("hotel_resource")->where($map)->field($field)->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $plist[$value['cid']][] = $value; 
            }
            $jsondata = json_encode($plist);
        }else{
            $jsondata = '';
        }
        if(!is_dir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$map['hid'])){
            mkdir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$map['hid']);
        }
        $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$map['hid'].'/hotelresource.json';
        file_put_contents($filename, $jsondata);
    }
}