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
			$this->Callback(10000,'ERROR: The mac is empty');
		}
		$controller_type = trim(I('post.controller_type'));

		if(empty($controller_type)){
			$this->Callback(10000,'ERROR: The controller_type is empty');
		}

		if(in_array($controller_type, $controller_type_arr)){
			$this->Callback(10000,'ERROR:The controller_type is illegal');
		}
		$model = D("controller_order");
		$model->startTrans();
		$addcontrollerData['mac'] = $mac;
		$addcontrollerData['add_time'] =  time();
		$addcontrollerData['device_type'] = $controller_type;
		switch ($controller_type) {
			case 'air':
				break;
			case 'lamp':
				$lampData['basic_param'] = I('post.basic_param','','strip_tags');
				$lampData['devicetype_param'] = I('post.devicetype_param','','strip_tags');
				$lampData['deviceorder_param'] = I('post.deviceorder_param','','strip_tags');
				$lampData['deviceorderchannel_param'] = I('post.deviceorderchannel_param','','strip_tags');
				$lampData['deviceaction_param'] = I('post.deviceaction_param','','strip_tags');
				$lampData['devicecheck_param'] = I('post.devicecheck_param','','strip_tags');
				if(empty($lampData['basic_param']) || empty($lampData['devicetype_param']) || empty($lampData['deviceorder_param']) || empty($lampData['deviceorderchannel_param']) || empty($lampData['deviceaction_param']) || empty($lampData['devicecheck_param'])){
					$this->Callback(10000,'ERROR:the controller params is empty');
				}
				$cid = $model->data($addcontrollerData)->add();
				$lampData['mac'] = $mac;
				$lampData['cid'] = $cid;
				$result = D("controller_lamporder")->data($lampData)->add();
				break;
			default:
				# code...
				break;
		}

		if($result !== false){
			$this->Callback(200,'Success');
		}else{
			$this->Callback(0,'Error');
		}
	}

	private function Callback($status,$info){
        $data['status'] = $status;
        $data['info'] = $info;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}