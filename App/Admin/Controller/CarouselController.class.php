<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 轮播视频管理控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class CarouselController extends ComController {

	public function resource_add(){
		$ctype=I('get.ctype');
        $category_id=I('get.category_id');
		$incontroller=I('get.incontroller');
        $vo= D($incontroller)->getById($category_id);
        $this->assign("current_category",$vo);
        $this->assign("ctype",$ctype);
        $this->assign("category_id",$category_id);
        if($incontroller == "hotel_category"){
            $this->assign("incontroller","HotelCategory");
        }elseif($incontroller == "hotel_chg_category"){
            $this->assign("incontroller","Chg");
        }
        $this->display();
	}

	public function resource_edit(){
		$ids=I('post.ids');
		if(count($ids)!=1){
			$this->error('系统提示：参数错误',U('HotelCategory/resource').'?ids='.I('get.category_id'));
		}
		$fo = D("hotel_carousel_resource")->getById($ids[0]);
        if($fo['ctype'] == "videochg"){
            $chg_category = D("hotel_chg_category")->getById($fo['cid']);
            $this->assign('incontroller',"Chg");
            $this->assign('pid',$chg_category['pid']);
        }else{
            $this->assign('incontroller',"HotelCategory");
        }
		$filepath = $fo['filepath'];
        $video_image = $fo['video_image'];
        if(!empty($filepath)){
            $fo['size1'] = round(filesize(FILE_UPLOAD_ROOTPATH.$filepath)/1024);
        }else{
            $fo['size1'] = 0;
        }
        if(!empty($video_image)){
            $fo['size2'] = round(filesize(FILE_UPLOAD_ROOTPATH.$video_image)/1024);
        }else{
            $fo['size2'] = 0;
        }
        
        $this->assign('vo',$fo);
        $this -> display();
	}

	public function resource_update(){
        $data['id'] = I('post.id','','intval');
        $data['hid'] = I('post.hid','','strip_tags');
        $data['title'] = isset($_POST['title'])?$_POST['title']:false;
        $data['intro'] = I('post.intro','','strip_tags');
        $data['sort'] = I('post.sort','','intval');
        $data['filepath'] = I('post.filepath','','strip_tags');
        $data['video_image'] = I('post.vimage','','strip_tags');
        $data['price'] = I('post.price','','strip_tags');
        $data['status'] = 0;
        $data['upload_time'] = date("Y-m-d H:i:s");
        $codevalue = I('post.codevalue','','intval');
        $data['audit_status'] = 0;
        $size1 = I('post.size1','','intval');
        $size2 = I('post.size2','','intval');
        $size = $size1 + $size2;
        $data['size'] = round($size/1024,3);
        $incontroller = I('post.incontroller');
        $cid = I('post.category_id');

        $hmap['hid'] = I('post.hid');
        if(!$data['title']){
            $this->error('警告！资源标题必须填写！',U($incontroller.'/resource').'?ids='.$cid);
        }
        if (empty($data['filepath'])) {
            $this->error('请上传资源文件！',U($incontroller.'/resource').'?ids='.$cid);
        }

        D("hotel_carousel_resource")->startTrans();

        $vo = '';
		if($data['id']){  //修改

			$vo = D("hotel_carousel_resource")->getById($data['id']);

			//查询容量
            $changesize = $data['size'] - $vo['size'];
            $volumeResult = $this->checkCarouselVolumn($data['hid'],$changesize);//检查容量是否超标
            if($volumeResult === false){
                if($vo['video_image'] != $data['video_image']){
                    @unlink(FILE_UPLOAD_ROOTPATH.$data['video_image']);
                    
                }
                if($vo['filepath'] != $data['filepath']){
                    @unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
                    
                }
                $this->error("超过申请容量值，无法修改资源",U($incontroller.'/resource').'?ids='.$cid);
            }
            //集团通用模块 子酒店查询
            if($incontroller == "Chg"){
                $pid = I('post.pid');
                $chghotellist = $this->getChghotel($data['hid'],$pid);
                if(!empty($chghotellist)){
                    $cvolumeResult = $this->checkChgCarouselVolumn($chghotellist,$changesize,$data['hid']);
                    if($cvolumeResult === false){
                        if($vo['video_image'] != $data['video_image']){
                            @unlink(FILE_UPLOAD_ROOTPATH.$data['video_image']);
                            
                        }
                        if($vo['filepath'] != $data['filepath']){
                            @unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
                            
                        }
                        $this->error("超过申请容量值，无法新增资源",U($incontroller.'/resource').'?ids='.$cid);
                    }
                }
            }

            $chghotellist[] = $data['hid'];
            $result = D("hotel_carousel_resource")->data($data)->where('id='.$data['id'])->save();

        }else{  //新增
                
            //查询容量
            $volumeResult = $this->checkCarouselVolumn($data['hid'],$data['size']);
            if($volumeResult === false){
                @unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
                @unlink(FILE_UPLOAD_ROOTPATH.$data['video_image']);
                $this->error("超过申请容量值，无法新增资源",U($incontroller.'/resource').'?ids='.$cid);
            }

            if($incontroller == "Chg"){  //需要查询子酒店是否超过申请容量
                $pid = I('post.pid');
                $chghotellist = $this->getChghotel($data['hid'],$pid);
                if(!empty($chghotellist)){
                    $cvolumeResult = $this->checkChgCarouselVolumn($chghotellist,$data['size'],$data['hid']);
                    if($cvolumeResult === false){
                        @unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
                        @unlink(FILE_UPLOAD_ROOTPATH.$data['video_image']);
                        $this->error("超过申请容量值，无法新增资源",U($incontroller.'/resource').'?ids='.$cid);
                    }
                }
            }

            $chghotellist[] = $data['hid'];
            $data['file_type'] = I('post.type','','intval');
            $data['ctype'] = I('post.ctype');
            $data['cid'] = $cid;		
            $result = D("hotel_carousel_resource")->data($data)->add();
        }

        //更新容量表
        $updatesize = true;
        $chgupdatesize = true;
        $sizelist = D("hotel_carousel_resource")->where($hmap)->field('sum(size)')->select();
        if(M("hotel_volume")->where($hmap)->count()){
            $hotelupdatesize = D("hotel_volume")->where($hmap)->setField("carousel_size",$sizelist[0]['sum(size)']);
        }else{
            $arrdata['hid'] = $data['hid'];
            $arrdata['carousel_size'] = $sizelist[0]['sum(size)'];
            $hotelupdatesize = M("hotel_volume")->data($arrdata)->add();
        }
        if(!empty($cvolumeResult)){  //更新子酒店
            foreach ($cvolumeResult as $key => $value) {
                $chgsetsize = $data['size'] + $value;
                $sql = "INSERT INTO `zxt_hotel_volume`(`hid`)values('".$key."')  ON DUPLICATE KEY UPDATE `carousel_size`='".$value."'";
                $chgupdatesize = D("hotel_volume")->execute($sql);
                if($chgupdatesize === false){
                    break;
                }
            }
        }
        if($hotelupdatesize===false || $chgupdatesize===false){
            $updatesize = false;
        }

        if($result !== false && $updatesize !== false){
            //更新酒店内容云宣教资源
            $jsonmap = $hmap;
            if($incontroller == "Chg"){
                $jsonmap['ctype'] = 'videochg';
            }else{
                $jsonmap['ctype'] = 'videohotel';
            }
            $this->updatejson_hotelcarousel($jsonmap);

            D("hotel_carousel_resource")->commit();
            if(!empty($vo)){
                if($data['filepath'] != $vo['filepath']){
                    @unlink(FILE_UPLOAD_ROOTPATH.$vo['filepath']);
                }
                if ($data['video_image'] != $vo['video_image']) {
                    @unlink(FILE_UPLOAD_ROOTPATH.$vo['video_image']);
                }
            }
            $this->success('恭喜，操作成功！',U($incontroller.'/resource').'?ids='.$cid);
        }else{
            D("hotel_carousel_resource")->rollback();
            $this->error('操作失败',U($incontroller.'/resource').'?ids='.$cid);
        }
	}

	public function upload(){
        $callback = array();
        if (!empty($_FILES[$_REQUEST["name"]]["name"])) {
            if(empty($_REQUEST['hid'])){
                $callback['status'] = 0;
                $callback['info'] = "请选择酒店";
            }else{
                $upload = new \Think\Upload(); //实例化上传类
                if ($_REQUEST['filetype']==1) {
                    $upload->exts=array('mp4');
                    $upload->maxSize=209715200;// 设置附件上传大小200M
                }else{
                    $callback['status'] = 0;
                    $callback['info']='未知错误！';
                    echo json_encode($callback);
                    exit;
                }
                $upload->rootPath='./Public/'; //保存根路径
                $upload->savePath='./upload/content/'.$_REQUEST['hid'].'/';
                $upload->autoSub = false;
                $upload->saveName = time().'_'.mt_rand();
                //单文件上传
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
            }
        }else{
            $callback['status'] = 0;
            $callback['info']='缺少文件';
        }
        echo json_encode($callback);
    }

    public function upload_icon(){
        $callback = array();
        if (!empty($_FILES[$_REQUEST["name"]]["name"])) {
             if(empty($_REQUEST['hid'])){
                $callback['status'] = 0;
                $callback['info'] = "请选择酒店";
            }else{
                $upload = new \Think\Upload();
                $upload->maxSize=2097152;  //设置大小为2M
                $upload->exts=array('jpg','png','jpeg');
                $upload->rootPath='./Public/';
                $upload->savePath='./upload/content/'.$_REQUEST['hid'].'/';
                $upload->autoSub = false;
                $upload->saveName = time().'_'.mt_rand();
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
            }
        }else{
            $callback['status'] = 0;
            $callback['info']='缺少文件';
        }
        echo json_encode($callback);
    }

    /**
     * [视频轮播资源启用]
     */
    public function resource_unlock(){
    	$ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        $gocontroller = I('get.gocontroller');
        if($ids){
            $ids_str = implode(',',$ids);
            $map['id']  = array('in',$ids_str);
            $map['ctype'] = I('get.ctype');
            $result = D('hotel_carousel_resource')->where($map)->setField("status",1);
            if($result !== false){
                $jsonmap = D("hotel_carousel_resource")->where('id='.$ids['0'])->field('hid,ctype')->find();
                $this->updatejson_hotelcarousel($jsonmap);
                $this->success('恭喜，启用成功！',U($gocontroller.'/resource').'?ids='.I('get.category_id'));
            }else{
                $this->error('启用失败，参数错误！',U($gocontroller.'/resource').'?ids='.I('get.category_id'));
            }
        }else{
            $this->error('参数错误！',U($gocontroller.'/resource').'?ids='.I('get.category_id'));
        }
    }

    /**
     * [视频轮播资源禁用]
     */
    public function resource_lock(){
    	$ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        $gocontroller = I('get.gocontroller');
        if($ids){
            $ids_str = implode(',',$ids);
            $map['id']  = array('in',$ids_str);
            $map['ctype'] = I('get.ctype');
            $result = D('hotel_carousel_resource')->where($map)->setField("status",0);
            if($result !== false){
                $jsonmap = D("hotel_carousel_resource")->where('id='.$ids['0'])->field('hid,ctype')->find();
                $this->updatejson_hotelcarousel($jsonmap);
                $this->success('恭喜，禁用成功！',U($gocontroller.'/resource').'?ids='.I('get.category_id'));
            }else{
                $this->error('禁用失败，参数错误！',U($gocontroller.'/resource').'?ids='.I('get.category_id'));
            }
        }else{
            $this->error('参数错误！',U($gocontroller.'/resource').'?ids='.I('get.category_id'));
        }
    }

    /**
     * [删除视频轮播资源]
     */
    public function resource_delete(){
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        $gocontroller = I('get.gocontroller');

        if(count($ids)!=1){
            $this->error('参数错误',U($gocontroller.'/resource').'ids='.I('get.category_id'));
        }
        $map['id'] = $ids['0'];
        $map['ctype'] = I('get.ctype');
        $model = M("hotel_carousel_resource");
        $vo = $model->where($map)->find();
        $model->startTrans();
        $getName = array();

        //更改volume表
        if($vo['ctype'] == "videochg"){  //集团通用 查看是否有子酒店绑定
            $chgCategoryMap['id'] = $vo['cid'];
            $chgCategoryvo = D("hotel_chg_category")->where($chgCategoryMap)->field('pid')->find();
            $chghotellist = $this->getChghotel($vo['hid'],$chgCategoryvo['pid']);
        }
        $chghotellist[] = $vo['hid'];
        $decVmap['hid'] = array('in',$chghotellist); 
        $vresult = $this->csetDec($decVmap,'carousel_size',$vo['size']);

        if($model->where($map)->delete() && $vresult!==false){
    
            addlog('删除资源表数据，数据ID：'.$ids['0']);
            $model->commit();

            //更新视频轮播json文件
            $jsonmap['hid'] = $vo['hid'];
            $jsonmap['ctype'] = $map['ctype'];
            $this->updatejson_hotelcarousel($jsonmap);

            //删除对应文件
            @unlink(FILE_UPLOAD_ROOTPATH.$vo['filepath']);
            @unlink(FILE_UPLOAD_ROOTPATH.$vo['video_image']);

            $this->success('恭喜，删除成功！',U($gocontroller.'/resource').'?ids='.I('get.category_id'));
        }else{
            $model->rollback();
            $this->error('删除失败，参数错误！',U($gocontroller.'/resource').'?ids='.I('get.category_id'));
        }
    }

    public function resource_sort(){
    	$menuid_str = I('post.menuid','','strip_tags');
        $remarks_str = I('post.remarks','','strip_tags');
        $menuid = explode(",", $menuid_str);
        $remarks = explode(",", $remarks_str);
        $count = count($menuid);
        $sql = "UPDATE zxt_hotel_carousel_resource SET sort = CASE id";
        for ($i=0; $i <$count ; $i++) { 
            $sql .= sprintf(" WHEN %d THEN '%s'",$menuid[$i],$remarks[$i]);
        }
        $sql .= "END WHERE id IN($menuid_str)";
        $result = D("hotel_carousel_resource")->execute($sql);

        if ($result === false) {
            echo false;
        }else{
            $vo = D("hotel_carousel_resource")->where('id='.$menuid['0'])->field('hid,ctype')->find();
            $this->updatejson_hotelcarousel($vo);
            echo true;
        }
    }

    //检查sd卡容量
    private function checkCarouselVolumn($hid,$size){
        if(empty($hid)){
            return false;
        }
        $carousel_space_vo = D("hotel")->where('hid="'.$hid.'"')->field('carousel_space')->find();
        $carousel_space = empty($carousel_space_vo['carousel_space'])?19456:$carousel_space_vo['carousel_space'];
        $carousel_resource = D("hotel_carousel_resource")->where('hid="'.$hid.'"')->field('sum(size)')->select();
        if(($size+$carousel_resource[0]['sum(size)'])>$carousel_space){
            return false;
        }else{
            return true;
        }
    }   

    //检查sd卡子酒店容量
    private function checkChgCarouselVolumn($hid_arr,$size,$phid){
        $chgCarouselMap['hid'] = array('in',$hid_arr);
        $carousel_space_list = D("hotel")->where($chgCarouselMap)->field('hid,carousel_space')->select();//子酒店规定容量                                                                                             
        foreach ($carousel_space_list as $key => $value) {
            $carousel_space_hotel[$value['hid']] = $value['carousel_space'];//对应酒店编号的sd卡容量
        }

        $modeldefinelist = D("modeldefine")->field('id')->where('codevalue=501')->select();
        foreach ($modeldefinelist as $key => $value) {
            $modeldefine[] = $value['id'];
        }
        //查询模型code码为501 栏目已绑定的容量累加的值
        $chgcidMap['zxt_hotel_chg_category.modeldefineid'] = array('in',$modeldefine);
        $chgcidMap['zxt_hotel_chglist.phid'] = $phid;
        $chgcidMap['zxt_hotel_carousel_resource.hid'] = $phid;
        $chgcidMap['zxt_hotel_carousel_resource.ctype'] = "videochg";
        $field = "zxt_hotel_chg_category.sum('size')";
        foreach($hid_arr as $key => $value) {
            $chgcidMap['zxt_hotel_chglist.hid'] = $value;
            $chgVolume = D("hotel_chg_category")->where($chgcidMap)->join('zxt_hotel_chglist ON zxt_hotel_chg_category.pid=zxt_hotel_chglist.chg_cid')->join('zxt_hotel_carousel_resource ON zxt_hotel_chg_category.id=zxt_hotel_carousel_resource.cid')->field('sum(zxt_hotel_carousel_resource.size)')->find();

            if(($size+$chgVolume['sum(zxt_hotel_carousel_resource.size)']) > $carousel_space_hotel[$value]){
                return false;
            }
            $returnData[$value] = $size+$chgVolume['sum(zxt_hotel_carousel_resource.size)'];
        }
        return $returnData;
    }

    //获取绑定子酒店列表
    private function getChghotel($hid,$cid){
        if(empty($hid) || empty($cid)){
            return false;
        }
        $chghotelMap['phid'] = $hid;
        $chghotelMap['chg_cid'] = $cid;
        $chghotel = D("hotel_chglist")->where($chghotelMap)->field('hid')->select();
        if(!empty($chghotel)){
            foreach ($chghotel as $key => $value) {
                $chghotellist[] = $value['hid'];
            }
            return $chghotellist;
        }else{
            return array();
        }
    }

    /**
     * [更新酒店视频轮播json数据]
     * @param  [array] $hmap [hid数组]
     */
    private function updatejson_hotelcarousel($jsonmap){
        $jsonmap['audit_status'] = 4;
        $jsonmap['status'] = 1;
        $field = "hid,cid,title,intro,sort,filepath,video_image";
        $list = D("hotel_carousel_resource")->where($jsonmap)->field($field)->order('sort')->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $plist[$value['cid']][] = $value;
            }
            $jsondata = json_encode($plist);
        }else{
            $jsondata = '';
        }
        if(!is_dir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$jsonmap['hid'])){
            mkdir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$jsonmap['hid']);
        }
        $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$jsonmap['hid'].'/'.$jsonmap['ctype'].'.json';
        file_put_contents($filename, $jsondata);
    }

}