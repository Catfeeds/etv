<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 酒店栏目管理控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
class HotelCategoryController extends ComController {
    //查询
    public function _map(){
        $map = array();
        $data = session(session_id());
        $hotelid = 0;
        if(!empty($_POST['hid'])){
                $map['hid'] = $_POST['hid'];
                $vo=M('hotel')->getByHid($_POST['hid']);
                $hotelid=$vo['id'];
                $data['content_hid'] = $_POST['hid'];
                session(session_id(),$data); 
        }else{
            if (!empty($data['content_hid'])) {
                $map['hid'] = $data['content_hid'];
                $vo = M('hotel')->getByHid($data['content_hid']);
                $hotelid = $vo['id'];
            }else if($hid_condition = $this->isHotelMenber()){
                $map['hid'] = $hid_condition['1']['0'];
                $vo = M('hotel')->getByHid($map['hid']);
                $hotelid = $vo['id'];
            }else{
                $vv = M('hotel_category')->find();
                $vo = M('hotel')->getByHid($vv['hid']);
                $map['hid'] = $vo['hid'];
                $hotelid = $vo['id'];
            }
        }
        $this->assign('hid',$map['hid']);
        $category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
        $category = $tree->get_tree(0,$str,$hotelid);
        $this->assign('pHotel',$category);
        return $map;
    }
    //列表
    public function index(){
        $model = M(CONTROLLER_NAME);
        $map = $this->_map();
        $this->assign('hid',$map['hid']);
        $list = $model ->where($map)->order("sort asc")->select();
        $volist = $this->list_to_tree($list, 'id', 'pid', '_child', 0);
        $this -> assign("list",$volist);
        $this -> display();
    }
    //获取上级栏目
    public function get_catgory(){
        $Modeldefine = D("Modeldefine");
        $modelList=$Modeldefine->where('codevalue="100" or codevalue="101"')->select();
        foreach ($modelList as $key => $value) {
            $arr[$key]=$value['id'];
        }
        $hid = $_REQUEST['hid'];
        $map['hid']=$hid;
        $map['pid']=0;
        $map['modeldefineid']=array("in",$arr);
        $catlist = M(CONTROLLER_NAME)->where($map)->order("sort asc")->field('id,name')->select();
        echo json_encode($catlist);
    }
    //添加
    public function add(){
        $this->isHotelMenber();
        $hotelid = 0;
        $data = session(session_id());
        if(!empty($data['content_hid'])){
            $vo=M('hotel')->getByHid($data['content_hid']);
            $hotelid=$vo['id'];
            //初始化栏目上级
            $Modeldefine = D("Modeldefine");
            $modelList=$Modeldefine->where('codevalue="100" or codevalue="101"')->select();
            foreach ($modelList as $key => $value) {
                $arr[$key]=$value['id'];
            }
            $map['hid']=$data['content_hid'];
            $map['pid']=0;
            $map['modeldefineid']=array("in",$arr);
            $catlist = M(CONTROLLER_NAME)->where($map)->order("sort asc")->field('id,name')->select();
            $this->assign('pcat',$catlist);
        }
        $this->assign('hid',$data['content_hid']);
        $Langcode = D("Langcode");
        $list = $Langcode->where("status=1")->order("id asc")->select();
        $this->assign("langlist",$list);

        $Modeldefine = D("Modeldefine");
        $modeldefinelist = $Modeldefine->where("status=1")->order("codevalue asc")->select();
        $this->assign("modeldefinelist",$modeldefinelist);

        $category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
        $category = $tree->get_tree(0,$str,$hotelid);
        $this->assign('pHotel',$category);
        $this->display();
    }
    //修改
    public function edit(){
        $model = M(CONTROLLER_NAME);
        $ids = isset($_POST['ids'])?$_POST['ids']:false;
        if(count($ids)!=1){
            $this->error('参数错误，每次只能修改一条内容！');
        }
        $var = $model->getById($ids[0]);
        $this->assign('vo',$var);

        $hotelname = D("hotel")->where("hid='".$var['hid']."'")->field('hotelname')->find();
        $this->assign('hotelname',$hotelname['hotelname']);

        $Langcode = D("Langcode");
        $list = $Langcode->where("status=1")->order("id asc")->select();
        $this->assign("langlist",$list);

        $Modeldefine = D("Modeldefine");
        $modeldefinelist = $Modeldefine->where("status=1")->order("codevalue asc")->select();
        $this->assign("modeldefinelist",$modeldefinelist);

        $currentMenuType=$Modeldefine->getById($var['modeldefineid']);
        if ($currentMenuType['codevalue']=="100" || $currentMenuType['codevalue']=="101") {
            $catlist=array();
        }else{
            $modelList=$Modeldefine->where('codevalue="100" or codevalue="101"')->select();
            foreach ($modelList as $key => $value) {
                $arr[$key]=$value['id'];
            }
            $map['hid']=$var['hid'];
            $map['pid']=0;
            $map['langcodeid']=$var['langcodeid'];
            $map['modeldefineid']=array("in",$arr);
            $catlist = $model->where($map)->order("sort asc")->field('id,name')->select();
        }
        $this->assign("pcat",$catlist);
        $this -> display();
    }
    //保存
    public function update(){
        $model=M(CONTROLLER_NAME);
        $data['id'] = I('post.id','','intval');
        $data['hid'] = $hmap['hid'] = I('post.hid','','strip_tags');
        $data['name'] = isset($_POST['name'])?$_POST['name']:false;
        $data['pid'] = I('post.pid','','intval');
        $data['langcodeid'] = I('post.langcodeid','','intval');
        $data['modeldefineid'] = I('post.modeldefineid','','intval');
        $data['intro'] = I('post.intro','','strip_tags');
        $data['icon'] = I('post.icon','','strip_tags');
        $size = I('post.size','','intval');
        $data['size'] = round($size/1024,3);

        if(empty($data['hid'])){
            $this->error('警告，请选择所属酒店！',U('index'));
        }
        if(!$data['name'] or !$data['langcodeid'] or !$data['modeldefineid'] ){
            $this->error('警告！栏目信息未填完整，请补充完整！',U('index'));
        }
        if ($data['pid']>0) {
            $foo=$model->getById($data['pid']);
            if ($foo['langcodeid']!=$data['langcodeid']) {
                $this->error('警告！当前菜单要与上级菜单的语言一致！',U('index'));
            }
        }
        $vo = '';
        $model->startTrans();
        if($data['id']){//修改
            if ($data['pid']==$data['id']) {
                $this->error('警告！栏目上级不能选本身！',U('index'));
            }
            $vo = $model->getById($data['id']);

            //查询容量
            $changesize = $data['size'] - $vo['size'];
            $volumeResult = $this->checkVolume($data['hid'],$changesize);//检查容量是否超标
      
            if($volumeResult === false){
                @unlink(FILE_UPLOAD_ROOTPATH.$data['icon']);
                $this->error("超过申请容量值，无法修改资源");
            }

            $result = $model->data($data)->where('id='.$data['id'])->save();
            addlog('修改栏目信息，栏目ID：'.$data['id']);

            //修改资源时  对allresource表进行操作
            $allresourceResult = true;
            $delAllresourceResult = true;
            $addAllresourceResult = true;
            if($data['icon'] != $vo['icon']){
                //删除
                if(!empty($vo['icon'])){
                    $delName_arr = explode("/", $vo['icon']);
                    $allresourceMap['name'] = $delName_arr[count($delName_arr)-1];
                    $delAllresourceResult = $this->allresource_del($allresourceMap);
                }
                //新增
                if(!empty($data['icon'])){
                    $allresourceDate = array();
                    $allresourceDate['hid'] = $data['hid'];
                    $allresourceDate['type'] = 2;
                    $allresourceName_arr = explode("/", $data['icon']);
                    $allresourceDate['name'] = $allresourceName_arr[count($allresourceName_arr)-1];
                    $allresourceDate['timeunix'] = time();
                    $allresourceDate['time'] = date("Y-m-d H:i:s");
                    $allresourceDate['web_upload_file'] = $data['icon'];
                    $addAllresourceResult = $this->allresource_add($allresourceDate);
                }
                if($delAllresourceResult!==false && $addAllresourceResult!==false){
                    $allresourceResult = true;
                }else{
                    $allresourceResult = false;
                }
            }

        }else{//新增
            
            //查询容量
            $volumeResult = $this->checkVolume($data['hid'],$data['size']);//检查容量是否超标

            if($volumeResult === false){
                @unlink(FILE_UPLOAD_ROOTPATH.$data['icon']);
                $this->error("超过申请容量值，无法新增资源");
            }

            $data['status'] = 0;
            $sort = $model->where('pid='.$data['pid'].' and langcodeid='.$data['langcodeid'].' and hid="'.$data['hid'].'"  ')->max('sort');
            $data['sort'] = $sort+1;
            $result = $model->data($data)->add();
            addlog('添加栏目信息，栏目ID：'.$result);
            
            // 新增资源的信息保存到allresource表
            $allresourceResult = true;
            if(!empty($data['icon'])){
                $allresourceDate = array();
                $allresourceDate['hid'] = $data['hid'];
                $allresourceName_arr = explode("/", $data['icon']);
                $allresourceDate['name'] = $allresourceName_arr[count($allresourceName_arr)-1];
                $allresourceDate['type'] = 2;
                $allresourceDate['timeunix'] = time();
                $allresourceDate['time'] = date("Y-m-d H:i:s");
                $allresourceDate['web_upload_file'] = $data['icon'];
                $allresourceResult = $this->allresource_add($allresourceDate);
            }

        }
        $sizelist = M('hotel_resource')->field('SUM(size)')->where($hmap)->select();
        $csizelist = M('hotel_category')->field('SUM(size)')->where($hmap)->select();
        $rsize = $sizelist[0]['sum(size)']+$csizelist[0]['sum(size)'];
        if(M("hotel_volume")->where($hmap)->count()){
            $updatesize = D("hotel_volume")->where($hmap)->setField("content_size",$rsize);
        }else{
            $arrdata['hid'] = $data['hid'];
            $arrdata['content_size'] = $rsize;
            $arrdata['topic_size'] = 0.00;
            $arrdata['ad_size'] = 0.00;
            $updatesize = M("hotel_volume")->data($arrdata)->add();
        }

        if($result !== false && $updatesize !== false && $allresourceResult!==false){
            $model->commit();
            
            //allresource资源写入xml文件
            $xmlFilepath = FILE_UPLOAD_ROOTPATH.'/upload/resourceXml/'.$data['hid'].'.txt';
            $xmlResult = $this->fileputXml(D("hotel_allresource"),$data['hid'],$xmlFilepath);

            if($data['id']){
                if(!empty($vo)){
                    if($data['icon'] != $vo['icon']){
                        @unlink(FILE_UPLOAD_ROOTPATH.$vo['icon']);
                    } 
                }
            }
            $this->success('恭喜，操作成功！',U('index'));
        }else{
            $model->rollback();
            $this->error('操作失败！',U('index'));
        }
    }
    public function upload_icon(){
        $callback = array();
        if (!empty($_FILES[$_REQUEST["name"]]["name"])) {
            if(empty($_REQUEST['hid'])){
                $callback['status'] = 0;
                $callback['info'] = "请选择酒店";
            }else{
                $upload = new \Think\Upload();
                $upload->maxSize=2097152;  //设置大小为2M
                $upload->exts=array('jpg','png','jpeg');
                $upload->rootPath='./Public/';
                $upload->savePath='./upload/content/'.$_REQUEST['hid'].'/';
                $upload->autoSub = false;
                $upload->saveName = time().'_'.mt_rand();
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
            }
        }else{
            $callback['status'] = 0;
            $callback['info']='缺少文件';
        }
        echo json_encode($callback);
    }

    public function delfilepath(){
        if($_POST['filepath']){
            @unlink(FILE_UPLOAD_ROOTPATH.$_POST['filepath']);
        }
        echo true;
    }

    //删除、批量删除
    public function delete(){

        $ids = I('post.ids');
        if(empty(intval($ids['0']))){
            $this->error('参数错误！');  
        }

        $map['zxt_hotel_category.id'] = $ids['0'];
        $field = "zxt_hotel_category.id,zxt_hotel_category.hid,zxt_hotel_category.icon,zxt_hotel_category.pid,zxt_hotel_category.size,zxt_modeldefine.codevalue";
        $vo = D("hotel_category")->where($map)->field($field)->join('zxt_modeldefine ON zxt_hotel_category.modeldefineid=zxt_modeldefine.id')->find();

        if($vo['pid'] == 0){
            $Category = D("hotel_category")->where('pid="'.$vo['id'].'"')->find();
            if(!empty($Category)){
                $this->error('该栏目下含有二级栏目，请先删除该栏目下的二级栏目');
                die();
            }
        }else{
            if($vo['codevalue'] == 501){
                $carouselMap['hid'] = $vo['hid'];
                $carouselMap['cid'] = $vo['id'];
                $Resource = D("hotel_carousel_resource")->where($carouselMap)->find();
            }else{
                $hotelMap['hid'] = $vo['hid'];
                $hotelMap['category_id'] = $vo['id'];
                $Resource = D("hotel_resource")->where($hotelMap)->find();
            }
            if(!empty($Resource)){
                $this->error('该栏目下含有资源，请先删除该栏目下资源再删除栏目');
                die();
            }
        }

        $model = M(CONTROLLER_NAME);
        $model->startTrans();
        $delAllresourceResult_f = true;
        
        $vmap['hid']=$vo['hid'];
        $vresult = $this->csetDec($vmap,'content_size',$vo['size']); //减少容量

        //更新资源表
        if(!empty($vo['icon'])){
            $delName_arr_f = explode("/", $vo['icon']);
            $allresourceMap_f['name'] = $delName_arr_f[count($delName_arr_f)-1];
            $delAllresourceResult_f = D("hotel_allresource")->where($allresourceMap_f)->delete();
        }

        //删除二级菜单
        $delCategoryResult = $model->where($map)->delete();

        if($vresult!==false && $delAllresourceResult_f!==false && $delCategoryResult!==false){
            @unlink(FILE_UPLOAD_ROOTPATH.$vo['icon']);
            addlog('删除hotel_category表数据');

            //allresource资源写入xml文件
            $xmlFilepath = FILE_UPLOAD_ROOTPATH.'/upload/resourceXml/'.$vo['hid'].'.txt';
            $xmlResult = $this->fileputXml(D("hotel_allresource"),$vo['hid'],$xmlFilepath);

            $model->commit();
            $this->success('恭喜，删除成功！');
        }else{
            $model->rollback();
            $this->error('删除失败，参数错误！');
        }
    }

    //更新栏目排序
    public function sort() {
        $menuids = explode(",", $_REQUEST['menuid']);
        $sorts = explode(",",$_REQUEST['sort']);
        $model = M(CONTROLLER_NAME);
        for ($i = 0; $i < count($sorts); $i++) {
            $model->where('id='.$menuids[$i])->setField('sort',$sorts[$i]);
        }
        $this->success('恭喜，操作成功！');
    }
    //栏目复制
    public function copy() {
        $model = M(CONTROLLER_NAME);
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if(is_array($ids)){
            if(!$ids){
                $this->error('参数错误！请选择要复制的内容！');
            }
            $map['id']  = array('in',$ids);
        }else{
            if(!$ids){
                $this->error('参数错误！请选择要复制的内容！');
            }
            $map['id']  = $ids;
        }
        //利用group方法去重
        $data = $model->where($map)->group('langcodeid')->select();
        if (count($data)>1) {
            $this->error('所选栏目的语言只能是同一种，请选择同一语言的栏目作为复制的源！');
        }
        $list=$model->where($map)->select();
        /*若所选菜单为子栏目但没选上级，系统补上上级栏目*/
        $p_num=0;
        $p_menu=array();
        $select_menuid=array();
        foreach ($list as $key => $value) {
            $select_menuid[$key]=$value['id'];
            if ($value['pid']>0) {
                $p_menu[$p_num]=$value['pid'];
                $p_num++;
            }
        }
        $menuIDs = array_merge($select_menuid, $p_menu);
        $menuIDs = array_unique($menuIDs);
        $menuIDs = array_values($menuIDs);
        $vomap['id']=array('in',$menuIDs);
        $folist = $model ->where($vomap)->order("pid asc,sort asc")->select();
        $volist = $this->list_to_tree($folist, 'id', 'pid', '_child', 0);
        $this->assign("volist",$volist);//要复制的栏目源
        $this->assign("srcLang",$data[0]['langcodeid']);//所选语言
        $this->assign("hid",$data[0]['hid']);//所属酒店编号
        $this->assign("menuIDs",implode(',', $menuIDs));

        $Langcode = D("Langcode");
        $list = $Langcode->where("status=1")->order("id asc")->select();
        $this->assign("langlist",$list);

        $this->display();
    }
    public function copy_run(){
        $hid=$_REQUEST['hid'];
        $langA = $_REQUEST['srcLang'];
        $langB = $_REQUEST['dstLang'];
        $timestamp=time();
        if (empty($langB)) {
            $this->error("请选择要复制的目标语言！");
        }
        $menuIDs=$_REQUEST['menuIDs'];
        $Menu = D("HotelCategory");
        $Resource = D("HotelResource");
        $map=array();
        $map['hid']=$hid;
        $map['pid']=0;
        $map['langcodeid']=$langA;
        $map['id']=array('in',$menuIDs);
        $srcOneMenu=$Menu->where($map)->order('sort asc')->select();
        foreach ($srcOneMenu as $key => $value) {
            //复制一级栏目
            $sort=0;
            $data=array();
            //B语言当前一级栏目最大排序
            $sort = $Menu->where('pid=0 and langcodeid='.$langB.' and hid="'.$hid.'"  ')->max('sort');
            $data[$key]['hid'] = $hid;
            $data[$key]['name'] = $value['name'].$timestamp;
            $data[$key]['modeldefineid'] = $value['modeldefineid'];
            $data[$key]['langcodeid'] = $langB;
            $data[$key]['pid'] = 0;
            $data[$key]['sort'] = $sort+1;
            $data[$key]['intro'] = $value['intro'];
            $data[$key]['icon'] = $value['icon'];
            $data[$key]['status'] = $value['status'];
            $oneCatID = $Menu->add($data[$key]);
            //复制一级栏目的资源
            $srcOneResource=$Resource->where('category_id='.$value['id'])->order('sort asc')->select();
            foreach ($srcOneResource as $keyOne => $valueOne) {
                $Rdata=array();
                $Rdata[$keyOne]['hid']=$valueOne['hid'];
                $Rdata[$keyOne]['title']=$valueOne['title'];
                $Rdata[$keyOne]['type']=$valueOne['type'];
                $Rdata[$keyOne]['cat']=$valueOne['cat'];
                $Rdata[$keyOne]['filepath']=$valueOne['filepath'];
                $Rdata[$keyOne]['intro']=$valueOne['intro'];
                $Rdata[$keyOne]['audit_status']=$valueOne['audit_status'];
                $Rdata[$keyOne]['upload_time']=$valueOne['upload_time'];
                $Rdata[$keyOne]['video_image']=$valueOne['video_image'];
                $Rdata[$keyOne]['price']=$valueOne['price'];
                $Rdata[$keyOne]['sort']=$valueOne['sort'];
                $Rdata[$keyOne]['status']=$valueOne['status'];
                $Rdata[$keyOne]['category_id']=$oneCatID;
                $resultR = $Resource->add($Rdata);
            }
            $twoMap=array();
            $twoMap['hid']=$hid;
            $twoMap['pid']=$value['id'];
            $twoMap['langcodeid']=$langA;
            $twoMap['id']=array('in',$menuIDs);
            $srcTwoMenu=$Menu->where($twoMap)->order('sort asc')->select();
            foreach ($srcTwoMenu as $keyTwoM => $valueTwoM) {
                //复制二级栏目
                $dataTwo=array();
                $sortTwo=0;
                $sortTwo = $Menu->where('pid='.$oneCatID.' and langcodeid='.$langB.' and hid="'.$hid.'"  ')->max('sort');
                $dataTwo[$keyTwoM]['hid'] = $hid;
                $dataTwo[$keyTwoM]['name'] = $valueTwoM['name'].$timestamp;
                $dataTwo[$keyTwoM]['modeldefineid'] = $valueTwoM['modeldefineid'];
                $dataTwo[$keyTwoM]['langcodeid'] = $langB;
                $dataTwo[$keyTwoM]['pid'] = $oneCatID;
                $dataTwo[$keyTwoM]['sort'] = $sortTwo+1;
                $dataTwo[$keyTwoM]['intro'] = $valueTwoM['intro'];
                $dataTwo[$keyTwoM]['icon'] = $valueTwoM['icon'];
                $dataTwo[$keyTwoM]['status'] = $valueTwoM['status'];
                $twoCatID = $Menu->add($dataTwo[$keyTwoM]);
                //复制二级栏目的资源
                $srcTwoResource=$Resource->where('category_id='.$valueTwoM['id'])->order('sort asc')->select();
                foreach ($srcTwoResource as $keyTwoR => $valueTwoR) {
                    $twoRdata=array();
                    $twoRdata[$keyTwoR]['hid']=$valueTwoR['hid'];
                    $twoRdata[$keyTwoR]['title']=$valueTwoR['title'];
                    $twoRdata[$keyTwoR]['type']=$valueTwoR['type'];
                    $twoRdata[$keyTwoR]['cat']=$valueTwoR['cat'];
                    $twoRdata[$keyTwoR]['filepath']=$valueTwoR['filepath'];
                    $twoRdata[$keyTwoR]['intro']=$valueTwoR['intro'];
                    $twoRdata[$keyTwoR]['audit_status']=$valueTwoR['audit_status'];
                    $twoRdata[$keyTwoR]['upload_time']=$valueTwoR['upload_time'];
                    $twoRdata[$keyTwoR]['video_image']=$valueTwoR['video_image'];
                    $twoRdata[$keyTwoR]['price']=$valueTwoR['price'];
                    $twoRdata[$keyTwoR]['sort']=$valueTwoR['sort'];
                    $twoRdata[$keyTwoR]['status']=$valueTwoR['status'];
                    $twoRdata[$keyTwoR]['category_id']=$twoCatID;
                    $resultRtwo = $Resource->add($twoRdata);
                }
            }
        }
        $this->success('恭喜，操作成功！',U('index').'?hid='.$hid);
    }

    public function resource(){
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if(is_array($ids)){
            if(count($ids)>1){
                $this->error('参数错误，每次只能管理一个栏目的资源！');
            }
            $category_id=$ids[0];
        }else{
            if(!$ids){
                $this->error('参数错误，请选择栏目！');
            }
            $category_id=$ids;
        }

        $vo = D("hotel_category")->getById($category_id);
        if ($vo['pid']==0) {
            $this->error('该栏目没有资源管理！',U('index').'?hid='.$vo['hid']);
        }
        $this->assign("current_category",$vo);
        $Modeldefine=M('modeldefine');
        $fo=$Modeldefine->getById($vo['modeldefineid']);
        if($fo['codevalue']==501){
            $map['cid'] = $category_id;
            $map['ctype'] = 'videohotel';
            $list = M('HotelCarouselResource')->where($map)->order("sort asc")->select();
            $this->assign('ctype','videohotel');
            $prevcontroller = U("HotelCategory/index");
            $this->assign('prevcontroller',$prevcontroller);
            $this->assign('incontroller','hotel_category');
            $this->assign('gocontroller','HotelCategory');
            $this -> assign("list",$list);
            $this->display("Carousel/resource");
        }else{
            $map['category_id']=$category_id;
            $map['cat']="content";
            $list = M('HotelResource')->where($map)->order("sort asc")->select();
            $this -> assign("list",$list);
            $this->display();
        }

    }
    //删除、批量删除资源
    public function resource_delete(){
        $model = M("HotelResource");
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
        $list = $model->where($map)->select();
        $model->startTrans();
        $getName = array();
        foreach ($list as $key => $value) {
            $vmap['hid']=$value['hid'];
            //更改volume表
            $vresult = $this->csetDec($vmap,'content_size',$value['size']);
            //需要删除的资源名称
            if(!empty($value['filepath'])){
                $getName_arr = explode("/", $value['filepath']);
                $getName[] = $getName_arr[count($getName_arr)-1];
            }
            if(!empty($value['video_image'])){
                $getName_arr = explode("/", $value['video_image']);
                $getName[] = $getName_arr[count($getName_arr)-1];
            }
        }

        //删除资源表
        $delAllresourceResult = true;
        if(!empty($getName)){
            $delAllresourceMap['name'] = array('in',$getName);
            $delAllresourceResult = D("hotel_allresource")->where($delAllresourceMap)->delete();
        }

        if($model->where($map)->delete() && $delAllresourceResult!==false){
            if(is_array($ids)){
                $ids = implode(',',$ids);
            }
            addlog('删除资源表数据，数据ID：'.$ids);
            $model->commit();
            foreach ($list as $key => $value) {
                //删除对应文件
                @unlink(FILE_UPLOAD_ROOTPATH.$value['filepath']);
                @unlink(FILE_UPLOAD_ROOTPATH.$value['video_image']);
            }
            $this->success('恭喜，删除成功！',U('resource').'?ids='.$_REQUEST['category_id']);
        }else{
            $model->rollback();
            $this->error('删除失败，参数错误！',U('resource').'?ids='.$_REQUEST['category_id']);
        }
    }
    public function resource_sort() {
        $menuids = explode(",", $_REQUEST['menuid']);
        $sorts = explode(",",$_REQUEST['sort']);
        $Resource = D("HotelResource");
        for ($i = 0; $i < count($sorts); $i++) {
            $Resource->where('id='.$menuids[$i])->setField('sort',$sorts[$i]);
        }
        $this->success('恭喜，操作成功！',U('resource').'?ids='.$_REQUEST['category_id']);
    }
    public function resource_unlock(){
        $model = M("HotelResource");
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if($ids){
            if(is_array($ids)){
                $ids = implode(',',$ids);
                $map['id']  = array('in',$ids);
            }else{
                $map = 'id='.$ids;
            }
            $result = $model->where($map)->setField("status",1);
            if($result !== false){
                addlog('启用'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
                $this->success('恭喜，启用成功！',U('resource').'?ids='.$_REQUEST['category_id']);
            }else{
                $this->error('启用失败，参数错误！',U('resource').'?ids='.$_REQUEST['category_id']);
            }
        }else{
            $this->error('参数错误！',U('resource').'?ids='.$_REQUEST['category_id']);
        }
    }
    public function resource_lock(){
        $model = M("HotelResource");
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if($ids){
            if(is_array($ids)){
                $ids = implode(',',$ids);
                $map['id']  = array('in',$ids);
            }else{
                $map = 'id='.$ids;
            }
            $result = $model->where($map)->setField("status",0);
            if($result !== false){
                addlog('禁用'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
                $this->success('恭喜，禁用成功！',U('resource').'?ids='.$_REQUEST['category_id']);
            }else{
                $this->error('禁用失败，参数错误！',U('resource').'?ids='.$_REQUEST['category_id']);
            }
        }else{
            $this->error('参数错误！',U('resource').'?ids='.$_REQUEST['category_id']);
        }
    }
    //添加
    public function resource_add(){
        $model = M(CONTROLLER_NAME);
        $category_id=$_REQUEST['category_id'];
        $vo=$model->getById($category_id);
        $this->assign("current_category",$vo);
        $Modeldefine=M('modeldefine');
        $fo=$Modeldefine->getById($vo['modeldefineid']);
        if ($vo['pid']==0) {
            //一级栏目的资源只能是图片
            $type=2;//图片
        }else if ($fo['codevalue']=="103") {
            $type=1;//视频
        }else if ($fo['codevalue']=="102") {
            $type=2;//图片
        }else{
            $this->error('该栏目不可添加资源！',U('resource').'?ids='.$category_id);
        }
        $this->assign("resource_type",$type);
        $this->display();
    }
    //修改
    public function resource_edit(){
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if(count($ids)!=1){
            $this->error('参数错误，每次请修改一条内容！');
        }
        $Resource = M("HotelResource");
        $fo=$Resource->getById($ids[0]);
        $filepath = $fo['filepath'];
        $video_image = $fo['video_image'];
        if(!empty($filepath)){
            $fo['size1'] = round(filesize(FILE_UPLOAD_ROOTPATH.$filepath)/1024);
        }else{
            $fo['size1'] = 0;
        }
        if(!empty($video_image)){
            $fo['size2'] = round(filesize(FILE_UPLOAD_ROOTPATH.$video_image)/1024);
        }else{
            $fo['size2'] = 0;
        }
        
        $this->assign('vo',$fo);
        $this -> display();
    }
    //保存
    public function resource_update(){
        $model=M('HotelResource');
        $data['id'] = I('post.id','','intval');
        $data['hid'] = I('post.hid','','strip_tags');
        $data['title'] = isset($_POST['title'])?$_POST['title']:false;
        $data['intro'] = I('post.intro','','strip_tags');
        $data['sort'] = I('post.sort','','intval');
        $data['filepath'] = I('post.filepath','','strip_tags');
        $data['video_image'] = I('post.vimage','','strip_tags');
        $data['price'] = I('post.price','','strip_tags');
        $data['status'] = 0;
        $codevalue = I('post.codevalue','','intval');
        $data['audit_status'] = 0;
        $size1 = I('post.size1','','intval');
        $size2 = I('post.size2','','intval');
        $size = $size1 + $size2;
        $data['size'] = round($size/1024,3);

        $hmap['hid'] = I('post.hid');
        if(!$data['title']){
            $this->error('警告！资源标题必须填写！');
        }
        if (empty($data['filepath'])) {
            $this->error('请上传资源文件！');
        }
        $model->startTrans();
        $vo = '';

        if($data['id']){//修改
            $vo = $model->getById($data['id']);

            //查询容量
            $changesize = $data['size'] - $vo['size'];
            $volumeResult = $this->checkVolume($data['hid'],$changesize);//检查容量是否超标

            if($volumeResult === false){
                if($vo['video_image'] != $data['video_image']){
                    @unlink(FILE_UPLOAD_ROOTPATH.$data['video_image']);
                    
                }
                if($vo['filepath'] != $data['filepath']){
                    @unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
                    
                }
                $this->error("超过申请容量值，无法修改资源");
            }

            $result = $model->data($data)->where('id='.$data['id'])->save();
            addlog('修改栏目资源，ID：'.$data['id']);

            //修改资源时 对allresource表进行操作 记录所存资源
            $delAllresourceResult_f = true;
            $delAllresourceResult_q = true;
            $addAllresourceResult_f = true;
            $addAllresourceResult_q = true;
            if($data['filepath'] != $vo['filepath']){
                //删除
                if(!empty($vo['filepath'])){
                    $delName_arr_f = explode("/", $vo['filepath']);
                    $allresourceMap_f['name'] = $delName_arr_f[count($delName_arr_f)-1];
                    $delAllresourceResult_f = $this->allresource_del($allresourceMap_f);
                }
                //新增
                if(!empty($data['filepath'])){
                    $allresourceDate_f['hid'] = $data['hid'];
                    $allresourceDate_f['type'] = $vo['type'];
                    $allresourceName_arr_f = explode("/", $data['filepath']);
                    $allresourceDate_f['name'] = $allresourceName_arr_f[count($allresourceName_arr_f)-1];
                    $allresourceDate_f['timeunix'] = time();
                    $allresourceDate_f['time'] = date("Y-m-d H:i:s");
                    $allresourceDate_f['web_upload_file'] = $data['filepath'];
                    $addAllresourceResult_f = $this->allresource_add($allresourceDate_f);
                }
            }
            if($data['video_image'] != $vo['video_image']){
                //删除
                if(!empty($vo['video_image'])){
                    $delName_arr_q = explode("/", $vo['video_image']);
                    $allresourceMap_q['name'] = $delName_arr_q[count($delName_arr_q)-1];
                    $delAllresourceResult_q = $this->allresource_del($allresourceMap_q);
                }
                //新增
                if(!empty($data['video_image'])){
                    $allresourceDate_q['hid'] = $data['hid'];
                    $allresourceDate_q['type'] = $vo['type'];
                    $allresourceName_arr_q = explode("/", $data['video_image']);
                    $allresourceDate_q['name'] = $allresourceName_arr_q[count($allresourceName_arr_q)-1];
                    $allresourceDate_q['timeunix'] = time();
                    $allresourceDate_q['time'] = date("Y-m-d H:i:s");
                    $allresourceDate_q['web_upload_file'] = $data['video_image'];
                    $addAllresourceResult_q = $this->allresource_add($allresourceDate_q);
                }
            }
            if($delAllresourceResult_f!==false && $delAllresourceResult_q!==false){
                $delAllresourceResult = true;
            }else{
                $delAllresourceResult = false;
            }
            if($addAllresourceResult_f!==false && $addAllresourceResult_q!==false){
                $addAllresourceResult = true;
            }else{
                $addAllresourceResult = false;
            }

            if($delAllresourceResult!==false && $addAllresourceResult!==false){
                $allresourceResult = true;
            }else{
                $allresourceResult = false;
            }

        }else{//新增
            
            //查询容量
            $volumeResult = $this->checkVolume($data['hid'],$data['size']);//检查容量是否超标

            if($volumeResult === false){
                @unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
                @unlink(FILE_UPLOAD_ROOTPATH.$data['video_image']);
                $this->error("超过申请容量值，无法新增资源");
            }
            
            $data['type'] = I('post.type','','intval');
            $data['cat'] = 'content';
            $data['category_id'] = I('post.category_id','','intval');
            $data['upload_time'] = time();
            $result = $model->data($data)->add();
            addlog('添加栏目资源，ID：'.$result);

            // 新增资源的信息保存到allresource表
            $allresourceResult_f = true;
            $allresourceResult_q = true;
            if(!empty($data['filepath'])){
                $allresourceDate_f['hid'] = $data['hid'];
                $allresourceDate_f['type'] = $data['type'];
                $allresourceName_arr_f = explode("/", $data['filepath']);
                $allresourceDate_f['name'] = $allresourceName_arr_f[count($allresourceName_arr_f)-1];
                $allresourceDate_f['timeunix'] = time();
                $allresourceDate_f['time'] = date("Y-m-d H:i:s");
                $allresourceDate_f['web_upload_file'] = $data['filepath'];
                $allresourceResult_f = $this->allresource_add($allresourceDate_f);
            }
            if(!empty($data['video_image'])){
                $allresourceDate_q['hid'] = $data['hid'];
                $allresourceDate_q['type'] = $data['type'];
                $allresourceName_arr_f = explode("/", $data['video_image']);
                $allresourceDate_q['name'] = $allresourceName_arr_f[count($allresourceName_arr_f)-1];
                $allresourceDate_q['timeunix'] = time();
                $allresourceDate_q['time'] = date("Y-m-d H:i:s");
                $allresourceDate_q['web_upload_file'] = $data['video_image'];
                $allresourceResult_q = $this->allresource_add($allresourceDate_q);
            }
            if($allresourceResult_f && $allresourceResult_q){
                $allresourceResult == true;
            }else{
                $allresourceResult == false;
            }
        }
        $sizelist = M('hotel_resource')->field('SUM(size)')->where($hmap)->select();
        $csizelist = M('hotel_category')->field('SUM(size)')->where($hmap)->select();
        $rsize = $sizelist[0]['sum(size)']+$csizelist[0]['sum(size)'];

        if(M("hotel_volume")->where($hmap)->count()){
            $updatesize = D("hotel_volume")->where($hmap)->setField("content_size",$rsize);
        }else{
            $arrdata['hid'] = $data['hid'];
            $arrdata['content_size'] = $rsize;
            $arrdata['topic_size'] = 0.00;
            $arrdata['ad_size'] = 0.00;
            $updatesize = M("hotel_volume")->data($arrdata)->add();
        }

        if($result !== false && $updatesize !== false && $allresourceResult!==false){
            $model->commit();

            //allresource资源写入xml文件
            $xmlFilepath = FILE_UPLOAD_ROOTPATH.'/upload/resourceXml/'.$data['hid'].'.txt';
            $xmlResult = $this->fileputXml(D("hotel_allresource"),$data['hid'],$xmlFilepath);

            if(!empty($vo)){
                if($data['filepath'] != $vo['filepath']){
                    @unlink(FILE_UPLOAD_ROOTPATH.$vo['filepath']);
                }
                if ($data['video_image'] != $vo['video_image']) {
                    @unlink(FILE_UPLOAD_ROOTPATH.$vo['video_image']);
                }
            }
            $this->success('恭喜，操作成功！',U('resource').'?ids='.$_REQUEST['category_id']);
        }else{
            $model->rollback();
            $this->error('操作失败',U('resource').'?ids='.$_REQUEST['category_id']);
        }
    }
    public function upload(){
        $callback = array();
        if (!empty($_FILES[$_REQUEST["name"]]["name"])) {
            if(empty($_REQUEST['hid'])){
                $callback['status'] = 0;
                $callback['info'] = "请选择酒店";
            }else{
                $upload = new \Think\Upload(); //实例化上传类
                if ($_REQUEST['filetype']==1) {
                    $upload->exts=array('mp4');
                    $upload->maxSize=209715200;// 设置附件上传大小200M
                }else if ($_REQUEST['filetype']==2){
                    $upload->maxSize=2097152;// 设置附件上传大小2M
                    $upload->exts=array('jpg','png','jpeg');
                }else{
                    $callback['status'] = 0;
                    $callback['info']='未知错误！';
                    echo json_encode($callback);
                    exit;
                }
                $upload->rootPath='./Public/'; //保存根路径
                $upload->savePath='./upload/content/'.$_REQUEST['hid'].'/';
                $upload->autoSub = false;
                $upload->saveName = time().'_'.mt_rand();
                //单文件上传
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
            }
        }else{
            $callback['status'] = 0;
            $callback['info']='缺少文件';
        }
        echo json_encode($callback);
    }

    //测试 xml生成并写入文件
    public function test_xml(){
        $hid = '1';
        $model = D("hotel_allresource");
        $filepath = FILE_UPLOAD_ROOTPATH.'/upload/resourceXml/'.$hid.'.txt';
        $result = $this->fileputXml($model,$hid,$filepath);
        var_dump($result);
    }

}