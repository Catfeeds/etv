<?php
namespace Admin\Controller;

use Think\Controller;

class TempletController extends Controller {

    // static protected $serverUrl = "http://www.189itv.com/etv/Public";
    // static protected $serverUrl = "http://localhost/etv2.0/Public";
	static protected $serverUrl = "http://61.143.52.102:9090/etv/Public";
    //获取酒店对应的栏目
    public function gettemplet(){
        $id = I('get.id','','strip_tags');
        $field = "zxt_hotel.hid,zxt_hotel_launcher_web.web_name,zxt_hotel_launcher_web.status";
        $map['zxt_hotel.id'] = $id;
        $hotel = D("hotel")->where($map)->field($field)->join('zxt_hotel_launcher_web ON zxt_hotel.launcher_skinid=zxt_hotel_launcher_web.id','left')->select();
        $launcher_web = $hotel[0];
        if(empty($hotel[0]['web_name']) || empty($hotel[0]['status']) || $hotel[0]['status']==0){
            $launcherMap['web_name'] != null;
            $launcher_web = D("hotel_launcher_web")->where($launcherMap)->find();
        }
        $this->redirect($launcher_web['web_name']."?hid=".$hotel[0]['hid']);
    }
	public function templetone_one($hid){
        $this->assign('hid',$hid);
		$this->display();
	}

	private function Callback($status,$info){
        $data['status'] = $status;
        $data['data'] = $info;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

	public function templetone_getHotelCategory_firstlevel(){

		$hid = I('post.hid','','strtoupper');
        $room = I('post.room','','strip_tags');
        $mac = I('post.mac','','strtoupper');

        if(empty($hid) || empty($mac)){
            $this->Callback(10000,'hid or mac is empty');
        }
        //酒店自身
        $map['hid'] = $hid;
        $map['modeldefineid'] = array('in',array(1,2));//集团菜单 上级菜单
        $map['pid'] = 0;
        $map['status'] = 1;
        $listHotel = D("hotel_category")->where($map)->order('sort asc')->select();
        //集团通用栏目
        $listChg = array();
        $chglist = D("hotel_chglist")->where('phid="'.$hid.'"')->field('chg_cid')->select();
        if(!empty($chglist)){
            foreach ($chglist as $key => $value) {
                $chglist_arr[] = $value['chg_cid'];
            }
            $chgMap['id'] = array('in',$chglist_arr);
            $chgMap['status'] = 1;
            $listChg = D("hotel_chg_category")->where($chgMap)->order('sort')->select();
        }
        //通用栏目
        $listTopic = array();
        $topiclist = D("hotel_topic")->where('hid="'.$hid.'"')->field('topic_id')->select();
        if(!empty($topiclist)){
            foreach ($topiclist as $k => $v) {
                $topiclist_arr[] = $v['topic_id'];
            }
            $topicMap['id'] = array('in',$topiclist_arr);
            $topicMap['status'] = 1;
            $listTopic = D("topic_group")->where($topicMap)->select();
        }

        $list = array();
        if(!empty($listHotel)){
            $i = 0;
            foreach ($listHotel as $key => $value) {
                $list[$i]['id'] = $value['id']; 
                $list[$i]['name'] = $value['name']; 
                $list[$i]['pid'] = $value['pid']; 
                $list[$i]['icon'] = self::$serverUrl.$value['icon']; 
                $list[$i]['hid'] = $value['hid']; 
                $list[$i]['modeldefineid'] = $value['modeldefineid'];
                $list[$i]['langcodeid'] = $value['langcodeid'];
                $list[$i]['sort'] = $value['sort'];
                $list[$i]['intro'] = $value['intro'];
                $list[$i]['votype'] = "hotel";
                $i++;
            }
        }
        if(!empty($listChg)){
            $j = count($list);
            foreach ($listChg as $key => $value) {
                $list[$j]['id'] = $value['id']; 
                $list[$j]['name'] = $value['name']; 
                $list[$j]['pid'] = $value['pid']; 
                $list[$j]['icon'] = self::$serverUrl.$value['filepath']; 
                $list[$j]['hid'] = $value['hid'];
                $list[$j]['modeldefineid'] = $value['modeldefineid'];
                $list[$j]['langcodeid'] = $value['langcodeid'];
                $list[$j]['sort'] = $value['sort'];
                $list[$j]['intro'] = $value['intro'];
                $list[$j]['votype'] = "chg";
                $j++;
            }
        }
        if(!empty($listTopic)){
            $k = count($list);
            foreach ($listTopic as $kk => $vv) {
                $list[$k]['id'] = $vv['id'];
                $list[$k]['name'] = $vv['name'];
                $list[$k]['pid'] = 0;
                $list[$k]['icon'] = self::$serverUrl.$vv['icon'];
                $list[$k]['hid'] = $hid;
                $list[$k]['modeldefineid'] = 3;
                $list[$k]['langcodeid'] = 0;
                $list[$k]['sort'] = $vv['id'];
                $list[$k]['intro'] = $vv['intro'];
                $list[$k]['votype'] = "topic";
                $k++;
            }
        }

        if(!empty($list)){
            $this->Callback(200,$list);
        }else{
            $this->Callback(404,null);
        }
	}

    //获取酒店自身二级栏目
    public function templetone_second(){

        $hid = I('request.hid','','strtoupper');
        $pid = I('request.pid','','intval');
        $votype = I('request.votype','','strip_tags');

        if(empty($hid) || empty($pid) || empty($votype)){
            $this->error('系统错误：参数错误！');
        }

        if($votype == "hotel"){
            $map['zxt_hotel_category.status'] = 1;
            $map['zxt_hotel_category.hid'] = $hid;
            $map['zxt_hotel_category.pid'] = $pid;
            $field = "zxt_hotel_category.id,zxt_hotel_category.hid,zxt_hotel_category.name,zxt_hotel_category.modeldefineid,zxt_hotel_category.langcodeid,zxt_hotel_category.pid,zxt_hotel_category.sort,zxt_hotel_category.intro,zxt_hotel_category.icon,zxt_modeldefine.codevalue,zxt_modeldefine.packagename,zxt_modeldefine.classname";
            $list = D("hotel_category")->where($map)->field($field)->join("zxt_modeldefine ON zxt_hotel_category.modeldefineid=zxt_modeldefine.id")->order('sort asc')->select();
        }elseif($votype == "chg"){
            $map['zxt_hotel_chg_category.status'] = 1;
            $map['zxt_hotel_chg_category.hid'] = $hid;
            $map['zxt_hotel_chg_category.pid'] = $pid;
            $field = "zxt_hotel_chg_category.id,zxt_hotel_chg_category.hid,zxt_hotel_chg_category.name,zxt_hotel_chg_category.modeldefineid,zxt_hotel_chg_category.langcodeid,zxt_hotel_chg_category.pid,zxt_hotel_chg_category.sort,zxt_hotel_chg_category.intro,zxt_hotel_chg_category.filepath as icon,zxt_modeldefine.codevalue,zxt_modeldefine.packagename,zxt_modeldefine.classname";
            $list = D("hotel_chg_category")->field($field)->where($map)->join("zxt_modeldefine ON zxt_hotel_chg_category.modeldefineid=zxt_modeldefine.id")->order('sort asc')->select();
        }elseif($votype == "topic"){
            $map['zxt_topic_category.status'] = 1;
            $map['zxt_topic_category.groupid'] = $pid;
            $field = "zxt_topic_category.id,zxt_topic_category.name,zxt_topic_category.modeldefineid,zxt_topic_category.langcodeid,zxt_topic_category.sort,zxt_topic_category.icon,zxt_modeldefine.codevalue,zxt_modeldefine.packagename,zxt_modeldefine.classname";
            $list = D("topic_category")->field($field)->where($map)->join("zxt_modeldefine ON zxt_topic_category.modeldefineid=zxt_modeldefine.id")->order('sort')->select();
        }else{
            $this->error('系统错误：栏目类型参数错误，请联系后台工作人员');
        }

        if(!empty($list)){
            if($list[0]['codevalue']>199){ //判断是否是apk
                $this->error('系统提示：此栏目为apk调用层,具体调用请使用盒子端演示！');
            }
            foreach ($list as $key => $value) {
                if($value['icon']){
                    $icon_arr = explode("/", $value['icon']);
                    $icon_name = $icon_arr[count($icon_arr)-1];
                    $list[$key]['icon'] = $icon_name;
                }
                if($votype == "chg"){
                    $list[$key]['votype'] = "chg";
                }elseif($votype == "hotel"){
                    $list[$key]['votype'] = "hotel";
                }elseif($votype == "topic"){
                    $list[$key]['votype'] = "topic";
                    $list[$key]['intro'] = "";
                    $list[$key]['hid'] = $hid;
                }
            }
            $this->assign('list',$list);
            $this->display();
        }else{
            $this->assign('list',"");
            $this->display();
        }
    }

    //获取二级栏目资源列表
    public function templetone_getHotelResource_second(){

        $hid = I('request.hid','','strtoupper');
        $cid = I('request.cid','','intval');
        $votype = I('request.votype','','strip_tags');

        if (empty($hid) || empty($cid) || empty($votype)) {
            $this->Callback(10000,'hid,id or votype is empty');
        }

        $map['hid'] = $hid;
        if($votype == "chg"){
            $map['cid'] = $cid;
            $model = D("hotel_chg_resource");
            $field = "id,hid,cid,title,intro,sort,filepath,file_type,filepath";
        }elseif($votype == "hotel"){
            $map['category_id'] = $cid;
            $model = D("hotel_resource");
            $field = "id,hid,category_id as cid,title,intro,sort,type as file_type,filepath";
        }elseif($votype == "topic"){
            $map['cid'] = $cid;
            $model = D("topic_resource");
            $field = "id,cid,title,intro,sort,type as file_type,video,image";
        }else{
            $this->Callback(10000,"the votype is wrong!");
        }
        $map['status'] = 1;
        $map['audit_status'] = 4;

        $list = $model->where($map)->field($field)->order('sort asc')->select();

        if(!empty($list)){
            foreach ($list as $key => $value) {
                if(!empty($value['filepath'])){
                    $list[$key]['filepath'] = self::$serverUrl.$value['filepath'];
                }else{
                    $list[$key]['filepath'] = "";
                }
                if($votype=="topic" && $value['file_type']==1 && !empty($value['video'])){
                    $list[$key]['filepath'] = self::$serverUrl.$value['video'];
                }elseif($votype=="topic" && $value['file_type']==2 && !empty($value['image'])){
                    $list[$key]['filepath'] = self::$serverUrl.$value['image'];
                }elseif(empty($value['video']) && empty($value['image']) && $votype=="topic"){
                    $list[$key]['filepath'] = "";
                }
            }
            $this->Callback(200,$list);
        }else{
            $this->Callback(404,null);
        }
    }
}
