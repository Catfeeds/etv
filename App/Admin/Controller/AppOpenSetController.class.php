<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/12
 * Time: 15:02
 */

namespace Admin\Controller;

use Admin\Controller\ComController;
use Vendor\Tree;

class AppOpenSetController extends ComController{

	public function index()
	{
		if($this->isHotelMenber()){
			$where['zxt_appopen_setting.hid'] = $this->isHotelMenber();
		}else{
			$where = [];
		}
		$field = "zxt_appopen_setting.*,zxt_hotel.hotelname";
		$list = D('appopen_setting')->where($where)
										->join('zxt_hotel on zxt_appopen_setting.hid=zxt_hotel.hid')
										->field($field)
										->select();
		$this->assign("list", $list);
		$this->display();
	}

	public function add(){
		$this->_assign_hour_minute();
		if(!$this->isHotelMenber()){
			$category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
			$tree = new Tree($category);
			$str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
			$category = $tree->get_tree(0,$str,0);
			$this->assign('pHotel',$category);
		}
		$this->display();
	}

	public function edit(){
		$ids = I('post.ids');
		if(!$ids['0']){
			$this->error('参数错误',U('index'));
		}
		$where['id'] = $ids['0'];
		$vo = D('appopen_setting')->where($where)->find();
		$this->assign('vo', $vo);
		$this->_assign_hour_minute();
		$this->display();
	}

	public function update(){
		$params = I('post.');
		$allowfield = "hid,title,content,repeat_set,weekday,norepeat_time,start_time,end_time,outtolink,createtime,status,mac";
		$params['createtime'] = date("Y-m-d H:i:s");
		if($params['id']){ //修改

		}else{
			$params['start_time'] = $params['starthour'].":".$params['startminute'];
			$params['end_time'] = $params['endhour'].":".$params['endminute'];
			if($params['repeat_set'] == 3){
				$params['weekday'] = implode(",", $params['weekday']);
			}else{
				$params['weekday'] = 0;
			}
			if($params['repeat_set'] != 0){
				$params['norepeat_time'] = date("Y-m-s");
			}
			$result = M('appopen_setting')->field($allowfield)->filter('strip_tags')->data($params)->add();
			if($result){
				$this->success('新增成功',U('index'));
			}else{
				$this->error('新增失败,请重试或联系管理员', U('index'));
			}
		}
	}

	public function _assign_hour_minute(){
		$hour = array();
		for ($i = 0; $i < 24; $i++) {
			if ($i<10) {
				$hour[] = "0".$i;
			}else{
				$hour[] = $i;
			}
		}
		$minute = array();
		for ($j = 0; $j < 60; $j++) {
			if ($j<10) {
				$minute[] = "0".$j;
			}else{
				$minute[] = $j;
			}
		}
		$this->assign("hour",$hour);
		$this->assign("minute",$minute);
	}

	public function findDeviceByHid(){
		$Device = D("Device");
		$hid = I('post.hid');
		if(trim($hid) == ''){
			echo false;
			die();
		}
		$map['hid']=array("eq",$hid);
		$roomList = $Device->field('id,room,mac,room_remark')->where($map)->order ('room asc')->select();
		echo json_encode($roomList);
	}
}