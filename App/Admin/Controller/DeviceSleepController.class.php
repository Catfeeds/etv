<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 休眠设置控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;

class DeviceSleepController extends ComController {

	public function _map(){
		$map = array();
        $data = session(session_id());
        $hotelid=0;
        if(!empty($_POST['hid'])) {
            if ($_POST['hid'] != 'hid-1gg') { //自定义查询所有酒店
                $map['zxt_device.hid'] = $_POST['hid'];
                $this->assign('hid',$_POST['hid']);
                $vo=M('hotel')->getByHid($_POST['hid']);
                $data['content_hid'] = $_POST['hid'];
                session(session_id(),$data);
            }else{
                $map = array();
                $data['content_hid'] = null;
                session(session_id(),$data);
            }
        }
        if(!empty($_POST['mac'])){
            $map['zxt_device.mac'] = array("LIKE","%{$_POST['mac']}%");;
        }
        $hotelid = empty($vo)?0:$vo['id'];
        $category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
        $category = $tree->get_tree(0,$str,$hotelid);
        $this->assign('pHotel',$category);
        return $map;
	}

	public function index(){
		$field = "zxt_device.hid,zxt_device.room,zxt_device.mac,zxt_device.dev_desc,zxt_device.firmware_version,zxt_hotel.name,sleep_time_start,sleep_time_end,zxt_device_sleep.sleep_status,zxt_device_sleep.sleep_marked_word,zxt_device_sleep.sleep_countdown_time,zxt_device_sleep.id";
		$where = $this->_map();
		$count = D("device")->where($map)->count();
		$Page = new\Think\Page($count,10,$_GET);//实例化分页类 
        $pageshow = $Page -> show();//分页显示输出
        $this -> assign("page",$pageshow);//赋值分页输出

		$list = D("device")->where($where)->field($field)->join('zxt_hotel on zxt_device.hid=zxt_hotel.hid')->join('zxt_device_sleep on zxt_device.mac=zxt_device_sleep.mac','left')->limit($Page ->firstRow.','.$Page -> listRows)->select();
		$this->assign('list',$list);

		$imagemodel = D('DeviceMacImage');
        $imageinfo = $imagemodel->field('*')->select();
        $this->assign('imageinfo',$imageinfo);

        $this->_assign_hour_minute();

		$this->display();
	}

    // 一条记录弹窗
	public function toSetSleep(){
        $map['zxt_device_sleep.id'] = I('post.deviceid','','intval');
        $result = D("device_sleep")->join('zxt_device_mac_image ON zxt_device_sleep.sleep_imageid = zxt_device_mac_image.id','left')->where($map)->field('zxt_device_sleep.id,sleep_status,sleep_time_start,sleep_time_end,sleep_marked_word,sleep_countdown_time,sleep_imageid,image_name,image_path')->find();
        //拆分时间
        if(empty($result)){
            echo '';
            die();
        }
        if (!empty($result['sleep_time_start'])) {
        	$starttime = explode(':', $result['sleep_time_start']);
            $endtime = explode(":", $result['sleep_time_end']);
            $result['starttime_h'] = $starttime[0];
            $result['starttime_m'] = $starttime[1];
            $result['endtime_h'] = $endtime[0];
            $result['endtime_m'] = $endtime[1];
        }
        echo json_encode($result);
	}

	public function sleep_set(){
        if(($_REQUEST['starthour'].":".$_REQUEST['startminute'] === $_REQUEST['endhour'].":".$_REQUEST['endminute']))
        {
            $this->error("休眠开始时间和结束时间不能相同！");
        }
        $ids = I('post.device_ids','','strip_tags');
        $ids_arr = explode(",", $ids);
    	$sleep_time_start = $_REQUEST['starthour'].":".$_REQUEST['startminute'];
	    $sleep_time_end = $_REQUEST['endhour'].":".$_REQUEST['endminute'];
	    $sleep_status = I('post.sleep_status');
	    $sleep_marked_word = I('post.sleep_marked_word','','strip_tags');
	    $sleep_countdown_time = I('post.sleep_countdown_time','','strip_tags');
	    $sleep_imageid = I('post.macimageid','','strip_tags');
        D("device_sleep")->startTrans();
        if (count($ids_arr) == 1) { //单挑记录操作
		    $data['sleep_time_start'] = $sleep_time_start;
		    $data['sleep_time_end'] = $sleep_time_end;
		    $data['sleep_status'] = $sleep_status;
		    $data['sleep_marked_word'] = $sleep_marked_word;
		    $data['sleep_countdown_time'] = $sleep_countdown_time;
		    $data['sleep_imageid'] = $sleep_imageid;
	        if ($ids == 0) { //新增
	        	$data['mac'] = I('post.addmac','','strtoupper');
	        	$result = D("device_sleep")->data($data)->add();
	        }else{  //修改
		        $map['zxt_device_sleep.id'] = $ids;
		        $result = D("device_sleep")->where($map)->setField($data);
	        }
        }elseif(count($ids_arr)>1){
        	$addmac_arr = explode(",", I('post.addmac','','strtoupper'));
        	$sql = "INSERT INTO `zxt_device_sleep`(`id`,`mac`,`sleep_status`,`sleep_time_start`,`sleep_time_end`,`sleep_marked_word`,`sleep_countdown_time`,`sleep_imageid`) VALUES";
            $count = count($ids_arr);
        	for ($i=0; $i < $count; $i++) { 
        		$sql .= "(".$ids_arr[$i].",'".$addmac_arr[$i]."',".$_REQUEST['sleep_status'].",'".$sleep_time_start."','".$sleep_time_end."','".$sleep_marked_word."','".$sleep_countdown_time."',".$sleep_imageid."),";
        	}
        	$sql = rtrim($sql,",");
            $sql .= " ON DUPLICATE KEY UPDATE `mac`=VALUES(mac),`sleep_status`=VALUES(sleep_status),`sleep_time_start`=VALUES(sleep_time_start),`sleep_time_end`=VALUES(sleep_time_end),`sleep_marked_word`=VALUES(sleep_marked_word),`sleep_countdown_time`=VALUES(sleep_countdown_time),`sleep_imageid`=VALUES(sleep_imageid)";
        	$result = D("device_sleep")->execute($sql);
        }
	    
        if($result !== false){
            D("device_sleep")->commit();
            $this->success("恭喜，操作成功！");
        }else{
            D("device_sleep")->rollback();
            $this->error("操作失败！");
        }
    }

    public function sleep_set_d(){
        $model = M(CONTROLLER_NAME);
        $macidlist = trim(I('post.macid','','strip_tags'),",");
        if(empty($macidlist)){
            $this->error('请选择需要下推的设备');
        }
        if(($_REQUEST['starthour'].":".$_REQUEST['startminute'] === $_REQUEST['endhour'].":".$_REQUEST['endminute'])){
            $this->error("休眠开始时间和结束时间不能相同！");
        }
        $model->startTrans();
        $mac_arr = explode(",", $macidlist);
        $sleep_time_start = $_REQUEST['starthour'].":".$_REQUEST['startminute'];
        $sleep_time_end = $_REQUEST['endhour'].":".$_REQUEST['endminute'];
        foreach ($mac_arr as $key => $value) {
        	$data[$key]['mac'] = $value;
        	$data[$key]['sleep_time_start'] = $sleep_time_start;
        	$data[$key]['sleep_time_end'] = $sleep_time_end;
        	$data[$key]['sleep_status'] = I('post.sleep_status','','strip_tags');
        	$data[$key]['sleep_marked_word'] = I('post.sleep_marked_word','','strip_tags');
        	$data[$key]['sleep_countdown_time'] = I('post.sleep_countdown_time','','strip_tags');
        	$data[$key]['sleep_imageid'] = I('post.macimageid','','strip_tags');
        }
        $result = $model->addAll($data);
        if($result !== false){
        	$model->commit();
            $this->success("恭喜，操作成功！",U('index'));
        }else{
        	$model->rollback();
            $this->error("操作失败！",U('index'));
        }
    }

    // 删除记录
    public function delete(){
        if (empty($_POST['sleepid']['0'])) {
            $this->error('参数错误');
        }
        $where['id'] = $_POST['sleepid']['0'];
        $result = D("device_sleep")->where($where)->delete();
        if ($result !== false) {
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    //休眠设置index 没有选中任何一条记录进行操作时
    public function sleepSetIndex(){
        $Region = D ( 'Region' );
        $plist = $Region->where('pid=0')->order("sort asc")->field('id,name')->select();
        $imagemodel = D('DeviceMacImage');
        $imageinfo = $imagemodel->field('*')->select();
        $this->assign('imageinfo',$imageinfo);
        $this->assign("plist",$plist);
        $this->_assign_hour_minute();
        $this->display();
    }

    //获取城市列表
    public function get_city_hotel(){
        $pid = $_REQUEST['pid'];
        $Region = D("Region");
        $citylist = $Region->where('pid='.$pid)->order("sort asc")->field('id,name')->select();
        $hotellist = $this->get_hotel_by_area($pid,1);
        $data['citylist'] = $citylist;
        $data['hotellist'] = $hotellist;
        echo json_encode($data);
    }

    //查询酒店
    public function get_hotel_by_area($areaid = false, $type = false){
        $Hotel = D("Hotel");
        if($type == 1){//省份
            $map['provinceid'] = $areaid;
        }elseif($type == 2){//城市
            $map['cityid'] = $areaid;
        }else{
            return false;
            die();
        }
        $hotellist = $Hotel->field('id,hotelname')->where($map)->select();
        return $hotellist;
    }

    //查询酒店 ajax
    public function ajax_findHotel_byArea(){
        $areaid = I('post.areaid','','intval');
        $type = I('post.type','','intval');

        if($areaid == 0 || empty($areaid)){
            echo -1;
            die();
        }

        $map = array();
        if($type == 1){//省份
            $map['provinceid'] = $areaid;
        }elseif($type == 2){//城市
            $map['cityid'] = $areaid;
        }else{
            echo -1;
            die();
        }

        $Hotel = D("Hotel");
        $hotellist = $Hotel->field('id,hotelname')->where($map)->select();
        if(empty($hotellist)){
            echo 0;
            die();
        }else{
            echo json_encode($hotellist);
            die();
        }
    }

    //根据provinceid 或 cityid 或 hotelname 或 hotelid 查询device
    public function search_device(){
        $map = array();
        $hotel = D("hotel");
        $device = D("device");
        $provinceid = I('post.provinceid','','intval');
        $cityid = I('post.cityid','','intval');
        $hotelname = I('post.hotelname','','strip_tags');
        $hotelid = I('post.hotelid','','intval');
        if(!empty($hotelname)){
            $map['zxt_hotel.hotelname']= array("like","%".$hotelname."%");
            $data = $device->field('zxt_hotel.hotelname,zxt_device.id,zxt_device.room,zxt_device.mac')->where($map)->join('left join zxt_hotel ON zxt_device.hid=zxt_hotel.hid')->select();
        }
        if($hotelid !=0 && !empty($hotelid)){
            $map['zxt_hotel.id'] = $hotelid;
            $data = $hotel->field('zxt_device.id,zxt_device.room,zxt_device.mac,zxt_hotel.hotelname')->where($map)->join('zxt_device ON zxt_hotel.hid = zxt_device.hid')->select();
        }
        if(!empty($cityid)){
            $map['zxt_hotel.cityid'] = $cityid;
            $hidlist = $hotel->field('hid')->where($map)->select();
            if(empty($hidlist)){
                echo 0;
                die();
            }
            $array = array();
            foreach ($hidlist as $key => $value) {
                array_push($array, $value['hid']);
            }
            $where['zxt_hotel.hid'] = array('in',$array);
            $data = $device->field('zxt_hotel.hotelname,zxt_device.id,zxt_device.room,zxt_device.mac')->where($where)->join('left join zxt_hotel ON zxt_device.hid=zxt_hotel.hid')->order('zxt_device.hid')->select();
        }
        if(!empty($provinceid)){
            $map['zxt_hotel.provinceid'] = $provinceid;
            $hidlist = $hotel->field('hid')->where($map)->select();
            if(empty($hidlist)){
                echo 0;
                die();
            }
            $array = array();
            foreach ($hidlist as $key => $value) {
                array_push($array, $value['hid']);
            }
            $where['zxt_hotel.hid'] = array('in',$array);
            $data = $device->field('zxt_hotel.hotelname,zxt_device.id,zxt_device.room,zxt_device.mac')->where($where)->join('left join zxt_hotel ON zxt_device.hid=zxt_hotel.hid')->order('zxt_device.hid')->select();
        }
        if(!empty($data)){
            $ret = array();
            foreach ($data as $key => $value) {
                $ret[$value['hotelname']][] = $value;
            }
            echo json_encode($ret);
        }else{
            echo 0;
        }
    }

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
}