<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 行政区划控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class RegionController extends ComController {
    //查询
    public function _map(){
        $map = array();
        if (!empty($_REQUEST['name'])) {
            $map['name'] = array("LIKE","%{$_REQUEST['name']}%");
            $this->assign('name', $_REQUEST['name']);
        }
        if (!empty($_REQUEST['code'])) {
            $map['code'] = array("LIKE","%{$_REQUEST['code']}%");
            $this->assign('code', $_REQUEST['code']);
        }
        return $map;
    }
    //列表
    public function index(){
        $model = M(CONTROLLER_NAME);
        $map = $this->_map();
        if (empty($_REQUEST['pid'])) {
            $map['pid']=0;
            $this -> assign("isCityList",0);
        }else{
            $map['pid']=$_REQUEST['pid'];
            $this -> assign("isCityList",1);
            $pnode = $model->getById($_REQUEST['pid']);
            $this -> assign("province",$pnode['name']);
        }
        if (empty($_REQUEST['fp'])) {
            $this -> assign("fp",1);
        }else{
            $this -> assign("fp",$_REQUEST['fp']);
        }
        $list = $this->_list($model,$map,10,'sort asc');
        $this -> assign("list",$list);
        $this -> display();
    }

    //添加
    public function add(){
        $model = M(CONTROLLER_NAME);
        if(empty($_REQUEST['pid'])) {
            $this -> assign("pnode","中国");
        }else{
            $var = $model->getById($_REQUEST['pid']);
            $this -> assign("pnode",$var['name']);
        }
        $this->display();
    }
    //修改
    public function edit(){
        $model = M(CONTROLLER_NAME);
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if(count($ids)>1){
            $this->error('参数错误，每次只能修改一条内容！');
        }
        $var = $model->getById($ids[0]);
        if(!$var){
            $this->error('参数错误！');
        }
        if(empty($_REQUEST['pid'])) {
            $this -> assign("pnode","中国");
        }else{
            $pnode = $model->getById($_REQUEST['pid']);
            $this -> assign("pnode",$pnode['name']);
        }
        $this->assign('vo',$var);


        $this -> display();
    }
    //保存
    public function update(){
        $model = M(CONTROLLER_NAME);
        $data['id'] = I('post.id','','intval');
        $data['pid'] = I('post.pid','','intval');
        $data['sort'] = I('post.sort','','intval');
        $data['name'] = isset($_POST['name'])?$_POST['name']:false;
        $data['code'] = I('post.code','','strip_tags');
        $data['longitude'] = I('post.longitude','','strip_tags');
        $data['latitude'] = I('post.latitude','','strip_tags');
        if($data['pid']){
            $data['level']=2;
        }else{
            $data['level']=1;
        }
        if(!$data['name'] or !$data['code'] ){
            $this->error('警告！行政区划名称、地区代码为必填项目。');
        }
        if($data['id']){
            $model->data($data)->where('id='.$data['id'])->save();
            addlog('编辑行政区划，ID：'.$data['id']);
            $this->success('恭喜，操作成功！',U('index').'?pid='.$data['pid'].'&p='.$_REQUEST['p']);
        }else{
            $data['status'] = 0;
            $aid = $model->data($data)->add();
            addlog('新增行政区划，ID：'.$aid);
            $this->success('恭喜，操作成功！',U('index').'?pid='.$data['pid']);
        }
    }
    //删除、批量删除
    public function delete(){
        $model = M(CONTROLLER_NAME);
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if(is_array($ids)){
            if(!$ids){
                $this->error('参数错误！');
            }
            foreach($ids as $k=>$v){
                $ids[$k] = intval($v);
            }
            $map['id']  = array('in',$ids);
            $map['pid']  = array('in',$ids);
        }else{
            $map['id']  = $ids;
            $map['pid']  = $ids;
        }
        $map['_logic'] = 'or';
        $list=$model->where($map)->select();
        if($model->where($map)->delete()){
            foreach($list as $key=>$value){
                $del_ids[$key] = intval($value['id']);
            }
            $ids = implode(',',$del_ids);
            addlog('删除'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
            $this->success('恭喜，删除成功！');
        }else{
            $this->error('删除失败，参数错误！');
        }
    }
}