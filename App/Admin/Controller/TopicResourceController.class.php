<?php
// +-------------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://www.hangthink.com All rights reserved.
// +-------------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +-------------------------------------------------------------------------
// | Author: Blues
// +-------------------------------------------------------------------------
// | Description: 专题资源控制器
// +-------------------------------------------------------------------------
namespace Admin\Controller;
use Admin\Controller\ComController;
class TopicResourceController extends ComController {
    public function _map(){
        $map = array();
        $resMap=array();
        if (!empty($_REQUEST['keyword'])) {
            $map['name']=array("like","%".$_REQUEST['keyword']."%");
        }
        if (!empty($_REQUEST['groupid'])) {
            $map['groupid'] = $_REQUEST['groupid'];
            $this->assign("groupId",$_REQUEST['groupid']);
        }else{
            $this->assign("groupId",'');
        }
        if (!empty($_REQUEST['langcodeid'])) {
            $map['langcodeid'] = $_REQUEST['langcodeid'];
            $this->assign("langId",$_REQUEST['langcodeid']);
        }else{
            $this->assign("langId",'');
        }
        if (!empty($map)) {
            $TopicCategory = D("TopicCategory");
            $list=$TopicCategory->where($map)->select();
            if (empty($list)) {
                return false;
            }else{
                $i=0;
                foreach ($list as $n){
                    $Ids[$i] = $n['id'];
                    $i++;
                }
            }
            $resMap['cid'] = array("in",$Ids);
        }
        return $resMap;
    }

    public function index(){
        $model = D("TopicResource");
        $map = $this->_map();
        if($map === false){
            $list = null;
        }else{
            $list = $this->_list($model, $map, 10 ,"cid asc,sort asc");
        }
        $this -> assign("list",$list);
        $this->display();
    }
    public function _before_index(){
        $this->_assign_list();
    }
    public function _assign_list(){
        $TopicGroup = D ('TopicGroup');
        $groupList = $TopicGroup->field ('id,title')->order('id asc')->select();
        $this->assign('groupList', $groupList);
        $Langcode = D ('Langcode');
        $langList = $Langcode->field ('id,name')->order('id asc')->select();
        $this->assign('langList', $langList);
        $Modeldefine = D ('Modeldefine');
        $modeList = $Modeldefine->where('codevalue="102" or codevalue="103" ')->field ('id,name')->order('codevalue asc')->select();
        $this->assign('modeList', $modeList);
    }
}