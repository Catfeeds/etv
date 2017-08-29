<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 广告图片管理控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class AdController extends ComController {
    //查询
    public function _map(){
        $map = array();
        if (!empty($_GET['title'])) {
            $map['title'] = array("LIKE","%{$_GET['title']}%");
        }
        return $map;
    }
    public function index(){
        $model = M("ad");
        $map = $this->_map();
        $list = $this->_list($model,$map);
        $this -> assign("list",$list);
        if (empty($list[0]['hidlist'])) {
            $list[0]['hidlist']='-';
        }else if($list[0]['hidlist']=="all"){
            $list[0]['hidlist']="全部酒店";
        }else{
            $ids = trim($list[0]['hidlist']);
            $idsArr = explode(",", $ids);
            $hotelname='';
            $Hotel = D("Hotel");
            foreach ($idsArr as $value) {
                $vo=array();
                $vo=$Hotel->getByHid($value);
                $hotelname = $hotelname.','.$vo['hotelname'];
            }
            $list[0]['hidlist']=trim($hotelname,',');
        }
        $this -> assign("vo",$list[0]);
        $this -> display();
    }
    public function _before_add(){
        $regionList = $this->_hotel_list();
        $this->assign('menuTree', $regionList);
    }
    public function edit(){
        $Ad = D(CONTROLLER_NAME);
        if(count($_REQUEST['ids'])!=1){
            $this->error("只能修改一条记录");
            die();
        }
        $regionList = $this->_hotel_list();
        $id = $_REQUEST['ids'][0];
        $list = $Ad->getById($id);
        $ids = trim($list['hidlist']);
        $idsArr = explode(",", $ids);
        foreach ($regionList as $key => $value) {
            if(!empty($value['sub'])){ 
                foreach ($value['sub'] as $kk => $vv) {
                    if(in_array($vv['hid'], $idsArr)){
                        $value['sub'][$kk]['isselect'] = 1;
                    }else{
                        $value['sub'][$kk]['isselect'] = 0;
                    }
                }
            }
            $regionList[$key] = $value;
        }
        $this->assign('menuTree', $regionList);
        if (empty($list['hidlist'])) {
            $list['hotelname']='/';
        }else if($list['hidlist']=="all"){
            $list['hotelname']="全部酒店";
        }else{
            
            $hotelname='';
            $Hotel = D("Hotel");
            foreach ($idsArr as $value) {
                $vo=array();
                $vo=$Hotel->getByHid($value);
                $hotelname = $hotelname.','.$vo['hotelname'];
            }
            $list['hotelname']=trim($hotelname,',');
        }
        $this->assign("vo",$list);
        $this->display();
    }
    public function detail(){
        $model = M("ad");
        $list = $model->getById($_REQUEST['id']);
        if (empty($list['hidlist'])) {
            $list['hidlist']='-';
        }else if($list['hidlist']=="all"){
            $list['hidlist']="全部酒店";
        }else{
            $ids = trim($list['hidlist']);
            $idsArr = explode(",", $ids);
            $hotelname='';
            $Hotel = D("Hotel");
            foreach ($idsArr as $value) {
                $vo=array();
                $vo=$Hotel->getByHid($value);
                $hotelname = $hotelname.','.$vo['hotelname'];
            }
            $list['hidlist']=trim($hotelname,',');
        }
        echo json_encode($list);
    }
    //保存
    public function update(){
        $model = M(CONTROLLER_NAME);
        $data['id'] = I('post.id','','intval');
        $data['title'] = I('post.title','','strip_tags');
        $data['filepath'] = I('post.filepath','','strip_tags');
        $data['audit_status'] = 0;
        $data['hidlist'] = trim(I('post.hidlist','','strip_tags'));
        $size = I('post.size','','intval');
        $data['size'] = round($size/1024,3);
        if (empty($data['filepath'])) {
            $this->error('请上传图片文件！');
        }
        $vo = '';
        $model->startTrans();
        if($data['id']){
            $vo = $model->getById($data['id']);
            if ($data['filepath'] != $vo['filepath']) {
                $data['upload_time']=time();
            }
            $result = $model->data($data)->where('id='.$data['id'])->save();
            addlog('修改广告图片信息，ID：'.$data['id']);
        }else{
            $data['upload_time']=time();
            $result = $model->data($data)->add();
            addlog('添加广告图片，ID：'.$result);
        }
        //直接更改容量
        if($data['id']){
            $changesize = $data['size'] - $vo['size'];
            if($data['hidlist'] == $vo['hidlist']){//没更改酒店
                if($data['hidlist'] == 'all'){
                    $arrhid = M("hotel_volume")->field('hid')->select();
                    $arr = array();
                    foreach ($arrhid as $key => $value) {
                        array_push($arr, $value['hid']);
                    }
                    $arrmap['hid'] = array('in',$arr);
                    $updatesize = M("hotel_volume")->where($arrmap)->setInc('ad_size',$changesize);
                }else{
                    $amap['hid'] = array('in',$data['hidlist']);
                    $updatesize = M("hotel_volume")->where($amap)->setInc('ad_size',$changesize);
                }

            }else{//更改酒店
                if($vo['hidlist'] == 'all'){//本来是全部酒店
                    $arrhid = M("hotel_volume")->field('hid')->select();
                    $arr = array();
                    foreach ($arrhid as $key => $value) {
                        array_push($arr, $value['hid']);
                    }
                    $arrmap['hid'] = array('in',$arr);
                    $dresult = M("hotel_volume")->where($arrmap)->setDec('ad_size',$vo['size']);
                    $imap['hid'] = $data['hidlist'];
                    $iresult = M("hotel_volume")->where($imap)->setInc('ad_size',$data['size']);

                }else{//本来不是全部酒店
                    $dmap['hid'] = array('in',$vo['hidlist']);
                    $dresult = M("hotel_volume")->where($dmap)->setDec('ad_size',$vo['size']);
                    if($data['hidlist'] == 'all'){//更改为全部酒店
                        $arrhid = M("hotel_volume")->field('hid')->select();
                        $arr = array();
                        foreach ($arrhid as $key => $value) {
                            array_push($arr, $value['hid']);
                        }
                        $arrmap['hid'] = array('in',$arr);
                        $iresult = M("hotel_volume")->where($arrmap)->setInc('ad_size',$data['size']);
                    }else{ 
                        $imap['hid'] = array('in',$data['hidlist']);
                        $iresult = M("hotel_volume")->where($imap)->setInc('ad_size',$data['size']);
                    }
                }
                if($iresult !== false && $dresult !== false){
                    $updatesize = true;
                }else{
                    $updatesize = false;
                }
            }
        }else{//新增
            if($data['hidlist'] == 'all'){
                $arrhid = M("hotel_volume")->field('hid')->select();
                $arr = array();
                foreach ($arrhid as $key => $value) {
                    array_push($arr, $value['hid']);
                }
                $arrmap['hid'] = array('in',$arr);
                $updatesize = D("hotel_volume")->where($arrmap)->setInc('ad_size',$data['size']);
            }else{
                $amap['hid'] = array('in',$data['hidlist']);
                $updatesize = M("hotel_volume")->where($amap)->setInc('ad_size',$data['size']);
            }

        }
        if($result !== false && $updatesize !== false){
            if(!empty($vo)){
                if ($data['filepath'] != $vo['filepath']) {
                    @unlink(FILE_UPLOAD_ROOTPATH.$vo['filepath']);
                }
            }
            $model->commit();
            $this->success('恭喜，操作成功！',U('index'));
        }else{
            $model->rollback();
            $this->error('操作失败！',U('index'));
        }
    }
    public function delete(){
        $model = M(CONTROLLER_NAME);
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if(is_array($ids)){
            if(!$ids){
                $this->error('参数错误！');
            }
            foreach($ids as $k=>$v){
                $ids[$k] = intval($v);
            }
            $map['id']  = array('in',$ids);
        }else{
            $map['id']  = $ids;
        }
        $list=$model->where($map)->select();
        $model->startTrans();
        foreach ($list as $key => $value) {
            if($value['hidlist'] == 'all'){//全部酒店
                $arrhid = M("hotel_volume")->field('hid')->select();
                $arr = array();
                foreach ($arrhid as $key => $vv) {
                    array_push($arr, $vv['hid']);
                }
                $arrmap['hid'] = array('in',$arr);
                $updatesize = M("hotel_volume")->where($arrmap)->setDec('ad_size',$value['size']);
            }else{
                $dmap['hid'] = $value['hidlist'];
                $updatesize = M("hotel_volume")->where($dmap)->setDec('ad_size',$value['size']);
            }
        }
        if($model->where($map)->delete() && $updatesize !== false){
            foreach ($list as $key => $value) {
                @unlink(FILE_UPLOAD_ROOTPATH.$value['filepath']);
            }
            if(is_array($ids)){
                $ids = implode(',',$ids);
            }
            addlog('删除'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
            $model->commit();
            $this->success('恭喜，删除成功！');
        }else{
            $model->rollback();
            $this->error('删除失败，参数错误！');
        }
    }
    public function upload(){
        $callback = array();
        if (!empty($_FILES[$_REQUEST["name"]]["name"])) {
            $upload = new \Think\Upload();
            $upload->maxSize=2097152;  //设置大小2M
            $upload->exts=array('jpg','png','jpeg');
            $upload->rootPath='./Public/';
            $upload->savePath='./upload/ad/';
            $info=$upload->uploadOne($_FILES[$_REQUEST["name"]]);
            if(!$info) {
                $callback['status'] = 0;
                $callback['info'] = $upload->getError();
            }else{
                $callback['status'] = 1;
                $callback['info']="上传成功！";
                $callback['size'] = round($info['size']/1024);
                $callback['storename']=trim($info['savepath'].$info['savename'],'.');
            }
        }else{
            $callback['status'] = 0;
            $callback['info']='缺少文件';
        }
        echo json_encode($callback);
    }
}