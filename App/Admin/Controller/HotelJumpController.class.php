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
        
        $hotelname = D("hotel")->where("hid='".$var['hid']."'")->field('hotelname')->find();

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
        $data['isjump'] = I('post.isjump','','intval'); //是否跳转
        $data['staytime'] = I('post.staytime','','intval');  //跳转时间
        $data['video_set'] = I('post.video_set','','intval'); //跳转选项设置
    
        if($data['isjump']==0){
            $data['staytime']=-1;
        }else if ($data['staytime']<=0) {
            $this->error('停留时间必须大于0！');
        }

        $logText='';
        $model->startTrans();
        if($data['id']){//修改
            if($model->data($data)->where('id='.$data['id'])->save()){
                $model->commit();
                $this->success("修改成功",U('index'));
                die();
            }else{
                $model->rollback();
                $this->error('修改失败!',U('index'));
            }
        }else{
            $isExist=$model->field('id')->where('hid="'.$data['hid'].'"')->find();
            if (!empty($isExist)) {
                @unlink(FILE_UPLOAD_ROOTPATH.$resData['filepath']);
                $this->error('请不要为酒店重复添加跳转设置！',U('index'));
            }
            if($model->data($data)->add()){
                $model->commit();
                $this->success('恭喜，操作成功！',U('index'));
            }else{
                $model->rollback();
                @unlink(FILE_UPLOAD_ROOTPATH.$resData['filepath']);
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

    public function resource(){
        $where['resourceid'] = $_REQUEST['resourceid'];
        $list = D("hotel_jump_resource")->where($where)->select();
        $this->assign('list', $list);
        $this->assign('resourceid', $_REQUEST['resourceid']);
        $this->assign('hid', $_REQUEST['resourcehid']);
        $this->display();
    }

    public function addresource(){
        $this->assign('hid',$_POST['hid']);
        $this->assign('resourceid',$_POST['resourceid']);
        $this->display();
    }

    public function updateresource(){
        $id = I('post.id','','intval');
        $data['title'] = I('post.title','','strip_tags');
        $data['resourceid'] = I('post.resourceid','','strip_tags');
        $data['hid'] = I('post.hid');
        $data['sort'] = I('post.sort','','strip_tags');
        $data['filepath'] = I('post.filepath','','strip_tags');
        $size = I('post.size','','intval');
        $data['size'] = round($size/1024,3);
        if ($id) {
            # code...
        }else{
            $volumeResult = $this->checkVolume($data['hid'],$data['size']);//检查容量是否超标
            if($volumeResult === false){
                @unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
                $this->error("超过申请容量值，无法添加资源");
            }
            $data['status'] = 0;
            $data['audit_status'] = 0;
            $data['audit_time'] = date("Y-m-d");
            $data['upload_time'] = date("Y-m-d");
            if(D("hotel_jump_resource")->data($data)->add()){
                $this->success('操作成功', U('resource',array('resourceid'=>$data['resourceid'], 'resourcehid'=>$data['hid']) ) );
            }else{

            }
        }
    }

    public function getjumptonew(){
        $where['zxt_hotel_resource.cat'] = "jump";
        $list = D("hotel_resource")
                ->where($where)
                ->join('zxt_hotel_jump ON zxt_hotel_resource.id=zxt_hotel_jump.resourceid')
                ->field('zxt_hotel_resource.*,zxt_hotel_jump.id as myresourceid')
                ->select();
        if (!empty($list)) {
            D("hotel_resource")->startTrans();
            $insertdata = [];
            foreach ($list as $key => $value) {
                $insertdata[$key]['hid'] = $value['hid'];
                $insertdata[$key]['resourceid'] = $value['myresourceid'];
                $insertdata[$key]['status'] = $value['status'];
                $insertdata[$key]['audit_status'] = $value['audit_status'];
                $insertdata[$key]['audit_time'] = date("Y-m-d H:i:s",$value['audit_status']);
                $insertdata[$key]['title'] = $value['title'];
                $insertdata[$key]['filepath'] = $value['filepath'];
                $insertdata[$key]['upload_time'] = date("Y-m-d H:i:s",$value['upload_time']);
                $insertdata[$key]['sort'] = $value['sort']+1;
                $insertdata[$key]['size'] = $value['size'];
            }
            if(D("hotel_jump_resource")->addAll($insertdata)){
                D("hotel_jump_resource")->commit();
                var_dump('ok');
            }else{
                var_dump('gg');
            }
        }
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