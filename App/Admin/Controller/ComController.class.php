<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 后台公用控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Common\Controller\BaseController;
use Think\Auth;
class ComController extends BaseController{
    public $USER;
    public function _initialize(){
        C(setting());
        $url = U("Login/index");
        $sessionID = session_id();
        $data = session($sessionID);
        if((time()-$data['time'])>60*60*2){
            session($sessionID,null);
            header("Location: {$url}");
            exit(0);
        }elseif(time()<$data['time']){
            
        }else{
            $data['time'] = time();
            session($sessionID,$data);
        }
        
        $this->USER = $data;

        $m = M();
        $prefix = C('DB_PREFIX');//数据库前缀
        $UID = $this->USER['uid'];
        $userinfo = $m->query("SELECT * FROM {$prefix}auth_group g left join {$prefix}auth_group_access a on g.id=a.group_id where a.uid=$UID");//查找用户所在的分组
        $Auth = new Auth();
        $allow_controller_name = array('Upload');//放行控制器名称
        $allow_action_name = array();//放行函数名称
        if ($userinfo[0]['group_id'] != 1 && !$Auth->check(CONTROLLER_NAME . '/' . ACTION_NAME, $UID) && !in_array(CONTROLLER_NAME, $allow_controller_name) && !in_array(ACTION_NAME, $allow_action_name)) {
            $this->error('没有权限访问本页面!');
        }
        $user = member(intval($UID));
        $this->assign('user', $user);
        //$current_action_name = ACTION_NAME == 'edit' ? "index" : ACTION_NAME;
        $current_action_name = ACTION_NAME;
        //根据当前模块/控制器/方法查找auth_rule表 //join表原因未明
        $current = $m->query("SELECT s.id,s.title,s.name,s.tips,s.pid,p.pid as ppid,p.title as ptitle FROM {$prefix}auth_rule s left join {$prefix}auth_rule p on p.id=s.pid where s.name='" . CONTROLLER_NAME . '/' . $current_action_name . "'");
        $this->assign('current', $current[0]);
        $menu_access_id = $userinfo[0]['rules'];
        if ($userinfo[0]['group_id'] != 1) {
            $menu_where = "AND id in ($menu_access_id)";
        } else {
            $menu_where = '';
        }
        $menu = M('auth_rule')->field('id,title,pid,name,icon')->where("islink=1 $menu_where ")->order('o ASC')->select();
        $menu = $this->getMenu($menu);
        $this->assign('menu', $menu);
    }
    //查询条件
    public function _map(){
        $map = array();
        if (!empty($_GET['keyword'])) {
            $map['keyword'] = array("LIKE","%{$_GET['keyword']}%");
        }
        return $map;
    }
    //列表
    public function index(){
        $model = M(CONTROLLER_NAME);
        $map = $this->_map();
        $list = $this->_list($model,$map);
        $this -> assign("list",$list);
        $this -> display();
    }
    //添加
    public function add(){
        $this->display();
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
            $this->error('参数错误！');
        }
        $this->assign('vo',$var);
        $this -> display();
    }
    //删除、批量删除
    public function delete(){
        $model = M(CONTROLLER_NAME);
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if(is_array($ids)){
            if(!$ids){
                $this->error('参数错误！');
            }
            foreach($ids as $k=>$v){
                $ids[$k] = intval($v);
            }
            $map['id']  = array('in',$ids);
        }else{
            $map['id']  = $ids;
        }
        if($model->where($map)->delete()){
            if(is_array($ids)){
                $ids = implode(',',$ids);
            }
            addlog('删除'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
            $this->success('恭喜，删除成功！');
        }else{
            $this->error('删除失败，参数错误！');
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
    public function update(){
        $model = D(CONTROLLER_NAME);
        if (!$model->create()) {
            $this->error($model->getError());
        }else{
            $result = $model->save();
            if ($result !== false) {
                 $this->success("操作成功");
            }else{
                $this->error("操作失败");
            }
        }
    }
    //形成树状结构
    public function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0) {
        $tree = array ();
        if (is_array ( $list )) {
            $refer = array ();
            foreach ( $list as $key => $data ) {
                $refer [$data [$pk]] = & $list [$key];
            }
            foreach ( $list as $key => $data ) {
                $parentId = $data [$pid];
                if ($root == $parentId) {
                    $tree [] = & $list [$key];
                } else {
                    if (isset ( $refer [$parentId] )) {
                        $parent = & $refer [$parentId];
                        $parent [$child] [] = & $list [$key];
                    }
                }
            }
        }
        return $tree;
    }
    public function _list($model,$map=array(),$pagesize=10,$order="id desc"){
        $list = array();
        $count = $model->where($map)->count();//查询满足要求的总记录数
        $Page = new\Think\Page($count,$pagesize,$_GET);//实例化分页类 
        $pageshow = $Page -> show();//分页显示输出
        //进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $list = $model ->where($map) -> limit($Page ->firstRow.','.$Page -> listRows)->order($order) -> select();
        $this->assign('theAllcount',$count);
        $this -> assign("page",$pageshow);//赋值分页输出
        return $list;
    }
    protected function getMenu($items, $id = 'id', $pid = 'pid', $son = 'children'){
        $tree = array();
        $tmpMap = array();
        //id作为键
        foreach ($items as $item) {
            $tmpMap[$item[$id]] = $item;
        }
        foreach ($items as $item) {
            //通过pid与tmpMap的下标查找菜单对应的下属菜单  若项目非子项目直接引用赋值给tree变量
            if (isset($tmpMap[$item[$pid]])) {
                $tmpMap[$item[$pid]][$son][] = &$tmpMap[$item[$id]];
            } else {
                $tree[] = &$tmpMap[$item[$id]];
            }
        }
        return $tree;
    }

    //判断是否酒店管理员 设置酒店管理员查看列表权限
    public function isHotelMenber(){
        $sessionID = session_id();
        $user = session($sessionID);
        if(!empty($user['hid'])){
            $hids = explode(',', $user['hid']);
            $this->assign('personhid',$hids);
            if(empty($_POST['hid']) || $_POST['hid'] == 'hid-1gg'){
                $map = array('in',$hids);
                return $map;
            }
        }
        return false;
    }
    //根据酒店名称返回hid （模糊查询）
    public function getHidByHotelname($hotelname){
        $model = D("Hotel");
        $map['name'] = array('like',"%".$hotelname."%");
        $hidlist = $model->where($map)->field('hid')->select();
        $hid = array();
        if(!empty($hidlist)){
            foreach ($hidlist as $key => $value) {
                $hid[] = $value['hid']; 
            }
        }
        return $hid;
    }
    //根据城市返回hid （模糊查询）
    public function getHidByCityName($cityname){
        $model_r = D("Region");
        $model_h = D("Hotel");
        $hid = array();
        $map_cityname['name'] = array('like',"%".$cityname."%");
        $cityidlist = $model_r->where($map_cityname)->field('id')->select();
        if(!empty($cityidlist)){
            foreach ($cityidlist as $key => $value) {
                $cityid[] = $value['id']; 
            }
            $map_cityid['cityid'] = array('in',$cityid);
            $hidlist = $model_h->where($map_cityid)->field('hid')->select();
            if(!empty($hidlist)){
                foreach ($hidlist as $key1 => $value1) {
                    $hid[] = $value1['hid'];
                }
            }
        }
        return $hid;
    }
    //查询所有酒店房间mac列表
    public function hotel_device_list($macArr = array()){
        $Hotel = D("Hotel");
        $Device = D("Device");
        $stbIdList = $Device->field('hid')->Distinct(true)->select();
        $hIds = '';
        foreach ($stbIdList as $val) {
            $hIds .= ','.$val['hid'];
        }
        $map['hid'] = array("in",$hIds);
        $hotelList = $Hotel->where($map)->field ('id,hid,hotelname,pid')->order ('pid asc')->select();
        foreach($hotelList  as $key=>$value){
            $subList=array();
            $stbMap['hid'] = $value['hid'];
            $subList = $Device->where($stbMap)->field ('id,mac,room,firmware_version,room_remark')->select();
            if(!empty($macArr)){
               if (!empty($subList)) {
                    foreach ($subList as $k => $v) {
                        if (in_array($v['mac'], $macArr)) {
                            $subList[$k]['flag']=1;
                        }else{
                            $subList[$k]['flag']=0;
                        }
                    }
                } 
            }
            $hotelList[$key]['sub'] = $subList;
        }
        $this->assign('menuTree', $hotelList);
    }
    public function _hotel_list(){//查询所有酒店
        $Region = D("Region");
        $Hotel = D("Hotel");
        $regionIdList = $Hotel->field('cityid')->Distinct(true)->select();
        $regionIds = '';
        foreach ($regionIdList as $val) {
            $regionIds .= ','.$val['cityid'];
        }
        $map['id'] = array("in",$regionIds);
        $regionList = $Region->where($map)->field('id,name,pid')->order ('pid asc, sort asc')->select();
        foreach($regionList  as $key=>$value){
            $vo=array();
            $vo=$Region->getById($value['pid']);
            $regionList[$key]['name']=$vo['name'].' - '.$value['name'];
            $subList = $Hotel->field('hid,hotelname')->where('status = 1 and cityid='.$value['id'])->order('create_time asc')->select();
            $regionList[$key]['sub'] = $subList;
        }
        return $regionList;
    }
    public function get_client_ip(){
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
        $ip = getenv("HTTP_CLIENT_IP");
        else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
        $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
        $ip = getenv("REMOTE_ADDR");
        else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
        $ip = $_SERVER['REMOTE_ADDR'];
        else
        $ip = "unknown";
        return($ip);
    }
    //删除资源时 容量表对应修改
    public function csetDec($map,$size_type,$size){
        $HotelVolume = D("hotel_volume")->where($map)->setDec($size_type,$size);
        if($HotelVolume !== false){
            return true;
        }else{
            return false;
        }
    }
    //查询容量余额
    public function checkVolume($hid=false,$size=false){
        if($hid==false){
            $this->error("缺少酒店编号！请检查后再提交");
        }
        $map['hid'] = $hid;
        $volume_vo = D("hotel_volume")->where($map)->find();
        $applyvolume = D("hotel")->where($map)->field('space')->find();
        if($volume_vo['content_size']>0 || $volume_vo['devicebg_size']>0 || $volume_vo['chg_size']>0){
            if($size==false){
                $hadvolume = floatval($volume_vo['content_size'])+floatval($volume_vo['devicebg_size'])+floatval($volume_vo['chg_size']);
            }else{
                $hadvolume = floatval($volume_vo['content_size'])+floatval($volume_vo['devicebg_size'])+floatval($volume_vo['chg_size'])+floatval($size);
            }
            if($hadvolume<$applyvolume['space']){
                return true;          //现有容量小于申请容量
            }else{
                return false;
            }
        }else{ 
            if(floatval($size)<floatval($applyvolume['space'])){
                return true;
            }else{
                return false;
            }
        }
    }
    //allresource表  删除,修改资源时操作
    public function allresource_del($map=false){
        if($map){
            $result = D("hotel_allresource")->where($map)->delete();
            return $result;
        }else{
            return false;
        }
    }
    //新增图片或者视频资源时 新增到资源新表allresource
    public function allresource_add($data=false){
        if($data){
            $result = D("hotel_allresource")->data($data)->add();
            return $result;
        }else{
            return false;
        }
    }
    //根据hid 查找list 并转为xml 写入文件
    public function fileputXml($model=false,$hid=false,$filepath=false){
        if(!$model || !$hid || !$filepath){
            return false;
        }

        $list = $model->where('hid="'.$hid.'"')->select();
        if(!empty($list)){
            $xmlContent = xml_encode($list);
        }else{
            $xmlContent = '';
        }

        //写入xml
        file_put_contents($filepath,$xmlContent);
        return true;
    }
}