<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 后台菜单控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class MenuController extends ComController {
    public function index(){
        $m = M('auth_rule');
        $list  = $m->order('o asc')->select();
        $list = $this->getMenu($list);
        $this->assign('list',$list);
        $this -> display();
    }
    //删除、批量删除
    public function del(){
        $model = M('auth_rule');
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if(is_array($ids)){
            if(!$ids){
                $this->error('参数错误！');
            }
            foreach($ids as $k=>$v){
                $ids[$k] = intval($v);
            }
            $map['id']  = array('in',$ids);
        }else{
            $map['id']  = $ids;
        }
        if($model->where($map)->delete()){
            if(is_array($ids)){
                $ids = implode(',',$ids);
            }
            addlog('删除菜单，ID：'.$ids);
            $this->success('恭喜，删除成功！');
        }else{
            $this->error('删除失败，参数错误！');
        }
    }
    public function add(){
        $option = M('auth_rule')->order('o ASC')->select();
        $option = $this->getMenu($option);
        $this->assign('option',$option);
        $this -> display();
    }
    public function edit($id=0){
        $id = intval($id);
        $m = M('auth_rule');
        $currentmenu = $m->where("id='$id'")->find();
        if(!$currentmenu) {
            $this->error('参数错误！');
        }
        $option = $m->order('o ASC')->select();
        $option = $this->getMenu($option);
        $this->assign('option',$option);
        $this->assign('currentmenu',$currentmenu);
        $this -> display();
    }
    public function update(){
        $id = I('post.id','','intval');
        $data['pid'] = I('post.pid','','intval');
        $data['title'] = I('post.title','','strip_tags');
        $data['name'] = I('post.name','','strip_tags');
        $data['icon'] = I('post.icon');
        $data['islink'] = I('post.islink','','intval');
        $data['status'] = 1;
        $data['o'] = I('post.o','','intval');
        $data['tips'] = I('post.tips','','strip_tags');
        if($id){
            M('auth_rule')->data($data)->where("id='{$id}'")->save();
            addlog('编辑菜单，ID：'.$id);
        }else{
            M('auth_rule')->data($data)->add();
            addlog('新增菜单，名称：'.$data['title']);
        }
        $this->success('恭喜，操作成功！',U('index'));
    }
    public function unlock(){
        $model = M('auth_rule');
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if($ids){
            if(is_array($ids)){
                $ids = implode(',',$ids);
                $map['id']  = array('in',$ids);
            }else{
                $map = 'id='.$ids;
            }
            $result = $model->where($map)->setField("islink",1);
            if($result){
                addlog('显示菜单，ID：'.$ids);
                $this->success('恭喜，操作成功！');
            }else{
                $this->error('操作失败，参数错误！');
            }
        }else{
            $this->error('参数错误！');
        }
    }
    public function lock(){
        $model = M('auth_rule');
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if($ids){
            if(is_array($ids)){
                $ids = implode(',',$ids);
                $map['id']  = array('in',$ids);
            }else{
                $map = 'id='.$ids;
            }
            $result = $model->where($map)->setField("islink",0);
            if($result){
                addlog('隐藏菜单，ID：'.$ids);
                $this->success('恭喜，操作成功！');
            }else{
                $this->error('操作失败，参数错误！');
            }
        }else{
            $this->error('参数错误！');
        }
    }
}