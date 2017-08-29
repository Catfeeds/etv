<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 专题栏目控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class TopicCategoryController extends ComController {
    public function _map(){
        $map = array();
        if (!empty($_REQUEST['keyword'])) {
            $map['name']=array("like","%".$_REQUEST['keyword']."%");
        }
        if (!empty($_REQUEST['groupid'])) {
            $map['groupid'] = $_REQUEST['groupid'];
            $this->assign("groupId",$_REQUEST['groupid']);
        }else{
            $this->assign("groupId",'');
        }
        if (!empty($_REQUEST['langcodeid'])) {
            $map['langcodeid'] = $_REQUEST['langcodeid'];
            $this->assign("langId",$_REQUEST['langcodeid']);
        }else{
            $this->assign("langId",'');
        }
        return $map;
    }
    public function _before_index(){
        $this->_assign_list();
    }
    public function _before_add(){
        $this->_assign_list();
    }
    public function _before_edit(){
        $this->_assign_list();
    }
    public function index(){
        $model = D(CONTROLLER_NAME);
        $map = $this->_map();
        $list = $this->_list($model, $map, 10 ,"groupid asc,langcodeid asc,sort asc");
        $this -> assign("list",$list);
        $this->display();
    }
    public function _assign_list(){
        $TopicGroup = D ('TopicGroup');
        $groupList = $TopicGroup->field ('id,title')->order('id asc')->select();
        $this->assign('groupList', $groupList);
        $Langcode = D ('Langcode');
        $langList = $Langcode->field ('id,name')->order('id asc')->select();
        $this->assign('langList', $langList);
        $Modeldefine = D ('Modeldefine');
        $modeList = $Modeldefine->where('codevalue="102" or codevalue="103" ')->field ('id,name')->order('codevalue asc')->select();
        $this->assign('modeList', $modeList);
    }

    public function update(){
        $model = M(CONTROLLER_NAME);
        $data['id'] = I('post.id','','intval');
        $data['name'] = I('post.name','','strip_tags');
        $data['modeldefineid'] = I('post.modeldefineid','','intval');
        $data['langcodeid'] = I('post.langcodeid','','intval');
        $data['groupid'] = I('post.groupid','','intval');
        $data['sort'] = I('post.sort','','intval');
        $data['icon'] = I('post.icon','','strip_tags');
        $size = I('post.size','','intval');
        $data['size'] = round($size/1024,3);
        $model->startTrans();
        $allresourceResult = true;
        $hotelvolumeResult = true;
        $allresourceHidList = array();
        if($data['id']){
            $vo = $model->getById($data['id']);
            //allresource删除
            $delAllresourceResult = true;
            if(!empty($vo['icon'])){
                $lastName_arr = explode("/", $vo['icon']);
                $lastName = $lastName_arr[count($lastName_arr)-1];
                $delAllresourceResult = D('hotel_allresource')->where('name="'.$lastName.'"')->delete();
            }
            //allresource新增
            $searchHidList = D("hotel_topic")->where('topic_id="'.$data["groupid"].'"')->field('hid')->select();
            $addAllresourceResult = true;
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
            $result = $model->data($data)->where('id='.$data['id'])->save();
            addlog('修改专题分组，ID：'.$data['id']);
        }else{
            $result = $model->data($data)->add();
            addlog('添加专题二级栏目，ID：'.$result);
            $searchHidList = D("hotel_topic")->where('topic_id="'.$data["groupid"].'"')->field('hid')->select();
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
                $allresourceResult = D("hotel_allresource")->addAll($addlist);
                //修改hotel_volume表
                $hotelVolumeMap['hid'] = array('in',$allresourceHidList);
                $hotelvolumeResult = D("hotel_volume")->where($hotelVolumeMap)->setInc('topic_size',$data['size']);
            }
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
            if($vo['icon'] != $data['icon']){
                @unlink(FILE_UPLOAD_ROOTPATH.$vo['icon']);
            }
            $this->success('恭喜，操作成功！',U('index'));
        }else{
            $model->rollback();
            $this->error('操作失败',U('index'));
        }
        
    }
    public function moveup(){
        $model = D(CONTROLLER_NAME);
        $currentid = $_REQUEST['ids'];
        if(count($currentid)!=1){
            $this->error("只能移动一条数据");
            die();
        }
        $currentid = $currentid['0'];
        $vo = $model->getById($currentid);
        $currentsort=$vo['sort'];
        $currentlang=$vo['langcodeid'];
        $currentgroup=$vo['groupid'];
        $list=$model->where('langcodeid = '.$currentlang.' and groupid='.$currentgroup)->order('sort asc')->select();
        if ($list[0]['sort'] == $currentsort) {
            $this->error("已在最前，不能上移了");
        }else{
            for ($i = 1; $i < count($list); $i++) {
                if ($list[$i]['sort'] == $currentsort) {
                    $tempsort=$list[$i-1]['sort'];
                    $model-> where('id='.$list[$i-1]['id'].'')->setField('sort',$currentsort);
                    $model-> where('id='.$currentid.'')->setField('sort',$tempsort);
                    $this->success("操作成功");
                    die();
                }
            }
        }
    }
    public function movedown(){
        $model = D(CONTROLLER_NAME);
        $currentid = $_REQUEST['ids'];
        if(count($currentid)!=1){
            $this->error("只能移动一条数据");
            die();
        }        
        $vo = $model->getById($currentid['0']);
        $currentsort=$vo['sort'];
        $currentlang=$vo['langcodeid'];
        $currentgroup=$vo['groupid'];
        $list=$model->where('langcodeid = '.$currentlang.' and groupid = '.$currentgroup)->order('sort asc')->select();
        if ($list[count($list)-1]['sort'] == $currentsort) {
            $this->error("已在最后，不能下移了");
        }else{
            for ($i = 0; $i < (count($list)-1); $i++) {
                if ($list[$i]['sort'] == $currentsort) {
                    $tempsort=$list[$i+1]['sort'];
                    $model-> where('id='.$list[$i+1]['id'].'')->setField('sort',$currentsort);
                    $model-> where('id='.$currentid['0'].'')->setField('sort',$tempsort);
                    $this->success("操作成功");
                    die();
                }
            }
        }
    }
    public function resource(){
        $menuid = is_array($_REQUEST['ids'])?$_REQUEST['ids']:array($_REQUEST['ids']);
        $model = D(CONTROLLER_NAME);
        if(count($menuid)!=1){
            $this->error("请选择一条记录进行操作");
            die();
        }
        $menuid = $menuid['0'];
        $vo = $model->getById($menuid);
        $TopicGroup = D ('TopicGroup');
        $voCat=$TopicGroup->getById($vo['groupid']);
        $menutitle='<font color="blue">['.$voCat['title'].']</font>&nbsp; - &nbsp;'.$vo['name'];//专题分组title和专题栏目名称
        $this->assign("menutitle",$menutitle);
        $this->assign("menuid",$menuid);
        $this->assign('groupid',$vo['groupid']);

        $Modeldefine = D("Modeldefine");
        $voModel=$Modeldefine->getById($vo['modeldefineid']);//模型 有固定码值固定
        if (empty($voModel)) {
            $type=0;
        }else if ($voModel['codevalue']=="102") { //图片资源
            $type=2;
        }else if($voModel['codevalue']=="103"){ //视频资源
            $type=1;
        }else{
            $type=0;
        }
        $this->assign("resType",$type);
        $TopicResource = D("TopicResource");
        $list = $TopicResource->where('cid='.$menuid.'')->order("sort asc")->select();
        $this->assign("list",$list);
        $this->assign("maxsort",end($list)['sort']);
        $this->display();
    }
    public function resourceinsert(){
        $menuid = $_REQUEST['menuid'];
        $resType = $_REQUEST['resType'];
        $maxsort = $_REQUEST['maxsort'];
        $groupid = $_REQUEST['groupid'];
        $this->assign('menuid',$menuid);
        $this->assign('resType',$resType);
        $this->assign('maxsort',$maxsort);
        $this->assign('groupid',$groupid);
        $this->display();
    }

    public function delete(){
        $ids = $_POST['ids'];
        if(count($ids)!=1){
            $this->error('请选择需要删除的一则专题栏目!');
            die();
        }
        $model = D(CONTROLLER_NAME);
        $model->startTrans();
        $list = $model->where('id="'.$ids['0'].'"')->find();
        $TopicResource = D("TopicResource"); //二级栏目
        $map['id'] = array('in',$ids);
        $rMap['cid'] = array('in',$ids);
        $TRlist = $TopicResource->where($rMap)->find(); //对应栏目下的资源
        if(!empty($TRlist)){
            $this->error('该栏目下含有资源，请先删除该栏目下的资源');
            die();
        }
        //删除volueme表
        $delvolumeResult = true;
        if(!empty($list['size'])){
            $searchHidList = D("hotel_topic")->where('topic_id="'.$list['groupid'].'"')->field('hid')->select();
            if(!empty($searchHidList)){
                foreach ($searchHidList as $key => $value) {
                    $allresourceHidList[] = $value['hid'];
                }
                $vMap['hid'] = array('in',$allresourceHidList);
                $delvolumeResult = D("hotel_volume")->where($vMap)->setDec('topic_size',$list['size']);
            }
        }
        //删除allresource表
        $allresourceResult = true;
        if(!empty($list['icon'])){
            $delName_arr = explode("/", $list['icon']);
            $delName = $delName_arr[count($delName_arr)-1];
            $allresourceResult = D("hotel_allresource")->where('name="'.$delName.'"')->delete();
        }
        //删除二级栏目
        $result = $model->where($map)->delete();
        if($result!==false && $allresourceResult!==false && $delvolumeResult!==false){
            addlog('删除通用栏目二级栏目');
            $model->commit();
            if(!empty($list['icon'])){
                @unlink(FILE_UPLOAD_ROOTPATH.$list['icon']);
            }
            //生成资源xml文件
            if(!empty($allresourceHidList)){
                foreach ($allresourceHidList as $key => $value) {
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
    public function upload(){
        $callback = array();
        if (!empty($_FILES[$_REQUEST["name"]]["name"])) {
            $upload = new \Think\Upload(); //实例化上传类
            if ($_REQUEST['filetype']==1) {
                $upload->exts=array('mp4');
                $upload->maxSize=209715200;// 设置附件上传大小200M
            }else if ($_REQUEST['filetype']==2){
                $upload->exts=array('jpg','png','jpeg');
                $upload->maxSize=2097152;// 设置附件上传大小2M
            }else{
                $callback['status'] = 0;
                $callback['info']='未知错误！';
                echo json_encode($callback);
                exit;
            }
            $upload->rootPath='./Public/'; //保存根路径
            $upload->savePath='./upload/topic/'; // 设置附件上传目录
            //单文件上传
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
    public function resourceadd(){
        $model = D('topic_resource');
        $data['cid'] = $_REQUEST['menuid'];
        $data['gid'] = $_REQUEST['groupid'];
        $data['title'] = I('post.title','','strip_tags');
        $data['intro'] = I('post.intro','','strip_tags');
        $data['filepath'] = I('post.filepath','','strip_tags');
        $data['video_image'] = I('post.vimage','','strip_tags');
        if($_REQUEST['resType'] == 1){
            $data['type'] = 1;
            $data['video'] = $data['filepath'];
            $addName_arr = explode("/", $data['video']);
            $getName= $addName_arr[count($addName_arr)-1];
            //视频封面
            $vimage_arr = explode("/", $data['video_image']);
            $vimageName = $vimage_arr[count($vimage_arr)-1];
        }elseif($_REQUEST['resType'] == 2){
            $data['type'] = 2;
            $data['image'] = $data['filepath'];
            $addName_arr = explode("/", $data['image']);
            $getName= $addName_arr[count($addName_arr)-1];
        }
        $data['audit_status'] = 0;
        $data['status'] = 1;
        $data['sort'] = intval($_REQUEST['maxsort']) + 1;
        $data['audit_time'] = time();
        $size1 = I('post.size','','intval');
        $size2 = I('post.size2','','intval');
        $size = $size1 + $size2;
        $data['size'] = round($size/1024,3);

        $model->startTrans();
        $tmap['topic_id'] = $data['gid'];
        $vo = M("hotel_topic")->field("hid")->where($tmap)->select();
        $arr = array();
        foreach ($vo as $key => $vv) {
            array_push($arr, $vv['hid']);
        }

        $allresourceResult = true;
        $updatesize = true;
        if(!empty($arr)){
            $arrmap['hid'] = array('in',$arr);
            $updatesize = M("hotel_volume")->where($arrmap)->setInc('topic_size',$data['size']);
            $i = 0;
            foreach ($vo as $key => $value) {
                $addlist[$i]['hid'] = $value['hid'];
                $addlist[$i]['name'] = $getName;
                $addlist[$i]['type'] = $_REQUEST['resType'];
                $addlist[$i]['timeunix'] = time();
                $addlist[$i]['time'] = date("Y-m-d H:i:s");
                $addlist[$i]['web_upload_file'] = $data['filepath'];
                $i++;
                if(!empty($data['video_image'])){
                    $addlist[$i]['hid'] = $value['hid'];
                    $addlist[$i]['name'] = $vimageName;
                    $addlist[$i]['type'] = 1;
                    $addlist[$i]['timeunix'] = time();
                    $addlist[$i]['time'] = date("Y-m-d H:i:s");
                    $addlist[$i]['web_upload_file'] = $data['video_image'];
                    $i++;
                }
            }
            $allresourceResult = D("hotel_allresource")->addAll($addlist);
        }
        $result = $model->add($data);
        if ($result !== false && $updatesize !== false && $allresourceResult!==false) {
            $model->commit();
            //生成资源xml文件
            if(!empty($arr)){
                foreach ($arr as $key => $value) {
                    $xmlFilepath = FILE_UPLOAD_ROOTPATH.'/upload/resourceXml/'.$value.'.txt';
                    $this->fileputXml(D("hotel_allresource"),$value,$xmlFilepath);
                }
            }
            $this->success("操作成功",U('resource').'?ids='.$_REQUEST['menuid']);
        }else{
            $model->rollback();
            $this->error("操作失败",U('resource').'?ids='.$_REQUEST['menuid']);
        }
    }
    public function resourceedit(){
        $menuid = $_REQUEST['ids'];
        if(count($menuid)!=1){
            $this->error("只能修改一条记录");
        }
        $map['id'] = $menuid['0'];
        $model = D('topic_resource');
        $vo = $model->where($map)->find();
        if($vo['type'] == '1'){
            $filepath = $vo['video'];
        }elseif($vo['type'] == '2'){
            $filepath = $vo['image'];
        }
        $video_image = $vo['video_image'];
        if(!empty($filepath)){
            $vo['size'] = round(filesize(FILE_UPLOAD_ROOTPATH.$filepath)/1024);
        }else{
            $vo['size'] = 0;
        }
         if(!empty($video_image)){
            $vo['size2'] = round(filesize(FILE_UPLOAD_ROOTPATH.$video_image)/1024);
        }else{
            $vo['size2'] = 0;
        }

        $this->assign('list',$vo);
        $this->display();
    }
    public function resourceupdate(){
        $model = D('topic_resource');
        $map['id'] = $_REQUEST['ids'];
        $data['cid'] = $_REQUEST['cid'];
        $data['gid'] = $_REQUEST['gid'];
        $data['title'] = I('post.title','','strip_tags');
        $data['intro'] = I('post.intro','','strip_tags');
        $data['audit_status'] = 0;
        $data['filepath'] = I('post.filepath','','strip_tags');
        $data['video_image'] = I('post.vimage','','strip_tags');
        $size = I('post.size','','strip_tags');
        $size2 = I('post.size2','','strip_tags');
        $data['size'] = round(($size+$size2)/1024,3);
        $vo = $model->getById($_REQUEST['ids']);
        $getVideoName = "";
        $voVideoName = "";
        if($_REQUEST['type'] == 1){
            $data['video'] = $data['filepath'];
            $editName_arr = explode("/", $data['video']);
            $getName = $editName_arr[count($editName_arr)-1];
            $voName_arr = explode("/", $vo['video']);
            $voName = $voName_arr[count($voName_arr)-1];
            //视频封面
            if(!empty($vo['video_image'])){
                $voVideoName_arr = explode("/", $vo['video_image']);
                $voVideoName = $voVideoName_arr[count($voVideoName_arr)-1];
            }
            if(!empty($data['video_image'])){
                $getVideoName_arr = explode("/", $data['video_image']);
                $getVideoName = $getVideoName_arr[count($getVideoName_arr)-1];
            }
        }elseif($_REQUEST['type'] == 2){
            $data['image'] = $data['filepath'];
            $editName_arr = explode("/", $data['image']);
            $getName = $editName_arr[count($editName_arr)-1];
            $voName_arr = explode("/", $vo['image']);
            $voName = $voName_arr[count($voName_arr)-1];
        }

        $model->startTrans();
        $updatesize = true;
        if($_REQUEST['type'] == 1){
            $passName = $vo['video'];    
        }elseif($_REQUEST['type'] == 2){
            $passName = $vo['image'];
        }
        //修改容量表
        $vo['size'] = empty($vo['size'])?0:$vo['size'];
        if($passName != $data['filepath'] || $vo['video_image'] != $data['video_image']){
            $changesize = $data['size']-$vo['size'];
            $tmap['topic_id'] = $vo['gid'];
            $hidlist = M("hotel_topic")->field('hid')->where($tmap)->select();
            if(!empty($hidlist)){
                $arr = array();
                foreach ($hidlist as $key => $vv) {
                    array_push($arr, $vv['hid']);
                }
                $arrmap['hid'] = array('in',$arr);
                $updatesize = M("hotel_volume")->where($arrmap)->setInc('topic_size',$changesize);
            }
        }
        $result = $model->data($data)->where($map)->save();
        //修改allresource表
        $allresourceResult = true;
        $delAllresourceResult = true;
        $addAllresourceResult = true;
        $allresourceHidList = array();
        
        if($passName != $data['filepath']){
            $searchHidList = D("hotel_topic")->where('topic_id="'.$data['gid'].'"')->field('hid')->select();
            $delAllresourceResult = D("hotel_allresource")->where('name="'.$voName.'"')->delete();

            if($searchHidList){
                $i = 0;
                foreach ($searchHidList as $key => $value) {
                    $allresourceHidList[] = $value['hid'];
                    $addlist[$i]['hid'] = $value['hid'];
                    $addlist[$i]['name'] = $getName;
                    $addlist[$i]['type'] = $_REQUEST['type'];
                    $addlist[$i]['timeunix'] = time();
                    $addlist[$i]['time'] = date("Y-m-d H:i:s");
                    $addlist[$i]['web_upload_file'] = $data['filepath'];
                    $i++;
                }
                $addAllresourceResult = D("hotel_allresource")->addAll($addlist);
            }
            if($addAllresourceResult===false || $delAllresourceResult===false){
                $allresourceResult = false;
            }

        }
        if($vo['video_image'] != $data['video_image']){
            if(!empty($vo['video_image'])){
                $videosearchHidList = D("hotel_topic")->where('topic_id="'.$data['gid'].'"')->field('hid')->select();
                $delAllresourceResult = D("hotel_allresource")->where('name="'.$voVideoName.'"')->delete();
            }else{
                $delAllresourceResult = true;
                $videosearchHidList = array();
            }

            if(!empty($videosearchHidList)){
                $i = 0;
                foreach ($videosearchHidList as $key => $value) {
                    $allresourceHidList[] = $value['hid'];
                    $addlist[$i]['hid'] = $value['hid'];
                    $addlist[$i]['name'] = $getVideoName;
                    $addlist[$i]['type'] = $_REQUEST['type'];
                    $addlist[$i]['timeunix'] = time();
                    $addlist[$i]['time'] = date("Y-m-d H:i:s");
                    $addlist[$i]['web_upload_file'] = $data['video_image'];
                    $i++;
                }
                $addAllresourceResult = D("hotel_allresource")->addAll($addlist);
            }else{
                $addAllresourceResult = true;
            }
            if($addAllresourceResult===false || $delAllresourceResult===false){
                $allresourceResult = false;
            }

        }
        
        if($result !== false && $updatesize !== false && $allresourceResult !==false){
            if($_REQUEST['type'] == 1){
                if($_REQUEST['filepath'] != $vo['video']){
                    @unlink(FILE_UPLOAD_ROOTPATH.$vo['video']);
                }
            }elseif($_REQUEST['type'] == 2){
                if($_REQUEST['filepath'] != $vo['image']){
                    @unlink(FILE_UPLOAD_ROOTPATH.$vo['image']);
                }
            }
            if($data['video_image'] != $vo['video_image']){
                @unlink(FILE_UPLOAD_ROOTPATH.$vo['video_image']);
            }
            $model->commit();
            //生成资源xml文件
            if(!empty($allresourceHidList)){
                $allresourceHidList = array_unique($allresourceHidList);
                foreach ($allresourceHidList as $key => $value) {
                    $xmlFilepath = FILE_UPLOAD_ROOTPATH.'/upload/resourceXml/'.$value.'.txt';
                    $this->fileputXml(D("hotel_allresource"),$value,$xmlFilepath);
                }
            }
            $this->success("修改成功",U('resource').'?ids='.$_REQUEST['cid']);
        }else{
            $model->rollback();
            $this->error("修改失败");
        }
    }
    public function resourcedelete(){
        $model = M('topic_resource');
        $HotelTopic = D("hotel_topic");
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if(count($ids)!=1){
            $this->error('请选择一条记录进行操作');
            die();
        }
        $map['id'] = $ids['0'];
        $list = $model->field('cid,gid,video,image,size,type,video_image')->where($map)->find();
        $cid = $list['cid'];
        $gid = $list['gid'];
        $tmap['topic_id'] = $gid;//一个二级栏目下的资源gid相同
        $hidlist = $HotelTopic->where($tmap)->field('hid')->select();
        if(!empty($cid)){
            $url = U('resource').'?ids='.$cid;
        }else{
            $url = U('index');
        }

        $model->startTrans();
        //更改volume表
        $vresult = true;
        if(!empty($hidlist)){
            foreach ($hidlist as $key => $value) {
                $hidlist_arr[] = $value['hid'];
            }
            $vMap['hid'] = array('in',$hidlist_arr);
            $vresult = D("hotel_volume")->where($vMap)->setDec('topic_size',$list['size']);
        }
        //更改allresource表
        if($list['type'] == 2){
            $filepath = $list['image'];
            
        }elseif ($list['type'] == 1) {
            $filepath = $list['video'];
        }
        $getName_arr = explode("/", $filepath);
        $getName = $getName_arr[count($getName_arr)-1];

        if(!empty($list['video_image'])){
            $getVideoName_arr = explode("/", $list['video_image']);
            $getVideoName = $getVideoName_arr[count($getVideoName_arr)-1];
        }
        $allresourceResult = true;
        if($getName){
            $allresourceResult = D("hotel_allresource")->where('name="'.$getName.'"')->delete();
        }
        if($getVideoName){
            $allresourceResult = D("hotel_allresource")->where('name="'.$getVideoName.'"')->delete();
        }

        //删除资源表
        $result = $model->where($map)->delete();

        if($result!==false && $allresourceResult!==false && $vresult!==false){
            $model->commit();
            @unlink(FILE_UPLOAD_ROOTPATH.$filepath);
            if (!empty($list['video_image'])) {
                @unlink(FILE_UPLOAD_ROOTPATH.$list['video_image']);
            }
            addlog('删除topic_resource表数据');
            $this->success('恭喜，删除成功！',$url);
        }else{
            $model->rollback();
            $this->error('删除失败，参数错误！',$url);
        }
    }
    public function resourceshow(){
        $model = M('topic_resource');
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if($ids){
            if(is_array($ids)){
                $ids = implode(',',$ids);
                $map['id']  = array('in',$ids);
            }else{
                $map = 'id='.$ids;
            }
            $result = $model->where($map)->setField("status",1);
            if($result){
                addlog('启用topic_resource表数据，数据ID：'.$ids);
                $this->success('恭喜，显示成功！');
            }else{
                $this->error('显示失败，参数错误！');
            }
        }else{
            $this->error('参数错误！');
        }
    }
    public function resourcehidden(){
        $model = M('topic_resource');
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if($ids){
            if(is_array($ids)){
                $ids = implode(',',$ids);
                $map['id']  = array('in',$ids);
            }else{
                $map = 'id='.$ids;
            }
            $result = $model->where($map)->setField("status",0);
            if($result){
                addlog('隐藏topic_resource表数据，数据ID：'.$ids);
                $this->success('恭喜，隐藏成功！');
            }else{
                $this->error('隐藏失败，参数错误！');
            }
        }else{
            $this->error('参数错误！');
        }
    }
    public function setsort(){
        $model = D('topic_resource');
        $menuid = I('post.menuid','','intval');
        $sort_now = I('post.sort','','intval');
        $ids = $_REQUEST['sortid'];
        if(!$ids){
            $this->error("请勾选需要更改排序的栏目资源");
        }
        foreach ($ids as $key => $value) {
            $result = $model->where('id='.$value)->setField('sort',$sort_now[$key]);
            if($result === false){
                $model->rollback();
                $this->error("更新失败",U("resource?ids=".$menuid));
            }
        }
        $model->commit();
        $this->success("更新成功",U("resource?ids=".$menuid));
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