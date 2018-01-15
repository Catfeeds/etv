<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 测试控制器 - 用于给测试人员测试
// +-------------------------------------------------------------------------
namespace Admin\Controller;

class TestunitController {


	public function outh(){

		$code = $_GET['code'];
		$state = $_GET['state'];

		// 通过code换取网页授权access_token
		$code_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx4690ac23b950f4ff&secret=ee7d80f83dda297987abb3a9531ae707&code=".$code."&grant_type=authorization_code";
		$res = $this->https_request($code_url);
		$res=(json_decode($res, true));

        // 查询数据库是否有注册

		// 刷新access_token(如果需要)
		$refresh_url = "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=wx4690ac23b950f4ff&grant_type=refresh_token&refresh_token=".$res['refresh_token'];
		$this->https_request($refresh_url);

		$row = $this->get_user_info($res['openid'], $res['access_token']);

		if ($row['openid']) {  
        	//这里写上逻辑,存入cookie,数据库等操作  
        	var_dump($row);die();
             
        }else{  
            var_dump('gg');
        }  
	}

	public function https_request($url, $data=null){
		$curl = curl_init();  
        curl_setopt($curl, CURLOPT_URL, $url);  
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);  
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);  
        if (!empty($data)){  
            curl_setopt($curl, CURLOPT_POST, 1);  
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);  
        }  
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
        $output = curl_exec($curl);  
        curl_close($curl);  
        return $output;  
	}

	//获取用户基本信息  
    public function get_user_info($openid, $access_token)  
    {  
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";  
        $res = $this->https_request($url);  
        return json_decode($res, true);  
    }  

    public function do_mencrypt()
    {   
        $input = "加密前的字符串";
        $key = "ZXT4008306306";
        $key = substr(md5($key), 0, 24);
        $td = mcrypt_module_open('tripledes', '', 'ecb', '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $encrypted_data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        echo trim(chop(base64_encode($encrypted_data)));
    }

    function do_mdecrypt()
    {
        $input = "加密后的字符串";
        $key = "ZXT4008306306";
        $input = trim(chop(base64_decode($input)));
        $td = mcrypt_module_open('tripledes', '', 'ecb', '');
        $key = substr(md5($key), 0, 24);
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $decrypted_data = mdecrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        echo trim(chop($decrypted_data));
    }   


}