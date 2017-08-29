<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 后台首页控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class IndexController extends ComController {
    //操作日志
    public function index(){
        $hotelModel = D("hotel");
        $deviceModel = D("Device");
        $hid_condition = $this->isHotelMenber();
        if($hid_condition){
            $hotel_count = count($hid_condition[1]);
            $map['hid'] = $hid_condition;
        }else{
            $hotel_count = $hotelModel->count();
        }
        $device_count = $deviceModel->where($map)->count();
        $map['online'] = 1;
        $device_online_count = $deviceModel->where($map)->count();
        $this->assign('hotelCount',$hotel_count);
        $this->assign('deviceCount',$device_count);
        $this->assign('deviceOnlineCount',$device_online_count);
        $this->assign('percer',sprintf("%.2f",($device_online_count/$device_count)*100));
        $this -> display();
    }
    //网站设置
    public function setting(){
        $this -> display();
    }
    //网站设置保存
    public function update(){
        $data = $_POST;
        $data['system_name'] = isset($_POST['system_name'])?strip_tags($_POST['system_name']):'';
        $data['company'] = isset($_POST['company'])?strip_tags($_POST['company']):'';
        $data['copyright']= isset($_POST['copyright'])?strip_tags($_POST['copyright']):'';
        $Model = M('setting');
        foreach($data as $k=>$v){
            $Model->data(array('v'=>$v))->where("k='{$k}'")->save();
        }
        addlog('修改网站配置。');
        $this->success('恭喜，网站配置成功！');
    }
    //个人资料
    public function profile(){
        $member = M('member')->where('uid='.$this->USER['uid'])->find();
        $this->assign('member',$member);
        $this -> display();
    }
    //资料保存
    public function profileUpdate(){
        $uid = $this->USER['uid'];
        $password = isset($_POST['password'])?trim($_POST['password']):false;
        if($password) {
            $data['password'] = password($password);
        }
        $head = I('post.head','','strip_tags');
        if($head<>'') {
            $data['head'] = $head;
        }
        $data['sex'] = isset($_POST['sex'])?intval($_POST['sex']):0;
        $data['birthday'] = isset($_POST['birthday'])?strtotime($_POST['birthday']):0;
        $data['phone'] = isset($_POST['phone'])?trim($_POST['phone']):'';
        $data['qq'] = isset($_POST['qq'])?trim($_POST['qq']):'';
        $data['email'] = isset($_POST['email'])?trim($_POST['email']):'';
        
        $Model = M('member');
        $Model->data($data)->where("uid=$uid")->save();
        addlog('修改个人资料');
        $this->success('操作成功！');
    }
    //后台登出
    public function logout(){
        $sessionID = session_id();
        session($sessionID,null);
        $url = U("Login/index");
        header("Location: {$url}");
        exit(0);
    }
}