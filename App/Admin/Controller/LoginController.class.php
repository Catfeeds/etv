<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 后台登录控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Common\Controller\BaseController;
use Think\Auth;
class LoginController extends BaseController {
    public function index(){
        $sessionID = session_id();
        $data = session($sessionID);
        if((time()-$data['time'])<60*60){
            $this -> error('您已经登录,正在跳转到主页',U("Index/index"));
        }
        $this -> display();
    }
    public function checklogin(){
        $code = trim(I('post.verify'));
        $verify = new \Think\Verify();
        if(!$verify->check($code,'login')){
            $data['status'] = 10000;
            $data['data'] = "验证码错误";
            echo json_encode($data);
            die();
        }
        $username = I('post.user','','strip_tags');
        $password = I('post.password','','strip_tags');
        $map['user'] = $username;
        $map['password'] = password($password);
        $map['status'] = 1;
        $user = D("member")->field('uid,user')->where($map)->find();
        if($user['uid']){
            $hotel_user = D("hotel_user")->field('hid')->where(array('user_id'=>$user['uid']))->select();
            if (!empty($hotel_user)) {
                foreach ($hotel_user as $key => $value) {
                    $hids[] = $value['hid'];
                }
                $user['hid'] = implode(',', $hids);
            }else{
                $user['hid'] = '';
            }

            $sessionID = session_id();
            if($remember){  //记住我
                $user['time'] = time()+60*60*24*30; //一个月失效
                session($sessionID,$user);
            }else{
                $user['time'] = time();
                session($sessionID,$user);
            }

            addlog('登录成功。');
            $url='Index/index';
            $data['status'] =200;
            echo json_encode($data);
            die();
        }else{
            addlog('登录失败。',$username);
            $data['status'] = 10000;
            $data['data'] = "账号或密码错误";
            echo json_encode($data);
            die();
        }
        
    }
    // public function login(){
    //     $verify = isset($_POST['verify'])?trim($_POST['verify']):'';
    //     if (!$this->check_verify($verify,'login')) {
    //         $this -> error('验证码错误！',U("Login/index"));
    //     }
    //     $username = isset($_POST['user'])?trim($_POST['user']):'';
    //     $password = isset($_POST['password'])?password(trim($_POST['password'])):'';
    //     $remember = I('post.remember');
    //     if ($username=='') {
    //         $this -> error('用户名不能为空！',U("Login/index"));
    //     } elseif ($password=='') {
    //         $this -> error('密码必须！',U("Login/index"));
    //     }
    //     $model = M("Member");
    //     $user = $model ->field('uid,user')-> where(array('user'=>$username,'password'=>$password,'status'=>1)) -> find();
    //     $hotel_user_model = M("HotelUser");
    //     $hotel_user = $hotel_user_model->field('hid')->where(array('user_id'=>$user['uid']))->select();
    //     if (!empty($hotel_user)) {
    //         foreach ($hotel_user as $key => $value) {
    //             $hids[] = $value['hid'];
    //         }
    //         $user['hid'] = implode(',', $hids);
    //     }else{
    //         $user['hid'] = '';
    //     }

    //     $sessionID = session_id();

    //     if($user['uid']) {
    //         if($remember){  //记住我
    //             $user['time'] = time()+60*60*24*30; //一个月失效
    //             session($sessionID,$user);
    //         }else{
    //             $user['time'] = time();
    //             session($sessionID,$user);
    //         }
    //         addlog('登录成功。');
    //         $url=U('Index/index');
    //         header("Location: $url");
            
    //     }else{
    //         addlog('登录失败。',$username);
    //         $this -> error('登录失败，请重试！',U("Login/index"));
    //     }
    // }
    public function verify() {
        $config = array(
		'fontSize' => 14, // 验证码字体大小
		'length' => 4, // 验证码位数
		'useNoise' => false, // 关闭验证码杂点
		'imageW'=>100,
		'imageH'=>30,
        );
        $verify = new \Think\Verify($config);
        $verify->codeSet = '0123456789';
        $verify -> entry('login');
    }
    function check_verify($code, $id = '') {
        $verify = new \Think\Verify();
        return $verify -> check($code, $id);
    }
}