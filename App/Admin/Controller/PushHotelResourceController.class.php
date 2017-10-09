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
        $model = D('HotelResource');
        $map = $this->_map();
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
        $field = "";
        $list = D("hotel_resource")->where($map)->field()->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $plist[$value['category_id']][] = $value; 
            }
            $jsondata = json_encode($plist);
        }else{
            $jsondata = '';
        }
        if(!is_dir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid)){
            mkdir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid);
        }
        $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid.'/hotelresource.json';
        file_put_contents($filename, $jsondata);
    }
}