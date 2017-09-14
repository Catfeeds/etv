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
        $HotelResource = D("HotelResource");
        $map['zxt_hotel_welcome.hid'] = $hid;
        $map['zxt_hotel_resource.status'] = 1;
        $map['zxt_hotel_resource.audit_status'] = 4;
        $foo = $HotelLogo->field('zxt_hotel_resource.audit_status,zxt_hotel_resource.status,zxt_hotel_resource.filepath')->where($map)->join('zxt_hotel_resource ON zxt_hotel_welcome.resourceid = zxt_hotel_resource.id')->select();
        if(empty($foo)){
            $this->errorCallback(404, "Error: the welcome image info is not existed!");
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
            $this->errorCallback(404, "Error: the language list is empty!");
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
        header("Content-type:text/html;charset=utf-8");
        $hid = strtoupper(I('request.hid'));
        if (empty($hid)) {
            $this->errorCallback(404, "Error: hid param is needed!");
        }
        $cityCode = $this->getCityCode($hid);
        $cityName=$this->getCityName($cityCode);
        $gb_cityName =  urlencode(mb_convert_encoding($cityName,'gb2312','utf-8'));
        $url = "http://php.weather.sina.com.cn/xml.php?city=".$gb_cityName.'&password=DJOYnieT8234jlsK&day=0';
        $curl = curl_init();

        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 1);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //执行命令
        $curl_return = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);

        list($header, $body) = explode("\r\n\r\n", $curl_return, 2);
        $info = simplexml_load_string($body);
        $weatherInfo = json_encode($info->Weather);
        $weatherInfo = json_decode($weatherInfo,true);
        $nowH = strtotime(date("H:i"));
        $beginH = strtotime(date("06:00"));
        $endH = strtotime(date("18:00"));

        if(!empty($weatherInfo)){
            $data['city'] = $weatherInfo['city'];
            $data['date'] = $weatherInfo['savedate_weather'];
            $data['weekday'] = '';
            $data['sign'] = '';
            if($beginH <= $nowH || $nowH <= $endH){  //白天
                $data['image'] = ' http://php.weather.sina.com.cn/images/yb3/78_78/'.$weatherInfo['figure1'].'_0.png';
                $data['low'] = $weatherInfo['temperature1']-2;
                $data['high'] = $weatherInfo['temperature1']+2;
                $data['wind'] = $weatherInfo['direction1'];
                $data['description'] = $weatherInfo['status1'];
            }else{ //黑夜
                $data['image'] = ' http://php.weather.sina.com.cn/images/yb3/78_78/'.$weatherInfo['figure1'].'_1.png';
                $data['low'] = $weatherInfo['temperature2']-2;
                $data['high'] = $weatherInfo['temperature2']+2;
                $data['wind'] = $weatherInfo['direction2'];
                $data['description'] = $weatherInfo['status2'];
            }
            
            $json['status'] = 200;
            $json['info'] = "Successed!";
            $json['weatherInfo'] = $data;
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($json);
        }else{
            $this->errorCallback(404, "Error: getWeather is error!");
        }
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
     *     Notice表已经废弃 现改用message表
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
        $HotelJump = D("hotel_jump");
        $volist=$HotelJump->getByHid($hid);
        if (empty($volist)) {
            $this->errorCallback(404, "Error: the hotel base setting is empty!");
        }
        $json['status'] = 200;
        $json['info'] = "Successed!";
        $json['isjump'] = $volist['isjump'];
        $json['staytime'] = $volist['staytime'];
        $json['hasvideo'] = $volist['video_set'];//状态1时 播放视频进入主页(中途可退出)  状态3时 强制播放完视频方可退出
        if ($volist['video_set']==1 || $volist['video_set']==3) {
            $HotelResource = D("hotel_resource");
            $hotelresourelist = $HotelResource->getById($volist['resourceid']);
            if(!empty($hotelresourelist)){
                $json['video'] = self::$serverUrl.$hotelresourelist['filepath'];
                $json['getvideo'] = "/Public".$hotelresourelist['filepath'];
            }else{
                $json['video'] = '';
                $json['getvideo'] = '';
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json);
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
        $HotelSpread = D("hotel_spread");
        $prefix = C('DB_PREFIX');
        $map["{$prefix}hotel_spread.hid"] = $hid;
        $map["{$prefix}hotel_resource.status"] = 1;
        $list = $HotelSpread->field("{$prefix}hotel_resource.*")->join("{$prefix}hotel_resource ON {$prefix}hotel_spread.resourceid={$prefix}hotel_resource.id")->where($map)->order('zxt_hotel_resource.sort')->find();
        if(empty($list)){
            $this->errorCallback(404, "Error: the hotel base setting is empty!");
            die();
        }
        if($list['audit_status']==4){//审核通过
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
        }else{
            $this->errorCallback(404, "Error: the content is empty or not audit!");
            die();
        }
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
        $Ad = D("ad");
        $list=$Ad->where('status=1 and audit_status=4 and (hidlist = "all" or hidlist like "'."%".$hid."%".'")')->order('upload_time desc')->select();
        if (empty($list)) {
            $this->errorCallback(404, "Error:ad is not authorized to the hotel, or the audit ad image is empty!");
        }
        $json['status'] = 200;
        $json['info'] = "Successed!";
        $json['image']=self::$serverUrl.$list[0]['filepath'];
        $json['getimage']="/Public".$list[0]['filepath'];
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json);
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
        $config=$HotelConfig->getByHid($hid);
        if (!empty($config)) {
            $map['id'] = array("in",$config['topic_id']);
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
                        $categorylist_second = D("hotel_chg_category")->where('pid='.$value2['id'].' and status=1')->field('id,name,modeldefineid')->select();
                        $vosubGroup = array();
                        if(!empty($categorylist_second)){
                            foreach ($categorylist_second as $k=>$val) {
                                $submdGroup=$Modeldefine->where('id="'.$val["modeldefineid"].'"')->field('packagename,classname')->find();
                                $vosubGroup[$k]['subMenuId']=$val['id'];
                                $vosubGroup[$k]['modelDefineId']=$val['modeldefineid'];
                                $vosubGroup[$k]['name']=$val['name'];
                                $vosubGroup[$k]['packageName']=$submdGroup['packagename'];
                                $vosubGroup[$k]['className']=$submdGroup['classname'];
                                if($submdGroup['codevalue']=='501'){
                                    $vosubGroup[$k]['type'] = "videochg";
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
            if ($normal_list[$i]['utc']>$currentVersion) {//大于当前版本号
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
            asort($versionList,SORT_NUMERIC);//每一项作为数字 升序
            $maxVersion=$versionList[0];//$maxVersion为最小
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
        $Status = I('request.status')?I('request.status'):0;
        $Ip = $this->get_client_ip();
        $this->stbLog($Hid,$Roomno,$Mac,$Cversion,$Uversion,$Status,$Ip);

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
    /**
     * http request:/api.php/Api/postErrorInfo
     * @author Blues
     * @uses 错误代码上传
     * @param hid 酒店编号
     * @param room 房间号
     * @param mac Mac地址
     * @var json
     * @return
     *     status 状态  （200成功    404失败）
     *     info 发送状态说明
     *     此接口 有问题 2017.08.08发现
     */
    public function postErrorInfo(){
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
        if (TRUE) {
            $json['status'] = 200;
            $json['info'] = "Successed!";
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($json);
        }else{
            $this->errorCallback(404, "Error: post errorInfo has bug!");
        }
    }
    /**
     * http request:/api.php/Api/getWifiInfo
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
     *     wifi_psk_type
     *     wifi_passwd
     */
    public function getWifiInfo(){
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
        $DeviceWifi = D("DeviceWifi");
        $map['hid']=$hid;
        $map['room']=$room;
        $map['mac']=$mac;
        $map['wifistatus']=1;//1开启wifi，0关闭
        $list=$DeviceWifi->where($map)->select();
        if (empty($list)) {
            $this->errorCallback(404, "Error:no wifi info or wifi is closed!");
        }else{
            $json['status'] = 200;
            $json['info'] = "Successed!";
            $json['wifi_ssid']=$list[0]['ssid'];
            if ($list[0]['security']==1) {
                $json['wifi_psk_type']="psk2";
                $json['wifi_passwd']=$list[0]['password'];
            }else{
                $json['wifi_psk_type']="none";
                $json['wifi_passwd']="";
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json);
    }
    /**
     * http request:/api.php/Api/postWifiResult
     * @author Blues
     * @uses Wifi设置结果上传
     * 传入参数
     * @param hid 酒店编号
     * @param room 房间号
     * @param mac Mac地址
     * @param status  wifi设置状态   1：成功  0：失败
     * @return
     *     status 状态  （200成功   404失败）
     *     info 发送状态说明
     */
    public function postWifiResult(){
        $hid = empty($_REQUEST['hid'])?"":strtoupper($_REQUEST["hid"]);
        $room = empty($_REQUEST['room'])?"":$_REQUEST["room"];
        $mac = empty($_REQUEST['mac'])?"":$_REQUEST["mac"];
        $status = empty($_REQUEST["status"]) ? 0 : $_REQUEST["status"];
        if (empty($hid)) {
            $this->errorCallback(404, "Error: hid param is needed");
        }
        if (empty($room)) {
            $this->errorCallback(404, "Error: room param is needed");
        }
        if (empty($mac)) {
            $this->errorCallback(404, "Error: mac param is needed");
        }
        $DeviceWifi = D("DeviceWifi");
        $map['hid']=$hid;
        $map['room']=$room;
        $map['mac']=$mac;
        $data['result']=$status;//1开启wifi，0关闭
        $list=$DeviceWifi->where($map)->save($data);

        if ($list) {
            $result['status'] = 200;
            $result['info'] = "Successed!";
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result);
        }else{
            $this->errorCallback(404, "Error: post Wifi result error!");
        }
    }
    /**
     * http request:/api.php/Api/postStbInfo
     * @author Blues
     * @uses 机顶盒信息上传
     * @param  json格式的机顶盒信息
     * @var json
     * @return
     *     status 状态  （200成功   404失败）
     *     info 发送状态说明
     */
    public function postStbInfo(){
        $result = array();
        $json_input = file_get_contents('php://input');
        if (empty($json_input)) {
            $this->errorCallback(404, "Error: did not transport datas!");
        }
        $jsonData = json_decode($json_input);
        $arrayData = $this->object_array($jsonData);

        $stbData=array();
        $stbData['model']=$arrayData['model'];
        $stbData['brand']=$arrayData['brand'];
        $stbData['board']=$arrayData['board'];
        $stbData['dev_desc']=$arrayData['dev_desc'];

        $stbData['firmware_version']=$arrayData['firmware_version'];
        $stbData['aaa_account']=$arrayData['aaa_account'];
        $stbData['aaa_passwd']=$arrayData['aaa_passwd'];

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

        $stbData['online'] = 1;
        $stbData['last_login_time'] = time();
        $stbData['last_login_ip'] = $this->get_client_ip();

        $stbData['hid']=$arrayData['hid'];
        $stbData['room']=$arrayData['room'];
        $stbData['mac']=$arrayData['mac'];

        $Device=D("Device");
        //$map['hid']=$arrayData['hid'];
        //$map['room']=$arrayData['room'];
        $map['mac']=$arrayData['mac'];
        $list=$Device->where($map)->select();
        if (empty($list)) {
            $stbData['status'] = 1;
            $deviceID=$Device->add($stbData);
        }else{
            $deviceID=$Device->where($map)->save($stbData);
        }
        $DeviceWifi = D("DeviceWifi");
        $wifiData=array();
        $wifiData['ssid'] = $arrayData['wifi_ssid'];
        if ($arrayData['wifi_psk_type']=="none") {
            $wifiData['security'] = 0;
        }else if($arrayData['wifi_psk_type']=="psk2"){
            $wifiData['security'] = 1;
        }
        $wifiData['password'] = $arrayData['wifi_passwd'];
        $wifiData['wifistatus'] = 1;
        $wifiData['result'] = 1;
        $wifiData['hid']=$arrayData['hid'];
        $wifiData['room']=$arrayData['room'];
        $wifiData['mac']=$arrayData['mac'];
        $wifiList=$DeviceWifi->where($map)->select();
        if (empty($wifiList)) {
            $wifiID=$DeviceWifi->add($wifiData);
        }else{
            $wifiID=$DeviceWifi->where($map)->save($wifiData);
        }
        if (($deviceID !== false) && ($wifiID !== false)) {
            $result['status'] = 200;
            $result['info'] = "Successed!";
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result);
        }else{
            $this->errorCallback(404, "Error: post error or update error!");
        }
    }
    /**
     * http request:/api.php/Api/getEventList
     * @author Blues
     * @uses 心跳例询,获取酒店事件更新表
     * @param hid 酒店编号
     * @param room 房间号
     * @param mac Mac地址
     * @var json
     * @return
     *     status 状态  （200成功   404失败）
     *     info 发送状态说明
     *     list 当前酒店事件列表
     *     --type 事件代码
     *     --update_time 事件更新时间
     */
    public function getEventList(){
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
        /*更新Device表 Start*/
        $Device = D("Device");
        $DeviceWifi = D("DeviceWifi");
        $deviceList = $Device->where('mac = "'.$mac.'"')->select();
        $deviceWifiList = $DeviceWifi->where('mac = "'.$mac.'"')->select();
        $data = array();
        $wifiData=array();
        $data['hid'] = $wifiData['hid'] = $hid;
        $data['mac'] =  $wifiData['mac'] = $mac;
        $data['room'] = $wifiData['room'] = $room;
        $data['online'] = 1;
        $data['last_login_time'] = time();
        $data['last_login_ip'] = $this->get_client_ip();
        if (empty($deviceList)) {
            $data['status'] = 1;
            $Device->add($data);
        }else{
            $Device->where('mac = "'.$mac.'"')->save($data);
        }
        if (empty($deviceWifiList)) {
            $DeviceWifi->add($wifiData);
        }else{
            $DeviceWifi->where('mac = "'.$mac.'"')->save($wifiData);
        }
        /*更新Device表 end*/
        $Event=D("Event");
        $map['hid']=$hid;
        $list=$Event->where($map)->field('type,update_time')->select();
        if (empty($list)) {
            $this->errorCallback(404, "Error: event is empty!");
        }
        $json['status'] = 200;
        $json['info'] = "Successed!";
        $json['list']=$list;
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
    private function execUrl($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $content = curl_exec($ch);
        return $content;
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
    public function get_client_ip_New($type = 0,$adv=false) {
        $type = $type ? 1 : 0;
        static $ip = NULL;
        if ($ip !== NULL) return $ip[$type];
        if($adv){
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown',$arr);
                if(false !== $pos) unset($arr[$pos]);
                $ip     =   trim($arr[0]);
            }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip     =   $_SERVER['HTTP_CLIENT_IP'];
            }elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip     =   $_SERVER['REMOTE_ADDR'];
            }
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        echo $ip[$type];
        return $ip[$type];
    }
    private function errorCallback($status,$info){
        $data['status'] = $status;
        $data['info'] = $info;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
    private function reCollectCityWeather($hid){
        $cityCode=$this->getCityCode($hid);
        $datetime = date('YmdHi');
        $areaid = $cityCode;
        $public_key = "http://webapi.weather.com.cn/data/?areaid=$areaid&type=forecast3d&date=$datetime&appid=9c6b98d6b9e7f42f";
        $appid = "9c6b98d6b9e7f42f";
        $private_key = "e0d331_SmartWeatherAPI_ee1b2e7";
        $key = urlencode(base64_encode(hash_hmac('sha1', $public_key, $private_key,true)));
        $url = "http://webapi.weather.com.cn/data/?areaid=$areaid&type=forecast3d&date=$datetime&appid=9c6b98&key=$key";
        $ch = curl_init ( );
        curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_POST, false );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec ( $ch );
        curl_close ( $ch );
         
        $data = json_decode($json);
        $dataArray = $this->object_array($data);
        $threeDayWeather = $dataArray['f']['f1'];
        $weatherdata = array();
        if (empty($dataArray)) {
            echo "Error : Curl Collect weather failed";
            exit;
        }else{
            $ctime = time();
            $today = date("Y-m-d",$ctime);
            $time = strtotime($today);
            for ($i = 0; $i < 3; $i++) {
                $weatherdata[$i]['high_c'] = $threeDayWeather[$i]['fc'];
                $weatherdata[$i]['low_c'] = $threeDayWeather[$i]['fd'];
                $weatherdata[$i]['date'] = $time+86400*$i;
                $weatherdata[$i]['weather'] = $threeDayWeather[$i]['fa'];
                $weatherdata[$i]['wind'] = $threeDayWeather[$i]['fg'];
            }
            $rn = "\n";
            $xml = '';
            $xml .= '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<data>';
            $xml .= '<version>1.0</version>';
            $xml .= '<content>';
            $xml .= '<city>';
            $xml .= '<name>'.$this->getCityName($cityCode).'</name>';
            $xml .= '<code>'.$cityCode.'</code>';
            $xml .= '<weathers>';
            foreach ($weatherdata as $wd) {
                $xml .= '<weather>';
                $xml .= '<date>'.date("Y-m-d",$wd["date"]).'</date>';
                $xml .= '<sign>'.$wd["weather"].'</sign>';
                $xml .= '<image>'.$wd["weather"].'.png</image>';
                $xml .= '<high_c>'.$wd["high_c"].'</high_c>';
                $xml .= '<low_c>'.$wd["low_c"].'</low_c>';
                $xml .= '<wind>'.$wd["wind"].'</wind>';
                $xml .= '<discription>'.$this->getWeatherDescription($wd['weather']).'</discription>';
                $xml .= '</weather>';
            }
            $xml .= '</weathers>';
            $xml .= '</city>';
            $xml .= '</content>';
            $xml .= '</data>';
            file_put_contents('./Public/weather/'.$cityCode.'.xml', $xml);
        }
    }
    private function getCityCode($hid){
        $Hotel=D("Hotel");
        $voHotel=$Hotel->getByHid($hid);
        $cityID=$voHotel['cityid'];
        $Region=D("Region");
        $c=$Region->getById($cityID);
        $cityCode=$c['code'];
        return $cityCode;
    }
    private function getCityName($cityCode){
        $Region = D("Region");
        $c = $Region->getByCode($cityCode);
        return $c['name'];
    }
    private function getWeatherDescription($weather){
        $weather = intval($weather);
        $name = '未知';
        switch ($weather) {
            case 0:
                $name = '晴';
                break;
            case 1:
                $name = '多云';
                break;
            case 2:
                $name = '阴';
                break;
            case 3:
                $name = '阵雨';
                break;
            case 4:
                $name = '雷阵雨';
                break;
            case 5:
                $name = '雷阵雨伴有冰雹';
                break;
            case 6:
                $name = '雨夹雪';
                break;
            case 7:
                $name = '小雨';
                break;
            case 8:
                $name = '中雨';
                break;
            case 9:
                $name = '大雨';
                break;
            case 10:
                $name = '暴雨';
                break;
            case 11:
                $name = '大暴雨';
                break;
            case 12:
                $name = '特大暴雨';
                break;
            case 13:
                $name = '阵雪';
                break;
            case 14:
                $name = '小雪';
                break;
            case 15:
                $name = '中雪';
                break;
            case 16:
                $name = '大雪';
                break;
            case 17:
                $name = '暴雪';
                break;
            case 18:
                $name = '雾';
                break;
            case 19:
                $name = '冻雨';
                break;
            case 20:
                $name = '沙尘暴';
                break;
            case 21:
                $name = '小到中雨';
                break;
            case 22:
                $name = '中到大雨';
                break;
            case 23:
                $name = '大到暴雨';
                break;
            case 24:
                $name = '暴雨到大暴雨';
                break;
            case 25:
                $name = '大暴雨到特大暴雨';
                break;
            case 26:
                $name = '小到中雪';
                break;
            case 27:
                $name = '中到大雪';
                break;
            case 28:
                $name = '大到暴雪';
                break;
            case 29:
                $name = '浮尘';
                break;
            case 30:
                $name = '扬沙';
                break;
            case 31:
                $name = '强沙尘暴';
                break;
            case 53:
                $name = '霾';
                break;
            default:
                $name = '无';
                break;
        }
        return $name;
    }
    //getWeatherXml内部调用(不一定使用)
    public function getWeatherXml(){
        $hid = $_REQUEST['hid'];
        $Hotel=D("Hotel");
        $voHotel=$Hotel->getByHid($hid);
        $cityID=$voHotel['cid'];
        $Region=D("Region");
        $c=$Region->getById($cityID);
        $cityCode=$c['code'];
        $xmlpath = "./Public/weather/".$cityCode."weather.xml";
        if (file_exists($xmlpath)) {
            $xml2 = simplexml_load_file($xmlpath);
            $mtime = filemtime($xmlpath);
            if (time()-$mtime>3600*3) {
                $this->reCollectCityWeather($hid);
            }else{
                if($xml2->content->city->code != $cityCode){
                    $this->reCollectCityWeather($hid);
                }
            }
        }else{
            $this->reCollectCityWeather($hid);
        }
        header('Content-Type: text/xml; charset=utf-8');
        echo file_get_contents($xmlpath);
    }
    /***************下方为2016-12-21增加的接口*******************/
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
     * @author Blues
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
                $json['image_path'] = 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER["SERVER_PORT"].__ROOT__.'/Public'.$imagelistbykey[$list['sleep_imageid']]['image_path'];
            }else{
                $json['image_path'] ='http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER["SERVER_PORT"]. __ROOT__.'/Public'.$imagelist[0]['image_path'];
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json);
    }
    //通过账号查询酒店信息
    public function getHotleinfoBymember(){
        $token = $_REQUEST['token'];
        if($token != 'zxtorder'){
            $this->errorCallback(404, "Error:bad param");
        }

        $hotel = D("hotel");

        $hotellist = $hotel->getField('id,hid,hotelname');
        
        $json = json_encode($hotellist);
        header('Content-Type: application/json; charset=utf-8');
        echo $json;
    }

    //查找appstore列表
    public function getApklist(){

        $hid = strtoupper(I('request.hid','','strip_tags'));
        $room = I('request.room','','strip_tags');
        $mac = strtoupper(I('request.mac','','strip_tags'));
        $app_type = I('request.app_type','','strip_tags');

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
        $listDo = D("appstore")->where($mapDo)->field($field)->order('app_version')->group('app_package')->select();
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
        
        // $arr['0']['app_version'] = 201709061714;
        // $arr['0']['app_package'] = 'com.tencent.mobileqq';
        // $arr['0']['status'] = 1;
        // $arr['1']['app_version'] = 201709061414;
        // $arr['1']['app_package'] = 'com.tencent.mobileqq';
        // $arr['1']['status'] = 1;
        // $arr['2']['app_version'] = 201709071111;
        // $arr['2']['app_package'] = 'com.duowan.mobile';
        // $arr['2']['status'] = 1;
        // $arr['3']['app_version'] = 201709072110;
        // $arr['3']['app_package'] = 'com.ucbrowser.tv';
        // $arr['3']['status'] = 0;
        // $apklist = json_encode($arr);
        

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

    // public function saveinstalledlist(){

    //     $hid = empty($_REQUEST['hid'])?"":strtoupper($_REQUEST["hid"]);
    //     $room = empty($_REQUEST['room'])?"":$_REQUEST["room"];
    //     $mac = empty($_REQUEST['mac'])?"":strtoupper($_REQUEST["mac"]);
    //     $apkid = empty($_REQUEST['apkid'])?"":$_REQUEST["apkid"];
    //     if (empty($hid)) {
    //         $this->Callback(10000, "Error: hid param is needed");
    //     }
    //     if (empty($room)) {
    //         $this->Callback(10000, "Error: room param is needed");
    //     }
    //     if (empty($mac)) {
    //         $this->Callback(10000, "Error: mac param is needed");
    //     }
    //     if (empty($apkid)) {
    //         $this->Callback(10000, "Error: apkid param is needed");
    //     }
    //     $data['install_apkid'] = implode(',',json_decode($apkid));
    //     $map['mac'] = $mac;
    //     $model = D("device_apk");

    //     if($model->where($map)->count()>0){
    //         $result = $model->where($map)->data($data)->save();
    //     }else{
    //         $data['apk_id'] = $data['install_apkid'];
    //         $result = $model->data($data)->add();
    //     }

    //     if($result === false){
    //         $this->callback(0,"Error,Can not add install apkid");
    //     }else{
    //         $this->callback(200,"Success");
    //     }
    // }

    private function Callback($status,$info){
        $data['status'] = $status;
        $data['data'] = $info;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }


    //2017-08-15 保持盒子在线接口
    public function keepDeviceStatus(){
        $hid = I('post.hid','','strtoupper,strip_tags');
        $room = I('post.room');
        $mac = strtoupper(I('post.mac'));
        if(!empty($mac)){
            $map['mac'] = $mac;
            $data['online'] = 1;
            $data['last_login_time'] = time();
            $result = D("device")->where($map)->setField($data);
            if($result !== false){
                $this->Callback(200,'Success');
            }else{
                $this->Callback(0,'Error');
            }
        }else{
            $this->Callback(10000,'the mac is empty');
        }
    }

    /***************lauch 网页端接口  2017.07.07*************/

    //获取酒店一级栏目 自身+集团通用+通用栏目
    public function getHotelCategory_firstlevel(){

        $hid = I('post.hid','','strtoupper');
        $room = I('post.room','','strip_tags');
        $mac = I('post.mac','','strtoupper');

        if(empty($hid)){
            $this->Callback(10000,'hid is empty');
        }
        //酒店自身
        $map['hid'] = $hid;
        $map['pid'] = 0;
        $map['status'] = 1;
        $listHotel = D("hotel_category")->where($map)->order('sort asc')->select();
        //集团通用栏目
        $listChg = array();
        $chglist = D("hotel_chglist")->where('hid="'.$hid.'"')->field('chg_cid')->select();
        if(!empty($chglist)){
            foreach ($chglist as $key => $value) {
                $chglist_arr[] = $value['chg_cid'];
            }
            $chgMap['id'] = array('in',$chglist_arr);
            $listChg = D("hotel_chg_category")->where($chgMap)->order('sort')->select();
        }
        //通用栏目
        $listTopic = array();
        $topiclist = D("hotel_topic")->where('hid="'.$hid.'"')->field('topic_id')->select();
        if(!empty($topiclist)){
            foreach ($topiclist as $k => $v) {
                $topiclist_arr[] = $v['topic_id'];
            }
            $topicMap['id'] = array('in',$topiclist_arr);
            $topicMap['status'] = 1;
            $listTopic = D("topic_group")->where($topicMap)->select();
        }

        if(!empty($listHotel)){
            $i = 0;
            foreach ($listHotel as $key => $value) {
                $list[$i]['id'] = $value['id']; 
                $list[$i]['name'] = $value['name']; 
                $list[$i]['pid'] = $value['pid']; 
                $list[$i]['icon'] = $value['icon']; 
                $list[$i]['hid'] = $value['hid']; 
                $list[$i]['modeldefineid'] = $value['modeldefineid'];
                $list[$i]['langcodeid'] = $value['langcodeid'];
                $list[$i]['sort'] = $value['sort'];
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
                $list[$j]['pid'] = $value['pid']; 
                $list[$j]['icon'] = $value['filepath']; 
                $list[$j]['hid'] = $value['hid'];
                $list[$j]['modeldefineid'] = $value['modeldefineid'];
                $list[$j]['langcodeid'] = $value['langcodeid'];
                $list[$j]['sort'] = $value['sort'];
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
                $list[$k]['pid'] = 0;
                $list[$k]['icon'] = $vv['icon'];
                $list[$k]['hid'] = $hid;
                $list[$k]['modeldefineid'] = 3;
                $list[$k]['langcodeid'] = 0;
                $list[$k]['sort'] = $vv['id'];
                $list[$k]['intro'] = $vv['intro'];
                $list[$k]['votype'] = "topic";
                $k++;
            }
        }

        if(!empty($list)){
            foreach ($list as $key => $value) {
                if($value['icon']){
                    $icon_arr = explode("/", $value['icon']);
                    $icon_name = $icon_arr[count($icon_arr)-1];
                    $list[$key]['icon'] = $icon_name;
                }else{
                    $list[$key]['icon'] = "";
                }
            }
            $this->Callback(200,$list);
        }else{
            $this->Callback(404,'the list is empty');
        }
    }

    //获取酒店自身二级栏目 自身+集团通用+通用栏目
    public function getHotelCategory_secondlevel(){

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
            $field = "zxt_hotel_category.id,zxt_hotel_category.hid,zxt_hotel_category.name,zxt_hotel_category.modeldefineid,zxt_hotel_category.langcodeid,zxt_hotel_category.pid,zxt_hotel_category.sort,zxt_hotel_category.intro,zxt_hotel_category.icon,zxt_modeldefine.codevalue,zxt_modeldefine.packagename,zxt_modeldefine.classname";
            $list = D("hotel_category")->where($map)->field($field)->join("zxt_modeldefine ON zxt_hotel_category.modeldefineid=zxt_modeldefine.id")->order('sort asc')->select();
        }elseif($votype == "chg"){
            $map['zxt_hotel_chg_category.status'] = 1;
            $map['zxt_hotel_chg_category.hid'] = $hid;
            $map['zxt_hotel_chg_category.pid'] = $pid;
            $field = "zxt_hotel_chg_category.id,zxt_hotel_chg_category.hid,zxt_hotel_chg_category.name,zxt_hotel_chg_category.modeldefineid,zxt_hotel_chg_category.langcodeid,zxt_hotel_chg_category.pid,zxt_hotel_chg_category.sort,zxt_hotel_chg_category.intro,zxt_hotel_chg_category.filepath as icon,zxt_modeldefine.codevalue,zxt_modeldefine.packagename,zxt_modeldefine.classname";
            $list = D("hotel_chg_category")->field($field)->where($map)->join("zxt_modeldefine ON zxt_hotel_chg_category.modeldefineid=zxt_modeldefine.id")->order('sort asc')->select();
        }elseif($votype == "topic"){
            $map['zxt_topic_category.status'] = 1;
            $map['zxt_topic_category.groupid'] = $pid;
            $field = "zxt_topic_category.id,zxt_topic_category.name,zxt_topic_category.modeldefineid,zxt_topic_category.langcodeid,zxt_topic_category.sort,zxt_topic_category.icon,zxt_modeldefine.codevalue,zxt_modeldefine.packagename,zxt_modeldefine.classname";
            $list = D("topic_category")->field($field)->where($map)->join("zxt_modeldefine ON zxt_topic_category.modeldefineid=zxt_modeldefine.id")->order('sort')->select();
        }else{
            $this->Callback(10000,"the votype is wrong!");
        }

        if(!empty($list)){
            foreach ($list as $key => $value) {
                if($value['icon']){
                    $icon_arr = explode("/", $value['icon']);
                    $icon_name = $icon_arr[count($icon_arr)-1];
                    $list[$key]['icon'] = $icon_name;
                }
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

    //获取二级栏目下的资源
    public function getHotelResource_second(){

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
                if(!empty($value['filepath'])){
                    $filepath_arr = explode("/", $value['filepath']);
                    $filepath_name = $filepath_arr[count($filepath_arr)-1];
                    $list[$key]['filepath'] = $filepath_name;
                }else{
                    $list[$key]['filepath'] = "";
                }
                if(!empty($value['video_image'])){
                    $filepath_arr = explode("/", $value['video_image']);
                    $filepath_name = $filepath_arr[count($filepath_arr)-1];
                    $list[$key]['video_image'] = $filepath_name;
                }else{
                    $list[$key]['video_image'] = "";
                }
                if($votype=="topic" && $value['file_type']==1 && !empty($value['video'])){
                    $$filepath_arr = explode("/", $value['video']);
                    $filepath_name = $filepath_arr[count($filepath_arr)-1];
                    $list[$key]['filepath'] = $filepath_name;
                }elseif($votype=="topic" && $value['file_type']==2 && !empty($value['image'])){
                    $filepath_arr = explode("/", $value['image']);
                    $filepath_name = $filepath_arr[count($filepath_arr)-1];
                    $list[$key]['filepath'] = $filepath_name;
                }elseif(empty($value['video']) && empty($value['image']) && $votype =="topic"){
                    $list[$key]['filepath'] = "";
                }
                $list[$key]['votype'] = $votype;
            }
            $this->Callback(200,$list);
        }else{
            $this->Callback(404,'the list is empty');
        }
    }

    //根据酒店hid获取xml文件
    public function getXmlResource(){
        $hid = I('request.hid','','strtoupper');
        $room = I('request.room','','strip_tags');
        $mac = I('request.mac','','strtoupper');
        if(empty($hid)){
            $this->Callback(10000,'hid is empty');
        }

        $filepath = self::$serverUrl.'/upload/resourceXml/'.$hid.'.txt';
        $file = dirname(dirname(dirname(dirname(__FILE__)))).'/Public/upload/resourceXml/'.$hid.'.txt';
        if(file_exists($file)){
            $vo['md5_file'] = md5_file($filepath);
            $vo['filepath'] = $filepath;
            $this->Callback(200,$vo);
        }else{
            $this->Callback(404,'hid is wrong');
        }
    }

    public function getAllresourcepathPrefix(){
        $url = self::$serverUrl;
        $this->Callback(200,$url);
    }

    public function getHotelWebLauncher(){
        $hid = I('post.hid','','strtoupper');
        $mac = I('post.mac','','strip_tags');
        $room = I('post.room','','strip_tags');

        if(empty($hid)){
            $this->Callback(10000,'the hid is empty');
        }
        $field = "zxt_hotel.hid,zxt_hotel.launcher_skinid,zxt_hotel_launcher_web.filename,zxt_hotel_launcher_web.web_name";
        $map['zxt_hotel.hid'] = $hid;
        $map['zxt_hotel_launcher_web.status'] = 1;

        $hotel = D("hotel")->where($map)->field($field)->join('zxt_hotel_launcher_web ON zxt_hotel.launcher_skinid=zxt_hotel_launcher_web.id')->find();
        if(!empty($hotel['filename'])){
            $filepath = self::$serverUrl.$hotel['filename'];
            //拼压缩包名称
            $zipName_arr = explode("/",$filepath);
            $zipName = $zipName_arr[count($zipName_arr)-1];
            $name = strrev(substr(strrev($zipName), 4));
            $suffix = $name."/html/".$hotel['web_name'].".html";
            $data['suffix'] = $suffix;
            $data['filepath'] = $filepath;
            $this->Callback(200,$data);
        }else{
            $this->Callback(404,"the skin is empty");
        }
    }


    //2017.08.03  新增弹窗广告接口
    
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

    //2017-08-25  查询所有视频轮播资源
    public function getVideoCarousel(){
        $hid = I('post.hid');
        if(empty($hid)){
            $this->Callback(10000,'the hid is empty');
        }
        $list = array();
        $map['hid'] = strtoupper($hid);
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

}
?>
