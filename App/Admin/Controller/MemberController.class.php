<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 用户控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class MemberController extends ComController {
    public function index(){
        $p = isset($_GET['p'])?intval($_GET['p']):'1';
        $field = isset($_GET['field'])?$_GET['field']:'';
        $keyword = isset($_GET['keyword'])?htmlentities($_GET['keyword']):'';
        $order = isset($_GET['order'])?$_GET['order']:'DESC';
        $where = '';
        $prefix = C('DB_PREFIX');
        if($order == 'asc'){
            $order = "{$prefix}member.t asc";
        }elseif(($order == 'desc')){
            $order = "{$prefix}member.t desc";
        }else{
            $order = "{$prefix}member.uid desc";
        }
        if($keyword <>''){
            if($field=='user'){
                $where = "{$prefix}member.user LIKE '%$keyword%'";
            }
            if($field=='phone'){
                $where = "{$prefix}member.phone LIKE '%$keyword%'";
            }
            if($field=='qq'){
                $where = "{$prefix}member.qq LIKE '%$keyword%'";
            }
            if($field=='email'){
                $where = "{$prefix}member.email LIKE '%$keyword%'";
            }
        }
        $user = M('member');
        $pagesize = 10;#每页数量
        $offset = $pagesize*($p-1);//计算记录偏移量
        $count = $user->field("{$prefix}member.*,{$prefix}auth_group.id as gid,{$prefix}auth_group.title")->order($order)->join("{$prefix}auth_group_access ON {$prefix}member.uid = {$prefix}auth_group_access.uid")->join("{$prefix}auth_group ON {$prefix}auth_group.id = {$prefix}auth_group_access.group_id")->where($where)->limit($offset.','.$pagesize)->count();
        $list = $user->field("{$prefix}member.*,{$prefix}auth_group.id as gid,{$prefix}auth_group.title")->order($order)->join("{$prefix}auth_group_access ON {$prefix}member.uid = {$prefix}auth_group_access.uid")->join("{$prefix}auth_group ON {$prefix}auth_group.id = {$prefix}auth_group_access.group_id")->where($where)->limit($offset.','.$pagesize)->select();

        $page = getpage($count,$pagesize);
        $this->assign('page', $page->show());
        $this->assign('list',$list);
        $this->display();
    }
    public function add(){
        $usergroup = M('auth_group')->field('id,title')->select();
        $this->assign('usergroup',$usergroup);
        $this -> display();
    }
    public function edit(){
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if(count($ids)>1){
            $this->error('参数错误，每次只能修改一条内容！');
        }
        if($ids){
            $uid=$ids[0];
            $prefix = C('DB_PREFIX');
            $user = M('member');
            $member  = $user->field("{$prefix}member.*,{$prefix}auth_group_access.group_id")->join("{$prefix}auth_group_access ON {$prefix}member.uid = {$prefix}auth_group_access.uid")->where("{$prefix}member.uid=$uid")->find();
        }else{
            $this->error('参数错误！');
        }
        $usergroup = M('auth_group')->field('id,title')->select();
        $this->assign('usergroup',$usergroup);
        $this->assign('member',$member);
        $this -> display();
    }
    public function update(){
        $uid = isset($_POST['uid'])?intval($_POST['uid']):false;
        $user = isset($_POST['user'])?htmlspecialchars($_POST['user'], ENT_QUOTES):'';
        $group_id = isset($_POST['group_id'])?intval($_POST['group_id']):0;
        if(!$group_id){
            $this->error('请选择用户组！');
        }
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
        if(!$uid){
            if($user==''){
                $this->error('用户名称不能为空！');
            }
            if(!$password){
                $this->error('用户密码不能为空！');
            }
            if(M('member')->where("user='$user}'")->count()){
                $this->error('用户名已被占用！');
            }
            $data['user'] = $user;
            $data['t'] = time();
            $uid = M('member')->data($data)->add();
            M('auth_group_access')->data(array('group_id'=>$group_id,'uid'=>$uid))->add();
            addlog('新增用户，用户UID：'.$uid);
        }else{
            M('auth_group_access')->data(array('group_id'=>$group_id))->where("uid=$uid")->save();
            M('member')->data($data)->where("uid=$uid")->save();
            addlog('编辑用户信息，用户UID：'.$uid);
        }
        $this->success('恭喜，操作成功！',U('index'));
    }
    public function unlock(){
        $model = M(CONTROLLER_NAME);
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if($ids){
            if(is_array($ids)){
                $ids = implode(',',$ids);
                $map['uid']  = array('in',$ids);
            }else{
                $map = 'uid='.$ids;
            }
            $result = $model->where($map)->setField("status",1);
            if($result){
                addlog('启用用户表数据，数据UID：'.$ids);
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
                $map['uid']  = array('in',$ids);
            }else{
                $map = 'uid='.$ids;
            }
            $result = $model->where($map)->setField("status",0);
            if($result){
                addlog('禁用用户表数据，数据UID：'.$ids);
                $this->success('恭喜，禁用成功！');
            }else{
                $this->error('禁用失败，参数错误！');
            }
        }else{
            $this->error('参数错误！');
        }
    }
    //删除 批量删除
    public function delete(){
        $model = D(CONTROLLER_NAME);
        $model_h_u = D('hotel_user'); 
        $prefix = C('DB_PREFIX');
        $ids = $_REQUEST['ids'];
        $map_member = array('in',$ids);
        foreach ($ids as $key => $value) {
            $list = $model->where("uid=$value")->field("{$prefix}member.uid,{$prefix}member.user,{$prefix}hotel_user.huid")->join("left join {$prefix}hotel_user ON {$prefix}member.uid={$prefix}hotel_user.user_id")->select();
            foreach ($list as $key => $l) {
                $model->startTrans();
                if(!empty($l['huid'])){
                    $result_m = $model->where("uid=$value")->delete();
                    $result_h_u = $model_h_u->where("user_id=$value")->delete();
                    if(!$result_m or !$result_h_u){
                        $model->rollback();
                        $message = $l['user'].'用户删除失败';
                        $this->error($message);
                        die();
                    }else{
                        $model->commit();
                    }
                }else{
                    $result_m = $model->where("uid=$value")->delete();
                    if(!$result_m){
                        $model->rollback();
                        $message = $l['user'].'用户删除失败';
                        $this->error($message);
                        die();
                    }else{
                        $model->commit();
                    }
                }
            }
        }
        $this->success("删除成功");
    }

    //酒店管理员账号
    public function checkUsername(){
        $user = I('post.user','','strip_tags');
        if(empty($user)){
            echo false;
        }
        $map['user'] = $user;
        $model = D("member");
        $result = $model->where($map)->find();
        if(!empty($result)){
            echo 0;
        }else{
            echo 1;
        }
    }
}