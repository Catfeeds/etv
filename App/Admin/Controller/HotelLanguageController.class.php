<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 酒店语言管理控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
class HotelLanguageController extends ComController {
    //查询
    public function _map(){
        $map = array();
        $data = session(session_id());
        $hotelid = 0;
        if(!empty($_POST['hid'])){
            if($_POST['hid'] != 'hid-1gg'){ //自定义查询所有酒店
                $map['hid'] = $_POST['hid'];
                $vo=M('hotel')->getByHid($_POST['hid']);
                $hotelid=$vo['id'];
                $data['content_hid'] = $_POST['hid'];
                session(session_id(),$data);
            }else{
                $map = array();
                $data['content_hid'] = null;
                session(session_id(),$data);
            }
        }else{
            $data = session(session_id());
            if (!empty($data['content_hid'])) {
                $map['hid'] = $data['content_hid'];
                $vo = M('hotel')->getByHid($data['content_hid']);
                $hotelid = $vo['id'];
            }
        }
        $this->assign('hid',$map['hid']);
        $category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
        $category = $tree->get_tree(0,$str,$hotelid);
        $this->assign('pHotel',$category);
        return $map;
    }
    //列表
    public function index(){
        $model = M('hotel_language');
        $map = $this->_map();
        $hid_condition = $this->isHotelMenber();
        if($hid_condition && empty($map)){
            $map['hid'] = $hid_condition;
        }
        $list = $this->_list($model,$map);
        $this -> assign("list",$list);
        $this -> display();
    }
    public function _before_add(){
        $this->isHotelMenber();
        $hotelid = 0;
        $data = session(session_id());
        if(!empty($data['content_hid'])){
            $vo=M('hotel')->getByHid($data['content_hid']);
            $hotelid=$vo['id'];
        }
        $Langcode = D("Langcode");
        $list = $Langcode->where("status=1")->order("id asc")->select();
        $this->assign("langlist",$list);
        $category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
        $category = $tree->get_tree(0,$str,$hotelid);
        $this->assign('pHotel',$category);
        $this->isHotelMenber();
        $category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
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
            $this->error('参数错误！请选择要修改的内容！');
        }
        $this->assign('vo',$var);

        $Langcode = D("Langcode");
        $list = $Langcode->where("status=1")->order("id asc")->select();
        $this->assign("langlist",$list);

        $voHotel=M('hotel')->getByHid($var['hid']);
        $category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>";
        $category = $tree->get_tree(0,$str,$voHotel['id']);
        $this->assign('pHotel',$category);

        $this -> display();
    }
    //保存
    public function update(){
        $model=M(CONTROLLER_NAME);
        $data['id'] = I('post.id','','intval');
        $data['hid'] = I('post.hid','','strip_tags');
        $data['name'] = I('post.name','','strip_tags');
        $data['langcodeid'] = I('post.langcodeid','','intval');
        $data['appellation'] = I('post.appellation','','strip_tags');
        $data['content'] = I('post.content','','strip_tags');
        $data['signer'] = I('post.signer','','strip_tags');
        $data['sort'] = I('post.sort','','intval');
        
        if(!$data['hid'] || !$data['name']|| !$data['langcodeid']|| !$data['appellation']|| !$data['content']|| !$data['signer']){
            $this->error('警告！语言信息未填完整，请补充完整！');
        }
        if($data['id']){
            $model->data($data)->where('id='.$data['id'])->save();
            addlog('修改酒店语言，ID：'.$data['id']);
        }else{
            $data['status'] = 0;
            $aid = $model->data($data)->add();
            addlog('添加酒店语言，ID：'.$aid);
        }
        $this->success('恭喜，操作成功！',U('index'));
    }
}