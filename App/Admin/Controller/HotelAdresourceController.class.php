<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 弹窗广告资源控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
class HotelAdresourceController extends ComController {

	public function _map(){

        $map = array();
		if (!empty($_GET['title'])) {
            $map['title'] = array("LIKE","%{$_GET['title']}%");
        }
        return $map;
    }

	public function index(){

		$map = $this->_map();
        $list = $this->_list(D('hotel_adresource'),$map,10,'update_time desc');
        $this->assign('list',$list);
        $this->display();
	}

	public function add(){
		$this->display();
	}

	public function edit(){
		$ids = I('post.ids','','strip_tags');
		if(count($ids)!= 1 && !is_numeric($ids[0])){
			$this->error('系统提示：参数错误');
		}

		$vo = D("hotel_adresource")->where('id="'.$ids[0].'"')->find();
		$this->assign('vo',$vo);
		$this->display();
	}

	public function upload(){
        $callback = array();
        if (!empty($_FILES[$_REQUEST["name"]]["name"])) {
            $upload = new \Think\Upload();
            if ($_REQUEST['filetype']==1) {
                $upload->exts=array('mp4');
                $upload->maxSize=209715200;// 设置附件上传大小200M
            }else if ($_REQUEST['filetype']==2){
                $upload->maxSize=2097152;// 设置附件上传大小2M
                $upload->exts=array('jpg','png','jpeg','gif');
            }else{
                $callback['status'] = 0;
                $callback['info']='未知错误！';
                echo json_encode($callback);
                exit;
            }
            $upload->rootPath='./Public/';
            $upload->savePath='./upload/adresource/';
            $info=$upload->uploadOne($_FILES[$_REQUEST["name"]]);
            if(!$info) {
                $callback['status'] = 0;
                $callback['info'] = $upload->getError();
            }else{
                $callback['status'] = 1;
                $callback['info']="上传成功！";
                $callback['type'] = $_REQUEST['filetype'];
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
    	$data['title'] = I('post.title','','strip_tags');
    	$data['filepath'] = I('post.filepath','','strip_tags');
        if(empty($data['title'])){
        	$this->error('系统提示：资源标题必须填写');
        }
        if(empty($data['filepath'])){
        	$this->error('系统提示：资源必须上传');
        }
    	$size = I('post.size','','intval');
        $data['size'] = round($size/1024,3);
        $data['filepath_type'] = $this->getSourceType($data['filepath']);
        $id = I('post.id','','intval');
    	$data['status'] = 0;
    	$data['audit_status'] = 0;
    	$model = D("hotel_adresource");
    	$model->startTrans();
        if(!empty($id)){
        	$vo = $model->where('id="'.$id.'"')->field('filepath')->find();
        	$data['update_time'] = date("Y-m-d H:i:s");
        	$result = $model->where('id="'.$id.'"')->data($data)->save();
        }else{
        	$data['create_time'] = $data['update_time'] = date("Y-m-d H:i:s");
        	$result = $model->data($data)->add();
        }

        if($result){
        	$model->commit();
        	if($data['filepath'] != $vo['filepath']){
        		@unlink(FILE_UPLOAD_ROOTPATH.$vo['filepath']);
        	}
        	$this->success('操作成功',U('index'));
        }else{
        	$model->rollback();
        	unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
        	$this->error('操作失败',U('index'));
        }
    }

    public function delete(){
    	$ids = I('post.ids','','strip_tags');
    	if(empty($ids)){
    		$this->error('系统提示：参数错误');
    	}
    	$model = D("hotel_adresource");
    	$model->startTrans();
    	
        if(count($ids)!=1){
            $this->error('系统提示：每次只能删除一条记录');
        }
        $map['id'] = $adset_adresource_map['adresource_id'] = $ids[0];
        $adsetList = D("hotel_adset_adresource")->where($adset_adresource_map)->field('id')->find();
        if(!empty($adsetList)){
            $this->error('系统提示：该资源正在被利用,不可删除');
        }
        $list = $model->where($map)->field('filepath')->find();
        $delResourceResult = $model->where($map)->delete();
    	if($delResourceResult!==false){
    		$model->commit();
			@unlink(FILE_UPLOAD_ROOTPATH.$list['filepath']);
    		$this->success('删除成功');
    	}else{
    		$model->rollback();
    		$this->error("系统提示：删除失败");
    	}
    }

    public function getSourceType($filepath){

    	$arr = explode(".", $filepath);
    	$typeName = strtolower(end($arr));
    	switch ($typeName) {
    		case 'mp4':
    			return 1;
    			break;
    		case 'jpg':
    			return 2;
    			break;
    		case 'png':
    			return 2;
    			break;
    		case 'jpeg':
    			return 2;
    			break;
            case 'gif':
                return 2;
                break;	
    		default:
    			return 0;
    			break;
    	}
    }
}