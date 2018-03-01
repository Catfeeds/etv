<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 模块公共文件
// +-------------------------------------------------------------------------

function UpImage($callBack="image",$width=100,$height=100,$image=""){
    echo '<iframe scrolling="no" frameborder="0" border="0" onload="this.height=this.contentWindow.document.body.scrollHeight;this.width=this.contentWindow.document.body.scrollWidth;" width='.$width.' height="'.$height.'"  src="'.U('Upload/uploadpic').'?Width='.$width.'&Height='.$height.'&BackCall='.$callBack.'&Img='.$image.'"></iframe>
         <input type="hidden" name="'.$callBack.'" id="'.$callBack.'">';
}
function BatchImage($callBack="image",$height=300,$image=""){
    echo '<iframe scrolling="no" frameborder="0" border="0" onload="this.height=this.contentWindow.document.body.scrollHeight;this.width=this.contentWindow.document.body.scrollWidth;" src="'.U('Upload/batchpic').'?BackCall='.$callBack.'&Img='.$image.'"></iframe>
		<input type="hidden" name="'.$callBack.'" id="'.$callBack.'">';
}
/**
 * 函数：网站配置获取函数
 * @param  string $k      可选，配置名称
 * @return array          用户数据
 */
function setting($k=''){
    if($k==''){
        $setting =M('setting')->field('k,v')->select();
        foreach($setting as $k=>$v){
            $config[$v['k']] = $v['v'];
        }
        return $config;
    }else{
        $model = M('setting');
        $result=$model->where("k='{$k}'")->find();
        return $result['v'];
    }
}
/**
 * 函数：格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 */
function format_bytes($size, $delimiter = '') {
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
    return round($size, 2) . $delimiter . $units[$i];
}
/**
 * 函数：格式化字节大小
 */
function format_size($size) {
    $sizes = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
    if ($size == 0){
        return('');
    } else {
        return (round($size/pow(1024, ($i = floor(log($size, 1024)))), $i > 1 ? 2 : 0) . $sizes[$i]);
    }
}
/**
 * 格式化时间戳
 */
function format_timestamp($timestamp) {
    if ($timestamp==0) {
        return '-';
    }else{
        return date("Y-m-d H:i:s",$timestamp);
    }
}
/**
 * 函数：加密
 * @param string            密码
 * @return string           加密后的密码
 */
function password($password){
    return md5('ZXT'.$password.'ETV');
}
/**
 * 函数：截取指定长度的字符串
 * @param string            密码
 * @return string           加密后的密码
 */
function str_cut($sourcestr,$cutlength,$suffix='...'){
    $returnstr='';
    $i=0;
    $n=0;
    $str_length=strlen($sourcestr);//字符串的字节数
    while (($n<$cutlength) and ($i<=$str_length)){
        $temp_str=substr($sourcestr,$i,1);
        $ascnum=Ord($temp_str);//得到字符串中第$i位字符的ascii码
        if ($ascnum>=224){//如果ASCII位高与224，
            $returnstr=$returnstr.substr($sourcestr,$i,3); //根据UTF-8编码规范，将3个连续的字符计为单个字符
            $i=$i+3;//实际Byte计为3
            $n++;//字串长度计1
        }elseif($ascnum>=192){//如果ASCII位高与192，
            $returnstr=$returnstr.substr($sourcestr,$i,2); //根据UTF-8编码规范，将2个连续的字符计为单个字符
            $i=$i+2;//实际Byte计为2
            $n++;//字串长度计1
        }elseif ($ascnum>=65 && $ascnum<=90){//如果是大写字母，
            $returnstr=$returnstr.substr($sourcestr,$i,1);
            $i=$i+1;//实际的Byte数仍计1个
            $n++;//但考虑整体美观，大写字母计成一个高位字符
        }else{//其他情况下，包括小写字母和半角标点符号，
            $returnstr=$returnstr.substr($sourcestr,$i,1);
            $i=$i+1;//实际的Byte数计1个
            $n=$n+0.5;//小写字母和半角标点等与半个高位字符宽...
        }
    }
    if ($str_length/3>$cutlength){
        $returnstr = $returnstr . $suffix;//超过长度时在尾处加上省略号
    }
    return $returnstr;
}
/**
 * 传递数据以易于阅读的样式格式化后输出
 */
function p($data){
    // 定义样式
    $str='<pre style="display: block;padding: 9.5px;margin: 44px 0 0 0;font-size: 13px;line-height: 1.42857;color: #333;word-break: break-all;word-wrap: break-word;background-color: #F5F5F5;border: 1px solid #CCC;border-radius: 4px;">';
    // 如果是boolean或者null直接显示文字；否则print
    if (is_bool($data)) {
        $show_data=$data ? 'true' : 'false';
    }elseif (is_null($data)) {
        $show_data='null';
    }else{
        $show_data=print_r($data,true);
    }
    $str.=$show_data;
    $str.='</pre>';
    echo $str;
}

function change_to_quotes($str){
    return sprintf("'%s'", $str);
}