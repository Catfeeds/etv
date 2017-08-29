<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 日志控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class LogController extends ComController {
    //系统后台操作日志
    public function syslog($p=1){
        $p = isset($_GET['p'])?intval($_GET['p']):'1';
        $t = time()-3600*24*30;
        $log = M('log');
        $log->where("t < $t")->delete();//删除30天前的日志
        $pagesize = 10;#每页数量
        $offset = $pagesize*($p-1);//计算记录偏移量
        $count = $log->count();
        $list = $log->order('id desc')->limit($offset.','.$pagesize)->select();
        $this->assign('list',$list);
        $page = getpage($count,$pagesize);
        $this->assign('page', $page->show());
        $this -> display();
    }
    //删除系统后台操作日志
    public function delete(){
        $model = M('log');
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
            $this->success('恭喜，删除成功！');
        }else{
            $this->error('删除失败，参数错误！');
        }
    }
    //删除所有系统后台操作日志
    public function deleteall(){
        $Dao = M();
        $Dao->execute("truncate table `zxt_log`");
        $this->success('恭喜，操作成功！');
    }
}