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
		$insertsql1 = "INSERT INTO `zxt_youqu_partybuild_group`(`partybuilt_id`,`name`,`type`) VALUES";
		foreach ($categoryList as $key => $value) {
			$insertsql1 .= "('".$value['id']."','".$value['name']."','".$value['type']."'),";
		}
		$insertsql1 = rtrim($insertsql1,",");
		$insertsql1 .= " ON DUPLICATE KEY UPDATE `name`= VALUES(name),`type`=VALUES(type)";
		$insert_result_1 = D("youqu_partybuild_group")->execute($insertsql1);
		if ($insert_result_1 === false) {
			D("youqu_partybuild_group")->rollback();
			die('gg');
		}

		//调用categoryid获取二级栏目
		$insertsql2_arr = ''; //新增或更新数据集合
		$new_ids_arr_o = array(); //二级栏目id集合
		foreach ($new_ids_arr as $key => $value) {
			$object = $this->partybulidload_category($value);
			if (!empty($object)) {
				foreach ($object as $okey => $ovalue) {
					$insertsql2_str .= "(".$value.", ".$ovalue['id'].",'".$ovalue['title']."','".$ovalue['titleImage']['0']."',".$ovalue['type']."),";
					$new_ids_arr_o[] = $ovalue['id']; 
					$new_ids_arr_o_key[$value][] = $ovalue['id']; //partybuild_id作为key的二维数组
				}
			}
		}
		$insertsql2_str = rtrim($insertsql2_str,",");
		$this->partybulidinsert_category($insertsql2_str, $new_ids_arr_o); //二级插入

		//调用objectId获取三级栏目
		$insertsql3_str = ''; //新增或更新数据集合
		foreach ($new_ids_arr_o_key as $k1 => $v1) {
			foreach ($v1 as $k2 => $v2) {
				$result = $this->partybulidload_resource($v2,$k1);
				if (!empty($result)) {
					$insertsql3_str .= $result;
				}
			}
		}
		// $insertsql3_str = $this->partybulidload_resource(4320,57);
		$insertsql3_str = rtrim($insertsql3_str,",");
		$insertsql3_str = "INSERT INTO `zxt_youqu_partybuild_resource`(`partybuild_id`, `category_id`, `title`, `titleimage`, `type`, `content`, `image`, `video`, `image_size`, `video_size`) VALUES ".$insertsql3_str." ON DUPLICATE KEY UPDATE `title`= VALUES(title),`titleimage`=VALUES(titleimage),`type`=VALUES(type),`content`=VALUES(content),`image`=VALUES(image),`video`=VALUES(video),`image_size`=VALUES(image_size),`video_size`=VALUES(video_size)";
		$insert_result_3 = D("youqu_partybuild_resource")->execute($insertsql3_str);//三级插入
		if ($insert_result_3 === false) {
			D("youqu_partybuild_group")->rollback();
			die('gg');
		}
		die('ok');
	}

	// 获取党建园地资讯列表获取
	public function partybulidload_category($groupid){
		$url = 'https://t.iyouqu.com.cn:8443/app/newsActivity/service.do?text={"index":0,"msgId":"APP150","categoryId":'.$groupid.',"userId":20488,"categoryType":1,"department":"02A39800"}';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //跳过SSL证书检查

		$output = curl_exec($ch);
		curl_close($ch);

		$data = json_decode($output,true);
		$objectList = $data['resultMap']['objectList']; //党建园地的资讯列表
		
		return $objectList;
	}

	// 党建园地资讯列表数据库操作
	public function partybulidinsert_category($insertsql2_str, $new_ids_arr_o){
		$categoryList = D("youqu_partybuild_category")->field('category_id')->group('category_id')->select(); // 党建园地category表中category_id集合
		if (!empty($categoryList)) {
			foreach ($categoryList as $key => $value) {
				$old_ids_arr_o = $value['category_id'];
			}
		}else{
			$old_ids_arr_o = [];
		}

		$diff_id_arr_o = array_diff($old_ids_arr_o,$new_ids_arr_o);
		if (!empty($diff_id_arr_o)) {
			$del_where['category_id'] = array('in', $diff_id_arr_o);
			$del2 = D("youqu_partybuild_category")->where($del_where)->delete();
			$del3 = D("youqu_partybuild_resource")->where($del_where)->delete();
			if ($del2===false || $del3===false) {
				D("youqu_partybuild_group")->rollback();
				die('gg');
			}
		}

		$insertsql2 = "INSERT INTO `zxt_youqu_partybuild_category`(`partybuild_id`, `category_id`, `title`, `image`, `type`) VALUES ".$insertsql2_str. "ON DUPLICATE KEY UPDATE `title`= VALUES(title),`image`=VALUES(image),`type`=VALUES(type)";
		$insert_result_2 = D("youqu_partybuild_group")->execute($insertsql2);
		if ($insert_result_2 === false) {
			D("youqu_partybuild_group")->rollback();
			die('gg');
		}
		return true;
	}

	// 党建园地内容详情获取
	public function partybulidload_resource($objectId, $partybuild_id){
		$url = 'https://t.iyouqu.com.cn:8443/app/newsActivity/service.do?text={"msgId":"APP009","userId":20488,"objectId":'.$objectId.'}';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //跳过SSL证书检查

		$output = curl_exec($ch);
		curl_close($ch);
		$data = json_decode($output,true);
		$result = $data['resultMap']; //党建园地的资讯列表
		if (empty($data['resultMap'])) {
			return;
		}
		$image_arr = array();
		$video_arr = array();
		$image_size = 0;
		$video_size = 0;
		if (!empty($result['attachList'])) {
			foreach ($result['attachList'] as $key => $value) {
				if ($value['type'] == 2) {  //视频
					$video_arr[] = $value['url'];
					$video_size += $value['size'];
				}elseif($value['type'] == 1){ //图片
					$image_arr[] = $value['url'];
					$image_size += $value['size'];
				} 
			}
		}

		$insertsql = "(".$partybuild_id.", ".$objectId.", '".$result['newsInfo']['title']."', '".$result['newsInfo']['titleimage']."',".$result['newsInfo']['type']." ,'".addslashes($result['newsInfo']['content'])."', '".json_encode($image_arr)."', '".json_encode($video_arr)."', ".$image_size.", ".$video_size."),";
		return $insertsql;
	}


	// 学习园地二级栏目列表获取
	public function learning_category(){

		$url1 = 'https://t.iyouqu.com.cn:8443/app/newsActivity/service.do?text={"index":0,"msgId":"APP150","categoryId":24,"userId":20488,"categoryType":1,"department":"02A39800"}';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //跳过SSL证书检查

		$output = curl_exec($ch);
		curl_close($ch);

		$data = json_decode($output,true);
		$objectList = $data['resultMap']['objectList'];
		if (!empty($objectList)) {
			$insertsql_str = "INSERT INTO `zxt_youqu_learning_category`(`category_id`, `title`, `image`, `type`) VALUES "; //插入sql 
			foreach ($objectList as $key => $value) {
				$id_arr1[] = $value['id'];
				$insertsql_str .= "(".$value['id'].",'".$value['title']."','".$value['titleImage']['0']."',".$value['type']."),"; 
			}
			$insertsql_str = rtrim($insertsql_str,",");
			$insertsql_str .= " ON DUPLICATE KEY UPDATE `title`= VALUES(title),`image`=VALUES(image),`type`=VALUES(type)";

			//查找数据库 查询已有的category_id
			$grouplist = D("youqu_learning_category")->field('category_id')->select(); //学习园地category表含有记录
			if (!empty($grouplist)) {
				foreach ($grouplist as $key => $value) {
					$ole_ids_arr[] = $value['category_id'];
				}
			}else{
				$ole_ids_arr = [];
			}
			//查找现在没有的  做删除
			$diff_id_arr_old = array_diff($ole_ids_arr,$id_arr1);
			D("youqu_learning_category")->startTrans();
			if (!empty($diff_id_arr_old)) {
				$del_where['category_id'] = array('in',$diff_id_arr_old);
				$del1 = D("youqu_learning_category")->where($del_where)->delete();
				$del2 = D("youqu_learning_resource")->where($del_where)->delete();
				if ($del1===false || $del2===false) {
					D("youqu_partybuild_group")->rollback();
					die('gg');
				}
			}

			// 更新或新增记录
			$insert_result_1 = D("youqu_learning_category")->execute($insertsql_str);
			if ($insert_result_1 === false) {
				D("youqu_partybuild_group")->rollback();
				die('gg');
			}

			// 学习园地内容接口调用
			$insertsql2_str = ''; //新增或更新数据集合
			foreach ($id_arr1 as $key => $value) {
				$result = $this->learning_load_resource($value);
				if (!empty($result)) {
					$insertsql2_str .= $result;
				}
			}
			$insertsql2_str = rtrim($insertsql2_str,",");
			$insertsql2_str = "INSERT INTO `zxt_youqu_learning_resource`(`category_id`, `title`, `titleimage`, `type`, `content`, `image`, `video`, `image_size`, `video_size`) VALUES ".$insertsql2_str." ON DUPLICATE KEY UPDATE `title`= VALUES(title),`titleimage`=VALUES(titleimage),`type`=VALUES(type),`content`=VALUES(content),`image`=VALUES(image),`video`=VALUES(video),`image_size`=VALUES(image_size),`video_size`=VALUES(video_size)";
			$insert_result_2 = D("youqu_learning_resource")->execute($insertsql2_str); //内容插入
			if ($insert_result_2 === false) {
				D("youqu_partybuild_group")->rollback();
				die('gg');
			}

			die('ok');
		}else{
			D("youqu_learning_category")->delete();
			D("youqu_learning_resource")->delete();
			die('delete ok');
		}
	}

	// 学习园地内容资源
	public function learning_load_resource($objectId){
		$url = 'https://t.iyouqu.com.cn:8443/app/newsActivity/service.do?text={"msgId":"APP009","userId":20488,"objectId":'.$objectId.'}';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //跳过SSL证书检查

		$output = curl_exec($ch);
		curl_close($ch);
		$data = json_decode($output,true);
		$result = $data['resultMap']; //党建园地的资讯列表
		if (empty($data['resultMap'])) {
			return;
		}

		$image_arr = array();
		$video_arr = array();
		$image_size = 0;
		$video_size = 0;
		if (!empty($result['attachList'])) {
			foreach ($result['attachList'] as $key => $value) {
				if ($value['type'] == 2) {  //视频
					$video_arr[] = $value['url'];
					$video_size += $value['size'];
				}elseif($value['type'] == 1){ //图片
					$image_arr[] = $value['url'];
					$image_size += $value['size'];
				} 
			}
		}

		$insertsql = "(".$objectId.", '".$result['newsInfo']['title']."', '".$result['newsInfo']['titleimage']."',".$result['newsInfo']['type']." ,'".addslashes($result['newsInfo']['content'])."', '".json_encode($image_arr)."', '".json_encode($video_arr)."', ".$image_size.", ".$video_size."),";

		return $insertsql;
	}

}