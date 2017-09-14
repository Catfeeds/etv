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
		$map['status'] = array('in',array(1,0));
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
            $upload->maxSize=104857600;
            $upload->exts=array('apk');
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
        $data['app_type'] = I('post.app_type','','intval');
        $data['app_version'] = I('post.app_version','','strip_tags');
        if(empty($data['app_version'])){
            $this->error("系统应用版本号不能为空");
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
        
        $model = D("appstore");

        $filepath=FILE_UPLOAD_ROOTPATH.$data['app_file'];
        $data['md5_file'] = md5_file($filepath);
        $data['app_identifier'] = I('post.app_identifier','','strip_tags');
        $data['app_introduce'] = I('post.app_introduce','','strip_tags');
        $data['app_uploadtime'] = date("Y-m-d H:i:s");
        $data['status'] = 0;
        $data['audit_status'] = 0;
        $app_size = I('post.app_size','','strip_tags');
    	$data['app_size'] = round($app_size/1024,3);
        $id = I('post.id','','intval');

        if(empty($id)){
            $map['app_package'] = $data['app_package'];
            $map['app_version'] = $data['app_version'];
            if($model->where($map)->count()){
                $this->error("包名+版本的组合有重复");
            }
            $appstoreResult = $model->data($data)->add();

        }else{
            $vo = $model->getById($id);
            if($vo['app_package'] != $data['app_package'] || $vo['app_version'] !== $data['app_version']){
                $map['app_package'] = $data['app_package'];
                $map['app_version'] = $data['app_version'];
                if($model->where($map)->count()){
                    $this->error("包名+版本的组合有重复");
                }
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
        $ids = I('post.ids');
        $map['id'] = array('in',$ids);
        $result = D("appstore")->where($map)->setField("status",1);
        if($result){
            addlog('启用'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
            $this->success('恭喜，启用成功！');
        }else{
            $this->error('启用失败，参数错误！');
        }
    }

    public function lock(){
        $ids = I('post.ids');
        $map['id'] = array('in',$ids);
        $result = D("appstore")->where($map)->setField("status",0);
        if($result){
            addlog('禁用'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
            $this->success('恭喜，禁用成功！');
        }else{
            $this->error('禁用失败，参数错误！');
        }
    }

    public function delete(){

        $ids = $_REQUEST['ids'];

        $map['id'] = array('in',$ids);
        $result = D("appstore")->where($map)->setField('status',2);
        if($result !== false){
            addlog("删除APPstore数据");
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }

    }

    public function maclist(){

        $ids = I('request.ids','','strip_tags');

        if(count($ids)!=1){
            $this->error('只能选择一条记录进行操作');
            die();
        }

        $appstoreModel = D("appstore");
        $deviceModel = D("device");
        $vo = $appstoreModel->getById($ids['0']);
        if($vo['maclist'] == 'all'){
            $updateResult = D("device_update_result")->where('uplist_id='.$ids[0].' and result=1')->field('mac')->select();
            $macarr = array();
            if(!empty($updateResult)){
                foreach ($updateResult as $key => $value) {
                    $macarr[] = $value['mac'];
                }
            }
            $this->hotel_device_list($macarr);
            $all = "全部房间";
        }else{
            $macarr = explode(',',$vo['maclist']);
            $devicemap['zxt_device.mac'] = array('in',$macarr);
            $devicefield = "zxt_hotel.hotelname,zxt_device.id,zxt_device.hid,zxt_device.room,zxt_device.mac,zxt_device_update_result.result as flag,zxt_device_update_result.uplist_id";
            $devicelist = $deviceModel->join('zxt_hotel ON zxt_device.hid = zxt_hotel.hid')->join('zxt_device_update_result ON zxt_device.mac=zxt_device_update_result.mac and zxt_device_update_result.uplist_id = '.$ids[0],'left')->field($devicefield)->where($devicemap)->select();
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