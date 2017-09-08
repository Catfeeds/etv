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
            addlog('修改栏目资源，ID：'.$data['id']);

            //修改资源时 对allresource表进行操作 记录所存资源
            // $delAllresourceResult_f = true;
            // $delAllresourceResult_q = true;
            // $addAllresourceResult_f = true;
            // $addAllresourceResult_q = true;
            // if($data['filepath'] != $vo['filepath']){
            //     //删除
            //     if(!empty($vo['filepath'])){
            //         $delName_arr_f = explode("/", $vo['filepath']);
            //         $allresourceMap_f['name'] = $delName_arr_f[count($delName_arr_f)-1];
            //         $delAllresourceResult_f = D("hotel_sd_allresource")->where($allresourceMap_f)->delete();
            //     }
            //     //新增
            //     if(!empty($data['filepath'])){
            //         $allresourceName_arr_f = explode("/", $data['filepath']);
            //          $theallresourceName = $allresourceName_arr_f[count($allresourceName_arr_f)-1];
            //         foreach ($chghotellist as $key => $value) {
            //             $allresourceDate_f[$key]['hid'] = $value;
            //             $allresourceDate_f[$key]['type'] = 1;
            //             $allresourceDate_f[$key]['mold'] = 'carousel';
            //             $allresourceDate_f[$key]['name'] = $theallresourceName;
            //             $allresourceDate_f[$key]['timeunix'] = time();
            //             $allresourceDate_f[$key]['time'] = date("Y-m-d H:i:s");
            //             $allresourceDate_f[$key]['web_upload_file'] = $data['filepath'];
            //         }
            //         $addAllresourceResult_f = D("hotel_sd_allresource")->addAll($allresourceDate_f);
            //     }
            // }
            // if($data['video_image'] != $vo['video_image']){
            //     //删除
            //     if(!empty($vo['video_image'])){
            //         $delName_arr_q = explode("/", $vo['video_image']);
            //         $allresourceMap_q['name'] = $delName_arr_q[count($delName_arr_q)-1];
            //         // $delAllresourceResult_q = $this->allresource_del($allresourceMap_q);
            //         $delAllresourceResult_q = D("hotel_sd_allresource")->where($allresourceMap_q)->delete();

            //     }
            //     //新增
            //     if(!empty($data['video_image'])){
            //         $allresourceName_arr_q = explode("/", $data['video_image']);
            //         $theallresourceName = $allresourceName_arr_q[count($allresourceName_arr_q)-1];
            //         foreach ($chghotellist as $key => $value) {
            //             $allresourceDate_q[$key]['hid'] = $value;
            //             $allresourceDate_q[$key]['type'] = 2;
            //             $allresourceDate_q[$key]['mold'] = 'carousel';
            //             $allresourceDate_q[$key]['name'] = $theallresourceName;
            //             $allresourceDate_q[$key]['timeunix'] = time();
            //             $allresourceDate_q[$key]['time'] = date("Y-m-d H:i:s");
            //             $allresourceDate_q[$key]['web_upload_file'] = $data['video_image'];
            //         }
            //         // $addAllresourceResult_q = D("hotel_allresource")->addAll($allresourceDate_q);
            //         $addAllresourceResult_q = D("hotel_sd_allresource")->addAll($allresourceDate_q);
            //     }
            // }
            // if($delAllresourceResult_f!==false && $delAllresourceResult_q!==false){
            //     $delAllresourceResult = true;
            // }else{
            //     $delAllresourceResult = false;
            // }
            // if($addAllresourceResult_f!==false && $addAllresourceResult_q!==false){
            //     $addAllresourceResult = true;
            // }else{
            //     $addAllresourceResult = false;
            // }

            // if($delAllresourceResult!==false && $addAllresourceResult!==false){
            //     $allresourceResult = true;
            // }else{
            //     $allresourceResult = false;
            // }

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

            // 新增资源的信息保存到allresource表
            // $allresourceResult_f = true;
            // $allresourceResult_q = true;
            // if(!empty($data['filepath'])){
            //     $allresourceName_arr_f = explode("/", $data['filepath']);
            //     $theallresourceName = $allresourceName_arr_f[count($allresourceName_arr_f)-1];
            //     foreach ($chghotellist as $key => $value) {
            //         $allresourceDate_f[$key]['hid'] = $value;
            //         $allresourceDate_f[$key]['type'] = 1;
            //         $allresourceDate_f[$key]['mold'] = 'carousel';
            //         $allresourceDate_f[$key]['name'] = $theallresourceName;
            //         $allresourceDate_f[$key]['timeunix'] = time();
            //         $allresourceDate_f[$key]['time'] = date("Y-m-d H:i:s");
            //         $allresourceDate_f[$key]['web_upload_file'] = $data['filepath'];
            //     }
            //     // $allresourceResult_f = D("hotel_allresource")->addAll($allresourceDate_f);
            //     $allresourceResult_f = D("hotel_sd_allresource")->addAll($allresourceDate_f);
            // }
            // if(!empty($data['video_image'])){
            //     $allresourceName_arr_q = explode("/", $data['video_image']);
            //     $theallresourceName = $allresourceName_arr_q[count($allresourceName_arr_q)-1];
            //     foreach ($chghotellist as $key => $value) {
            //         $allresourceDate_q[$key]['hid'] = $value;
            //         $allresourceDate_q[$key]['type'] = 2;
            //         $allresourceDate_q[$key]['mold'] = 'carousel';
            //         $allresourceDate_q[$key]['name'] = $theallresourceName;
            //         $allresourceDate_q[$key]['timeunix'] = time();
            //         $allresourceDate_q[$key]['time'] = date("Y-m-d H:i:s");
            //         $allresourceDate_q[$key]['web_upload_file'] = $data['video_image'];
            //     }
            //     // $allresourceResult_q = D("hotel_allresource")->addAll($allresourceDate_q);
            //     $allresourceResult_q = D("hotel_sd_allresource")->addAll($allresourceDate_q);
            // }
            // if($allresourceResult_f && $allresourceResult_q){
            //     $allresourceResult == true;
            // }else{
            //     $allresourceResult == false;
            // }
            
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
           D("hotel_carousel_resource")->commit();

            //allresource资源写入xml文件
            // foreach ($chghotellist as $key => $value) {
            //     $xmlFilepath = FILE_UPLOAD_ROOTPATH.'/upload/sdresourceXml/'.$value.'.txt';
            //     $xmlResult = $this->fileputXml(D("hotel_allresource"),$value,$xmlFilepath);
            // }

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
            $upload->savePath='./upload/carousel/'; // 设置附件上传目录
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
        }else{
            $callback['status'] = 0;
            $callback['info']='缺少文件';
        }
        echo json_encode($callback);
    }

    public function upload_icon(){
        $callback = array();
        if (!empty($_FILES[$_REQUEST["name"]]["name"])) {
            $upload = new \Think\Upload();
            $upload->maxSize=2097152;  //设置大小为2M
            $upload->exts=array('jpg','png','jpeg');
            $upload->rootPath='./Public/';
            $upload->savePath='./upload/carousel/';
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

    public function resource_unlock(){
    	$ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        $gocontroller = I('get.gocontroller');
        if($ids){
            $ids = implode(',',$ids);
            $map['id']  = array('in',$ids);
            $map['ctype'] = I('get.ctype');
            $result = D('hotel_carousel_resource')->where($map)->setField("status",1);
            if($result !== false){
                addlog('启用hotel_carousel_resource表数据，数据ID：'.$ids);
                $this->success('恭喜，启用成功！',U($gocontroller.'/resource').'?ids='.I('get.category_id'));
            }else{
                $this->error('启用失败，参数错误！',U($gocontroller.'/resource').'?ids='.I('get.category_id'));
            }
        }else{
            $this->error('参数错误！',U($gocontroller.'/resource').'?ids='.I('get.category_id'));
        }
    }

    public function resource_lock(){
    	$ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        $gocontroller = I('get.gocontroller');

        if($ids){
            $ids = implode(',',$ids);
            $map['id']  = array('in',$ids);
            $map['ctype'] = I('get.ctype');
            $result = D('hotel_carousel_resource')->where($map)->setField("status",0);
            if($result !== false){
                addlog('启用hotel_carousel_resource表数据，数据ID：'.$ids);
                $this->success('恭喜，禁用成功！',U($gocontroller.'/resource').'?ids='.I('get.category_id'));
            }else{
                $this->error('禁用失败，参数错误！',U($gocontroller.'/resource').'?ids='.I('get.category_id'));
            }
        }else{
            $this->error('参数错误！',U($gocontroller.'/resource').'?ids='.I('get.category_id'));
        }
    }

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

        //需要删除的资源名称
        // if(!empty($vo['filepath'])){
        //     $getName_arr = explode("/", $vo['filepath']);
        //     $getName[] = $getName_arr[count($getName_arr)-1];
        // }
        // if(!empty($vo['video_image'])){
        //     $getName_arr = explode("/", $vo['video_image']);
        //     $getName[] = $getName_arr[count($getName_arr)-1];
        // }

        //删除资源表
        // $delAllresourceResult = true;
        // if(!empty($getName)){
        //     $delAllresourceMap['name'] = array('in',$getName);
        //     $delAllresourceResult = D("hotel_sd_allresource")->where($delAllresourceMap)->delete();
        // }

        if($model->where($map)->delete() && $vresult!==false){
    
            addlog('删除资源表数据，数据ID：'.$ids['0']);

            $model->commit();
            //删除对应文件
            @unlink(FILE_UPLOAD_ROOTPATH.$vo['filepath']);
            @unlink(FILE_UPLOAD_ROOTPATH.$vo['video_image']);

            //allresource资源写入xml文件
            // foreach ($chghotellist as $key => $value) {
            //     $xmlFilepath = FILE_UPLOAD_ROOTPATH.'/upload/sdresourceXml/'.$value.'.txt';
            //     $xmlResult = $this->fileputXml(D("hotel_allresource"),$value,$xmlFilepath);
            // }

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

        $model = D("hotel_carousel_resource");
        $model->startTrans();
        $map['ctype'] = I('post.ctype');
        $j = 1;
        for ($i=0; $i <count($menuid) ; $i++) { 
            $map['id'] = intval($menuid[$i]);
            $data['sort'] = $remarks[$i];
            $result = $model->where($map)->data($data)->save();
            if($result === false){
                --$j;
            }
        }
        if($j>0){
            $model->commit();
            echo true;
        }else{
            $model->rollback();
            echo false;
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

    //查询绑定的子栏目
    private function getchgcategory(){

    }
}