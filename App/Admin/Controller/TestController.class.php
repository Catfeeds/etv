<?php
namespace Admin\Controller;

Vendor('apkparser.examples.autoload');

use Think\Controller;

class TestController extends Controller {

	public function index(){
		
		$apk = new \ApkParser\Parser('EBHS.apk');

		$manifest = $apk->getManifest();
		$permissions = $manifest->getPermissions();

		echo '<pre>';
		echo "Package Name      : " . $manifest->getPackageName() . "" . PHP_EOL;
		echo "Version           : " . $manifest->getVersionName() . " (" . $manifest->getVersionCode() . ")" . PHP_EOL;
		echo "Min Sdk Level     : " . $manifest->getMinSdkLevel() . "" . PHP_EOL;
		echo "Min Sdk Platform  : " . $manifest->getMinSdk()->platform . "" . PHP_EOL;
		echo PHP_EOL;
		echo "------------- Permssions List -------------" . PHP_EOL;
	}
}
