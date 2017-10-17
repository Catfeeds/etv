<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 专题分组控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class TopicGroupController extends ComController {
    //查询
    public function _map(){
        $map = array();
        if (!empty($_GET['title'])) {
            $map['title'] = array("LIKE","%{$_GET['title']}%");
        }
        return $map;
    }
    
    /**
     * [保存通用一级栏目]
     */
    public function update(){
        $model = M(CONTROLLER_NAME);
        $data['id'] = I('post.id','','intval');
        $data['title'] = I('post.title','','strip_tags');
        $data['name'] = I('post.name','','strip_tags');
        $data['en_name'] = I('post.en_name','','strip_tags');
        $data['intro'] = I('post.intro','','strip_tags');
        $data['icon'] = I('post.icon','','strip_tags');
        $size = I('post.size','','intval');
        $data['size'] = round($size/1024,3);
        $model->startTrans();
        $allresourceResult = true;
        $hotelvolumeResult = true;
        $searchHidList = array();
        if($data['id']){
            $vo = $model->getById($data['id']);
            $searchHidList = D("hotel_topic")->where('topic_id="'.$data['id'].'"')->field('hid')->select();
            $result = $model->data($data)->where('id='.$data['id'])->save();
            addlog('修改专题分组，ID：'.$data['id']);
        }else{
            $result = $model->data($data)->add();
            addlog('添加专题分组，ID：'.$result);
        }
        if($result!==false && $allresourceResult!==false && $hotelvolumeResult!==false){
            $model->commit();
            //生成json文件
            if(!empty($searchHidList)){
                $this->updatejson_one($searchHidList);
            }
            if($data['id']){
                if($vo['icon'] != $data['icon']){
                    @unlink(FILE_UPLOAD_ROOTPATH.$vo['icon']);
                }
            }
            $this->success('恭喜，操作成功！',U('index'));
        }else{
            $model->rollback();
            $this->error('操作失败',U('index'));
        }
    }

    /**
     * [启用通用一级栏目]
     */
    public function unlock(){
        if (count($_POST['ids'])<1) {
            $this->error('系统提示：参数错误');
        }
        $map['id'] = $htMap['topic_id'] = array('in',$_POST['ids']);
        $result = D("topic_group")->where($map)->setField('status',1);
        if ($result === false) {
            $this->error('系统提示：启用失败');
        }else{
            $hidlist = D("hotel_topic")->where($htMap)->field('hid')->group('hid')->select();
            $this->updatejson_one($hidlist);
            $this->success('系统提示：启用成功');
        }
    }

    /**
     * [禁用通用一级栏目]
     */
    public function lock(){
        if (count($_POST['ids'])<1) {
            $this->error('系统提示：参数错误');
        }
        $map['id'] = $htMap['topic_id'] = array('in',$_POST['ids']);
        $result = D("topic_group")->where($map)->setField('status',0);
        if ($result === false) {
            $this->error('系统提示：禁用失败');
        }else{
            $hidlist = D("hotel_topic")->where($htMap)->field('hid')->group('hid')->select();
            $this->updatejson_one($hidlist);
            $this->success('系统提示：禁用成功');
        }
    }

    /**
     * [删除通用一级栏目]
     */
    public function delete(){
        $ids = $_POST['ids'];
        if(count($ids)!=1){
            $this->error('仅能对一则通用栏目进行操作');
            die();
        }
        $model = D(CONTROLLER_NAME);
        $TopicCategory = D("TopicCategory");
        $map['id'] = $ids[0];
        $list = $model->where($map)->find();
        $TClist = $TopicCategory->where('groupid="'.$ids[0].'"')->select();
        if($TClist){
            $this->error('该一级栏目下含有二级栏目，请先删除对应的二级栏目！');
        }
        $model->startTrans();
        //操作volume表
        $searchHidList = D("hotel_topic")->where('topic_id="'.$ids[0].'"')->field('hid')->select();//hid集合
        $delvolumeResult = true;
        if(!empty($list['size'])){
            if (!empty($searchHidList)) {
                foreach ($searchHidList as $key => $value) {
                    $volumeHidList[] = $value['hid'];
                }
                $vMap['hid'] = array('in',$volumeHidList);
                $delvolumeResult = D("hotel_volume")->where($vMap)->setDec('topic_size',$list['size']);
            }
        }
        //删除资源
        $result = $model->where($map)->delete();
        if($result!==false && $delvolumeResult!==false){
            $model->commit();
            //生成json文件
            if(!empty($searchHidList)){
                $this->updatejson_one($searchHidList);
            }
            $this->success('恭喜，删除成功！');
        }else{
            $model->rollback();
            $this->error('删除失败，参数错误！');
        }
    }

    public function upload_icon(){
        $callback = array();
        if (!empty($_FILES[$_REQUEST["name"]]["name"])) {
            $upload = new \Think\Upload();
            $upload->maxSize=10485760; //上传10M
            $upload->exts=array('jpg','png','jpeg');
            $upload->rootPath='./Public/';
            $upload->savePath='./upload/topic/';
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

    /**
     * [更新通用一级栏目json]
     * @param  [array] $hid_arr [酒店集合 array('hid'=>4008)]
     */
    private function updatejson_one($hid_arr){
        $list = D("topic_group")->where('status=1')->field('id,title,name,en_name,intro,icon')->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $list[$key]['nexttype'] = 'topic_second';
            }
            $jsondata = json_encode($list);
        }else{
            $jsondata = '';
        }
        foreach ($hid_arr as $key => $value) {
            if (!is_dir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$value['hid'])) {
                mkdir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$value['hid']);
            }
            $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$value['hid'].'/topic_first.json';
            file_put_contents($filename, $jsondata);
        }
    }
}