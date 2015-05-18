<?php

class ProjectManager
{
	public static function createProject($argProjectName=''){
		$conName = PROJECT_NAME."Configure";
		debug('$argProjectName='.$argProjectName);
		$samplePackage = $conName::SAMPLE_PROJECT_PACKAGE_PATH;
		$newProjectName = str_replace('Package', '', ucfirst($argProjectName.basename($samplePackage)));
		debug('$newProjectName='.$newProjectName);
		// 移動先のパス
		$movePath = dirname($conName::PROJECT_ROOT_PATH).'/'.$newProjectName.'Package';
		debug('$movePath='.$movePath);
		if(!dir_copy($samplePackage, $movePath)){
			return FALSE;
		}
		// プロジェクト名が指定されている場合は、デフォルトの定義を書き換えて上げる為の処理
		if('' !== $argProjectName){
			// config.xmlのファイル名を書き換える
			$newConfigXMLPath = $movePath.'/core/' . $newProjectName . '.config.xml';
			rename($movePath . '/core/Project.config.xml', $newConfigXMLPath);
			// package.xmlのファイル名を書き換える
			rename($movePath . '/core/Project.package.xml', $movePath.'/core/' . $newProjectName . '.package.xml');
			// config.xml内のプロジェクト名を書き換える
			$configXMLStr = file_get_contents($newConfigXMLPath);
			$configXMLStr = str_replace(array('<Project>', '</Project>'), array('<'.$newProjectName.'>', '</'.$newProjectName.'>'), $configXMLStr);
			// 新しい定義で書き換え
			file_put_contents($newConfigXMLPath, $configXMLStr);
			// 重いのでコマメにunset
			unset($configXMLStr);
			// installer内のプロジェクト名を書き換える
			// 			$installerStr = file_get_contents($movePath.'/installer/index.php');
			// 			$installerStr = str_replace('$projectpkgName = "Project";', '$projectpkgName = "'.ucfirst($newProjectName).'";', $installerStr);
			$peoductNameDefinedLine = 'define("PROJECT_NAME", "'.ucfirst($newProjectName).'");';

			// インストーラーからみたフレームワークのパスを書き換える
			$baseFrameworkPath = dirname(Configure::CORE_PATH);
			self::resolveProjectInstaller($newProjectName, $movePath);

			// RESRAPI-index内のプロジェクト名を書き換える
			$apidocPath = $movePath.'/apidocs';
			$apiIndexStr = file_get_contents($apidocPath.'/index.php');
			$apiIndexStr = str_replace('$projectpkgName = "Project";', '$projectpkgName = "'.ucfirst($newProjectName).'";', $apiIndexStr);
			// 新しい定義で書き換え
			file_put_contents($movePath.'/apidocs/index.php', $apiIndexStr);
			// 重いのでコマメにunset
			unset($apiIndexStr);
			// iOSサンプル内のプロジェクト内のRESTfulAPIの向け先を帰る
			$iosdefineStr = file_get_contents($movePath.'/iOSSample/Project/SupportingFiles/define.h');
			// レビュー用の暫定処理
			$basePath = str_replace('/'.PROJECT_NAME.'/', '|', $_SERVER["REQUEST_URI"]);
			$basePaths = explode('|', $basePath);
			$iosdefineStr = str_replace('# define URL_BASE @"/workspace/UNICORN/src/lib/FrameworkManager/template/managedocs/"', '# define URL_BASE @"'.$basePaths[0].'/'.$newProjectName.'Package/apidocs/'.'"', $iosdefineStr);
			// 新しい定義で書き換え
			file_put_contents($movePath.'/iOSSample/Project/SupportingFiles/define.h', $iosdefineStr);
			// 重いのでコマメにunset
			unset($iosdefineStr);
		}
		return TRUE;
	}

	public static function resolveProjectInstaller($argTargetProjectName, $argInstallerBasePath){
		// プロジェクト名の定義設定
		$newProjectName = $argTargetProjectName;
		if(0 < strpos($argTargetProjectName, 'Package')){
			$newProjectName = substr($argTargetProjectName, 0, -7);
		}
		$peoductNameDefinedLine = 'define("PROJECT_NAME", "'.$newProjectName.'");';
		// インストーラーからみたフレームワークのパスを書き換える
		$baseFrameworkPath = dirname(Configure::CORE_PATH);
		$paths = explode('/', str_replace('//','/', $argInstallerBasePath.'/installer'));
		// パスが一致するところまでさかのぼり、それを新たなルートパスとし、そこを基準にFrameworkのパスを設定しなおす
		$tmpPath = "/";
		for($tmpPathIdx=0, $pathIdx=1; $pathIdx <= count($paths); $pathIdx++){
			// 空文字は無視
			if(isset($paths[$pathIdx]) && strlen($paths[$pathIdx]) > 0){
				if(0 === strpos($baseFrameworkPath, $tmpPath.$paths[$pathIdx])){
					$tmpPath .= $paths[$pathIdx]."/";
					$tmpPathIdx++;
					// パスが一致したので次へ
					continue;
				}
				else{
					// 一致しなかったので、この前までが一致パスとする
					break;
				}
			}
		}
		$depth = count($paths) - $tmpPathIdx;
		$dirnameStr = '__FILE__';
		for($dirnameIdx=0; $dirnameIdx < $depth; $dirnameIdx++){
			$dirnameStr = 'dirname('.$dirnameStr.')';
		}
		$frameworkPathStr = '$frameworkPath = '.str_replace($tmpPath, $dirnameStr.'."/', $baseFrameworkPath).'";';
		debug($baseFrameworkPath);
		debug($tmpPath);
		debug($dirnameStr.'."/');
		$projectPathStr = '$projectPath = '.str_replace($tmpPath, $dirnameStr.'."/', dirname(getConfig(PROJECT_ROOT_PATH)).'/'.$newProjectName.'Package').'";';
		$fwmgrPathStr = '$fwmgrPath = "'.substr(getConfig(PROJECT_ROOT_PATH), 0, -1).'";';
		debug($frameworkPathStr);
		debug($projectPathStr);
		debug($fwmgrPathStr);
		
		$targetLine1Num = 15;
		$targetLine2Num = 20;
		$targetLine4Num = 23;
		$targetLine3Num = 26;
		$readLine = 0;

		// 新しい定義で書き換え
		debug($argInstallerBasePath);
		$handle = fopen($argInstallerBasePath.'/installer/index.php', 'r');
		$file='';
		while (($buffer = fgets($handle, 4096)) !== false) {
			$readLine++;
			if($targetLine1Num === $readLine){
				// 置換処理
				$file .= $peoductNameDefinedLine . PHP_EOL;
			}
			elseif($targetLine2Num === $readLine){
				// 置換処理
				$file .= $frameworkPathStr . PHP_EOL;
			}
			elseif($targetLine3Num === $readLine){
				// 置換処理
				$file .= $fwmgrPathStr . PHP_EOL;
			}
			elseif($targetLine4Num === $readLine){
				// 置換処理
				$file .= $projectPathStr . PHP_EOL;
			}
			else {
				$file .= $buffer;
			}
		}
		fclose($handle);
		file_put_contents($argInstallerBasePath.'/installer/index.php', $file);
	}
}

?>