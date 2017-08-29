<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 角色控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class GroupController extends ComController {
    public function index(){
        $group = M('auth_group')->select();
        $this->assign('list',$group);
        $this->assign('nav',array('user','grouplist','grouplist'));//导航
        $this -> display();
    }
    public function del(){
        $ids = isset($_POST['ids'])?$_POST['ids']:false;
        if(is_array($ids)){
            foreach($uids as $k=>$v){
                $uids[$k] = intval($v);
            }
            $ids = implode(',',$ids);
            $map['id']  = array('in',$ids);
            if(M('auth_group')->where($map)->delete()){
                addlog('删除角色，ID：'.$ids);
                $this->success('恭喜，角色删除成功！');
            }else{
                $this->error('参数错误！');
            }
        }else{
            $this->error('参数错误！');
        }
    }
    public function unlock(){
        $model = M('auth_group');
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
                addlog('启用角色，ID：'.$ids);
                $this->success('恭喜，启用成功！');
            }else{
                $this->error('启用失败，参数错误！');
            }
        }else{
            $this->error('参数错误！');
        }
    }
    public function lock(){
        $model = M('auth_group');
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
                addlog('禁用角色，ID：'.$ids);
                $this->success('恭喜，模型禁用成功！');
            }else{
                $this->error('禁用失败，参数错误！');
            }
        }else{
            $this->error('参数错误！');
        }
    }
    public function update(){
        $data['title'] = isset($_POST['title'])?trim($_POST['title']):false;
        $id = isset($_POST['id'])?intval($_POST['id']):false;
        if($data['title']){
            $status = isset($_POST['status'])?$_POST['status']:'';
            if($status == 'on'){
                $data['status'] =1;
            }else{
                $data['status'] =0;
            }
            $rules = isset($_POST['rules'])?$_POST['rules']:0;
            if(is_array($rules)){
                foreach($rules as $k=>$v){
                    $rules[$k] = intval($v);
                }
                $rules = implode(',',$rules);
            }
            $data['rules'] = $rules;
            if($id){
                if($group = M('auth_group')->where('id='.$id)->data($data)->save()){
                    addlog('编辑角色，ID：'.$id.'，角色名：'.$data['title']);
                }else{
                    $this->success('未修改内容');
                    exit(0);
                }
            }else{
                $id=M('auth_group')->data($data)->add();
                addlog('新增角色，ID：'.$id.'，角色名：'.$data['title']);
            }
            $this->success('恭喜，操作成功！',U('index'));
            exit(0);
        }else{
            $this->success('角色名称不能为空！');
        }
    }
    public function edit(){
        $id = isset($_GET['id'])?intval($_GET['id']):false;
        if(!$id){
            $this->error('参数错误！');
        }
        $group = M('auth_group')->where('id='.$id)->find();
        if(!$group){
            $this->error('参数错误！');
        }
        //获取所有启用的规则
        $rule = M('auth_rule')->field('id,pid,title')->where('status=1')->order('o asc')->select();
        $group['rules'] = explode(',',$group['rules']);
        $rule = $this->getMenu($rule);
        $this->assign('rule',$rule);
        $this->assign('group',$group);
        $this->assign('nav',array('user','grouplist','addgroup'));//导航
        $this -> display();
    }
    public function add(){
        //获取所有启用的规则
        $rule = M('auth_rule')->field('id,pid,title')->where('status=1')->order('o asc')->select();
        $rule = $this->getMenu($rule);
        $this->assign('rule',$rule);
        $this -> display();
    }
}