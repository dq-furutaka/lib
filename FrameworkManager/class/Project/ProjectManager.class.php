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
			//$peoductNameDefinedLine = 'define("PROJECT_NAME", "'.ucfirst($newProjectName).'");';

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
			// iOSサンプル内のプロジェクト内のRESTfulAPIの向け先を変える
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

	public static function migrateAppModel($argTargetProjectName, $argTargetPlatform){
		$DBO = DBO::sharedInstance(getConfig('DB_DSN', $argTargetProjectName));
		$tables = $DBO->getTables();
		for ($tblIdx=0; $tblIdx < count($tables); $tblIdx++){
			// テーブル毎にマイグレーション
			$tableName = strtolower($tables[$tblIdx]);
			$modelName = str_replace(' ', '', ucwords(str_replace('_', ' ', $tableName)));
			$describes = $DBO->getTableDescribes($tables[$tblIdx]);
			if(is_array($describes) && count($describes) > 0){
				if ('iOS' === $argTargetPlatform){
					$headerfile = file_get_contents(getConfig('SAMPLE_PROJECT_PACKAGE_PATH', PROJECT_NAME).'/core/emptyModel.h');
					$modelfile = file_get_contents(getConfig('SAMPLE_PROJECT_PACKAGE_PATH', PROJECT_NAME).'/core/emptyModel.m');
					$protected = '';
					$public = '';
					$synthesize = '';
					$flags = '';
					$accesser = '';
					$init = '';
					$save = '';
					$convert = '';
					$set = '';
					$reset = '';
					foreach($describes as $colName => $describe){
						if ('id' !== $colName){
							$protected .= '    NSString *'.$colName.';'.PHP_CR.PHP_LF;
							$public .= '@property (strong, nonatomic) NSString *'.$colName.';'.PHP_CR.PHP_LF;
							$synthesize .= '@synthesize '.$colName.';'.PHP_CR.PHP_LF;
							$flags .= '    BOOL '.$colName.'_replaced;'.PHP_CR.PHP_LF;
							$accesser .= '-(void)set'.ucfirst($colName).':(NSString *)arg'.ucfirst($colName).PHP_CR.PHP_LF;
							$accesser .= '{'.PHP_CR.PHP_LF;
							$accesser .= '    '.$colName.' = arg'.ucfirst($colName).';'.PHP_CR.PHP_LF;
							$accesser .= '    '.$colName.'_replaced = YES;'.PHP_CR.PHP_LF;
							$accesser .= '    replaced = YES;'.PHP_CR.PHP_LF;
							$accesser .= '}'.PHP_CR.PHP_LF.PHP_CR.PHP_LF;
							$init .= '        '.$colName.'_replaced = NO;'.PHP_CR.PHP_LF;
							$save .= '        if(YES == '.$colName.'_replaced){'.PHP_CR.PHP_LF;
							$save .= '            [saveParams setValue:self.'.$colName.' forKey:@"'.$colName.'"];'.PHP_CR.PHP_LF;
							$save .= '        }'.PHP_CR.PHP_LF;
							$convert .= '    [newDic setObject:self.'.$colName.' forKey:@"'.$colName.'"];'.PHP_CR.PHP_LF;
							$set .= '    self.'.$colName.' = [argDataDic objectForKey:@"'.$colName.'"];'.PHP_CR.PHP_LF;
							$reset .= '    '.$colName.'_replaced = NO;'.PHP_CR.PHP_LF;
						}
						else {
							$convert .= '    [newDic setObject:self.ID forKey:@"id"];'.PHP_CR.PHP_LF;
							$set .= '    self.ID = [argDataDic objectForKey:@"id"];'.PHP_CR.PHP_LF;
						}
					}
					$headerfile = str_replace('%modelName%', $modelName, $headerfile);
					$headerfile = str_replace('%protected%', $protected, $headerfile);
					$headerfile = str_replace('%public%', $public, $headerfile);
					$modelfile = str_replace('%modelName%', $modelName, $modelfile);
					$modelfile = str_replace('%tableName%', $tableName, $modelfile);
					$modelfile = str_replace('%flags%', $flags, $modelfile);
					$modelfile = str_replace('%synthesize%', $synthesize, $modelfile);
					$modelfile = str_replace('%accesser%', $accesser, $modelfile);
					$modelfile = str_replace('%init%', $init, $modelfile);
					$modelfile = str_replace('%save%', $save, $modelfile);
					$modelfile = str_replace('%convert%', $convert, $modelfile);
					$modelfile = str_replace('%set%', $set, $modelfile);
					$modelfile = str_replace('%reset%', $reset, $modelfile);
					file_put_contents(getConfig('PROJECT_ROOT_PATH', $argTargetProjectName).'/'.$argTargetPlatform.'Sample/Project/Classes/Model/'.$modelName.'ModelBase.h', $headerfile);
					file_put_contents(getConfig('PROJECT_ROOT_PATH', $argTargetProjectName).'/'.$argTargetPlatform.'Sample/Project/Classes/Model/'.$modelName.'ModelBase.m', $modelfile);
				}
				elseif ('android' === $argTargetPlatform){
					
				}
				elseif ('cocos' === $argTargetPlatform){
					
				}
				elseif ('swift' === $argTargetPlatform){
					
				}
				elseif ('cocosjs' === $argTargetPlatform){
					
				}
			}
		}
		return TRUE;
	}
}

?>