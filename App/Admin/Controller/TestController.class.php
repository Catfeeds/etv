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

	// public function idauth(){
	// 	$ch = curl_init();
	//     $url = 'http://apis.baidu.com/apix/idauth/idauth?name=%E5%BC%A0%E6%BE%9C&cardno=542626199110239641';
	//     $header = array(
	//         'apikey: 您自己的apikey',
	//     );
	//     // 添加apikey到header
	//     curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
	//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	//     // 执行HTTP请求
	//     curl_setopt($ch , CURLOPT_URL , $url);
	//     $res = curl_exec($ch);

	//     var_dump(json_decode($res));
	// }

	//移动文件和更改字段
	public function movefilepath(){
		
		$File = new File;
		$list = D("hotel_category")->field('id,hid,icon')->select();
		$rootfile = dirname(dirname(dirname(dirname(__FILE__)))).'/Public';
		$destfile = dirname(dirname(dirname(dirname(__FILE__)))).'/Public/upload/content/';
		foreach ($list as $key => $value) {
			if(!empty($value['icon'])){
				$arr = explode("/", $value['icon']);
				$filename = $arr['4'];
				$source = $rootfile.$value['icon'];
				$dest = $destfile.$value['hid'].'/'.$filename;
				$changefilepath[$value['id']] = '/upload/content/'.$value['hid'].'/'.$filename;
				var_dump($source,$dest);die();
				// var_dump($source,$dest);
				$copyresult = copy($source, $dest);//移动文件
				var_dump($copyresult);
			}
		}
		$ids = implode(",", array_keys($changefilepath));
		$sql = "UPDATE zxt_hotel_category SET icon = CASE id";
		foreach ($changefilepath as $id => $value) {
			$sql .= sprintf(" WHEN %d THEN '%s'",$id,$value);
		}
		$sql .= "END WHERE id IN($ids)";
		var_dump("------------------------------------");
		$result = D("hotel_category")->execute($sql);
		var_dump($result);
	}

	//更新xml文件
	public function updatexml(){
		$list = D("hotel_allresource")->field('hid')->group('hid')->select();
		foreach ($list as $key => $value) {
			$allresourceHidList[] = $value['hid'];
		}
		foreach ($allresourceHidList as $key => $value) {
            $xmlFilepath = FILE_UPLOAD_ROOTPATH.'/upload/resourceXml/'.$value.'.txt';
            $this->fileputXml(D("hotel_allresource"),$value,$xmlFilepath);
        }
	}
}
