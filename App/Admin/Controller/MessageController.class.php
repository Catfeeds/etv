<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 消息通知控制器--房间
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
class MessageController extends ComController {
    //查询
    public function _map(){

        if (!empty($_GET['title'])) {
            $map['title'] = array("LIKE","%{$_GET['title']}%");
        }
        return $map;
    }
    public function index(){

        $map = $this->_map();

        //权限判断
        $hid_condition = $this->isHotelMenber();

        if($hid_condition){
            $map['hid'] = $hid_condition;
        }

        $model = M("message");
        $list = $this->_list($model,$map);
        $this -> assign("list",$list);
        $this -> assign("vo",$list[0]);
        $this -> display();
    }
    public function add(){
        $this->_assign_hour_minute();
        $this->isHotelMenber();
        $category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
        $category = $tree->get_tree(0,$str,0);
        $this->assign('pHotel',$category);
        $this->display();
    }
    public function _before_edit(){
        $this->_assign_hour_minute();
    }
    public function edit(){
        $ids = $_REQUEST['ids'];
        if(count($ids)!=1){
            $this->error('只能修改一条记录');
            die();
        }
        $model = D(CONTROLLER_NAME);
        $Device = D("Device");
        $vo = $model->getById($ids['0']);
        $senddate = $sendhour = $sendminute = '';
        if(!empty($vo['starttime'])){
            $senddate = date("Y-m-d",$vo['starttime']);
            $sendhour = date("H",$vo['starttime']);
            $sendminute = date("i",$vo['starttime']);
        }
        $date = $hour = $minute = '';
        if(!empty($vo['endtime'])){
            $enddate = date("Y-m-d",$vo['endtime']);
            $endhour = date("H",$vo['endtime']);
            $endminute = date("i",$vo['endtime']);
        }

        $map['hid']=array("eq",$vo['hid']);
        $roomList = $Device->field('id,room,mac')->where($map)->order ('room asc')->select();
        $macarr = explode(',', $vo['maclist']);
        foreach ($roomList as $key => $value) {
            if(in_array($value['mac'], $macarr)){
                $roomList[$key]['isselect'] = 1;
            }else{
                $roomList[$key]['isselect'] = 0;
            }
        }
        $this->assign('roomList',$roomList);
        $this->assign('senddate',$senddate);
        $this->assign('sendhour',$sendhour);
        $this->assign('sendminute',$sendminute);
        $this->assign('enddate',$enddate);
        $this->assign('endhour',$endhour);
        $this->assign('endminute',$endminute);
        $this->assign('vo',$vo);
        $this->display();
    }
    public function detail(){
        $model = M("message");
        $list = $model->getById($_REQUEST['id']);
        $list['starttime'] =date("Y-m-d H:i:s",$list['starttime']);
        $list['endtime'] =date("Y-m-d H:i:s",$list['endtime']);
        echo json_encode($list);
    }
    //保存
    public function update(){
        $model = M(CONTROLLER_NAME);
        if(empty(I('post.hid'))){
            $this->error('请选择消息推送的酒店');
            die();
        }
        $data['hid'] = I('post.hid');
        $data['id'] = I('post.id','','intval');
        $data['title'] = I('post.title','','strip_tags');
        $data['content'] = I('post.content','','strip_tags');
        $senddate = $_POST['senddate']." ".$_POST['sendhour'].":".$_POST['sendminute'];
        $starttime = strtotime($senddate);
        $timestr = $_POST['date']." ".$_POST['hour'].":".$_POST['minute'];
        $endtime = strtotime($timestr);
        if ($endtime<=$starttime) {
            $this->error("时间填写错误：推送时间要小于结束时间！");
        }
        $data['sendmode'] = I('post.sendmode');
        $data['starttime'] = $starttime;
        $data['endtime'] = $endtime;
        $data['maclist'] = I('post.maclist','0','strip_tags');
        $data['roomlist'] = I('post.room');
        $data['status'] = 0;
        if($data['id']){
            $model->data($data)->where('id='.$data['id'])->save();
            addlog('修改消息通知，ID：'.$data['id']);
        }else{
            $data['create_time']=time();
            $aid = $model->data($data)->add();
            addlog('添加消息通知，ID：'.$aid);
        }
        $this->success('恭喜，操作成功！',U('index'));
    }

    public function _assign_hour_minute(){
        $hour = array();
        for ($i = 0; $i < 24; $i++) {
            if ($i<10) {
                $hour[] = "0".$i;
            }else{
                $hour[] = $i;
            }
        }
        $minute = array();
        for ($j = 0; $j < 60; $j++) {
            if ($j<10) {
                $minute[] = "0".$j;
            }else{
                $minute[] = $j;
            }
        }
        $this->assign("hour",$hour);
        $this->assign("minute",$minute);
    }
    public function findDeviceByHid(){
        $Device = D("Device");
        $hid = I('post.hid');
        if(trim($hid) == ''){
            echo false;
            die();
        }
        $map['hid']=array("eq",$hid);
        $roomList = $Device->field('id,room,mac,room_remark,firmware_version')->where($map)->order ('room asc')->select();
        echo json_encode($roomList);
    }
}