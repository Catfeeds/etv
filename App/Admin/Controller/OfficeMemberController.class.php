<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/28
 * Time: 11:28
 */

namespace Admin\Controller;
use Vendor\Tree;
use Admin\Controller\ComController;

Class OfficeMemberController extends ComController{

	public  function _map()
	{
		$map = array();
		$data = session(session_id());
		$hotelid = 0;
		if(!empty($_POST['hid'])){
			if($_POST['hid'] != 'hid-1gg'){ //自定义查询所有酒店
				$map['zxt_hotel_member.hid'] = $_POST['hid'];
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
				$map['zxt_hotel_member.hid'] = $data['content_hid'];
				$vo=M('hotel')->getByHid($data['content_hid']);
				$hotelid=$vo['id'];
			}
		}
		$this->assign('hid',$map['zxt_hotel_member.hid']);
		$category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
		$tree = new Tree($category);
		$str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
		$category = $tree->get_tree(0,$str,$hotelid);
		$this->assign('pHotel',$category);
		return $map;
	}

	public  function index()
	{
		$model = D('hotel_member');
		$map = $this->_map();
		$hid_condition = $this->isHotelMenber();
		if($hid_condition && empty($map)){
			$map['zxt_hotel_member.hid'] = $hid_condition;
		}
		$count = $model->where($map)->count();
		$Page = new\Think\Page($count,$pagesize=10,$_POST);
		$pageshow = $Page -> show();
		$list = $model->where($map)
			->field('zxt_hotel_member.*,zxt_hotel.hotelname,zxt_hotel_department.title')
			->join('left join zxt_hotel ON zxt_hotel_member.hid=zxt_hotel.hid','left')
			->join('left join zxt_hotel_department ON zxt_hotel_member.department_id=zxt_hotel_department.id','left')
			->limit($Page ->firstRow.','.$Page -> listRows)
			->order('zxt_hotel_member.hid asc,sort asc')
			->select();
		$this -> assign("page",$pageshow);
		$this -> assign("list",$list);
		$this -> display();
	}

	public function  add(){
		$hotelMember = $this->isHotelMenber();
		$category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();

		$hotelid = 0;
		if($hotelMember!=false){
			$departmentHid = $hotelMember['in']['0'];
		}else{
			$departmentHid = $category['0']['hid'];
		}
		$data = session(session_id());
		if(!empty($data['content_hid'])){
			$vo=M('hotel')->getByHid($data['content_hid']);
			$hotelid=$vo['id'];
			$departmentHid = $data['content_hid'];
		}
		$tree = new Tree($category);
		$str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
		$category = $tree->get_tree(0,$str,$hotelid);
		$this->assign('pHotel',$category);

		$this->display();
	}

	public function edit()
	{
		$ids = I('post.ids','','strip_tags');
		if(count($ids)!=1){
			$this->error('仅能对一条记录进行操作');
		}
		$field = "zxt_hotel_member.*,zxt_hotel.hotelname,zxt_hotel_department.title";
		$list = D("hotel_member")->where('zxt_hotel_member.id="'.$ids["0"].'"')
								->join('zxt_hotel ON zxt_hotel_member.hid=zxt_hotel.hid','left')
								->join('zxt_hotel_department ON zxt_hotel_member.department_id=zxt_hotel_department.id','left')
								->field($field)
								->find();
		$this->assign('list',$list);
		$departmentList = D("hotel_department")->where('hid="'.$list["hid"].'"')->field('id,title')->select();
		$this->assign('departmentList',$departmentList);
		$this->display();
	}

	public function update()
	{
		$data['id'] = I('post.id','','strip_tags');
		$data['hid'] = I('post.hid','','strtoupper');
		$data['department_id'] = I('post.department_id','','intval');
		$data['identifier_m'] = I('post.identifier_m','','strip_tags');
		$data['name'] = I('post.name','','strip_tags');
		$data['intro'] = I('post.intro','','strip_tags');
		$data['sex'] = I('post.sex','','intval');
		$data['filepath'] = I('post.filepath','','strip_tags');
		$data['sort'] = I('post.sort','','intval');

		if(empty($data['hid']) || empty($data['department_id']) || empty($data['name'])){
			$this->error('系统提示：酒店编号、部门、姓名为必填项');
		}

		//获取后缀名
		$data['filepath_type'] = 0;//没有图片或者视频资源
		if(!empty($data['filepath'])){
			$filepath_arr = explode(".",$data['filepath']);
			$ext = trim($filepath_arr[count($filepath_arr)-1]);
			$picture_ext = array('jpg','png','jpeg');
			$video_ext = array('mp4');
			if(in_array($ext,$picture_ext)){
				$data['filepath_type'] = 1;
			}elseif(in_array($ext,$video_ext)){
				$data['filepath_type'] = 2;
			}
		}

		$model = D('hotel_member');
		if(!empty($id)){
			$result = $model->where('id="'.$id.'"')->data($data)->save();
		}else{
			$result = $model->data($data)->add();
		}
		if($result !== false){
			addlog('操作职能管理中的人员管理操作');
			$this->success('操作成功',U('index'));
		}else{
			$this->error('操作失败');
		}
	}

	public function upload(){
		$callback = array();
		if (!empty($_FILES[$_REQUEST["name"]]["name"])) {
			$upload = new \Think\Upload();
			$upload->maxSize=209715200; //200M
			$upload->exts=array('jpg','png','jpeg','mp4');
			$upload->rootPath='./Public/';
			$upload->savePath='./upload/member/';
			$info=$upload->uploadOne($_FILES[$_REQUEST["name"]]);
			if(!$info) {
				$callback['status'] = 0;
				$callback['info'] = $upload->getError();
			}else{
				$callback['status'] = 1;
				$callback['info']="上传成功！";
				$callback['storename']=trim($info['savepath'].$info['savename'],'.');
			}
		}else{
			$callback['status'] = 0;
			$callback['info']='缺少文件';
		}
		echo json_encode($callback);
	}

	public function change_department_d(){
		$hid = I('post.hid','','strip_tags');
		if(empty($hid)){
			echo false;
		}
		$model = D("hotel_department");
		$map['hid'] = $hid;
		$map['status'] = 1;
		$list = $model->where($map)->field('id,title')->select();
		if(!empty($list)){
			$data['status'] = 200;
			$data['data'] = $list;
		}else{
			$data['status'] = 404;
			$data['data'] = null;
		}
		echo json_encode($data);
	}

	public function unlock(){
		$model = M("hotel_member");
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
				addlog('启用hotel_member表数据，数据ID：'.$ids);
				$this->success('恭喜，启用成功！');
			}else{
				$this->error('启用失败，参数错误！');
			}
		}else{
			$this->error('参数错误！');
		}
	}

	public function lock(){
		$model = M("hotel_member");
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
				addlog('禁用hotel_member表数据，数据ID：'.$ids);
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
		$model = M("hotel_member");
		for ($i = 0; $i < count($sorts); $i++) {
			$model->where('id='.$menuids[$i])->setField('sort',$sorts[$i]);
		}
		$this->success('恭喜，操作成功！');
	}

	public function  delete()
	{
		$ids = I('post.ids','','strip_tags');
		if(count($ids)<1){
			$this->error('系统提示：至少请选择一条需要删除的数据');
		}
		$model = D("hotel_member");
		$map['id'] = array('in',$ids);
		$list = $model->where($map)->field('filepath')->select();
		$model->startTrans();
		$result = $model->where($map)->delete();
		if($result!==false){
			foreach($list as $key => $value){
				@unlink(FILE_UPLOAD_ROOTPATH.$value['filepath']);
			}
			$model->commit();
			addlog('删除部门数据');
			$this->success('删除成功');
		}else{
			$model->rollback();
			$this->error('删除失败');
		}
	}
}