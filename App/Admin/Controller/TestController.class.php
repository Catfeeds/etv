<?php
namespace Admin\Controller;
use Admin\Controller\ComController;

use Vendor\File;

class TestController extends comController {

	public function filetest(){

		$File = new File;
		$rootfile = dirname(dirname(dirname(dirname(__FILE__)))).'/Public/upload/content/';
		var_dump($rootfile);
		$a = $File::copy_dir($rootfile.'/4008/',$rootfile.'/lockin/');
		var_dump($a);
	}

	//移动文件和更改字段sql
	public function movefilepath(){
		
		$File = new File;
		// $map['id'] = array('not in',array(87,89,91,93,94,95,100,101,102,103,104,105));
		$map = array();
		$list = D("hotel_chg_category")->where($map)->field('id,hid,filepath')->select();
		$rootfile = dirname(dirname(dirname(dirname(__FILE__)))).'/Public';
		$destfile = dirname(dirname(dirname(dirname(__FILE__)))).'/Public/upload/content/';
		foreach ($list as $key => $value) {
			if(!empty($value['filepath'])){
				$arr = explode("/", $value['filepath']);
				$filename = $arr['4'];
				$source = $rootfile.$value['filepath'];
				$dest = $destfile.$value['hid'].'/'.$filename;
				$changefilepath[$value['id']] = '/upload/content/'.$value['hid'].'/'.$filename;
				if(!is_dir($destfile.$value['hid'])){
					mkdir($destfile.$value['hid']);
				}
				$copyresult = copy($source, $dest);//移动文件
				var_dump($copyresult);
			}
		}
		$ids = implode(",", array_keys($changefilepath));
		$sql = "UPDATE zxt_hotel_chg_category SET filepath = CASE id";
		foreach ($changefilepath as $id => $value) {
			$sql .= sprintf(" WHEN %d THEN '%s'",$id,$value);
		}
		$sql .= "END WHERE id IN($ids)";
		var_dump("------------------------------------");
		$result = D("hotel_chg_category")->execute($sql);
		var_dump($result);
	}


	public function get_sql_temp(){
		$sql = "SHOW VARIABLES LIKE 'tmpdir'";
		$result = M()->query($sql);
		dump($result);
	}

	public function get_qcache(){
		$sql = "SHOW STATUS LIKE 'Qcache%'";
		$result = M()->query($sql);
		dump($result);
	}

	public function in_xls(){

		//导入PHPExcel类库，因为PHPExcel没有用命名空间，只能inport导入
		import("Org.Util.PHPExcel");

		//要导入的xls文件，位于根目录下的Public文件夹
		$filename=dirname(__FILE__)."/2.xls";

		//创建PHPExcel对象，注意，不能少了\
		$PHPExcel=new \PHPExcel();

		//如果excel文件后缀名为.xls，导入这个类
		import("Org.Util.PHPExcel.Reader.Excel5");
		//如果excel文件后缀名为.xlsx，导入这下类
		//import("Org.Util.PHPExcel.Reader.Excel2007");
		//$PHPReader=new \PHPExcel_Reader_Excel2007();

		$PHPReader=new \PHPExcel_Reader_Excel5();

		//载入文件
		$PHPExcel=$PHPReader->load($filename);

		//获取表中的第一个工作表，如果要获取第二个，把0改为1，依次类推
		$currentSheet=$PHPExcel->getSheet(0);

		//获取总列数
		$allColumn=$currentSheet->getHighestColumn();

		//获取总行数
		$allRow=$currentSheet->getHighestRow();

		//循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
		for($currentRow=1;$currentRow<=$allRow;$currentRow++){
			//从哪列开始，A表示第一列
			for($currentColumn='A';$currentColumn<=$allColumn;$currentColumn++){
				//数据坐标
				$address=$currentColumn.$currentRow;
				//读取到的数据，保存到数组$arr中
				$arr[$currentRow][$currentColumn]=$currentSheet->getCell($address)->getValue();

			}
		}

		dump($arr);

	}

	public function out_excel(){
		//导入PHPExcel类库，因为PHPExcel没有用命名空间，只能inport导入
		import("Org.Util.PHPExcel");

		//创建PHPExcel对象，注意，不能少了\
		$excel=new \PHPExcel();

		//Excel表格式,这里简略写了8列
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'F', 'G');
		//表头数组
        $tableheader = array('酒店', '标题', '内容');
		//填充表头信息
        for ($i = 0; $i < count($tableheader); $i++)
        {
            $excel->getActiveSheet()->setCellValue("$letter[$i]1", "$tableheader[$i]");
        }

        //在这里调用你要导出的数据
		// $MsgModel = M("Msg"); // 实例化Msg对象
		// $list = $MsgModel->select();

        // 表格数组
       	$data = array(
			array('1', '小王', '男', '20', '100'),
			array('2', '小李', '男', '20', '101'),
			array('3', '小张', '女', '20', '102'),
			array('4', '小赵', '女', '20', '103')
       	);
        // $data = $list;
		//填充表格信息
		for ($d=1; $d <3 ; $d++) { 
			$excel->createSheet();
			$excel->setactivesheetindex($d);
	        for ($i = 2; $i <= count($data) + 1; $i++)
	        {
				$j = 0;
				foreach ($data[$i - 2] as $key => $value)
				{
					$excel->getActiveSheet()->setCellValue("$letter[$j]$i", "$value");
					$j++;
				}
	        }
		}

		//创建Excel输入对象
		$write = new \PHPExcel_Writer_Excel5($excel);
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
		header("Content-Type:application/force-download");
		header("Content-Type:application/vnd.ms-execl");
		header("Content-Type:application/octet-stream");
		header("Content-Type:application/download");
		header('Content-Disposition:attachment;filename="testdata.xls"');
		header("Content-Transfer-Encoding:binary");
		$write->save('php://output');
	}

}
