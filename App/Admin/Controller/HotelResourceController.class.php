<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 酒店资源管理控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
class HotelResourceController extends ComController {
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
        $model = M('hotel_resource');
        $map = $this->_map();
        $hid_condition = $this->isHotelMenber();
        if($hid_condition && empty($map)){
            $map['hid'] = $hid_condition;
        }
        $list = $this->_list($model,$map);
        $this -> assign("list",$list);
        $this -> display();
    }
    public function _list($model,$map=array(),$pagesize=10,$order="id desc"){
        $list = array();
        $count = $model->where($map)->count();
        $Page = new\Think\Page($count,$pagesize,$_GET);
        $pageshow = $Page -> show();
        $list = $model ->where($map) -> limit($Page ->firstRow.','.$Page -> listRows)->order($order) -> select();
        $this -> assign("page",$pageshow);
        return $list;
    }
}