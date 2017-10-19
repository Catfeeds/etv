<?php
namespace Admin\Controller;
use Admin\Controller\ComController;

use Vendor\File;

class TestController extends comController {

	public function filetest(){

		$File = new File;
		$rootfile = dirname(dirname(dirname(dirname(__FILE__)))).'/Public/upload/content/';
		var_dump($rootfile);
		$a = $File::copy_dir($rootfile.'/4008/',$rootfile.'/lockin/');
		var_dump($a);
	}

	public function idauth(){
		$ch = curl_init();
	    $url = 'http://apis.baidu.com/apix/idauth/idauth?name=%E5%BC%A0%E6%BE%9C&cardno=542626199110239641';
	    $header = array(
	        'apikey: 您自己的apikey',
	    );
	    // 添加apikey到header
	    curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    // 执行HTTP请求
	    curl_setopt($ch , CURLOPT_URL , $url);
	    $res = curl_exec($ch);

	    var_dump(json_decode($res));
	}

	//移动文件和更改字段sql
	public function movefilepath(){
		
		$File = new File;
		// $map['id'] = array('not in',array(87,89,91,93,94,95,100,101,102,103,104,105));
		$map = array();
		$list = D("hotel_chg_category")->where($map)->field('id,hid,filepath')->select();
		$rootfile = dirname(dirname(dirname(dirname(__FILE__)))).'/Public';
		$destfile = dirname(dirname(dirname(dirname(__FILE__)))).'/Public/upload/content/';
		foreach ($list as $key => $value) {
			if(!empty($value['filepath'])){
				$arr = explode("/", $value['filepath']);
				$filename = $arr['4'];
				$source = $rootfile.$value['filepath'];
				$dest = $destfile.$value['hid'].'/'.$filename;
				$changefilepath[$value['id']] = '/upload/content/'.$value['hid'].'/'.$filename;
				if(!is_dir($destfile.$value['hid'])){
					mkdir($destfile.$value['hid']);
				}
				$copyresult = copy($source, $dest);//移动文件
				var_dump($copyresult);
			}
		}
		$ids = implode(",", array_keys($changefilepath));
		$sql = "UPDATE zxt_hotel_chg_category SET filepath = CASE id";
		foreach ($changefilepath as $id => $value) {
			$sql .= sprintf(" WHEN %d THEN '%s'",$id,$value);
		}
		$sql .= "END WHERE id IN($ids)";
		var_dump("------------------------------------");
		$result = D("hotel_chg_category")->execute($sql);
		var_dump($result);
	}

	//更新xml文件
	public function updatexml(){
		$list = D("hotel_chg_category")->field('hid')->group('hid')->select();
		foreach ($list as $key => $value) {
			$allresourceHidList[] = $value['hid'];
		}
		foreach ($allresourceHidList as $key => $value) {
            $xmlFilepath = FILE_UPLOAD_ROOTPATH.'/upload/resourceXml/'.$value.'.txt';
            $this->fileputXml(D("hotel_chg_category"),$value,$xmlFilepath);
        }
	}

	public function md5password(){
		$password = '88888888';
		$password_md5 = 'ZXT'.$password.'ETV';
		var_dump(md5($password_md5));
	}

	public function getfilepath(){
		$hid = 4008;
        $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid.'/hotelcategory_first.json';
		var_dump($filename);
	}

	public function get_sql_temp(){
		$sql = "SHOW VARIABLES LIKE 'tmpdir'";
		$result = M()->query($sql);
		dump($result);
	}

	public function get_qcache(){
		$sql = "SHOW STATUS LIKE 'Qcache%'";
		$result = M()->query($sql);
		dump($result);
	}

	public function update_hotel_json_index(){
		$this->display();
	}

	public function update_hotel_json(){
		$hid = I('post.hid','','strtoupper');
		$hid = "4008";//测试
		// 更新酒店一级栏目              
		// $this->updatehotel_one($hid);
		// 更新酒店二级栏目
		// $this->updatehotel_two($hid);
		// 更新酒店栏目资源
		// $this->updatehotel_resource($hid);
		// 更新集团一级栏目
		// $this->updatechg_one($hid);
		// 更新集团二级栏目
		// $this->updatechg_two($hid);
		// 更新集团栏目资源
		// $this->updatechg_resource($hid);
		// 更新通用一级栏目
		// $this->updatetopic_one($hid);
		// 更新通用二级栏目
		// $this->updatetopic_two($hid);
		// 更新通用栏目资源
		// $this->updatetopic_resource($hid);
		// 更新视频轮播资源
		$this->updatecarousel_resource($hid);
		die('ok');
	}

	/**
	 * [酒店一级栏目json更新]
	 * @param  [string] $hid [酒店编号]
	 */
	private function updatehotel_one($hid){
		$map['zxt_hotel_category.hid'] = $hid;
		$map['zxt_hotel_category.pid'] = 0;
		$map['zxt_hotel_category.status'] = 1;
		$field = "zxt_hotel_category.id,zxt_hotel_category.hid,zxt_hotel_category.name,zxt_hotel_category.sort,zxt_hotel_category.intro,zxt_hotel_category.icon,zxt_modeldefine.codevalue,zxt_modeldefine.packagename,zxt_modeldefine.classname";
		$list = D("hotel_category")->field($field)->where($map)->join('zxt_modeldefine on zxt_hotel_category.modeldefineid = zxt_modeldefine.id')->order('sort')->select();
		if(!empty($list)){
            foreach ($list as $key => $value) {
                $list[$key]['nexttype'] = 'hotelcategory_second';
                if (in_array($value['codevalue'],array('100','101','102','103'))) {
                    $list[$key]['nexttype'] = 'hotelcategory_second';
                }elseif ($value['codevalue'] == '501') {
                    $list[$key]['nexttype'] = 'videohotel';
                }else{
                    $list[$key]['nexttype'] = 'app';
                }
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
     * [酒店二级栏目json更新]
     * @param  [string] $hid [酒店编号]
     */
    private function updatehotel_two($hid){
        $map = array();
        $map['zxt_hotel_category.hid'] = $hid;
        $map['zxt_hotel_category.status'] = 1;
        $map['zxt_hotel_category.pid'] = array('neq',0);
        // $map['zxt_modeldefine.codevalue'] = array('in',array('100','101','102','103'));
        $field = "zxt_hotel_category.id,zxt_hotel_category.hid,zxt_hotel_category.name,zxt_hotel_category.pid,zxt_hotel_category.sort,zxt_hotel_category.intro,zxt_hotel_category.icon,zxt_modeldefine.codevalue,zxt_modeldefine.packagename,zxt_modeldefine.classname";
        $list = D("hotel_category")->where($map)->field($field)->join('zxt_modeldefine on zxt_hotel_category.modeldefineid = zxt_modeldefine.id')->order('sort')->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                if (in_array($value['codevalue'],array('100','101','102','103'))) {
                    $value['nexttype'] = 'hotelresource';
                }elseif ($value['codevalue'] == '501') {
                    $value['nexttype'] = 'videohotel';
                }else{
                    $value['nexttype'] = 'app';
                }
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
     * [酒店栏目资源json更新]
     */
    private function updatehotel_resource($hid){
        $map['hid'] = $hid;
        $map['cat'] = 'content';
        $map['status'] = 1;
        $map['audit_status'] = 4;
        $field = "id,hid,category_id as cid,title,intro,sort,filepath,type as file_type,video_image as icon";
        $list = D("hotel_resource")->where($map)->field($field)->order('sort')->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $plist[$value['cid']][] = $value; 
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

    /**
     * [集团一级栏目json更新]
     * @param  [string] $hid [酒店编号]
     */
    private function updatechg_one($hid){
        $jsonmap['zxt_hotel_chg_category.hid'] = $hid;
        $jsonmap['zxt_hotel_chg_category.pid'] = 0;
        $jsonmap['zxt_hotel_chg_category.status'] = 1;
        $field = "zxt_hotel_chg_category.id,zxt_hotel_chg_category.hid,zxt_hotel_chg_category.name,zxt_hotel_chg_category.modeldefineid,zxt_hotel_chg_category.sort,zxt_hotel_chg_category.filepath,zxt_hotel_chg_category.intro,zxt_modeldefine.codevalue,zxt_modeldefine.packagename,zxt_modeldefine.classname";
        $list = D("hotel_chg_category")->field($field)->where($jsonmap)->join('zxt_modeldefine on zxt_hotel_chg_category.modeldefineid  = zxt_modeldefine.id')->order('zxt_hotel_chg_category.sort')->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                if (in_array($value['codevalue'],array('100','101','102','103'))) {
                    $list[$key]['nexttype'] = 'chgcategory_second';
                }elseif ($value['codevalue'] == '501') {
                    $list[$key]['nexttype'] = 'videochg';
                }else{
                    $list[$key]['nexttype'] = 'app';
                }
            }
            $jsondata = json_encode($list);
        }else{
            $jsondata = '';
        }
        if(!is_dir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid)){
            mkdir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid);
        }
        $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid.'/chgcategory_first.json';
        file_put_contents($filename, $jsondata);
    }

    /**
     * [集团二级栏目json更新]
     * @param  [string] $hid [酒店编号]
     */
    private function updatechg_two($hid){
        $jsonmap['zxt_hotel_chg_category.hid'] = $hid;
        $jsonmap['zxt_hotel_chg_category.pid'] = array('neq',0);
        $jsonmap['zxt_hotel_chg_category.status'] = 1;
        $field = "zxt_hotel_chg_category.id,zxt_hotel_chg_category.pid,zxt_hotel_chg_category.hid,zxt_hotel_chg_category.name,zxt_hotel_chg_category.modeldefineid,zxt_hotel_chg_category.sort,zxt_hotel_chg_category.filepath,zxt_hotel_chg_category.intro,zxt_modeldefine.codevalue,zxt_modeldefine.packagename,zxt_modeldefine.classname";
        $list = D("hotel_chg_category")->field($field)->where($jsonmap)->join('zxt_modeldefine on zxt_hotel_chg_category.modeldefineid  = zxt_modeldefine.id')->order('zxt_hotel_chg_category.sort')->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                if (in_array($value['codevalue'],array('100','101','102','103'))) {
                    $value['nexttype'] = 'chgresource';
                }elseif ($value['codevalue'] == '501') {
                    $value['nexttype'] = 'videochg';
                }else{
                    $value['nexttype'] = 'app';
                }
                $plist[$value['pid']][] = $value;
            }
            $jsondata = json_encode($plist);
        }else{
            $jsondata = '';
        }
        if(!is_dir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid)){
            mkdir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid);
        }
        $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid.'/chgcategory_second.json';
        file_put_contents($filename, $jsondata);
    }

    /**
     * [集团通用栏目资源更新]
     * @param  [string] $hid [酒店编号]
     */
    private function updatechg_resource($hid){
        $map['hid'] = $hid;
        $map['status'] = 1;
        $map['audit_status'] = 4;
        $field = "id,hid,cid,title,intro,sort,filepath,file_type,icon";
        $list = D("hotel_chg_resource")->field($field)->where($map)->select();
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $plist[$value['cid']][] = $value;
            }
            $jsondata = json_encode($plist);
        }else{
            $jsondata = '';
        }
        if(!is_dir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid)){
            mkdir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid);
        }
        $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid.'/chgresource.json';
        file_put_contents($filename, $jsondata);
    }

    /**
     * [通用一级栏目更新]
     * @param  [string] $hid [酒店编号]
     */
    private function updatetopic_one($hid){
    	$field = "zxt_topic_group.id,zxt_topic_group.title,zxt_topic_group.name,zxt_topic_group.en_name,zxt_topic_group.intro,zxt_topic_group.icon";
    	$map['zxt_hotel_topic.hid'] = $hid; 
    	$map['zxt_topic_group.status'] = 1;
    	$list = D("hotel_topic")->where($map)->field($field)->join('zxt_topic_group on zxt_hotel_topic.topic_id = zxt_topic_group.id')->select();
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
     * [通用二级栏目更新]
     * @param  [string] $hid [酒店编号]
     */
    private function updatetopic_two($hid){
    	$field = "zxt_topic_category.id,zxt_topic_category.groupid,zxt_topic_category.sort,zxt_topic_category.name,zxt_topic_category.modeldefineid,zxt_topic_category.icon";
    	$map['zxt_hotel_topic.hid'] = $hid;
    	$map['zxt_topic_category.status'] = 1; 
    	$list = D("hotel_topic")->where($map)->field($field)->join('zxt_topic_category on zxt_hotel_topic.topic_id = zxt_topic_category.groupid')->order('zxt_topic_category.sort')->select();
    	if (!empty($list)) {
	        foreach ($list as $key => $value) {
	        	$value['nexttype'] = 'topicresource';
	            $plist[$value['groupid']][] = $value;
	        }
	        $jsondata = json_encode($plist);
	    }else{
	        $jsondata = '';
	    }
    	if (!is_dir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid)) {
            mkdir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid);
        }
        $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid.'/topic_second.json';
        file_put_contents($filename, $jsondata);
    }

    /**
     * [通用栏目资源更新]
     * @param  [string] $hid [酒店编号]
     */
    private function updatetopic_resource($hid){
    	// $field = "zxt_topic_resource.cid,zxt_topic_resource.gid,zxt_topic_resource.title,zxt_topic_resource.type,zxt_topic_resource.";
    	$map['zxt_hotel_topic.hid'] = $hid;
    	$map['zxt_topic_resource.status'] = 1;
    	$map['zxt_topic_resource.audit_status'] = 4;
    	$list = D("hotel_topic")->where($map)->field($field)->join('zxt_topic_resource on zxt_hotel_topic.topic_id  = zxt_topic_resource.gid')->order('zxt_topic_resource.sort')->select();
    	if (!empty($list)) {
	        foreach ($list as $key => $value) {
	            $plist[$value['cid']][] = $value;
	        }
	        $jsondata = json_encode($plist);
	    }else{
	        $jsondata = '';
	    }
    	if (!is_dir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid)) {
            mkdir(FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid);
        }
        $filename = FILE_UPLOAD_ROOTPATH.'/hotel_json/'.$hid.'/topicresource.json';
        file_put_contents($filename, $jsondata);
    }

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
