<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 酒店通用栏目管理控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
class ChgController extends ComController {
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
        $category = M('hotel')->where('pid=0')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
        $category = $tree->get_tree(0,$str,$hotelid);
        $this->assign('pHotel',$category);
        return $map;
    }

    public function index(){
    	$model = D("hotel_chg_category");
        $array = $this->_map();
        $hid_condition = $this->isHotelMenber();
        if($hid_condition && empty($array['hotelid'])){
            $map['hid'] = $hid_condition['1']['0'];
        }else{
            $map['hid'] = $array['hid'];
        }
        $this->assign('hid',$map['hid']);
        $list = $model ->where($map)->order("sort asc")->select();
        $volist = $this->list_to_tree($list, 'id', 'pid', '_child', 0);
        $this -> assign("list",$volist);
        $this -> display();
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
            $catlist = M("hotel_chg_category")->where($map)->order("sort asc")->field('id,name')->select();
            $this->assign('pcat',$catlist);
        }
        $this->assign('hid',$data['content_hid']);
        //初始化语言模型
        $Langcode = D("Langcode");
        $list = $Langcode->where("status=1")->order("id asc")->select();
        $this->assign("langlist",$list);

        //初始化模型
        $Modeldefine = D("Modeldefine");
        $modeldefinelist = $Modeldefine->where("status=1")->order("codevalue asc")->select();
        $this->assign("modeldefinelist",$modeldefinelist);

        $category = M('hotel')->where('pid=0')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
        $category = $tree->get_tree(0,$str,$hotelid);
        $this->assign('pHotel',$category);
        $this->display();
    }

    //上传类
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
                    $upload->exts=array('jpg','png','jpeg');
                    $upload->maxSize=2097152;// 设置附件上传大小2M
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
                    $callback['size'] = round($info['size']/1024,3);
                    $callback['storename']=trim($info['savepath'].$info['savename'],'.');
                }
            }
        }else{
            $callback['status'] = 0;
            $callback['info']='缺少文件';
        }
        echo json_encode($callback);
    }

    //栏目图标上传类
    public function upload_icon(){
        $callback = array();
        if (!empty($_FILES[$_REQUEST["name"]]["name"])) {
            if(empty($_REQUEST['hid'])){
                $callback['status'] = 0;
                $callback['info'] = "请选择酒店";
            }else{
                $upload = new \Think\Upload();
                $upload->maxSize=104857600;
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
                    $callback['size'] = round($info['size']/1024,3);
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
        $catlist = M("hotel_chg_category")->where($map)->order("sort asc")->field('id,name')->select();
        echo json_encode($catlist);
    }

    //保存
    public function update(){
        $model=M("hotel_chg_category");
        $data['id'] = I('post.id','','intval');
        $data['hid'] = $hmap['hid'] = I('post.hid','','strip_tags');
        $data['name'] = isset($_POST['name'])?$_POST['name']:false;
        $data['pid'] = I('post.pid','','intval');
        $data['langcodeid'] = I('post.langcodeid','','intval');
        $data['modeldefineid'] = I('post.modeldefineid','','intval');
        $data['intro'] = I('post.intro','','strip_tags');
        $data['weburl'] = I('post.weburl','','strip_tags');
        $data['filepath'] = I('post.icon','','strip_tags');
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
        $volumeHid_arr[] = $data['hid']; //修改容量表的hid数组
                                         
        if($data['id']){//修改
            
            if ($data['pid']==$data['id']) {
                $this->error('警告！栏目上级不能选本身！',U('index'));
            }
            $vo = $model->getById($data['id']);

            //查询容量
            $changesize = $data['size'] - $vo['size'];

            if($changesize!=0){ //修改后 容量变小或不变  不执行容量判断

	            $volumeResult = $this->checkVolume($data['hid'],$changesize);//检查自身容量是否超标
	            if($volumeResult === false){
	                @unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
	                $this->error("超过申请容量值，无法修改资源");
	            }

	            //查找所关联酒店是否会超标
	            //查找现一级栏目的资源总和
            	if($data['pid']>0){//判断是一级栏目修改还是二级栏目修改
            		$chg_pid = $data['pid'];
            	}else{
            		$chg_pid = $data['id'];
            	}
            	$chg_all_size = $model->where('id='.$chg_pid)->field('all_size')->find();
            	//新增栏目和已有容量总和
            	$newallsize = $changesize + $chg_all_size['all_size'];

            	//查询所有关联的子酒店已用容量
            	$returnVolume = $this->checkchgVolumn($chg_pid,$data['hid']);
            	$overproof_hid_arr = array();//超标hid数组                
            	foreach ($returnVolume as $key => $value) {
            		if($newallsize>$value['lastvolume']){
            			$overproof_hid_arr[] = $value['hid']; 
            		}
            		$volumeHid_arr[] = $value['hid'];
            	}

            	if(!empty($overproof_hid_arr)){
            		$overproof_hid = implode(',',$overproof_hid_arr);
            		@unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
            		$this->error('新增资源后，该栏目的总容量加上关联子酒店的总容量已有值，将超过子酒店设定的容量值，上传不成功！子酒店酒店编号为：'.$overproof_hid);
            	}

            }

            $result = $model->data($data)->where('id='.$data['id'])->save();
            addlog('修改栏目信息，栏目ID：'.$data['id']);
        
        }else{//新增
            
            //查询容量
            $volumeResult = $this->checkVolume($data['hid'],$data['size']);//检查自身容量是否超标
            if($volumeResult === false){
                @unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
                $this->error("超过申请容量值，无法新增资源");
            }

            //查找所关联酒店是否会超标 (pid>0 非一级栏目 才需要进行判断)
            if($data['pid']>0){
            	//查找现一级栏目的资源总和
            	$chg_all_size = $model->where('id='.$data['pid'])->field('all_size')->find();
            	//新增栏目和已有容量总和
            	$newallsize = $data['size'] + $chg_all_size['all_size'];

            	//查询所有关联的子酒店已用容量
            	$returnVolume = $this->checkchgVolumn($data['pid'],$data['hid']);
            	$overproof_hid_arr = array();//超标hid数组
            	foreach ($returnVolume as $key => $value) {
            		if($newallsize>$value['lastvolume']){
            			$overproof_hid_arr[] = $value['hid']; 
            		}
            		$volumeHid_arr[] = $value['hid'];
            	}
				
            	if(!empty($overproof_hid_arr)){
            		$overproof_hid = implode(',',$overproof_hid_arr);
            		@unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
            		$this->error('新增资源后，该二级栏目所在的栏目的总容量加上关联子酒店的总容量已有值，将超过子酒店设定的容量值，上传不成功！子酒店酒店编号为：'.$overproof_hid);
            	}
            }
            
            $changesize = $data['size'];//改变值的大小
            
            $data['status'] = 0;
            $data['all_size'] = 0;
            $sort = $model->where('pid='.$data['pid'].' and langcodeid='.$data['langcodeid'].' and hid="'.$data['hid'].'"  ')->max('sort');
            $data['sort'] = $sort+1;
            $result = $model->data($data)->add();
            addlog('添加酒店通用栏目信息，栏目ID：'.$result);
        }

        //修改酒店通用栏目某一级栏目下所有资源的总和大小字段值
        if($data['pid']>0){
        	$rsize_id = $data['pid'];
        }else{
        	$rsize_id = $result;
        }

        $chgallsizeResult = $model->where('id='.$rsize_id)->setInc('all_size',$changesize); //修改栏目all_size字段
                   
        //更改容量表chg_size
        $volumeMap['hid'] = array('in',$volumeHid_arr);
        $volumeHid_len = count($volumeHid_arr);
        $volumeHid_str = "";
        for ($i=0; $i < $volumeHid_len; $i++) { 
            $volumeHid_str = $volumeHid_str."'".$volumeHid_arr[$i]."',";
        }
        $volumeHid_str = trim($volumeHid_str,",");
        $sql = "UPDATE `zxt_hotel_volume` SET `chg_size`=chg_size"."+".$changesize." where hid in(".$volumeHid_str.")";
        $volumeResult = D("hotel_volume")->execute($sql);

        if($result !== false  && $chgallsizeResult !== false && $volumeResult!==false){
            //写入json文件
            if($data['pid'] == 0){
                $this->updatejson_one($data['hid']);
            }else{
                $this->updatejson_two($data['hid']);
            }
            $model->commit();
            
            if($data['id']){
                if(!empty($vo)){
                    if($data['filepath'] != $vo['filepath']){
                        @unlink(FILE_UPLOAD_ROOTPATH.$vo['filepath']);
                    } 
                }
            }
            $this->success('恭喜，操作成功！',U('index'));
        }else{
            $model->rollback();
            $this->error('操作失败！',U('index'));
        }
    }

    //修改
    public function edit(){
        $model = M("hotel_chg_category");
        $ids = isset($_POST['ids'])?$_POST['ids']:false;
        if(count($ids)!=1){
            $this->error('参数错误，每次只能修改一条内容！');
        }
        $var = $model->getById($ids[0]);
        $this->assign('vo',$var);

        $voHotel=M('hotel')->getByHid($var['hid']);
        $this->assign('pHotel',$voHotel['hotelname']);

        $Langcode = D("Langcode");
        $list = $Langcode->where("status=1")->order("id asc")->select();
        $this->assign("langlist",$list);

        $Modeldefine = D("Modeldefine");
        $modeldefinelist = $Modeldefine->where("status=1")->order("codevalue asc")->select();
        $this->assign("modeldefinelist",$modeldefinelist);

        $currentMenuType=$Modeldefine->getById($var['modeldefineid']);
        $this->assign('currentMenuType', $currentMenuType);
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

    /**
     * [启用栏目]
     */
    public function unlock(){
        if (count($_POST['ids'])<1) {
            $this->error('系统提示：参数错误');
        }
        $map['id'] = array('in',$_POST['ids']);
        $result = D("hotel_chg_category")->where($map)->setField('status',1);
        if ($result === false) {
            $this->error('系统提示:启用失败');
        }else{
            $vo = D("hotel_chg_category")->where('id='.$_POST['ids']['0'])->field('hid')->find();
            $this->updatejson_one($vo['hid']);
            $this->updatejson_two($vo['hid']);
            $this->success('系统提示：启用成功');
        }
    }

    /**
     * [禁用栏目]
     */
    public function lock(){
        if (count($_POST['ids'])<1) {
            $this->error('系统提示：参数错误');
        }
        $map['id'] = array('in',$_POST['ids']);
        $result = D("hotel_chg_category")->where($map)->setField('status',0);
        if ($result === false) {
            $this->error('系统提示:禁用失败');
        }else{
            $vo = D("hotel_chg_category")->where('id='.$_POST['ids']['0'])->field('hid')->find();
            $this->updatejson_one($vo['hid']);
            $this->updatejson_two($vo['hid']);
            $this->success('系统提示：禁用成功');
        }
    }

    /**
     * [更新栏目排序]
     */
    public function sort() {
        $menuids = explode(",", $_REQUEST['menuid']);
        $sorts = explode(",",$_REQUEST['sort']);
        $count = count($menuids);
        $sql = "UPDATE zxt_hotel_chg_category SET sort = CASE id";
        for ($i=0; $i < $count; $i++) { 
            $sql .= sprintf(" WHEN %d THEN '%s'",$menuids[$i],$sorts[$i]);
        }
        $menuids_str = $_REQUEST['menuid'];
        $sql .= "END WHERE id IN($menuids_str)";
        D("hotel_chg_category")->startTrans();
        $result = D("hotel_chg_category")->execute($sql);

        if($result === false){
            D("hotel_chg_category")->rollback();
            $this->error('更新失败');
        }else{
            $vo = D("hotel_chg_category")->where('id='.$menuids['0'])->field('hid')->find();
            $this->updatejson_one($vo['hid']);
            $this->updatejson_two($vo['hid']);
            D("hotel_chg_category")->commit();
            $this->success('更新成功');
        }
    }

    //栏目内的资源
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

        $vo=D("hotel_chg_category")->getById($category_id);
        if ($vo['pid']==0) {
            $this->error('该栏目没有资源管理！',U('index').'?hid='.$vo['hid']);
        }
        $this->assign("current_category",$vo);

        $fo=M('modeldefine')->getById($vo['modeldefineid']);
        $map['cid']=$category_id;
        if($fo['codevalue']==501){
            $map['ctype'] = 'videochg';
            $list = D("HotelCarouselResource")->where($map)->order("sort asc")->select();
            $this->assign('ctype',"videochg");
            $prevcontroller = U("Chg/index");
            $this->assign('prevcontroller',$prevcontroller);
            $this->assign('incontroller','hotel_chg_category');
            $this->assign('gocontroller','Chg');
            $this->assign('list',$list);
            $this->display("Carousel/resource");
        }else{
            $list = M('hotel_chg_resource')->where($map)->order("sort asc")->select();
            $this -> assign("list",$list);
            $this->display();
        }
    }

    //资源添加
    public function resource_add(){
        $model = M("hotel_chg_category");

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
            // $this->error('该栏目不可添加资源！',U('resource').'?ids='.$category_id);
        }
        $this->assign("resource_type",$type);
        $this->display();
    }


    //资源修改
    public function resource_edit(){
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if(count($ids)!=1){
            $this->error('参数错误，每次请修改一条内容！');
        }
        $model = M('hotel_chg_category');
        $Resource = M("hotel_chg_resource");
        $fo=$Resource->getById($ids[0]);
        $filepath = $fo['filepath'];
        $qrcode = $fo['icon'];
        if(!empty($filepath)){
            $fo['size1'] = round(filesize(FILE_UPLOAD_ROOTPATH.$filepath)/1024);
        }else{
            $fo['size1'] = 0;
        }
        if(!empty($filepath)){
            $fo['size2'] = round(filesize(FILE_UPLOAD_ROOTPATH.$qrcode)/1024);
        }else{
            $fo['size2'] = 0;
        }
        
        $this->assign('vo',$fo);
        $this -> display();
    }
    

    //栏目资源保存
    public function resource_update(){
        $model=M('hotel_chg_resource');
        $data['id'] = I('post.id','','intval');
        $data['hid'] = $hmap['hid'] = I('post.hid','','strip_tags');
        $data['cid'] = I('post.cid','','intval');
        $data['title'] = isset($_POST['title'])?$_POST['title']:false;
        $data['intro'] = I('post.intro','','strip_tags');
        $data['sort'] = I('post.sort','','intval');
        $data['filepath'] = I('post.filepath','','strip_tags');
        $data['icon'] = I('post.icon','','strip_tags');
        $data['price'] = I('post.price','','strip_tags');
        $data['status'] = 0;
        $data['audit_status'] = 0;
        $size1 = I('post.size1','','intval');
        $size2 = I('post.size2','','intval');
        $size = $size1 + $size2;
        $data['size'] = round($size/1024,3);

        if(!$data['title']){
            $this->error('警告！资源标题必须填写！');
        }
        if (empty($data['filepath'])) {
            $this->error('请上传资源文件！');
        }
        if(empty($data['cid'])){
        	$this->error("资源所属栏目ID出错！");
        }

        //判断资源所属的一级栏目的id 赋值为pcid
        $chg_category_vo = D("hotel_chg_category")->where('id='.$data['cid'])->getField('pid');
        if($chg_category_vo>0){
        	$pcid = $chg_category_vo;
        }else{
        	$pcid = $data['cid'];
        }

        $model->startTrans();
        $vo = '';
        if($data['id']){//修改
            $vo = $model->getById($data['id']);

             //查询容量
            $changesize = $data['size'] - $vo['size'];
            if($changesize!=0){

	            $volumeResult = $this->checkVolume($data['hid'],$changesize);//检查自身容量是否超标
	            if($volumeResult === false){
	                if($vo['icon'] != $data['icon']){
	                    @unlink(FILE_UPLOAD_ROOTPATH.$data['icon']);
	                    
	                }
	                if($vo['filepath'] != $data['filepath']){
	                    @unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
	                    
	                }
	                $this->error("超过申请容量值，无法修改资源");
	            }
	            //查找所关联酒店是否会超标
	            //查找现栏目的资源总和
            	$chg_all_size = D("hotel_chg_category")->where('id='.$pcid)->field('hid,all_size')->find();
            	$volumeHid_arr[] = $chg_all_size['hid'];//容量表 hid数组
            	//新增栏目和已有容量总和
            	$newallsize = $changesize + $chg_all_size['all_size'];

            	//查询所有关联的子酒店已用容量
            	$returnVolume = $this->checkchgVolumn($pcid,$data['hid']);
            	$overproof_hid_arr = array();//超标hid数组
            	foreach ($returnVolume as $key => $value) {
            		if($newallsize>$value['lastvolume']){
            			$overproof_hid_arr[] = $value['hid']; 
            		}
            		$volumeHid_arr[] = $value['hid'];
            	}
            	if(!empty($overproof_hid_arr)){
            		$overproof_hid = implode(',',$overproof_hid_arr);
            		@unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
            		$this->error('新增资源后，该栏目的总容量加上关联子酒店的总容量已有值，将超过子酒店设定的容量值，上传不成功！子酒店酒店编号为：'.$overproof_hid);
            	}
            }
            $result = $model->data($data)->where('id='.$data['id'])->save();
            addlog('修改栏目资源，ID：'.$data['id']);

        }else{//新增
            
            //查询容量
            $volumeResult = $this->checkVolume($data['hid'],$data['size']);//检查自身容量是否超标
            if($volumeResult === false){
                @unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
                @unlink(FILE_UPLOAD_ROOTPATH.$data['icon']);
                $this->error("超过申请容量值，无法新增资源");
            }

            //查找所关联酒店是否会超标
            //查找现栏目的资源总和
            $chg_all_size = D("hotel_chg_category")->where('id='.$pcid)->field('hid,all_size')->find();
            $volumeHid_arr[] = $chg_all_size['hid'];//容量表 hid数组
            //新增栏目和已有容量总和
            $newallsize = $data['size'] + $chg_all_size['all_size'];

            //查询所有关联的子酒店已用容量
            $returnVolume = $this->checkchgVolumn($pcid,$data['hid']);
        	$overproof_hid_arr = array();//超标hid数组
        	foreach ($returnVolume as $key => $value) {
        		if($newallsize>$value['lastvolume']){
        			$overproof_hid_arr[] = $value['hid']; 
        		}
        		$volumeHid_arr[] = $value['hid'];
        	}
			
        	if(!empty($overproof_hid_arr)){
        		$overproof_hid = implode(',',$overproof_hid_arr);
        		@unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
        		$this->error('新增资源后，该资源所在的栏目的总容量加上关联子酒店的总容量已有值，将超过子酒店设定的容量值，上传不成功！子酒店酒店编号为：'.$overproof_hid);
        	}

        	$changesize = $data['size'];//改变值的大小

            $data['file_type'] = I('post.file_type','','intval');
            $data['upload_time'] = date("Y-m-d H:i:s");
            $result = $model->data($data)->add();
            addlog('添加栏目资源，ID：'.$result);

            //通过pcid查找表hotel_chglist中的hid列表
            $searchHidList = D("hotel_chglist")->where('chg_cid="'.$pcid.'"')->field('hid')->select();
        }
        
        //改变容量不为0进行更新容量
        $chgallsizeResult = true;
        $volumeResult = true;
        if($changesize){
            $chgallsizeResult = D("hotel_chg_category")->where('id='.$pcid)->setInc('all_size',$changesize);//修改所在的一级栏目统计总容量字段
            $volumeHid_len = count($volumeHid_arr);
            $volumeHid_str = "";
            for ($i=0; $i < $volumeHid_len; $i++) { 
                $volumeHid_str = $volumeHid_str."'".$volumeHid_arr[$i]."',";
            }

            $volumeHid_str = trim($volumeHid_str,",");
            $sql = "UPDATE `zxt_hotel_volume` SET `chg_size`=chg_size"."+".$changesize." where hid in(".$volumeHid_str.")";
            $volumeResult = D("hotel_volume")->execute($sql); //修改容量表酒店的chg_size字段
        }

        if($result !== false && $chgallsizeResult !== false && $volumeResult!==false){
            //写入json文件
            $this->updatejson_chgresource($data['hid']);
            $model->commit();

            if(!empty($vo)){
                if($data['filepath'] != $vo['filepath']){
                    @unlink(FILE_UPLOAD_ROOTPATH.$vo['filepath']);
                }
                if ($data['icon'] != $vo['icon']) {
                    @unlink(FILE_UPLOAD_ROOTPATH.$vo['icon']);
                }
            }
            $this->success('恭喜，操作成功！',U('resource').'?ids='.$_REQUEST['cid']);
        }else{
            $model->rollback();
            $this->error('操作失败',U('resource').'?ids='.$_REQUEST['cid']);
        }
    }

    /**
     * [栏目资源启用]
     */
    public function resource_unlock(){
        $model = D("hotel_chg_resource");
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if($ids){
            $map['id'] = array('in',$ids);
            $result = $model->where($map)->setField("status",1);
            if($result !== false){
                $vo = D("hotel_chg_resource")->where('id='.$ids['0'])->field('hid')->find();
                $this->updatejson_chgresource($vo['hid']);
                $this->success('恭喜，启用成功！',U('resource').'?ids='.$_REQUEST['cid']);
            }else{
                $this->error('启用失败，参数错误！',U('resource').'?ids='.$_REQUEST['cid']);
            }
        }else{
            $this->error('参数错误！',U('resource').'?ids='.$_REQUEST['cid']);
        }
    }

    /**
     * [栏目资源禁用]
     */
    public function resource_lock(){
        $model = D("hotel_chg_resource");
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if($ids){
            $map['id'] = array('in',$ids);
            $result = $model->where($map)->setField("status",0);
            if($result !== false){
                $vo = D("hotel_chg_resource")->where('id='.$ids['0'])->field('hid')->find();
                $this->updatejson_chgresource($vo['hid']);
                $this->success('恭喜，禁用成功！',U('resource').'?ids='.$_REQUEST['cid']);
            }else{
                $this->error('禁用失败，参数错误！',U('resource').'?ids='.$_REQUEST['cid']);
            }
        }else{
            $this->error('参数错误！',U('resource').'?ids='.$_REQUEST['cid']);
        }
    }

    //栏目资源排序更新
    public function resource_sort() {
        $menuids = explode(",", $_REQUEST['menuid']);
        $sorts = explode(",",$_REQUEST['sort']);
        $count = count($menuids);
        $sql = "UPDATE zxt_hotel_chg_resource SET sort = CASE id";
        for ($i=0; $i < $count ; $i++) { 
            $sql .= sprintf(" WHEN %d THEN '%s'",$menuids[$i],$sorts[$i]);
        }
        $menuids_str = $_REQUEST['menuid'];
        $sql .= "END WHERE id IN($menuids_str)";
        $result = D("hotel_chg_resource")->execute($sql);
        if($result === false){
            $this->success('系统提示，操作失败！',U('resource').'?ids='.$_REQUEST['cid']);
        }else{
            $vo = D("hotel_chg_resource")->where('id='.$menuids['0'])->field('hid')->find();
            $this->updatejson_chgresource($vo['hid']);
            $this->success('恭喜，操作成功！',U('resource').'?ids='.$_REQUEST['cid']);
        }
    }

    //栏目删除  （只做单个栏目删除  不做批量删除）
    public function delete(){

    	$ids = I('post.ids','','strip_tags');
    	if(count($ids)!=1){
    		$this->error('系统提示：集团栏目删除操作只能针对一个栏目进行删除');
    	}

    	$model = D("hotel_chg_category");
    	$cMap['zxt_hotel_chg_category.id'] = $ids['0'];
    	$field = "zxt_hotel_chg_category.*,zxt_hotel_chg_resource.id as rid";
    	$vo = $model->where($cMap)->join('zxt_hotel_chg_resource ON zxt_hotel_chg_category.id = zxt_hotel_chg_resource.cid',"left")->field($field)->find();
    	if($vo['rid']){ //判断栏目下是否含有资源 rid是资源id
    		$this->error('该栏目下含有资源，若需删除栏目，请先删除本栏目下的资源');
    	}else{

    		$delAllsizeResult = true; //二级栏目情况下  减少一级栏目容量结果
    		$delChaglistResult = true; //一级栏目情况下 删除绑定表结果
    		$decVolumeResult = true; //减少容量表中chg_size字段大小
    		$model->startTrans();
    		if($vo['pid']>0){ //二级栏目
    			$delMap['id'] = $chgCategoryMap['id'] = $vo['pid'];
    			$delAllsizeResult = $model->where($delMap)->setDec('all_size',$vo['size']); //减少一级栏目的总容量
    			
    			$decVolumeSize = $vo['size']; //需要减少容量表容量大小
    			
    			//查找二级所对应一级栏目
    			$chgCategoryVo = $model->where($chgCategoryMap)->find();
    			$chglistMap['phid'] = $chgCategoryVo['hid'];
	    		$chglistMap['chg_cid'] = $chgCategoryVo['id'];
	    		//查询绑定表中绑定的子酒店
	    		$chglist = D("hotel_chglist")->where($chglistMap)->select();  

    		}else{ //一级栏目 需要删除绑定表
	    		if(D("hotel_chg_category")->where('pid="'.$vo['id'].'"')->count()>0){
                    $this->error('系统提示：该栏目下含有二级栏目，请先删除对应的二级栏目');
                }
                $chglistMap['phid'] = $vo['hid'];
	    		$chglistMap['chg_cid'] = $vo['id'];
	    		//查询绑定表中绑定的子酒店
	    		$chglist = D("hotel_chglist")->where($chglistMap)->select();
	    		//删除绑定表 		
	    		$delChaglistResult = D("hotel_chglist")->where($chglistMap)->delete();

	    		$decVolumeSize = $vo['all_size']; //需要减少容量表容量大小
    		}


            //减少容量表中的chg_size大小
            $volumeHid_str = "'".$vo['hid']."'";
            if(!empty($chglist)){
                $volume_arr[] = $vo['hid'];
                foreach ($chglist as $key => $value) {
                    $volume_arr[] = $value['hid'];
                }

                $volumeHid_len = count($volume_arr);
                for ($i=0; $i < $volumeHid_len; $i++) { 
                    $volumeHid_str = $volumeHid_str."'".$volume_arr[$i]."',";
                }
                $volumeHid_str = trim($volumeHid_str,",");
            }
            $sql = "UPDATE `zxt_hotel_volume` SET `chg_size`=chg_size"."-".$decVolumeSize." where hid in(".$volumeHid_str.")";
            $decVolumeResult = D("hotel_volume")->execute($sql);
    		
    		$delResult = $model->where($cMap)->delete(); //删除栏目

    		if($delAllsizeResult!==false && $delResult!==false && $delChaglistResult!==false && $decVolumeResult!==false){
                //更新json文件
                if ($vo['pid']>0) {
                    $this->updatejson_two($vo['hid']);
                }else{
                    $this->updatejson_one($vo['hid']);
                }
    			$model->commit();

    			if(!empty($vo['filepath'])){
    				@unlink(FILE_UPLOAD_ROOTPATH.$vo['filepath']);
    			}
    			$this->success('删除成功');
    		}else{
    			$model->rollback();
    			$this->error('删除失败');
    		}
    	}
    }

    /**
     * [集团栏目资源删除]
     */
    public function resource_delete(){
    	$model = D("hotel_chg_resource");
    	$ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
    	if($ids){
    		if(count($ids)>1){
    			$rMap['zxt_hotel_chg_resource.id'] = array('in',$ids);
    		}elseif(count($ids)==1){
    			$rMap['zxt_hotel_chg_resource.id'] = $ids[0];
    		}else{
    			$this->error('参数错误',U('index'));
    		}
    	}else{
    		$this->error('参数错误',U('index'));
    	}
    	$rField = "id,hid,cid,filepath,icon,size";
    	$rList = $model->where($rMap)->field($rField)->select();
    	if(!empty($rList)){
    		$all_size = 0;
    		foreach ($rList as $key => $value) {
    			$all_size += $value['size'];
    			$delFilepath[] = $value['filepath'];
    			$delFilepath[] = $value['icon'];
    			$hid_arr[] = $value['hid'];
    			$cid_arr[] = $value['cid'];
    		}

    		$hid_unique = array_unique($hid_arr);
    		$cid_unique = array_unique($cid_arr);
    		if(count($hid_unique)>1 || count($cid_unique)>1){
    			$this->error('系统错误：删除的资源非同一栏目下，请联系系统管理员处理',U('index'));
    		}

    		//查询栏目是否为一级栏目
    		$chgCategoryMap['id'] = $cid_unique['0'];
    		$nowCategory = D("hotel_chg_category")->where($chgCategoryMap)->field('id,pid')->find();
    		if($nowCategory['pid']>0){
    			$cPidMap['id'] = $nowCategory['pid'];
    			$category_first = D("hotel_chg_category")->where($cPidMap)->find();
    			$decCsizeMap['id'] = $category_first['id']; //减少栏目容量查询条件
    			$releateHCMap['chg_cid'] = $category_first['id']; //酒店栏目关联表查询条件
    		}else{
    			$decCsizeMap['id'] = $nowCategory['id'];
    			$releateHCMap['chg_cid'] = $nowCategory['id'];
    		}
   
    		$model->startTrans();
    		//减少栏目容量
    		$decCsizeResult = D("hotel_chg_category")->where($decCsizeMap)->setDec('all_size',$all_size);

    		//减少容量表中酒店容量
    		$chglist = D("hotel_chglist")->where($releateHCMap)->field('hid')->select();
    		$volume_hid_arr[] = $hid_unique['0']; //容量表hid数组  
    		if(!empty($chglist)){
    			foreach ($chglist as $key => $value) {
    				$volume_hid_arr[] = $value['hid'];
    			}
    		}

            $volume_hid_unique = array_unique($volume_hid_arr); //用于查询容量表需要修改容量的查询条件
            $volumeHid_len = count($volume_hid_unique);
            $volumeHid_str = "";
            for ($i=0; $i < $volumeHid_len; $i++) { 
                $volumeHid_str = $volumeHid_str."'".$volume_hid_unique[$i]."',";
            }
            $volumeHid_str = trim($volumeHid_str,",");
            $sql = "UPDATE `zxt_hotel_volume` SET `chg_size`=chg_size"."-".$all_size." where hid in(".$volumeHid_str.")";
            $decVolumeResult = D("hotel_volume")->execute($sql);

    		//删除资源
    		$delResourceResult = D("hotel_chg_resource")->where($rMap)->delete();

    		//写入日志
    		addlog("删除集团栏目资源");
    		if($decCsizeResult!==false && $decVolumeResult!==false && $delResourceResult!==false){
                //更新json文件
                $this->updatejson_chgresource($hid_unique[0]);
    			$model->commit();
    			//删除服务器资源
    			foreach ($delFilepath as $key => $value) {
    				@unlink(FILE_UPLOAD_ROOTPATH.$value);
    			}
    			$this->success('删除成功',U('resource').'?ids='.$cid_unique['0']);
    		}else{
    			$model->rollback();
    			$this->error('删除失败',U('resource').'?ids='.$cid_unique['0']);
    		}

    	}else{
    		$this->error('系统错误，请联系系统管理员',U('index'));
    	}
    }

    //查找关联子酒店的剩余容量
    public function checkchgVolumn($id=false,$hid=false){
		//$id = 1;
		//$hid ="4008";	
		if($id==false || $hid==false){
			$this->error("检测容量值出错,请重试或联系系统管理员");
		}

    	$chgvolumn = D("hotel_chg_category")->where('id='.$id)->field('all_size')->find();
    	$chglistMap['zxt_hotel_chglist.phid'] = $hid;
    	$chglistMap['zxt_hotel_chglist.chg_cid'] = $id;
    	$field = "zxt_hotel_volume.*,zxt_hotel.space,zxt_hotel.hid";

    	$volumnResult = D("hotel_chglist")->field($field)->where($chglistMap)->join("zxt_hotel ON zxt_hotel_chglist.hid=zxt_hotel.hid","left")->join("zxt_hotel_volume ON zxt_hotel_chglist.hid=zxt_hotel_volume.hid","left")->select();
    	$returnarr = array();
    	foreach ($volumnResult as $key => $value) {
    		$returnarr[$key]['hid'] = $value['hid'];
    		$returnarr[$key]['lastvolume'] = $value['space'] - $value['content_size'] - $value['topic_size'] - $value['ad_size'] - $value['devicebg_size'];
    	}
        
    	return $returnarr;
    }

    /**
     * [更新集团一级栏目json]
     * @param  [string] $hid [酒店编号]
     */
    private function updatejson_one($hid){
        $jsonmap['zxt_hotel_chg_category.hid'] = $hid;
        $jsonmap['zxt_hotel_chg_category.pid'] = 0;
        $jsonmap['zxt_hotel_chg_category.status'] = 1;
        $field = "zxt_hotel_chg_category.id,zxt_hotel_chg_category.hid,zxt_hotel_chg_category.name,zxt_hotel_chg_category.modeldefineid,zxt_hotel_chg_category.sort,zxt_hotel_chg_category.filepath,zxt_hotel_chg_category.intro,zxt_modeldefine.codevalue,zxt_modeldefine.packagename,zxt_modeldefine.classname";
        $list = D("hotel_chg_category")->field($field)->where($jsonmap)->join('zxt_modeldefine on zxt_hotel_chg_category.modeldefineid  = zxt_modeldefine.id')->order('zxt_hotel_chg_category.sort')->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                if (in_array($value['codevalue'],array('100','101','102','103'))) {
                    $list[$key]['nexttype'] = 'chgcategory_second';
                }elseif ($value['codevalue'] == '501') {
                    $list[$key]['nexttype'] = 'videochg';
                }else{
                    $list[$key]['nexttype'] = 'app';
                }
            }
            $jsondata = json_encode($list);
        }else{
            $jsondata = '';
        }
        if(!is_dir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid)){
            mkdir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid);
        }
        $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid.'/chgcategory_first.json';
        file_put_contents($filename, $jsondata);
    }

    /**
     * [更新集团二级栏目json]
     * @param  [string] $hid [酒店编号]
     */
    private function updatejson_two($hid){
        $jsonmap['zxt_hotel_chg_category.hid'] = $hid;
        $jsonmap['zxt_hotel_chg_category.pid'] = array('neq',0);
        $jsonmap['zxt_hotel_chg_category.status'] = 1;
        $field = "zxt_hotel_chg_category.id,zxt_hotel_chg_category.pid,zxt_hotel_chg_category.hid,zxt_hotel_chg_category.name,zxt_hotel_chg_category.modeldefineid,zxt_hotel_chg_category.sort,zxt_hotel_chg_category.filepath,zxt_hotel_chg_category.intro,zxt_modeldefine.codevalue,zxt_modeldefine.packagename,zxt_modeldefine.classname";
        $list = D("hotel_chg_category")->field($field)->where($jsonmap)->join('zxt_modeldefine on zxt_hotel_chg_category.modeldefineid  = zxt_modeldefine.id')->order('zxt_hotel_chg_category.sort')->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                if (in_array($value['codevalue'],array('100','101','102','103'))) {
                    $value['nexttype'] = 'chgresource';
                }elseif ($value['codevalue'] == '501') {
                    $value['nexttype'] = 'videochg';
                }else{
                    $value['nexttype'] = 'app';
                }
                $plist[$value['pid']][] = $value;
            }
            $jsondata = json_encode($plist);
        }else{
            $jsondata = '';
        }
        if(!is_dir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid)){
            mkdir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid);
        }
        $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid.'/chgcategory_second.json';
        file_put_contents($filename, $jsondata);
    }

    /**
     * [更新集团通用栏目资源]
     * @param  [string] $hid [酒店编号]
     */
    private function updatejson_chgresource($hid){
        $map['hid'] = $hid;
        $map['status'] = 1;
        $map['audit_status'] = 4;
        $field = "id,hid,cid,title,intro,sort,filepath,file_type,icon";
        $list = D("hotel_chg_resource")->field($field)->where($map)->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $plist[$value['cid']][] = $value;
            }
            $jsondata = json_encode($plist);
        }else{
            $jsondata = '';
        }
        if(!is_dir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid)){
            mkdir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid);
        }
        $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid.'/chgresource.json';
        file_put_contents($filename, $jsondata);
    }
}