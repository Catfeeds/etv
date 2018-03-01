<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 参数上传日志控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class DeviceParaLogController extends ComController {
    //查询
    public function _map(){
        $map = array();
        if (!empty($_GET['mac'])) {
            $map['mac'] = array("LIKE","%{$_GET['mac']}%");
        }
        return $map;
    }
    public function index(){

        $list = array();
        if(empty($_GET['mac'])){
            $count = M("device_para_log")->count();
            if(empty($_GET['p'])){
                $sql = "select * from zxt_device_para_log order by id desc limit 10";
            }else{
                $getpage = $_GET['p'];
                $idcount = ($getpage-1)*10;
                $sql = "select * from (select id from zxt_device_para_log order by id desc limit ".$idcount." ,10)a left join zxt_device_para_log b on a.id=b.id";
            }
        }else{
            if (empty($_GET['p'])) {
                $idcount = 0;
            }else{
                $getpage = $_GET['p'];
                $idcount = ($getpage-1)*10;
            }

            $sql = 'select * from zxt_device_para_log where mac='.'"'.$_GET['mac'].'" order by id desc limit '.$idcount.' ,10';
            $map['mac'] = $_GET['mac'];
            $count = D("device_para_log")->where($map)->count();
        }
        $list = M("device_para_log")->query($sql);
        $Page = new\Think\Page($count,10,$_GET);
        $pageshow = $Page -> show();
        $this->assign('theAllcount',$count);
        $this -> assign("page",$pageshow);//赋值分页输出
                                          
        $this -> assign("list",$list);
        $this -> assign("vo",$list[0]);
        $this -> display();
    }
   
    public function detail(){
        $model = M("device_para_log");
        $list = $model->getById($_REQUEST['id']);
        $list['boot_time'] =date("Y-m-d H:i:s",$list['boot_time']);
        $list['post_time'] =date("Y-m-d H:i:s",$list['post_time']);
        echo json_encode($list);
    }
    //删除参数上传日志
    public function delete(){
        $model = M('device_para_log');
        $ids = isset($_REQUEST['ids'])?$_REQUEST['ids']:false;
        if(is_array($ids)){
            if(!$ids){
                $this->error('参数错误！');
            }
            foreach($ids as $k=>$v){
                $ids[$k] = intval($v);
            }
            $map['id']  = array('in',$ids);
        }else{
            $map['id']  = $ids;
        }
        if($model->where($map)->delete()){
            if(is_array($ids)){
                $ids = implode(',',$ids);
            }
            addlog('删除参数上传日志表数据，数据ID：'.$ids);
            $this->success('恭喜，删除成功！');
        }else{
            $this->error('删除失败，参数错误！');
        }
    }
    //删除所有参数上传日志
    public function deleteall(){
        $Dao = M();
        $Dao->execute("truncate table `zxt_device_para_log`");
        addlog('清空参数上传日志表所有数据');
        $this->success('恭喜，操作成功！');
    }
    // 删除最近一周的上传参数日志
    public function deleteweek(){
        $time = time()-60*60*24*7;
        $map['post_time'] = array('ELT',$time);
        $result = D("device_para_log")->where($map)->delete();
        if($result!==false){
            $this->success('删除日志成功');
        }else{
            $this->error('删除日志失败');
        }
    }

}