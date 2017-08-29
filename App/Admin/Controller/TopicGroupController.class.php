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
    //保存
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
        $allresourceHidList = array();
        if($data['id']){
            $vo = $model->getById($data['id']);

            if($vo['icon'] != $data['icon']){
                //allresource删除
                $delAllresourceResult = true;
                $lastName_arr = explode("/", $vo['icon']);
                $lastName = $lastName_arr[count($lastName_arr)-1];
                $delAllresourceResult = D('hotel_allresource')->where('name="'.$lastName.'"')->delete();

                //allresource新增
                $addAllresourceResult = true;
                $searchHidList = D("hotel_topic")->where('topic_id="'.$data['id'].'"')->field('hid')->select();
                if($searchHidList){
                    $getName_arr = explode("/", $data['icon']);
                    $getName = $getName_arr[count($getName_arr)-1];
                    $i = 0;
                    foreach ($searchHidList as $key => $value) {
                        $allresourceHidList[] = $value['hid'];
                        $addlist[$i]['hid'] = $value['hid'];
                        $addlist[$i]['name'] = $getName;
                        $addlist[$i]['type'] = 2;
                        $addlist[$i]['timeunix'] = time();
                        $addlist[$i]['time'] = date("Y-m-d H:i:s");
                        $addlist[$i]['web_upload_file'] = $data['icon'];
                        $i++;
                    }
                    $addAllresourceResult = D("hotel_allresource")->addAll($addlist);

                    //修改hotel_volume表
                    $vo['size'] = empty($vo['size'])?0:$vo['size'];
                    $changeSize = $data['size'] - $vo['size'];
                    $hotelVolumeMap['hid'] = array('in',$allresourceHidList);
                    $hotelvolumeResult = D("hotel_volume")->where($hotelVolumeMap)->setInc('topic_size',$changeSize);
                }
                if($addAllresourceResult===false || $delAllresourceResult===false){
                    $allresourceResult = false;
                }
            }
            
            $result = $model->data($data)->where('id='.$data['id'])->save();
            addlog('修改专题分组，ID：'.$data['id']);
        }else{
            $result = $model->data($data)->add();
            addlog('添加专题分组，ID：'.$result);
        }
        if($result!==false && $allresourceResult!==false && $hotelvolumeResult!==false){
            $model->commit();
            //生成资源xml文件
            if(!empty($allresourceHidList)){
                foreach ($allresourceHidList as $key => $value) {
                    $xmlFilepath = FILE_UPLOAD_ROOTPATH.'/upload/resourceXml/'.$value.'.txt';
                    $this->fileputXml(D("hotel_allresource"),$value,$xmlFilepath);
                }
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
        $delvolumeResult = true;
        if(!empty($list['size'])){
            $searchHidList = D("hotel_topic")->where('topic_id="'.$ids[0].'"')->field('hid')->select();
            if (!empty($searchHidList)) {
                foreach ($searchHidList as $key => $value) {
                    $volumeHidList[] = $value['hid'];
                }
                $vMap['hid'] = array('in',$volumeHidList);
                $delvolumeResult = D("hotel_volume")->where($vMap)->setDec('topic_size',$list['size']);
            }
        }
        //操作allresource表
        $allresourceResult = true;
        if(!empty($list['icon'])){
            $delName_arr = explode("/",$list['icon']);
            $delName = $delName_arr[count($delName_arr)-1];
            if(!empty($delName)){
                $delAllresourceResult = D("hotel_allresource")->where('name="'.$delName.'"')->delete();
            }
        }
        //删除资源
        $result = $model->where($map)->delete();
        // var_dump($result,$delAllresourceResult,$allresourceResult);die();
        if($result!==false && $delAllresourceResult!==false & $allresourceResult!==false){
            addlog('删除通用一级栏目');
            $model->commit();
            //生成资源xml文件
            if(!empty($volumeHidList)){
                foreach ($volumeHidList as $key => $value) {
                    $xmlFilepath = FILE_UPLOAD_ROOTPATH.'/upload/resourceXml/'.$value.'.txt';
                    $this->fileputXml(D("hotel_allresource"),$value,$xmlFilepath);
                }
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
}