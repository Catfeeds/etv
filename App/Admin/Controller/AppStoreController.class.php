<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: appstore管理控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Apkparser;
class AppStoreController extends ComController {

	public function _map(){
		$map = array();
        if(!empty($_POST['app_name'])){
            $app_name = I('post.app_name','','strip_tags');
            $map['app_name'] = $app_name;
            $this->assign('app_name',$app_name);
        }
		return $map;
	}

	public function _before_add(){
        $this->hotel_device_list();
        $vo = D("appstore")->field('app_identifier')->order('id desc')->find();
        if(!empty($vo['app_identifier'])){
        	$app_identifier = substr($vo['app_identifier'],0,6).sprintf("%06d", intval(substr($vo['app_identifier'], 6))+1);//自增
        	$this->assign('app_identifier',$app_identifier);
        }else{
        	$app_identifier = 'ZXTAPP000001';
        	$this->assign('app_identifier',$app_identifier);
        }
    }

	public function index(){

		$model = D("appstore");
		$map = $this->_map();
		$list = $this->_list($model,$map,10,'app_uploadtime desc');
		$this->assign('list',$list);
		$this->display();
	}

	public function add(){
		$this->display();
	}

	public function upload(){
        $callback = array();
        if (!empty($_FILES[$_REQUEST["name"]]["name"])) {
            $upload = new \Think\Upload();
            $upload->maxSize=1048576000;
            $upload->exts=array('zip','rar','apk');
            $upload->rootPath='./Public/';
            $upload->savePath='./upload/apk/';
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

    public function upload_icon(){
        $callback = array();
        if (!empty($_FILES[$_REQUEST["name"]]["name"])) {
            $upload = new \Think\Upload();
            $upload->maxSize=104857600;
            $upload->exts=array('jpg','png','jpeg');
            $upload->rootPath='./Public/';
            $upload->savePath='./upload/apk/';
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

    public function update(){

    	$data['app_name'] = I('post.app_name','','strip_tags');
    	if(empty($data['app_name'])){
    		$this->error("名称不能为空");
    	}
    	$data['app_package'] = I('post.app_package','','strip_tags');
    	if(empty($data['app_package'])){
    		$this->error("apk包名不能为空");
    	}
    	$data['app_file'] = I('post.app_file','','strip_tags');
    	if(empty($data['app_file'])){
    		$this->error("apk文件不能为空");
    	}
    	$data['app_pic'] = I('post.app_pic','','strip_tags');
    	if(empty($data['app_pic'])){
    		$this->error("APK图标不能为空");
    	}
    	$data['maclist'] = I('post.maclist','','strip_tags');
    	if(empty($data['maclist'])){
    		$this->error("系统出错，请选择MAC列表");
    	}
    	$map['app_package'] = $data['app_package'];
    	$model = D("appstore");
    	
    	$filepath=FILE_UPLOAD_ROOTPATH.$data['app_file'];
        $data['md5_file'] = md5_file($filepath);
    	$data['app_identifier'] = I('post.app_identifier','','strip_tags');
    	$data['app_introduce'] = I('post.app_introduce','','strip_tags');
    	$data['app_uploadtime'] = date("Y-m-d H:i:s");
    	$data['status'] = 0;
    	$data['audit_status'] = 0;
    	$app_size = I('post.app_size','','strip_tags');
    	$id = I('post.id','','intval');
    	$vo = $model->getById($id);

    	if(empty($id)){
    		if($model->where($map)->count()){
	    		$this->error("包名已存在，不可重复");
	    	}
	    	$data['app_size'] = round($app_size/1024,3);
	    	$appstoreResult = $model->data($data)->add();

    	}else{
    		if($app_size){
    			$data['app_size'] = round($app_size/1024,3);
    		}
    		$lockresult = $this->dolock($id);
    		if($lockresult == false){
    			$this->error('系统错误，请联系管理员');
    			die();
    		}
    		$appstoreResult = $model->where('id='.$id)->data($data)->save();
    	}

    	if($appstoreResult){
    		if(empty($id)){
    			addlog("新增apk，ID为：".$appstoreResult);
	    		$this->success("新增成功",U('index'));

    		}else{ //修改  删除以前上传的文件
    		    	
    			if($data['app_file'] != $vo['app_file']){
    				@unlink(FILE_UPLOAD_ROOTPATH.$vo['app_file']);
    			}
    			if($data['app_pic'] != $vo['app_pic']){
    				@unlink(FILE_UPLOAD_ROOTPATH.$vo['app_pic']);
    			}

    			addlog("修改apk，ID为：".$appstoreResult);
	    		$this->success("修改成功",U('index'));
    		}
    	}else{
    		$this->error("操作失败",U('index'));
    	}
    }

    public function edit(){

    	$ids = $_REQUEST['ids'];
        if(count($ids)!=1){
            $this->error('只能修改一条记录');
            die();
        }
        
        $model = D("appstore");
        $Device = D("Device");
        $vo = $model->getById($ids['0']);
        if($vo['maclist'] == 'all'){
        	$macarr = array();
        	$roomstr = "全部房间(不用勾选)";
        }else{
        	$macarr = explode(',', $vo['maclist']);
	        $deviceMap['mac'] = array('in',$macarr);
	        $device = $Device->where($deviceMap)->field('room')->select();
	        $roomarr = array();
	        foreach ($device as $key => $value) {
	        	array_push($roomarr, $value['room']);
	        }
	        $roomstr = implode(',', $roomarr);
        }

        $this->hotel_device_list($macarr);

        $this->assign('roomstr',$roomstr);
        $this->assign('vo',$vo);
        $this->display();
    }

    public function unlock(){

    	$ids = $_REQUEST['ids'];
        if(count($ids)!=1){
            $this->error('只能启用一条记录');
            die();
        }

        $appstoreModel = D("appstore");
        $deviceapkModel = D("device_apk");
        $vo = $appstoreModel->getById($ids['0']);

        if($vo['audit_status'] != 4){
        	$this->error("未通过审核发布，不能启用");
        	die();
        }
        if($vo['status'] !=0){
        	$this->error("该状态已启用");
        	die();
        }

        //添加apk_id
        if($vo['maclist'] == 'all'){
        	$allmap['mac'] = 'all';
        	$device_apk_all_vo = $deviceapkModel->where($allmap)->find();//已存在公用
        	M()->startTrans();
    		$appresult = $appstoreModel->where('id='.$vo['id'])->setField('status',1);
        	if($device_apk_all_vo){
        		$apk_id_value = $device_apk_all_vo['apk_id'].",".$vo['app_identifier'];
        		$result = $deviceapkModel->where($allmap)->setField('apk_id',$apk_id_value);
        	}else{  //不存在公用
        		$alldata['mac'] = 'all';
        		$alldata['apk_id'] = $vo['app_identifier'];
        		$result = $deviceapkModel->data($alldata)->add();
        	}
        	if($result && $appresult){
        		addlog("启用apk，启用APK的编号为：".$vo['app_identifier']);
        		M()->commit();
        		$this->success('启用成功');
        	}else{
        		M()->rollback();
        		$this->error('启用失败');
        	}
        }else{
        	$macarr = explode(',', $vo['maclist']);
        	M()->startTrans();
        	$errornum = 0;
        	$appresult = $appstoreModel->where('id='.$vo['id'])->setField('status',1);
        	foreach ($macarr as $key => $value) {
        		$add_apk_id = ",".$vo['app_identifier'];
        		$sql = "INSERT INTO `zxt_device_apk` (mac,apk_id) VALUES('".$value."', '".$vo['app_identifier']."') ON DUPLICATE KEY UPDATE apk_id = concat(apk_id,'".$add_apk_id."')";
	        	$result = $deviceapkModel->execute($sql);
	        	if($result == 0 || $result === false){
	        		$errornum++;
	        	}
        	}
        	if($errornum>0 && $appresult === false){
        		M()->rollback();
        		$this->error('启用失败');
        	}else{
        		M()->commit();
        		addlog("启用apk，启用APK编号为：".$vo['app_identifier']);
        		$this->success('启用成功');
        	}
        }
    }

    //内部调用方法
    private function dolock($id){

    	$ids = array($id);
    	if(count($ids)!=1){
            return false;
        }

        $appstoreModel = D("appstore");
        $deviceapkModel = D("device_apk");
        $vo = $appstoreModel->getById($ids['0']);
        if($vo['status'] ==0){
        	return true;
        }
        $appstoreModel->startTrans();
    	$appresult = $appstoreModel->where('id='.$ids['0'])->setField('status',0);
    	$sql = "UPDATE `zxt_device_apk` SET apk_id = REPLACE(apk_id,'".$vo['app_identifier']."','')";

        if($vo['maclist'] == 'all'){
        	$result = $deviceapkModel->where('mac=all')->execute($sql);
        }else{
        	$macarr = explode(',', $vo['maclist']);
        	$apkmap['mac'] = array('in',$macarr);
        	$result = $deviceapkModel->where($apkmap)->execute($sql);
        }

        if($result !==false && $appresult !==false){
    		$appstoreModel ->commit();
    		addlog("禁用apk，apk编号为：".$vo['app_identifier']);
    		return true;
    	}else{
    		$appstoreModel->rollback();
    		return false;
    	}
    }

    public function lock(){

    	$ids = $_REQUEST['ids'];

        if(count($ids)!=1){
            $this->error('只能禁用一条记录');
            die();
        }

        $appstoreModel = D("appstore");
        $deviceapkModel = D("device_apk");
        $vo = $appstoreModel->getById($ids['0']);
        if($vo['status'] !=1){
        	$this->error("该状态已禁用");
        	die();
        }
        $appstoreModel->startTrans();
    	$appresult = $appstoreModel->where('id='.$ids['0'])->setField('status',0);
    	$sql = "UPDATE `zxt_device_apk` SET apk_id = REPLACE(apk_id,'".$vo['app_identifier']."','')";

        if($vo['maclist'] == 'all'){
        	$result = $deviceapkModel->where('mac=all')->execute($sql);
        }else{
        	$macarr = explode(',', $vo['maclist']);
        	$apkmap['mac'] = array('in',$macarr);
        	$result = $deviceapkModel->where($apkmap)->execute($sql);
        }

    	if($result !==false && $appresult !==false){
    		$appstoreModel ->commit();
    		addlog("禁用apk，apk编号为：".$vo['app_identifier']);
    		$this->success('禁用成功');
    	}else{
    		$appstoreModel->rollback();
    		$this->error("禁用失败");
    	}
    }

    public function delete(){

        $ids = $_REQUEST['ids'];

        if(count($ids)!=1){
            $this->error('只能禁用一条记录');
            die();
        }

        $appstoreModel = D("appstore");
        $deviceapkModel = D("device_apk");
        $vo = $appstoreModel->getById($ids['0']);
        $appstoreModel->startTrans();

        //删除device_apk记录
        $sql = "UPDATE `zxt_device_apk` SET apk_id = REPLACE(apk_id,'".$vo['app_identifier']."','')";
        if($vo['maclist'] == 'all'){
            $map = array();
        }else{
            $arr = explode(',', $vo['maclist']);
            $map['mac'] = array('in',$arr);
        }
        $deviceapkResult = $deviceapkModel->where($map)->execute($sql);
        //删除appstore表记录

        $appstoreResult = $appstoreModel->where('id='.$vo['id'])->delete();

        if($appstoreResult!==false && $deviceapkResult!==false){
            $appstoreModel->commit();
            addlog("删除apk，名称为：".$vo['app_name']);
            @unlink(FILE_UPLOAD_ROOTPATH.$vo['app_pic']);
            @unlink(FILE_UPLOAD_ROOTPATH.$vo['app_file']);
            $this->success('删除成功');
        }else{
            $appstoreModel->rollback();
            $this->error("删除失败");
        }

    }

    public function maclist(){

        $ids = $_REQUEST['ids'];

        if(count($ids)!=1){
            $this->error('只能禁用一条记录');
            die();
        }

        $appstoreModel = D("appstore");
        $deviceModel = D("device");
        $vo = $appstoreModel->getById($ids['0']);
        if($vo['maclist'] == 'all'){
            $this->hotel_device_list();
            $all = "全部房间";
        }else{
            $macarr = explode(',',$vo['maclist']);
            $devicemap['zxt_device.mac'] = array('in',$macarr);
            $devicefield = "zxt_hotel.hotelname,zxt_device.id,zxt_device.hid,zxt_device.room,zxt_device.mac";
            $devicelist = $deviceModel->where($devicemap)->join('zxt_hotel ON zxt_device.hid = zxt_hotel.hid')->field($devicefield)->select();
            $list = array();
            if(!empty($devicelist)){
                foreach ($devicelist as $key => $value) {
                    $list[$value['hotelname']]['id'] = $key;
                    $list[$value['hotelname']]['hotelname'] = $value['hotelname'];
                    $list[$value['hotelname']]['sub'][] = $value;
                }     
            }
            $this->assign('menuTree',$list);
        }

        $this->display();
    }

}