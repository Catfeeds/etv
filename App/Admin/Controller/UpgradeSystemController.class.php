<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 系统软件升级控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class UpgradeSystemController extends ComController {
    //查询
    public function _map(){
        $map = array();
        if (!empty($_GET['utc'])) {
            $map['utc'] = array("LIKE","%{$_GET['utc']}%");
            $this->assign('utc', $_GET['utc']);
        }
        if (!empty($_GET['version'])) {
            $map['version'] = array("LIKE","%{$_GET['version']}%");
            $this->assign('version', $_GET['version']);
        }
        return $map;
    }
    public function _before_add(){
        $this->hotel_device_list();
    }
    public function edit(){
        $model = D(CONTROLLER_NAME);
        $Hotel = D("Hotel");
        $Device = D("Device");
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if(count($ids)>1){
            $this->error('参数错误，每次只能修改一条内容！');
        }
        $vo = $model->getById($ids[0]);
        if(!$vo){
            $this->error('参数错误！');
        }
        $this->assign('vo',$vo);

        $macArr=array();
        $room = '';
        if ($vo['maclist']=="all") {
            $volist=$Device->field("room,mac")->select();
            if (!empty($volist)) {
                foreach ($volist as $kk => $vv) {
                    $macArr[$kk]=$vv['mac'];
                }
            }
            $room = '全部房间';
        }else{
            $macArr=explode(',', $vo['maclist']);
            foreach ($macArr as $key => $value) {
                $map['mac'] = array('eq',$value);
                $voDevice=$Device->field('room')->where($map)->find();
                $room=$room.','.$voDevice['room'];
            }
        }
        $room=trim($room, ',');
        $this->assign("roomlist",$room);
        $this->hotel_device_list($macArr);
        $this->display();
    }
    //保存
    public function update(){
        $model = M(CONTROLLER_NAME);
        $data['id'] = I('post.id','','intval');
        $data['name'] = isset($_POST['name'])?$_POST['name']:false;
        $data['filename'] = I('post.filename','','strip_tags');
        $data['size'] = I('post.size','0','intval');
        if (!$data['name']) {
            $this->error('获取文件名称出错，请检查上传文件是否正确');
            die();
        }
        if (empty($data['filename'])) {
            $this->error('请上传升级包！');
            die();
        }
        $filepath=FILE_UPLOAD_ROOTPATH.$data['filename'];
        $data['md5_file'] = md5_file($filepath);
        $data['version'] = I('post.version','','strip_tags');
        if (empty($data['version'])) {
            $this->error('获取版本号错误，请检查上传文件是否正确');
            die();
        }
        $data['utc'] = I('post.utc','','strip_tags');
        if (empty($data['utc'])) {
            $this->error('获取UTC版本错误，请检查上传文件是否正确');
            die();
        }
        $data['description'] = I('post.description','','strip_tags');
        $data['status'] = I('post.status',0,'intval');
        $data['maclist'] = I('post.maclist','all','strip_tags');
        $data['audit_status'] = 0;
        if($data['id']){
            $vo = $model->getById($data['id']);
            if ($data['filename'] != $vo['filename']) {
                @unlink(FILE_UPLOAD_ROOTPATH.$vo['filename']);
                $data['upload_time']=time();
            }
            $model->data($data)->where('id='.$data['id'])->save();
            addlog('修改系统升级包信息，ID：'.$data['id']);
        }else{
            $data['upload_time']=time();
            $aid = $model->data($data)->add();
            addlog('添加系统升级包，ID：'.$aid);
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
            $upload = new \Think\Upload();
            $upload->maxSize=629145600;// 设置附件上传大小500M
            $upload->exts  = array('bin','zip','rar');
            $upload->rootPath='./Public/';
            $upload->savePath='./upload/software/';
            $info=$upload->uploadOne($_FILES[$_REQUEST["name"]]);
            if(!$info) {
                $callback['status'] = 0;
                $callback['info'] = $upload->getError();
            }else{
                $callback['status'] = 1;
                $callback['info']="上传成功！";
                $callback['storename']=trim($info['savepath'].$info['savename'],'.');
                $callback['name']=$info['name'];
                $callback['size']=$info['size'];
                //start从升级包提取版本号和UTC字段
                $filepath=FILE_UPLOAD_ROOTPATH.trim($info['savepath'].$info['savename'],'.');
                $zip = zip_open($filepath);
                if ($zip) {
                    do {
                        $entry = zip_read($zip);
                    }while ($entry && zip_entry_name($entry) != "system/build.prop");
                    zip_entry_open($zip, $entry, "r");
                    $entry_content = zip_entry_read($entry, zip_entry_filesize($entry));

                    $version_open_pos = strpos($entry_content, "ro.build.version.release=");
                    $version_close_pos = strpos($entry_content, "ro.build.version.incremental", $version_open_pos);
                    $version = substr($entry_content,$version_open_pos + strlen("ro.build.version.release="),$version_close_pos - ($version_open_pos + strlen("ro.build.version.release=")) );

                    $utc_open_pos = strpos($entry_content, "ro.build.date.utc=");
                    $utc_close_pos = strpos($entry_content, "ro.build.type", $utc_open_pos);
                    $utc = substr($entry_content,$utc_open_pos + strlen("ro.build.date.utc="),$utc_close_pos - ($utc_open_pos + strlen("ro.build.date.utc=")) );

                    zip_entry_close($entry);
                    zip_close($zip);
                }
                //end
                $callback['version'] = trim($version);//incremental做显示用
                $callback['utc'] = trim($utc);//utc做为版本比较
            }
        }else{
            $callback['status'] = 0;
            $callback['info']='缺少文件';
        }
        echo json_encode($callback);
    }
}