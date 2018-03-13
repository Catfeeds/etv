<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/12
 * Time: 15:02
 */

namespace Admin\Controller;

use Admin\Controller\ComController;
use Think\Exception;
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
		$starttime = explode(":", $vo['start_time']);
		$endtime = explode(":", $vo['end_time']);
		$vo['starthour'] = $starttime['0'];
		$vo['startminute'] = $starttime['1'];
		$vo['endhour'] = $endtime['0'];
		$vo['endminute'] = $endtime['1'];
		if(!empty($vo['maclist'])){
			$device_where['hid'] = $vo['hid'];
			$macarr = explode(",", $vo['maclist']);
			$roomList = D("device")->field('mac,room')->where($device_where)->select();
			if(!empty($roomList)){
				foreach($roomList as $key=>$value){
					if(in_array($value['mac'], $macarr)){
						$roomList[$key]['isselect'] = 1;
						$room_arr[] = $value['room'];
					}else{
						$roomList[$key]['isselect'] = 0;
					}
				}
				$vo['room'] = implode(",", $room_arr);
			}else{
				$vo['room'] = '';
			}
		}else{
			$vo['room'] = '';
			$roomList = [];
		}
		$this->assign('roomList', $roomList);
		$this->assign('vo', $vo);
		$this->_assign_hour_minute();
		$this->display();
	}

	public function update(){
		$params = I('post.');
		$allowfield = "hid,title,content,repeat_set,weekday,norepeat_time,start_time,end_time,outtolink,createtime,status,maclist";
		$params['createtime'] = date("Y-m-d H:i:s");
		$params['start_time'] = $params['starthour'].":".$params['startminute'];
		$params['end_time'] = $params['endhour'].":".$params['endminute'];
		if($params['repeat_set'] == 3){
			$params['weekday'] = implode(",", $params['weekday']);
		}else{
			$params['weekday'] = 0;
		}
		if($params['repeat_set'] != 0){
			$params['norepeat_time'] = date("Y-m-d");
		}

		if($params['relock']){ //修改
			$where['id'] = $params['relock'];
			$result = M("appopen_setting")->where($where)->field($allowfield)->filter('strip_tags')->save($params);
			if($result){
				$this->success('修改成功',U('index'));
			}else{
				$this->error('修改失败,请重试或联系管理员', U('index'));
			}
		}else{
			$result = M('appopen_setting')->field($allowfield)->filter('strip_tags')->data($params)->add();
			if($result){
				$this->success('新增成功',U('index'));
			}else{
				$this->error('新增失败,请重试或联系管理员', U('index'));
			}
		}
	}

	public function unlock(){
		$model = M('appopen_setting');
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
				addlog('启用'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
				$this->success('恭喜，启用成功！');
			}else{
				$this->error('启用失败，参数错误！');
			}
		}else{
			$this->error('参数错误！');
		}
	}

	public function lock(){
		$model = M('appopen_setting');
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
				addlog('禁用'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
				$this->success('恭喜，禁用成功！');
			}else{
				$this->error('禁用失败，参数错误！');
			}
		}else{
			$this->error('参数错误！');
		}
	}

	public function delete()
	{
		$params = I('post.');
		if(!$params['ids']){
			$this->error('参数错误',U('index'));
		}
		$where['id'] = array('in', $params['ids']);
		try{
			D("appopen_setting")->where($where)->delete();
		}catch(Exception $e){
			$this->error('操作错误');
		}
		$this->success('删除成功');
	}

	public function appindex(){
		if($this->isHotelMenber()){
			$where['zxt_appopen_name.hid'] = $this->isHotelMenber();
		}else{
			$where = [];
		}
		$field = "zxt_appopen_name.*,zxt_hotel.hotelname";
		$list = D('appopen_name')->where($where)
			->join('zxt_hotel on zxt_appopen_name.hid=zxt_hotel.hid')
			->field($field)
			->select();
		$this->assign("list", $list);
		$this->display();
	}

	public function appadd(){
		if(!$this->isHotelMenber()){
			$category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
			$tree = new Tree($category);
			$str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
			$category = $tree->get_tree(0,$str,0);
			$this->assign('pHotel',$category);
		}
		$this->display();
	}

	public function appedit(){
		$ids = I('post.ids');
		if(!$ids['0']){
			$this->error('参数错误',U('index'));
		}
		$where['id'] = $ids['0'];
		$vo = D('appopen_name')->where($where)->find();
		$this->assign('vo', $vo);
		$this->display();
	}

	public function appupdate(){
		$params = I('post.');
		$allowfield = "hid,title,packagename,classname";
		if($params['relock']){ //修改
			$where['id'] = $params['relock'];
			$result = M("appopen_name")->where($where)->field($allowfield)->filter('strip_tags')->save($params);
			if($result){
				$this->success('修改成功',U('appindex'));
			}else{
				$this->error('修改失败,请重试或联系管理员', U('appindex'));
			}
		}else{
			$result = M('appopen_name')->field($allowfield)->filter('strip_tags')->data($params)->add();
			if($result){
				$this->success('新增成功',U('index'));
			}else{
				$this->error('新增失败,请重试或联系管理员', U('appindex'));
			}
		}
	}

	public function appdelete(){
		$params = I('post.');
		if(!$params['ids']){
			$this->error('参数错误',U('appindex'));
		}
		$where['id'] = array('in', $params['ids']);
		try{
			D("appopen_name")->where($where)->delete();
		}catch(Exception $e){
			$this->error('操作错误',U('appindex'));
		}
		$this->success('删除成功',U('appindex'));
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