<?php

/**
 * ADODBを利用したDBクラス
 *
 * Singletonパターン実装
 * @see <a href="http://adodb.sourceforge.net/">ADOdb</a>
 */
class GenericDBO {

	/**
	 * @var array DBインスタンス保持用
	 */
	private static $_DBInstance = NULL;

	/**
	 * @var array DSN情報保持用
	 */
	private static $_DSN = NULL;

	/**
	 * @var array トランザクション保存用
	 */
	private static $_transaction = NULL;

	/**
	 * @var string インスタンス化されて使用された場合、DSN情報を取っておく
	 */
	public $DSN = NULL;

	/**
	 * @var string インスタンス化されて使用された場合、DSN情報のハッシュ値を取っておく
	 */
	public $dbidentifykey = NULL;

	/**
	 * @var string インスタンス化されて使用された場合の、読み込み専用DSN情報を取っておく
	 */
	public $readableDSN = NULL;

	/**
	 * @var string インスタンス化されて使用された場合、読み込み専用DSN情報のハッシュ値を取っておく
	 */
	public $dbreadableidentifykey = NULL;

	/**
	 * インスタンス化されて使用された場合、DBType情報を取っておく
	 * <ul><li>mysql</li><li>oracle</li><li>postgres</li></ul>
	 * @var string
	 */
	public $DBType = NULL;

	/**
	 * DBインスタンスを取得
	 * @param string $argDSN DSN
	 * @return GenericDBO DB
	 */
	public static function sharedInstance($argDSN="Default", $argReadable=FALSE){
		static $DBO = array();
		if(!isset($DBO[$argDSN.(string)$argReadable])){
			$DSN = $argDSN;
			if("Default" === $DSN){
				$DSN = NULL;
			}
			$DBO[$argDSN.(string)$argReadable] = new DBO($DSN, $argReadable);
		}
		return $DBO[$argDSN.(string)$argReadable];
	}

	/**
	 * インスタンス化対応
	 * @param string $argDSN DSN
	 */
	public function __construct($argDSN=NULL, $argReadable=FALSE){
		if(NULL !==$argDSN && strlen($argDSN) > 0){
			$this->DSN = $argDSN;
			$this->dbidentifykey = sha1($argDSN);
		}
		self::_initDB($argReadable);
		if(0 === strpos($this->DSN, "mysql")){
			$this->DBType = "mysql";
		}
		elseif(0 === strpos($this->DSN, "postgres")){
			$this->DBType = "postgres";
		}
		elseif(0 === strpos($this->DSN, "oci")){
			$this->DBType = "oracle";
		}
	}

	/**
	 * 該当get_called_classのDBインスタンスの初期化
	 */
	private function _initDB($argReadable=FALSE){
		$dsn = NULL;
		if(TRUE === @property_exists($this, "DSN") && isset($this->DSN) && NULL !== $this->DSN && strlen($this->DSN) > 0 && FALSE === (TRUE === $argReadable && TRUE === @property_exists($this, "readableDSN") && NULL === $this->readableDSN)){
			// 与えられたDSN情報を使用する
			$dsn = $this->DSN;
			$calledClassName = strtolower($this->dbidentifykey);
			logging("initDB readable=".(string)$argReadable, "query");
			if (TRUE === $argReadable && FALSE !== $this->readableDSN){
				$dsn = $this->readableDSN;
				$calledClassName = strtolower($this->dbreadableidentifykey);
				logging("initDB readableDSN=".$dsn, "query");
			}
// 			$calledClassName = "default";
// 			if(TRUE === @property_exists($this, "dbidentifykey")){
// 				$calledClassName = strtolower($this->dbidentifykey);
// 				$this->dbidentifykey = $calledClassName;
// 			}
		}
		else{
			// フレームワークの機能を使ったDSNの自動解決処理
			// 継承元クラス名を取得(静的クラス名を取るためにget_called_classを使用する)
			$calledClassName = @get_called_class();
			if(0 == strlen($calledClassName)){
				// もしもの時の為
				$calledClassName = "default";
			}

			// 継承元クラス名に応じたDSN定義を取得
			$ProjectConfigure = NULL;
			if(defined('PROJECT_NAME') && strlen(PROJECT_NAME) > 0 && class_exists(PROJECT_NAME . 'Configure')){
				$ProjectConfigure = PROJECT_NAME . 'Configure';
			}
			$readabledsn = NULL;
			if(NULL !== $ProjectConfigure && NULL !== $ProjectConfigure::constant('DB_DSN')){
				$dsn = $ProjectConfigure::DB_DSN;
				if (TRUE === $argReadable){
					if(NULL !== $ProjectConfigure::constant('DB_DSN_READABLE')){
						$readabledsn = $ProjectConfigure::DB_DSN_READABLE;
					}
					elseif(NULL !== $ProjectConfigure::constant('DB_DSN_READREPLICA')){
						$readabledsn = $ProjectConfigure::DB_DSN_READREPLICA;
					}
					elseif(NULL !== $ProjectConfigure::constant('DB_DSN_REPLICA')){
						$readabledsn = $ProjectConfigure::DB_DSN_REPLICA;
					}
				}
			}
			elseif(0 < strlen($calledClassName) && __CLASS__  != $calledClassName && class_exists("Configure") && NULL !== Configure::constant(strtoupper($calledClassName) . "_DSN")){
				$dsn = Configure::constant(strtoupper($calledClassName) . "_DSN");
				if (TRUE === $argReadable){
					if(NULL !== Configure::constant(strtoupper($calledClassName) . "_DSN_READABLE")){
						$readabledsn = Configure::constant(strtoupper($calledClassName) . "_DSN_READABLE");
					}
					elseif(NULL !== Configure::constant(strtoupper($calledClassName) . "_DSN_READREPLICA")){
						$readabledsn = Configure::constant(strtoupper($calledClassName) . "_DSN_READREPLICA");
					}
					elseif(NULL !== Configure::constant(strtoupper($calledClassName) . "_DSN_REPLICA")){
						$readabledsn = Configure::constant(strtoupper($calledClassName) . "_DSN_REPLICA");
					}
				}
			}
			elseif(class_exists("Configure") && NULL !== Configure::constant("DB_DSN")){
				$dsn = Configure::DB_DSN;
				if (TRUE === $argReadable){
					if(NULL !== Configure::constant("DB_DSN_READABLE")){
						$readabledsn = Configure::DB_DSN_READABLE;
					}
					elseif(NULL !== Configure::constant("DB_DSN_READREPLICA")){
						$readabledsn = Configure::DB_DSN_READREPLICA;
					}
					elseif(NULL !== Configure::constant("DB_DSN_REPLICA")){
						$readabledsn = Configure::DB_DSN_REPLICA;
					}
				}
			}
			elseif (defined("DB_DSN")){
				// 定数を使う
				$dsn = DB_DSN;
				if (TRUE === $argReadable){
					if (defined("DB_DSN_READABLE")){
						// 定数を使う
						$readabledsn = DB_DSN_READABLE;
					}
					elseif (defined("DB_DSN_READREPLICA")){
						// 定数を使う
						$readabledsn = DB_DSN_READREPLICA;
					}
					elseif (defined("DB_DSN_REPLICA")){
						// 定数を使う
						$readabledsn = DB_DSN_REPLICA;
					}
				}
			}
			$calledClassName = sha1($dsn);
			if(TRUE === @property_exists($this, "DSN")){
				$this->DSN = $dsn;
			}
			if(TRUE === @property_exists($this, "dbidentifykey")){
				$this->dbidentifykey = sha1($dsn);
			}

			if (TRUE === $argReadable){
				if (NULL !== $readabledsn){
					$calledClassName = sha1($readabledsn);
					if(TRUE === @property_exists($this, "readableDSN")){
						$this->readableDSN = $readabledsn;
					}
					if(TRUE === @property_exists($this, "dbreadableidentifykey")){
						$this->dbreadableidentifykey = sha1($readabledsn);
					}
					// 置き換え
					$dsn = $readabledsn;
					logging("initDB first readableDSN=".$dsn, "query");
				}
				else {
					$this->readableDSN = FALSE;
					$argReadable = FALSE;
				}
			}
		}

		if(!(isset(self::$_DBInstance[$calledClassName]) && is_object(self::$_DBInstance[$calledClassName]))) {
			$DBInstance = ADONewConnection($dsn);
			self::$_DBInstance[$calledClassName] = TRUE;
			if(false === $DBInstance){
				// DBインスタンス初期化エラー
				throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
			}
			if (FALSE === $argReadable){
				// XXX Pconnectの場合、直前のエラー終了プロセスのコネクションがコミットされずに残っていて
				// それを再利用してしまうケースがあるので、まず再利用コネクションをロールバックする事に注意！！！
				$DBInstance->RollbackTrans();
			}
			// インスタンスの保持
			self::$_DBInstance[$calledClassName] = $DBInstance;
			self::$_DSN[$calledClassName] = $dsn;
			// MySQlはSET NAMES utf8する
			if(0 === strpos($dsn,'mysql') && FALSE === self::$_DBInstance[$calledClassName]->Execute("SET NAMES utf8")){
				throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
			}
			// OracleはALTER SESSIONする
			if(0 === strpos($dsn,'oci') && FALSE === self::$_DBInstance[$calledClassName]->Execute("ALTER SESSION SET NLS_DATE_FORMAT = 'yyyy-mm-dd hh24:mi:ss'")){
				throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__);
			}
		}
		return $calledClassName;
	}

	/**
	 * クエリー実行とDBインスタンスの初期化を同時に行う
	 * @param string $argQuery クエリ
	 * @param array $argBinds バインド
	 * @return 実行結果
	 */
	public function execute($argQuery, $argBinds = NULL, $argAutoReadable = TRUE){
		$instanceIndex = self::_initDB();
		// MYSQLの時にBindを変換してあげる処理
		if(NULL !== $argBinds && is_array($argBinds)){
			$dsn = self::$_DSN[$instanceIndex];
			if(0 === strpos($dsn, "mysql")){
				$keys = array_keys($argBinds);
				// Bindがキー=値で来ているかどうか
				if(0 < count($keys) && TRUE === is_string($keys[0])){
					$newBinds = array();
					$pattern = "/:([a-zA-Z0-9\_\-]+?)\s/";
					$matches = NULL;
					$argQuery = " ".$argQuery." ";
					while(1 === preg_match($pattern, $argQuery, $matches)){
						$newBinds[] = $argBinds[$matches[1]];
						$argQuery = preg_replace($pattern, "? ", $argQuery, 1);
						$matches = NULL;
					}
					// ?の個数に対応した新しいBindsに置き換え
					$argBinds = $newBinds;
				}
			}
		}
		// オートトランザクション
		if(FALSE !== strpos(strtolower($argQuery), "update") || FALSE !== strpos(strtolower($argQuery), "insert") || FALSE !== strpos(strtolower($argQuery), "delete")){
			self::begin();
		}
		else if (0 === strpos(strtolower(trim($argQuery)), "select") || 0 === strpos(strtolower(trim($argQuery)), "show")){
			// リーダブルDBを探してみる
			$instanceIndex = self::_initDB($argAutoReadable);
			logging("initDB use readableDSN=".$instanceIndex, "query");
			logging("initDB readable query=".$argQuery, "query");
		}
		self::$_DBInstance[$instanceIndex]->SetFetchMode(ADODB_FETCH_ASSOC);
		if(NULL === $argBinds){
			// 新ADODB用の対応
			$argBinds = FALSE;
		}
		logging($instanceIndex, "query");
		logging(self::$_DSN[$instanceIndex], "query");
		logging($argQuery . PHP_EOL . var_export($argBinds, TRUE), "query");
		$response = self::$_DBInstance[$instanceIndex]->Execute($argQuery, $argBinds);
		$responseBool = FALSE;
		if(FALSE !== $response){
			$responseBool = TRUE;
		}
		// logging
		//logging(array('db'=> __METHOD__, 'query'=>$argQuery, 'binds'=>$argBinds, 'response'=>$responseBool), 'db');
		return $response;
	}

	/**
	 * クエリー実行とDBインスタンスの初期化を同時に行う
	 * 件数指定版execute
	 */
	public function selectLimit($argQuery, $argRows, $argOffset=1, $argBinds = NULL){
		$instanceIndex = self::_initDB(TRUE);
		$response = self::$_DBInstance[$instanceIndex]->SelectLimit($argQuery, $argRows, $argOffset, $argBinds);
		// logging
		//logging(array('db'=> __METHOD__, 'query'=>$argQuery, 'binds'=>$argBinds, 'rows'=>$argRows, 'offset'=>$argOffset, 'response'=>$response), 'db');
		return $response;
	}

	/**
	 * テーブル一覧の取得
	 */
	public function getTables(){
		$instanceIndex = self::_initDB(TRUE);
		$dsn = self::$_DSN[$instanceIndex];
		if(0 === strpos($dsn, "mysql")){
			// mysql
			$sql = 'SHOW TABLE STATUS';
		}
		elseif(0 === strpos($dsn, "postgres")){
			// postgres
			$sql = 'SELECT * FROM pg_tables WHERE NOT tablename LIKE \'pg%\' ORDER BY tablename';
		}
		elseif(0 === strpos($dsn,"oci")){
			// oracle
			$sql = 'SELECT table_name FROM user_tables';
		}
		else {
			// 未対応のDBエンジン
			return FALSE;
		}

		$response = self::execute($sql);
		if(FALSE === $response){
			return $response;
		}
		else{
			$tables = array();
			$basetables = $response->GetAll();
			debug($basetables);
			for($idx=0; $idx < count($basetables); $idx++){
				$keys = array_keys($basetables[$idx]);
				$tables[$idx] = array('name', 'row', 'comment');
				for ($kidx=0; $kidx < count($keys); $kidx++){
					$tables[$idx][strtolower($keys[$kidx])] = $basetables[$idx][$keys[$kidx]];
					if (FALSE !== strpos(strtolower($keys[$kidx]), 'name')){
						$tables[$idx]['name'] = $basetables[$idx][$keys[$kidx]];
					}
					if (FALSE !== strpos(strtolower($keys[$kidx]), 'row') && FALSE === strpos($keys[$kidx], '_')){
						$tables[$idx]['row'] = $basetables[$idx][$keys[$kidx]];
					}
					if (FALSE !== strpos(strtolower($keys[$kidx]), 'comment')){
						$tables[$idx]['comment'] = $basetables[$idx][$keys[$kidx]];
					}
				}
			}
			return $tables;
		}
	}

	/**
	 * テーブル定義の取得
	 */
	public function getTableDescribes($argTable){
		$instanceIndex = self::_initDB(TRUE);
		$dsn = self::$_DSN[$instanceIndex];
		if(0 === strpos($dsn, "mysql")){
			// MySQL
			$sql = "SHOW FULL COLUMNS FROM `".strtolower($argTable)."`";
			$response = self::execute($sql);
			if(FALSE === $response){
				return  $response;
			}else{
				$describes = array();
				$baseDescribes = $response->GetAll();
				for($baseDescribeNum=0; count($baseDescribes) > $baseDescribeNum; $baseDescribeNum++){
					$describes[$baseDescribes[$baseDescribeNum]["Field"]] = array();
					$describes[$baseDescribes[$baseDescribeNum]["Field"]]["type"] = $baseDescribes[$baseDescribeNum]["Type"];
					$describes[$baseDescribes[$baseDescribeNum]["Field"]]["min-length"] = 1;

					if("NO" === $baseDescribes[$baseDescribeNum]["Null"]){
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] = FALSE;
					}
					elseif("YES" === $baseDescribes[$baseDescribeNum]["Null"]){
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] = TRUE;
					}
					if(FALSE !== strpos($baseDescribes[$baseDescribeNum]["Type"], "char")){
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["type"] = "string";
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = FALSE;
						if(TRUE === $describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] && NULL === $baseDescribes[$baseDescribeNum]["Default"]){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = NULL;
						}
						if(TRUE !== $describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] && NULL !== $baseDescribes[$baseDescribeNum]["Default"]){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = $baseDescribes[$baseDescribeNum]["Default"];
						}
						$matches = NULL;
						preg_match("/\(([0-9\,]+)\)/", $baseDescribes[$baseDescribeNum]["Type"], $matches);
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["length"] = $matches[1];
						if(0 === strpos($baseDescribes[$baseDescribeNum]["Type"], "char")){
							// char型は固定長なのでmin-lengthは要らない
							unset($describes[$baseDescribes[$baseDescribeNum]["Field"]]["min-length"]);
						}
					}
					elseif(FALSE !== strpos($baseDescribes[$baseDescribeNum]["Type"], "blob")){
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["type"] = "blob";
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = FALSE;
						if(TRUE === $describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] && NULL === $baseDescribes[$baseDescribeNum]["Default"]){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = NULL;
						}
						if(TRUE !== $describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] && NULL !== $baseDescribes[$baseDescribeNum]["Default"]){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = $baseDescribes[$baseDescribeNum]["Default"];
						}
					}
					elseif(FALSE !== strpos($baseDescribes[$baseDescribeNum]["Type"], "decimal")){
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["type"] = "decimal";
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = FALSE;
						if(TRUE === $describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] && NULL === $baseDescribes[$baseDescribeNum]["Default"]){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = NULL;
						}
						if(TRUE !== $describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] && NULL !== $baseDescribes[$baseDescribeNum]["Default"]){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = $baseDescribes[$baseDescribeNum]["Default"];
						}
						$matches = NULL;
						preg_match("/\(([0-9\,]+)\)/", $baseDescribes[$baseDescribeNum]["Type"], $matches);
						if (isset($matches[1])){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["length"] = $matches[1];
						}
					}
					elseif(FALSE !== strpos($baseDescribes[$baseDescribeNum]["Type"], "float")){
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["type"] = "float";
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = FALSE;
						if(TRUE === $describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] && NULL === $baseDescribes[$baseDescribeNum]["Default"]){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = NULL;
						}
						if(TRUE !== $describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] && NULL !== $baseDescribes[$baseDescribeNum]["Default"]){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = $baseDescribes[$baseDescribeNum]["Default"];
						}
						$matches = NULL;
						preg_match("/\(([0-9\,]+)\)/", $baseDescribes[$baseDescribeNum]["Type"], $matches);
						if (isset($matches[1])){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["length"] = $matches[1];
						}
					}
					elseif(FALSE !== strpos($baseDescribes[$baseDescribeNum]["Type"], "bigint")){
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["type"] = "bigint";
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = FALSE;
						if(TRUE === $describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] && NULL === $baseDescribes[$baseDescribeNum]["Default"]){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = NULL;
						}
						if(TRUE !== $describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] && NULL !== $baseDescribes[$baseDescribeNum]["Default"]){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = $baseDescribes[$baseDescribeNum]["Default"];
						}
						$matches = NULL;
						preg_match("/\(([0-9\,]+)\)/", $baseDescribes[$baseDescribeNum]["Type"], $matches);
						if (isset($matches[1])){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["length"] = $matches[1];
						}
					}
					elseif(FALSE !== strpos($baseDescribes[$baseDescribeNum]["Type"], "longtext")){
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["type"] = "longtext";
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = FALSE;
						if(TRUE === $describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] && NULL === $baseDescribes[$baseDescribeNum]["Default"]){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = NULL;
						}
						if(TRUE !== $describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] && NULL !== $baseDescribes[$baseDescribeNum]["Default"]){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = $baseDescribes[$baseDescribeNum]["Default"];
						}
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["length"] = 4294967295;
					}
					elseif(FALSE !== strpos($baseDescribes[$baseDescribeNum]["Type"], "mediumtext")){
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["type"] = "mediumtext";
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = FALSE;
						if(TRUE === $describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] && NULL === $baseDescribes[$baseDescribeNum]["Default"]){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = NULL;
						}
						if(TRUE !== $describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] && NULL !== $baseDescribes[$baseDescribeNum]["Default"]){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = $baseDescribes[$baseDescribeNum]["Default"];
						}
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["length"] = 16777215;
					}
					elseif(FALSE !== strpos($baseDescribes[$baseDescribeNum]["Type"], "tinytext")){
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["type"] = "tinytext";
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = FALSE;
						if(TRUE === $describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] && NULL === $baseDescribes[$baseDescribeNum]["Default"]){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = NULL;
						}
						if(TRUE !== $describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] && NULL !== $baseDescribes[$baseDescribeNum]["Default"]){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = $baseDescribes[$baseDescribeNum]["Default"];
						}
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["length"] = 255;
					}
					elseif(FALSE !== strpos($baseDescribes[$baseDescribeNum]["Type"], "text")){
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["type"] = "text";
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = FALSE;
						if(TRUE === $describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] && NULL === $baseDescribes[$baseDescribeNum]["Default"]){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = NULL;
						}
						if(TRUE !== $describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] && NULL !== $baseDescribes[$baseDescribeNum]["Default"]){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = $baseDescribes[$baseDescribeNum]["Default"];
						}
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["length"] = 65535;
					}
					elseif(FALSE !== strpos($baseDescribes[$baseDescribeNum]["Type"], "int")){
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["type"] = "int";
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = FALSE;
						if(TRUE === $describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] && NULL === $baseDescribes[$baseDescribeNum]["Default"]){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = NULL;
						}
						if(TRUE !== $describes[$baseDescribes[$baseDescribeNum]["Field"]]["null"] && NULL !== $baseDescribes[$baseDescribeNum]["Default"]){
							$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = (int)$baseDescribes[$baseDescribeNum]["Default"];
						}
						$matches = NULL;
						preg_match("/\(([0-9\,]+)\)/", $baseDescribes[$baseDescribeNum]["Type"], $matches);
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["length"] = $matches[1];
					}
					elseif(FALSE !== strpos($baseDescribes[$baseDescribeNum]["Type"], "date") || FALSE !== strpos($baseDescribes[$baseDescribeNum]["Type"], "time")){
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["type"] = "date";
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["default"] = FALSE;
					}
					$describes[$baseDescribes[$baseDescribeNum]["Field"]]["pkey"] = FALSE;
					if("PRI" === $baseDescribes[$baseDescribeNum]["Key"]){
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["pkey"] = TRUE;
					}
					$describes[$baseDescribes[$baseDescribeNum]["Field"]]["autoincrement"] = FALSE;
					if("auto_increment" === $baseDescribes[$baseDescribeNum]["Extra"]){
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["autoincrement"] = TRUE;
					}
					// コメントがあったら入れておく
					if(isset($baseDescribes[$baseDescribeNum]["Comment"])){
						$describes[$baseDescribes[$baseDescribeNum]["Field"]]["comment"] = $baseDescribes[$baseDescribeNum]["Comment"];
					}

				}
				return $describes;
			}
		}
		elseif(0 === strpos($dsn, "postgres")){
			// PostgreSQL
			$sql = NULL;
			$sql .= "SELECT ";
			$sql .= " att.attnum AS NUM, ";
			$sql .= " att.attname AS COL_NAME, ";
			$sql .= " typ.typname AS COL_TYPE, ";
			$sql .= " CASE typ.typname ";
			$sql .= "  WHEN 'varchar'   THEN att.atttypmod-4 ";
			$sql .= "  WHEN 'bpchar'    THEN att.atttypmod-4 ";
			$sql .= "  WHEN 'numeric'   THEN att.atttypmod/65536 ";
			$sql .= "  WHEN 'date'      THEN att.attlen ";
			$sql .= "  WHEN 'timestamp' THEN att.attlen ";
			$sql .= " END AS COL_LENGTH, ";
			$sql .= " CASE att.attnotnull ";
			$sql .= "  WHEN 't' THEN 'NOT NULL' ";
			$sql .= "  WHEN 'f' THEN 'NULL' ";
			$sql .= " END AS NOTNULL, ";
			$sql .= " com.description AS COL_COMMENT ";
			$sql .= "FROM ";
			$sql .= " pg_attribute att LEFT JOIN pg_description com ON att.attrelid = com.objoid AND att.attnum = com.objsubid, ";
			$sql .= " pg_stat_user_tables sut, ";
			$sql .= " pg_type typ, ";
			$sql .= "WHERE ";
			$sql .= " att.attrelid = sut.relid ";
			$sql .= "AND ";
			$sql .= " att.atttypid = typ.oid ";
			$sql .= "AND ";
			$sql .= " att.attnum > 0 ";
			$sql .= "AND ";
			$sql .= " sut.relname = '" . $argTable . "' ";
			$sql .= "ORDER BY ";
			$sql .= " att.attnum ";
			$response = self::execute($sql);
			if(FALSE === $response){
				return  $response;
			}else{
				$describes = array();
				$baseDescribes = $response->GetAll();
			}
		}
		elseif(0 === strpos($dsn,"oci")){
			// oracle
			// XXX oracleだけコメント定義の取り方が解らなかった・・・
			$sql = "DESCRIBE ".$argTable;
			$response = self::execute($sql);
			if(FALSE === $response){
				return  $response;
			}else{
				$describes = array();
				$baseDescribes = $response->GetAll();
			}
		}
		else{
			// 未対応のDBエンジン
			return FALSE;
		}
	}

	/**
	 * BLOBのUpdate
	 */
	public function updateBlob($argTable,$argKey,$argVal,$argWhere){
		$instanceIndex = self::_initDB();
		if(NULL === self::$_DBInstance[$instanceIndex]) {
			return FALSE;
		}
		$response = self::$_DBInstance[$instanceIndex]->UpdateBlob($argTable,$argKey,$argVal,$argWhere);
		//logging(array('db'=> __METHOD__, 'table'=>$argTable, 'key'=>$argKey, 'response'=>$response), 'db');
		return $response;
	}

	/**
	 * CLOBのUpdate
	 */
	public function updateClob($argTable,$argKey,$argVal,$argWhere){
		$instanceIndex = self::_initDB();
		if(NULL === self::$_DBInstance[$instanceIndex]) {
			return FALSE;
		}
		$response = self::$_DBInstance[$instanceIndex]->UpdateClob($argTable,$argKey,$argVal,$argWhere);
		//logging(array('db'=> __METHOD__, 'table'=>$argTable, 'key'=>$argKey, 'response'=>$response), 'db');
		return $response;
	}

	/**
	 * 最後の実行クエリーのエラーを取得する
	 * インスタンスがある時だけ実行する
	 * 無いときはNULLを返す
	 */
	public function getLastErrorMessage(){
		$instanceIndex = self::_initDB();
		if(NULL !== self::$_DBInstance[$instanceIndex]){
			return self::$_DBInstance[$instanceIndex]->ErrorMsg();
		}
		return NULL;
	}

	/**
	 * クエリー実行とDBインスタンスの初期化を同時に行う
	 */
	public function begin(){
		$instanceIndex = self::_initDB();
		if(TRUE !== self::$_transaction[$instanceIndex]){
			$res = self::$_DBInstance[$instanceIndex]->BeginTrans();
			self::$_transaction[$instanceIndex] = TRUE;
			return $res;
		}
		return TRUE;
	}

	/**
	 * クエリー実行とDBインスタンスの初期化を同時に行う
	 */
	public function commit(){
		$instanceIndex = self::_initDB();
		if(TRUE === self::$_transaction[$instanceIndex]){
			$res = self::$_DBInstance[$instanceIndex]->CommitTrans();
			self::$_transaction[$instanceIndex] = FALSE;
			return $res;
		}
		return NULL;
	}

	/**
	 * クエリー実行とDBインスタンスの初期化を同時に行う
	 */
	public function rollback(){
		$instanceIndex = self::_initDB();
		if(TRUE === self::$_transaction[$instanceIndex]){
			$res = self::$_DBInstance[$instanceIndex]->RollbackTrans();
			self::$_transaction[$instanceIndex] = FALSE;
			return $res;
		}
		return NULL;
	}
}

?>
