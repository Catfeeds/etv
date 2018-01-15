<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 加密方法
// +-------------------------------------------------------------------------
namespace Admin\Controller;

class DesController {

	function do_mdecrypt()
    {
        $input = $_POST['input'];
        $key = "ZXT4008306306";
        $input = trim(chop(base64_decode($input)));
        $td = mcrypt_module_open('tripledes', '', 'ecb', '');
        $key = substr(md5($key), 0, 24);
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $decrypted_data = mdecrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return trim(chop($decrypted_data));
    }  

}