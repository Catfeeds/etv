<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 酒店栏目管理控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Vendor\Tree;
class HotelCategoryController extends ComController {

    /**
     * [map查询条件]
     * @return [array] $array [查询条件]
     */
    public function _array(){
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
            if (!empty($data['content_hid'])) { //其他模块已查hid
                $map['hid'] = $data['content_hid'];
                $vo = M('hotel')->getByHid($data['content_hid']);
                $hotelid = $vo['id'];
            }
        }
        $array['map'] = $map;
        $array['hotelid'] = $hotelid;
        $this->assign('hid',$array['map']['hid']);

        return $array;
    }

    /**
     * [新建酒店树状结构图]
     * @param  [int] $hotelid [酒店列表ID]
     */
    private function newTree($hotelid){
        $category = M('hotel')->field('id,pid,hotelname,hid')->order('hid asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$hid \$selected>\$spacer\$hotelname</option>"; //生成的形式
        $category = $tree->get_tree(0,$str,$hotelid);
        $this->assign('pHotel',$category);
    }

    /**
     * [栏目管理主页]
     */
    public function index(){
        $model = M(CONTROLLER_NAME);
        $array = $this->_array();
        $hid_condition = $this->isHotelMenber();
        // 查询账号关联酒店  并且没有其他模块关联查询  没有主页
        if($hid_condition && empty($array['hotelid'])){
            $map['hid'] = $hid_condition['1']['0'];
        }else{
            if(empty($array['hotelid'])){
                $vv = M('hotel_category')->field('hid')->find();
                $vo = M('hotel')->getByHid($vv['hid']);
                $map['hid'] = $vo['hid'];
                $hotelid = $vo['id'];
            }else{
                $map['hid'] = $array['map']['hid'];
            }
            $this->newTree($array['hotelid']);
        }

        $list = $model ->where($map)->order("sort asc")->select();
        //生成树状图
        $volist = $this->list_to_tree($list, 'id', 'pid', '_child', 0);
        $this -> assign("list",$volist);
        $this -> display();
    }

    /**
     * [栏目管理添加页]
     */
    public function add(){
        $hotelid = 0;

        //判断session 是否hid有查询
        $data = session(session_id());
        if(!empty($data['content_hid'])){
            $vo=M('hotel')->where('hid="'.$data['content_hid'].'"')->field('id')->find();
            //初始化栏目上级
            $Modeldefine = D("Modeldefine");
            $modelList=$Modeldefine->where('codevalue="100" or codevalue="101"')->field('id')->select();
            foreach ($modelList as $key => $value) {
                $arr[$key]=$value['id'];
            }
            $map['hid'] = $data['content_hid'];
            $map['pid'] = 0;
            $map['modeldefineid']=array("in",$arr);
            $catlist = M(CONTROLLER_NAME)->where($map)->order("sort asc")->field('id,name')->select();
            $this->assign('pcat',$catlist);
            $this->assign('hid',$data['content_hid']);
        }

        //语言管理
        $Langcode = D("Langcode");
        $list = $Langcode->field('id,name')->where("status=1")->order("id asc")->select();
        $this->assign("langlist",$list);

        //栏目模型
        $Modeldefine = D("Modeldefine");
        $modeldefinelist = $Modeldefine->field('id,name')->where("status=1")->order("codevalue asc")->select();
        $this->assign("modeldefinelist",$modeldefinelist);

        $hid_condition = $this->isHotelMenber();
        if($hid_condition === false){
            $this->newTree($hotelid);
        }
            
        $this->display();
    }

    /**
     * [栏目管理修改页]
     */
    public function edit(){
        $ids = I('post.ids');
        if(count($ids)!=1){
            $this->error('参数错误，每次只能修改一条内容！');
        }
        $model = M(CONTROLLER_NAME);
        $var = $model->getById($ids[0]);
        $field = "zxt_hotel_category.id,zxt_hotel_category.hid,zxt_hotel_category.langcodeid,zxt_hotel_category.modeldefineid,zxt_hotel_category.pid,zxt_hotel_category.name,zxt_hotel_category.icon,zxt_hotel_category.size,zxt_hotel.hotelname,zxt_modeldefine.name as modeldefinename,zxt_langcode.name as langcodename";
        $vo = D("hotel_category")->where('zxt_hotel_category.id='.$ids['0'])->join('zxt_hotel ON zxt_hotel_category.hid=zxt_hotel.hid','left')->join('zxt_modeldefine ON zxt_hotel_category.modeldefineid=zxt_modeldefine.id')->join('zxt_langcode on zxt_hotel_category.langcodeid=zxt_langcode.id')->field($field)->find();

        //判断栏目是否可选上级栏目
        $currentMenuType = D("Modeldefine")->where('id='.$vo['modeldefineid'])->field('codevalue')->find();
        if ($currentMenuType['codevalue']=="100" || $currentMenuType['codevalue']=="101") {
            $catlist=array();
        }else{
            $modelList = D("Modeldefine")->where('codevalue="100" or codevalue="101"')->field('id')->select();
            foreach ($modelList as $key => $value) {
                $arr[$key]=$value['id'];
            }
            $map['hid']=$var['hid'];
            $map['pid']=0;
            $map['langcodeid']=$var['langcodeid'];
            $map['modeldefineid']=array("in",$arr);
            $catlist = D("hotel_category")->field('id,name')->where($map)->order("sort asc")->select();
        }
        $this->assign("pcat",$catlist);
        
        $this->assign('vo',$vo);
        $this -> display();
    }

    /**
     * [获取酒店栏目的集团和上级菜单]
     * [ajax动态获取]
     * @return [json] $catlist [集团和上级菜单列表json集合]
     */
    public function get_catgory(){
        $Modeldefine = D("Modeldefine");
        $modelList = $Modeldefine->where('codevalue="100" or codevalue="101"')->field('id')->select();
        foreach ($modelList as $key => $value) {
            $arr[$key]=$value['id'];
        }
        $hid = I('get.hid','','strtoupper');
        $map['hid'] = $hid;
        $map['pid'] = 0;
        $map['modeldefineid']= array("in",$arr);
        $catlist = M(CONTROLLER_NAME)->where($map)->order("sort asc")->field('id,name')->select();
        echo json_encode($catlist);
    }

    /**
     * [更新栏目]
     */
    public function update(){

        $data = $this->categoryInputProcess();

        $model=M(CONTROLLER_NAME);
        $model->startTrans();
        if($data['id']){//修改
            
            $vo = $model->where('id='.$data['id'])->field('size,icon')->find();

            // 查询容量
            $changesize = $data['size'] - $vo['size'];
            $volumeResult = $this->checkVolume($data['hid'],$changesize);//检查容量是否超标
            if($volumeResult === false){
                @unlink(FILE_UPLOAD_ROOTPATH.$data['icon']);
                $this->error("超过申请容量值，无法修改资源");
            }
            // 更新数据
            $result = $model->data($data)->where('id='.$data['id'])->save();
            if($result === false){
                $model->rollback();
                $this->error('新增数据失败');
            }

        }else{

            // 查询容量
            $volumeResult = $this->checkVolume($data['hid'],$data['size']);//检查容量是否超标
            if($volumeResult === false){
                @unlink(FILE_UPLOAD_ROOTPATH.$data['icon']);
                $this->error("超过申请容量值，无法新增资源");
            }
            // 新增插入数据
            $result = $model->data($data)->add();
            if($result === false){
                $model->rollback();
                $this->error('新增数据失败');
            }
        }

        // 更新容量表
        $hmap['hid'] = $data['hid'];
        $this->updatevolume($hmap);

        // 更新资源json文件
        if($data['pid'] == 0){
            $this->updatejson_one($data['hid']);
        }elseif($data['pid'] >0){
            $this->updatejson_two($data['hid']);
        }

        $model->commit();
        if($data['id']){
            if ($data['icon'] != $vo['icon']) {
                @unlink(FILE_UPLOAD_ROOTPATH.$vo['icon']);
            }
        }
        $this->success('恭喜，操作成功！',U('index'));
    }

    /**
     * [栏目保存数据校验处理]
     * @return [array] $data [保存数据]
     */
    private function categoryInputProcess(){

        $data['id'] = I('post.id','','intval');
        $data['hid'] = I('post.hid','','strip_tags');
        $data['name'] = isset($_POST['name'])?$_POST['name']:false;
        $data['pid'] = I('post.pid','','intval');
        $data['langcodeid'] = I('post.langcodeid','','intval');
        $data['modeldefineid'] = I('post.modeldefineid','','intval');
        $data['intro'] = I('post.intro','','strip_tags');
        $sort = I('post.sort','','intval');
        $data['icon'] = I('post.icon','','strip_tags');
        $size = I('post.size','','intval');
        $data['size'] = round($size/1024,3);
        $data['status'] = 0;

        if(empty($data['hid'])){
            @unlink(FILE_UPLOAD_ROOTPATH.$data['icon']);
            $this->error('警告，请选择所属酒店！',U('index'));
        }
        if(!$data['name'] or !$data['langcodeid'] or !$data['modeldefineid'] ){
            @unlink(FILE_UPLOAD_ROOTPATH.$data['icon']);
            $this->error('警告！栏目信息未填完整，请补充完整！',U('index'));
        }
        if ($data['pid']>0) {
            $foo = D("hotel_category")->getById($data['pid']);
            if ($foo['langcodeid']!=$data['langcodeid']) {
                @unlink(FILE_UPLOAD_ROOTPATH.$data['icon']);
                $this->error('警告！当前菜单要与上级菜单的语言一致！',U('index'));
            }
        }
        if ($data['pid']==$data['id'] && !empty($data['pid'])) {
            @unlink(FILE_UPLOAD_ROOTPATH.$data['icon']);
            $this->error('警告！栏目上级不能选本身！',U('index'));
        }
        if (!is_null($sort)) {
            $data['sort'] = $sort;
        }
        return $data;
    }

    /**
     * [上传图标]
     */
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
     * [动态删除图标]
     * @return [type] [description]
     */
    public function delfilepath(){
        if($_POST['filepath']){
            @unlink(FILE_UPLOAD_ROOTPATH.$_POST['filepath']);
        }
        echo true;
    }

    /**
     * [启用栏目]
     */
    public function unlock(){
        if(count($_POST['ids']) <1){
            $this->error('系统提示：参数错误');
        }
        $map['id'] = array('in',$_POST['ids']);
        $result = D("hotel_category")->where($map)->setField('status',1);
        if($result === false){
            $this->error('系统提示：修改失败');
        }else{
            $vo = D("hotel_category")->where($map)->field('hid')->find();
            $this->updatejson_one($vo['hid']);
            $this->updatejson_two($vo['hid']);
            $this->success('系统提示：启用成功');
        }
    }

    /**
     * [禁用栏目]
     */
    public function lock(){
        if(count($_POST['ids']) <1){
            $this->error('系统提示：参数错误');
        }
        $map['id'] = array('in',$_POST['ids']);
        $result = D("hotel_category")->where($map)->setField('status',0);
        if($result === false){
            $this->error('系统提示：修改失败');
        }else{
            $vo = D("hotel_category")->where($map)->field('hid')->find();
            $this->updatejson_one($vo['hid']);
            $this->updatejson_two($vo['hid']);
            $this->success('系统提示：禁用成功');
        } 
    }

    /**
     * [一级二级栏目删除]
     */
    public function delete(){

        $ids = I('post.ids');
        if(empty(intval($ids['0']))){
            $this->error('参数错误！');  
        }

        //查询删除数据
        $map['zxt_hotel_category.id'] = $ids['0'];
        $field = "zxt_hotel_category.id,zxt_hotel_category.hid,zxt_hotel_category.icon,zxt_hotel_category.pid,zxt_hotel_category.size,zxt_modeldefine.codevalue";
        $vo = D("hotel_category")->where($map)->field($field)->join('zxt_modeldefine ON zxt_hotel_category.modeldefineid=zxt_modeldefine.id')->find();

        //检验是否可以删除
        if($vo['pid'] == 0){
            $Category = D("hotel_category")->where('pid="'.$vo['id'].'"')->field('id')->find();
            if(!empty($Category)){
                $this->error('该栏目下含有二级栏目，请先删除该栏目下的二级栏目');
                die();
            }
        }else{
            if($vo['codevalue'] == 501){
                $carouselMap['hid'] = $vo['hid'];
                $carouselMap['cid'] = $vo['id'];
                $Resource = D("hotel_carousel_resource")->where($carouselMap)->field('id')->find();
            }else{
                $hotelMap['hid'] = $vo['hid'];
                $hotelMap['category_id'] = $vo['id'];
                $Resource = D("hotel_resource")->where($hotelMap)->field('id')->find();
            }
            if(!empty($Resource)){
                $this->error('该栏目下含有资源，请先删除该栏目下资源再删除栏目');
                die();
            }
        }

        D("hotel_category")->startTrans();
        
        //减少容量
        $vmap['hid']=$vo['hid'];
        $vresult = $this->csetDec($vmap,'content_size',$vo['size']);
        if($vresult === false){
            D("hotel_category")->rollback();
            $this->error('减少容量操作失败');
        }

        //删除栏目
        $delCategoryResult = D("hotel_category")->where($map)->delete();
        if($delCategoryResult === false){
            D("hotel_category")->rollback();
            $this->error('删除栏目失败');
        }

        //更新json
        if($vo['pid'] == 0){
            $this->updatejson_one($vo['hid']);
        }elseif($vo['pid'] >0){
            $this->updatejson_two($vo['hid']);
        }

        @unlink(FILE_UPLOAD_ROOTPATH.$vo['icon']);
        D("hotel_category")->commit();
        $this->success('恭喜，删除成功！');
    }

    /**
     * [更新栏目排序]
     */
    public function sort() {
        $menuids = explode(",", $_REQUEST['menuid']);
        $sorts = explode(",",$_REQUEST['sort']);
        $count = count($menuids);
        if ($count>0) {
            $sql = "UPDATE zxt_hotel_category SET sort = CASE id";
            for ($i=0; $i < $count ; $i++) { 
                $sql .= sprintf(" WHEN %d THEN '%s'",$menuids[$i],$sorts[$i]);
            }
            $menuids_str = implode(",", $menuids);
            $sql .= "END WHERE id IN($menuids_str)";
            D("hotel_category")->execute($sql);

            $vo = D("hotel_category")->where('id='.$menuids['0'])->field('hid')->find();
            $this->updatejson_one($vo['hid']);
            $this->updatejson_two($vo['hid']);
            $this->success('恭喜，操作成功！');
        }else{
            $this->error('系统提示：参数错误');
        }   
    }

    //栏目复制(暂时屏蔽)
    public function copy() {
        $model = M(CONTROLLER_NAME);
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if(is_array($ids)){
            if(!$ids){
                $this->error('参数错误！请选择要复制的内容！');
            }
            $map['id']  = array('in',$ids);
        }else{
            if(!$ids){
                $this->error('参数错误！请选择要复制的内容！');
            }
            $map['id']  = $ids;
        }
        //利用group方法去重
        $data = $model->where($map)->group('langcodeid')->select();
        if (count($data)>1) {
            $this->error('所选栏目的语言只能是同一种，请选择同一语言的栏目作为复制的源！');
        }
        $list=$model->where($map)->select();
        /*若所选菜单为子栏目但没选上级，系统补上上级栏目*/
        $p_num=0;
        $p_menu=array();
        $select_menuid=array();
        foreach ($list as $key => $value) {
            $select_menuid[$key]=$value['id'];
            if ($value['pid']>0) {
                $p_menu[$p_num]=$value['pid'];
                $p_num++;
            }
        }
        $menuIDs = array_merge($select_menuid, $p_menu);
        $menuIDs = array_unique($menuIDs);
        $menuIDs = array_values($menuIDs);
        $vomap['id']=array('in',$menuIDs);
        $folist = $model ->where($vomap)->order("pid asc,sort asc")->select();
        $volist = $this->list_to_tree($folist, 'id', 'pid', '_child', 0);
        $this->assign("volist",$volist);//要复制的栏目源
        $this->assign("srcLang",$data[0]['langcodeid']);//所选语言
        $this->assign("hid",$data[0]['hid']);//所属酒店编号
        $this->assign("menuIDs",implode(',', $menuIDs));

        $Langcode = D("Langcode");
        $list = $Langcode->where("status=1")->order("id asc")->select();
        $this->assign("langlist",$list);

        $this->display();
    }
    //栏目复制主页(暂时屏蔽)
    public function copy_run(){
        $hid=$_REQUEST['hid'];
        $langA = $_REQUEST['srcLang'];
        $langB = $_REQUEST['dstLang'];
        $timestamp=time();
        if (empty($langB)) {
            $this->error("请选择要复制的目标语言！");
        }
        $menuIDs=$_REQUEST['menuIDs'];
        $Menu = D("HotelCategory");
        $Resource = D("HotelResource");
        $map=array();
        $map['hid']=$hid;
        $map['pid']=0;
        $map['langcodeid']=$langA;
        $map['id']=array('in',$menuIDs);
        $srcOneMenu=$Menu->where($map)->order('sort asc')->select();
        foreach ($srcOneMenu as $key => $value) {
            //复制一级栏目
            $sort=0;
            $data=array();
            //B语言当前一级栏目最大排序
            $sort = $Menu->where('pid=0 and langcodeid='.$langB.' and hid="'.$hid.'"  ')->max('sort');
            $data[$key]['hid'] = $hid;
            $data[$key]['name'] = $value['name'].$timestamp;
            $data[$key]['modeldefineid'] = $value['modeldefineid'];
            $data[$key]['langcodeid'] = $langB;
            $data[$key]['pid'] = 0;
            $data[$key]['sort'] = $sort+1;
            $data[$key]['intro'] = $value['intro'];
            $data[$key]['icon'] = $value['icon'];
            $data[$key]['status'] = $value['status'];
            $oneCatID = $Menu->add($data[$key]);
            //复制一级栏目的资源
            $srcOneResource=$Resource->where('category_id='.$value['id'])->order('sort asc')->select();
            foreach ($srcOneResource as $keyOne => $valueOne) {
                $Rdata=array();
                $Rdata[$keyOne]['hid']=$valueOne['hid'];
                $Rdata[$keyOne]['title']=$valueOne['title'];
                $Rdata[$keyOne]['type']=$valueOne['type'];
                $Rdata[$keyOne]['cat']=$valueOne['cat'];
                $Rdata[$keyOne]['filepath']=$valueOne['filepath'];
                $Rdata[$keyOne]['intro']=$valueOne['intro'];
                $Rdata[$keyOne]['audit_status']=$valueOne['audit_status'];
                $Rdata[$keyOne]['upload_time']=$valueOne['upload_time'];
                $Rdata[$keyOne]['video_image']=$valueOne['video_image'];
                $Rdata[$keyOne]['price']=$valueOne['price'];
                $Rdata[$keyOne]['sort']=$valueOne['sort'];
                $Rdata[$keyOne]['status']=$valueOne['status'];
                $Rdata[$keyOne]['category_id']=$oneCatID;
                $resultR = $Resource->add($Rdata);
            }
            $twoMap=array();
            $twoMap['hid']=$hid;
            $twoMap['pid']=$value['id'];
            $twoMap['langcodeid']=$langA;
            $twoMap['id']=array('in',$menuIDs);
            $srcTwoMenu=$Menu->where($twoMap)->order('sort asc')->select();
            foreach ($srcTwoMenu as $keyTwoM => $valueTwoM) {
                //复制二级栏目
                $dataTwo=array();
                $sortTwo=0;
                $sortTwo = $Menu->where('pid='.$oneCatID.' and langcodeid='.$langB.' and hid="'.$hid.'"  ')->max('sort');
                $dataTwo[$keyTwoM]['hid'] = $hid;
                $dataTwo[$keyTwoM]['name'] = $valueTwoM['name'].$timestamp;
                $dataTwo[$keyTwoM]['modeldefineid'] = $valueTwoM['modeldefineid'];
                $dataTwo[$keyTwoM]['langcodeid'] = $langB;
                $dataTwo[$keyTwoM]['pid'] = $oneCatID;
                $dataTwo[$keyTwoM]['sort'] = $sortTwo+1;
                $dataTwo[$keyTwoM]['intro'] = $valueTwoM['intro'];
                $dataTwo[$keyTwoM]['icon'] = $valueTwoM['icon'];
                $dataTwo[$keyTwoM]['status'] = $valueTwoM['status'];
                $twoCatID = $Menu->add($dataTwo[$keyTwoM]);
                //复制二级栏目的资源
                $srcTwoResource=$Resource->where('category_id='.$valueTwoM['id'])->order('sort asc')->select();
                foreach ($srcTwoResource as $keyTwoR => $valueTwoR) {
                    $twoRdata=array();
                    $twoRdata[$keyTwoR]['hid']=$valueTwoR['hid'];
                    $twoRdata[$keyTwoR]['title']=$valueTwoR['title'];
                    $twoRdata[$keyTwoR]['type']=$valueTwoR['type'];
                    $twoRdata[$keyTwoR]['cat']=$valueTwoR['cat'];
                    $twoRdata[$keyTwoR]['filepath']=$valueTwoR['filepath'];
                    $twoRdata[$keyTwoR]['intro']=$valueTwoR['intro'];
                    $twoRdata[$keyTwoR]['audit_status']=$valueTwoR['audit_status'];
                    $twoRdata[$keyTwoR]['upload_time']=$valueTwoR['upload_time'];
                    $twoRdata[$keyTwoR]['video_image']=$valueTwoR['video_image'];
                    $twoRdata[$keyTwoR]['price']=$valueTwoR['price'];
                    $twoRdata[$keyTwoR]['sort']=$valueTwoR['sort'];
                    $twoRdata[$keyTwoR]['status']=$valueTwoR['status'];
                    $twoRdata[$keyTwoR]['category_id']=$twoCatID;
                    $resultRtwo = $Resource->add($twoRdata);
                }
            }
        }
        $this->success('恭喜，操作成功！',U('index').'?hid='.$hid);
    }

    /**
     * [栏目资源主页]
     */
    public function resource(){

        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if(is_array($ids)){
            if(count($ids)>1){
                $this->error('参数错误，每次只能管理一个栏目的资源！');
            }
            $category_id=$ids[0];
        }else{
            if(!$ids){
                $this->error('参数错误，请选择栏目！');
            }
            $category_id=$ids;
        }

        $vo = D("hotel_category")->where('id='.$category_id)->field('id,hid,modeldefineid,pid')->find();
        if ($vo['pid']==0) {
            $this->error('该栏目没有资源管理！',U('index').'?hid='.$vo['hid']);
        }
        $this->assign("current_category",$vo);

        $fo = D('modeldefine')->where('id='.$vo['modeldefineid'])->field('codevalue')->find();
        if($fo['codevalue']==501){
            $map['cid'] = $category_id;
            $map['ctype'] = 'videohotel';
            $list = M('HotelCarouselResource')->where($map)->order("sort asc")->select();
            $this->assign('ctype','videohotel');
            $prevcontroller = U("HotelCategory/index");
            $this->assign('prevcontroller',$prevcontroller);
            $this->assign('incontroller','hotel_category');
            $this->assign('gocontroller','HotelCategory');
            $this -> assign("list",$list);
            $this->display("Carousel/resource");
        }else{
            $map['category_id']=$category_id;
            $map['cat']="content";
            $list = M('HotelResource')->where($map)->order("sort asc")->select();
            $this->assign('codevalue',$fo['codevalue']);
            $this->assign("list",$list);
            $this->display();
        }
    }

    /**
     * [删除栏目资源]
     */
    public function resource_delete(){

        $model = M("HotelResource");
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if ($ids) {
            $map['id']  = array('in',$ids);
        }else{
            $this->error('删除失败，参数错误！',U('resource').'?ids='.$_REQUEST['category_id']);
        }

        $list = $model->where($map)->field('id,hid,filepath,video_image,size')->select();
        $model->startTrans();

        //更新容量
        $vresult = $this->updatevolume($list['0']['hid']);
        if ($vresult === false) {
            $model->rollback();
            $this->error('删除失败，参数错误！',U('resource').'?ids='.$_REQUEST['category_id']);
        }

        if($model->where($map)->delete()){

            $model->commit();

            //更新资源栏目
            $this->updatejson_hotelresource($list['0']['hid']);

            //删除对应文件
            foreach ($list as $key => $value) {
                @unlink(FILE_UPLOAD_ROOTPATH.$value['filepath']);
                @unlink(FILE_UPLOAD_ROOTPATH.$value['video_image']);
            }
            $this->success('恭喜，删除成功！',U('resource').'?ids='.$_REQUEST['category_id']);
        }else{
            $model->rollback();
            $this->error('删除失败，参数错误！',U('resource').'?ids='.$_REQUEST['category_id']);
        }
    }

    /**
     * [酒店栏目资源排序更新]
     * @return [type] [description]
     */
    public function resource_sort() {
        $menuids = explode(",", $_REQUEST['menuid']);
        $sorts = explode(",",$_REQUEST['sort']);
        $count = count($menuids);
        if ($count>0) {
            $sql = "UPDATE zxt_hotel_resource SET sort = CASE id";
            for ($i=0; $i < $count ; $i++) { 
                $sql .= sprintf(" WHEN %d THEN '%s'",$menuids[$i],$sorts[$i]);
            }
            $menuids_str = implode(",", $menuids);
            $sql .= "END WHERE id IN($menuids_str)";
            D("hotel_resource")->execute($sql);

            $vo = D("hotel_resource")->where('id='.$menuids['0'])->field('hid')->find();
            $this->updatejson_hotelresource($vo['hid']);
            $this->success('恭喜，操作成功！');
        }else{
            $this->error('系统提示：参数错误');
        }   
        $this->success('恭喜，操作成功！',U('resource').'?ids='.$_REQUEST['category_id']);
    }

    /**
     * [栏目资源启用]
     */
    public function resource_unlock(){
        $model = M("HotelResource");
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if($ids){
            $ids_str = implode(',',$ids);
            $map['id']  = array('in',$ids_str);
            $result = $model->where($map)->setField("status",1);
            if($result !== false){
                addlog('启用'.CONTROLLER_NAME.'表数据，数据ID：'.$ids_str);
                $vo = D("hotel_resource")->where('id='.$ids['0'])->field('hid')->find();
                $this->updatejson_hotelresource($vo['hid']);
                $this->success('恭喜，启用成功！',U('resource').'?ids='.$_REQUEST['category_id']);
            }else{
                $this->error('启用失败，参数错误！',U('resource').'?ids='.$_REQUEST['category_id']);
            }
        }else{
            $this->error('参数错误！',U('resource').'?ids='.$_REQUEST['category_id']);
        }
    }

    /**
     * [栏目资源禁用]
     */
    public function resource_lock(){
        $model = M("HotelResource");
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if($ids){
            $ids_str = implode(',',$ids);
            $map['id']  = array('in',$ids_str);
            $result = $model->where($map)->setField("status",0);
            if($result !== false){
                addlog('禁用'.CONTROLLER_NAME.'表数据，数据ID：'.$ids_str);
                $vo = D("hotel_resource")->where('id='.$ids['0'])->field('hid')->find();
                $this->updatejson_hotelresource($vo['hid']);
                $this->success('恭喜，禁用成功！',U('resource').'?ids='.$_REQUEST['category_id']);
            }else{
                $this->error('禁用失败，参数错误！',U('resource').'?ids='.$_REQUEST['category_id']);
            }
        }else{
            $this->error('参数错误！',U('resource').'?ids='.$_REQUEST['category_id']);
        }
    }
    
    /**
     * [栏目资源添加主页]
     */
    public function resource_add(){

        $category_id = I('get.category_id');
        $vo = D("hotel_category")->where('id='.$category_id)->field('id,hid,pid,modeldefineid')->find();
        $this->assign("current_category",$vo);

        $codevalue = I('get.codevalue','','intval');
        if ($vo['pid']==0) {
            //一级栏目的资源只能是图片
            $type=2;//图片
        }else if ($codevalue == 103) {
            $type=1;//视频
        }else if ($codevalue == 102) {
            $type=2;//图片
        }else{
            $this->error('该栏目不可添加资源！',U('resource').'?ids='.$category_id);
        }
        
        $this->assign("resource_type",$type);
        $this->display();
    }

    /**
     * [栏目资源修改主页]
     */
    public function resource_edit(){

        if(count(I('request.ids'))!=1){
            $this->error('参数错误，每次请修改一条内容！');
        }
        $ids = I('request.ids');
        $fo = D("hotel_resource")->getById($ids[0]);

        if(!empty($fo['filepath'])){
            $fo['size1'] = round(filesize(FILE_UPLOAD_ROOTPATH.$fo['filepath'])/1024);
        }else{
            $fo['size1'] = 0;
        }
        if(!empty($fo['video_image'])){
            $fo['size2'] = round(filesize(FILE_UPLOAD_ROOTPATH.$fo['video_image'])/1024);
        }else{
            $fo['size2'] = 0;
        }
        
        $this->assign('vo',$fo);
        $this -> display();
    }

    /**
     * [栏目资源保存]
     */
    public function resource_update(){

        //数据校验
        $data = $this->resourceInputProcess();

        D("hotel_resource")->startTrans();

        if($data['id']){//修改
            $vo = $model->getById($data['id']);

            //查询容量
            $changesize = $data['size'] - $vo['size'];
            $volumeResult = $this->checkVolume($data['hid'],$changesize);//检查容量是否超标
            if($volumeResult === false){
                if($vo['video_image'] != $data['video_image']){
                    @unlink(FILE_UPLOAD_ROOTPATH.$data['video_image']);
                }
                if($vo['filepath'] != $data['filepath']){
                    @unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
                }
                $this->error("超过申请容量值，无法修改资源");
            }

            $result = $model->data($data)->where('id='.$data['id'])->save();
            if($result === false){
                D("hotel_resource")->rollback();
                $this->error('系统提示：新增失败');
            }

        }else{

            //查询容量
            $volumeResult = $this->checkVolume($data['hid'],$data['size']);//检查容量是否超标
            if($volumeResult === false){
                @unlink(FILE_UPLOAD_ROOTPATH.$data['filepath']);
                @unlink(FILE_UPLOAD_ROOTPATH.$data['video_image']);
                $this->error("超过申请容量值，无法新增资源");
            }

            //插入新增数据
            $result = D("hotel_resource")->data($data)->add();
            if($result === false){
                D("hotel_resource")->rollback();
                $this->error('系统提示：新增失败');
            }
        }

        $hmap['hid'] = I('post.hid');
        //更新容量表
        $this->updatevolume($hmap);
        //更新资源json
        // $this->updatejson_hotelresource($hmap);

        if (!empty($data['id'])) {
            if($data['filepath'] != $vo['filepath']){
                @unlink(FILE_UPLOAD_ROOTPATH.$vo['filepath']);
            }
            if ($data['video_image'] != $vo['video_image']) {
                @unlink(FILE_UPLOAD_ROOTPATH.$vo['video_image']);
            }
        }
        
        D("hotel_resource")->commit();
        $this->success('恭喜，操作成功！',U('resource').'?ids='.$_REQUEST['category_id']);
    }

    /**
     * [栏目资源输入数据处理]
     */
    private function resourceInputProcess(){

        $data['id'] = I('post.id','','intval');
        $data['hid'] = I('post.hid','','strip_tags');
        $data['title'] = isset($_POST['title'])?$_POST['title']:false;
        $data['intro'] = I('post.intro','','strip_tags');
        $data['sort'] = I('post.sort','','intval');
        $data['filepath'] = I('post.filepath','','strip_tags');
        $data['video_image'] = I('post.vimage','','strip_tags');
        $data['price'] = I('post.price','','strip_tags');
        $data['status'] = 0;
        $codevalue = I('post.codevalue','','intval');
        $data['audit_status'] = 0;
        $size1 = I('post.size1','','intval');
        $size2 = I('post.size2','','intval');
        $size = $size1 + $size2;
        $data['size'] = round($size/1024,3);
        if (empty($data['id'])) {
            $data['cat'] = 'content';
            $data['upload_time'] = time();
            $data['type'] = I('post.type','','intval');
            $data['category_id'] = I('post.category_id','','intval');
        }

        if(!$data['title']){
            $this->error('警告！资源标题必须填写！');
        }
        if (empty($data['filepath'])) {
            $this->error('请上传资源文件！');
        }
        return $data;
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
                }else if ($_REQUEST['filetype']==2){
                    $upload->maxSize=2097152;// 设置附件上传大小2M
                    $upload->exts=array('jpg','png','jpeg');
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

    /**
     * [更新容量表]
     * @param  [array] $hmap [更新容量表条件]
     * @return [array] $updatesizeResult [更新结果]
     */
    private function updatevolume($hmap){

        $sizelist = M('hotel_resource')->field('SUM(size)')->where($hmap)->select();
        $csizelist = M('hotel_category')->field('SUM(size)')->where($hmap)->select();
        $rsize = $sizelist[0]['sum(size)']+$csizelist[0]['sum(size)'];
        if(M("hotel_volume")->where($hmap)->count()){
            $updatesizeResult = D("hotel_volume")->where($hmap)->setField("content_size",$rsize);
        }else{
            $arrdata['hid'] = $data['hid'];
            $arrdata['content_size'] = $rsize;
            $arrdata['topic_size'] = 0.00;
            $arrdata['ad_size'] = 0.00;
            $updatesizeResult = M("hotel_volume")->data($arrdata)->add();
        }
        if($updatesizeResult === false){
            M("hotel_volume")->rollback();
            $this->error('更新容量表失败');
        }
        return $updatesizeResult;
    }

    /**
     * [更新一级栏目json数据]
     * @param  [string] $hid [酒店编号]
     */
    private function updatejson_one($hid){
        $map = array();
        $map['zxt_hotel_category.hid'] = $hid;
        $map['zxt_hotel_category.pid'] = 0;
        $map['zxt_hotel_category.status'] = 1;
        $map['zxt_modeldefine.codevalue'] = array('in',array('100','101','102','103'));
        $field = "zxt_hotel_category.id,zxt_hotel_category.hid,zxt_hotel_category.name,zxt_hotel_category.sort,zxt_hotel_category.intro,zxt_hotel_category.icon,zxt_modeldefine.codevalue";
        $list = D("hotel_category")->field($field)->where($map)->join('zxt_modeldefine on zxt_hotel_category.modeldefineid = zxt_modeldefine.id')->select();
        if(!empty($list)){
            foreach ($list as $key => $value) {
                $list[$key]['nexttype'] = 'hotelcategory_second';
            }
            $jsondata = json_encode($list);
        }else{
            $jsondata = '';
        }
        if(!is_dir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid)){
            mkdir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid);
        }
        $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid.'/hotelcategory_first.json';
        file_put_contents($filename, $jsondata);
    }

    /**
     * [更新二级栏目json数据]
     * @param  [string] $hid [酒店编号]
     */
    private function updatejson_two($hid){
        $map = array();
        $map['zxt_hotel_category.hid'] = $hid;
        $map['zxt_hotel_category.status'] = 1;
        $map['zxt_hotel_category.pid'] = array('neq',0);
        $map['zxt_modeldefine.codevalue'] = array('in',array('100','101','102','103'));
        $field = "zxt_hotel_category.id,zxt_hotel_category.hid,zxt_hotel_category.name,zxt_hotel_category.pid,zxt_hotel_category.sort,zxt_hotel_category.intro,zxt_hotel_category.icon,zxt_modeldefine.codevalue";
        $list = D("hotel_category")->where($map)->field($field)->join('zxt_modeldefine on zxt_hotel_category.modeldefineid = zxt_modeldefine.id')->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $value['nexttype'] = 'hotelresource';
                $plist[$value['pid']][] = $value;
            }
            $jsondata = json_encode($plist);
        }else{
            $jsondata = '';
        }
        if(!is_dir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid)){
            mkdir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid);
        }
        $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid.'/hotelcategory_second.json';
        file_put_contents($filename, $jsondata);
    }

    /**
     * [更新栏目资源json文件]
     */
    private function updatejson_hotelresource($hid){
        $map['hid'] = $hid;
        $map['cat'] = 'content';
        $map['status'] = 1;
        $map['audit_status'] = 4;
        $field = "";
        $list = D("hotel_resource")->where($map)->field()->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $plist[$value['category_id']][] = $value; 
            }
            $jsondata = json_encode($plist);
        }else{
            $jsondata = '';
        }
        if(!is_dir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid)){
            mkdir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid);
        }
        $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid.'/hotelresource.json';
        file_put_contents($filename, $jsondata);
    }

}