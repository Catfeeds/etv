<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 设备信息控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
class DeviceController extends ComController {
    //查询
    public function _map(){
        $map = array();
        $data = session(session_id());
        $hotelid=0;
        if(!empty($_POST['hid'])) {
            if ($_POST['hid'] != 'hid-1gg') { //自定义查询所有酒店
                $map['hid'] = $_POST['hid'];
                $this->assign('hid',$_POST['hid']);
                $vo=M('hotel')->getByHid($_POST['hid']);
                $data['content_hid'] = $_POST['hid'];
                session(session_id(),$data);
            }else{
                $map = array();
                $data['content_hid'] = null;
                session(session_id(),$data);
            }
        }else{
            if(!empty($data['content_hid'])){
                $map['hid'] = $data['content_hid'];
                $vo=M('hotel')->getByHid($data['content_hid']);
                $hotelid=$vo['id'];
            }   
        }

        //新增房间号 mac地址查询
        if(!empty($_POST['room'])){
            $map['room'] = $_POST['room'];
        }
        if(!empty($_POST['mac'])){
            $map['mac'] = array("LIKE","%{$_POST['mac']}%");;
        }
        if (!empty($_POST['search_type']) ) {
            $keyword = I('post.keyword','','strip_tags');
            if(!empty(trim($keyword))){
                if($_POST['search_type'] == 'wifi_security'){   
                    if(trim($keyword) == 'psk2'){
                        $map['wifi_passwd'] = array('neq','');
                    }elseif(trim($keyword) == 'none'){
                        $map['wifi_passwd'] = array('eq','');
                    }else{
                        $map['id'] = 0;
                    }     
                }elseif ($_POST['search_type'] == 'wifi_status') {
                    if(trim($keyword) == '启用'){
                        $map['wifi_status'] = array('eq',1);
                    }elseif(trim($keyword) == '禁用'){
                        $map['wifi_status'] = array('eq',0);
                    }else{
                        $map['wifi_status'] = array('eq',2);
                    }
                }else{
                    $map[$_POST['search_type']] = array("like","%".trim(I('post.keyword','','strip_tags'))."%");
                }
            }
        }
        $hotelid=$vo['id'];
        $category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
        $category = $tree->get_tree(0,$str,$hotelid);
        $this->assign('pHotel',$category);
        return $map;
    }
    public function index(){
        $model = M(CONTROLLER_NAME);
        $map = $this->_map();
        $hid_condition = $this->isHotelMenber();
        if($hid_condition){
            $map['hid'] = $hid_condition;
        }
        $list = $this->_list($model,$map,10);
        $this -> assign("list",$list);

        $imagemodel = D('DeviceMacImage');
        $imageinfo = $imagemodel->field('*')->select();
        $this->assign('imageinfo',$imageinfo);
        if(!empty($map['hid'])){
            $map['online'] = 1;
            $onlineNum = D("device")->where($map)->count();
        }else{
            $onlineNum = null;
        }
        $this->assign('onlineNum',$onlineNum);

        $this->_assign_hour_minute();
        $this -> display();
    }
    public function _before_add(){
        $category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
        $category = $tree->get_tree(0,$str,0);
        $this->assign('pHotel',$category);
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
        $data['id'] = I('post.id','','intval');
        $data['hid'] =I('post.hid','','strip_tags');
        $data['room'] = I('post.room','','strip_tags');
        $data['mac'] = I('post.mac','','strip_tags');
        $data['server_url'] = I('post.server_url','','strip_tags');
        $data['itv_mode'] = I('post.itv_mode','','strip_tags');
        $data['itv_pppoe_user'] = I('post.itv_pppoe_user','','strip_tags');
        $data['itv_pppoe_pwd'] = I('post.itv_pppoe_pwd','','strip_tags');
        $data['itv_pppoe_ip'] = I('post.itv_pppoe_ip','','strip_tags');
        $data['itv_dhcp_ip'] = I('post.itv_dhcp_ip','','strip_tags');
        $data['itv_dhcp_plus_ip'] = I('post.itv_dhcp_plus_ip','','strip_tags');
        $data['itv_static_ip'] = I('post.itv_static_ip','','strip_tags');
        $data['wan_dhcp_ip'] = I('post.wan_dhcp_ip','','strip_tags');
        $data['wan_static_ip'] = I('post.wan_static_ip','','strip_tags');
        $data['vlan_status'] = I('post.vlan_status','','strip_tags');
        $data['wifi_ssid'] = I('post.wifi_ssid','','strip_tags');
        $data['wifi_passwd'] = I('post.wifi_passwd','','strip_tags');
        $data['wifi_psk_type'] = I('post.wifi_psk_type','','strip_tags');
        $data['intro'] = I('post.intro','','strip_tags');
        if(!$data['hid']){
            $this->error('警告！所属酒店为必选项目。');
        }
        if(!$data['room']){
            $this->error('警告！房间号必填！');
        }
        if(!$data['mac']){
            $this->error('警告！MAC地址必填！');
        }
        $data['status'] = 0;
        // $data['online'] = 0;
        if($data['id']){
            $model = D('device');
            $vo = $model->where('id='.$data['id'])->field("last_login_time")->find();
            if((time()-$vo['last_login_time'])/60 > 6){
                $data['online'] = 0;
            }
            M(CONTROLLER_NAME)->data($data)->where('id='.$data['id'])->save();
            addlog('修改设备信息，ID：'.$data['id']);
        }else{
            $aid = M(CONTROLLER_NAME)->data($data)->add();
            addlog('添加设备信息，ID：'.$aid);
        }
        $this->success('恭喜，操作成功！',U('index'));
    }
    public function detail(){
        $model = M(CONTROLLER_NAME);
        $list = $model->getById($_REQUEST['id']);
        $list['last_login_time'] = date("Y-m-d H:i:s",$list['last_login_time']);
        $list['online'] = get_is_online($list['online']);
        $list['status'] = get_status($list['status']);
        $list['sleep_status'] = get_status($list['sleep_status']);
        echo json_encode($list);
    }
    public function reboot(){
        $model = M(CONTROLLER_NAME);
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if($ids){
            if(is_array($ids)){
                $ids = implode(',',$ids);
                $map['id']  = array('in',$ids);
            }else{
                $map = 'id='.$ids;
            }
            $data = array('command'=>'reboot','command_result'=>-1);
            $result = $model->where($map)->setField($data);
            if($result !== false){
                addlog('重启'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
                $this->success('恭喜，操作成功！');
            }else{
                $this->error('操作失败，参数错误！');
            }
        }else{
            $this->error('参数错误！');
        }
    }
    public function clearvolume(){
        $model = M(CONTROLLER_NAME);
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if($ids){
            if(is_array($ids)){
                $ids = implode(',',$ids);
                $map['id']  = array('in',$ids);
            }else{
                $map = 'id='.$ids;
            }
            $data = array('command'=>'clearvolume','command_result'=>-1);
            $result = $model->where($map)->setField($data);
            if($result !== false){
                addlog('清除内存，'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
                $this->success('恭喜，操作成功！');
            }else{
                $this->error('操作失败，参数错误！');
            }
        }else{
            $this->error('参数错误！');
        }
    }

    //自选重启
    public function reboot_d(){
        $model = M(CONTROLLER_NAME);
        $ids = I('post.macid','','strip_tags');
        if(empty($ids)){
            $this->error('请选择需要修改的设备');
        }
        $map['id']  = array('in',$ids);
        $data = array('command'=>'reboot','command_result'=>-1);
        $result = $model->where($map)->setField($data);
        if($result !== false){
            addlog('重启'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
            $this->success('恭喜，操作成功！');
        }else{
            $this->error('操作失败，参数错误！');
        }
    }
    //自选清除内存
    public function clearvolume_d(){
        $model = M(CONTROLLER_NAME);
        $ids = I('post.macid','','strip_tags');
        if(empty($ids)){
            $this->error('请选择需要修改的设备');
        }
        $map['id']  = array('in',$ids);
        $data = array('command'=>'clearvolume','command_result'=>-1);
        $result = $model->where($map)->setField($data);
        if($result !== false){
            addlog('清除内存'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
            $this->success('恭喜，操作成功！');
        }else{
            $this->error('操作失败，参数错误！');
        }
    }
    public function modify_para(){
        $model = M(CONTROLLER_NAME);
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if($ids){
            if(is_array($ids)){
                $ids = implode(',',$ids);
                $map['id']  = array('in',$ids);
            }else{
                $map = 'id='.$ids;
            }
            $data = array('command'=>'modify_para','command_result'=>-1);
            $result = $model->where($map)->setField($data);
            if($result !== false){
                addlog('下推参数'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
                $this->success('恭喜，操作成功！');
            }else{
                $this->error('操作失败，参数错误！');
            }
        }else{
            $this->error('参数错误！');
        }
    }
    public function modify_para_d(){
        $model = M(CONTROLLER_NAME);
        $ids = I('post.macid','','strip_tags');
        if(empty($ids)){
            $this->error('请选择需要下推的设备');
        }
        $map['id']  = array('in',$ids);
        $data = array('command'=>'modify_para','command_result'=>-1);
        $result = $model->where($map)->setField($data);
        if($result !== false){
            addlog('下推参数'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
            $this->success('恭喜，操作成功！');
        }else{
            $this->error('操作失败，参数错误！');
        }
        
    }
    public function sleep_set(){
        $model = M(CONTROLLER_NAME);
        $ids=trim($_REQUEST['device_ids'],',');
        $map['zxt_device.id'] = array("in",$ids);
        if($_REQUEST['sleep_status']==1 && ($_REQUEST['starthour'].":".$_REQUEST['startminute'] === $_REQUEST['endhour'].":".$_REQUEST['endminute'])){
            $this->error("休眠开始时间和结束时间不能相同！");
        }
        $sleep_time=$_REQUEST['starthour'].":".$_REQUEST['startminute'].'-'.$_REQUEST['endhour'].":".$_REQUEST['endminute'];
        $data['sleep_time'] = $sleep_time;
        $data['sleep_status'] = $_REQUEST['sleep_status'];
        $data['sleep_marked_word'] = I('post.sleep_marked_word','','strip_tags');
        $data['sleep_countdown_time'] = I('post.sleep_countdown_time','','strip_tags');
        $data['sleep_imageid'] = I('post.macimageid','','strip_tags');
        $result = $model->where($map)->setField($data);

        //获取所有hid
        $vo = $model->field('hid')->where($map)->group('hid')->select();
        $arrhid = array();
        foreach ($vo as $key => $value) {
            array_push($arrhid, $value['hid']);
        }
        $maphid['hid'] = array('in',$arrhid);
        //查询hid及其对应的size
        $imgsizelist = M("device")->field('hid,max(image_size)')->join('zxt_device_mac_image ON zxt_device.sleep_imageid = zxt_device_mac_image.id')->group('hid')->where($maphid)->select();
        foreach ($imgsizelist as $key => $value) {
            $updatesize = M("hotel_volume")->where(array('hid'=>$value['hid']))->setField('devicebg_size',$value['max(image_size)']);
        }
        
        if($result !== false){
            addlog('休眠设置'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
            $this->success("恭喜，操作成功！");
        }else{
            $this->error("操作失败！");
        }
    }
    public function sleep_set_d(){
        $model = M(CONTROLLER_NAME);
        $ids = I('post.macid','','strip_tags');
        if(empty($ids)){
            $this->error('请选择需要下推的设备');
        }
        $map['zxt_device.id']  = array('in',$ids);
        if($_REQUEST['sleep_status']==1 && ($_REQUEST['starthour'].":".$_REQUEST['startminute'] === $_REQUEST['endhour'].":".$_REQUEST['endminute'])){
            $this->error("休眠开始时间和结束时间不能相同！");
        }
        $sleep_time=$_REQUEST['starthour'].":".$_REQUEST['startminute'].'-'.$_REQUEST['endhour'].":".$_REQUEST['endminute'];
        $data['sleep_time'] = $sleep_time;
        $data['sleep_status'] = $_REQUEST['sleep_status'];
        $data['sleep_marked_word'] = I('post.sleep_marked_word','','strip_tags');
        $data['sleep_countdown_time'] = I('post.sleep_countdown_time','','strip_tags');
        $data['sleep_imageid'] = I('post.macimageid','','strip_tags');
        $result = $model->where($map)->setField($data);

        //获取所有hid
        $vo = $model->field('hid')->where($map)->group('hid')->select();
        $arrhid = array();
        foreach ($vo as $key => $value) {
            array_push($arrhid, $value['hid']);
        }
        $maphid['hid'] = array('in',$arrhid);
        //查询hid及其对应的size
        $imgsizelist = M("device")->field('hid,max(image_size)')->join('zxt_device_mac_image ON zxt_device.sleep_imageid = zxt_device_mac_image.id')->group('hid')->where($maphid)->select();
        foreach ($imgsizelist as $key => $value) {
            $updatesize = M("hotel_volume")->where(array('hid'=>$value['hid']))->setField('devicebg_size',$value['max(image_size)']);
        }

        if($result !== false){
            addlog('休眠设置'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
            $this->success("恭喜，操作成功！");
        }else{
            $this->error("操作失败！");
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

    /*2017.03.04新增功能
    * 新增图片上传功能
    * 新增删除调用功能
    */
    public function upload(){
        $callback = array();
        if (!empty($_FILES[$_REQUEST["name"]]["name"])) {
            $upload = new \Think\Upload();
            $upload->maxSize=104857600;
            $upload->exts=array('jpg','png','jpeg');
            $upload->rootPath='./Public/';
            $upload->savePath='./upload/off/';
            $info=$upload->uploadOne($_FILES[$_REQUEST["name"]]);
            if(!$info) {
                $callback['status'] = 0;
                $callback['info'] = $upload->getError();
            }else{
                $callback['status'] = 1;
                $callback['info']="上传成功！";
                $callback['size'] = round($info['size']/1024);
                $callback['storename']=trim($info['savepath'].$info['savename'],'.');
            }
        }else{
            $callback['status'] = 0;
            $callback['info']='缺少文件';
        }
        echo json_encode($callback);
    }
    public function deleteimage(){
        $imagename = $_REQUEST['imagename'];
        $imageurl = dirname(dirname(dirname(__DIR__))).'/Public'.$imagename;  
        if(file_exists($imageurl)){
            unlink($imageurl);
            echo json_encode($imageurl);;
        }else{
            echo json_encode($imageurl);
        }
    }
    public function setSleepImage(){
        $model = D('DeviceMacImage');
        $image_name = I('post.image_name','','strip_tags');
        $image_path = I('post.filepath','','strip_tags');
        $size = I('post.size','','intval');
        $image_size = round($size/1024,3);
        if (!empty($image_name) && !empty($image_path)) {
            $data = array('image_name' => $image_name, 'image_path' => $image_path,'image_size'=> $image_size);
            $result = $model->data($data)->add();
            if($result !== false){
                $this->success("操作成功");
            }else{
                $this->error("操作失败");
            }
        }else{
            $this->error("请上传图片和填写图片名称");
        }
    } 
    public function toSetSleep(){
        $model = D(CONTROLLER_NAME);
        $map['zxt_device.id'] = I('post.deviceid','','intval');
        $result = $model->join('zxt_device_mac_image ON zxt_device.sleep_imageid = zxt_device_mac_image.id')->where($map)->field('zxt_device.id,sleep_status,sleep_time,sleep_marked_word,sleep_countdown_time,sleep_imageid,image_name,image_path')->select();
        //拆分时间
        if(empty($result)){
            echo '';
            die();
        }
        if(!empty($result[0]['sleep_time'])){
            $time = explode('-', $result[0]['sleep_time']);
            $starttime = explode(':', $time['0']);
            $endtime = explode(":", $time["1"]);
            $result[0]['starttime_h'] = $starttime[0];
            $result[0]['starttime_m'] = $starttime[1];
            $result[0]['endtime_h'] = $endtime[0];
            $result[0]['endtime_m'] = $endtime[1];

        }
        echo json_encode($result);
    }
    /*WIFI信息功能*/
    public function wifiindex(){
        $model = D(CONTROLLER_NAME);
        $map = $this->_map();
        $hid_condition = $this->isHotelMenber();
        if($hid_condition){
            $map['hid'] = $hid_condition;
        }
        $list = $this->_list($model,$map,10);
        $this->assign('list',$list);
        $this->display();
    }
    public function wifiSettingOne(){
        $id = $_REQUEST['ids'];
        if(count($id) != 1){
            $this->error("请勾选一条信息,单条设置WIFI",'wifiindex');
            die();
        }
        $model = D(CONTROLLER_NAME);
        $vo = $model->getById($id['0']);
        $this->assign('vo',$vo);
        $this->display();
    }
    public function doWifiSettingOne(){
        $model = D(CONTROLLER_NAME);
        $data['wifi_status'] =I('post.wifi_status');
        $data['wifi_ssid'] =I('post.wifi_ssid','','strip_tags');
        $data['wifi_passwd'] =I('post.wifi_passwd','','strip_tags');
        $map['id'] = I('post.id');
        $result = $model->where($map)->setField($data);
        if($result !== false){
            addlog('WIFI单条设置-'.CONTROLLER_NAME.'表数据，数据ID：'.$map['id']);
            $this->success("恭喜，操作成功！","wifiindex");
        }else{
            $this->error("操作失败！");
        }
    }
    public function wifiSettingSome(){
        $ids = $_REQUEST['ids'];
        if(empty($ids)){
            $this->error("请勾选以设置WIFI",'wifiindex');
            die();
        }
        $idsstr = implode(',', $ids);
        $map['id'] = array('in',$idsstr);
        $model = D(CONTROLLER_NAME);
        $list = $model->where($map)->field('id,mac,room')->select();
        $mac = array();
        $room = array();
        foreach($list as $val){
            $mac[] = $val['mac'];
            $room[] = $val['room'];
        }
        $macstr = implode(',', $mac);
        $roomstr = implode(',', $room);
        $this->assign('ids',$idsstr);
        $this->assign('macstr', $macstr);
        $this->assign('roomstr', $roomstr);
        $this->display();
    }
    public function doWifiSettingSome(){
        $model = D(CONTROLLER_NAME);
        $ids = $_REQUEST['ids'];
        $idsarr = explode(',', $ids);
        $roomstr = $_REQUEST['roomstr'];
        $data['wifi_status'] = I('post.wifi_status','0','intval');
        $ssid_way = I('post.ssid_way');
        $security = $_REQUEST['security'];
        $pw_way = $_REQUEST['pw_way'];
        $model->startTrans();
        if($ssid_way == 1){//SSID用房间号设置
            $roomarr = explode(',', $roomstr);
            $wifi_prefix = I('post.wifi_prefix','','strip_tags');
            $wifi_subffix = I('post.wifi_subffix','','strip_tags');
            if($security == 0){ //安全性为none
                foreach ($idsarr as $key => $value) {
                    $data['wifi_ssid'] = $wifi_prefix.$roomarr[$key].$wifi_subffix;
                    $data['wifi_passwd'] = '';
                    $ok = $model->where(array('id'=>$value))->save($data);
                    if($ok === false){
                        $model->rollback();
                        $this->error("提交更改出错",'wifiindex');
                        die();
                    }
                }
                $model->commit();
                addlog('WIFI批量设置-'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
                $this->success('操作成功','wifiindex');
            }elseif($security == 1){ //安全性为psk2
                if($pw_way == 0){ //手动输入密码
                    $data['wifi_passwd'] = I('post.wifi_passwd','','strip_tags');
                    foreach ($idsarr as $key => $value) {
                        $data['wifi_ssid'] = $wifi_prefix.$roomarr[$key].$wifi_subffix;
                        $ok = $model->where(array('id'=>$value))->save($data);
                        if($ok === false){
                            $model->rollback();
                            $this->error("提交更改出错",'wifiindex');
                            die();
                        }
                    }
                    $model->commit();
                    addlog('WIFI批量设置-'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
                    $this->success('操作成功','wifiindex');
                }elseif($pw_way == 1){ //设置SSID为密码
                    foreach ($idsarr as $key => $value) {
                        $data['wifi_ssid'] = $data['wifi_passwd'] = $wifi_prefix.$roomarr[$key].$wifi_subffix;
                         $ok = $model->where(array('id'=>$value))->save($data);
                        if($ok === false){
                            $model->rollback();
                            $this->error("提交更改出错",'wifiindex');
                            die();
                        }
                    }
                    $model->commit();
                    addlog('WIFI批量设置-'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
                    $this->success('操作成功','wifiindex');
                }else{
                    $this->error("密码设置错误,请重试",'wifiindex');
                }
            }else{
                $this->error("wifi安全性设置错误",'wifiindex');
            }
        }elseif($ssid_way == 0){//手动输入SSID
            $data['wifi_ssid'] = I('post.wifi_ssid','','strip_tags');
            if($security == 0){ //安全性none
                $data['wifi_passwd'] = '';
                foreach ($idsarr as $key => $value) {
                    $ok = $model->where(array('id'=>$value))->setField($data);
                    if($ok === false){
                        $model->rollback();
                        $this->error("提交更改出错",'wifiindex');
                        die();
                    }
                }
                $model->commit(); 
                addlog('WIFI批量设置-'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
                $this->success('操作成功',"wifiindex");
            }else{  //安全性为psk2
                if($pw_way == 0){//手动输入密码
                    $data['wifi_passwd'] = I('post.wifi_passwd','','strip_tags');
                    $model->startTrans();
                    foreach ($idsarr as $key => $value) {
                        $ok = $model->where(array('id'=>$value))->setField($data);
                        if($ok === false){
                            $model->rollback();
                            $this->error("提交更改出错",'wifiindex');
                            die();
                        }
                    }
                    $model->commit(); 
                    addlog('WIFI批量设置-'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
                    $this->success('操作成功',"wifiindex");
                }elseif($pw_way == 1){//设置SSID为密码
                    $data['wifi_passwd'] = $data['wifi_ssid'];
                    foreach ($idsarr as $key => $value) {
                        $ok = $model->where(array('id'=>$value))->setField($data);
                        if($ok === false){
                            $model->rollback();
                            $this->error("提交更改出错",'wifiindex');
                            die();
                        }
                    }
                    $model->commit();
                    addlog('WIFI批量设置-'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
                    $this->success('操作成功',"wifiindex");
                }else{  
                    $this->error("设置密码错误，请重试",'wifiindex');
                }
            }
        }else{
            $this->error("SSID设置错误,请重试",'wifiindex');
        }
  
    }
    public function wifiSettingAll(){
        $hid = $_REQUEST['hid'];
        $model = D(CONTROLLER_NAME);
        if(!empty($hid)){
            $this->assign('hid',$hid);
            $this->display();
        }else{
            $this->_map();
            $this->display();
        }
    }
    public function doWifiSettingAll(){
        $model = D(CONTROLLER_NAME);
        $map['hid'] = $_REQUEST['hid'];
        $vo = $model->where($map)->field('id,hid,room')->select();
        if(empty($vo)){
            $this->error("没有设备可更改",'wifiindex');
            die();
        }
        foreach ($vo as $key => $value) {
            $idsarr[] = $value['id'];
            $roomarr[] = $value['room'];
        }
        $idsstr = implode(',', $idsarr);
        $data['wifi_status'] = I('post.wifi_status','0','intval');
        $ssid_way = I('post.ssid_way');
        $security = $_REQUEST['security'];
        $pw_way = $_REQUEST['pw_way'];
        $model->startTrans();
        if($ssid_way == 1){//SSID用房间号设置
            $roomarr = explode(',', $roomstr);
            $wifi_prefix = I('post.wifi_prefix','','strip_tags');
            $wifi_subffix = I('post.wifi_subffix','','strip_tags');
            if($security == 0){ //安全性为none
                foreach ($idsarr as $key => $value) {
                    $data['wifi_ssid'] = $wifi_prefix.$roomarr[$key].$wifi_subffix;
                    $data['wifi_passwd'] = '';
                    $ok = $model->where(array('id'=>$value))->save($data);
                    if($ok === false){
                        $model->rollback();
                        $this->error("提交更改出错",'wifiindex');
                        die();
                    }
                }
                $model->commit();
                addlog('WIFI统一设置-'.CONTROLLER_NAME.'表数据，数据ID：'.$idsstr);
                $this->success('操作成功','wifiindex');
            }elseif($security == 1){ //安全性为psk2
                if($pw_way == 0){ //手动输入密码
                    $data['wifi_passwd'] = I('post.wifi_passwd','','strip_tags');
                    foreach ($idsarr as $key => $value) {
                        $data['wifi_ssid'] = $wifi_prefix.$roomarr[$key].$wifi_subffix;
                        $ok = $model->where(array('id'=>$value))->save($data);
                        if($ok === false){
                            $model->rollback();
                            $this->error("提交更改出错",'wifiindex');
                            die();
                        }
                    }
                    $model->commit();
                    addlog('WIFI统一设置-'.CONTROLLER_NAME.'表数据，数据ID：'.$idsstr);
                    $this->success('操作成功','wifiindex');
                }elseif($pw_way == 1){ //设置SSID为密码
                    foreach ($idsarr as $key => $value) {
                        $data['wifi_ssid'] = $data['wifi_passwd'] = $wifi_prefix.$roomarr[$key].$wifi_subffix;
                         $ok = $model->where(array('id'=>$value))->save($data);
                        if($ok === false){
                            $model->rollback();
                            $this->error("提交更改出错",'wifiindex');
                            die();
                        }
                    }
                    $model->commit();
                    addlog('WIFI统一设置-'.CONTROLLER_NAME.'表数据，数据ID：'.$idsstr);
                    $this->success('操作成功','wifiindex');
                }else{
                    $this->error("密码设置错误,请重试",'wifiindex');
                }
            }else{
                $this->error("wifi安全性设置错误",'wifiindex');
            }
        }elseif($ssid_way == 0){//手动输入SSID
            $data['wifi_ssid'] = I('post.wifi_ssid','','strip_tags');
            if($security == 0){ //安全性none
                $data['wifi_passwd'] = '';
                foreach ($idsarr as $key => $value) {
                    $ok = $model->where(array('id'=>$value))->setField($data);
                    if($ok === false){
                        $model->rollback();
                        $this->error("提交更改出错",'wifiindex');
                        die();
                    }
                }
                $model->commit(); 
                addlog('WIFI统一设置-'.CONTROLLER_NAME.'表数据，数据ID：'.$idsstr);
                $this->success('操作成功',"wifiindex");
            }else{  //安全性为psk2
                if($pw_way == 0){//手动输入密码
                    $data['wifi_passwd'] = I('post.wifi_passwd','','strip_tags');
                    foreach ($idsarr as $key => $value) {
                        $ok = $model->where(array('id'=>$value))->setField($data);
                        if($ok === false){
                            $model->rollback();
                            $this->error("提交更改出错",'wifiindex');
                            die();
                        }
                    }
                    $model->commit(); 
                    addlog('WIFI统一设置-'.CONTROLLER_NAME.'表数据，数据ID：'.$idsstr);
                    $this->success('操作成功',"wifiindex");
                }elseif($pw_way == 1){//设置SSID为密码
                    $data['wifi_passwd'] = $data['wifi_ssid'];
                    foreach ($idsarr as $key => $value) {
                        $ok = $model->where(array('id'=>$value))->setField($data);
                        if($ok === false){
                            $model->rollback();
                            $this->error("提交更改出错",'wifiindex');
                            die();
                        }
                    }
                    $model->commit(); 
                    addlog('WIFI统一设置-'.CONTROLLER_NAME.'表数据，数据ID：'.$idsstr);
                    $this->success('操作成功',"wifiindex");
                }else{  
                    $this->error("设置密码错误，请重试",'wifiindex');
                }
            }
        }else{
            $this->error("SSID设置错误,请重试",'wifiindex');
        }
    }
        //启用
    public function unlock(){
        $model = M(CONTROLLER_NAME);
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if($ids){
            if(is_array($ids)){
                $ids = implode(',',$ids);
                $map['id']  = array('in',$ids);
            }else{
                $map = 'id='.$ids;
            }
            $result = $model->where($map)->setField("status",1);
            if($result !== false){
                addlog('启用'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
                $this->success('恭喜，启用成功！');
            }else{
                $this->error('启用失败，参数错误！');
            }
        }else{
            $this->error('参数错误！');
        }
    }
    //禁用
    public function lock(){
        $model = M(CONTROLLER_NAME);
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if($ids){
            if(is_array($ids)){
                $ids = implode(',',$ids);
                $map['id']  = array('in',$ids);
            }else{
                $map = 'id='.$ids;
            }
            $result = $model->where($map)->setField("wifi_status",0);
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
    public function uploadxml(){
        $callback = array();
        if (!empty($_FILES[$_REQUEST["name"]]["name"])) {
            $upload = new \Think\Upload();
            $upload->maxSize=104857600;
            $upload->exts=array('xml');
            $upload->rootPath='./Public/';
            $upload->savePath='./upload/xml/';
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
    public function pushxml(){
        $xml = $_REQUEST['xmlpath'];
        if(empty($xml)){
            $this->error('请上传xml文件后再提交','index');
            die();
        }
        $xmlpath =  dirname(dirname(dirname(__DIR__))).'/Public'.$xml;
        if(file_exists($xmlpath)){

            $xmlobject = (array)simplexml_load_file($xmlpath);
            $networkObject = (array)$xmlobject['category'][0];
            $wifiObject = (array)$xmlobject['category'][1];
            $hotelObject = (array)$xmlobject['category'][2];
            $deviceObject = (array)$xmlobject['category'][3];
            $itvObject = (array)$xmlobject['category'][4];
            $setData = array();
            foreach ($networkObject['item'] as $key => $value) {
                if($value['id'] == 'wanpppoeuser'){
                    $setData['wan_pppoe_user'] = $value['value'];
                }
                if($value['id'] == 'wannetmask'){
                    $setData['wan_netmask'] = $value['value'];
                }
                if($value['id'] == 'itvdns1'){
                    $setData['itv_dns1'] = $value['value'];
                }
                if($value['id'] == 'itvdns2'){
                    $setData['itv_dns2'] = $value['value'];
                }
                if($value['id'] == 'itvdhcppasswd'){
                    $setData['itv_dhcp_pwd'] = $value['value'];
                }
                if($value['id'] == 'itvpppoepasswd'){
                    $setData['itv_pppoe_pwd'] = $value['value'];
                }
                if($value['id'] == 'itvnetmask'){
                    $setData['itv_netmask'] = $value['value'];
                }
                if($value['id'] == 'wandns1'){
                    $setData['wan_dns1'] = $value['value'];
                }
                if($value['id'] == 'wandns2'){
                    $setData['wan_dns2'] = $value['value'];
                }
                if($value['id'] == 'wangateway'){
                    $setData['wan_gateway'] = $value['value'];
                }
                if($value['id'] == 'itvpppoeuser'){
                    $setData['itv_pppoe_user'] = $value['value'];
                }
                if($value['id'] == 'wanpppoepasswd'){
                    $setData['wan_pppoe_pwd'] = $value['value'];
                }
                if($value['id'] == 'wanstaticip'){
                    $setData['wan_static_ip'] = $value['value'];
                }
                if($value['id'] == 'itvdhcpuser'){
                    $setData['itv_dhcp_user'] = $value['value'];
                }
                if($value['id'] == 'itvstaticip'){
                    $setData['itv_static_ip'] = $value['value'];
                }
                if($value['id'] == 'networkmode'){
                    $setData['network_mode'] = $value['value'];
                }
                if($value['id'] == 'itvgateway'){
                    $setData['itv_gateway'] = $value['value'];
                }
            }
            foreach ($deviceObject['item'] as $key => $value) {
                if($value['id'] == 'lanstaticip'){
                    $setData['lanstaticip'] = $value['value'];
                }
                if($value['id'] == 'ethdns2'){
                    $setData['ethdns2'] = $value['value'];
                }
                if($value['id'] == 'ethdns1'){
                    $setData['ethdns1'] = $value['value'];
                }
                if($value['id'] == 'ethgateway'){
                    $setData['ethgateway'] = $value['value'];
                }
                if($value['id'] == 'lannetmask'){
                    $setData['lannetmask'] = $value['value'];
                }
            }
            foreach ($itvObject['item'] as $key => $value) {
                if($value['id'] == 'update_url'){
                    $setData['update_url'] = $value['value'];
                }
                if($value['id'] == 'aaa_password'){
                    $setData['aaa_passwd'] = $value['value'];
                }
                if($value['id'] == 'isvlan'){
                    $setData['vlan_status'] = $value['value'];
                }
                if($value['id'] == 'area_code'){
                    $setData['area_code'] = $value['value'];
                }
                if($value['id'] == 'aaa_account'){
                    $setData['aaa_account'] = $value['value'];
                }
                if($value['id'] == 'aaa_url_backup'){
                    $setData['aaa_url_backup'] = $value['value'];
                }
                if($value['id'] == 'aaa_url'){
                    $setData['aaa_url'] = $value['value'];
                }
                if($value['id'] == 'vlanid'){
                    $setData['vlanid'] = $value['value'];
                }
                if($value['id'] == 'nm_url'){
                    $setData['nm_url'] = $value['value'];
                }
            }
            foreach ($wifiObject['item'] as $key => $value) {
                if($value['id'] == 'wifipsktype'){
                    $setData['wifi_psk_type'] = $value['value'];
                }
                if($value['id'] == 'wifipasswd'){
                    $setData['wifi_passwd'] = $value['value'];
                }
                if($value['id'] == 'wifissid'){
                    $setData['wifi_ssid'] = $value['value'];
                }
            }
            foreach ($hotelObject['item'] as $key => $value) {
                if ($value['id'] == 'server_url' ) {
                    $setData['server_url'] = $value['value'];
                }
                if ($value['id'] == 'hotel_number' ) {
                    $setData['hid'] = $value['value'];
                }
                if ($value['id'] == 'room_number' ) {
                    $setData['room'] = $value['value'];
                }
            }
            if (!empty($setData)) {
                $map_hid = (array)$setData['hid'];
                $map_room = (array)$setData['room'];
                if(!empty($map_hid[0]) && !empty($map_room[0])){
                    $map = array();
                    $model = D(CONTROLLER_NAME);
                    $map['hid'] = $map_hid[0];
                    $map['room'] = $map_room[0];
                    $list = $model->where($map)->find();
                    $this->assign('vo',$list);
                    $this->assign('list',$setData);
                    $this->display();
                }else{
                    $this->error('XML文件格式有误 未含有Hid或room','index');
                }
                
            }else{
                $this->error('XML文件数据有误 请检查文件','index');
            }
        }else{
            $this->error('读取文件出错');
        }  
    }

    public function xmlupdate(){

        $stbData=array();
        $id = I('post.id','','intval');
        $model = D(CONTROLLER_NAME);
        $stbData['hid'] = I('post.hid','','strip_tags');
        $stbData['room'] = I('post.room','','strip_tags');
        $stbData['mac'] = I('post.mac','','strip_tags');
        $stbData['aaa_account'] = I('post.aaa_account','','strip_tags');
        $stbData['aaa_passwd'] = I('post.aaa_passwd','','strip_tags');
        // $stbData['dev_desc'] = I('post.dev_desc','','strip_tags');
        // $stbData['firmware_version'] = I('post.firmware_version','','strip_tags');
        // $stbData['model'] = I('post.model','','strip_tags');
        // $stbData['brand'] = I('post.brand','','strip_tags');
        // $stbData['board'] = I('post.board','','strip_tags');
        $stbData['network_mode'] = I('post.network_mode','','strip_tags');
        // $stbData['itv_mode'] = I('post.itv_mode','','strip_tags');
        // $stbData['wan_mode'] = I('post.wan_mode','','strip_tags');
        $stbData['itv_dhcp_user'] = I('post.itv_dhcp_user','','strip_tags');
        $stbData['itv_dhcp_pwd'] = I('post.itv_dhcp_pwd','','strip_tags');
        $stbData['itv_pppoe_user'] = I('post.itv_pppoe_user','','strip_tags');
        $stbData['itv_pppoe_pwd'] = I('post.itv_pppoe_pwd','','strip_tags');
        $stbData['itv_static_ip'] = I('post.itv_static_ip','','strip_tags');
        $stbData['itv_netmask'] = I('post.itv_netmask','','strip_tags');
        $stbData['itv_gateway'] = I('post.itv_gateway','','strip_tags');
        $stbData['itv_dns1'] = I('post.itv_dns1','','strip_tags');
        $stbData['itv_dns2'] = I('post.itv_dns2','','strip_tags');
        $stbData['wan_pppoe_user'] = I('post.wan_pppoe_user','','strip_tags');
        $stbData['wan_pppoe_pwd'] = I('post.wan_pppoe_pwd','','strip_tags');
        $stbData['wan_static_ip'] = I('post.wan_static_ip','','strip_tags');
        $stbData['wan_netmask'] = I('post.wan_netmask','','strip_tags');
        $stbData['wan_gateway'] = I('post.wan_gateway','','strip_tags');
        $stbData['wan_dns1'] = I('post.wan_dns1','','strip_tags');
        $stbData['wan_dns2'] = I('post.wan_dns2','','strip_tags');
        $stbData['last_login_ip'] = $this->get_client_ip();
        $stbData['last_login_time'] = time();
        $stbData['online'] = 1;
        // $stbData['itv_version'] = I('post.itv_version','','strip_tags');
        // $stbData['route_firmware_version'] = I('post.route_firmware_version','','strip_tags');
        // $stbData['wan_pppoe_ip'] = I('post.wan_pppoe_ip','','strip_tags');
        // $stbData['vlan_number'] = I('post.vlan_number','','strip_tags');
        // $stbData['itv_pppoe_ip'] = I('post.itv_pppoe_ip','','strip_tags');
        // $stbData['itv_dhcp_ip'] = I('post.itv_dhcp_ip','','strip_tags');
        // $stbData['itv_dhcp_plus_ip'] = I('post.itv_dhcp_plus_ip','','strip_tags');
        // $stbData['wan_dhcp_ip'] = I('post.wan_dhcp_ip','','strip_tags');
        // $stbData['vlan_status'] = I('post.vlan_status','','strip_tags');
        $stbData['server_url'] = I('post.server_url','','strip_tags');
        // $stbData['boot_time'] = I('post.boot_time','','strip_tags');
        $stbData['wifi_ssid'] =   I('post.wifi_ssid','','strip_tags');
        $stbData['wifi_psk_type'] =   I('post.wifi_psk_type','','strip_tags');
        $stbData['wifi_passwd'] =   I('post.wifi_passwd','','strip_tags');

        if(empty($id)){
            $result = $model->data($stbData)->add();
            if($result){
                addlog('导入XML文件到设备，ID：'.$result);
            }
        }else{
            $map['id'] = $id;
            $result = $model->where($map)->save($stbData);
            if($result !== false){
                addlog('XML文件更改设备信息，ID: '.$id);
            }
        }
        if($result === false){
            $this->error('导入数据失败','index');
        }else{
            $this->success('导入数据成功','index');
        }
    }

    public function checkmac(){
        $mac = trim($_POST['mac']);
        if(empty($mac)){
            echo 2;
            die();
        }
        $map['mac'] = $mac;
        $model = D(CONTROLLER_NAME);
        $list = $model->where($map)->find();
        if(empty($list)){
            echo 1;
        }else{
            echo 0;
        }

    }
    //重启主页
    public function rebootIndex(){
        $Region = D ( 'Region' );
        $plist = $Region->where('pid=0')->order("sort asc")->field('id,name')->select();
        $this->assign("plist",$plist);
        $this->display();
    }
     //清除内存主页
    public function clearvolumeIndex(){
        $Region = D ( 'Region' );
        $plist = $Region->where('pid=0')->order("sort asc")->field('id,name')->select();
        $this->assign("plist",$plist);
        $this->display();
    }
    //下推参数主页
    public function modifyParaIndex(){
        $Region = D ( 'Region' );
        $plist = $Region->where('pid=0')->order("sort asc")->field('id,name')->select();
        $this->assign("plist",$plist);
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
    //休眠设置index
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
    //休眠背景图主页
    public function deviceimgIndex(){
        $model = D("device_mac_image");
        $list = $model->select();
        $this->assign('list',$list);
        $this->display();
    }
    //修改休眠背景图主页
    public function deviceimgedit(){
        $ids = $_REQUEST['ids']['0'];
        $model = D("device_mac_image");
        $map['id'] = $ids;
        $list = $model->where($map)->find();
        $this->assign('vo',$list);
        $this->display();
    }
    //保存休眠背景图
    public function deviceimgupdate(){
        $id = I('post.id','','intval');
        if(empty($id)){
            $this->error("保存休眠背景图失败");
            die();
        }
        $model = D("device_mac_image");
        $data['image_name'] = I('post.title','','strip_tags');
        $data['image_path'] = I('post.filepath','','strip_tags');
        $size = I('post.size','','intval');
        $data['image_size'] = round($size/1024,3);
        $map['id'] = $id;
        $vo = $model->getById($id);
        $result = $model->data($data)->where($map)->save();
        if($data['image_size'] != $vo['image_size']){
            $updatesize = true;
            $hidlist = M("device")->field('hid')->where(array('sleep_imageid'=>$vo['id']))->select();
            $arrhid = array();
            foreach ($hidlist as $key => $value) {
                array_push($arrhid, $value['hid']);
            }
            $maphid['hid'] = array('in',$arrhid);
            $imgsizelist = M("device")->field('hid,max(image_size)')->join('zxt_device_mac_image ON zxt_device.sleep_imageid = zxt_device_mac_image.id')->group('hid')->where($maphid)->group('hid')->select();
            foreach ($imgsizelist as $key => $value) {
                $updatesize = M("hotel_volume")->where(array('hid'=>$value['hid']))->setField('devicebg_size',$value['max(image_size)']);
            }
        }

        if($result !== false && $updatesize !== false){
            if ($data['image_path'] != $vo['image_path']) {
                @unlink(FILE_UPLOAD_ROOTPATH.$vo['image_path']);
            }
            $this->success('保存成功',"deviceimgIndex");
        }else{
            $this->error('保存失败');
        }
    }


    /*
     *2017.05.24新增
     *ajax获取设备apk信息
     */
    
    public function getapklist_d(){
        $id = I('get.id');
        $deviceModel = D("device");
        $deviceapkModel = D("device_apk");
        $appstoreModel = D("appstore");
        $devicemap['zxt_device.id'] = $id;
        $devicefield = "zxt_device_apk.*";

        $apkidvo = $deviceModel->where($devicemap)->join('zxt_device_apk ON zxt_device.mac = zxt_device_apk.mac')->field($devicefield)->find();//私用的apk列表

        $allmap['mac'] = "all";
        $allapkid = $deviceapkModel->where($allmap)->field('apk_id')->find();//公用的apk列表

        $apkid_list = array();//需要安装的apk列表
        $install_apkid_list = array();//已安装的apk列表 mac已安装apk列表只能在此处找到
        $arr_apkidvo = array();//私用需安装的apk列表 
        $arr_allapkid = array();//公用需要安装的apk列表
        
        //私用
        if(!empty($apkidvo)){
            $arr1 = array_filter(explode(',',$apkidvo['apk_id']));
            //需安装列表
            if(!empty($arr1)){
                $appstoreMap1['app_identifier'] = array('in',$arr1);
                $appnamelist1 = $appstoreModel->where($appstoreMap1)->field('app_name')->select();
                foreach ($appnamelist1 as $key => $value) {
                    array_push($apkid_list, $value['app_name']);
                }
            }
            //已安装列表
            $arr2 = array_filter(explode(',', $apkidvo['install_apkid']));
            if(!empty($arr2)){
                $appstoreMap2['app_identifier'] = array('in',$arr2);
                $appnamelist2 = $appstoreModel->where($appstoreMap2)->field('app_name')->select();
                foreach ($appnamelist2 as $key => $value) {
                    array_push($install_apkid_list, $value['app_name']);
                }
            }
        }
        //公用
        if(!empty($allapkid)){
            $arrall = array_filter(explode(',',$allapkid['apk_id']));
            if(!empty($arrall)){
                $appstoreallMap['app_identifier'] = array('in',$arrall);
                $appnamelistall = $appstoreModel->where($appstoreallMap)->field('app_name')->select();
                foreach ($appnamelistall as $key => $value) {
                    array_push($arr_allapkid, $value['app_name']);
                }
            }
        }
        $apkid_list = array_merge($apkid_list,$arr_allapkid);

        $data['apk_id'] = $apkid_list;
        $data['install_apk_id'] = $install_apkid_list;
        echo json_encode($data);

    }

    /*
    *2017.0808 新增房间备注
    * room_remark字段
    * 批量更新房间备注
    */
    public function updateroomremarks(){

        $menuid_str = I('post.menuid','','strip_tags');
        $remarks_str = I('post.remarks','','strip_tags');

        // $menuid_str = "901,890,879,873,638,492,63,56,9,1";
        // $remarks_str = "8806,214,,,,,,,,";

        $menuid = explode(",", $menuid_str);
        $remarks = explode(",", $remarks_str);

        $model = D("device");
        $model->startTrans();
        $j = 1;
        for ($i=0; $i <count($menuid) ; $i++) { 
            $map['id'] = $menuid[$i];
            $data['room_remark'] = $remarks[$i];
            $result = D("device")->where($map)->data($data)->save();
            if($result === false){
                --$j;
            }
        }

        if($j>0){
            $model->commit();
            echo 1;
        }else{
            $model->rollback();
            echo 0;
        }
        
    }   
}