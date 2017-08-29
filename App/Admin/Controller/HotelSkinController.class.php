<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 酒店皮肤包控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class HotelSkinController extends ComController {
    //查询
    public function _map(){
        $map = array();
        if (!empty($_GET['name'])) {
            $map['name'] = array("LIKE","%{$_GET['name']}%");
            $this->assign('name', $_GET['name']);
        }
        if (!empty($_GET['version'])) {
            $map['version'] = array("LIKE","%{$_GET['version']}%");
            $this->assign('version', $_GET['version']);
        }
        return $map;
    }
    //保存
    public function update(){
        $model=M('hotel_skin');
        $data['id'] = I('post.id','','intval');
        $data['name'] = isset($_POST['name'])?$_POST['name']:false;
        $data['version'] = I('post.version','','strip_tags');
        $data['filename'] = I('post.filename','','strip_tags');
        if(!$data['name'] or !$data['version']){
            $this->error('警告！皮肤包名称和版本号为必填项目。');
        }
        if (empty($data['filename'])) {
            $this->error('请上传皮肤包！');
        }
        $filepath=FILE_UPLOAD_ROOTPATH.$data['filename'];
        $data['md5_file'] = md5_file($filepath);
        if($data['id']){
            //若皮肤文件重新上传则删除旧文件
            $vo = $model->getById($data['id']);
            if ($data['filename'] != $vo['filename']) {
                @unlink(FILE_UPLOAD_ROOTPATH.$vo['filename']);
            }
            $model->data($data)->where('id='.$data['id'])->save();
            addlog('编辑皮肤包，ID：'.$data['id']);
        }else{
            $data['status'] = 0;
            $aid = $model->data($data)->add();
            addlog('新增皮肤包，ID：'.$aid);
        }
        $this->success('恭喜，操作成功！',U('index'));
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
        if($model->where($map)->delete()){
            foreach ($list as $key => $value) {
                //删除对应文件
                @unlink(FILE_UPLOAD_ROOTPATH.$value['filename']);
            }
            if(is_array($ids)){
                $ids = implode(',',$ids);
            }
            addlog('删除'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
            $this->success('恭喜，删除成功！');
        }else{
            $this->error('删除失败，参数错误！');
        }
    }
    public function upload(){
        $callback = array();
        if (!empty($_FILES[$_REQUEST["name"]]["name"])) {
            $upload = new \Think\Upload(); //实例化上传类
            $upload->maxSize=524288000;// 设置附件上传大小
            //$upload->exts=array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->exts=array('zip');// 设置附件上传类型
            $upload->rootPath='./Public/'; //保存根路径
            $upload->savePath='./upload/skin/'; // 设置附件上传目录
            //单文件上传
            $info=$upload->uploadOne($_FILES[$_REQUEST["name"]]);
            if(!$info) {
                $callback['status'] = 0;
                $callback['info'] = $upload->getError();
            }else{
                $callback['status'] = 1;
                $callback['info']="上传成功！";
                //$callback['savename']=$info['savename'];//上传后的文件名
                $callback['storename']=trim($info['savepath'].$info['savename'],'.');//存到数据库的
            }
        }else{
            $callback['status'] = 0;
            $callback['info']='缺少文件';
        }
        echo json_encode($callback);
    }
}