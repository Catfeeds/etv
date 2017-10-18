<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 审核管理--专题审核
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class PushTopicResourceController extends ComController {
        public function _map(){
        $map = array();
        $resMap=array();
        if (!empty($_REQUEST['keyword'])) {
            $title = trim(I('get.keyword','','strip_tags'));
            $map['title']=array("like","%".$title."%");
        }
        if (is_numeric($_REQUEST['status'])) { 
            $status = I('get.status','','strip_tags');
            $map['audit_status']= $status;
            $this->assign('status',$status);
        }else{
            $map['audit_status'] = array('in',array(2,3,4));
        }
        if (!empty($_REQUEST['groupid'])) {
            $map_gid['groupid'] = $_REQUEST['groupid'];
            $this->assign("groupId",$_REQUEST['groupid']);
            $TopicCategory = D("TopicCategory");
            $list=$TopicCategory->where($map_gid)->select();
            if(!empty($list)){
                foreach ($list as $key => $n) {
                    $Ids[] = $n['id'];
                }
                $map['cid'] = array("in",$Ids);
            }
        }else{
            $this->assign("groupId",'');
        }
        return $map;
    }
    public function index(){
        $model = D("TopicResource");
        $map = $this->_map();
        if($map === false){
            $list = null;
        }else{
            $list = $this->_list($model, $map, 10 ,"audit_status,cid asc,sort asc");
        }
        $this -> assign("list",$list);
        $this->display();
    }
    public function _before_index(){
        $this->_assign_list();
    }
    public function _assign_list(){
        $TopicGroup = D ('TopicGroup');
        $groupList = $TopicGroup->field ('id,title')->order('id asc')->select();
        $this->assign('groupList', $groupList);
    }
    public function push(){
        $this->changeAuditStatus(4);
    }
    public function unpush(){
        $this->changeAuditStatus(3);
    }
    public function changeAuditStatus($audit_status){
        if(empty($_REQUEST['ids'])){
            $this->error("至少选择一条记录");
            die();
        }
        $idsstr = implode(',', $_REQUEST['ids']);
        $model = D("TopicResource");
        $map['id'] = array("in",$_REQUEST['ids']);
 
        $data = array();
        $data['audit_status'] = $audit_status;
        $data['audit_time'] = time();
        if($audit_status == 4){
            $data['status'] = 1;
            $result = $model->where($map)->setField($data);
        }else{
            $data = array('audit_time'=>time(),'audit_status'=>$audit_status);
            $result = $model->where($map)->setField($data);
        }
        if($result === false){
            $this->error("修改状态失败");
        }else{

            //更新json文件
            $groupList = D("topic_resource")->where($map)->field('gid')->group('gid')->select();
            foreach ($groupList as $key => $value) {
                $groupid[] = $value['gid'];
            }
            $groupid_str = implode(",", $groupid);
            $sql = "SELECT hid,topic_id FROM zxt_hotel_topic WHERE hid IN(SELECT hid FROM zxt_hotel_topic WHERE topic_id in (".$groupid_str."))";
            $hid_arr = D("hotel_topic")->query($sql);
            $this->updatejson_resource($hid_arr);

            if($audit_status == 3){
               addAuditlog("专题发布未通过,数据ID为：".$idsstr,$resouce_type=12,$type=2);
            }elseif($audit_status == 4){
               addAuditlog("专题发布通过,数据ID为：".$idsstr,$resouce_type=12,$type=2);
            }
            $this->success("修改状态成功");
        }
    }

    /**
     * [通用栏目资源json更新]
     * @param  [array] $hid_arr [酒店hid对应栏目topic_id组合的集合]
     */
    private function updatejson_resource($hid_arr){
        $hid_topic_id = array();//存储hid对应的topic
        foreach ($hid_arr as $key => $value) {
            $hid_topic_id[$value['hid']][] = $value['topic_id'];
        }
        foreach ($hid_topic_id as $key => $value) {
            $thehid = $key;//当前hid
            $map['gid'] = array('in',$value);
            $map['status'] = 1;
            $map['audit_status'] = 4;
            $list = D("topic_resource")->where($map)->field('cid,gid,title,type,video,image,video_image,intro,sort')->order('sort')->select();
            $plist = array();
            if(!empty($list)){
                foreach ($list as $key => $value) {
                    $plist[$value['cid']][] = $value;
                }
                $jsondata = json_encode($plist);
            }else{
                $jsondata = '';
            }
            if (!is_dir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$thehid)) {
                mkdir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$thehid);
            }
            $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$thehid.'/topicresource.json';
            file_put_contents($filename, $jsondata);
        }
    }
}