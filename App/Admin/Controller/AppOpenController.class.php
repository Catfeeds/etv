<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/1
 * Time: 16:16
 */

namespace Admin\Controller;

use Admin\Controller\ComController;
use Think\Exception;
use Think\Log;
use Vendor\Tree;

class AppOpenController extends ComController
{

	public function _map()
	{
		$map = array();
		return $map;
	}

	/**
	 * 主页方法
	 * 显示APP名称 APP对应的定时设置
	 */
	public function index()
	{
		$map = $this->_map();
		$field = "zxt_appopen_name.id,zxt_hotel.hotelname,appname,GROUP_CONCAT(zxt_appopen_set.date,' ',zxt_appopen_set.start_time,'--',zxt_appopen_set.end_time,',' SEPARATOR '\n' ) as settime";
		$list = D('appopen_name')->where($map)
			->join('left join zxt_appopen_set on zxt_appopen_name.id=zxt_appopen_set.applistid')
			->join('left join zxt_hotel on zxt_appopen_name.hid=zxt_hotel.hid')
			->field($field)
			->group('zxt_appopen_name.id')
			->select();
		$this->assign("list", $list);
		$this->display();
	}

	/**
	 * 增加APP主页面
	 * 包含增添内容为
	 * 所属客户 ,APP名称, APP报名, APP类名
	 */
	public function addappname()
	{
		$this->isHotelMenber();
		$hotelid = 0;
		$data = session(session_id());
		if (!empty($data['content_hid'])) {
			$vo = M('hotel')->getByHid($data['content_hid']);
			$hotelid = $vo['id'];
		}
		$category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
		$tree = new Tree($category);
		$str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
		$category = $tree->get_tree(0, $str, $hotelid);
		$this->assign('pHotel', $category);

		$this->display();
	}

	/**
	 * 修改APP名称主页面
	 */
	public function editappname()
	{
		$ids = I('post.ids');
		if ($ids['0']) {
			$where['id'] = $ids['0'];
			$vo = D('appopen_name')->where($where)->find();
			if(!empty($vo)){
				$hotelid_map['hid'] = $vo['hid'];
				$hotel_arr = D('hotel')->where($hotelid_map)->field('id')->find();
				$hotelid = $hotel_arr['id'];
				$hid = $vo['hid'];
			}else{
				$this->error('参数错误,请重试或联系后台管理员');
			}
			$ismember = $this->isHotelMenber();

			if(!$ismember){
				$category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
				$tree = new Tree($category);
				$str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
				$category = $tree->get_tree(0, $str, $hotelid);
				$this->assign('pHotel', $category);
			}
			$this->assign('hid',$hid);
			$this->assign('vo',$vo);
			$this->display();
		} else {
			$this->error('参数错误,请重试或联系后台管理员');
		}
	}

	/**
	 * 保存添加APP方法
	 */
	public function updateappname()
	{
		$params = I('post.', '', 'htmlspecialchars,strip_tags');
		if (!empty($params['hid']) && !empty($params['appname']) && !empty($params['packagename']) && !empty($params['classname'])) {
			try {
				if($params['id']){
					D('appopen_name')->where('id='.$params['id'])->data($params)->save();
				}else{
					D('appopen_name')->add($params);
				}
			} catch (Exception $e) {
				if($params['id']){
					Log::write('内容管理修改APP错误');
				}else{
					Log::write('内容管理新增APP错误');
				}
			}
			if($params['id']){
				$this->success('修改成功', U('index'));
			}else{
				$this->success('新增成功', U('index'));
			}
		} else {
			$this->error('参数有误,请确认填写参数合法性', U('index'));
		}
	}

	/**
	 * 删除APP名称方法
	 */
	public function delappname()
	{
		$ids = I('post.ids');
		if ($ids['0']) {
			$map['id'] = $issetmap['applistid'] = $ids['0'];
			D()->startTrans();
			try {
				D('appopen_name')->where($map)->delete();
				D('appopen_set')->where($map)->delete();
			} catch (Exception $e) {
				D()->rollback();
				Log::write('删除内容管理中,APP定时名称删除出错,删除列表ID为' . $ids['0']);
				$this->error('删除出错,请重试或联系后台管理员');
			}
			D()->commit();
			$this->success('删除成功');
		} else {
			$this->error('参数错误,请重试或联系后台管理员');
		}
	}

	/**
	 * app定时设置主页面
	 */
	public function indexappset(){
		$ids = I('request.ids');
		if($ids){
			$list = D('appopen_set')->where('applistid='.$ids['0'])->select();
			$this->assign('list',$list);
			$this->assign('applistid',$ids['0']);
			$this->display();
		}else{
			$this->error('参数错误,请重试或联系后台管理员');
		}
	}

	/**
	 * 新增定时设置页面
	 */
	public function addappset(){
		$applistid = I('post.applistid','','strip_tags');
		if($applistid || !is_int($applistid)){
			$this->_assign_hour_minute();
			$this->assign('applistid', $applistid);
			$this->display();
		}else{
			$this->error('参数错误,请重试或联系后台管理员');
		}
	}

	/**
	 * 修改定时设置页面
	 */
	public function editappset(){
		$ids = I('post.ids');
		if($ids){
			$this->_assign_hour_minute();
			$vo = D('appopen_set')->where('id='.$ids['0'])->find();
			if(!empty($vo) && !empty($vo['start_time']) && !empty($vo['end_time'])){
				$start_time = explode(":",$vo['start_time']);
				$vo['starthour'] = $start_time['0'];
				$vo['startminute'] = $start_time['1'];
				$end_time = explode(":", $vo['end_time']);
				$vo['endhour'] = $end_time['0'];
				$vo['endminute'] = $end_time['1'];
			}
			$this->assign('vo', $vo);
			$this->display();
		}else{
			$this->error('参数错误,请联系管理员或重试',U('index'));
		}
	}

	/**
	 * 保存定时设置方法
	 */
	public function updateappset(){
		$params = I('post.', '', 'htmlspecialchars,strip_tags');
		$judgeresult = $this->judgetimeset($params['starthour'],$params['startminute'],$params['endhour'],$params['endminute']);
		if($judgeresult){
			$data['applistid'] = $params['applistid'];
			$data['date'] = $params['date'];
			$data['start_time'] = $params['starthour'].':'.$params['startminute'];
			$data['end_time'] = $params['endhour'].':'.$params['endminute'];
			$data['status'] = 0;
			try{
				if($params['id']){ //修改
					D('appopen_set')->where('id='.$params['id'])->data($data)->save();
				}else{ //新增
					D('appopen_set')->add($data);
				}
			}catch(Exception $e){
				if($params['id']){
					Log::write('修改APP定时时间错误');
				}else{
					Log::write('新增APP定时时间错误');
				}
			}
			$this->success('操作成功',U('indexappset?ids='.$params['applistid']));
		}else{
			$this->error('时间设置有误,请确认开启时间早于结束时间',U('indexappset?ids='.$params['applistid']));
		}
	}

	/**
	 * 删除定时设置方法
	 */
	public function delappset(){
		$applistid = I('post.applistid');
		$ids = I('post.ids');
		if(empty($ids['0'])){
			$this->error('参数错误,请联系管理员或重试', U('index'));
		}
		try{
			D('appopen_set')->where('id='.$ids['0'])->delete();
		}catch(Exception $e){
			Log::write('删除内容管理中,APP定时设置删除出错,删除列表ID为' . $ids['0']);
			$this->error('操作失败',U('indexappset?ids='.$applistid));
		}
		$this->success('操作成功',U('indexappset?ids='.$applistid));
	}

	public function setunlock()
	{
		$applistid = I('post.applistid');
		$model = D('appopen_set');
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
				$this->success('操作成功',U('indexappset?ids='.$applistid));
			}else{
				$this->error('操作失败',U('indexappset?ids='.$applistid));
			}
		}else{
			$this->error('参数错误！',U('index'));
		}
	}

	public function setlock()
	{
		$applistid = I('post.applistid');
		$model = D('appopen_set');
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
				$this->success('操作成功',U('indexappset?ids='.$applistid));
			}else{
				$this->error('操作失败',U('indexappset?ids='.$applistid));
			}
		}else{
			$this->error('参数错误！');
		}
	}

	/**
	 * 时分列表方法
	 */
	private function _assign_hour_minute(){
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

	/**
	 * 判断时间格式是否正确
	 * @param starthour
	 * @param $startminute
	 * @param $endhour
	 * @param $endminute
	 * @return bool
	 */
	private function judgetimeset($starthour, $startminute, $endhour, $endminute){
		if($starthour > $endhour){
			return false;
		}else{
			if(starthour == $endhour){
				if($startminute > $endminute){
					return false;
				}
			}
		}
		return true;

	}
}