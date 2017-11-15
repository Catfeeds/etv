<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 悠趣管理控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
use Think\Exception;

class YouquController extends ComController {

	public function partybulidload(){

        $url1 = 'https://t.iyouqu.com.cn:8443/app/newsActivity/service.do?text={"msgId":"APP155","menuId":"1","userId":20488}';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //跳过SSL证书检查

		$output = curl_exec($ch);
		curl_close($ch);

		$data = json_decode($output,true);
		$categoryList = $data['resultMap']['categoryList']; //党建园地的栏目
		foreach ($categoryList as $key => $value) {
			$new_ids_arr[] = $value['id'];
		}

		//调用categoryid获取二级栏目
		foreach ($new_ids_arr as $key => $value) {
			$this->partybulidload_category($value);
		}

		$grouplist = D("youqu_partybuild_group")->field('partybuilt_id')->select(); //党建园地group表含有记录
		if (!empty($grouplist)) {
			foreach ($grouplist as $key => $value) {
				$ole_ids_arr[] = $value['partybuilt_id'];
			}
		}else{
			$ole_ids_arr = [];
		}

		D("youqu_partybuild_group")->startTrans();
		//查找现在没有的  做删除
		$diff_id_arr_old = array_diff($ole_ids_arr,$new_ids_arr);
		if (!empty($diff_id_arr_old)) {
			$del_where['partybuilt_id'] = array('in',$diff_id_arr_old);
			$del1 = D("youqu_partybuild_group")->where($del_where)->delete();
			$del2 = D("youqu_partybuild_category")->where($del_where)->delete();
			$del3 = D("youqu_partybuild_resource")->where($del_where)->delete();
			if ($del1===false || $del2===false || $del3===false) {
				D("youqu_partybuild_group")->rollback();
				die('gg');
			}
		}

		//相同的更新  新的做新增
		foreach ($categoryList as $key => $value) {
			$insertsql1 = "INSERT INTO `zxt_youqu_partybuild_group`(`partybuilt_id`,`name`,`type`)values('".$value['id']."','".$value['name']."','".$value['type']."') ON DUPLICATE KEY UPDATE `name`='".$value['name']."' ,`type`='".$value['type']."'";
			$insert_result_1 = D("youqu_partybuild_group")->execute($insertsql1);
			if ($insert_result_1 === false) {
				D("youqu_partybuild_group")->rollback();
				die('gg');
			}
		}
		// dump($categoryList);
		// dump($grouplist);
		// dump($diff_id_arr_old);
	}

	public function partybulidload_category($categoryId){
		$url = 'https://t.iyouqu.com.cn:8443/app/newsActivity/service.do?text={"index":0,"msgId":"APP150","categoryId":'.$categoryId.',"userId":20488,"categoryType":1,"department":"02A39800"}';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //跳过SSL证书检查

		$output = curl_exec($ch);
		curl_close($ch);

		$data = json_decode($output,true);
		$objectList = $data['resultMap']['objectList']; //党建园地的栏目
		var_dump($objectList);
		die();
	}
}