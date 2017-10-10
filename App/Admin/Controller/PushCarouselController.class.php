<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 发布管理--SD卡视频资源发布
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
class PushCarouselController extends ComController {
    public function _map(){
        $map = array();
        if (!empty($_REQUEST['keyword'])) { 
            $title = trim(I('get.keyword','','strip_tags'));
            $map['title']=array("like","%".$title."%");
        }
        if (is_numeric($_REQUEST['status'])) { 
            $status = I('get.status','','strip_tags');
            $map['audit_status']= $status;
            $this->assign('status',$status);
        }else{
            $map['audit_status'] = array('in',array(2,3,4));
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
        $model = D('hotel_carousel_resource');
        $map = $this->_map();
        $list = $this->_list($model, $map, 10 ,"audit_status asc,upload_time desc,sort asc");
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
        $model = D("hotel_carousel_resource");
        $map['id'] = array("in",$_REQUEST['ids']);
        $data = array();
        $data['audit_status'] = $audit_status;
        $data['audit_time'] = date("Y-m-d H:i:s");
        if($audit_status == 4){
            $data['status'] = 1;
            $result = $model->where($map)->setField($data);
        }else{
            $data = array('audit_time'=>date("Y-m-d H:i:s"),'audit_status'=>$audit_status);
            $result = $model->where($map)->setField($data);
        }
        if($result === false){
            $this->error("修改状态失败");
        }else{

            //获取需更新hid并更新hid对应的视频轮播json
            $hidlist = $model->where($map)->group('hid,ctype')->field('hid,ctype')->select();
            foreach ($hidlist as $key => $value) {
                $this->updatejson_hotelcarousel($value);
            }
            if($audit_status == 3){
               addAuditlog("SD卡视频资源发布未通过,数据ID为：".$idsstr,$resouce_type=20,$type=2);
            }elseif($audit_status == 4){
               addAuditlog("SD卡视频资源发布通过,数据ID为：".$idsstr,$resouce_type=20,$type=2);
            }
            $this->success("修改状态成功");
        }
    }

    /**
     * [更新酒店视频轮播json数据]
     * @param  [array] $hmap [hid数组]
     */
    private function updatejson_hotelcarousel($hmap){
        $hmap['audit_status'] = 4;
        $hmap['status'] = 1;
        $field = "hid,cid,title,intro,sort,filepath,video_image";
        $list = D("hotel_carousel_resource")->where($hmap)->field($field)->order('sort')->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $plist[$value['cid']][] = $value;
            }
            $jsondata = json_encode($plist);
        }else{
            $jsondata = '';
        }
        if(!is_dir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hmap['hid'])){
            mkdir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hmap['hid']);
        }
        $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hmap['hid'].'/'.$hmap['ctype'].'.json';
        file_put_contents($filename, $jsondata);
    }
}