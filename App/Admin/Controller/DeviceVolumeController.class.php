<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 酒店设备容量控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
class DeviceVolumeController extends ComController {
    public function _map(){
        $map = array();
        $data = session(session_id());
        $hotelid = 0;
        if(!empty($_POST['hid'])){
            if($_POST['hid'] != 'hid-1gg'){ //自定义查询所有酒店
                $map['zxt_hotel_volume.hid'] = $_POST['hid'];
                $vo=M('hotel')->getByHid($_POST['hid']);
                $hotelid=$vo['id'];
            }else{
                $map = array();
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
    public function index(){
        $model = D("HotelVolume");
        $map = $this->_map();
        $count = $model->where($map)->count();
        $Page = new\Think\Page($count,$pagesize=10,$_GET);
        $pageshow = $Page -> show();
        $this -> assign("page",$pageshow);
        $list = $model->field('zxt_hotel.hotelname,zxt_hotel.space,zxt_hotel_volume.*')->where($map)->join('zxt_hotel ON zxt_hotel_volume.hid = zxt_hotel.hid')->limit($Page ->firstRow.','.$Page -> listRows)->select();
        if(!empty($list)){
            foreach ($list as $key => $value) {
                $lastspace = $value['space']-$value['devicebg_size']-$value['content_size']-$value['chg_size'];
                $list[$key]['lastspace'] = $lastspace>0?$lastspace:0;
            }
        }

        $this->assign('list',$list);
        $this->display();
    }
}