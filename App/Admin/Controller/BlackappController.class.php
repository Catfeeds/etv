<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 黑名单控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class BlackappController extends ComController {
    //查询
    public function _map(){
        $map = array();
        if (!empty($_GET['name'])) {
            $map['name'] = array("LIKE","%{$_GET['name']}%");
            $this->assign('name', $_GET['name']);
        }
        if (!empty($_GET['packagename'])) {
            $map['packagename'] = array("LIKE","%{$_GET['packagename']}%");
            $this->assign('packagename', $_GET['packagename']);
        }
        return $map;
    }
    //保存
    public function update(){
        $model = M(CONTROLLER_NAME);
        $data['id'] = I('post.id','','intval');
        $data['name'] = isset($_POST['name'])?$_POST['name']:false;
        $data['packagename'] = I('post.packagename','','strip_tags');
        if(!$data['name'] or !$data['packagename']){
            $this->error('警告！应用名称、包名为必填项目。');
        }
        if($data['id']){
            $model->data($data)->where('id='.$data['id'])->save();
            addlog('编辑黑名单应用，ID：'.$data['id']);
        }else{
            $data['status'] = 0;
            $aid = $model->data($data)->add();
            addlog('新增黑名单应用，ID：'.$aid);
        }
        $this->success('恭喜，操作成功！',U('index'));
    }
    public function unlock(){
        $model = M(CONTROLLER_NAME);
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
                addlog('显示黑名单应用，数据ID：'.$ids);
                $this->success('恭喜，操作成功！');
            }else{
                $this->error('操作失败，参数错误！');
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
                $map['id']  = array('in',$ids);
            }else{
                $map = 'id='.$ids;
            }
            $result = $model->where($map)->setField("status",0);
            if($result){
                addlog('隐藏黑名单应用，数据ID：'.$ids);
                $this->success('恭喜，操作成功！');
            }else{
                $this->error('操作失败，参数错误！');
            }
        }else{
            $this->error('参数错误！');
        }
    }
}