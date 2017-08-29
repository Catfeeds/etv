<?php
// +------------------------------------------------------------------------------------------------------------
// | Author:Blues
// +------------------------------------------------------------------------------------------------------------
// | Last Update Time:2017-08-24
// +------------------------------------------------------------------------------------------------------------
// | 1.siteName 服务器IP或域名
// | 2.port 端口，默认端口80
// | 3.projectName 项目名，与./app/Conf/db.php中的PROJECT_NAME值一致
// | 4.interfaceName 接口方法名
// | 5.paramN 传入参数
// | 6.valueN 传入参数的值
// +-------------------------------------------------------------------------------------------------------------
namespace Admin\Controller;
use Think\Controller;
class SmarthomeController extends Controller{

    static protected $serverUrl = 'http://125.88.254.149/etv/Public';
    static protected $controller_type_arr = ['air','lamp'];

	public function uploadhomeparams(){
		$mac = strtoupper(I('post.mac'));
		if(empty($mac)){
			$this->errorCallback(10000,'ERROR: The mac is empty');
		}
		$controller_type = trim(I('post.controller_type'));

		if(empty($controller_type)){
			$this->errorCallback(10000,'ERROR: The controller_type is empty');
		}

		if(in_array($controller_type, $controller_type_arr)){
			$this->errorCallback(10000,'ERROR:The controller_type is illegal');
		}

		switch ($controller_type) {
			case 'air':
				$air_id = I('post.air_id');
				break;
			case 'lamp':
				# code...
				break;
			default:
				# code...
				break;
		}
	}

	private function errorCallback($status,$info){
        $data['status'] = $status;
        $data['info'] = $info;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}