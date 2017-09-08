<?php
// +-------------------------------------------------------------------------
// | Author: Lockin
// +-------------------------------------------------------------------------
// | Date: 2017/08/10
// +-------------------------------------------------------------------------
// | Description: 弹窗广告设置控制器
// +-------------------------------------------------------------------------

namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
class HotelAdsetController extends ComController {

	/**
     * 查询
     */
    public function _map(){
        $map = array();
        $data = session(session_id());
        $hotelid = 0;
        if(!empty($_POST['hid'])){
            if($_POST['hid'] != 'hid-1gg'){ //自定义查询所有酒店
                $map['hid'] = $_POST['hid'];
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
            if (!empty($data['content_hid'])) {
                $map['hid'] = $data['content_hid'];
                $vo = M('hotel')->getByHid($data['content_hid']);
                $hotelid = $vo['id'];
            }
        }
        $this->assign('hid',$map['hid']);
        $category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
        $category = $tree->get_tree(0,$str,$hotelid);
        $this->assign('pHotel',$category);
        return $map;
    }

    /**
     * 广告弹窗设置主页页面
     */
	public function index(){
        $map = $this->_map();
        $hid_condition = $this->isHotelMenber();
        if($hid_condition && empty($map)){
            $map['hid'] = $hid_condition;
        }
        $list = $this->_list(D("hotel_adset"),$map,10,'hid,hour,minute');
        $this -> assign("list",$list);
        $this -> display();
	}

    
	public function _before_add(){
        $this->isHotelMenber();
        $hotelid = 0;
        $data = session(session_id());
        if(!empty($data['content_hid'])){
            $vo=M('hotel')->getByHid($data['content_hid']);
            $hotelid=$vo['id'];
        }
        $Langcode = D("Langcode");
        $list = $Langcode->where("status=1")->order("id asc")->select();
        $this->assign("langlist",$list);
        $category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
        $category = $tree->get_tree(0,$str,$hotelid);
        $this->assign('pHotel',$category);
        $this->isHotelMenber();
        $category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $this->_assign_hour_minute();
    }

    /**
     * 广告弹窗设置新增页面
     */
    public function add(){
        $video_resourceList = $this->searchResource(1);
    	$image_resourceList = $this->searchResource(2);
        $this->assign('video_resourceList',$video_resourceList);
    	$this->assign('image_resourceList',$image_resourceList);
    	$this->display();
    }

 	public function _before_edit(){
 		$this->_assign_hour_minute();
 	}

    /**
     * 广告弹窗设置修改页面
     */
    public function edit(){	
    	$ids = I('post.ids','','strip_tags');
    	if(count($ids)!=1){
    		$this->error('系统提示：参数错误');
    	}

    	$vo = D("hotel_adset")->where('id="'.$ids["0"].'"')->find();
    	$videoSelectList = array();  //已选视频资源
    	$videoHadList = array();     //除去已选视频资源的所有视频资源
        $resourceId_str = "";
        $imageSelectList = array();  //已选图片资源
        $imageHadList = array();     //除去已选图片资源的所有图片资源
        switch ($vo['ad_type']) {
            case '1':
                $videoSelectList = $this->searchSelectResource($vo['id'],1);
                $resourceId_str = $this->arrToStr($videoSelectList,"id");
                $videoHadList = $this->searchExceptResource($resourceId_str,1);
                $imageSelectList = array();
                $imageHadList = $this->searchExceptResource("",2);
                break;
            case '2':
                $videoSelectList = array();
                $videoHadList = $this->searchExceptResource("",1);
                $imageSelectList = $this->searchSelectResource($vo['id'],2);
                $imageId_str = $this->arrToStr($imageSelectList,"id");
                $imageHadList = $this->searchExceptResource($imageId_str,2);
                break;
            case '3':
                $videoSelectList = array();
                $resourceId_str = "";
                $videoHadList = $this->searchExceptResource("",1);
                $imageSelectList = array();
                $imageHadList = $this->searchExceptResource("",2);
                break;
            default:
                $videoSelectList = array();
                $resourceId_str = "";
                $videoHadList = $this->searchExceptResource("",1);
                $imageSelectList = array();
                $imageHadList = $this->searchExceptResource("",2);
                break;
        }

    	$this->assign('videoSelectList',$videoSelectList);
    	$this->assign('videoHadList',$videoHadList);
        $this->assign('resourceId_str',$resourceId_str);
        $this->assign('imageSelectList',$imageSelectList);
    	$this->assign('imageHadList',$imageHadList);
    	$this->assign('vo',$vo);
    	$this->display();
    }

    /**
     * 保存设置方法
     */
    public function update(){
        $data = $this->filterDate();
        $id = I('post.id','','intval');

        //判断相同时间（时间段内）不能创建设置
        $time = intval($data['hour']*60)+intval($data['minute']);
        $overtime = 30;//超出时间 单位分钟
        $checkTimeResult = $this->checkTime($data['hid'],$time,$overtime,$id);
        if($checkTimeResult==false){
            $this->error("系统提示：设置时间不在可允许的规定范围内,现允许相邻设置时间为".$overtime."分钟");
        }
        $model = D("hotel_adset");
        $model->startTrans();
        $releateResult = true;  //绑定结果
    	if(!empty($id)){  //修改
    		$remark = "修改";
            $adsetMap['id'] = $releateMap['adset_id'] = $id;
            $vo = $model->where($adsetMap)->find();
			$adsetId = D("hotel_adset")->where($adsetMap)->data($data)->save();
            
            if($data['ad_type']==1){
                $videoresourceId = trim(I('post.videoresourceId','','strip_tags'),","); //新资源id集合
                if($data['ad_type']==$vo['ad_type']){
                    $voVideoReleateList = D("hotel_adset_adresource")->field('adresource_id')->where($releateMap)->order('sort')->select();
                    $voVideoResourceId_str = $this->arrToStr($voVideoReleateList,"adresource_id");//旧资源id集合
                    if ($videoresourceId != $voVideoResourceId_str) {
                        $delResult = $this->delReleate($id); //删除旧的
                        $addResult = $this->addVideoReleate($videoresourceId,$data['hid'],$id); //添加新的
                        if ($delResult===false || $addResult===false) {
                            $releateResult = false;
                        }
                    }
                }else{
                    if($vo['ad_type']==2){
                        $delResult = $this->delReleate($id); //删除旧的
                        $addResult = $this->addVideoReleate($videoresourceId,$data['hid'],$id); //添加新的
                        if ($delResult===false || $addResult===false) {
                            $releateResult = false;
                        }
                    }elseif($vo['ad_type']==3){
                        $releateResult = $this->addVideoReleate($videoresourceId,$data['hid'],$id); //添加新的
                    }
                }
            }
            if($data['ad_type']==2){
                $imageId = I('post.imageid','','intval');
                if($data['ad_type']==$vo['ad_type']){
                    $voImageReleate = D("hotel_adset_adresource")->field('adresource_id')->where($releateMap)->find();
                    if($imageId != $voImageReleate['adresource_id']){
                        $releateResult = D("hotel_adset_adresource")->where($releateMap)->setField('adresource_id',$imageId);
                    }
                }else{
                    if($vo['ad_type']==1){
                        $delResult = $this->delReleate($id); //删除旧的
                        $addResult = $this->addImageReleate($imageId,$data['hid'],$id);//添加新的
                        if ($delResult===false || $addResult===false) {
                            $releateResult = false;
                        }
                    }
                    if($vo['ad_type']==3){
                        $releateResult = $this->addImageReleate($imageId,$data['hid'],$id);//添加新的
                    }
                }
            }
            if($data['ad_type']==3){
                if($data['ad_type']!=$vo['ad_type']){
                    $releateResult = $this->delReleate($id);
                }
            }

    	}else{
    		$remark = "添加";
    		$adsetId = $model->data($data)->add();
            
            //视频添加绑定方法
            if($data['ad_type'] == 1){
                $videoresourceId = trim(I('post.videoresourceId','','strip_tags'),",");
                $releateResult = $this->addVideoReleate($videoresourceId,$data['hid'],$adsetId);
            }
            //图片添加绑定方法
            if($data['ad_type'] == 2){
                $imageId = I('post.imageid','','intval');
                $releateResult = $this->addImageReleate($imageId,$data['hid'],$adsetId);
            }
    	}

        //判断容量是否符合
        $hotelAdSpace_vo = D("hotel")->where('hid="'.$data['hid'].'"')->field('ad_space')->find();
        $checkPopupResulet = $this->checkpopupad($data['hid'],$hotelAdSpace_vo['ad_space']);
        if($checkPopupResulet === false){
            $this->error('选取总容量超标',U('index'));
            die();
        }

        //更新容量
        $popupSizeResult = $this->updatePopupadSize($data['hid'],$checkPopupResulet);

    	if($adsetId!==false && $releateResult!==false && $popupSizeResult!==false){
    		$model->commit();
    		addlog($remark."广告设置成功"."修改酒店编号为".$data['hid']);
    		$this->success($remark."成功",U('index'));
    	}else{	
    		$model->rollback();
    		$this->success($remark."失败",U('index'));
    	}
    }

     /**
     * 查看所选资源列表
     */
	public function detail(){
        $id = I('request.id','','intval');
		$type = I('request.type','','intval');
        $list = $this->searchSelectResource($id,$type);
		echo json_encode($list);
	}

    /**
     * 删除弹窗设置
     */
	public function delete(){
		$ids = I("post.ids",'','strip_tags');

		if(count($ids)<1){
			$this->error('系统提示：参数错误');
		}

		$model = D("hotel_adset");
		$model->startTrans();
		$adsetMap['id'] = $releateMap['adset_id'] = array('in',$ids);

		$delAdsetResult = D("hotel_adset")->where($adsetMap)->delete();
		$delReleateResult = D("hotel_adset_adresource")->where($releateMap)->delete();

		if($delAdsetResult!==false && $delReleateResult!==false){
			$model->commit();
			addlog("删除广告设置");
			$this->success('删除成功');
		}else{
			$model->rollback();
			$this->error('删除失败');
		}
	}

    /**
     * @return 时分
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
     * 查询资源
     * @param  int $filetype 资源类型
     * @return array  $resourceList 资源列表
     */
    private function searchResource($filetype=false){
        $map['status'] = 1;
        $map['audit_status'] = 4;
        if($filetype!==false){
            $map['filepath_type'] = $filetype;
        }
        $resourceList = D("hotel_adresource")->where($map)->field('id,title,filepath')->order('update_time desc')->select();
        return $resourceList;
    }

    /**
     * 查询设置关联的资源列表
     * @param  int $id 设置列表ID
     * @param  int $type 资源类型
     * @return array  $selectList  关联资源集合
     */
    private function searchSelectResource($id,$type){
        $selectMap['zxt_hotel_adset_adresource.adset_id'] = $id;
        $selectMap['zxt_hotel_adresource.status'] = 1;
        $selectMap['zxt_hotel_adresource.audit_status'] =  4;
        $selectMap['zxt_hotel_adresource.filepath_type'] = $type;
        $selectList = D("hotel_adset_adresource")->where($selectMap)->join('zxt_hotel_adresource ON zxt_hotel_adset_adresource.adresource_id=zxt_hotel_adresource.id')->field("zxt_hotel_adresource.id,zxt_hotel_adresource.title,zxt_hotel_adresource.filepath")->order('zxt_hotel_adset_adresource.sort')->select();
        return $selectList;
    }

    /**
     * 查询所有未关联的资源列表
     * @param  string $resourceId_arr 已选资源id集合
     * @return array $ExceptList     未选视频资源集合
     */
    private function searchExceptResource($resourceId_str="",$type){
        if(!empty($resourceId_str)){
            $ExceptMap['id'] = array('not in',$resourceId_str);
        }
        $ExceptMap['status'] = 1;
        $ExceptMap['audit_status'] = 4;
        $ExceptMap['filepath_type'] = $type;
        $ExceptList = D("hotel_adresource")->where($ExceptMap)->field('id,title,filepath')->select();
        return $ExceptList;
    }

    /**
     * 过滤提交数据
     * @return $data 广告弹窗设置数据
     */
    private function filterDate(){
        $data['hid'] = I('post.hid','','strip_tags');
        $data['ad_type'] = I('post.ad_type','','intval');
        if(empty($data['hid'])){
            $this->error('系统提示：请选择酒店!');
        }
        if(empty($data['ad_type'])){
            $this->error('系统提示：请选择是否能中途退出!');
        }
        $data['hour'] = I('post.hour','','intval');
        $data['minute'] = I('post.minute','','intval');
        $data['can_quit'] = I('post.can_quit','','intval');
        if($data['can_quit']==0 && $data['ad_type']==1){
            $data['play_time'] = Null;
        }else{
            $data['play_time'] = I('post.play_time','','intval');
        }

        if($data['ad_type'] ==1){
            $data['ad_position'] = 0;
            $data['ad_word'] = "";
        }elseif($data['ad_type']==2){
            $data['ad_position'] = I('post.video_ad_position','1','intval');
            $data['ad_word'] = "";
        }elseif ($data['ad_type']==3) {
            $data['ad_position'] = I('post.word_ad_position','1','intval');        
            $data['ad_word'] = I('post.ad_word');   
        }
        $data['status'] = 0;
        return $data;
    }

    /**
     * 添加视频资源绑定方法
     * @param string  $videoresourceId [视频资源列表ID集合]
     * @param string $hid             [酒店HID]
     * @param string $adsetId         [设置表ID]
     * @return array $releateResult   [添加结果]
     */
    private function addVideoReleate($videoresourceId="",$hid="",$adsetId=""){
        if($videoresourceId=="" || $hid=="" || $adsetId==""){
            return false;
        }
        $video_resourceId_arr = explode(",", $videoresourceId);
        $i = 1;
        foreach ($video_resourceId_arr as $key => $value) {
            $releateDate[$key]['hid'] = $hid;
            $releateDate[$key]['adset_id'] = $adsetId;
            $releateDate[$key]['adresource_id'] = $value;
            $releateDate[$key]['sort'] = $i;
            $i++;
        }
        $releateResult = D("hotel_adset_adresource")->addAll($releateDate);
        return $releateResult;
    }

    /**
     * 添加图片资源绑定方法
     * @param string $imageId [图片资源ID]
     * @param string $hid     [酒店HID]
     * @param string $adsetId [设置表ID]
     * @return  $releateResult [返回结果]
     */
    private function addImageReleate($imageId="",$hid="",$adsetId=""){
        if($imageId=="" || $hid=="" || $adsetId==""){
            return false;
        }
        $releateDate['hid'] = $hid;
        $releateDate['adset_id'] = $adsetId;
        $releateDate['adresource_id'] = $imageId;
        $releateDate['sort'] = 1;
        $releateResult = D("hotel_adset_adresource")->data($releateDate)->add();
        return $releateResult;
    }

    /**
     * 删除资源绑定方法
     * @param  string $id [设置表ID]
     * @return [type] $delResult [返回结果]
     */
    private function delReleate($id=""){
        if(empty($id)){
            return false;
        }
        $delReleateMap['adset_id'] = $id;
        $delResult = D("hotel_adset_adresource")->where($delReleateMap)->delete();
        return $delResult;
    }

    /**
     * 查询时间是否合法
     * @param  string  $hid      [酒店编号]
     * @param  integer  $time     [转换为分钟数的值]
     * @param  integer $overtime [超过时间单位分钟]
     * @param  integer $id       [修改设置ID]
     * @return [bool]  true/false    [返回结果]
     */
    private function checkTime($hid="",$time=0,$overtime=30,$id){
        if($hid==""){
            return false;
        }
        $map['hid'] = $hid;
        if(!empty($id)){
            $map['id'] = array('neq',$id); 
        }
        $adsetList = D("hotel_adset")->where($map)->field('hour,minute')->order('hour,minute')->select();
        if(empty($adsetList)){
            return true;
        }else{
            foreach ($adsetList as $key => $value) {
                if(abs(intval($time) - (intval($value['hour']*60)+intval($value['minute']))) < $overtime){
                    return false;
                }
            }
            return true;
        }
    }

    /**
     * 二维数组某key值转字符串
     * @param  array  $array 需转换的二维数组
     * @param  string $kk    key值
     * @return string $str   转换为的字符串(逗号分隔)
     */
    public function arrToStr($array=array(),$kk){
        if(!empty($array)){
            foreach ($array as $key => $value) {
                $Arr[] = $value["$kk"];
            }
            $str = implode(",", $Arr);
            return $str;
        }else{
            return false;
        }
    }

    /**
     * [checkpopupad 判断弹窗广告容量是否超标]
     * @param  string $hid        [酒店编号]
     * @param  float $space_size  [酒店弹窗广告设定容量]
     * @return $popupSize/false   [已用容量/超标标志false]
     */
    public function checkpopupad($hid="",$space_size){
        $popupSize = D("hotel_adset_adresource")->where('zxt_hotel_adset_adresource.hid="'.$hid.'"')->join('zxt_hotel_adresource ON zxt_hotel_adset_adresource.adresource_id=zxt_hotel_adresource.id')->field('sum(zxt_hotel_adresource.size)')->find();
        if ($popupSize['sum(zxt_hotel_adresource.size)']>$space_size) {
            return false;
        }else{
            return $popupSize['sum(zxt_hotel_adresource.size)'];
        }
    }

    /**
     * [updatePopupadSize 更新容量表popupad_size]
     * @param  [string] $hid  [酒店编号]
     * @param  [float] $size  [更新容量值]
     * @return $result        [返回结果]
     */
    public function updatePopupadSize($hid,$size){
        if(D("hotel_volume")->where('hid="'.$hid.'"')->count()){
            $result = D("hotel_volume")->where('hid="'.$hid.'"')->setField('popupad_size',$size);
        }else{
            $popupData['popupad_size'] = $size;
            $popupData['hid'] = $hid;
            $result = D("hotel_volume")->data($popupData)->add();
        }
        return $result;
    }

    public function addAllResource($ids,$hid){

        $id_arr = explode(",", $ids);
        $allresourceMap['id'] = array('in',$id_arr);
        $adresourceList = D("hotel_adresource")->where($allresourceMap)->field('filepath,filepath_type')->select();
        if(!empty($adresourceList)){
            foreach ($adresourceList as $key => $value) {
                $allresourceName_arr = explode("/", $value['filepath']);
                $allresourceName = $allresourceName_arr[count($allresourceName_arr)-1];
                $allresourceDate[$key]['hid'] = $hid;
                $allresourceDate[$key]['type'] = $value['type'];
                $allresourceDate[$key]['mold'] = 'adset';
                $allresourceDate[$key]['name'] = $allresourceName;
                $allresourceDate[$key]['timeunix'] = time();
                $allresourceDate[$key]['time'] = date("Y-m-d H:i:s");
                $allresourceDate[$key]['web_upload_file'] = $value['filepath']; 
            }

            $allresourceResult = D("hotel_sd_allresource")->addAll($allresourceDate);
        }
    }
}