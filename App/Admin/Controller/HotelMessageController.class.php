<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 消息通知控制器--酒店
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
class HotelMessageController extends ComController {
    //查询
    public function _map(){
        $map = array();
        if (!empty($_GET['title'])) {
            $map['title'] = array("LIKE","%{$_GET['title']}%");
        }
        return $map;
    }
    public function index(){
        $model = M("HotelMessage");
        $map = $this->_map();
        $list = $this->_list($model,$map);
        $this -> assign("list",$list);
        if (empty($list[0]['hidlist'])) {
            $list[0]['hidlist']='-';
        }else if($list[0]['hidlist']=="all"){
            $list[0]['hidlist']="全部酒店";
        }else{
            $ids = trim($list[0]['hidlist']);
            $idsArr = explode(",", $ids);
            $hotelname='';
            $Hotel = D("Hotel");
            foreach ($idsArr as $value) {
                $vo=array();
                $vo=$Hotel->getByHid($value);
                $hotelname = $hotelname.','.$vo['hotelname'];
            }
            $list[0]['hidlist']=trim($hotelname,',');
        }
        $this -> assign("vo",$list[0]);
        $this -> display();
    }
    public function add(){
        $this->_assign_hour_minute();
        $regionList = $this->_hotel_list();
        $this->assign('menuTree', $regionList);
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
        $vo = $model->getById($ids['0']);
        //时间
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

        $regionList = $this->_hotel_list();
        if(trim($vo['hidlist']) == 'all'){
            $vo['hotelname']="全部酒店";
            foreach ($regionList as $key => $value) {
                if (!empty($value['sub'])) {
                    $value['sub']['0']['isselect'] = 1;
                }
                $regionList[$key] = $value;
            }
        }else{
            $idsArr = explode(',', trim($vo['hidlist']));
            //查询已选酒店
            foreach ($regionList as $key => $value) {
                if(!empty($value['sub'])){
                    if(in_array($value['sub']['0']['hid'], $idsArr)){
                        $value['sub']['0']['isselect'] = 1;
                    }else{
                        $value['sub']['0']['isselect'] = 0;
                    }
                }
                $regionList[$key] = $value;
            }
            //酒店名称
            $hotelname='';
            $Hotel = D("Hotel");
            foreach ($idsArr as $value) {
                $hvo=array();
                $hvo=$Hotel->getByHid($value);
                $hotelname = $hotelname.','.$hvo['hotelname'];
            }
            $vo['hotelname']=trim($hotelname,',');
        }
        
        $this->assign('senddate',$senddate);
        $this->assign('sendhour',$sendhour);
        $this->assign('sendminute',$sendminute);
        $this->assign('enddate',$enddate);
        $this->assign('endhour',$endhour);
        $this->assign('endminute',$endminute);
        $this->assign('vo',$vo);
        $this->assign('menuTree', $regionList);
        $this->display();
    }
    public function detail(){
        $model = M("HotelMessage");
        $list = $model->getById($_REQUEST['id']);
        if (empty($list['hidlist'])) {
            $list['hidlist']='-';
        }else if($list['hidlist']=="all"){
            $list['hidlist']="全部酒店";
        }else{
            $ids = trim($list['hidlist']);
            $idsArr = explode(",", $ids);
            $hotelname='';
            $Hotel = D("Hotel");
            foreach ($idsArr as $value) {
                $vo=array();
                $vo=$Hotel->getByHid($value);
                $hotelname = $hotelname.','.$vo['hotelname'];
            }
            $list['hidlist']=trim($hotelname,',');
        }
        $list['starttime'] =date("Y-m-d H:i:s",$list['starttime']);
        $list['endtime'] =date("Y-m-d H:i:s",$list['endtime']);
        echo json_encode($list);
    }
    //保存
    public function update(){
        $model = M(CONTROLLER_NAME);
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
        $data['sendmode'] = empty(I('post.sendmode'))?0:I('post.sendmode');
        $data['starttime'] = $starttime;
        $data['endtime'] = $endtime;
        $data['hidlist'] = I('post.hidlist','','strip_tags');
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
}