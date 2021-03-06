<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 酒店信息控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
use Vendor\File;
class HotelController extends ComController {
    //查询
    public function _map(){
        $map = array();
        if (!empty($_GET['hotelname'])) {
            $map['hotelname']=array("like","%".$_GET['hotelname']."%");
            $this->assign('hotelname', $_GET['hotelname']);
        }
        if (!empty($_GET['name'])) {
            $map['name']=array("like","%".$_GET['name']."%");
        }
        if (!empty($_GET['hid'])) {
            $map['hid']=array("like","%".$_GET['hid']."%");
            $this->assign('hid',$_GET['hid']);
        }
        if (!empty($_GET['provinceid'])) {
            $map['provinceid'] = $_GET['provinceid'];
            $this->assign("provinceid",$_GET['provinceid']);
        }else{
            $this->assign("provinceid",'');
        }
        if (!empty($_GET['cityid'])) {
            $map['cityid'] = $_GET['cityid'];
            $this->assign("cityid",$_GET['cityid']);
        }else{
            $this->assign("cityid",'');
        }
        return $map;
    }
    //列表
    public function index(){
        $model = M(CONTROLLER_NAME);
        $map = $this->_map();
        $hid_condition = $this->isHotelMenber();
        if($hid_condition){
            $map['hid'] = $hid_condition;
        }
        $list = $this->_list($model,$map);
        $this -> assign("list",$list);
        $Region = D ( 'Region' );
        $plist = $Region->where('pid=0')->order("sort asc")->field('id,name')->select();
        $this->assign("plist",$plist);
        $this -> display();
    }
    //添加
    public function add(){
        $category = M('hotel')->field('id,pid,name')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$id \$selected>\$spacer\$name</option>"; //生成的形式
        $category = $tree->get_tree(0,$str, 0);
        $this->assign('pHotel',$category);
        //皮肤
        $HotelSkin = D("HotelSkin");
        $list = $HotelSkin->where("status=1")->order("id asc")->field('id,name')->select();
        $this->assign("skinlist",$list);
        //省份
        $Region = D ( 'Region' );
        $plist = $Region->where('pid=0')->order("sort asc")->field('id,name')->select();
        $this->assign("plist",$plist);

        $this->display();
    }
    //修改
    public function edit(){
        $model = M(CONTROLLER_NAME);
        $ids = $_REQUEST['ids'];//只能同时修改一条记录
        if(count($ids)<>1){
            $this->error('参数错误，每次只能修改一条内容！');
        }
        $where['zxt_hotel.id'] = array('in',$ids);
        $var = $model->field("zxt_hotel.*,zxt_hotel_user.huid")->where($where)->join('left join zxt_hotel_user ON zxt_hotel.id=zxt_hotel_user.hotel_id')->select();
        $var = $var['0'];
        if(!$var){
            $this->error('参数错误！');
        }
        $this->assign('vo',$var);

        //客户上级不能是以本身为根节点的树上的菜单
        $leafIds = $this->getIdTree($model,$ids[0]); //获取该记录的子记录的id
        $map['id']=array("not in",$leafIds);
        $category = $model->where($map)->field('id,pid,name')->order('hid asc')->select();//子集不能作其上级
        $tree = new Tree($category);
        $str = "<option value=\$id \$selected>\$spacer\$name</option>"; //生成的形式
        $category = $tree->get_tree(0,$str, $var['pid']);//生成树型结构的代码
        $this->assign('pHotel',$category);
        //皮肤
        $HotelSkin = D("HotelSkin");
        $list = $HotelSkin->where("status=1")->order("id asc")->field('id,name')->select();
        $this->assign("skinlist",$list);
        $Region = D ( 'Region' );
        $plist = $Region->where('pid=0')->order("sort asc")->field('id,name')->select();
        $this->assign("plist",$plist);
        $citylist = $Region->where('pid='.$var['provinceid'])->order("sort asc")->field('id,name')->select();//所有城市
        $this->assign("clist",$citylist);
        $this -> display();
    }
    //保存
    public function update(){
        $data['id'] = I('post.id','','intval');
        $data['pid'] = $_POST['pid'];
        $data['name'] = I('post.name','','strip_tags');
        $data['hotelname'] = I('post.hotelname','','strip_tags');
        $data['hid'] =strtoupper(I('post.hid','','strip_tags'));
        $data['skinid'] = I('post.skinid','','intval');
        $data['provinceid'] = intval($_POST['provinceid']);
        $data['cityid'] = intval($_POST['cityid']);
        $data['manager'] = I('post.manager','','strip_tags');
        $data['mobile'] = I('post.mobile','','strip_tags');
        $data['tel'] = I('post.tel','','strip_tags');
        $data['address'] = I('post.address','','strip_tags');
        $data['space'] = I('post.space','500','strip_tags');
        $data['intro'] = I('post.intro','','strip_tags');
        $data['main_type'] = I('post.main_type','','strip_tags');
        $data['demo'] = I('post.demo','','strip_tags');
        $data['update_time'] = time();
        if(!$data['name'] or !$data['hotelname'] or !$data['hid'] ){
            $this->error('警告！酒店客户名称、酒店名称、酒店编号为必填项目。');
        }
        if($data['id']){//修改
            if ($data['pid']==$data['id']) {
                $this->error('警告！客户上级不能选本身！');
            }
            $data['isinsert'] = I('post.isinsert');
            if ($data['pid'] != 0) {
                $this->bindchgadmin($data['hid'], $data['id'], $data['pid']);
            }
            if (empty($data['isinsert'])) {//判断是否需要新增中间表和member表 antu_group_access表
                M('hotel')->data($data)->where('id='.$data['id'])->save();
            }else{
                $userData['user'] = I('post.user','','strip_tags');
                $userData['phone'] = I('post.phone','','','intval');
                $password = I('post.password','','strip_tags');
                $userData['password'] = password($password);
                if(!$userData['user'] or !$userData['phone'] or !$userData['password']){
                    $this->error("警告 管理员账号密码电话号码为必填项目");
                }
                $data['status'] = 0;
                $data['create_time'] = time();
                $userData['t'] = time();
                $huDate['hid'] = $userData['hid'] = $data['hid'];
                $model = D();

                $model->startTrans();
                $save = M('hotel')->data($data)->where('id='.$data['id'])->save();
                $user_id = D('member')->data($userData)->add();
                $huDate['hotel_id'] = $data['id'];
                $huDate['user_id'] = $user_id;
                $hu = D('hotel_user')->data($huDate)->add();
                $access_id = M('auth_group_access')->data(array('group_id'=>3,'uid'=>$user_id))->add();
                if($save===false or !$user_id or !$hu or !$access_id){
                    $model->rollback();
                    $this->error('修改失败',U('index'));
                    die();
                }else{
                    addlog('修改酒店信息，酒店ID：'.$data['id']);
                    $model->commit();
                }
            }
        }else{
            $rules = array(
                array('hid','','酒店编号已经存在！',0,'unique',1),
            );
            $member_rules = array(
                array('user','','登录账号已经存在！',0,'unique',1),
            );
            $userData['user'] = I('post.user','','strip_tags');
            $userData['phone'] = I('post.phone','','','intval');
            $password = I('post.password','','strip_tags');
            $userData['password'] = password($password);
            if(!$userData['user'] or !$userData['phone'] or !$userData['password']){
                $this->error("警告 管理员账号密码电话号码为必填项目");
            }
            $data['status'] = 0;
            $data['create_time'] = time();
            $userData['t'] = time();
            $userData['hid'] = $data['hid'];
            $model = D();
            $hotel_m = D('hotel');
            $model->startTrans();
            $validate = $hotel_m->validate($rules)->create();
            if(!$validate){
                $model->rollback();
                $this->error($hotel_m->getError());
                die();
            }
            $member_validate = D('member')->validate($member_validate)->create();
            if(!$member_validate){
                $model->rollback();
                $this->error(D('member')->getError());
                die();
            }
            $hotel_id = D('hotel')->data($data)->add();
            $user_id = D('member')->data($userData)->add();
            $huDate['hotel_id'] = $hotel_id;
            $huDate['user_id'] = $user_id;
            $huDate['hid'] = $data['hid'];
            $hu = D('hotel_user')->data($huDate)->add();
            $access_id = M('auth_group_access')->data(array('group_id'=>3,'uid'=>$user_id))->add();
            //子酒店关联上级酒店账号
            if ($data['pid'] != 0) {
                $this->bindchgadmin($data['hid'], $hotel_id, $data['pid']);
            }
            //新增酒店的时候广告容量添加
            $allAdVolume = M("Ad")->field('SUM(size)')->where(array('hidlist'=>'all'))->find();
            $Volumedata['hid'] = $data['hid'];
            $Volumedata['ad_size'] = $allAdVolume['sum(size)'];
            $Volumedata['content_size'] = 0.000;
            $Volumedata['topic_size'] = 0.000;
            $Volumedata['devicebg_size'] = 0.000;
            $Volumeid = M("hotel_volume")->data($Volumedata)->add();
            if(!$hotel_id or !$user_id or !$hu or !$access_id or !$Volumeid){
                $model->rollback();
                $this->success('添加酒店失败',U('index'));
                die();
            }else{
                $model->commit();
            }
            addlog('添加酒店ID：'.$hotel_id.'  添加用户ID'.$user_id);
        }

        $this->success('恭喜，操作成功！',U('index'));
    }

    //子酒店绑定集团账号方法
    public function bindchgadmin($hid,$hotel_id,$pid){
        $where['zxt_hotel.id'] = $_POST['lastpid'];
        if (!empty($where['zxt_hotel.id'])) {
            $deluserlist = D("hotel")->where($where)->join('zxt_hotel_user ON zxt_hotel.hid=zxt_hotel_user.hid')->field('zxt_hotel_user.user_id')->select();
            if(!empty($deluserlist)){
                foreach ($deluserlist as $key => $value) {
                    $user_id_arr[] = $value['user_id'];
                }
                $delwhere['user_id'] = array('in', $user_id_arr);
                $delwhere['hid'] = $hid;
                D("hotel_user")->where($delwhere)->delete();
            }
        }
        $adduserlist = D("hotel")->where('zxt_hotel.id='.$pid)->join('zxt_hotel_user ON zxt_hotel.hid=zxt_hotel_user.hid')->field('zxt_hotel_user.user_id')->select();
        if (!empty($adduserlist)) {
            foreach ($adduserlist as $key => $value) {
                $adddata[$key]['user_id'] = $value['user_id'];
                $adddata[$key]['hotel_id'] = $hotel_id;
                $adddata[$key]['hid'] = $hid;
            }
            D("hotel_user")->addAll($adddata);
        }
    }

    //获取城市列表
    public function get_city(){
        $pid = $_REQUEST['pid'];
        $Region = D("Region");
        $citylist = $Region->where('pid='.$pid)->order("sort asc")->field('id,name')->select();
        echo json_encode($citylist);
    }
    //获取该记录的子记录的id
    private function getIdTree($model,$pid=0,$ids=''){
        $volist = $model->where('pid='.$pid)->select();
        if(!empty($volist)){
            foreach ($volist as $value) {
                $idTree = $this->getIdTree($model,$value['id'],$value['id']);
                $ids .= ','.$idTree;
            }
        }
        return $ids;
    }
    public function copy(){
       $ids = I('post.ids','','strip_tags');
       $pHotel = D("hotel")->field('id,hid,hotelname,pid')->where('id="'.$ids[0].'"')->find();
       $hotelList = array();
       if($pHotel['pid'] == 0){
            $this->assign('ischg',$pHotel['hid']);
            $hotelList = D("hotel")->field('id,hid,hotelname')->where('pid="'.$pHotel["id"].'"')->select();
       }

       $this->assign('pHotel',$pHotel);
       $this->assign('hotelList',$hotelList);
       $this->display();
    }
    public function _usermap(){
        $map = array();
        $username = trim(I('post.username','','strip_tags'));
        if(!empty($username)){
            $map['user']=array("like","%".$username."%");
        }
        return $map;
    }
    public function hoteluser(){
        $map = $this->_usermap();
        $hid_condition = $this->isHotelMenber();
        if ($hid_condition) {            
            $map['hid'] = $hid_condition;
        }
        $where_group['group_id'] = ['in', C('HOTEL_GROUP')];
        $accessgroup = D("auth_group_access")->where($where_group)->field('uid')->select();
        $uid_arr = [];
        if (!empty($accessgroup)) {
            foreach ($accessgroup as $key => $value) {
                $uid_arr[] = $value['uid'];
            }
        }
        $map['uid'] = ['in', $uid_arr];
        $count = D("member")->where($map)->count();
        $prefix = C('DB_PREFIX');
        $Page = new\Think\Page($count,$pagesize=10,$_GET);//实例化分页类 
        $pageshow = $Page -> show();//分页显示输出               
        $list = D("member")->where($map)->limit($Page ->firstRow.','.$Page -> listRows)->select();
        $this -> assign("page",$pageshow);//赋值分页输出
        $this->assign('list',$list);
        $this->display();
    }

    public function searchhotel(){
        if(!empty($_POST['hid'])){
            $hid = trim(I('post.hid','','strip_tags'));
            if(empty($hid)){
                return false;
                die();
            }
            $model = D(CONTROLLER_NAME);
            $map['hid'] = array('eq',$hid);
            $list = $model->field('id,hid,hotelname')->where($map)->find();
            echo json_encode($list);
            die();
        }
    }
    public function insertHotelUser(){
        $model = D("hotel_user");
        $data['user_id'] = I('post.userid','','strip_tags');
        $data['hotel_id'] = I('post.hotelid','','strip_tags');
        $data['hid'] = I('post.inserthid','','strip_tags');
        if(empty($data['user_id'])){
            $this->error('未能获取到酒店管理员ID');
            die();
        }
        $map['hid'] = $data['hid'];
        $map['user_id'] = $data['user_id'];
        $have = $model->where($map)->find();
        if(!empty($have)){
            $this->error('该酒店已归属');
            die();
        }
        $result = $model->data($data)->add();
        if($result){
            $this->success('酒店归属成功');
        }else{
            $this->error('酒店归属失败');
        }
    }
    public function _showbelonghotel(){
        $ids = $_REQUEST['ids'];
        if(count($ids)>1){
            $this->error('只能同时查看一位管理员的所属酒店');
            die();
        }elseif(count($ids)<1){
            $this->error('请选择一名酒店管理员');
            die();
        }
        $model = D("hotel_user");
        $prefix = C('DB_PREFIX');
        $map["{$prefix}hotel_user.user_id"] = array('eq',$ids['0']);
        $field = "{$prefix}hotel_user.huid,{$prefix}hotel.id,{$prefix}hotel.hid,{$prefix}hotel.hotelname,{$prefix}hotel.manager,{$prefix}hotel.mobile,{$prefix}hotel.provinceid,{$prefix}hotel.cityid,{$prefix}hotel.status";
        $list = $model->field($field)->join("{$prefix}hotel ON {$prefix}hotel_user.hotel_id = {$prefix}hotel.id")->where($map)->select();
        return $list;
    }
    public function checkbelong(){
        $list = $this->_showbelonghotel();
        $this->assign("list",$list);
        $this->display();
    }
    public function showdelbelong(){
        $list = $this->_showbelonghotel();
        $this->assign("list",$list);
        $this->display();
    }
    public function deletebelong(){
        $ids = $_REQUEST['ids'];
        if(count($ids)<1){
            $this->error('至少要选择一条数据');
            die();
        }
        $model = D("hotel_user");
        $map['huid'] = array('in',$ids);
        if($model->where($map)->delete()){
            $this->success("删除成功","hoteluser");
        }else{
            $this->error('删除失败',"hoteluser");
        }
    }
    public function relatetopic(){
        $ids = $_REQUEST['ids'];
        if(count($ids)!=1){
            $this->error('请选择一家酒店进行专题关联');
            die();
        }
        $model = D("HotelTopic");
        $hotelList = D("Hotel")->where('id="'.$ids['0'].'"')->field('hid')->find();//HID
        $topicgroupList = D("TopicGroup")->field('id,title')->where('status=1')->select();//一级栏目
        $HotelTopicList = $model->where('hid="'.$hotelList['hid'].'"')->select();
        if(!empty($HotelTopicList)){
            $topicname='';
            foreach ($HotelTopicList as $key => $value) {
                $voo = array();
                $voo = D("TopicGroup")->where('id="'.$value['topic_id'].'"')->field('title')->find();
                $topicname = $topicname.','.$voo['title'];
                $topic_id_arr[] = $value['topic_id'];
            }
            $topic_id_str = implode(",", $topic_id_arr);
            foreach ($topicgroupList as $k => $v) {
                if(in_array($v['id'], $topic_id_arr)){
                    $topicgroupList[$k]['isselect'] = 1;
                }else{
                    $topicgroupList[$k]['isselect'] = 0;
                }
            }
            $this->assign("topicName",trim($topicname,','));
        }else{
            $this->assign("topicName","--");
        }
        $this->assign('topic_id_str',$topic_id_str);
        
        $this->assign("hid",$hotelList['hid']);
        $this->assign('topiclist', $topicgroupList);
        $this->display();
    }
    //保存关联通用栏目
    public function saverelatetopic(){
        $hid = I('post.hid','','strip_tags');
        $topic_list = I('post.ids','','strip_tags');
        $pass_topic_list = I('post.passids','','strip_tags');

        $topic_list_arr = explode(",", $topic_list);
        $pass_topic_list_arr = explode(",", $pass_topic_list);
        $add_id_arr = array_diff($topic_list_arr, $pass_topic_list_arr);
        $del_id_arr = array_diff($pass_topic_list_arr, $topic_list_arr);

        if(empty($hid)){
            $this->error('系统错误：未能获取酒店编号');
        }
        $model = D("hotel_topic");
        $model->startTrans();
        $topic_list_arr = array();

        //操作hotel_topic表
        $hoteltopicResult = true;
        $addHoteltopicResult = true;
        $delHoteltopicResult = $model->where('hid="'.$hid.'"')->delete();//删除操作
        if(!empty($topic_list)){
            $topic_list_arr = explode(",", $topic_list);
            $i = 0;
            foreach ($topic_list_arr as $key => $value) {
                $addHoteltopicDate[$i]['hid'] = $hid;
                $addHoteltopicDate[$i]['topic_id'] = $value;
                $i++;
            }
            $addHoteltopicResult = D("hotel_topic")->addAll($addHoteltopicDate);
        }
        if($addHoteltopicResult===false || $delHoteltopicResult===false){
            $hoteltopicResult = false;
        }
        //获取资源大小
        $allsize = 0;
        if(!empty($topic_list_arr)){
            foreach ($topic_list_arr as $key => $value) {
                $allsize += $this->searchTopicAndResource($value);//累加各个栏目的资源大小
            }
        }
        if(M("hotel_volume")->where('hid="'.$hid.'"')->count()){
            $updatesize = D("hotel_volume")->where('hid="'.$hid.'"')->setField("topic_size",$allsize);
        }else{
            $arrdata['hid'] = $hid;
            $arrdata['content_size'] = 0.00;
            $arrdata['topic_size'] = $allsize;
            $arrdata['ad_size'] = 0.00;
            $updatesize = M("hotel_volume")->data($arrdata)->add();
        }

        if($hoteltopicResult!== false && $updatesize !== false){
            $model->commit();
            //更新json
            $this->updatejson_one($hid,$topic_list_arr);
            $this->updatejson_two($hid,$topic_list_arr);
            $this->updatejson_resource($hid,$topic_list_arr);
            $this->success('关联成功','index');
        }else{
            $model->rollback();
            $this->error('关联失败','index');
        }
    }
    //酒店管理员账号
    public function checkUsername(){
        $user = I('post.user','','strip_tags');
        if(empty($user)){
            echo false;
        }
        $map['user'] = $user;
        $model = D("member");
        $result = $model->where($map)->find();
        if(!empty($result)){
            echo 0;
        }else{
            echo 1;
        }
    }
    //酒店删除 需要删除volume表
    public function delete(){
        $ids = $_REQUEST['ids'];
        if(count($ids) !=1){
            $this->error('系统提示：仅能同时进行一目标进行删除操作');
        }
        $model = D("hotel");
        $map['id'] = $ids[0];
        $hotelhid_arr = $model->field('hid')->where($map)->find();
        $hotelHid = $hotelhid_arr['hid'];
        $arrmap['hid'] = $hotelHid; //酒店HID
        $hotel_volume = D("hotel_volume")->where($arrmap)->find();
        if(!empty($hotel_volume) && $hotel_volume['content_size']>0){
            $this->error("系统提示，删除目标含有资源，请先删除资源再进行删除操作");
            die();
        }

        $model->startTrans();
        $delHotelvolumeResult = M("hotel_volume")->where($arrmap)->delete();
        if($model->where($map)->delete() && $delHotelvolumeResult!==false){
            if(is_array($ids)){
                $ids = implode(',',$ids);
            }
            addlog('删除'.CONTROLLER_NAME.'表数据，数据ID：'.$ids);
            $model->commit();
            $this->success('恭喜，删除成功！');
        }else{
            $model->rollback();
            $this->error('删除失败，参数错误！');
        }
    }

    /*新增 集团栏目关联功能  20170630*/
    //关联集团栏目
    public function relatechg(){
        $ids = $_REQUEST['ids'];
        if(count($ids)!=1){
            $this->error('请选择集团子酒店进行专题关联');
            die();
        }

        $hotelMap['id'] = $ids['0'];
        $hotel = D("hotel")->where($hotelMap)->field('id,hid,pid,space')->find();
        $hid = $hotel['hid'];
        $phidMap['id'] = $hotel['pid']; 
        $photel = D("hotel")->where($phidMap)->field('id,hid,pid')->find();
        $phid = $photel['hid'];

        //查询酒店剩余容量值(不包括已选集团栏目)
        $volumeMap['hid'] = $hid;
        $volumeList = D("hotel_volume")->where($volumeMap)->find();
        if(!empty($volumeList)){
            $residueV = $hotel['space'] - $volumeList['content_size'] - $volumeList['topic_size'] - $volumeList['ad_size'] - $volumeList['devicebg_size'];
        }else{
            $residueV = $hotel['space'];
        }
        $chgHadV = 0; //已选集团栏目所占容量

        //查询该酒店下的集团通用栏目
        $chgCategoryMap['hid'] = $phid;
        $chgCategoryMap['pid'] = 0;
        $chgCategoryMap['status'] = 1;
        $categoryList = D("hotel_chg_category")->where($chgCategoryMap)->field('id,hid,name,all_size')->select();

        //查询已关联集团通用栏目
        $hadchgReleateMap['zxt_hotel_chglist.hid'] = $hid;
        $hadchgReleateMap['pid'] = 0;
        $field = "zxt_hotel_chglist.chg_cid,zxt_hotel_chg_category.id,zxt_hotel_chg_category.name,zxt_hotel_chg_category.all_size";
        $releateList = D("hotel_chglist")->where($hadchgReleateMap)->join("zxt_hotel_chg_category ON zxt_hotel_chglist.chg_cid=zxt_hotel_chg_category.id")->field($field)->select();

        if(!empty($releateList)){ //有绑定执行赋值操作
                
            foreach ($releateList as $k => $v) {
                $showlist .= $v['name'];
                $showlist .= '('.$v['all_size'].'MB)&nbsp;&nbsp;&nbsp;&nbsp;';
                $chglist_id_arr[] = $v['chg_cid']; //已选集团栏目id数组
                $chgHadV += $v['all_size'];
            }
            $chglist_id = implode(',', $chglist_id_arr);

            foreach ($categoryList as $key => $value) {
                $isselect = 0;

                foreach ($releateList as $key2 => $value2) {
                    if($value['id'] == $value2['id']){
                        $isselect = 1;
                    }
                }

                if($isselect==1){
                    $categoryList[$key]['isselect'] = 1;
                }else{
                    $categoryList[$key]['isselect'] = 0;
                }
            }
        }

        //最终可用容量
        $residueSize = $residueV - $chgHadV;

        $this->assign('hid',$hid);  //本身（子酒店）hid
        $this->assign('phid',$phid);  //父级hid
        $this->assign('releateList',$releateList); //
        $this->assign('showlist',$showlist); //textarea显示列表
        $this->assign('selectlist',$categoryList);  //弹窗勾选列表
        $this->assign('chglist_id',$chglist_id); //已选择集团栏目id列表
        $this->assign('residueV',$residueV); //剩余容量(不包括所选栏目)
        $this->assign('chgHadV',$chgHadV); //所选栏目已用容量
        $this->assign('residueSize',$residueSize);
        $this->display();

    }

    //更新关联集团
    public function updaterelatechg(){

        $hid = I('post.hid','','strip_tags');
        $phid = I('post.phid','','strip_tags');
        $chglist_id = I('post.chglist_id','','strip_tags');
        $ids = I('post.ids','','strip_tags');
        $size = I('post.size','','strip_tags');

        if(empty($hid) || empty($phid)){
            $this->error('参数错误，请联系系统管理员',U("index"));
        }

        //查询酒店容量
        $hotelMap['hid'] = $hid;
        $hotel = D("hotel")->where($hotelMap)->field('space,carousel_space')->find(); 
        $hotelspace = floatval($hotel['space']); //酒店容量
        
        //查询已用容量（不包括关联集团） 
        $volumeMap['hid'] = $hid;
        $volumeVo = D("hotel_volume")->where($volumeMap)->find();  
        if(!empty($volumeVo)){
            $usedVolume = $volumeVo['content_size'] + $volumeVo['topic_size'] + $volumeVo['ad_size'] + $volumeVo['devicebg_size'];
        }else{
            $usedVolume = 0;
        }
        
        //查询选择的栏目的容量大小
        if(!empty($ids)){
            $id_arr = explode(',', $ids);
            $chgCategoryMap['id'] = array('in',$id_arr);
            $chgCategoryList = D("hotel_chg_category")->where($chgCategoryMap)->field('SUM(all_size)')->select();
            $chgVolume = floatval($chgCategoryList[0]['sum(all_size)']);
        }else{
            $chgVolume = 0;
        }

        if(($hotelspace-$usedVolume-$chgVolume)>0){ //有容量剩余 更新

            //判断关联集团栏目是否有变化 若有 找出异同
            if(!empty($chglist_id)){
                $chglistId_arr = explode(',', $chglist_id);//原关联栏目id组
                if(!empty($ids)){
                    $delId_arr = array_diff($chglistId_arr, $id_arr);
                    $addId_arr = array_diff($id_arr,$chglistId_arr);
                }else{ //为空时  全部删除
                    $delId_arr = $chglistId_arr;
                    $addId_arr = array();
                }

            }else{
                if(!empty($ids)){ //新操作有选定集团栏目
                    $delId_arr = array();
                    $addId_arr = $id_arr;
                }else{
                    $delId_arr = array();
                    $addId_arr = array();
                }
            }

            if(!empty($ids)){
                $CarouselSizeMap['zxt_hotel_chg_category.pid'] = array('in',$ids);
                $CarouselSizeList = D("hotel_chg_category")->where($CarouselSizeMap)->join("zxt_hotel_carousel_resource ON zxt_hotel_chg_category.id = zxt_hotel_carousel_resource.cid")->field("sum(zxt_hotel_carousel_resource.size)")->select();
                $CarouselSize = $CarouselSizeList['0']['sum(zxt_hotel_carousel_resource.size)'];
                if($CarouselSize>$hotel['carousel_space']){
                    $this->error('关联集团栏目中视频轮播容量超过酒店规定值,请重新选择关联集团栏目!',U('index'));
                }
            }else{
                $CarouselSize = 0;
            }

            //删除（原有选定 现不选定）
            $result_del = true;
            $result_add = true;
            $model = D("hotel_chglist");
            $model->startTrans();
            if(!empty($delId_arr)){
                $delMap['hid'] = $hid;
                $delMap['phid'] = $phid;
                $delMap['chg_cid'] = array('in',$delId_arr);
                $result_del = D("hotel_chglist")->where($delMap)->delete();
            }

            //新增 （原来没选 新选定）
            if(!empty($addId_arr)){
                $i = 0;
                foreach ($addId_arr as $key => $value) {
                    $addDate[$i]['hid'] = $hid;
                    $addDate[$i]['phid'] = $phid;
                    $addDate[$i]['chg_cid'] = intval($value);
                    $chg_cid_arr[] = intval($value);//chg_cid数组集合
                    $i++;
                }
                $result_add = D("hotel_chglist")->addAll($addDate);
            }

            //添加到酒店容量表
            $hvMap['hid'] = $hid;
            if(M("hotel_volume")->where($hvMap)->count()){
                $hvDate['chg_size'] = $size;
                $hvDate['carousel_size'] = $CarouselSize;
                $updatesize = D("hotel_volume")->where($hvMap)->data($hvDate)->save();
            }else{
                $hvDate['hid'] = $data['hid'];
                $hvDate['content_size'] = 0.000;
                $hvDate['topic_size'] = 0.000;
                $hvDate['ad_size'] = 0.000;
                $hvDate['chg_size'] = $size;
                $updatesize = M("hotel_volume")->data($hvDate)->add();
            }

            if($result_del!==false && $result_add!==false && $updatesize!==false){
                $model->commit();
                $this->success('操作成功',U('index'));
            }else{
                $model->rollback();
                $this->error('操作失败',U('index'));
            }
        }
        else{
            $this->error('关联集团栏目所需容量超过剩余容量,请重新选择关联集团栏目!',U('index'));
        }
    }

    //找出chg一级栏目及其资源
    public function searchChgFcategoryAndResource($chg_cid_arr = array()){
        if(empty($chg_cid_arr)){
            return array();
        }
        $list = array();
        $categoryMap['id'] = $resourceMap['cid'] = array('in',$chg_cid_arr);
        $categoryList = D("hotel_chg_category")->where($categoryMap)->field('filepath')->select();//一级栏目
        $resourceList = D("hotel_chg_resource")->where($resourceMap)->field('file_type,filepath,icon')->select();// 一级栏目对应的资源
        
        if(!empty($categoryList)){
            $i = 0;
            foreach ($categoryList as $ckey => $cvalue) {
                if(!empty($cvalue['filepath'])){
                    $list[$i]['type'] = 2; //图片类型
                    $getName = explode("/", $cvalue['filepath']);
                    $list[$i]['name'] = $getName[count($getName)-1];
                    $list[$i]['web_upload_file'] = $cvalue['filepath'];
                    $i++;
                }
            }
        }
        if(!empty($resourceList)){
            $j = count($list);
            foreach ($resourceList as $rkey => $rvalue) {
                $list[$j]['type'] = $rvalue['file_type'];
                $getName = explode("/",$rvalue['filepath']);
                $list[$j]['name'] = $getName[count($getName)-1];
                $list[$j]['web_upload_file'] = $rvalue['filepath'];
                $j++;
            }

            $k = count($list);
            foreach ($resourceList as $ikey => $ivalue) {
                if(!empty($ivalue['icon'])){
                    $list[$k]['type'] = 2;
                    $getName = explode("/", $ivalue['icon']);
                    $list[$k]['name'] = $getName[count($getName)-1];
                    $list[$k]['web_upload_file'] = $ivalue['icon'];
                }
                $k++;
            }
        }

        return $list;
    }

    //找出chg二级栏目及其资源
    public function searchChgScategoryAndResource($chg_cid_arr = array()){
        if(empty($chg_cid_arr)){
            return array();
        }

        $list = array();
        $second_id_arr = array();//二级栏目id数组集合
        $categoryMap['pid'] = array('in',$chg_cid_arr);
        $categoryList = D("hotel_chg_category")->where($categoryMap)->field('id,filepath')->select();//二级栏目

        if(!empty($categoryList)){
            $i = 0;
            foreach ($categoryList as $ckey => $cvalue) {
                $second_id_arr[] = $cvalue['id'];
                $list[$i]['type'] = 2;//图片资源
                $getName = explode("/", $cvalue['filepath']);
                $list[$i]['name'] = $getName[count($getName)-1];
                $list[$i]['web_upload_file'] = $cvalue['filepath'];
                $i++;
            }
        }

        if(!empty($second_id_arr)){
            $resourceMap['cid'] = array('in',$second_id_arr);
            $resourceList = D('hotel_chg_resource')->where($resourceMap)->field('file_type,filepath,icon')->select();//二级栏目对应的资源
            $j = count($list);
            foreach ($resourceList as $rkey => $rvalue) {
                $list[$j]['type'] = $rvalue['file_type'];
                $getName = explode("/",$rvalue['filepath']);
                $list[$j]['name'] = $getName[count($getName)-1];
                $list[$j]['web_upload_file'] = $rvalue['filepath'];
                $j++;
            }

            $k = count($list);
            foreach ($resourceList as $ikey => $ivalue) {
                if(!empty($ivalue['icon'])){
                    $list[$k]['type'] = 2;
                    $getName = explode("/", $ivalue['icon']);
                    $list[$k]['name'] = $getName[count($getName)-1];
                    $list[$k]['web_upload_file'] = $ivalue['icon'];
                }
                $k++;
            }
        }

        return $list;
    }

    //找出通用栏目下资源和栏目图标总大小
    public function searchTopicAndResource($id=false){
        //一级栏目
        $topic_group = D("topic_group")->field('size')->where('id='.$id)->find();
        $topic_group_size = !empty($topic_group['size'])?$topic_group['size']:0;
        //二级栏目
        $topic_category = D("topic_category")->field('sum(size)')->where('groupid="'.$id.'"')->select();
        $topic_category_size = !empty($topic_category[0]['sum(size)'])?$topic_category[0]['sum(size)']:0;
        //资源
        $topic_resource = D("topic_resource")->where('gid="'.$id.'"')->field('sum(size)')->select();
        $topic_resource_size = $topic_resource[0]['sum(size)'];
        return ($topic_group_size+$topic_category_size+$topic_resource_size);
    }

    //找出通用栏目下资源的名称
    public function searchTopicAndName($id_arr = array()){
        if(empty($id_arr)){
            return;
        }
        $getName = array();
        $getType = array();
        $getFilepath = array();
        foreach ($id_arr as $key => $value) {
            //查找一级
            $topic_group = D("topic_group")->field('icon')->where('id="'.$value.'"')->find();
            if(!empty($topic_group['icon'])){
                $getName[] = $this->filepathToName($topic_group['icon']);
                $getType[] = 2;
                $getFilepath[] = $topic_group['icon'];
            }
            //查找二级
            $topic_category = D("topic_category")->field('icon')->where('groupid="'.$value.'"')->select();
            if(!empty($topic_category)){
                foreach ($topic_category as $k => $v) {
                    if(!empty($v['icon'])){
                        $getName[] = $this->filepathToName($v['icon']);
                        $getType[] = 2;
                        $getFilepath[] = $v['icon'];
                    }
                }
            }
            //查找资源
            $topic_resource = D("topic_resource")->field('video,image,video_image')->where('gid="'.$value.'"')->select();
            if(!empty($topic_resource)){
                foreach ($topic_resource as $kk => $vv) {
                    if(!empty($vv['video'])){
                        $getName[] = $this->filepathToName($vv['video']);
                        $getType[] = 1;
                        $getFilepath[] = $vv['video'];
                    }
                    if(!empty($vv['image'])){
                        $getName[] = $this->filepathToName($vv['image']);
                        $getType[] = 2;
                        $getFilepath[] = $vv['image'];
                    }
                    if(!empty($vv['video_image'])){
                        $getName[] = $this->filepathToName($vv['video_image']);
                        $getType[] = 2;
                        $getFilepath[] = $vv['video_image'];
                    }
                }
            }
        }
        $result['name'] = $getName;
        $result['type'] = $getType;
        $result['filepath'] = $getFilepath;
        return $result;
    }

    //根据路径获得名字
    public function filepathToName($str){
        $Name_arr = explode("/", $str);
        $getName = $Name_arr[count($Name_arr)-1];
        return $getName;
    }

    //复制酒店操作
    public function savecopy(){
        //校验数据
        $verifiData = $this->verifiData();
        if($verifiData == false){
            $this->error('系统提示：参数有误');
        }

        //获取pass酒店数据
        $hoteldata = $this->getlasthotel($verifiData);
        foreach ($hoteldata as $key => $value) {
            $hidarr[$key]['passhid'] = $value['hid'];
            $hidarr[$key]['newhid'] = $verifiData[$key]['hid'];
        } 

        D()->startTrans();

        //复制酒店列表 hotel
        $hotellistid = $this->copyhotel($hoteldata,$verifiData);

        //复制用户列表 member
        $memberid = $this->copymember($verifiData);

        //复制关联表 hotel_user
        $this->copyhoteluser($memberid,$hotellistid,$verifiData);
        
        //复制欢迎图片 hotel_welcome + hotel_resource
        $this->copywelcome($hidarr);
        
        //复制语言管理 hotel_language
        $this->copylanguage($hidarr);

        //复制跳转设置 hotel_jump + hotel_resource
        $this->copyjump($hidarr);

        //复制酒店宣传 hotel_spread + hotel_resource
        $this->copyspread($hidarr);

        //复制酒店栏目及其资源 hotel_category + hotel_resource + hotel_carousel_resource
        $this->copycategory($hidarr);
        
        //复制集团通用栏目 hotel_chg hotel_chg_resource + hotel_carousel_resource
        $chghid = I('post.ischg','','strip_tags');//集团酒店的标志
        if(!empty($chghid)){
            $cNewId_arr = $this->copychg($chghid,$hidarr);
        }

        //关联通用栏目 hotel_topic
        $this->copytopic($hidarr);
        
        //关联集团栏目
        $this->copychglist($hidarr,$cNewId_arr);

        //复制酒店容量表 hotel_volume
        $this->copyvolume($hidarr);
        
        //复制酒店资源表 更新XML文件  hotel_allresource 
        //新需求改动 0926确定不需要此功能
        // $this->copyallresource($hidarr);

        //文件复制
        $File = new File;
        $rootfile = dirname(dirname(dirname(dirname(__FILE__)))).'/Public/upload/content/';
        foreach ($hidarr as $key => $value) {
            $havecopyHid[] = $value['newhid'];
            $copyfileResult = $File::copy_dir($rootfile.$value['passhid'].'/', $rootfile.$value['newhid'].'/');
            if($copyfileResult !== true){
                D()->rollback();
                $this->error('复制资源时失败,请联系管理员确定资源是否存在');
                foreach ($havecopyHid as $kk => $vv) {
                    $File::del_dir($rootfile.$vv.'/');
                }
            }
        }
        D()->commit();
        $this->success('复制成功',U('index'));
        //暂不处理 弹窗广告，日志记录，appstore关联，升级系统关联等
    }

    /**
     * [校验数据是否有误、可行]
     * @return [array] [复制酒店数据集合]
     */
    private function verifiData(){
        $listid = I('post.listid','','strip_tags');
        $hotelHid = I('post.hotelHid','','strip_tags');
        $memberName = I('post.memberName','','strip_tags');
        $password = I('post.password','','strip_tags');
        $num[] = count($listid); 
        $num[] = count($hotelHid); 
        $num[] = count($memberName); 
        $num[] = count($password);
        if(count(array_unique($num))!=1){
            return false;
        }
        $returnData = array();
        for ($i=0; $i < $num['0']; $i++) { 
            if(trim($listid[$i]) == ""){
                return false;
            }
            if(trim($hotelHid[$i]) == ""){
                return false;
            }
            if(trim($memberName[$i]) == ""){
                return false;
            }
            if(trim($password[$i]) == ""){
                return false;
            }
            $returnData[$i]['listid'] = $listid[$i];
            $returnData[$i]['hid'] = strtoupper($hotelHid[$i]);
            $returnData[$i]['member'] = $memberName[$i];
            $returnData[$i]['password'] = $password[$i];
        }
        $checkHadHid['hid'] = array('in',$hotelHid);
        $hotellist = D("hotel")->where($checkHadHid)->count();
        if($hotellist>0){
            $this->error('所填酒店编号已存在');
        }
        $checkHadMember['user'] = array('in',$memberName);
        $memberlist = D("member")->where($checkHadMember)->count();
        if($memberlist>0){
            $this->error('所填登录账号已存在');
        }
        return $returnData;
    }

    /**
     * [获取酒店表hotel的数据]
     * @param  [array] $data [校验数据]
     * @return [array]       [酒店表hotel数据]
     */
    private function getlasthotel($data){
        $field = "hid,name,hotelname,manager,mobile,tel,address,intro,status,provinceid,cityid,create_time,update_time,space,ad_space,carousel_space,skinid,pid,longitude,latitude";
        $list = array();
        foreach ($data as $key => $value) {
            if(!empty($value['listid'])){
                $id_arr[] = $value['listid'];
            }
        }
        $map['id'] = array('in',$id_arr);
        $list = D("hotel")->where($map)->field($field)->select();
        return $list;
    }

    /**
     * [复制酒店表hotel]
     * @param  [array] $data     [校验数据]
     * @return [array] $returnid [新增ID列表]
     */
    private function copyhotel($data,$verifiData){
        $returnid = array();
        $adddata = array();
        foreach ($data as $key => $value) {
            $adddata[$key] = $value;
            $adddata[$key]['hid'] = $verifiData[$key]['hid'];
            $adddata[$key]['create_time'] = time();
            $adddata[$key]['name'] = $value['name'].'(copy)';
            $adddata[$key]['hotelname'] = $value['hotelname'].'(copy)';
            $adddata[$key]['update_time'] = time();
            $adddata[$key]['longitude'] = 0;
            $adddata[$key]['latitude'] = 0;
        }

        $result = D("hotel")->addAll($adddata);
        if($result == false){
            D("hotel")->rollback();
            $this->error("复制酒店基本信息失败");
        }

        $count = count($verifiData);
        for ($i=0; $i < $count; $i++) { 
            $returnid[] = (int)$result;
            $result++;
        }
        $chghid = I('post.ischg','','strip_tags');//集团酒店的标志
        if(!empty($chghid)){
            if(count($returnid)>1){
                $updatepidMap['id'] = array('in',array_slice($returnid, 1));
                D("hotel")->where($updatepidMap)->setField('pid',$returnid[0]);
            }
        }
        return $returnid;
    }

    /**
     * [复制用户表member]
     * @param  [array] $data      [校验数据]
     * @return [array] $memberid  [新增用户ID]
     */
    private function copymember($data){
        $memberid = array();
        foreach ($data as $key => $value) {
            $adddata[$key]['user'] = $value['member'];
            $adddata[$key]['password'] = md5('ZXT'.$value['password'].'ETV');
            $adddata[$key]['hid'] = $value['hid'];
            $adddata[$key]['phone'] = '';
            $adddata[$key]['birthday'] = time();
            $adddata[$key]['qq'] = '';
            $adddata[$key]['email'] = 'example@189.com';
            $adddata[$key]['t'] = time();
            $adddata[$key]['status'] = 1;
        }
        $result = D("member")->addAll($adddata);
        if($result === false){
            D('member')->rollback();
            $this->error("复制用户失败");
        }

        $count = count($data);
        for ($i=0; $i < $count; $i++) { 
            $accessResult = D("auth_group_access")->data(array('uid'=>$result,'group_id'=>3))->add();
            if($accessResult === false){
                D("auth_group_access")->rollback();
                $this->error('创建组别出错');
            }
            $memberid[] = (int)$result;
            $result++;
        }
        return $memberid;
    }

    /**
     * [新增酒店用户关联方法 hotel_user]
     * @param  [array] $memberid    [用户列表ID]
     * @param  [array] $hotellistid [酒店列表ID]
     * @param  [array] $verifiData  [校验数据]
     */
    private function copyhoteluser($memberid,$hotellistid,$verifiData){
        $count = count($memberid);
        for ($i=0; $i < $count; $i++) { 
            $vo[$i]['user_id'] = $memberid[$i];
            $vo[$i]['hotel_id'] = $hotellistid[$i];
            $vo[$i]['hid'] = $verifiData[$i]['hid'];
        }
        $hoteluser = D('hotel_user')->addAll($vo);
        if($hoteluser === false){
            D('hotel_user')->rollback();
            $this->error("酒店用户关联失败");
        }
    }

    /**
     * [替换文件路径方法]
     * 文件路径形式：/upload/content/6008/1505458748_766379486.jpg
     * @param  string $oldfilepath [被复制文件路径]
     * @param  string $newhid      [新的HID]
     * @return string $newfilepath [新的文件路径]
     */
    private function changefilename($oldfilepath='',$newhid=''){
        $file_arr = explode("/", $oldfilepath);
        return '/'.$file_arr['1'].'/'.$file_arr['2'].'/'.$newhid.'/'.$file_arr['4'];
    }

    /**
     * [复制欢迎页数据 hotel_welcome]
     * @param  [array] $hidarr [新旧hid列表]
     */
    private function copywelcome($hidarr){
        $field = "hid,title,type,cat,category_id,filepath,intro,audit_status,audit_time,upload_time,video_image,qrcode,price,sort,status,size";
        foreach ($hidarr as $key => $value) {
            $passhid[] = $value['passhid'];
            $newhid[$value['passhid']] = $value['newhid'];
        }
        $welcomeRMap['hid'] = $welcomeMap['hid'] = array('in',$passhid);
        $welcomeRMap['cat'] = 'welcome';
        $welcomeRMap['category_id'] = 0;
        $welcomeRList = D("hotel_resource")->where($welcomeRMap)->field($field)->select();
        if(!empty($welcomeRList)){
            foreach ($welcomeRList as $key => $value) {
                $welcomeRList[$key]['audit_time'] = time();
                $welcomeRList[$key]['video_image'] = '';
                $welcomeRList[$key]['qrcode'] = '';
                $welcomeRList[$key]['price'] = '';
                $welcomeRList[$key]['filepath'] = $this->changefilename($value['filepath'],$newhid[$value['hid']]);
                $welcomeRList[$key]['hid'] = $newhid[$value['hid']];
                $new_hid_arr[] = $newhid[$value['hid']];
            }
            $welResourceResult = D("hotel_resource")->addAll($welcomeRList);
            if($welResourceResult === false){
                D("hotel_resource")->rollback();
                $thie->error('复制欢迎图片资源失败');
            }
            foreach ($new_hid_arr as $key => $value) {
                $welAddData[$key]['hid'] = $value;
                $welAddData[$key]['resourceid'] = (int)$welResourceResult;
                $welResourceResult++; 
            }
            $welcomeResult = D("hotel_welcome")->addAll($welAddData);
            if($welcomeResult === false){
                D("hotel_welcome")->rollback();
                $this->error('欢迎页复制失败');
            }
        }
    }

    /**
     * [复制语言管理页数据  hotel_language]
     * @param  [array] $hidarr [新旧hid列表]
     */
    private function copylanguage($hidarr){
        $field = "hid,name,appellation,content,signer,langcodeid,sort,status";
        foreach ($hidarr as $key => $value) {
            $passhid[] = $value['passhid'];
            $newhid[$value['passhid']] = $value['newhid'];
        }
        $map['hid'] = array('in',$passhid);
        $languageList = D("hotel_language")->where($map)->field($field)->select();
        if(!empty($languageList)){
            foreach ($languageList as $key => $value) {
                $languageList[$key]['hid'] = $newhid[$value['hid']];
            }
        }
        $result = D("hotel_language")->addAll($languageList);
        if($result === false){
            D("hotel_language")->rollback();
            $this->error('语言管理复制失败');
        }
    }

    /**
     * [复制跳转管理页数据  hotel_jump]
     * @param  [array] $hidarr [新旧hid列表]
     */
    private function copyjump($hidarr){
        $jumpField = "hid,isjump,staytime,video_set,resourceid";
        $resourceField = "hid,title,type,cat,category_id,filepath,intro,audit_status,audit_time,upload_time,video_image,qrcode,price,sort,status,size";
        foreach ($hidarr as $key => $value) {
            $jumpVo = D("hotel_jump")->where('hid="'.$value['passhid'].'"')->field($jumpField)->find();
            if(!empty($jumpVo)){
                $jumpVo['hid'] = $value['newhid'];
                if($jumpVo['video_set']==1){
                    $resourceVo = D("hotel_resource")->where('id="'.$jumpVo['resourceid'].'"')->field($resourceField)->find();
                    $resourceVo['filepath'] = $this->changefilename($resourceVo['filepath'],$jumpVo['hid']);
                    $resourceVo['hid'] = $value['newhid'];
                    $resourceVo['intro'] = empty($resourceVo['intro'])?'':$resourceVo['intro'];
                    $resourceVo['audit_time'] = time();
                    $resourceVo['video_image'] = '';
                    $resourceVo['qrcode'] = '';
                    $resourceVo['price'] = '';
                    $resourceResult = D("hotel_resource")->data($resourceVo)->add();
                    if($resourceResult === false){
                        D("hotel_resource")->rollback();
                        $this->error('跳转资源复制失败');
                    }
                    $jumpVo['resourceid'] = $resourceResult;
                }
                $result = D("hotel_jump")->data($jumpVo)->add();
                if($result === false){
                    D("hotel_jump")->rollback();
                    $this->error('跳转设置复制失败');
                }     
            }
        }
    }

    /**
     * [复制酒店宣传]
     * @param  [array] $hidarr [新旧hid列表]
     */
    private function copyspread($hidarr){
        $field = "hid,title,type,cat,category_id,filepath,intro,audit_status,audit_time,upload_time,video_image,qrcode,price,sort,status,size";
        foreach ($hidarr as $key => $value) {
            $passhid[] = $value['passhid'];
            $newhid[$value['passhid']] = $value['newhid'];
        }
        $spreadRMap['hid'] = array('in',$passhid);
        $spreadRMap['cat'] = 'spread';
        $spreadRMap['category_id'] = 0;
        $resourceList = D("hotel_resource")->where($spreadRMap)->field($field)->select();
        if(!empty($resourceList)){
            foreach ($resourceList as $key => $value) {
                $resourceList[$key]['filepath'] = $this->changefilename($value['filepath'],$newhid[$value['hid']]);
                $resourceList[$key]['hid'] = $newhid[$value['hid']];
                $resourceList[$key]['audit_time'] = time();
                $resourceList[$key]['qrcode'] = '';
                $resourceList[$key]['price'] = '';
                $resourceList[$key]['video_image'] = '';
            }
            $resourceResult = D("hotel_resource")->addAll($resourceList);
            if($resourceResult === false){
                D("hotel_resource")->rollback();
                $this->error('酒店宣传资源复制失败');
            }
            foreach ($resourceList as $key => $value) {
                $spreaddata[$key]['hid'] = $value['hid'];
                $spreaddata[$key]['resourceid'] = (int)$resourceResult;
                $resourceResult++;
            }
            $spreadResult = D("hotel_spread")->addAll($spreaddata);
            if($spreadResult === false){
                D("hotel_spread")->rollback();
                $this->error('酒店宣传设置复制失败');
            }
        }
    }

    /**
     * [复制栏目及其资源 hotel_category hotel_resource]
     * @param  [array] $hidarr [新旧hid列表]
     */
    private function copycategory($hidarr){
        foreach ($hidarr as $key => $value) {
            $passhid[] = $value['passhid'];
            $newhid[$value['passhid']] = $value['newhid'];
        }
        $field = 'id,hid,name,modeldefineid,langcodeid,pid,sort,intro,icon,status,size';
        $firstMap['hid'] = array('in',$passhid);
        $firstMap['pid'] = 0;
        $firstCategory = D("hotel_category")->where($firstMap)->field($field)->select();
        if(!empty($firstCategory)){
            //用于查询二级栏目条件
            $pid_arr = array();
            //处理数据
            foreach ($firstCategory as $key => $value) {
                $pid_arr[] = $value['id'];
                if(!empty($value['icon'])){
                    $firstCategory[$key]['icon'] = $this->changefilename($value['icon'],$newhid[$value['hid']]);
                }else{
                    $firstCategory[$key]['icon'] = '';
                }
                $firstCategory[$key]['hid'] = $newhid[$value['hid']];
                if(empty($value['intro'])){
                    $firstCategory[$key]['intro'] = '';
                }
                unset($firstCategory[$key]['id']);
            }
            //一级栏目复制
            $firstResult = D("hotel_category")->addAll($firstCategory);
            if($firstResult === false){
                D('hotel_category')->rollback();
                $this->error('一级栏目复制失败');
            }
            foreach ($pid_arr as $key => $value) {
                $new_pid_arr[$value] = (int)$firstResult;
                $firstResult++;
            }

            //二级栏目复制BEGIN
            if(!empty($pid_arr)){
                $secondMap['hid'] = array('in',$passhid);
                $secondMap['pid'] = array('in',$pid_arr);
                $secondCategory = D("hotel_category")->where($secondMap)->field($field)->select();
                if (!empty($secondCategory)) {
                    //用于查询资源条件
                    $resourceSearch_arr = array();
                    $cid_arr = array();
                    foreach ($secondCategory as $key => $value) {
                        $cid_arr[] = $value['id'];
                        $resourceSearch_arr[$key]['cid'] = $value['id'];
                        if(empty($value['icon'])){
                            $secondCategory[$key]['icon'] = '';
                        }else{
                            $secondCategory[$key]['icon'] = $this->changefilename($value['icon'],$newhid[$value['hid']]);
                        }
                        $secondCategory[$key]['pid'] = $new_pid_arr[$value['pid']];
                        $secondCategory[$key]['hid'] = $newhid[$value['hid']];
                        if(empty($value['intro'])){
                            $secondCategory[$key]['intro'] = '';
                        }
                        unset($secondCategory[$key]['id']);
                    }
                    //二级栏目复制
                    $secondResult = D("hotel_category")->addAll($secondCategory);
                    // $secondResult = 139;//测试
                    if ($secondResult === false) {
                        D("hotel_category")->rollback();
                        $this->error('二级栏目复制失败');
                    }

                    foreach ($cid_arr as $key => $value) {
                        $newcid[$value] = (int)$secondResult;
                        $secondResult++;
                    }
                }

                //资源复制BEGIN
                if(!empty($cid_arr)){
                    $resourceMap['hid'] = array('in',$passhid);
                    $resourceMap['category_id'] = array('in',$cid_arr);
                    $resourceMap['cat'] = 'content';
                    $resourceField = "hid,title,type,cat,category_id,filepath,intro,audit_status,audit_time,upload_time,video_image,qrcode,price,sort,status,size";
                    //资源列表
                    $resourceList = D("hotel_resource")->where($resourceMap)->field($resourceField)->select();
                    if (!empty($resourceList)) {
                        foreach ($resourceList as $key => $value) {
                            $resourceList[$key]['filepath'] = $this->changefilename($value['filepath'],$newhid[$value['hid']]);
                            $resourceList[$key]['category_id'] = $newcid[$value['category_id']];
                            if(empty($value['video_image'])){
                                $resourceList[$key]['video_image'] = '';
                            }else{
                                $resourceList[$key]['video_image'] = $this->changefilename($value['video_image'],$newhid[$value['hid']]);
                            }
                            if(empty($value['qrcode'])){
                                $resourceList[$key]['qrcode'] = '';
                            }else{
                                $resourceList[$key]['qrcode'] = $this->changefilename($value['qrcode'],$newhid[$value['hid']]);
                            }
                            $resourceList[$key]['hid'] = $newhid[$value['hid']];
                            $resourceList[$key]['audit_time'] = time();
                            $resourceList[$key]['price'] = '';
                        }
                        $resourceResult = D("hotel_resource")->addAll($resourceList);
                        if($resourceResult === false){
                            D("hotel_resource")->rollback();
                            $this->error('栏目资源复制失败');
                        }
                    }

                    //视频轮播
                    $carouselMap['hid'] = array('in',$passhid);
                    $carouselMap['ctype'] = 'videohotel';
                    $carouselField = "hid,cid,ctype,title,intro,sort,filepath,file_type,video_image,upload_time,audit_status,audit_time,status,size";
                    $carouselList = D("hotel_carousel_resource")->where($carouselMap)->field($carouselField)->select();
                    if (!empty($carouselList)) {
                        foreach ($carouselList as $key => $value) {
                            $carouselList[$key]['cid'] = $newcid[$value['cid']];
                            if(empty($value['intro'])){
                                $carouselList[$key]['intro'] = '';
                            }
                            $carouselList[$key]['filepath'] = $this->changefilename($value['filepath'],$newhid[$value['hid']]);
                            if(empty($value['video_image'])){
                                $carouselList[$key]['video_image'] = '';
                            }else{
                                $carouselList[$key]['video_image'] = $this->changefilename($value['video_image'],$newhid[$value['hid']]);
                            }
                            $carouselList[$key]['hid'] = $newhid[$value['hid']];
                            $carouselList[$key]['audit_time'] = date("Y-m-d H:i:s");
                        }
                        $carouselResult = D("hotel_carousel_resource")->addAll($carouselList);
                        if($carouselResult === false){
                            D("hotel_carousel_resource")->rollback();
                            $this->error('资源视频轮播复制失败');
                        }
                    }
                }
            }
        }
    }

    /**
     * [复制集团通用栏目及其资源]
     * @param  [array] $chghid [集团HID]
     * @param  [array] $hidarr [新旧hid列表]
     */
    private function copychg($chghid,$hidarr){
        foreach ($hidarr as $key => $value) {
            if($value['passhid'] == $chghid){
                $newhid = $value['newhid'];
            }
        }
        //获取集团通用栏目
        $categoryAllList = D("hotel_chg_category")->where('hid="'.$chghid.'"')->select();
        $cNewId_arr = array(); //新一级栏目ID集合  key为就一级栏目id
        if(!empty($categoryAllList)){
            foreach ($categoryAllList as $key => $value) {
                $value['hid'] = $newhid;
                $value['filepath'] = $this->changefilename($value['filepath'],$newhid);
                if($value['pid'] == 0){
                    $Fid_arr[] = $value['id']; //一级栏目id
                    unset($value['id']);
                    $categoryFList[] = $value;
                }else{
                    $SPid_arr[] = $value['id']; //二级栏目id
                    unset($value['id']);
                    $categorySList[] = $value;
                }
            }
            if(!empty($categoryFList)){
                $categoryFResult = D("hotel_chg_category")->addAll($categoryFList);
                if($categoryFResult === false){
                    D("hotel_chg_category")->rollback();
                    $this->error('复制集团通用栏目失败');
                }
            }
            $categoryFcount = count($categoryFList);
            for ($i=0; $i < $categoryFcount; $i++) { 
                $cNewId_arr[$Fid_arr[$i]] = (int)$categoryFResult; //复制一集栏目后新的id集合
                $categoryFResult++;
            }
            if (!empty($categorySList)) {
                foreach ($categorySList as $key => $value) {
                    $categorySList[$key]['pid'] = $cNewId_arr[$value['pid']];
                }
                $categorySResult = D("hotel_chg_category")->addAll($categorySList);
                if($categorySResult === false){
                    D("hotel_chg_category")->rollback();
                    $this->error('复制集团通用栏目失败');
                }
                foreach ($SPid_arr as $key => $value) {
                    $chg_cid_arr[$value] = (int)$categorySResult;
                    $categorySResult++;
                }

                //复制集团通用栏目资源START
                $chgresourceMap['hid'] = $chghid;
                $chgresourceField = "hid,cid,title,intro,sort,filepath,file_type,icon,price,status,audit_status,size";
                $chgresourceList = D("hotel_chg_resource")->where($chgresourceMap)->field($chgresourceField)->select();
                if (!empty($chgresourceList)) {
                    foreach ($chgresourceList as $key => $value) {
                        $chgresourceList[$key]['hid'] = $newhid;
                        $chgresourceList[$key]['cid'] = $chg_cid_arr[$value['cid']];
                        $chgresourceList[$key]['filepath'] = $this->changefilename($value['filepath'],$newhid);
                        if(empty($value['icon'])){
                            $chgresourceList[$key]['intro'] = '';
                        }else{
                            $chgresourceList[$key]['icon'] = $this->changefilename($value['icon'],$newhid);
                        }
                        $chgresourceList[$key]['upload_time'] = date("Y-m-d H:i:s");
                        $chgresourceList[$key]['audit_time'] = date("Y-m-d H:i:s");
                    }
                    $chgresourceResult = D("hotel_chg_resource")->addAll($chgresourceList);
                    if($chgresourceResult === false){
                        D("hotel_chg_resource")->rollback();
                        $this->error('集团通用栏目资源复制失败');
                    }
                }

                //复制集团通用栏目视频轮播资源START
                $chgcarouselMap['hid'] = $chghid;
                $chgcarouselMap['ctype'] = 'videochg';
                $chgcarouseField = "hid,cid,ctype,title,intro,sort,filepath,file_type,video_image,upload_time,audit_status,audit_time,status,size";
                $chgcarouselList = D("hotel_carousel_resource")->where($chgcarouselMap)->field($chgcarouseField)->select();
                if(!empty($chgcarouselList)){
                    foreach ($chgcarouselList as $key => $value) {
                        $chgcarouselList[$key]['hid'] = $newhid;
                        $chgcarouselList[$key]['filepath'] = $this->changefilename($value['filepath'],$newhid);
                        if(empty($value['video_image'])){
                            $chgcarouselList[$key]['video_image'] = '';
                        }else{
                            $chgcarouselList[$key]['video_image'] = $this->changefilename($value['video_image'],$newhid);
                        }
                        $chgcarouselList[$key]['audit_time'] = date("Y-m-d H:i:s");
                        $chgcarouselList[$key]['cid'] = $chg_cid_arr[$value['cid']];
                    }
                    $chgcarouseResult = D("hotel_carousel_resource")->addAll($chgcarouselList);
                    if($chgcarouseResult === false){
                        D("hotel_carousel_resource")->rollback();
                        $this->error('集团通用栏目视频轮复制失败');
                    }
                }
            }
        }
        return $cNewId_arr;
    }

    /**
     * [复制酒店容量表]
     * @param  [array] $hidarr [新旧hid列表]
     */
    private function copyvolume($hidarr){
        foreach ($hidarr as $key => $value) {
            $passhid[] = $value['passhid'];
            $newhid[$value['passhid']] = $value['newhid'];
        }
        $volumeMap['hid'] = array('in',$passhid);
        $volumeField = "hid,content_size,topic_size,ad_size,devicebg_size,chg_size,popupad_size,carousel_size";
        $volumeList = D("hotel_volume")->where($volumeMap)->field($volumeField)->select();
        if(!empty($volumeList)){
            foreach ($volumeList as $key => $value) {
                $volumeList[$key]['hid'] = $newhid[$value['hid']];
                $volumeList[$key]['content_size'] = empty($value['content_size'])?0:$value['content_size'];
                $volumeList[$key]['topic_size'] = empty($value['topic_size'])?0:$value['topic_size'];
                $volumeList[$key]['ad_size'] = empty($value['ad_size'])?0:$value['ad_size'];
                $volumeList[$key]['devicebg_size'] = empty($value['devicebg_size'])?0:$value['devicebg_size'];
                $volumeList[$key]['chg_size'] = empty($value['chg_size'])?0:$value['chg_size'];
                $volumeList[$key]['popupad_size'] = empty($value['popupad_size'])?0:$value['popupad_size'];
                $volumeList[$key]['carousel_size'] = empty($value['carousel_size'])?0:$value['carousel_size'];
            }
            $volumeResult = D("hotel_volume")->addAll($volumeList);
            if($volumeResult === false){
                D("hotel_volume")->rollback();
                $this->error('复制酒店容量表失败');
            }
        }
    }

    /**
     * [复制topic关联表]
     * @param  [array] $hidarr [新旧hid列表]
     */
    private function copytopic($hidarr){
        foreach ($hidarr as $key => $value) {
            $passhid[] = $value['passhid'];
            $newhid[$value['passhid']] = $value['newhid'];
        }
        $map['hid'] = array('in',$passhid);
        $list = D("hotel_topic")->where($map)->field('hid,topic_id')->select();
        if(!empty($list)){
            $adddatatopic = array();
            foreach ($list as $key => $value) {
                $adddatatopic[$key]['hid'] = $newhid[$value['hid']];
                $adddatatopic[$key]['topic_id'] = $value['topic_id'];
            }
            $result = D("hotel_topic")->addAll($adddatatopic);
            if($result === false){
                D("hotel_topic")->rollback();
                $this->error('复制酒店通用栏目关联失败');
            }
        }
    }

    /**
     * [复制酒店集团栏目关联表]
     * @param  [array] $hidarr     [新旧hid集合]
     * @param  [array] $cNewId_arr [集团酒店新一级栏目ID]
     */
    private function copychglist($hidarr,$cNewId_arr){
        foreach ($hidarr as $key => $value) {
            $passhid[] = $value['passhid'];
            $newhid[$value['passhid']] = $value['newhid'];
        }
        $map['hid'] = array('in',$passhid);
        $list = D("hotel_chglist")->where($map)->field('hid,phid,chg_cid')->select();
        if(!empty($list)){
            $adddatachg = array();
            $chghid = I('post.ischg','','strip_tags');//集团酒店的标志
            if(!empty($cNewId_arr)){
                foreach ($list as $key => $value) {
                    $adddatachg[$key]['hid'] = $newhid[$value['hid']];
                    $adddatachg[$key]['phid'] = $newhid[$chghid];
                    $adddatachg[$key]['chg_cid'] = $cNewId_arr[$value['chg_cid']];
                }
            }else{
                foreach ($list as $key => $value) {
                    $adddatachg[$key]['hid'] = $newhid[$value['hid']];
                    $adddatachg[$key]['phid'] = $value['phid'];
                    $adddatachg[$key]['chg_cid'] = $value['chg_cid'];
                }
                
            }
            $result = D("hotel_chglist")->addAll($adddatachg);
            if($result === false){
                D("hotel_chglist")->rollback();
                $this->error('复制酒店集团栏目关联失败');
            }
        }
    }

    /**
     * [动态校验酒店编号和用户账号合法性]
     * @return [json] $callback [结果返回]
     */
    public function checkVerity(){
        $hid_arr = I('post.hidarr');
        $member_arr = I('post.memberarr');
        $hMap['hid'] = array('in',$hid_arr);
        $mMap['user'] = array('in',$member_arr);
        $hList = D("hotel")->where($hMap)->field('hid')->select();
        $mList = D("member")->where($mMap)->field('user')->select();
        $str = '';

        if(empty($hList) && empty($mList)){
            $callback['status'] = 200;
            $callback['message'] = 'Success';
        }elseif(!empty($hList) && !empty($mList)){ 
            foreach ($hList as $key => $value) {
                $str .= $value['hid'].'&nbsp';
            }
            $str .= "酒店编号已经存在！";
            $str .="&nbsp&nbsp&nbsp&nbsp&nbsp";
            foreach ($mList as $key => $value) {
                $str .= $value['user'].'&nbsp';
            }
            $str .= "用户编号已经存在！";
            $callback['status'] = 0;
            $callback['message'] = $str;
        }elseif(!empty($hList)){
            foreach ($hList as $key => $value) {
                $str .= $value['hid'].'&nbsp';
            }
            $str .= "酒店编号已经存在！";
            $callback['status'] = 0;
            $callback['message'] = $str;
        }elseif(!empty($mList)){
            foreach ($mList as $key => $value) {
                $str .= $value['user'].'&nbsp';
            }
            $str .= "用户编号已经存在！";
            $callback['status'] = 0;
            $callback['message'] = $str;
        }
        echo json_encode($callback);
    }

    /**
     * [更新通用一级栏目json数据]
     * @param  [string] $hid [酒店编号]
     * @param  [array] $gid [栏目组ID集合]
     */
    private function updatejson_one($hid,$gid){
        $map['id'] = array('in',$gid);
        $map['status'] = 1;
        $list = D("topic_group")->where($map)->field('id,title,name,en_name,intro,icon')->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $list[$key]['nexttype'] = 'topic_second';
            }
            $jsondata = json_encode($list);
        }else{
            $jsondata = '';
        }
        if (!is_dir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid)) {
            mkdir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid);
        }
        $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid.'/topic_first.json';
        file_put_contents($filename, $jsondata);
    }

    /**
     * [更新通用二级栏目json数据]
     * @param  [string] $hid [酒店编号]
     * @param  [array] $gid [栏目组ID集合]
     */
    private function updatejson_two($hid,$gid){
        $map['groupid'] = array('in',$gid);
        $map['status'] = 1;
        $list = D("topic_category")->where($map)->field('id,groupid,name,modeldefineid,icon')->order('sort')->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $value['nexttype'] = 'topicresource';
                $plist[$value['groupid']][] = $value;
            }
            $jsondata = json_encode($plist);
        }else{
            $jsondata = '';
        }
        $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid.'/topic_second.json';
        file_put_contents($filename,$jsondata);
    }

    /**
     * [更新通用栏目资源json数据]
     * @param  [string] $hid [酒店编号]
     * @param  [array] $gid [栏目组ID集合]
     */
    private function updatejson_resource($hid,$gid){
        $map['gid'] = array('in',$gid);
        $map['audit'] = 1;
        $map['audit_status'] = 4;
        $list = D("topic_resource")->where($map)->field('id,cid,gid,title,type,video,image,video_image,intro,sort')->order('sort')->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $plist[$value['cid']][] = $value;
            }
            $jsondata = json_encode($plist);
        }else{
            $jsondata = '';
        }
        $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid.'/topicresource.json';
        file_put_contents($filename, $jsondata);
    }

    /**
     * 更新视频轮播(云宣教)json数据
     */
    private function updatecarousel_resource($hid){
        $map['hid'] = $hid;
        $map['status'] = 1;
        $map['audit_status'] = 4;
        $field = "hid,cid,ctype,title,intro,sort,filepath,video_image";
        $list = D("hotel_carousel_resource")->where($map)->field($field)->order('sort')->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $clist[$value['ctype']][] = $value;
            }
            $hotel_list = array();//酒店视频轮播
            $chg_list = array();//集团视频轮播
            if (!empty($clist['videohotel'])) {
                foreach ($clist['videohotel'] as $key => $value) {
                    $hotel_list[$value['cid']][] = $value;
                }
            }
            if(!empty($clist['videochg'])){
                foreach ($clist['videochg'] as $key => $value) {
                    $chg_list[$value['cid']][] = $value;
                }
            }
            if (!empty($hotel_list)) {
                $jsondata_hotel = json_encode($hotel_list);
            }else{
                $jsondata_hotel = '';
            }
            if (!empty($chg_list)) {
                $jsondata_chg = json_encode($chg_list);
            }else{
                $jsondata_chg = '';
            }
        }else{
            $jsondata_hotel = '';
            $jsondata_chg = '';
        }
        
        if(!is_dir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.hid)){
            mkdir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.hid);
        }
        $filename_hotel = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid.'/videohotel.json';
        file_put_contents($filename_hotel, $jsondata_hotel);
        $filename_chg = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid.'/videochg.json';
        file_put_contents($filename_chg, $jsondata_chg);
    }

}