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
}
