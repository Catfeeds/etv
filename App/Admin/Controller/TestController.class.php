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
}
