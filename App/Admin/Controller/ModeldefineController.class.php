<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 模型定义控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class ModeldefineController extends ComController {
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
        if (!empty($_GET['classname'])) {
            $map['classname'] = array("LIKE","%{$_GET['classname']}%");
            $this->assign('classname', $_GET['classname']);
        }
        return $map;
    }
    //保存
    public function update(){
        $data['id'] = I('post.id','','intval');
        $data['name'] = isset($_POST['name'])?$_POST['name']:false;
        $data['codevalue'] = I('post.codevalue','','strip_tags');
        $data['packagename'] = I('post.packagename','','strip_tags');
        $data['classname'] = I('post.classname','','strip_tags');
        $data['description'] = I('post.description','','strip_tags');
        if(!$data['name'] or !$data['codevalue'] or !$data['packagename'] or !$data['classname']){
            $this->error('警告！模型名称、码值、包名及类名为必填项目。');
        }
        if($data['id']){
            M('modeldefine')->data($data)->where('id='.$data['id'])->save();
            addlog('编辑模型，ID：'.$data['id']);
        }else{
            $data['status'] = 0;
            $aid = M('modeldefine')->data($data)->add();
            addlog('新增模型，ID：'.$aid);
        }
        $this->success('恭喜，操作成功！',U('index'));
    }
}