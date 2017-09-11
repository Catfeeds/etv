<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 酒店跳转设置控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
class HotelJumpController extends ComController {
    //查询
    public function _map(){
        $map = array();
        $data = session(session_id());
        $hotelid = 0;
        if(!empty($_POST['hid'])){
            if($_POST['hid'] != 'hid-1gg'){ //自定义查询所有酒店
                $map['hid'] = $_POST['hid'];
                $vo=M('hotel')->getByHid($_POST['hid']);
                $hotelid=$vo['id'];
                $data['content_hid'] = $_POST['hid'];
                session(session_id(),$data);
            }else{
                $map = array();
                $data['content_hid'] = null;
                session(session_id(),$data);
            }
        }else{
            $data = session(session_id());
            if (!empty($data['content_hid'])) {
                $map['hid'] = $data['content_hid'];
                $vo = M('hotel')->getByHid($data['content_hid']);
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
        $model = M('hotel_jump');
        $map = $this->_map();
        $hid_condition = $this->isHotelMenber();
        if($hid_condition && empty($map)){
            $map['hid'] = $hid_condition;
        }
        $list = $this->_list($model,$map);
        foreach ($list as $key => $value) {
            $list[$key]['jumpinfo']='';
            if($value['isjump']==0){
                $list[$key]['jumpinfo']='开机后，停留在欢迎页。';
            }else if($value['isjump']==1){
                $list[$key]['jumpinfo']='开机后，欢迎页停留'.$list[$key]['staytime'].'秒后，自动跳过欢迎页。';
            }
            if($value['video_set']==1){
                $list[$key]['jumpinfo'] .= '欢迎页前插播视频。';
            }else if($value['video_set']==2){
                $list[$key]['jumpinfo'] .= '跳过欢迎页后，插播视频，再进入主页。';
            }else{
                $list[$key]['jumpinfo'] .= '欢迎页前后都不播放视频。';
            }
            if (!empty($value['resourceid'])) {
                $vo=M('hotel_resource')->getById($value['resourceid']);
                $list[$key]['video']=$vo['filepath'];
                $list[$key]['audit_status']=$vo['audit_status'];
                $list[$key]['status']=$vo['status'];
            }else{
                $list[$key]['video']='';
                $list[$key]['audit_status']='-2';
            }
        }
        $this -> assign("list",$list);
        $this -> display();
    }
    public function _before_add(){
        $this->isHotelMenber();
        $hotelid = 0;
        $data = session(session_id());
        if(!empty($data['content_hid'])){
            $vo=M('hotel')->getByHid($data['content_hid']);
            $hotelid=$vo['id'];
        }
        $category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
        $category = $tree->get_tree(0,$str,$hotelid);
        $this->assign('pHotel',$category);
    }
    //修改
    public function edit(){
        $model = M(CONTROLLER_NAME);
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if(count($ids)!=1){
            $this->error('参数错误，每次只能修改一条内容！');
        }
        $var = $model->getById($ids[0]);

        $voHotel=M('hotel')->getByHid($var['hid']);
        $voResource=M('hotel_resource')->getById($var['resourceid']);
        
        $hotelname = D("hotel")->where("hid='".$var['hid']."'")->field('hotelname')->find();

        $var['filepath']=$voResource['filepath'];
        $this->assign('vo',$var);
        $this->assign('hotelname',$hotelname['hotelname']);
        $this -> display();
    }
    //保存
    public function update(){
        $model = M(CONTROLLER_NAME);
        $data = array();
        $resData = array();
        $data['id'] = I('post.id','','intval');
        $data['hid'] = $hmap['hid'] = I('post.hid','','strip_tags');
        $data['isjump'] = I('post.isjump','','intval');
        $data['staytime'] = I('post.staytime','','intval');
        $data['video_set'] = I('post.video_set','','intval');
        $size = I('post.size','','intval');
        $resData['size'] = round($size/1024,3);
        $resData['filepath'] = I('post.filepath','','strip_tags');

        if($data['isjump']==0){
            $data['staytime']=-1;
        }else if ($data['staytime']<=0) {
            $this->error('停留时间必须大于0！');
        }
        if ($data['video_set']>0 && empty($resData['filepath'])) {
            $this->error('请上传跳转视频！');
        }

        $logText='';
        $model->startTrans();
        if($data['id']){//修改
            $vo = $model->getById($data['id']);
            $resourceInfo=M('hotel_resource')->getById($vo['resourceid']);

            if ($vo['resourceid']>0) {//含有跳转资源
                if ($data['video_set']>0){//播放视频
                    //查询容量
                    $changesize = $resData['size'] - $resourceInfo['size']; // 新的减去旧的
                    $volumeResult = $this->checkVolume($data['hid'],$changesize);//检查容量是否超标
              
                    if($volumeResult === false){
                        @unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
                        $this->error("超过申请容量值，无法修改资源");
                    }

                    if ($resData['filepath'] != $resourceInfo['filepath']) {//修改视频
                        $resData['upload_time'] = time();
                        $resData['audit_status'] = 0;
                        @unlink(FILE_UPLOAD_ROOTPATH.$resourceInfo['filepath']);
                        $result = M('hotel_resource')->data($resData)->where('id='.$vo['resourceid'])->save();
                        $logText=' 修改跳转视频,资源ID：'.$vo['resourceid'];
                    }
                }elseif($data['video_set'] == 0){ //从含有视频资源修改为不含有视频资源时候
                    $result = D("hotel_resource")->where('id='.$vo['resourceid'])->delete();
                    D("hotel_jump")->where('id='.$data['id'])->setField('resourceid',0);
                    @unlink(FILE_UPLOAD_ROOTPATH.$resourceInfo['filepath']);
                }
                
            }else{//本来没有含有跳转视频
                if($data['video_set']>0){

                    //查询容量
                    $volumeResult = $this->checkVolume($data['hid'],$resData['size']);//检查容量是否超标
                    if($volumeResult === false){
                        @unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
                        $this->error("超过申请容量值，无法修改资源");
                    }
                    $resData['hid'] = $data['hid'];
                    $resData['title'] = '跳转视频';
                    $resData['type'] = 1;
                    $resData['cat'] = 'jump';
                    $resData['intro'] = '跳转视频';
                    $resData['status'] = 1;
                    $resData['audit_status'] = 0;
                    $resData['upload_time'] = time();
                    $result = M('hotel_resource')->data($resData)->add();
                    $data['resourceid'] = $result;
                    $logText=' 添加跳转视频,资源ID：'.$result;
                }
            }
            $hjid = $model->data($data)->where('id='.$data['id'])->save();
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
            if($hjid !== false && $result !== false && $updatesize !== false){
                $model->commit();
                addlog('修改跳转设置，ID：'.$data['id'].$logText);
                $this->success("修改成功",U('index'));
                die();
            }else{
                $model->rollback();
                $this->error('修改失败!',U('index'));
            }
        }else{
            $isExist=$model->field('id')->where('hid="'.$data['hid'].'"')->select();
            if (!empty($isExist)) {
                $this->error('请不要为酒店重复添加跳转设置！',U('index'));
            }
            if ($data['video_set']>0) {
                //查询容量
                $volumeResult = $this->checkVolume($data['hid'],$resData['size']);//检查容量是否超标
                if($volumeResult === false){
                    @unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
                    $this->error("超过申请容量值，无法添加资源");
                }

                $resData['hid'] = $data['hid'];
                $resData['title'] = '跳转视频';
                $resData['type'] = 1;
                $resData['cat'] = 'jump';
                $resData['intro'] = '跳转视频';
                $resData['status'] = 1;
                $resData['audit_status'] = 0;
                $resData['upload_time'] = time();
                $rid = M('hotel_resource')->data($resData)->add();
                $data['resourceid'] = $rid;
                $logText=' 添加跳转视频,资源ID：'.$rid;
            }else{//不播放视频
                $data['resourceid'] = 0;
            }
            $id = $model->data($data)->add();
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
            if($id !== false && $rid !== false &&  $updatesize!==false){
                $model->commit();
                addlog('添加跳转设置，ID：'.$id.$logText);
                $this->success('恭喜，操作成功！',U('index'));
            }else{
                $model->rollback();
                $this->error('新增失败！',U('index'));
            }
        }
    }

    public function upload(){
        $callback = array();
        if (!empty($_FILES[$_REQUEST["name"]]["name"])) {
            if(empty($_REQUEST['hid'])){
                $callback['status'] = 0;
                $callback['info'] = "请选择酒店";
            }else{
                $upload = new \Think\Upload();
                $upload->maxSize=209715200;// 设置附件上传大小200M
                $upload->exts=array('mp4');
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

    public function delete(){
        $ids = $_POST['ids'];
        if(count($ids)<1){
            $this->error('请选择至少一条记录');
            die();
        }
        $model = D(CONTROLLER_NAME);
        $map['zxt_hotel_jump.id'] = array('in',$ids);
        $decVolume = $model->field('zxt_hotel_jump.resourceid,zxt_hotel_resource.hid,zxt_hotel_resource.filepath,zxt_hotel_resource.size')->join('zxt_hotel_resource ON zxt_hotel_jump.resourceid = zxt_hotel_resource.id')->where($map)->select();

        $model->startTrans();
        $vresult = true;
        $rresult = true;
        $jresult = true;
        $residArr=array();
        if(!empty($decVolume)){
            foreach ($decVolume as $key => $value) {
                $residArr[$key] = $value['resourceid'];
                $vmap['hid']=$value['hid'];
                //更改volume表
                $vresult = $this->csetDec($vmap,'content_size',$value['size']);
            }
            //删除resource表
            $Rmap['id']=array('in',$residArr);
            $rresult = M('hotel_resource')->where($Rmap)->delete();
        }

        //删除hotelJump表
        $jresult = $model->where($map)->delete();

        if($vresult !== false && $rresult !== false && $jresult !== false){
            if(!empty($decVolume)){
                foreach ($decVolume as $key => $value) {
                    @unlink(FILE_UPLOAD_ROOTPATH.$value['filepath']);
                }
            }
            if(is_array($ids)){
                $ids = implode(',',$ids);
            }
            if(is_array($residArr)){
                $rids = implode(',',$residArr);
            }
            addlog('删除'.CONTROLLER_NAME.'表数据，数据ID：'.$ids.'，删除资源ID：'.$rids);
            $model->commit();
            $this->success('删除成功');
        }else{
            $model->rollback();
            $this->error('删除失败');
        }
    }
    //启用
    public function unlock(){
        $model = M(CONTROLLER_NAME);
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if($ids){
            if(is_array($ids)){
                $ids = implode(',',$ids);
                $map['id']  = array('in',$ids);
            }else{
                $map = 'id='.$ids;
            }
            $list = $model->where($map)->select();
            $residArr=array();
            foreach ($list as $key => $value) {
                $residArr[$key]=$value['resourceid'];
            }
            $where['id']=array('in',$residArr);
            $result=M('hotel_resource')->where($where)->setField("status",1);
            if($result !== false){
                addlog('启用跳转视频资源数据，资源数据ID：'.$ids);
                $this->success('恭喜，启用成功！');
            }else{
                $this->error('启用失败，参数错误！');
            }
        }else{
            $this->error('参数错误！');
        }
    }
    public function lock(){
        $model = M(CONTROLLER_NAME);
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if($ids){
            if(is_array($ids)){
                $ids = implode(',',$ids);
                $map['id']  = array('in',$ids);
            }else{
                $map = 'id='.$ids;
            }
            $list = $model->where($map)->select();
            $residArr=array();
            foreach ($list as $key => $value) {
                $residArr[$key]=$value['resourceid'];
            }
            $where['id']=array('in',$residArr);
            $result=M('hotel_resource')->where($where)->setField("status",0);
            if($result !== false){
                addlog('禁用跳转视频资源数据，资源数据ID：'.$ids);
                $this->success('恭喜，禁用成功！');
            }else{
                $this->error('禁用失败，参数错误！');
            }
        }else{
            $this->error('参数错误！');
        }
    }
}