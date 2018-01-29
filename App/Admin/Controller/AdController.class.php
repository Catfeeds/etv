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
        $hid_condition = $this->isHotelMenber();
        $ad_id_arr = [];
        if ($hid_condition) {   
            $where_bind['hid'] = $hid_condition;
            $bindlist = D("ad_bind")->where($where_bind)->field('ad_id')->distinct(true)->select();
            if (!empty($bindlist)) {
                foreach ($bindlist as $key => $value) {
                    $ad_id_arr[] = $value['ad_id'];
                }
                $map['id'] = ['in', $ad_id_arr];
            }else{
                $map['id'] = 0;
            }
        }
        $list = $this->_list($model,$map);
        $this -> assign("list",$list);
        if (!empty($list['0'])) {
            $firstbind = D("ad_bind")->where('ad_id="'.$list['0']['id'].'"')->field('hid')->select();
            if (!empty($firstbind)) {
                foreach ($firstbind as $key => $value) {
                    $where_hid_arr[] = $value['hid'];
                }
                $where_hotel['hid'] = ['in', $where_hid_arr];
                $hotellist = D('hotel')->where($where_hotel)->field('name')->select();
                foreach ($hotellist as $key => $value) {
                    $hotelname .= $value['name'].", ";
                }
                $hotelname = trim($hotelname, ", ");
            }
        }else{
            $hotelname = "-";
        }
        $list['0']['hidlist']=trim($hotelname,',');
        
        $this -> assign("vo",$list['0']);
        $this -> display();
    }

    public function _before_add(){
        $regionList = $this->_hotel_list();
        $hid_condition = $this->isHotelMenber();
        $map = [];
        if ($hid_condition) {   
            $map['hid'] = $hid_condition;
        }
        $regionList = $this->_new_hotel_list($map);
        $this->assign('menuTree', $regionList);
    }

    public function edit(){
        if(count($_REQUEST['ids'])!=1){
            $this->error("只能修改一条记录");
            die();
        }
        $bind_list = array();   //绑定hid数据集合
        $hidsArr = array(); //hid集合
        $id = $_REQUEST['ids'][0];
        $list = D("ad")->where('id='.$id)->find();
        if ($hid_condition) {   
            $map['hid'] = $hid_condition;
        }
        $map = [];
        $hid_condition = $this->isHotelMenber();
        if ($hid_condition) {   
            $map['hid'] = $hid_condition;
        }
        $regionList = $this->_new_hotel_list($map);
        if ($regionList) {
            $bind_list = D("ad_bind")->where('ad_id='.$id)->field('hid')->select();
            foreach ($bind_list as $key => $value) {
                $hidsArr[] = $value['hid'];
            }
            foreach ($regionList as $key => $value) {
                if(!empty($value['sub'])){ 
                    foreach ($value['sub'] as $kk => $vv) {
                        if(in_array($vv['hid'], $hidsArr)){
                            $value['sub'][$kk]['isselect'] = 1;
                        }else{
                            $value['sub'][$kk]['isselect'] = 0;
                        }
                    }
                }
                $regionList[$key] = $value;
            }
        }

        $this->assign('menuTree', $regionList);
        if (empty($bind_list)) {
            $list['hotelname']='/';
        }else{
            $hotelname='';
            $hotel_where['hid'] = array('in',$hidsArr);
            $hotelnamelist = D("hotel")->where($hotel_where)->field('hotelname')->select();
            if (!empty($hotelnamelist)) {
                foreach ($hotelnamelist as $key => $value) {
                    $hotelname_arr[] = $value['hotelname'];
                }
                $list['hotelname'] = implode(",", $hotelname_arr);
            }
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
        $hidlist = trim(I('post.hidlist','','strip_tags'));
        $passhid_arr = explode(",", $hidlist);
        $size = I('post.size','','intval');
        $data['size'] = round($size/1024,3);
        if (empty($data['filepath'])) {
            $this->error('请上传图片文件！');
        }
        $vo = '';
        $model->startTrans();

        if($data['id']){
            // 获取广告信息
            $vo = $model->getById($data['id']);
            if ($data['filepath'] != $vo['filepath']) {
                $data['upload_time'] = time();
            }
            $result = $model->data($data)->where('id='.$data['id'])->save();
            // 获取绑定hid列表
            $bind_hidlist = D("ad_bind")->where('ad_id='.$data['id'])->field('hid')->select();
            $bind_hid_arr = [];
            if (!empty($bind_hidlist)) {
                foreach ($bind_hidlist as $key => $value) {
                    $bind_hid_arr[] = $value['hid'];
                }
            }
            
            $add_hid = array_diff($passhid_arr, $bind_hid_arr);// 新增hid
            $del_hid = array_diff($bind_hid_arr, $passhid_arr);// 删除hid
            
            // 新增绑定表
            if (!empty($add_hid)) {
                foreach ($add_hid as $key => $value) {
                    $add_bind_info[$key]['ad_id'] = $data['id'];
                    $add_bind_info[$key]['hid'] = $value;
                }
                try {
                    D('ad_bind')->addAll($add_bind_info);
                } catch (Exception $e) {
                    $model->rollback();
                    $this->error('操作失败！',U('index'));
                }
            }
            // 删除绑定表
            if (!empty($del_hid)) {
                $del_bind_where['ad_id'] = $data['id'];
                $del_bind_where['hid'] = array('in', $del_hid);
                try {
                    D('ad_bind')->where($del_bind_where)->delete();
                } catch (Exception $e) {
                    $model->rollback();
                    $this->error('操作失败！',U('index'));
                }
            }
            $editvolumeresult = $this->editsize($data['id'],$del_hid);
        }else{
            $data['upload_time'] = time();
            $result = $model->data($data)->add();
            // 新增绑定表
            foreach ($passhid_arr as $key => $value) {
                $add_bind_info[$key]['ad_id'] = $result;
                $add_bind_info[$key]['hid'] = $value;
            }
            try {
                D('ad_bind')->addAll($add_bind_info);  
            } catch (Exception $e) {
                $this->error('操作失败！',U('index'));
            }
            $editvolumeresult = $this->addsize($data['size'],$passhid_arr);
        }
       
        if($result !== false && $editvolumeresult !== false){
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

    private function addsize($nowsize, $nowhid = array()){
        if (empty($nowhid)) {
            $where_hid['hid'] = array('in', $nowhid);
            try {
                D("hotel_volume")->where($where_hid)->setInc('ad_size',$nowsize);
            } catch (Exception $e) {
                return false;
            }
            return true;
        }else{
            return false;
        }
    }

    public function editsize($id=11, $diffhid = array()){
        if (!$id) {
            return false;
        }
        $sql = "SELECT hid, GROUP_CONCAT(ad_id) as id_str FROM `zxt_ad_bind` WHERE hid in (SELECT hid FROM `zxt_ad_bind` WHERE ad_id = $id) ";
        if (!empty($diffhid)) {
            $sql .= " OR hid in (".implode(",", $diffhid).")";
        }
        $sql .= " GROUP BY hid";
        $bindlist = D("ad_bind")->query($sql);
        if (!empty($bindlist)) {
            foreach ($bindlist as $key => $value) {
                $where_id = $value['id_str'];
                $current_hid = $value['hid'];
                $hid_arr[] = $value['hid'];
                $sql_ad = "SELECT $current_hid as current_hid, SUM(size) as current_size FROM `zxt_ad` WHERE id IN($where_id)";
                $sizelist = D('ad')->query($sql_ad);
                $update_info[] = $sizelist['0'];
            }
            $sql_volume = "UPDATE `zxt_hotel_volume` SET ad_size = CASE";
            foreach ($update_info as $key => $value) {
                $sql_volume .= sprintf(" WHEN '%s' THEN %s", $value['current_hid'], $value['current_size']);
            }
            $hid_str = implode(",", $hid_arr);
            $sql_volume .= " END WHERE hid IN($hid_str)";
            try {
                D('hotel_volume')->execute($sql_volume);
            } catch (Exception $e) {
                return false;
            }
            return true;
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