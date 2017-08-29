<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 后台公共文件
// +-------------------------------------------------------------------------

/**
 *
 * 函数：日志记录
 * @param  string $log   日志内容。
 * @param  string $name （可选）用户名。
 *
 **/
function addlog($log,$name=false){
    $Model = M('log');
    if(!$name){
        $sessionData = session(session_id());
        $data['name'] = $sessionData['user'];
    }else{
        $data['name'] = $name;
    }
    $data['t'] = time();
    $data['ip'] = $_SERVER["REMOTE_ADDR"];
    $data['log'] = $log;
    $Model->data($data)->add();
}
/**
 *
 * 函数：审核发布日志记录
 * @param  string $log   日志内容。
 * @param  string $resouce_type   资源类型。
 * @param  string $type   审核 发布类型  1审核 2发布。
 * @param  string $name （可选）用户名。
 *
 **/
function addAuditlog($log,$resouce_type,$type,$name=false){
    $Model = D('AuditLog');
    if(!$name){
        $sessionData = session(session_id());
        $data['name'] = $sessionData['user'];
    }else{
        $data['name'] = $name;
    }
    $data['time'] = time();
    $data['ip'] = $_SERVER["REMOTE_ADDR"];
    $data['log'] = $log;
    $data['resource_type'] = $resouce_type;
    $data['type'] = $type;
    $Model->data($data)->add();
}
/**
 *
 * 函数：获取用户信息
 * @param  int $uid      用户ID。
 * @param  string $name  数据列（如：'uid'、'uid,user'）
 *
 **/
function member($uid,$field=false) {
    $model = M('Member');
    if($field){
        return $model ->field($field)-> where(array('uid'=>$uid)) -> find();
    }else{
        return $model -> where(array('uid'=>$uid)) -> find();
    }
}
/**
 * TODO 基础分页的相同代码封装，使前台的代码更少
 * @param $count 要分页的总记录数
 * @param int $pagesize 每页查询条数
 * @return \Think\Page
 */
function getpage($count, $pagesize = 10) {
    $p = new Think\Page($count, $pagesize);
    $p->setConfig('header', '<li><a>共<b>%TOTAL_ROW%</b>条记录&nbsp;第<b>%NOW_PAGE%</b>页/共<b>%TOTAL_PAGE%</b>页</a></li>');
    //$p->setConfig('prev', '上一页');
    //$p->setConfig('next', '下一页');
    //$p->setConfig('last', '末页');
    //$p->setConfig('first', '首页');
    $p->setConfig('theme', '%HEADER%%FIRST%%UP_PAGE%%LINK_PAGE%%DOWN_PAGE%%END%');
    //$p->lastSuffix = false;//最后一页不显示为总页数
    return $p;
}
//根据HID返回酒店名称
function getHotelnameByHid($hid) {
    $Hotel = D("Hotel");
    $vo=$Hotel->getByHid($hid);
    if (empty($vo['hotelname'])) {
        return "-";
    }else{
        return $vo['hotelname'];
    }
}
//根据ID返回酒店业务名称
function getHotelCustomerNameById($id) {
    $Hotel = D("Hotel");
    $vo=$Hotel->getById($id);
    if (empty($vo['name'])) {
        return "-";
    }else{
        return $vo['name'];
    }
}
//获取行政区划名称
function get_regionname($regionid) {
    $Region = D("Region");
    $vo=$Region->getById($regionid);
    if (empty($vo['name'])) {
        return "-";
    }else{
        return $vo['name'];
    }
}
//获取皮肤名称
function get_skin_by_id($id) {
    $HotelSkin = D("HotelSkin");
    $vo=$HotelSkin->getById($id);
    if (empty($vo['name'])) {
        return "-";
    }else{
        return $vo['name'];
    }
}
//获取当前酒店在线设备数
function get_online_device($hid) {
    $Device = D("Device");
    $count=$Device->where('hid="'.$hid.'"')->count();
    $oneMinuteAgo=time()-C('DEVICE_ONLINE_TIME');//设备在线判断（120秒）
    $online_count=$Device->where('hid="'.$hid.'" and last_login_time>'.$oneMinuteAgo)->count();
    return $online_count.'&nbsp;/&nbsp;'.$count;
}

//新方法  获取当前设备在线数目
function get_online_device_online($hid){
    $Device = D("Device");
    $count=$Device->where('hid="'.$hid.'"')->count();
    $online_count=$Device->where('hid="'.$hid.'" and online=1')->count();
    return $online_count.'&nbsp;/&nbsp;'.$count;
}


//返回审核状态
function get_audit_status($status) {
    switch ($status) {
        case 0 :
            $show = '<span class="label label-inverse arrowed">待审核</span>';
            break;
        case 1 :
            $show = '<span class="label label-danger arrowed">审核未通过</span>';
            break;
        case 2 :
            $show = '<span class="label label-primary arrowed">审核通过</span>';
            break;
        case 3 :
            $show = '<span class="label label-danger arrowed">发布未通过</span>';
            break;
        case 4 :
            $show = '<span class="label label-success arrowed">发布通过</span>';
            break;
        case -1 :
            $show = '<span class="label label-inverse arrowed">未提交审核</span>';
            break;
        case -2 :
            $show = '<span class="label label-inverse arrowed">无需审核</span>';
            break;
        default:
            $show = '<span class="label label-warning arrowed">未知</span>';
            break;
    }
    return $show;
}
//返回状态
function get_status($status) {
    switch ($status) {
        case 0 :
            $show = '<span class="label label-warning arrowed">禁用</span>';
            break;
        case 1 :
            $show = '<span class="label label-info arrowed">启用</span>';
            break;
        default:
            $show = '<span class="label label-danger arrowed">未知</span>';
            break;
    }
    return $show;
}
//返回显示状态
function get_status_show($status) {
    switch ($status) {
        case 0 :
            $show = '<span class="label label-warning arrowed">隐藏</span>';
            break;
        case 1 :
            $show = '<span class="label label-info arrowed">显示</span>';
            break;
        default:
            $show = '<span class="label label-danger arrowed">未知</span>';
            break;
    }
    return $show;
}
//返回资源文件类型名称
function get_resource_filetype($type) {
    switch ($type) {
        case 0 :
            $show = '<span class="label label-success arrowed">图片</span>';
            break;
        case 1 :
            $show = '<span class="label label-info arrowed">视频</span>';
            break;
        case 2 :
            $show = '<span class="label label-success arrowed">图片</span>';
            break;
        default:
            $show = '<span class="label label-danger arrowed">未知</span>';
            break;
    }
    return $show;
}
//返回资源归档名称
function get_resource_group($type) {
    switch ($type) {
        case 'content' :
            $show = '<span class="label label-warning arrowed">栏目资源</span>';
            break;
        case 'welcome' :
            $show = '<span class="label label-success arrowed">欢迎图片</span>';
            break;
        case 'spread' :
            $show = '<span class="label label-primary arrowed">酒店宣传</span>';
            break;
        case 'jump' :
            $show = '<span class="label label-info arrowed">跳转视频</span>';
            break;
        default:
            $show = '<span class="label label-danger arrowed">未知</span>';
            break;
    }
    return $show;
}
//返回语言定义名称
function getLangcodeNameById($id){
    $Langcode = D("Langcode");
    $vo=$Langcode->getById($id);
    if (empty($vo['name'])) {
        return "-";
    }else{
        return $vo['name'];
    }
}
//返回模型定义名称
function getModeldefineNameById($id){
    $Modeldefine = D("Modeldefine");
    $vo=$Modeldefine->getById($id);
    if (empty($vo['name'])) {
        return "-";
    }else{
        return $vo['name'];
    }
}
//返回栏目名称
function getCategoryNameById($id){
    $HotelCategory = D("HotelCategory");
    $vo=$HotelCategory->getById($id);
    if (empty($vo['name'])) {
        return "-";
    }else{
        return $vo['name'];
    }
}
//在线状态
function get_is_online($status) {
    switch ($status) {
        case 0 :
            $show = '<span class="label label-warning arrowed">离线</span>';
            break;
        case 1 :
            $show = '<span class="label label-info arrowed">在线</span>';
            break;
    }
    return $show;
}
//返回指令执行结果1成功0失败-1待执行
function get_command_result($command_result) {
    switch ($command_result) {
        case -1 :
            $show = '<span class="label label-warning arrowed">待执行</span>';
            break;
        case 0 :
            $show = '<span class="label label-danger arrowed">失败</span>';
            break;
        case 1 :
            $show = '<span class="label label-success arrowed">成功</span>';
            break;
        default:
            $show = '<span class="label label-info arrowed">未知</span>';
            break;
    }
    return $show;
}
//返回指令名称
function get_command_name($command) {
    switch ($command) {
        case "modify_para" :
            $show = '<span class="label label-purple arrowed">下推参数</span>';
            break;
        case "reboot" :
            $show = '<span class="label label-pink arrowed">重启</span>';
            break;
        default:
            $show = '<span class="label label-danger arrowed">未知</span>';
            break;
    }
    return $show;
}
//返回升级状态
function get_upgrade_status($status) {
    switch ($status) {
        case 1 :
            $show = '<span class="label label-primary arrowed">开始下载</span>';
            break;
        case 2 :
            $show = '<span class="label label-pink arrowed">下载成功</span>';
            break;
        case 3 :
            $show = '<span class="label label-info arrowed">校验失败</span>';
            break;
        case 4 :
            $show = '<span class="label label-warning arrowed">升级失败</span>';
            break;
        case 5 :
            $show = '<span class="label label-success arrowed">升级成功</span>';
            break;
        default:
            $show = '<span class="label label-danger arrowed">未知</span>';
            break;
    }
    return $show;
}
//返回专题名称
function getTopicNameById($id){
    $model = D("TopicGroup");
    $vo=$model->getById($id);
    if (empty($vo['title'])) {
        return "/";
    }else{
        return $vo['title'];
    }
}
//返回类型
function getResourceModelType($type) {
    switch ($type) {
        case 0 :
            $show = '<font color="blue">Logo</font>';
            break;
        case 1 :
            $show = '<font color="blue">视频</font>';
            break;
        case 2 :
            $show = '<font color="blue">图片</font>';
            break;
    }
    return $show;
}
function extract_title($str, $count) {
    return mb_substr($str, 0, $count, 'utf-8');
}
//过多字符省略显示
function shorter_title($str) {
    return mb_strlen($str, 'UTF8') <= 35 ? $str : extract_title($str, 35) . "…";
}

//返回    专题+模型+语言+栏目
function getTopicCategory($cid){
    $TopicCategory = D("TopicCategory");
    $TopicGroup = D("TopicGroup");
    $Modeldefine = D("Modeldefine");
    $Langcode=D("Langcode");
    $vo=$TopicCategory->getById($cid);
    if (empty($vo['name'])) {
        $cat="";
    }else {
        $cat=$vo['name'];
    }
    $voGroup=$TopicGroup->getById($vo['groupid']);
    if(empty($voGroup)){
        $title= '[/]';
    }else{
        $title='['.$voGroup['title'].']';
    }
    $voModel=$Modeldefine->getById($vo['modeldefineid']);
    if (empty($voModel)) {
        $type="[/]";
    }else if ($voModel['codevalue']=="102") {
        $type="[图片]";
    }else if($voModel['codevalue']=="103"){
        $type="[视频]";
    }else{
        $type="[/]";
    }
    $voLang=$Langcode->getById($vo['langcodeid']);
    if (empty($voLang)) {
        $lang="[/]";
    }else{
        $lang="[".$voLang['name']."]";
    }
    return $title.'&nbsp;<font color="blue">'.$type.'</font>&nbsp;<font color="red">'.$lang.'</font>&nbsp;'.$cat;
}

//返回运行日志的中文状态名称
function getNameByDeviceLogStatus($status){
    switch ($status) {
        case 0 :
            $show = '<span class="label label-success arrowed">升级成功</span>';
            break;
        case 1 :
            $show = '<span class="label label-warning arrowed">校验失败</span>';
            break;
        case 2 :
            $show = '<span class="label label-danger arrowed">安装失败</span>';
            break;
        case 3 :
            $show = '<span class="label label-info arrowed">下载安装包</span>';
            break;
        case 4 :
            $show = '<span class="label label-primary arrowed">开机例询</span>';
            break;
        default:
            $show = '<span class="label label-primary arrowed">未知</span>';
            break;
    }
    return $show;
}

//返回是审核还是发布的中文状态名称
function getAuditresourcetype($resourcetype){
    switch ($resourcetype) {
        case '1':
            $show = '<span class="label label-success arrowed">内容审核</span>';
            break;
        case '2':
            $show = '<span class="label label-success arrowed">专题审核</span>';
            break;
        case '3':
            $show = '<span class="label label-success arrowed">广告审核</span>';
            break;
        case '4':
            $show = '<span class="label label-success arrowed">系统软件审核</span>';
            break;
        case '5':
            $show = '<span class="label label-success arrowed">路由固件审核</span>';
            break;
        case '6':
            $show = '<span class="label label-success arrowed">应用软件审核</span>';
            break;
        case '7':
            $show = '<span class="label label-success arrowed">APK审核</span>';
            break;
        case '11':
            $show = '<span class="label label-success arrowed">内容发布</span>';
            break;
        case '12':
            $show = '<span class="label label-success arrowed">专题发布</span>';
            break;
        case '13':
            $show = '<span class="label label-success arrowed">广告发布</span>';
            break;
        case '14':
            $show = '<span class="label label-success arrowed">系统软件发布</span>';
            break;
        case '15':
            $show = '<span class="label label-success arrowed">路由固件发布</span>';
            break;
        case '16':
            $show = '<span class="label label-success arrowed">应用软件发布</span>';
            break;
        case '17':
            $show = '<span class="label label-success arrowed">APK发布</span>';
            break;
        default:
            # code...
            break;
    }
    return $show;
}
//MB转KB
function mbToKb($mb){
    return $KB = $mb*1024;
}