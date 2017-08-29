<?php
// +-------------------------------------------------------------------------
// | Author: Lockin
// +-------------------------------------------------------------------------
// | Date: 2017/07/28
// +-------------------------------------------------------------------------
// | Description: 部门管理控制器
// +-------------------------------------------------------------------------

namespace Admin\Controller;
use Vendor\Tree;
use Admin\Controller\ComController;

class DepartmentController extends ComController{

	/**
	 * @interfaceName _map
	 * @uses 查询条件
	 * @param
	 * @return array();
	 */
	public function _map()
	{
		$map = array();
		$data = session(session_id());
		$hotelid = 0;
		if(!empty($_POST['hid'])){
			if($_POST['hid'] != 'hid-1gg'){ //自定义查询所有酒店
				$map['zxt_hotel_department.hid'] = $_POST['hid'];
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
			if(!empty($data['content_hid'])){
				$map['zxt_hotel_department.hid'] = $data['content_hid'];
				$vo=M('hotel')->getByHid($data['content_hid']);
				$hotelid=$vo['id'];
			}
		}
		$this->assign('hid',$map['zxt_hotel_department.hid']);
		$category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
		$tree = new Tree($category);
		$str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
		$category = $tree->get_tree(0,$str,$hotelid);
		$this->assign('pHotel',$category);
		return $map;
	}

	public  function  index()
	{
		$model = D('hotel_department');
		$map = $this->_map();
		$hid_condition = $this->isHotelMenber();
		if($hid_condition && empty($map)){
			$map['hid'] = $hid_condition;
		}
		$list = $this->_list($model,$map,'10','hid asc,sort asc');
		$this->assign('list',$list);
		$this->display();
	}

	public function add()
	{
		$hotelid = 0;
		$data = session(session_id());
		if(!empty($data['content_hid'])){
			$vo=M('hotel')->getByHid($data['content_hid']);
			$hotelid=$vo['id'];
		}
		$category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
		$tree = new Tree($category);
		$str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
		$category = $tree->get_tree(0,$str,$hotelid);
		$this->assign('pHotel',$category);

		$this->display();
	}

	public  function edit()
	{
		$ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
		if($ids==false || empty($ids['0'])){
			$this->error('系统提示：参数错误');
			die();
		}
		$map['id'] = $ids['0'];
		$list = D('hotel_department')->where($map)->find();
		if(!empty($list)){
			$hotel = D("hotel")->field('hotelname')->where('hid="'.$list['hid'].'"')->find();
			$list['hotelname'] = $hotel['hotelname'];
			$this->assign('list',$list);
			$this->display();
		}else{
			$this->error('系统提示：出现系统错误，请联系系统管理员');
		}
	}

	public  function update()
	{
		$data['id'] = I('post.id','','intval');
		$data['hid'] = I('post.hid','','strip_tags');
		$data['identifier_d'] = I('post.identifier_d','','strip_tags');
		$data['title'] = I('post.title','','strip_tags');
		$data['intro'] = I('post.intro','','strip_tags');
		$data['sort'] = I('post.sort','','strip_tags');

		if(empty(data['hid']) || empty($data['identifier_d']) || empty($data['title'])){
			$this->error('系统提示：酒店，部门名称，部门标识必须填写');
		}
		$model = D('hotel_department');
		if(!empty($data['id'])){
			$result = $model->where('id="'.$data["id"].'"')->data($data)->save();
		}else{
			$result = $model->data($data)->add();
		}
		if($result!==false){
			$this->success('操作成功',U('index'));
		}else{
			$this->error('操作失败',U('index'));
		}
	}

	public function unlock(){
		$model = M("hotel_department");
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
				addlog('启用hotel_department表数据，数据ID：'.$ids);
				$this->success('恭喜，启用成功！');
			}else{
				$this->error('启用失败，参数错误！');
			}
		}else{
			$this->error('参数错误！');
		}
	}

	public function lock(){
		$model = M("hotel_department");
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
				addlog('禁用hotel_department表数据，数据ID：'.$ids);
				$this->success('恭喜，禁用成功！');
			}else{
				$this->error('禁用失败，参数错误！');
			}
		}else{
			$this->error('参数错误！');
		}
	}

	public function sort() {
		$menuids = explode(",", $_REQUEST['menuid']);
		$sorts = explode(",",$_REQUEST['sort']);
		$model = M("hotel_department");
		for ($i = 0; $i < count($sorts); $i++) {
			$model->where('id='.$menuids[$i])->setField('sort',$sorts[$i]);
		}
		$this->success('恭喜，操作成功！');
	}

	public function  delete()
	{
		$ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
		$checkMap['department_id'] = array('in',$ids);
		$hotel_member_list = D('hotel_member')->where($checkMap)->select();
		if(!empty($hotel_member_list)){
			$this->error('系统提示：删除部门下含有人员，请先删除或更改人员所属部门');
			die();
		}
		$map['id'] = array('in',$ids);
		$model = D('hotel_department');
		$model->startTrans();
		$result = $model->where($map)->delete();
		if($result!==false){
			$model->commit();
			addlog('删除部门数据');
			$this->success('删除成功');
		}else{
			$model->rollback();
			$this->error('删除失败');
		}
	}
}