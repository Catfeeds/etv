<?php
// +------------------------------------------------------------------------------------------------------------
// | Author:Blues
// +------------------------------------------------------------------------------------------------------------
// | Last Update Time:2016-01-07
// +------------------------------------------------------------------------------------------------------------
// | HttpRequest示例1 http://siteName:port/projectName/api.php/Api/interfaceName?param1=value1&paramN=valueN
// | HttpRequest示例2 http://siteName:port/projectName/api.php?s=Api/interfaceName&param1=value1&paramN=valueN
// | HttpRequest示例3【推荐】 http://siteName:port/projectName/api.php?m=Api&a=interfaceName&param1=value1&paramN=valueN
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
class ApiController extends Controller{

    static protected $serverUrl = 'http://61.143.52.102:9090/etv/Public';
    // static protected $serverUrl = 'http://125.88.254.149/etv/Public';
    // static protected $serverUrl = 'http://localhost/etv/Public';
     
    public function _empty(){
        $data = ['code'=>10002, 'message'=>'请检查请求地址是否正确'];
        echo json_encode($data);
    } 
    
    /**
     * @interfaceName getSkinPath
     * @uses 获取酒店皮肤包
     * @param hid 酒店编号
     * @param room 房间号
     * @param mac Mac地址
     * @var json
     * @return
     *     status 状态  （200成功    404失败）
     *     info 发送状态说明
     *     name 皮肤包名
     *     version 皮肤包版本号
     *     md5 文件MD5值
     *     path 皮肤包路径
     */
    public function getSkinPath(){
        $json = array();
        $hid = strtoupper(I('request.hid'));
        $room = I('request.room');
        $mac = I('request.mac');

        if (empty($hid)) {
            $this->errorCallback(404, "Error: hid param is needed!");
        }
        if (empty($room)) {
            $this->errorCallback(404, "Error: room param is needed!");
        }
        if (empty($mac)) {
            $this->errorCallback(404, "Error: mac param is needed!");
        }
        $Hotel = D("Hotel");
        $Skin = D("HotelSkin");
        $voHotel=$Hotel->getByHid($hid);
        $voHS = $Skin->getById($voHotel['skinid']);
        if (empty($voHS) || $voHS['status']<>1){
            $this->errorCallback(404, "Error:The skin is empty or is locked!");
        }
        $filename = $voHS['filename'];
        $json['status'] = 200;
        $json['info'] = "Success,The skin is existed!";
        $json['name'] = $voHS['name'];
        $json['version'] = $voHS['version'];
        $json['md5'] = $voHS['md5_file'];
        $json['path'] = "/Public".$filename;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json);
    }

    /**
     * @interfaceName getWelcomeImg
     * @uses 获取欢迎背景图
     * @param hid 酒店编号
     * @param room 房间号
     * @param mac Mac地址
     * @var json
     * @return
     *     status 状态  （200成功    404失败）
     *     info 发送状态说明
     *     logoUrl Logo地址
     */
    public function getWelcomeImg(){
        $json = array();
        $hid = strtoupper(I('request.hid'));
        $room = I('request.room');
        $mac = I('request.mac');
        if (empty($hid)) {
            $this->errorCallback(404, "Error: hid param is needed!");
        }
        if (empty($room)) {
            $this->errorCallback(404, "Error: room param is needed!");
        }
        if (empty($mac)) {
            $this->errorCallback(404, "Error: mac param is needed!");
        }
        $HotelLogo = D("HotelWelcome");
        $map['zxt_hotel_welcome.hid'] = $hid;
        $map['zxt_hotel_resource.status'] = 1;
        $map['zxt_hotel_resource.audit_status'] = 4;
        $foo = $HotelLogo->field('zxt_hotel_resource.audit_status,zxt_hotel_resource.status,zxt_hotel_resource.filepath')->where($map)->join('zxt_hotel_resource ON zxt_hotel_welcome.resourceid = zxt_hotel_resource.id')->select();
        if(empty($foo)){
            $thepid = $this->getphid($hid);
            if($thepid){
                $map['zxt_hotel_welcome.hid'] = $thepid['hid'];
                $foo = $HotelLogo->field('zxt_hotel_resource.audit_status,zxt_hotel_resource.status,zxt_hotel_resource.filepath')->where($map)->join('zxt_hotel_resource ON zxt_hotel_welcome.resourceid = zxt_hotel_resource.id')->select();
                if(empty($foo)){
                    $this->errorCallback(404, "Error: the welcome image info is not existed!");
                }
            }else{
                $this->errorCallback(404, "Error: the welcome image info is not existed!");
            }
        }
        $json['status'] = 200;
        $json['info'] = "Successed!";
        $json['logoUrl']= self::$serverUrl.$foo[0]['filepath'];
        $json['getlogoUrl'] = "/Public".$foo[0]['filepath'];
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json);
    }
    
    /**
     * @interfaceName getLanguageList
     * @uses 获取语言列表
     * @param hid 酒店编号
     * @param room 房间号
     * @param mac Mac地址
     * @var json
     * @return
     *     status 状态  （200成功    404失败）
     *     info 发送状态说明
     *     list 语言信息
     *     -name 显示名
     *     -langcode 语言标识
     *     -appellation 称谓
     *     -content 欢迎辞
     *     -signer 落款
     */
    public function getLanguageList() {
        $json = array();
        $hid = strtoupper(I('request.hid'));
        $room = I('request.room');
        $mac = I('request.mac');
        if (empty($hid)) {
            $this->errorCallback(404, "Error: hid param is needed!");
        }
        if (empty($room)) {
            $this->errorCallback(404, "Error: room param is needed!");
        }
        if (empty($mac)) {
            $this->errorCallback(404, "Error: mac param is needed!");
        }
        $Lang=D("HotelLanguage");
        $Languagecode=D("langcode");
        $list=$Lang->where('hid="'.$hid.'" and status =1')->order("sort asc")->select();
        if (empty($list)) {
            $thepid = $this->getphid($hid);
            if ($thepid) {
                $list=$Lang->where('hid="'.$thepid['hid'].'" and status =1')->order("sort asc")->select();
                if (empty($list)) {
                    $this->errorCallback(404, "Error: the language list is empty!");
                }
            }else{
                $this->errorCallback(404, "Error: the language list is empty!");
            }
        }
        $volist=array();
        foreach ($list as $key => $value) {
            $volist[$key]['name']=$value['name'];
            $vo=array();
            $vo=$Languagecode->getById($value['langcodeid']);
            $volist[$key]['langcode']=$vo['code'];
            $volist[$key]['appellation']=$value['appellation'];
            $volist[$key]['content']=$value['content'];
            $volist[$key]['signer']=$value['signer'];
        }
        $json['status'] = 200;
        $json['info'] = "Successed!";
        $json['list'] = $volist;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json);
    }

    /**
     * http request:/api.php/Api/getWeather
     * @uses 获取天气信息
     * @param hid 酒店编号
     * @param room 房间号
     * @param mac Mac地址
     * @var json
     * @return
    */
    public function getWeather(){
        $hid = strtoupper(I('request.hid'));
        if (empty($hid)) {
            $this->errorCallback(404, "Error: hid param is needed!");
        }

        $regioninfo=D("Hotel")->where('zxt_hotel.hid="'.$hid.'"')->field('zxt_region.name,zxt_region.code')->join('zxt_region on zxt_hotel.cityid = zxt_region.id')->find();
        if (empty($regioninfo)) {
            $this->errorCallback(404, "Error: the regioninfo is error!");
        }

        //直接取数据库当日天气
        $where_weather['code_id'] = $regioninfo['code'];
        $where_weather['date'] = date("Y-m-d");
        $hadweather = D('weatherinfo')->where($where_weather)->find();
        if (!empty($hadweather)) {
            $data['city'] = $hadweather['city'];
            $data['image'] = '/Public'.$hadweather['image'];
            $data['get_image'] = self::$serverUrl.$hadweather['image'];
            $data['low'] = $hadweather['low'];
            $data['high'] = $hadweather['high'];
            $data['description'] = $hadweather['description'];
            $json['status'] = 200;
            $json['info'] = "Successed!";
            $json['weatherInfo'] = $data;
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($json);
            die();
        }

        // 心知天气接口调用凭据
        $key = 'c9fhn149mbtychgh'; // 测试用 key，请更换成您自己的 Key
        $uid = 'U810D587DB'; // 测试用 用户 ID，请更换成您自己的用户 ID
        // 参数
        $api = 'https://api.seniverse.com/v3/weather/daily.json'; // 接口地址
        $location = $regioninfo['name']; // 城市名称。除拼音外，还可以使用 v3 id、汉语等形式
        // 生成签名。文档：https://www.seniverse.com/doc#sign
        $param = [
            'ts' => time(),
            'ttl' => 300,
            'uid' => $uid,
        ];
        $sig_data = http_build_query($param); // http_build_query 会自动进行 url 编码
        // 使用 HMAC-SHA1 方式，以 API 密钥（key）对上一步生成的参数字符串（raw）进行加密，然后 base64 编码
        $sig = base64_encode(hash_hmac('sha1', $sig_data, $key, TRUE));
        // 拼接 url 中的 get 参数。文档：https://www.seniverse.com/doc#daily
        $param['sig'] = $sig; // 签名
        $param['location'] = $location;
        $param['start'] = 0; // 开始日期。0 = 今天天气
        $param['days'] = 1; // 查询天数，1 = 只查一天
        // 构造url
        $url = $api . '?' . http_build_query($param);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // 终止从服务端进行验证
        // 如需设置为 TRUE，建议参考如下解决方案：
        // https://stackoverflow.com/questions/18971983/curl-requires-curlopt-ssl-verifypeer-false
        // https://stackoverflow.com/questions/6324391/php-curl-setoptch-curlopt-ssl-verifypeer-false-too-slow
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $output=curl_exec($ch);
        curl_close($ch);
        $return = json_decode($output,true);
        if (!empty($return['results'])) {
            $data['city'] = $return['results']['0']['location']['name'];
            $data['low'] = $return['results']['0']['daily']['0']['low'];
            $data['high'] = $return['results']['0']['daily']['0']['high'];
            $data['description'] = $return['results']['0']['daily']['0']['wind_direction'];
        }else{
            $this->errorCallback(404, "Error: getWeather is error!");
        }

        $url2 = "http://www.weather.com.cn/data/cityinfo/".$regioninfo['code'].".html";
        //中国天气网天气接口
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url2);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 1);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //执行命令
        $curl_return = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        list($header, $body) = explode("\r\n\r\n", $curl_return, 2);
        $chinaweatherinfo = json_decode($body, true);
        if (!empty($chinaweatherinfo)) {
            $imagename_arr = explode(".", $chinaweatherinfo['weatherinfo']['img2']);
            $data['image'] = '/Public/weather/weather_image/'.$imagename_arr['0'].'.png';
            $data['get_image'] = self::$serverUrl.'/weather/weather_image/'.$imagename_arr['0'].'.png';
        }

        $json['status'] = 200;
        $json['info'] = "Successed!";
        $json['weatherInfo'] = $data;
        header('Content-Type: application/json; charset=utf-8');

        //插入数据库
        $data['date'] = date("Y-m-d");
        $data['code_id'] = $regioninfo['code'];
        unset($data['get_image']);
        if (!empty($chinaweatherinfo)) {
            $data['image'] = '/weather/weather_image/'.$imagename_arr['0'].'.png';
        }else{
            $data['image'] = '';
        }
        D("weatherinfo")->data($data)->add();
        echo json_encode($json);
    }
     
    /**
     * http request:/api.php/Api/getMessage
     * @author Blues
     * @uses 获取跑马灯
     * @param hid 酒店编号
     * @param room 房间号
     * @param mac Mac地址
     * @var json
     * @return
     *     status 状态  （200成功    404失败）
     *     info 发送状态说明
     *     msgList 消息列表
     *     -content 内容
     *     -start_time 开始时间
     *     -end_time 结束时间
     */
    public function getMessage(){
        $json = array();
        $hid = strtoupper(I('request.hid'));
        $room = I('request.room');
        $mac = I('request.mac');
        if (empty($hid)) {
            $this->errorCallback(404, "Error: hid param is needed!");
        }
        if (empty($room)) {
            $this->errorCallback(404, "Error: room param is needed!");
        }
        if (empty($mac)) {
            $this->errorCallback(404, "Error: mac param is needed!");
        }
        $HotelMessage = D("HotelMessage");
        $Message = D("Message");
        $hlist = $HotelMessage->where('status = 1 and (hidlist = "all" or hidlist like "'."%".$hid."%".'")')->select();
        $list = $Message->where('status = 1 and hid ="'.$hid.'" and (maclist = "all" or maclist like "'."%".$mac."%".'")')->select();
        if (empty($hlist) && empty($list)) {
            $this->errorCallback(404, "Error: no message!");
        }
        $json['status'] = 200;
        $json['info'] = "Successed!";
        $i=0;
        if(!empty($list)){
            foreach ($list as $key => $value) {
                $volist[$i]['content']=$value['content'];
                $volist[$i]['start_time']=$value['starttime'];
                $volist[$i]['end_time']=$value['endtime'];
                $i++;
            }
        }else{
            foreach ($hlist as $key => $value) {
                $volist[$i]['content']=$value['content'];
                $volist[$i]['start_time']=$value['starttime'];
                $volist[$i]['end_time']=$value['endtime'];
                $i++;
            }
        }
        $json['msgList'] = $volist;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json);
    }

    /**
     * http request:/api.php/Api/getBaseSet
     * @author Blues
     * @uses 获取酒店基本设置：包括是否跳转,跳转时间,是否插播跳转视频，跳转视频地址
     * @param hid 酒店编号
     * @param room 房间号
     * @param mac Mac地址
     * @var json
     * @return
     *     status 状态  （200成功   404失败）
     *     info 发送状态说明
     *     isjump 是否跳转 :0不跳1跳转
     *     staytime 停留时间：不跳时值为-1
     *     hasvideo 是否插播跳转视频:0不插播，1插播
     *     video 跳转视频地址
     *     修改  现已没有hotelset表  查询hotel_jump表 2017-3-23
     */
    public function getBaseSet(){
        $hid = strtoupper(I('request.hid'));
        $room = I('request.room');
        $mac = I('request.mac');
        if (empty($hid)) {
            $this->errorCallback(404, "Error: hid param is needed!");
        }
        if (empty($room)) {
            $this->errorCallback(404, "Error: room param is needed!");
        }
        if (empty($mac)) {
            $this->errorCallback(404, "Error: mac param is needed!");
        }
        $field = "isjump,staytime,video_set as hasvideo";
        $setvo = D("hotel_jump")->where(array('hid'=>$hid))->field($field)->find();
        if (!empty($setvo)) {
            if($setvo['hasvideo']>0){
                $whereresource['hid'] = $hid;
                $whereresource['status'] = 1;
                $whereresource['audit_status'] = 4;
                $resourcefield = "filepath";
                $filepathvo = D("hotel_jump_resource")->where($whereresource)->order('sort')->field($resourcefield)->find();
                $json = $setvo;
                if (!empty($filepathvo)) {
                    $json['video'] = self::$serverUrl.$filepathvo['filepath'];
                    $json['getvideo'] = "/Public".$filepathvo['filepath'];
                }else{
                    $json['video'] = '';
                    $json['getvideo'] = '';
                }

            }else{
                $json['video'] = '';
                $json['getvideo'] = '';
            }
        }
        $json['status'] = 200;
        $json['info'] = "Successed!";
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json);
    }

    /**
     * 酒店视频跳转设置 
     * 可含有多个视频
     * 视频资源存在hotel_jump_resource表中
     */
    public function hoteljumpset(){
        $hid = I('request.hid','','strip_tags');
        if (empty($hid)) {
            $this->errorCallback(404, "Error: hid param is needed!");
        }
        $wherehid['hid'] = $whereresource['hid'] = $hid;
        $setvo = D("hotel_jump")->where($wherehid)->find();
        $whereresource['status'] = 1;
        $whereresource['audit_status'] = 4;
        $resourcelist = D('hotel_jump_resource')->where($whereresource)->field('filepath')->order('sort')->select();
        if (!empty($setvo)) {
            $data['set'] = $setvo;
            if (!empty($resourcelist)) {
                foreach ($resourcelist as $key => $value) {
                    $data['filepath'][] = '/Public'.$value['filepath'];
                    $data['get_filepath'][] = self::$serverUrl.$value['filepath'];
                }
            }else{
                $data['filepath'] = [];
                $data['get_filepath'] = [];
            }
            $json['status'] = 200;
            $json['msg'] = "Successed!";
            $json['data'] = $data;
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($json);
        }else{
            $this->errorCallback(404, "the hoteljump set is empty"); 
        }
    }

    /**
     * http request:/api.php/Api/getMainleft
     * @author Blues
     * @uses 获取主页左侧内容：一个或多个视频（图片）
     * @param hid 酒店编号
     * @param room 房间号
     * @param mac Mac地址
     * @var json
     * @return
     *     status 状态  （200成功    404失败）
     *     info 发送状态说明
     *     type 左侧内容类型  1视频  0图片
     *     volist 内容列表
     *     --title 标题
     *     --video 视频路径（type=1返回）
     *     --image 图片路径（type=0返回）
     *     
     */
    public function getMainleft(){
        $json = array();
        $hid = I('request.hid','','strtoupper');
        if (empty($hid)) {
            $this->errorCallback(404, "Error: hid param is needed!");
        }
        $HotelSpread = D("hotel_spread");
        $prefix = C('DB_PREFIX');
        $map["zxt_hotel_spread.hid"] = $hid;
        $map["zxt_hotel_resource.status"] = 1;
        $map["zxt_hotel_resource.audit_status"] = 4;
        $list = $HotelSpread->field("zxt_hotel_resource.*")->join("zxt_hotel_resource ON zxt_hotel_spread.resourceid=zxt_hotel_resource.id")->where($map)->order('zxt_hotel_resource.sort')->find();
        if(empty($list)){
            $thepid = $this->getphid($hid);
            if ($thepid) {
                $map["zxt_hotel_spread.hid"] = $thepid['hid'];
                $list = $HotelSpread->field("zxt_hotel_resource.*")->join("zxt_hotel_resource ON zxt_hotel_spread.resourceid=zxt_hotel_resource.id")->where($map)->order('zxt_hotel_resource.sort')->find();
                if (empty($list)) {
                    $this->errorCallback(404, "Error: the hotel base setting is empty!");
                }
            }else{
                $this->errorCallback(404, "Error: the hotel base setting is empty!");
            }
        }
        $json['status'] = 200;
        $json['info'] = "Successed!";
        $json['type'] = $list['type'];
        $pp[0]['title'] = $list['title'];//兼容过去写法
        if($list['type']==1){    
            $pp[0]['video'] = self::$serverUrl.$list['filepath'];
            $pp[0]['getvideo'] = '/Public'.$list['filepath'];
        }else{
            $pp[0]['image'] = self::$serverUrl.$list['filepath'];
            $pp[0]['getimage'] = '/Public'.$list['filepath'];
        }
        $json['volist'] = $pp;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json); 
        die();
    }

    /**
     * http request:/api.php/Api/getMainAd
     * @author Blues
     * @uses 获取主页右侧广告
     * @param hid 酒店编号
     * @param room 房间号
     * @param mac Mac地址
     * @var json
     * @return
     *     status 状态  （200成功    404失败）
     *     info 发送状态说明
     *     image 广告图片路径
     */
    public function getMainAd(){
        $json = array();
        $hid = strtoupper(I('request.hid'));
        
        if (empty($hid)) {
            $this->errorCallback(404, "Error: hid param is needed!");
        }
        $bindlist = D('ad_bind')->where('hid='.$hid)->field('ad_id')->select();
        if (!empty($bindlist)) {
            $map['id'] = array('in', $bindlist['0']['ad_id']);
            $map['status'] = 1;
            $map['audit_status'] = 4;
            $list = D('ad')->where($map)->field('filepath')->select();
            if (empty($list)) {
                $this->errorCallback(404, "Error:ad is not authorized to the hotel, or the audit ad image is empty!");
            }
            $json['status'] = 200;
            $json['info'] = "Successed!";
            $json['image']=self::$serverUrl.$list[0]['filepath'];
            $json['getimage']="/Public".$list[0]['filepath'];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($json);
        }else{
            $this->errorCallback(404, "Error:ad is not authorized to the hotel, or the audit ad image is empty!");
        }
    }

    /**
     * http request:/api.php/Api/getMenu
     * @version 2015-11-10并入集团、专题栏目，集团栏目最前、专题最后
     * @uses 获取栏目菜单
     * @param hid 酒店编号
     * @param room 房间号
     * @param mac Mac地址
     * @param langcode 语言标识
     * @var json
     * @return
     *     status 状态  （200成功    404失败）
     *     info 发送状态说明
     *     menuList 菜单列表
     *     --mainMenuId 菜单ID
     *     --modeldefineid 菜单模型
     *     --name 菜单名称
     *     --packageName 包名
     *     --className 类名
     *     --type 类型（groupmenu、hotelmenu、topicmenu）
     *     --submenuList 子菜单列表
     *       --subMenuId 子菜单ID
     *       --modeldefineid 菜单类型
     *       --name 子菜单名称
     *       --packageName 包名
     *       --className 类名
     *       --type 类型
     */
    public function getMenu(){
        $json = array();
        $hid = strtoupper(I('request.hid'));
        $room = I('request.room');
        $mac = I('request.mac');
        if (empty($hid)) {
            $this->errorCallback(404, "Error: hid param is needed!");
        }
        if (empty($room)) {
            $this->errorCallback(404, "Error: room param is needed!");
        }
        if (empty($mac)) {
            $this->errorCallback(404, "Error: mac param is needed!");
        }
        $code = empty($_REQUEST['langcode'])?"zh_CN":$_REQUEST['langcode'];
        $Menu=D("HotelCategory");
        $HotelResource = D("HotelResource");
        $Language = D("HotelLanguage");
        $Languagecode = D("Langcode");
        $Modeldefine = D("Modeldefine");
        $HotelConfig = D("HotelTopic");
        $TopicGroup = D("TopicGroup");
        $TopicCategory = D("TopicCategory");
        $vo=$Languagecode->getByCode($code);

        $volist=array();
        $i=0;
        //集团菜单
        $groupMenu=array();
        $Hotel=D("Hotel");
        $hotelinfo=$Hotel->getByHid($hid);
        $hotelgroupInfo=$Hotel->getById($hotelinfo['pid']);

        //酒店菜单
        $mainMenu=$Menu->where('hid="'.$hid.'" and status=1 and pid=0 and langcodeid="'.$vo['id'].'"')->order('sort asc')->select();
        if (!empty($mainMenu)) {
            foreach ($mainMenu as $valueH) {
                $sub=array();
                $sub=$Menu->where('hid="'.$hid.'" and status=1 and pid='.$valueH['id'].' and langcodeid="'.$vo['id'].'"')->order('sort asc')->select();
                $vosub=array();
                foreach ($sub as $kk=>$valH) {
                    $submd=array();
                    $submd=$Modeldefine->getById($valH['modeldefineid']);
                    // $submd=$Modeldefine->getById($valueH['modeldefineid']);
                    $vosub[$kk]['subMenuId']=$valH['id'];
                    $vosub[$kk]['modelDefineId']=$valH['modeldefineid'];
                    $vosub[$kk]['name']=$valH['name'];
                    $vosub[$kk]['packageName']=$submd['packagename'];
                    $vosub[$kk]['className']=$submd['classname'];
                    if($submd['codevalue'] == '501'){
                        $vosub[$kk]['type'] = 'videohotel';
                    }elseif($submd['codevalue'] == '505'){
                        $vosub[$kk]['type']="weburl";
                        $vosub[$kk]['weburl'] = $valH['weburl'];
                    }else{
                        $vosub[$kk]['type']="hotelmenu";
                    }
                }
                $md=array();
                $md=$Modeldefine->getById($valueH['modeldefineid']);
                $volist[$i]['mainMenuId']=$valueH['id'];
                $volist[$i]['modelDefineId']=$valueH['modeldefineid'];
                $volist[$i]['name']=$valueH['name'];
                $volist[$i]['packageName']=$md['packagename'];
                $volist[$i]['className']=$md['classname'];
                $volist[$i]['type']="hotelmenu";
                $volist[$i]['submenuList']=$vosub;
                $i++;
            }
        }

        //专题菜单
        $topicMenu=array();
        $config = $HotelConfig->where('hid="'.$hid.'"')->field('topic_id')->select();
        if (!empty($config)) {
            foreach ($config as $key => $value) {
                $topic_id_arr[] = $value['topic_id']; 
            }
            $map['id'] = array("in",$topic_id_arr);
            $topicMenu=$TopicGroup->where($map)->select();
            if (!empty($topicMenu)) {
                foreach ($topicMenu as $valueT) {
                    $subT=array();
                    $subT=$TopicCategory->where('status=1 and groupid='.$valueT['id'].' and langcodeid='.$vo['id'])->order('sort asc')->select();
                    $vosubT=array();
                    foreach ($subT as $sT=>$valT) {
                        $submdT=array();
                        $submdT=$Modeldefine->getById($valT['modeldefineid']);
                        $vosubT[$sT]['subMenuId']=$valT['id'];
                        $vosubT[$sT]['modelDefineId']=$valT['modeldefineid'];
                        $vosubT[$sT]['name']=$valT['name'];
                        $vosubT[$sT]['packageName']=$submdT['packagename'];
                        $vosubT[$sT]['className']=$submdT['classname'];
                        $vosubT[$sT]['type']="topicmenu";
                    }
                    $mdT=array();
                    $mdT=$Modeldefine->getByCodevalue("101");
                    $volist[$i]['mainMenuId']=$valueT['id'];
                    $volist[$i]['modelDefineId']="2";
                    if ($code=="zh_CN") {
                        $volist[$i]['name']=$valueT['name'];
                    }else{
                        $volist[$i]['name']=$valueT['en_name'];
                    }
                    $volist[$i]['packageName']=$mdT['packagename'];
                    $volist[$i]['className']=$mdT['classname'];
                    $volist[$i]['type']="topicmenu";
                    $volist[$i]['submenuList']=$vosubT;
                    $i++;
                }
            }
        }
        //酒店上级集团
        if ($hotelinfo['pid']!=0 && !empty($hotelgroupInfo)) {
            $groupHid=$hotelgroupInfo['hid'];
            $groupMenu=$Menu->where('hid="'.$groupHid.'" and status=1 and pid=0 and modeldefineid=1 and langcodeid="'.$vo['id'].'"')->order('sort asc')->select();//只查询模型为集团类型

            if (!empty($groupMenu)) {//category
                foreach ($groupMenu as $value) {//父category
                    $subGroup=array();
                    $subGroup=$Menu->where('hid="'.$groupHid.'" and status=1 and pid='.$value['id'].' and langcodeid="'.$vo['id'].'"')->order('sort asc')->select();//子category
                    $vosubGroup=array();
                    foreach ($subGroup as $k=>$val) {
                        $submdGroup=array();
                        $submdGroup=$Modeldefine->getById($val['modeldefineid']);
                        $vosubGroup[$k]['subMenuId']=$val['id'];
                        $vosubGroup[$k]['modelDefineId']=$val['modeldefineid'];
                        $vosubGroup[$k]['name']=$val['name'];
                        $vosubGroup[$k]['packageName']=$submdGroup['packagename'];
                        $vosubGroup[$k]['className']=$submdGroup['classname'];
                        $vosubGroup[$k]['type']="groupmenu";
                    }
                    $mdGroup=array();
                    $mdGroup=$Modeldefine->getById($value['modeldefineid']);
                    $volist[$i]['mainMenuId']=$value['id'];
                    $volist[$i]['modelDefineId']=$value['modeldefineid'];
                    $volist[$i]['name']=$value['name'];
                    $volist[$i]['packageName']=$mdGroup['packagename'];
                    $volist[$i]['className']=$mdGroup['classname'];
                    $volist[$i]['type']="groupmenu";
                    $volist[$i]['submenuList']=$vosubGroup;//子->父
                    $i++;
                }
            }

            //新增 查询子酒店关联集团的栏目
            $chgList = D("hotel_chglist")->where('hid="'.$hid.'"')->field('chg_cid')->select();
            if(!empty($chgList)){
                foreach ($chgList as $key => $value) {
                    $chg_id_arr[] = $value['chg_cid']; 
                }
                $chgCategoryMap['id'] = array('in',$chg_id_arr);
                $chgCategoryMap['status'] = 1;
                $categorylist = D("hotel_chg_category")->where($chgCategoryMap)->field('id,name,modeldefineid')->select();
                if(!empty($categorylist)){
                    foreach ($categorylist as $key2 => $value2) { //一级栏目
                        $mdGroup=$Modeldefine->where('id="'.$value2["modeldefineid"].'"')->field('packagename,classname')->find();
                        $categorylist_second = D("hotel_chg_category")->where('pid='.$value2['id'].' and status=1')->field('id,name,modeldefineid,weburl')->select();
                        $vosubGroup = array();
                        if(!empty($categorylist_second)){
                            foreach ($categorylist_second as $k=>$val) {
                                $submdGroup=$Modeldefine->where('id="'.$val["modeldefineid"].'"')->field('codevalue,packagename,classname')->find();
                                $vosubGroup[$k]['subMenuId']=$val['id'];
                                $vosubGroup[$k]['modelDefineId']=$val['modeldefineid'];
                                $vosubGroup[$k]['name']=$val['name'];
                                $vosubGroup[$k]['packageName']=$submdGroup['packagename'];
                                $vosubGroup[$k]['className']=$submdGroup['classname'];
                                if($submdGroup['codevalue']=='501'){
                                    $vosubGroup[$k]['type'] = "videochg";
                                }elseif($submdGroup['codevalue']=='505'){
                                    $vosubGroup[$k]['type']="weburl";
                                    $vosubGroup[$k]['weburl'] = $val['weburl'];
                                }else{
                                    $vosubGroup[$k]['type']="chgmenu";
                                }
                            }    
                        }
                        $volist[$i]["mainMenuId"] = $value2['id'];
                        $volist[$i]["modelDefineId"] = $value2['modeldefineid'];
                        $volist[$i]["name"] = $value2['name'];
                        $volist[$i]['packageName']=$mdGroup['packagename'];
                        $volist[$i]['className']=$mdGroup['classname'];
                        $volist[$i]["type"] = 'chgmenu';
                        $volist[$i]["submenuList"] = $vosubGroup;
                        $i++;
                    }
                }
            }
        }
        if (empty($volist)) {
            $this->errorCallback(404, "Error: the menu is empty!");
        }
        $json['status'] = 200;
        $json['info'] = "Successed!";
        $json['menuList']=$volist;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json);
    }

    /**
     * http request:/api.php/Api/getContent
     * @author Blues
     * @uses 获取二级菜单内容
     * @param hid 酒店编号
     * @param room 房间号
     * @param mac Mac地址
     * @param subMenuId 子菜单ID
     * @param type 菜单类型（groupmenu、hotelmenu、topicmenu）
     * @var json
     * @return
     *     status 状态  （200成功    404失败）
     *     info 发送状态说明
     *     type 资源类型  picture图片video视频
     *     resourceList 资源列表
     *     --title 标题
     *     --video 视频 （资源类型为视频时有效）
     *     --image 图片（资源类型为图片时有效）
     *     --intro 描述
     *     --price 价格（参数type="topicmenu"无效）
     *     --qrcode 二维码（参数type="topicmenu"无效）
     */
    public function getContent(){
        $json = array();
        $hid = strtoupper(I('request.hid'));
        $room = I('request.room');
        $mac = I('request.mac');
        if (empty($hid)) {
            $this->errorCallback(404, "Error: hid param is needed!");
        }
        if (empty($room)) {
            $this->errorCallback(404, "Error: room param is needed!");
        }
        if (empty($mac)) {
            $this->errorCallback(404, "Error: mac param is needed!");
        }
        $subMenuId=empty($_REQUEST['subMenuId'])?0:$_REQUEST["subMenuId"];
        if (empty($subMenuId) || $subMenuId==0) {
            $this->errorCallback(404, "Error: the subMenuId param is needed!");
        }
        $menutype=empty($_REQUEST['type'])?"":$_REQUEST["type"];
        if (empty($menutype)) {
            $this->errorCallback(404, "Error: type param is needed!");
        }
        $Modeldefine = D("Modeldefine");

        $resourceList=array();
        $key=0;
        if ($menutype=="topicmenu") {
            //专题栏目的资源
            $Menu=D("TopicCategory");
            $Resource=D("TopicResource");
            $vo=$Menu->getById($subMenuId);
            $fo=$Modeldefine->getById($vo['modeldefineid']);
            if ($fo['codevalue']=='102') {
                $restype = 2;//图片
            }else if ($fo['codevalue']=='103') {
                $restype = 1;//视频
            }else{
                $this->errorCallback(404, "Error: Menudefined Error!");
            }
            $resList=$Resource->where('status=1 and audit_status=4 and cid='.$subMenuId)->order('sort asc')->select();
            foreach ($resList as $value) {
                if ($restype==$value['type']) {//菜单与资源类型相同
                    $resourceList[$key]['title']=$value['title'];
                    if ($restype==1) {
                        $resourceList[$key]['video']=self::$serverUrl.$value['video'];
                        $resourceList[$key]['getvideo']="/Public".$value['video'];
                        $type = 'video';
                    }else if($restype==2){
                        $resourceList[$key]['image']=self::$serverUrl.$value['image'];
                        $resourceList[$key]['getimage']="/Public".$value['image'];
                        $type = 'picture';
                    }
                    $resourceList[$key]['intro']=$value['intro'];
                    $resourceList[$key]['price']=0;
                    $resourceList[$key]['qrcode']="";
                    $resourceList[$key]['getqrcode']="";
                    $resourceList[$key]['loadpath'] = "local";
                    $key++;
                }
            }
        }elseif($menutype=="chgmenu"){ //新增 查询集团通用栏目资源
            $category_vo = D("hotel_chg_category")->where('id="'.$subMenuId.'"')->field('id,pid,modeldefineid')->find();
            if($category_vo['pid']>0){
                $chg_cid = $category_vo['pid'];
            }else{
                $chg_cid = $category_vo['id'];
            }
            $chglist_vo = D("hotel_chglist")->where('hid="'.$hid.'" and chg_cid="'.$chg_cid.'"')->field('phid')->find();
            if($category_vo['modeldefineid']==3){
                $resourcetype = $type = "image";
                $getresourcetype = "getimage";
            }elseif($category_vo['modeldefineid'] ==4){
                $resourcetype = $type = "video";
                $getresourcetype = "getvideo";
            }
            $resList = D("hotel_chg_resource")->where('hid="'.$chglist_vo['phid'].'" and cid="'.$subMenuId.'" and status=1 and audit_status=4')->order('sort asc')->select();
            if(!empty($resList)){
                foreach ($resList as $value) {
                    $resourceList[$key]['title'] = $value['title'];
                    $resourceList[$key][$resourcetype] = self::$serverUrl.$value['filepath'];
                    $resourceList[$key][$getresourcetype] = '/Public'.$value['filepath'];
                    $resourceList[$key]['intro'] = $value['intro'];
                    $resourceList[$key]['price'] = $value['price'];
                    $resourceList[$key]['loadpath'] = "local";
                    if(empty($value['icon'])){
                        $resourceList[$key]['qrcode'] = '';
                        $resourceList[$key]['getqrcode'] = '';
                    }else{
                        $resourceList[$key]['qrcode']=self::$serverUrl.$value['qrcode'];
                        $resourceList[$key]['getqrcode']="/Public".$value['qrcode'];
                    }
                    $key++;
                }
            }else{
                $this->errorCallback(404, "Error: the contents is empty or not released!");
                die();
            }

        }elseif($menutype=="hotelmenu"){
            //酒店栏目、集团栏目的资源
            $Menu=D("HotelCategory");
            $Resource=D("HotelResource");
            if ($menutype=="groupmenu") {
                $Hotel=D("Hotel");
                $hotelinfo=$Hotel->getByHid($hid);
                $hotelgroupInfo=$Hotel->getById($hotelinfo['pid']);
                $resHid=$hotelgroupInfo['hid'];
            }else if ($menutype=="hotelmenu") {
                $resHid=$hid;
            }
            $vo=$Menu->getById($subMenuId);
            $fo=$Modeldefine->getById($vo['modeldefineid']);
            if ($fo['codevalue']=='102') {
                $type = 'picture';
            }else if ($fo['codevalue']=='103') {
                $type = 'video';
            }else{
                $this->errorCallback(404, "Error: Menudefined Error!");
            }
            $resList = $Resource->where('status=1 and audit_status=4 and hid="'.$resHid.'" and category_id="'.$subMenuId.'"')->order('sort asc')->select();
            if(empty($resList)){
                $this->errorCallback(404, "Error: the contents is empty or not released!");
                die();
            }
            foreach ($resList as $value) {
                $resourceList[$key]['title']=$value['title'];
                if($value['type']==2){//图片
                    $resourceList[$key]['image'] = self::$serverUrl.$value['filepath'];
                    $resourceList[$key]['getimage'] = '/Public'.$value['filepath'];
                }elseif($value['type']==1){//视频
                    $resourceList[$key]['video'] = self::$serverUrl.$value['filepath'];
                    $resourceList[$key]['getvideo'] = '/Public'.$value['filepath'];
                }
                $resourceList[$key]['intro']=$value['intro'];
                $resourceList[$key]['price']=$value['price'];
                if(empty($value['qrcode'])){
                    $resourceList[$key]['qrcode'] = '';
                    $resourceList[$key]['getqrcode'] = '';
                }else{
                    $resourceList[$key]['qrcode']=self::$serverUrl.$value['qrcode'];
                    $resourceList[$key]['getqrcode']="/Public".$value['qrcode'];
                }
                $key++;
            }
        }elseif($menutype=="videohotel" || $menutype =="videochg"){
            $type = 'video';
            $carouselMap['hid'] = $hid;
            if ($menutype == "videochg") {
                $hotelinfo = D("hotel")->where('hid="'.$hid.'"')->field('pid')->find();
                $photelinfo = D("hotel")->where('id='.$hotelinfo['pid'])->field('hid')->find();
                $carouselMap['hid'] = $photelinfo['hid'];    
            }
            $carouselMap['cid'] = $subMenuId;
            $carouselMap['ctype'] = $menutype;
            $carouselMap['audit_status'] = 4;
            $carouselMap['status'] = 1;
            $carouseField = "id,title,filepath,intro,video_image as qrcode";
            $resList = D("hotel_carousel_resource")->where($carouselMap)->field($carouseField)->select();
            foreach ($resList as $value) {
                $resourceList[$key]['title']=$value['title'];
                $resourceList[$key]['video'] = self::$serverUrl.$value['filepath'];
                $resourceList[$key]['getvideo'] = '/Public'.$value['filepath'];
                $resourceList[$key]['intro']=$value['intro'];
                if(empty($value['qrcode'])){
                    $resourceList[$key]['qrcode'] = '';
                    $resourceList[$key]['getqrcode'] = '';
                }else{
                    $resourceList[$key]['qrcode']=self::$serverUrl.$value['qrcode'];
                    $resourceList[$key]['getqrcode']="/Public".$value['qrcode'];
                }
                $key++;
            }
        }
        if (empty($resourceList)) {
            $this->errorCallback(404, "Error: the contents is empty or not released!");
        }
        $json['status'] = 200;
        $json['info'] = "Successed!";
        $json['type']=$type;
        $json['resourceList']=$resourceList;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json);
    }

    /**
     * [startup_weburl 获取定时 浏览器url弹窗]
     * @return [type] [description]
     */
    public function weburllist(){
        $hid =I('request.hid');
        if (empty($hid)) {
            $this->errorCallback(10000, "Error: hid param is needed!");
        }
        $hotelinfo = D("hotel")->where('hid="'.$hid.'"')->field('pid')->find();
        if ($hotelinfo['pid']>0) {
            $photelinfo = D("hotel")->where('id='.$hotelinfo['pid'])->field('hid')->find();
            $where['hid'] = array('in', array($hid, $photelinfo['hid']));
        }else{
            $where['hid'] = $hid;
        }
        $where['status'] = 1;
        $where['date'] = date("Y-m-d");
        $list = D("weburl")->where($where)->field("id,name,weburl,time")->select();
        if (!empty($list)) {
            $json['status'] = 200;
            $json['data'] = $list;
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($json);
        }else{
            $this->errorCallback(404, "thd list is empty");
        }
    }

    /**
     * http request:/api.php/Api/getUpgrade
     * @author Blues
     * @uses 获取系统软件升级信息
     * @param hid 酒店编号
     * @param room 房间号
     * @param mac Mac地址
     * @param cversion 当前版本号(时间戳)
     * @var json
     * @return
     *     status 状态  （200有升级包    404无升级包）
     *     info 发送状态说明
     *     url 升级包路径
     *     version 升级版本号
     *     md5文件MD5值
     */
    public function getUpgrade(){
        $Hid = strtoupper(I('request.hid'));
        $Mac = strtoupper(I('request.mac'));
        $Roomno = I('request.room');
        if (empty($Hid) || $Hid=="NULL") {
            $this->errorCallback(404, "Error: hid param is needed!");
        }
        if (empty($Roomno)|| $Roomno=="NULL") {
            $this->errorCallback(404, "Error: room param is needed!");
        }
        if (empty($Mac)|| $Mac=="NULL") {
            $this->errorCallback(404, "Error: mac param is needed!");
        }
        $currentVersion = I('request.cversion');
        $currentVersion_route = I('request.cversionroute');
        $currentVersion_app = I('request.cversionapp');
        if (empty($currentVersion)) {
            $this->errorCallback(404, "Error: cversion param is needed!");
        }
        $ip = $this->get_client_ip();
        $this->stbLog($Hid,$Roomno,$Mac,$currentVersion,'',4,$ip);//开机例询
        $upgrade = D('upgrade_system');
        $upgradeRoute = D('upgrade_route');
        $upgradeApp = D('upgrade_app');
        $normal_list = $upgrade->where('status = 1 and audit_status = 4 and (maclist = "all" or maclist like "'."%".$Mac."%".'")')->select();
        $normal_list_route = array();
        if(!empty($currentVersion_route)){
            $normal_list_route = $upgradeRoute->where('status = 1 and audit_status = 4 and (maclist = "all" or maclist like "'."%".$Mac."%".'") and version="'.$currentVersion_route.'"')->select();
        }
        $normal_list_app = array();
        if (!empty($currentVersion_app)) {
            $normal_list_app = $upgradeApp->where('status = 1 and audit_status = 4 and (maclist = "all" or maclist like "'."%".$Mac."%".'") and version="'.$currentVersion_app.'"')->select();
        }
        $k=0;
        $versionList = array();
        for ($i = 0; $i < count($normal_list); $i++) {
            // if ($normal_list[$i]['utc']>$currentVersion) {//大于当前版本号
            if ($normal_list[$i]['utc']!=$currentVersion) {//不等于当前版本号
                $versionList[$k] = $normal_list[$i]['utc'];
                $k++;
            }
        }
        if(empty($versionList[0]) && empty($normal_list_route) && empty($normal_list_app)){
            $this->errorCallback(404, "Error: no new upgrade package!");
        }
        $result = array();
        $result['status'] = 200;
        $result['info'] = "Successed!";
        if (!empty($versionList[0]) || $versionList[0] != '') {
            rsort($versionList);//降序排序
            $maxVersion=$versionList[0];//$maxVersion为最大
            $vomax = $upgrade->where('status = 1 and (maclist = "all" or maclist like "'."%".$Mac."%".'") and utc="'.$maxVersion.'"')->select();
            if (empty($vomax)) {
                $result['upgrade'] = array();
            }else{
                $result['upgrade']['url'] = self::$serverUrl.$vomax[0]['filename'];
                $result['upgrade']['version'] = $maxVersion;
                $result['upgrade']['md5_file'] = $vomax[0]['md5_file'];
            }
        }else{
            $result['upgrade'] = array();
        }
        if(!empty($normal_list_route[0])){
            $result['upgraderoute']['url'] = self::$serverUrl.$normal_list_route[0]['filename'];
            $result['upgraderoute']['version'] = $normal_list_route[0]['version'];
            $result['upgraderoute']['md5_file'] = $normal_list_route[0]['md5_file'];
        }else{
            $result['upgraderoute'] = array();
        }
        if(!empty($normal_list_app[0])){
            $result['upgradeapp']['url'] = self::$serverUrl.$normal_list_app[0]['filename'];
            $result['upgradeapp']['version'] = $normal_list_app[0]['version'];
            $result['upgradeapp']['md5_file'] = $normal_list_app[0]['md5_file'];
        }else{
            $result['upgradeapp'] = array();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
    }

    /**
     * http request:/api.php/Api/postUpgradeResult
     * @author Blues
     * @uses 软件升级结果上传
     * 传入参数
     * @param hid 酒店编号
     * @param room 房间号
     * @param mac Mac地址
     * @param cversion 当前版本号
     * @param uversion 升级版本号
     * @param status  升级状态   0：成功  1：校验失败   2：安装失败 3:下载安装包 4：开机例询
     * @return
     *     status 状态  （200成功   404失败）
     *     info 发送状态说明
     */
    public function postUpgradeResult(){
        $Hid = I('request.hid');
        $Roomno = I('request.room');
        $Mac = strtoupper(I('request.mac'));
        $Cversion = I('request.cversion');
        $Uversion = I('request.uversion');
        $Status = I('request.status');
        $Ip = $this->get_client_ip();
        if (empty($Hid) || empty($Mac)) {
            $this->errorCallback(404, "Error: hid and mac param is needed!");
        }
        if (is_numeric($Status) && $Status == 4) {
            $this->stbLog($Hid,$Roomno,$Mac,$Cversion,$Uversion,$Status,$Ip);
        }else{
            $this->upgradeLog($Hid,$Roomno,$Mac,$Cversion,$Uversion,$Status,$Ip);
        }

        $json['status'] = 200;
        $json['info'] = "Successed!";
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json);
    }

    //STB运行日志
    private function stbLog($hid="NULL",$roomno='NULL',$mac='NULL', $cversion='NULL',$uversion='NULL',$status=4,$ip='') {
        date_default_timezone_set('Asia/Shanghai');
        $stblogs = D('DeviceLog');
        $data = array();
        $data['hid'] = $hid;
        $data['room'] = $roomno;
        $data['mac'] = strtoupper($mac);
        $data['cversion'] = $cversion;
        $data['uversion'] = $uversion;
        $data['login_ip'] = $ip;
        $data['runtime'] = time();
        $data['status'] = $status;
        $result = $stblogs->add($data);
    }

    private function upgradeLog($hid="NULL",$roomno='NULL',$mac='NULL', $cversion='NULL',$uversion='NULL',$status=4,$ip=''){
        date_default_timezone_set('Asia/Shanghai');
        $upgradelogmodel = D('UpgradeLog');
        $data = array();
        $data['hid'] = $hid;
        $data['room'] = $roomno;
        $data['mac'] = strtoupper($mac);
        $data['cversion'] = $cversion;
        $data['uversion'] = $uversion;
        $data['msg'] = '系统升级';
        $data['login_ip'] = $ip;
        $data['runtime'] = time();
        $data['status'] = $status;
        $result = $upgradelogmodel->add($data);
    }

    /**
     * http request:/api.php/Api/getwifiinfo
     * @author Blues
     * @uses 获取设备wifi热点信息
     * @param hid 酒店编号
     * @param room 房间号
     * @param mac Mac地址
     * @var json
     * @return
     *     status 状态  （200成功    404失败）
     *     info 发送状态说明
     *     wifi_ssid
     *     wifi_passwd
     */
    public function getwifiinfo(){
        $json = array();
        $hid = empty($_REQUEST['hid'])?"":strtoupper($_REQUEST["hid"]);
        $room = empty($_REQUEST['room'])?"":$_REQUEST["room"];
        $mac = empty($_REQUEST['mac'])?"":$_REQUEST["mac"];
        if (empty($hid)) {
            $this->errorCallback(10000, "Error: hid param is needed");
        }
        if (empty($room)) {
            $this->errorCallback(10000, "Error: room param is needed");
        }
        $DeviceWifi = D("device");
        $map['hid']=$hid;
        $map['room']=$room;
        $vo=$DeviceWifi->where($map)->field('wifi_ssid,wifi_passwd,wifi_status')->find();
        if (empty($vo)) {
            $this->errorCallback(404, "Error:no wifi info or wifi is closed!");
        }else{
            $json['status'] = 200;
            $json['info'] = "Successed!";
            $json['wifiinfo']=$vo;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json);
    }

    /**
     * @interfaceName getBlackApp
     * @uses 获取终端不显示的APP列表
     * @param hid 酒店编号
     * @param room 房间号
     * @param mac Mac地址
     * @var json
     * @return
     *     status 状态  （200成功    404失败）
     *     info 发送状态说明
     *     list APP列表
     *     --name 名称
     *     --packagename 包名
     */
    public function getBlackApp(){
        $json = array();
        $hid = empty($_REQUEST['hid'])?"":strtoupper($_REQUEST["hid"]);
        $room = empty($_REQUEST['room'])?"":$_REQUEST["room"];
        $mac = empty($_REQUEST['mac'])?"":$_REQUEST["mac"];
        if (empty($hid)) {
            $this->errorCallback(404, "Error: hid param is needed!");
        }
        if (empty($room)) {
            $this->errorCallback(404, "Error: room param is needed!");
        }
        if (empty($mac)) {
            $this->errorCallback(404, "Error: mac param is needed!");
        }
        $BlackApp = D("Blackapp");
        $list = $BlackApp->where('status=0')->field("name,packagename")->select();
        if (empty($list)){
            $this->errorCallback(404, "Error:The black app list is empty!");
        }
        $json['status'] = 200;
        $json['info'] = "Success,The black app list is existed!";
        $json['list'] = $list;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json);
    }

    private function object_array($array) {
        if(is_object($array)) {
            $array = (array)$array;
        }
        if(is_array($array)) {
            foreach($array as $key=>$value){
                $array[$key] = $this->object_array($value);
            }
        }
        return $array;
    }

    /**
     * [获取客户端IP]
     * @return [string] $ip [IP]
     */
    private function get_client_ip(){
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
        $ip = getenv("HTTP_CLIENT_IP");
        else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
        $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
        $ip = getenv("REMOTE_ADDR");
        else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
        $ip = $_SERVER['REMOTE_ADDR'];
        else
        $ip = "unknown";
        return($ip);
    }

    /**
     * [错误方法调用]
     */
    private function errorCallback($status,$info){
        $data['status'] = $status;
        $data['info'] = $info;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /**
     * [根据hid获取地址代码]
     * @param  [type] $hid [description]
     * @return [type]      [description]
     */
    private function getCityName($hid){
        $Hotel=D("Hotel");
        $voHotel=$Hotel->getByHid($hid);
        $cityID=$voHotel['cityid'];
        $Region=D("Region");
        $c=$Region->getById($cityID);
        return $c['name'];
    }

    /**
     * @interfaceName getLastCommand
     * @uses 获取设备最近一次的指令（如重启、强制改设备参数）
     * 传入参数
     * @param hid 酒店编号
     * @param room 房间号
     * @param mac Mac地址
     * @var json
     * @return
     *     status 状态  （200成功；404失败，没有指令）
     *     info 发送状态说明
     *     command_name 指令名称（重启：reboot  强制改设备参数：modify_para）
     */
    public function getLastCommand(){
        $json = array();
        $hid = empty($_REQUEST['hid'])?"":strtoupper($_REQUEST["hid"]);
        $room = empty($_REQUEST['room'])?"":$_REQUEST["room"];
        $mac = empty($_REQUEST['mac'])?"":$_REQUEST["mac"];
        if (empty($hid)) {
            $this->errorCallback(404, "Error: hid param is needed!");
        }
        if (empty($room)) {
            $this->errorCallback(404, "Error: room param is needed!");
        }
        if (empty($mac)) {
            $this->errorCallback(404, "Error: mac param is needed!");
        }
        $Device = D("Device");
        $stbinfo=$Device->getByMac($mac);
        if (empty($stbinfo)){
            $this->errorCallback(404, "Error:The stb info is empty!");
        }else if(intval($stbinfo['command_result'])!=-1 || empty($stbinfo['command'])){
            $this->errorCallback(404, "Error:No command!");
        }else{
            $json['status'] = 200;
            $json['info'] = "Success,The command is existed!";
            $json['command_name'] = $stbinfo['command'];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($json);
        }
    }

    /**
     * http request:/api.php/Api/postCommandResult
     * @author Blues
     * @uses 指令执行结果上传
     * 传入参数
     * @param hid 酒店编号
     * @param room 房间号
     * @param mac Mac地址
     * @param command_name 指令名称
     * @param command_result 执行结果   1：成功  0：失败
     * @return
     *     status 状态  （200成功   404失败）
     *     info 发送状态说明
     */
    public function postCommandResult(){
        $hid = empty($_REQUEST['hid'])?"":strtoupper($_REQUEST["hid"]);
        $room = empty($_REQUEST['room'])?"":$_REQUEST["room"];
        $mac = empty($_REQUEST['mac'])?"":$_REQUEST["mac"];
        $command_name = empty($_REQUEST['command_name'])?"":$_REQUEST["command_name"];
        $command_result = empty($_REQUEST['command_result'])?0:$_REQUEST["command_result"];
        if (empty($hid)) {
            $this->errorCallback(404, "Error: hid param is needed!");
        }
        if (empty($room)) {
            $this->errorCallback(404, "Error: room param is needed!");
        }
        if (empty($mac)) {
            $this->errorCallback(404, "Error: mac param is needed!");
        }
        if (empty($command_name)) {
            $this->errorCallback(404, "Error: command name is needed!");
        }
        $Device=D("Device");
        $map['hid']=$hid;
        $map['room']=$room;
        $map['mac']=$mac;
        $map['command']=$command_name;
        $list=$Device->where($map)->select();
        if (empty($list)) {
            $this->errorCallback(404, "Error:The stb info is empty!");
        }
        $result = $Device->where($map)->setField("command_result",$command_result);
        $json['status'] = 200;
        $json['info'] = "Command result is received!";
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json);
    }

    /**
     * http request:/api.php/Api/getStbInfo
     * @author Blues
     * @uses 获取机顶盒要修改的参数信息
     * 传入参数
     * @param mac Mac地址
     * @var json
     * @return
     *     status 状态  （200成功；404失败，没有指令）
     *     info 发送状态说明
     *     stbinfo 设备各参数
     */
    public function getStbInfo(){
        $mac = empty($_REQUEST['mac'])?"":$_REQUEST["mac"];
        if (empty($mac)) {
            $this->errorCallback(404, "Error: mac param is needed!");
        }
        $Device = D("Device");
        $stbinfo=$Device->where('mac="'.$mac.'"')->field("itv_pppoe_user,itv_pppoe_pwd,itv_mode,itv_pppoe_ip,itv_dhcp_ip,itv_dhcp_plus_ip,itv_static_ip,wan_dhcp_ip,wan_static_ip,vlan_status,wifi_ssid,wifi_passwd,wifi_psk_type,server_url,hid,room")->limit(1)->select();
        if (empty($stbinfo)) {
            $this->errorCallback(404, "Error:The stb info is empty!");
        }else{
            $json['status'] = 200;
            $json['info'] = "Successed!";
            $json['stbinfo'] = $stbinfo[0];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($json);
        }
    }

    /**
     * http request:/api.php/Api/postStbParaInfo
     * @author Blues by 2016-12-21
     * @uses 机顶盒参数信息上传
     * @param  json格式的机顶盒参数信息
     * @var json
     * @return
     *     status 状态  （200成功   404失败）
     *     info 发送状态说明
     */
    public function postStbParaInfo(){
        $result = array();
        $json_input = file_get_contents('php://input');
        if (empty($json_input)) {
            $this->errorCallback(404, "Error: did not transport datas!");
        }
        $jsonData = json_decode($json_input);
        $arrayData = $this->object_array($jsonData);

        $stbData=array();
        $stbData['hid']=$arrayData['hid'];
        $stbData['room']=$arrayData['room'];
        $stbData['mac']=$arrayData['mac'];
        $stbData['aaa_account']=$arrayData['aaa_account'];
        $stbData['aaa_passwd']=$arrayData['aaa_passwd'];
        $stbData['dev_desc']=$arrayData['dev_desc'];
        $stbData['firmware_version']=$arrayData['firmware_version'];
        $stbData['model']=$arrayData['model'];
        $stbData['brand']=$arrayData['brand'];
        $stbData['board']=$arrayData['board'];
        $stbData['network_mode']=$arrayData['network_mode'];
        $stbData['itv_mode']=$arrayData['itv_mode'];
        $stbData['wan_mode']=$arrayData['wan_mode'];
        $stbData['itv_dhcp_user']=$arrayData['itv_dhcp_user'];
        $stbData['itv_dhcp_pwd']=$arrayData['itv_dhcp_pwd'];
        $stbData['itv_pppoe_user']=$arrayData['itv_pppoe_user'];
        $stbData['itv_pppoe_pwd']=$arrayData['itv_pppoe_pwd'];
        $stbData['itv_static_ip']=$arrayData['itv_static_ip'];
        $stbData['itv_netmask']=$arrayData['itv_netmask'];
        $stbData['itv_gateway']=$arrayData['itv_gateway'];
        $stbData['itv_dns1']=$arrayData['itv_dns1'];
        $stbData['itv_dns2']=$arrayData['itv_dns2'];
        $stbData['wan_pppoe_user']=$arrayData['wan_pppoe_user'];
        $stbData['wan_pppoe_pwd']=$arrayData['wan_pppoe_pwd'];
        $stbData['wan_static_ip']=$arrayData['wan_static_ip'];
        $stbData['wan_netmask']=$arrayData['wan_netmask'];
        $stbData['wan_gateway']=$arrayData['wan_gateway'];
        $stbData['wan_dns1']=$arrayData['wan_dns1'];
        $stbData['wan_dns2']=$arrayData['wan_dns2'];
        $stbData['last_login_ip'] = $this->get_client_ip();
        $stbData['last_login_time'] = time();
        $stbData['online'] = 1;
        $stbData['itv_version']=$arrayData['itv_version'];
        $stbData['route_firmware_version']=$arrayData['route_firmware_version'];
        $stbData['wan_pppoe_ip']=$arrayData['wan_pppoe_ip'];
        $stbData['vlan_number']=$arrayData['vlan_number'];
        $stbData['itv_pppoe_ip']=$arrayData['itv_pppoe_ip'];
        $stbData['itv_dhcp_ip']=$arrayData['itv_dhcp_ip'];
        $stbData['itv_dhcp_plus_ip']=$arrayData['itv_dhcp_plus_ip'];
        $stbData['wan_dhcp_ip']=$arrayData['wan_dhcp_ip'];
        $stbData['vlan_status']=$arrayData['vlan_status'];
        $stbData['server_url']=$arrayData['server_url'];
        $stbData['boot_time']=$arrayData['boot_time'];
        $stbData['wifi_ssid'] = $arrayData['wifi_ssid'];
        $stbData['wifi_psk_type'] = $arrayData['wifi_psk_type'];
        $stbData['wifi_passwd'] = $arrayData['wifi_passwd'];

        $Device=D("Device");
        $map['mac']=$arrayData['mac'];
        $list=$Device->where($map)->select();
        if (empty($list)) {
            //参数日志：记录参数前后变化
            $list=array();
            $log_info=$this->DeviceParaLog($stbData,$list);
            //添加设备信息
            $stbData['status'] = 1;
            $deviceID=$Device->add($stbData);
        }else{
            //参数日志：记录参数前后变化
            $log_info=$this->DeviceParaLog($stbData,$list[0]);
            //修改设备信息
            $deviceID=$Device->where($map)->save($stbData);
        }
        if ($deviceID !== false) {
            $result['status'] = 200;
            $result['info'] = "Successed!";
            $result['log_info']=$log_info;
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result);
        }else{
            $this->errorCallback(404, "Error: post error or update error!");
        }
    }
    
    //内部调用方法
    private function DeviceParaLog($after_info=array(),$before_info=array()) {
        $Device_para_log = D('device_para_log');
        $data = array();
        $data['mac'] = $after_info['mac'];
        $data['before_info'] = $this->LogBefore($before_info);
        $data['after_info'] = $this->LogBefore($after_info);
        if (empty($after_info['boot_time'])) {
            $data['boot_time'] = 0;
        }else{
            $data['boot_time'] = $after_info['boot_time'];
        }
        $data['post_time'] = time();
        $result = $Device_para_log->data($data)->add();
        if ($result!==false) {
            return "Successed!";
        }else{
            return "Failed!";
        }
    }

    //内部调用方法
    private function LogBefore($stbinfo=array()){
        $str = "";
        $str .="<p><strong>硬件信息</strong></p>";
        $str .="<p>MAC地址：".$stbinfo['mac']."</p>";
        $str .="<p>房间号：".$stbinfo['room']."</p>";
        $str .="<p>酒店编号：".$stbinfo['hid']."</p>";
        $str .="<p>服务器地址：".$stbinfo['server_url']."</p>";
        $str .="<p>设备型号：".$stbinfo['dev_desc']."</p>";
        $str .="<p>软件版本：".$stbinfo['firmware_version']."</p>";
         
        $str .="<p>ITV版本：".$stbinfo['itv_version']."</p>";
        $str .="<p>路由固件版本：".$stbinfo['route_firmware_version']."</p>";
         
        $str .="<p>Model：".$stbinfo['model']."</p>";
        $str .="<p>Brand：".$stbinfo['brand']."</p>";
        $str .="<p>Board：".$stbinfo['board']."</p>";

        $str .="<p><strong>网络模式</strong></p>";
        $str .="<p>模式：".$stbinfo['network_mode']."</p>";
        $str .="<p>ITV模式：".$stbinfo['itv_mode']."</p>";
        $str .="<p>WAN模式：".$stbinfo['wan_mode']."</p>";
         
        $str .="<p>VLAN号：".$stbinfo['vlan_number']."</p>";
        $str .="<p>VLAN状态：".$stbinfo['vlan_status']."</p>";
         
        $str .="<p><strong>WiFi信息</strong></p>";
        $str .="<p>WIFI的SSID：".$stbinfo['wifi_ssid']."</p>";
        $str .="<p>WIFI密码：".$stbinfo['wifi_passwd']."</p>";
        $str .="<p>WIFI加密方式：".$stbinfo['wifi_psk_type']."</p>";

        $str .="<p><strong>ITV参数</strong></p>";
        $str .="<p>itv_dhcp_user：".$stbinfo['itv_dhcp_user']."</p>";
        $str .="<p>itv_dhcp_pwd：".$stbinfo['itv_dhcp_pwd']."</p>";
        $str .="<p>itv_pppoe_user：".$stbinfo['itv_pppoe_user']."</p>";
        $str .="<p>itv_pppoe_pwd：".$stbinfo['itv_pppoe_pwd']."</p>";
         
        $str .="<p>itv_pppoe_ip：".$stbinfo['itv_pppoe_ip']."</p>";
        $str .="<p>itv_dhcp_ip：".$stbinfo['itv_dhcp_ip']."</p>";
        $str .="<p>itv_dhcp+_ip：".$stbinfo['itv_dhcp_plus_ip']."</p>";
         
        $str .="<p>itv_static_ip：".$stbinfo['itv_static_ip']."</p>";
        $str .="<p>itv_netmask：".$stbinfo['itv_netmask']."</p>";
        $str .="<p>itv_gateway：".$stbinfo['itv_gateway']."</p>";
        $str .="<p>itv_dns1：".$stbinfo['itv_dns1']."</p>";
        $str .="<p>itv_dns2：".$stbinfo['itv_dns2']."</p>";
         
        $str .="<p><strong>WAN参数</strong></p>";
        $str .="<p>wan_pppoe_user：".$stbinfo['wan_pppoe_user']."</p>";
        $str .="<p>wan_pppoe_pwd：".$stbinfo['wan_pppoe_pwd']."</p>";
         
        $str .="<p>wan_pppoe_ip：".$stbinfo['wan_pppoe_ip']."</p>";
        $str .="<p>wan_dhcp_ip：".$stbinfo['wan_dhcp_ip']."</p>";
         
        $str .="<p>wan_static_ip：".$stbinfo['wan_static_ip']."</p>";
        $str .="<p>wan_netmask：".$stbinfo['wan_netmask']."</p>";
        $str .="<p>wan_gateway：".$stbinfo['wan_gateway']."</p>";
        $str .="<p>wan_dns1：".$stbinfo['wan_dns1']."</p>";
        $str .="<p>wan_dns2：".$stbinfo['wan_dns2']."</p>";

        $str .="<p><strong>其他</strong></p>";
        $str .="<p>业务账号：".$stbinfo['aaa_account']."</p>";
        $str .="<p>业务密码：".$stbinfo['aaa_passwd']."</p>";
        $str .="<p>最近上线IP：".$stbinfo['last_login_ip']."</p>";
        if ($stbinfo['boot_time']>0) {
            $str .="<p>最近开机时间：".date("Y-m-d H:i:s",$stbinfo['boot_time'])."</p>";
        }else{
            $str .="<p>最近开机时间：-</p>";
        }
        if ($stbinfo['last_login_time']>0) {
            $str .="<p>访问接口时间：".date("Y-m-d H:i:s",$stbinfo['last_login_time'])."</p>";
        }else{
            $str .="<p>访问接口时间：-</p>";
        }
        return $str;
    }

    /**
     * interfaceName:getStbSleepInfo
     * @uses 获取设备休眠设置信息
     * @param hid 酒店编号
     * @param room 房间号
     * @param mac Mac地址
     * @var json
     * @return
     *     status 状态  （200成功    404失败）
     *     info 发送状态说明
     *     sleep_status 休眠状态 （1开启  2关闭）
     *     sleep_time 休眠时间段
     */
    public function getStbSleepInfo(){
        $json = array();
        $hid = empty($_REQUEST['hid'])?"":strtoupper($_REQUEST["hid"]);
        $room = empty($_REQUEST['room'])?"":$_REQUEST["room"];
        $mac = empty($_REQUEST['mac'])?"":$_REQUEST["mac"];
        if (empty($hid)) {
            $this->errorCallback(404, "Error: hid param is needed");
        }
        if (empty($room)) {
            $this->errorCallback(404, "Error: room param is needed");
        }
        if (empty($mac)) {
            $this->errorCallback(404, "Error: mac param is needed");
        }
        $Device = D("Device");
        $Device_mac_image = D("DeviceMacImage");
        $map['hid']=$hid;
        $map['room']=$room;
        $map['mac']=$mac;
        $list=$Device->where($map)->find();
        $imagelist = $Device_mac_image->select();
        foreach ($imagelist as $key => $value) {
            $imagelistbykey[$value['id']] = $value;
        }
        if (empty($list) || $list['sleep_status'] == null) {
            $this->errorCallback(404, "Error:stb sleep info is empty!");
        }else{
            $json['status'] = 200;
            $json['info'] = "Successed!";
            $json['sleep_status']=$list['sleep_status'];
            $json['wifi_order']=$list['wifi_order'];
            $json['sleep_time']=$list['sleep_time'];
            $json['sleep_marked_word']=$list['sleep_marked_word'];
            $json['sleep_countdown_time']=$list['sleep_countdown_time'];
            if(!empty($list['sleep_imageid'])){
                $json['image_path'] = self::$serverUrl.$imagelistbykey[$list['sleep_imageid']]['image_path'];
            }else{
                $json['image_path'] =self::$serverUrl.$imagelist[0]['image_path'];
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json);
    }

    // 新休眠状态返回记录
    public function device_sleepinfo(){
        $hid = I('post.hid','','strtoupper');
        $room = I('post.room','','strip_tags');
        $mac = I('post.mac','','strtoupper');
        if (empty($hid)) {
            $this->errorCallback(404, "Error: hid param is needed");
        }
        if (empty($room)) {
            $this->errorCallback(404, "Error: room param is needed");
        }
        if (empty($mac)) {
            $this->errorCallback(404, "Error: mac param is needed");
        }
        $field = "zxt_device_sleep.*,zxt_device_mac_image.image_name,zxt_device_mac_image.image_path";
        $list = D("device_sleep")->where('zxt_device_sleep.mac="'.$mac.'"')->join('zxt_device_mac_image on zxt_device_sleep.sleep_imageid=zxt_device_mac_image.id','left')->field($field)->select();
        if (empty($list)) {
            $this->errorCallback(404, "Error:stb sleep info is empty!");
        }
        header('Content-Type: application/json; charset=utf-8');
        $this->Callback(200,$list);
    }

    /**
     * [获取APK列表]
     * @return [array] $list [所有需要安装的最高版本的apk列表]
     */
    public function getApklist(){

        $hid = strtoupper(I('request.hid','','strip_tags'));
        $room = I('request.room','','strip_tags');
        $mac = strtoupper(I('request.mac','','strip_tags'));
        $app_type = I('request.type','','strip_tags');

        if (empty($hid)) {
            $this->Callback(10000, "Error: hid param is needed");
        }
        if (empty($room)) {
            $this->Callback(10000, "Error: room param is needed");
        }
        if (empty($mac)) {
            $this->Callback(10000, "Error: mac param is needed");
        }
        $mapDone['zxt_appstore.maclist'] = $mapDo['zxt_appstore.maclist'] = array(array('like',"%$mac%"), array('eq','all'),'or');
        $mapDone['zxt_appstore.audit_status'] = $mapDo['zxt_appstore.audit_status'] = 4;
        $field = "zxt_appstore.id,zxt_appstore.app_name,zxt_appstore.app_version,zxt_appstore.app_identifier,zxt_appstore.md5_file,zxt_appstore.app_package,zxt_appstore.app_introduce,zxt_appstore.app_file,zxt_appstore.app_pic,zxt_appstore.status";
        
        if(!empty($app_type)){
            if($app_type == "app"){
                $mapDone['zxt_appstore.app_type'] = $mapDo['zxt_appstore.app_type'] = 2;
            }elseif($app_type == "system"){
                $mapDone['zxt_appstore.app_type'] = $mapDo['zxt_appstore.app_type']  = 1;
            }
        }
        $mapDo['zxt_appstore.status'] = 1;
        $mapDone['zxt_appstore.status'] = array('neq',1);
        $listone = D("appstore")->where($mapDo)->field($field)->order('app_version desc')->select();
        if(!empty($listone)){
            foreach ($listone as $key => $value) {
                $listone_arr[$value['app_package']][] = $value;
            }
            foreach ($listone_arr as $key => $value) {
                $listDo[] = $value[0];
            }
        }

        $listDone = D("appstore")->where($mapDone)->field($field)->select();
        $list = array();

        if(!empty($listDo)){
            foreach ($listDo as $key => $value) {
                $list[$key] = $value;
                $list[$key]['app_file'] = self::$serverUrl.$value['app_file'];
                $list[$key]['app_pic'] = self::$serverUrl.$value['app_pic'];
            }
        }
        $count = count($list);
        if(!empty($listDone)){
            foreach ($listDone as $key => $value) {
                $list[$count] = $value;
                $list[$count]['app_file'] = self::$serverUrl.$value['app_file'];
                $list[$count]['app_pic'] = self::$serverUrl.$value['app_pic'];
                $count++;
            }
        }

        if(empty($list)){
            $this->Callback(404,"the list is empty");
        }else{
            $this->Callback(200,$list);
        }
    }

    /**
     * [记录apk安装情况]
     */
    public function installedapk(){

        $hid = strtoupper(I('request.hid'));
        $room = I('request.room');
        $mac = strtoupper(I('request.mac'));

        if (empty($hid)) {
            $this->Callback(10000, "Error: hid param is needed");
        }
        if (empty($room)) {
            $this->Callback(10000, "Error: room param is needed");
        }
        if (empty($mac)) {
            $this->Callback(10000, "Error: mac param is needed");
        }

        $apklist = I('post.apklist','','strip_tags');
        if(empty($apklist)){
            $this->Callback(10000,"Error:apklist param is needed");
        }
        
        $apklist = json_decode(urldecode($apklist),true);

        foreach ($apklist as $key => $value) {
            $searchMap['app_version'] = $value['app_version'];
            $searchMap['app_package'] = $value['app_package'];
            $appstore = D("appstore")->where($searchMap)->field('id')->find();
            if(!empty($appstore['id'])){
                $sql = 'replace into `zxt_device_update_result`(unique_flag,mac,uplist_id,result)VALUES("'.md5($value['app_version'].$mac.$value['app_package']).'","'.$mac.'","'.$appstore['id'].'","'.$value['status'].'")';
                D("device_update_result")->execute($sql);
            }
        }
        $this->Callback(200,'Success');
    }

    /**
     * [内部调用方法]
     */
    private function Callback($status,$info){
        $data['status'] = $status;
        $data['data'] = $info;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /**
     * [保持终端在线接口]
     */
    public function keepDeviceStatus(){
        $hid = I('post.hid','','strtoupper');
        $room = I('post.room');
        $mac = strtoupper(I('post.mac'));
        if(!empty($mac) || !empty($room) || !empty($hid)){
            $map['mac'] = $mac;
            $count = D("device")->where($map)->count();
            $data['online'] = 1;
            $data['last_login_time'] = time();
            if ($count>0) {
                $result = D("device")->where($map)->setField($data);
            }else{
                $data['hid'] = $hid;
                $data['room'] = $room;
                $data['mac'] = $mac; 
                $result = D("device")->data($data)->add();
            }
            if($result !== false){
                $this->Callback(200,'Success');
            }else{
                $this->Callback(0,'Error');
            }
        }else{
            $this->Callback(10000,'the mac is empty');
        }
    }

    /**
     * [获取一级栏目  酒店+集团+通用]
     * @return [json] $list [一级栏目集合]
     */
    public function hotelcategory_firstlevel(){

        $hid = I('post.hid','','strtoupper');
        $room = I('post.room','','strip_tags');
        $mac = I('post.mac','','strtoupper');
        if(empty($hid)){
            $this->Callback(10000,'hid is empty');
        }

        //查询是集团酒店还是子酒店
        $pid = $this->checkPid($hid);

        //酒店自身
        $map['hid'] = $hid;
        $map['pid'] = 0;
        $map['status'] = 1;
        $hotelField = "id,hid,name,modeldefineid,sort,intro,icon as filepath";
        $listHotel = D("hotel_category")->where($map)->field($hotelField)->order('sort asc')->select();

        //集团通用栏目
        $listChg = array();
        $chgField = "id,hid,name,modeldefineid,sort,filepath,intro";
        if($pid['pid']){  //子酒店
            $chglist = D("hotel_chglist")->where('hid="'.$hid.'"')->field('chg_cid')->select();
            if(!empty($chglist)){
                foreach ($chglist as $key => $value) {
                    $chglist_arr[] = $value['chg_cid'];
                }
                $chgMap['id'] = array('in',$chglist_arr);
                $chgMap['status'] = 1;
                $listChg = D("hotel_chg_category")->where($chgMap)->order('sort')->select();
            }
        }else{
            $listChg = D("hotel_chg_category")->where($map)->order('sort')->select();
        }

        //通用栏目
        $listTopic = array();
        $topiclist = D("hotel_topic")->where('hid="'.$hid.'"')->field('topic_id')->select();
        if(!empty($topiclist)){
            $topicField = "id,title as name,icon as filepath,intro";
            foreach ($topiclist as $k => $v) {
                $topiclist_arr[] = $v['topic_id'];
            }
            $topicMap['id'] = array('in',$topiclist_arr);
            $topicMap['status'] = 1;
            $listTopic = D("topic_group")->where($topicMap)->field($topicField)->select();
        }

        if(!empty($listHotel)){
            $i = 0;
            foreach ($listHotel as $key => $value) {
                $list[$i]['id'] = $value['id']; 
                $list[$i]['hid'] = $value['hid'];
                $list[$i]['name'] = $value['name'];
                $list[$i]['filepath'] = $value['filepath'];
                $list[$i]['modeldefineid'] = $value['modeldefineid'];
                $list[$i]['intro'] = $value['intro'];
                $list[$i]['votype'] = "hotel";
                $i++;
            }
        }
        if(!empty($listChg)){
            $j = count($list);
            foreach ($listChg as $key => $value) {
                $list[$j]['id'] = $value['id']; 
                $list[$j]['name'] = $value['name'];
                $list[$j]['filepath'] = $value['filepath']; 
                $list[$j]['hid'] = $value['hid'];
                $list[$j]['modeldefineid'] = $value['modeldefineid'];
                $list[$j]['intro'] = $value['intro'];
                $list[$j]['votype'] = "chg";
                $j++;
            }
        }
        if(!empty($listTopic)){
            $k = count($list);
            foreach ($listTopic as $kk => $vv) {
                $list[$k]['id'] = $vv['id'];
                $list[$k]['name'] = $vv['name'];
                $list[$k]['filepath'] = $vv['filepath'];
                $list[$k]['hid'] = $hid;
                $list[$k]['modeldefineid'] = 3;
                $list[$k]['intro'] = $vv['intro'];
                $list[$k]['votype'] = "topic";
                $k++;
            }
        }
        if(!empty($list)){
            $this->Callback(200,$list);
        }else{
            $this->Callback(404,'the list is empty');
        }
    }

    /**
     * [根据hid查找PID]
     * @param  [string] $hid [酒店编号]
     * @return [int]    $pid [父id]
     */
    private function checkPid($hid){
        return D("hotel")->where('hid="'.$hid.'"')->field('pid')->find();
    }

    /**
     * [获取二级栏目  酒店+集团+通用]
     * @return [json] $list [一级栏目集合]
     */
    public function hotelcategory_secondlevel(){

        $hid = I('request.hid','','strtoupper');
        $pid = I('request.id','','intval');
        $votype = I('request.votype','','strip_tags');

        if(empty($hid) || empty($pid) || empty($votype)){
            $this->Callback(10000,'hid,id or votype is empty');
        }

        if($votype == "hotel"){
            $map['zxt_hotel_category.status'] = 1;
            $map['zxt_hotel_category.hid'] = $hid;
            $map['zxt_hotel_category.pid'] = $pid;
            $field = "zxt_hotel_category.id,zxt_hotel_category.hid,zxt_hotel_category.name,zxt_hotel_category.modeldefineid,zxt_hotel_category.pid,zxt_hotel_category.sort,zxt_hotel_category.intro,zxt_hotel_category.icon,zxt_modeldefine.codevalue,zxt_modeldefine.packagename,zxt_modeldefine.classname";
            $list = D("hotel_category")->where($map)->field($field)->join("zxt_modeldefine ON zxt_hotel_category.modeldefineid=zxt_modeldefine.id")->order('sort asc')->select();
        }elseif($votype == "chg"){
            $map['zxt_hotel_chg_category.status'] = 1;
            $map['zxt_hotel_chg_category.hid'] = $hid;
            $map['zxt_hotel_chg_category.pid'] = $pid;
            $field = "zxt_hotel_chg_category.id,zxt_hotel_chg_category.hid,zxt_hotel_chg_category.name,zxt_hotel_chg_category.modeldefineid,zxt_hotel_chg_category.pid,zxt_hotel_chg_category.sort,zxt_hotel_chg_category.intro,zxt_hotel_chg_category.filepath as icon,zxt_modeldefine.codevalue,zxt_modeldefine.packagename,zxt_modeldefine.classname";
            $list = D("hotel_chg_category")->field($field)->where($map)->join("zxt_modeldefine ON zxt_hotel_chg_category.modeldefineid=zxt_modeldefine.id")->order('sort asc')->select();
        }elseif($votype == "topic"){
            $map['zxt_topic_category.status'] = 1;
            $map['zxt_topic_category.groupid'] = $pid;
            $field = "zxt_topic_category.id,zxt_topic_category.name,zxt_topic_category.modeldefineid,zxt_topic_category.sort,zxt_topic_category.icon,zxt_modeldefine.codevalue,zxt_modeldefine.packagename,zxt_modeldefine.classname";
            $list = D("topic_category")->field($field)->where($map)->join("zxt_modeldefine ON zxt_topic_category.modeldefineid=zxt_modeldefine.id")->order('sort')->select();
        }else{
            $this->Callback(10000,"the votype is wrong!");
        }

        if(!empty($list)){
            foreach ($list as $key => $value) {
                if($votype == "chg"){
                    if($value['codevalue'] != 501){
                        $list[$key]['votype'] = "chg";
                    }else{
                        $list[$key]['votype'] = "videochg";
                    }
                }elseif($votype == "hotel"){
                    if($value['codevalue'] != 501){
                        $list[$key]['votype'] = "hotel";
                    }else{
                        $list[$key]['votype'] = "videohotel";
                    }
                }elseif($votype == "topic"){
                    $list[$key]['votype'] = "topic";
                    $list[$key]['hid'] = $hid;
                    $list[$key]['pid'] = $pid;
                    $list[$key]['intro'] = "";
                }
            }
            $this->Callback(200,$list);
        }else{
            $this->Callback(404,'the list is empty');
        }
    }

    /**
     * [获取二级栏目下的资源  酒店+集团+通用栏目]
     * @return [json] $list [资源列表集合]
     */
    public function hotelresource_second(){

        $hid = I('request.hid','','strtoupper');
        $room = I('request.room','','strip_tags');
        $mac = I('request.mac','','strtoupper');
        $cid = I('request.id','','intval');
        $votype = I('request.votype','','strip_tags');

        if (empty($hid) || empty($cid) || empty($votype)) {
            $this->Callback(10000,'hid,id or votype is empty');
        }

        $map['hid'] = $hid;
        if($votype == "chg"){
            $map['cid'] = $cid;
            $model = D("hotel_chg_resource");
            $field = "id,hid,cid,title,intro,sort,filepath,file_type,icon as video_image";
        }elseif($votype == "hotel"){
            $map['category_id'] = $cid;
            $model = D("hotel_resource");
            $field = "id,hid,category_id as cid,title,intro,sort,type as file_type,filepath,video_image";
        }elseif($votype=="topic"){
            $map['cid'] = $cid;
            $model = D("topic_resource");
            $field = "id,cid,title,intro,sort,type as file_type,video,image,video_image";
        }else{
            $this->Callback(10000,"the votype is wrong!");
        }
        $map['status'] = 1;
        $map['audit_status'] = 4;

        $list = $model->where($map)->field($field)->order('sort asc')->select();

        if(!empty($list)){
            foreach ($list as $key => $value) {
                if(empty($value['filepath'])){
                    $list[$key]['filepath'] = "";
                }
                if(empty($value['video_image'])){
                    $list[$key]['video_image'] = "";
                }
                if($votype=="topic" && $value['file_type']==1 && !empty($value['video'])){
                    $list[$key]['filepath'] = $value['video'];
                    unset($list[$key]['video']);
                    unset($list[$key]['image']);
                }elseif($votype=="topic" && $value['file_type']==2 && !empty($value['image'])){
                    $list[$key]['filepath'] = $value['image'];
                    unset($list[$key]['video']);
                    unset($list[$key]['image']);
                }elseif(empty($value['video']) && empty($value['image']) && $votype =="topic"){
                    $list[$key]['filepath'] = "";
                    unset($list[$key]['video']);
                    unset($list[$key]['image']);
                }
                $list[$key]['votype'] = $votype;
            }
            $this->Callback(200,$list);
        }else{
            $this->Callback(404,'the list is empty');
        }
    }

    /**
     * [获取地址前缀]
     * @return [string] $url [url地址前缀]
     */
    public function getAllresourcepathPrefix(){
        $url = self::$serverUrl;
        $this->Callback(200,$url);
    }

    /**
     * [获取广告弹窗信息列表]
     * @return [array] $adsetList [广告弹窗信息列表]
     */
    public function getAdresource(){

        $hid = I('post.hid','','strtoupper');
        $mac = I('post.mac','','strip_tags');
        $room = I('post.room','','strip_tags');

        if(empty($hid)){
            $this->Callback(10000,'the hid is empty');
        }
        $adsetMap['zxt_hotel_adset.hid'] = $hid;
        $adsetMap['zxt_hotel_adset.status'] = 1;
        $adsetList = D("hotel_adset")->where($adsetMap)->select();

        if(!empty($adsetList)){
            foreach ($adsetList as $key => $value) {
                $arr_adsetid[] = $value['id'];
                $adsetList[$key]['hour'] = sprintf("%02d",$value["hour"]);
                $adsetList[$key]['minute'] = sprintf("%02d",$value["minute"]);
            }
            $releateMap['zxt_hotel_adset_adresource.hid'] = $hid;
            $releateMap['zxt_hotel_adset_adresource.adset_id'] = array('in',$arr_adsetid);
            $releateMap['zxt_hotel_adresource.audit_status'] = 4;
            $releateMap['zxt_hotel_adresource.status'] = 1;
            $releateField = "zxt_hotel_adset_adresource.*,zxt_hotel_adresource.title,zxt_hotel_adresource.filepath";
            $releateList = D("hotel_adset_adresource")->where($releateMap)->join('zxt_hotel_adresource ON zxt_hotel_adset_adresource.adresource_id = zxt_hotel_adresource.id')->field($releateField)->order('sort asc')->select();
            
            if(!empty($releateList)){ 
                $resourceList = array();
                //同一adset_id 归为同一数组
                foreach ($releateList as $key => $value) {
                    $resourceList[$value['adset_id']][$key]['title'] = $value['title'];
                    $resourceList[$value['adset_id']][$key]['filepath'] = $value['filepath'];
                    $resourceList[$value['adset_id']][$key]['web_filepath'] = self::$serverUrl.$value['filepath'];
                    $resourceList[$value['adset_id']][$key]['sort'] = $value['sort'];
                }
                //循环赋值
                foreach ($adsetList as $key => $value) {
                    if(!empty($resourceList[$value['id']])){
                        $adsetList[$key]['resource'] = array_values($resourceList[$value['id']]);
                    }else{
                        $adsetList[$key]['resource'] = array();
                    }
                }
            }else{//有设置  但是关联表
                foreach ($adsetList as $key => $value) {
                    $adsetList[$key]['resource'] = array();
                }
            }
            $this->Callback(200,$adsetList);
        }else{ //没有设置 即无关联
            $this->Callback(404,'the adresource is empty');
        }
    }

    /**
     * [根据HID查询酒店视频轮播资源]
     * @return [array] $list [视频轮播资源列表]
     */
    public function getVideoCarousel(){
        $hid = I('request.hid');
        if(empty($hid)){
            $this->Callback(10000,'the hid is empty');
        }
        $hotelinfo = D("hotel")->where('hid="'.$hid.'"')->field('pid')->find();
        if ($hotelinfo['pid'] > 0) {
            $photelinfo = D("hotel")->where('id='.$hotelinfo['pid'])->field('hid')->find();
            $map['hid'] = array('in', array($hid,$photelinfo['hid']));
        }else{
            $map['hid'] = strtoupper($hid);
        }
        $list = array();
        $map['audit_status'] = 4;
        $map['status'] = 1;
        $field = "id,hid,cid,ctype,title,sort,filepath,video_image,size";
        $list = D("hotel_carousel_resource")->where($map)->field($field)->select();

        if(!empty($list)){
            foreach ($list as $key => $value) {
                $list[$key]['getfilepath'] = self::$serverUrl.$value['filepath'];
                if(!empty($value['video_image'])){
                    $list[$key]['getimage'] = self::$serverUrl.$value['video_image'];
                }else{
                     $list[$key]['getimage'] = null;
                }
            }
            $this->Callback(200,$list);
        }else{
            $this->Callback(404,'not find');
        }        
    }

    /**
     * [获取酒店launcher_json资源]
     */
    public function hotelresource_json(){
        $hid = I('post.hid','','strtoupper');
        if (empty($hid)) {
            $this->Callback(10000,'the hid is empty');
        }
        $hoteldataMap['zxt_hotel.hid'] = $hid;
        $hoteldata = D("hotel")->where($hoteldataMap)->field('zxt_hotel.pid,zxt_hotel_chglist.phid')->join('zxt_hotel_chglist ON zxt_hotel.hid=zxt_hotel_chglist.hid','left')->find();
        $dir = dirname(dirname(dirname(dirname(__FILE__)))).DIRECTORY_SEPARATOR.'Public'.DIRECTORY_SEPARATOR.'hotel_json'.DIRECTORY_SEPARATOR.$hid;

        if ($hoteldata['pid']==0) { //集团酒店
            if (!is_dir($dir)) {
                $this->Callback(404,'the dir is empty');
            }
        }else{
            $dir_pre = dirname(dirname(dirname(dirname(__FILE__)))).DIRECTORY_SEPARATOR.'Public'.DIRECTORY_SEPARATOR.'hotel_json';
            $phidfile = false;
            if(!empty($hoteldata['phid'])){
                if (!is_dir($dir_pre.DIRECTORY_SEPARATOR.$hoteldata['phid'])) {
                    $phidfile = false;
                }
            }
            if (!is_dir($dir) && $phidfile) {
                $this->Callback(404,'the dir is empty');
            }
        }
        $list = array();

        $filelist = scandir($dir);

        if (count($filelist)>2) {
            unset($filelist['0'],$filelist['1']);
            foreach ($filelist as $key => $value) {
                $list[substr($value, 0,-5)] = self::$serverUrl.DIRECTORY_SEPARATOR.'hotel_json'.DIRECTORY_SEPARATOR.$hid.DIRECTORY_SEPARATOR.$value;
            }
        }

        if ($hoteldata['pid']>0 && !empty($hoteldata['phid'])) {
            $pfilelist = scandir($dir_pre.DIRECTORY_SEPARATOR.$hoteldata['phid']);
            if (count($pfilelist)>2) {
                unset($pfilelist[0],$pfilelist[1]);
                $chg_arr = array('chgcategory_first','chgcategory_second','chgresource','videochg');
                foreach ($pfilelist as $key => $value) {
                    if (in_array(substr($value,0,-5), $chg_arr)) {
                        $list[substr($value,0,-5)] = self::$serverUrl.DIRECTORY_SEPARATOR.'hotel_json'.DIRECTORY_SEPARATOR.$hoteldata['phid'].DIRECTORY_SEPARATOR.$value;
                    }
                }
            }
            //生成chgcategory_first
            $chgbind = D("hotel_chglist")->where('hid="'.$hid.'"')->field('chg_cid')->select();
            if (!empty($chgbind)) {
                
            }
        }
        if (!empty($list)) {
            if (empty($list['chgcategory_first'])) {
                $list['chgcategory_first'] = '';
            }
            if (empty($list['chgcategory_second'])) {
                $list['chgcategory_second'] = '';
            }
            if (empty($list['chgresource'])) {
                $list['chgresource'] = '';
            }
            if (empty($list['hotelcategory_first'])) {
                $list['hotelcategory_first'] = '';
            }
            if (empty($list['hotelcategory_second'])) {
                $list['hotelcategory_second'] = '';
            }
            if (empty($list['hotelresource'])) {
                $list['hotelresource'] = '';
            }
            if (empty($list['videochg'])) {
                $list['videochg'] = '';
            }
            if (empty($list['videohotel'])) {
                $list['videohotel'] = '';
            }
         
            $this->Callback(200,$list);
        }else{
            $this->Callback(404,'the resource is empty');
        }
    }

    /**
     * [验证酒店HID合法性]
     * @return [json] [成功返回酒店基本信息]
     */
    public function verify_hotel(){
        $hid = I('post.hid','','strtoupper');
        $token = I('post.token','','strip_tags');
        if(trim($token) != "zxt_check_hotelinfo" ){
            $this->Callback(10002,'wrong token!');
        }
        if(!empty($hid)){
            $map['hid'] = $hid;
            $vo = D("hotel")->where($map)->field('id,hid,name,hotelname,manager,mobile,status')->find();
            if(!empty($vo)){
                $this->Callback(200,$vo);
            }else{
                $this->Callback(404,'the hid is wrong');
            }
        }else{
            $this->Callback(10000,'the hid is needed');
        }
    }

    /**
     * [验证设备Mac是否存在]
     */
    public function verify_device(){
        $mac = I('post.mac','','strtoupper');
        $token = I('post.token','','strip_tags');
        if (empty($mac)) {
            $this->Callback(10000,'the mac is needed');
        }
        if(trim($token) != "zxt_check_hotelinfo" ){
            $this->Callback(10002,'wrong token!');
        }
        
        $field = "id,hid,room,mac,room_remark";
        $vo = D("device")->where('mac="'.$mac.'"')->field($field)->find();
        if (!empty($vo)) {
            $this->Callback(200,$vo);
        }else{
            $this->Callback(404,'the mac is wrong!');
        }
    }

    /**
     * [根据HID和ROOM获取MAC]
     * @param  [string] $hid [酒店编号]
     * @param  [string] $room [房间号]
     * @return [json] $vo [结果集 状态码+data]
     */
    public function mac_by_hotelandroom(){
        $hid = I('post.hid','','strtoupper');
        $room = I('post.room','','strip_tags');
        if (empty($hid)) {
            $this->Callback(10000,'the hid is empty');
        }
        if (empty($room)) {
            $this->Callback(10000,'the room is empty');
        }

        $map['hid'] = $hid;
        $map['room'] = $room;
        $vo = D("device")->where($map)->field('mac')->select();
        if (!empty($vo)) {
            $this->Callback(200,$vo);
        }else{
            $this->Callback(404,'the mac is Non-existent');
        }
    }

    /**
     * [获取酒店列表]
     * @param [string] token [登录token]
     * @param [string] hid [查询条件hid]
     * @param [string] hotelname [查询条件hotelname]
     * @param [string] offset [查询开始条数 默认第一条数据]
     * @param [string] rows [查询条数 默认10条]
     */
    public function hotellist(){
        $token = I("post.token",'','strip_tags');
        if ($token != "zxt_search_hotellist") {
            $this->Callback(10002,'you have wrong token');
        }
        $hotelinfo = I('post.hotelinfo','','strtoupper');
        $main_type = I('post.main_type','','intval');
        $demo = I('post.demo','','intval');
        $offset = I('post.offset','','intval');//分页偏移量
        $rows = I('post.rows','','intval'); //条数
        $field = "id,hid,hotelname,name,manager,mobile";
        $list = array(); //
        $map = array();
        if (!empty($hotelinfo)) {
            $where['hid'] = array('like',"%$hotelinfo%");
            $where['hotelname'] = array('like',"%$hotelinfo%");
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
        }
        if ($offset>0) {
            $offset = $offset-1;
        }else{
            $offset = 0;
        }
        if (!$rows) {
            $rows = 10;
        }
        if (is_numeric($main_type)) {
            $map['main_type'] = $main_type;
        }
        if (is_numeric($demo)) {
            $map['demo'] = $demo;
        }
        $count = D("hotel")->where($map)->count();
        $list = D("hotel")->where($map)->limit($offset.','.$rows)->field($field)->select();
        $data['count'] = $count;
        $data['list'] = $list;
        if ($count>0) {
            $this->Callback(200,$data);
        }else{
            $this->Callback(404,'the hotellist is empty');
        }
    }

    // 获取父hid
    public function getphid($hid){
        $vo = D("hotel")->where("hid='".$hid."'")->field("hid,pid")->find();
        if ($vo['pid'] == 0) {
            return false;
        }
        $result = D("hotel")->where("id=".$vo['pid'])->field('hid')->find();
        return $result;
    }

	/**
     * 获取app定时启动设置
     */
    public function getapptimeset()
    {
        $hid = I('request.hid', '', 'strip_tags');
        if (empty($hid)) {
            $this->Callback(404, 'the hid is empty');
        }
        $where['zxt_appopen_name.hid'] = $hid;
        $where['zxt_appopen_set.status'] = 1;
        $field = "zxt_appopen_name.appname,zxt_appopen_name.packagename,zxt_appopen_name.classname,zxt_appopen_set.start_time,zxt_appopen_set.end_time,zxt_appopen_set.date";
        $list = D('appopen_name')
            ->where($where)
            ->field($field)
            ->join('join zxt_appopen_set on zxt_appopen_name.id=zxt_appopen_set.applistid')
            ->select();
        $this->Callback('200', $list);
    }
}
?>
