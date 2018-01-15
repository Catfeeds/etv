<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 酒店欢迎图片控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
class HotelwelcomeController extends ComController {
    public function _map(){
        $map = array();
        $data = session(session_id());
        $hotelid = 0;
        if(!empty($_POST['hid'])){
            if($_POST['hid'] != 'hid-1gg'){ //自定义查询所有酒店
                $map['zxt_hotel_welcome.hid'] = $_POST['hid'];
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
            if(!empty($data['content_hid'])){
                $map['zxt_hotel_welcome.hid'] = $data['content_hid'];
                $vo=M('hotel')->getByHid($data['content_hid']);
                $hotelid=$vo['id'];
            }
        }
        $this->assign('hid',$map['zxt_hotel_welcome.hid']);
        $category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
        $category = $tree->get_tree(0,$str,$hotelid);
        $this->assign('pHotel',$category);
        return $map;
    }
    public function index(){
        $model = D(CONTROLLER_NAME);
        $map = $this->_map();
        $hid_condition = $this->isHotelMenber();
        if($hid_condition && empty($map)){
            $map['zxt_hotel_welcome.hid'] = $hid_condition;
        }
        $count = $model->where($map)->join('left join zxt_hotel_resource ON zxt_hotel_welcome.resourceid=zxt_hotel_resource.id')->count();
        $Page = new\Think\Page($count,$pagesize=10,$_POST);
        $pageshow = $Page -> show();
        $list = $model->where($map)
                      ->field('zxt_hotel_welcome.id,zxt_hotel_welcome.hid,zxt_hotel_resource.sort,zxt_hotel_resource.title,zxt_hotel_resource.filepath,zxt_hotel_resource.intro,zxt_hotel_resource.upload_time,zxt_hotel_resource.audit_status,zxt_hotel_resource.status')
                      ->join('left join zxt_hotel_resource ON zxt_hotel_welcome.resourceid=zxt_hotel_resource.id')
                      ->limit($Page ->firstRow.','.$Page -> listRows)
                      ->order('zxt_hotel_resource.upload_time desc,zxt_hotel_welcome.hid desc')
                      ->select();
        $this -> assign("page",$pageshow);
        $this -> assign("list",$list);
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
        }
        $category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
        $category = $tree->get_tree(0,$str,$hotelid);
        $this->assign('pHotel',$category);

        $this->display();
    }
    //修改
    public function edit(){
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if(count($ids)!=1){
            $this->error('参数错误，每次只能修改一条内容！');
        }

        $vo = M('hotel_welcome')->table('zxt_hotel_welcome welcome,zxt_hotel_resource resource')
        ->field('welcome.id,welcome.hid,resource.title,resource.intro,resource.filepath,resource.sort')
        ->where('welcome.resourceid=resource.id AND welcome.hid=resource.hid AND welcome.id='.$ids[0])
        ->find();
        if(empty($vo)){
            $this->error('参数错误！');
        }
        $hotelname = D("hotel")->where("hid='".$vo['hid']."'")->field('hotelname')->find();
        $this->assign('hotelname',$hotelname['hotelname']);
        $this->assign('vo',$vo);
        $this -> display();
    }
    //保存
    public function update(){
        $model=M('hotel_welcome');
        $HWdata['id'] = I('post.id','','intval');
        $data['hid'] = $hmap['hid'] = I('post.hid','','strip_tags');
        $data['title'] = isset($_POST['title'])?$_POST['title']:false;
        $data['intro'] = I('post.intro','','strip_tags');
        $data['sort'] = I('post.sort','','intval');
        $data['filepath'] = I('post.filepath','','strip_tags');
        $size = I('post.size','','intval');
        $data['size'] = round($size/1024,3);
        $data['type'] = 2;
        $data['cat'] = 'welcome';
        $data['audit_status'] = 0;

        if(!$data['title']){
            $this->error('警告！资源标题必须填写！');
        }
        if (empty($data['filepath'])) {
            $this->error('请上传图片文件！');
        }

        $model->startTrans();
        if($HWdata['id']){//修改
            $vo = $model->getById($HWdata['id']);
            $resourceInfo=M('hotel_resource')->getById($vo['resourceid']);
            if ($data['filepath'] != $resourceInfo['filepath']) {
                $data['upload_time'] = time();
            }
            //查询容量
            $changesize = $data['size'] - $resourceInfo['size'];
            $volumeResult = $this->checkVolume($data['hid'],$changesize);//检查容量是否超标

            if($volumeResult === false){
                @unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
                $this->error("超过申请容量值，无法修改资源");
            }

            $result = M('hotel_resource')->data($data)->where('id='.$vo['resourceid'])->save();
            $updatesize = $this->updatecontentsize($hmap);
            if ($result !== false && $updatesize !== false) {
                $model->commit();
                if ($data['filepath'] != $resourceInfo['filepath']) {
                    @unlink(FILE_UPLOAD_ROOTPATH.$resourceInfo['filepath']);
                }
                addlog('修改欢迎图片，ID：'.$HWdata['id'].'，资源ID：'.$vo['resourceid']);
                $this->success('修改成功',U('index'));
            }else{
                $model->rollback();
                $this->error('修改失败',U('index'));
            }
            
        }else{

            $volumeResult = $this->checkVolume($data['hid'],$data['size']);//检查容量是否超标

            if($volumeResult === false){
                @unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
                $this->error("超过申请容量值，无法新增资源");
            }

            $data['upload_time'] = time();
            $data['status'] = 0;
            $rid = M('hotel_resource')->data($data)->add();
            $HWdata['hid'] = $data['hid'];
            $HWdata['resourceid']=$rid;
            $hwid = $model->data($HWdata)->add();
            $updatesize = $this->updatecontentsize($hmap);
            if($rid !== false && $hwid !== false && $updatesize !== false){
                $model->commit();
                addlog('添加欢迎图片，ID：'.$hwid.'，资源ID：'.$rid);
                $this->success('恭喜，操作成功！',U('index'));
            }else{
                $model->rollback();
                $this->error('添加欢迎图片失败');
            }
        }
    }
    public function delete(){
        $model = M(CONTROLLER_NAME);
        $ids = $_REQUEST['ids'];
        if(count($ids)<1){
            $this->error('参数错误！');
        }else{
            $map['zxt_hotel_welcome.id'] = array('in',$ids);
        }
        $decVolume = $model->field('zxt_hotel_welcome.resourceid,zxt_hotel_resource.hid,zxt_hotel_resource.filepath,zxt_hotel_resource.size')->join('zxt_hotel_resource ON zxt_hotel_welcome.resourceid = zxt_hotel_resource.id')->where($map)->select();

        $model->startTrans();
        $residArr=array();
        foreach ($decVolume as $key => $value) {
            $residArr[$key] = $value['resourceid'];
            $vmap['hid']=$value['hid'];
            //更改volume表
            $vresult = $this->csetDec($vmap,'content_size',$value['size']);
        }
        //删除resource表
        $Rmap['id']=array('in',$residArr);
        $rresult = M('hotel_resource')->where($Rmap)->delete();
        //删除welcome表
        $wresult = $model->where($map)->delete();
        if($vresult !== false && $rresult !== false && $wresult !== false){
            foreach ($decVolume as $key => $value) {
                @unlink(FILE_UPLOAD_ROOTPATH.$value['filepath']);
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

    public function upload(){
        $callback = array();
        if (!empty($_FILES[$_REQUEST["name"]]["name"])) {
            if(empty($_REQUEST['hid'])){
                $callback['status'] = 0;
                $callback['info'] = "请选择酒店";
            }else{
                $upload = new \Think\Upload();
                $upload->maxSize=2097152;  //设置大小为2M
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
                addlog('启用欢迎图片资源数据，资源数据ID：'.$ids);
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
                addlog('禁用欢迎图片资源数据，资源数据ID：'.$ids);
                $this->success('恭喜，禁用成功！');
            }else{
                $this->error('禁用失败，参数错误！');
            }
        }else{
            $this->error('参数错误！');
        }
    }
}