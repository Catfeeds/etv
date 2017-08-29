<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 语言定义控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class LangcodeController extends ComController {
    //查询
    public function _map(){
        $map = array();
        if (!empty($_GET['name'])) {
            $map['name'] = array("LIKE","%{$_GET['name']}%");
            $this->assign('name', $_GET['name']);
        }
        if (!empty($_GET['code'])) {
            $map['code'] = array("LIKE","%{$_GET['code']}%");
            $this->assign('code', $_GET['code']);
        }
        return $map;
    }
    public function index(){
        $model = M(CONTROLLER_NAME);
        $map = $this->_map();
        $list = $this->_list($model,$map,10,"id asc");
        $this -> assign("list",$list);
        $this -> display();
    }
    //保存
    public function update(){
        $data['id'] = I('post.id','','intval');
        $data['name'] = isset($_POST['name'])?$_POST['name']:false;
        $data['code'] = I('post.code','','strip_tags');
        $data['language'] = I('post.language','','strip_tags');
        $data['nation'] = I('post.nation','','strip_tags');
        if(!$data['name'] or !$data['code'] ){
            $this->error('警告！模型名称和标识为必填项目。');
        }
        if($data['id']){
            M(CONTROLLER_NAME)->data($data)->where('id='.$data['id'])->save();
            addlog('编辑语言，ID：'.$data['id']);
        }else{
            $data['status'] = 0;
            $aid = M(CONTROLLER_NAME)->data($data)->add();
            addlog('新增语言，ID：'.$aid);
        }
        $this->success('恭喜，操作成功！',U('index'));
    }
}