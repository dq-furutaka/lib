<?php

/**
 * Sessionクラス(PECL:Memcache版(非PECL:Memcached))
 * @author saimushi
 */
class SessionMemcache extends SessionDataMemcache implements SessionIO {

	protected static $_DSN = NULL;
	protected static $_initialized = FALSE;
	protected static $_tokenInitialized = FALSE;
	protected static $_replaced = FALSE;
	protected static $_tokenKeyName = 'token';
	protected static $_token = NULL;
	protected static $_identifier = NULL;
	protected static $_domain = NULL;
	protected static $_path = '/';
	protected static $_expiredtime = 3600;// 60分
	protected static $_sessionTblName = 'session_table';
	protected static $_sessionPKeyName = 'token';
	protected static $_sessionDateKeyName = 'create_date';
	protected static $_cryptKey = NULL;
	protected static $_cryptIV = NULL;

	/**
	 * Sessionクラスの初期化
	 * @param string セッションの範囲となるドメイン
	 * @param string セッションの有効期限
	 * @param string DBDSN情報
	 */
	protected static function _init($argDomain=NULL, $argExpiredtime=NULL, $argDSN=NULL){
		if(FALSE === self::$_initialized){

			// セッションの有効ドメインを設定
			self::$_domain = $argDomain;
			if(NULL === $argDomain){
				self::$_domain = $_SERVER['SERVER_NAME'];
			}

			$DSN = NULL;
			$expiredtime = self::$_expiredtime;

			if(class_exists('Configure') && NULL !== Configure::constant('MEMCACHE_DSN')){
				// 定義からセッションDBの接続情報を特定
				$DSN = Configure::MEMCACHE_DSN;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('MC_DSN')){
				// 定義からセッションDBの接続情報を特定
				$DSN = Configure::MC_DSN;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('SESSION_MEMCACHE_DSN')){
				// 定義からセッションDBの接続情報を特定
				$DSN = Configure::SESSION_MEMCACHE_DSN;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('SESSION_MC_DSN')){
				// 定義からセッションDBの接続情報を特定
				$DSN = Configure::SESSION_MC_DSN;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('SESSION_EXPIRED_TIME')){
				// 定義からセッションの有効期限を設定
				$expiredtime = Configure::SESSION_EXPIRED_TIME;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('SESSION_TBL_NAME')){
				// 定義からセッションテーブル名を特定
				self::$_sessionTblName = Configure::SESSION_TBL_NAME;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('SESSION_TBL_PKEY_NAME')){
				// 定義からセッションテーブルのPKey名を特定
				self::$_sessionPKeyName = Configure::SESSION_TBL_PKEY_NAME;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('SESSION_DATE_KEY_NAME')){
				// 定義から日時フィールド名を特定
				self::$_sessionDateKeyName = Configure::SESSION_DATE_KEY_NAME;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('CRYPT_KEY')){
				// 定義から暗号化キーを設定
				self::$_cryptKey = Configure::CRYPT_KEY;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('NETWORK_CRYPT_KEY')){
				// 定義から暗号化キーを設定
				self::$_cryptKey = Configure::NETWORK_CRYPT_KEY;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('SESSION_CRYPT_KEY')){
				// 定義から暗号化キーを設定
				self::$_cryptKey = Configure::SESSION_CRYPT_KEY;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('CRYPT_IV')){
				// 定義から暗号化IVを設定
				self::$_cryptIV = Configure::CRYPT_IV;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('NETWORK_CRYPT_IV')){
				// 定義から暗号化IVを設定
				self::$_cryptIV = Configure::NETWORK_CRYPT_IV;
			}
			if(class_exists('Configure') && NULL !== Configure::constant('SESSION_CRYPT_IV')){
				// 定義から暗号化IVを設定
				self::$_cryptIV = Configure::SESSION_CRYPT_IV;
			}
			if(defined('PROJECT_NAME') && strlen(PROJECT_NAME) > 0 && class_exists(PROJECT_NAME . 'Configure')){
				$ProjectConfigure = PROJECT_NAME . 'Configure';
				if(NULL !== $ProjectConfigure::constant('MEMCACHE_DSN')){
					// 定義からセッションDBの接続情報を特定
					$DSN = $ProjectConfigure::MEMCACHE_DSN;
				}
				if(NULL !== $ProjectConfigure::constant('MC_DSN')){
					// 定義からセッションDBの接続情報を特定
					$DSN = $ProjectConfigure::MC_DSN;
				}
				if(NULL !== $ProjectConfigure::constant('SESSION_MEMCACHE_DSN')){
					// 定義からセッションDBの接続情報を特定
					$DSN = $ProjectConfigure::SESSION_MEMCACHE_DSN;
				}
				if(NULL !== $ProjectConfigure::constant('SESSION_MC_DSN')){
					// 定義からセッションDBの接続情報を特定
					$DSN = $ProjectConfigure::SESSION_MC_DSN;
				}
				if(NULL !== $ProjectConfigure::constant('SESSION_EXPIRED_TIME')){
					// 定義からセッションの有効期限を設定
					$expiredtime = $ProjectConfigure::SESSION_EXPIRED_TIME;
				}
				if(NULL !== $ProjectConfigure::constant('SESSION_TBL_NAME')){
					// 定義からセッションテーブル名を特定
					self::$_sessionTblName = $ProjectConfigure::SESSION_TBL_NAME;
				}
				if(NULL !== $ProjectConfigure::constant('SESSION_TBL_PKEY_NAME')){
					// 定義からセッションテーブルのPKey名を特定
					self::$_sessionPKeyName = $ProjectConfigure::SESSION_TBL_PKEY_NAME;
				}
				if(NULL !== $ProjectConfigure::constant('SESSION_DATE_KEY_NAME')){
					// 定義から日時フィールド名を特定
					self::$_sessionDateKeyName = $ProjectConfigure::SESSION_DATE_KEY_NAME;
				}
				if(NULL !== $ProjectConfigure::constant('CRYPT_KEY')){
					// 定義から暗号化キーを設定
					self::$_cryptKey = $ProjectConfigure::CRYPT_KEY;
				}
				if(NULL !== $ProjectConfigure::constant('NETWORK_CRYPT_KEY')){
					// 定義から暗号化キーを設定
					self::$_cryptKey = $ProjectConfigure::NETWORK_CRYPT_KEY;
				}
				if(NULL !== $ProjectConfigure::constant('SESSION_CRYPT_KEY')){
					// 定義から暗号化キーを設定
					self::$_cryptKey = $ProjectConfigure::SESSION_CRYPT_KEY;
				}
				if(NULL !== $ProjectConfigure::constant('CRYPT_IV')){
					// 定義から暗号化IVを設定
					self::$_cryptIV = $ProjectConfigure::CRYPT_IV;
				}
				if(NULL !== $ProjectConfigure::constant('NETWORK_CRYPT_IV')){
					// 定義から暗号化IVを設定
					self::$_cryptIV = $ProjectConfigure::NETWORK_CRYPT_IV;
				}
				if(NULL !== $ProjectConfigure::constant('SESSION_CRYPT_IV')){
					// 定義から暗号化IVを設定
					self::$_cryptIV = $ProjectConfigure::SESSION_CRYPT_IV;
				}
			}

			// memcacheのDSNを取っておく
			if(NULL === self::$_DSN){
				if(NULL !== $argDSN){
					// セッションmemcacheの接続情報を直指定
					$DSN = $argDSN;
				}
				self::$_DSN = $DSN;
			}

			// セッションの有効期限を設定
			if(NULL !== $argExpiredtime){
				// セッションの有効期限を直指定
				$expiredtime = $argExpiredtime;
			}
			self::$_expiredtime = $expiredtime;

			// セッションデータクラスの初期化
			parent::_init($expiredtime, $DSN);

			// 初期化済み
			self::$_initialized = TRUE;
		}
	}

	/**
	 * トークンを固有識別子まで分解する
	 * 分解したトークンの有効期限チェックを自動で行います
	 * XXX 各システム毎に、Tokenの仕様が違う場合はこのメソッドをオーバーライドして実装を変更して下さい
	 * @param string トークン文字列
	 * @return mixed パースに失敗したらFALSE 成功した場合はstring 固有識別子を返す
	 */
	protected static function _tokenToIdentifier($argToken, $argUncheck=FALSE){
		logging(self::$_cryptKey, 'session');
		logging(self::$_cryptIV, 'session');
		logging(self::$_expiredtime, 'session');
		$token = $argToken;
		// 暗号化されたトークンの本体を取得
		$encryptedToken = substr($token, 0, strlen($token)-14);
		// トークンが発行された日時分秒文字列
		$tokenExpierd = substr($token, strlen($token)-14, 14);
		logging('$tokenExpierd=' . $tokenExpierd, 'session');
		logging('$encryptedToken=' . $encryptedToken, 'session');
		// トークンを複合
		$decryptToken = Utilities::doHexDecryptAES($encryptedToken, self::$_cryptKey, self::$_cryptIV);
		logging('$decryptToken=' . $decryptToken, 'session');
		// XXXデフォルトのUUIDはSHA256
		$identifier = substr($decryptToken, 0, strlen($decryptToken) - 14);
		// トークンの中に含まれていた、トークンが発行された日時分秒文字列
		$tokenTRUEExpierd = substr($decryptToken, strlen($decryptToken) - 14, 14);

		logging('tokenIdentifier='.$identifier, 'session');
		logging('$tokenTRUEExpierd=' . $tokenTRUEExpierd . '&$decryptToken=' . $decryptToken, 'session');
		// expierdの偽装チェックはココでしておく
		if(FALSE === $argUncheck && FALSE === (strlen($tokenExpierd) == 14 && $tokenExpierd == $tokenTRUEExpierd)){
			// $tokenExpierdと$tokenTRUEExpierdが一致しない=$tokenExpierdが偽装されている！？
			// XXX ペナルティーレベルのクラッキングアクセス行為に該当
			logging(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__, "hack");
			// パースに失敗したとみなす
			return FALSE;
		}

		// tokenの有効期限のチェック
		$year = substr($tokenTRUEExpierd, 0, 4);
		$month = substr($tokenTRUEExpierd, 4, 2);
		$day = substr($tokenTRUEExpierd, 6, 2);
		$hour = substr($tokenTRUEExpierd, 8, 2);
		$minute = substr($tokenTRUEExpierd, 10, 2);
		$second = substr($tokenTRUEExpierd, 12, 2);
		$tokenexpiredatetime = (int)Utilities::date('U', $year . '-' . $month . '-'. $day . ' ' . $hour . ':' . $minute . ':' . $second, 'GMT');
		$expiredatetime = (int)Utilities::modifyDate("-".(string)self::$_expiredtime . 'sec', 'U', NULL, NULL, 'GMT');
		logging('$tokenTRUEExpierd='.$tokenTRUEExpierd.'&$tokenexpiredatetime=' . $tokenexpiredatetime . '&$expiredatetime=' . $expiredatetime, 'session');
		if(FALSE === $argUncheck && $tokenexpiredatetime < $expiredatetime){
			return FALSE;
		}
		logging('tokenIdentifier='.$identifier, 'session');
		return $identifier;
	}

	/**
	 * 固有識別子からトークンを生成する
	 * XXX 各システム毎に、Tokenの仕様が違う場合はこのメソッドをオーバーライドして実装を変更して下さい
	 * @param string identifier
	 * @return string token
	 */
	protected static function _identifierToToken($argIdentifier){
		$identifier = $argIdentifier;
		$newExpiredDatetime = Utilities::modifyDate('+'.(string)self::$_expiredtime . 'sec', 'YmdHis', NULL, NULL, 'GMT');
		$token = Utilities::doHexEncryptAES($identifier.$newExpiredDatetime, self::$_cryptKey, self::$_cryptIV).$newExpiredDatetime;
		return $token;
	}

	/**
	 * トークンの初期化
	 */
	protected static function _initializeToken(){
		if(FALSE === self::$_tokenInitialized){
			self::$_tokenInitialized = TRUE;
			if(NULL === self::$_token){
				// 8バイト以下の$_COOKIE[self::$_tokenKeyName]はセットされていてもTOKENとして認めない
				if(isset($_COOKIE[self::$_tokenKeyName]) && strlen($_COOKIE[self::$_tokenKeyName]) > 8){
					// Cookieが在る場合はCookieからトークンと固有識別子を初期化する
					$token = $_COOKIE[self::$_tokenKeyName];
					$identifier = self::_tokenToIdentifier($token);
					logging('is identifier? '.$identifier, 'session');
					if(FALSE !== $identifier){
						logging('is identifier! '.$identifier, 'session');
						// SESSIONレコードを走査
						$Session = MCO::get($token, self::$_DSN);
						logging('is session key! '.$Session, 'session');
						if (FALSE !== $Session && 0 < strlen($Session)){
							$Session = @unserialize($Session);
						}
						if(FALSE !== $Session && is_object($Session) && isset($Session->{self::$_sessionPKeyName}) && strlen($Session->{self::$_sessionPKeyName}) > 0){
							logging('is session! '.$Session->{self::$_sessionPKeyName}, 'session');
							// tokenとして認める
							self::$_token = $token;
							self::$_identifier = $identifier;
							return TRUE;
						}
						// identiferだけは認める
						self::sessionID($identifier);
					}
					logging('cookie delete!', 'session');
					// 二度処理しない為に削除する
					unset($_COOKIE[self::$_tokenKeyName]);
					setcookie(self::$_tokenKeyName, '', time() - 3600);
				}
				else{
					// 固有識別子をセットする
					self::sessionID(self::$_identifier);
					// トークンがまだ無いので、最初のトークンとして空文字を入れておく
					self::$_token = "";
					return TRUE;
				}
				// セッションは存在したが、一致特定出来なかった時はエラー終了
				logging('session expired!', 'session');
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Cookieからトークンを出し入れする時のキー名を変えられるようにする為のアクセサ
	 * @param string トークンキー名
	 */
	public static function setTokenKey($argTokenKey){
		self::$_tokenKeyName = $argTokenKey;
	}

	/**
	 * 新しいトークンを指定のトークンキー名で払い出しcookieにセットする
	 * @param string トークンキー名
	 */
	public static function setTokenToCookie($argTokenKey){
		// 新しいtokenを発行する
		self::$_token = self::_identifierToToken(self::$_identifier);
		// クッキーを書き換える
		setcookie($argTokenKey, self::$_token, 0, self::$_path);
		// SESSHONレコードを更新
		$Session = new stdClass;
		$Session->{self::$_sessionPKeyName} = self::$_token;
		$Session->{self::$_sessionDateKeyName} = Utilities::date('Y-m-d H:i:s', NULL, NULL, 'GMT');
		// memcacheに保存
		MCO::set(self::$_token, @serialize($Session), FALSE, self::$_expiredtime, self::$_DSN);
		logging('set session='.var_export($Session, TRUE), 'session');
	}

	/**
	 * セッションIDを明示的に指定する
	 * @param string identifier
	 */
	public static function sessionID($argIdentifier=NULL){
		if(FALSE === self::$_initialized){
			self::_init();
		}
		if(NULL === self::$_identifier && NULL === $argIdentifier){
			// トークンの初期化
			if(FALSE === self::$_tokenInitialized && FALSE === self::_initializeToken()){
				// $_identifierを特定出来なかったエラー
				return FALSE;
			}
			if(NULL === self::$_identifier){
				// idetifierが未セットの場合は自動生成
				self::$_identifier = Utilities::doHexEncryptAES(uniqid(), self::$_cryptKey, self::$_cryptIV);
			}
		}
		if(NULL === $argIdentifier){
			// 現在設定されている固有識別子をセッションIDとして返却
			return self::$_identifier;
		}
		else{
			// 渡された値を固有識別子に書き換える
			// セッションIDをセットした時はTokenを書き換える事になるので、_initializeTokenをスキップする
			self::$_tokenInitialized = TRUE;
			self::$_token = "";
			self::$_identifier = $argIdentifier;
		}
	}

	/**
	 * セッションの開始する(_initのアクセサ)
	 * @param string セッションの範囲となるドメイン
	 * @param string セッションの有効期限
	 * @param string DBDSN情報
	 * @throws Exception
	 */
	public static function start($argDomain=NULL, $argExpiredtime=NULL, $argDSN=NULL){
		if(FALSE === self::$_initialized){
			self::_init($argDomain, $argExpiredtime, $argDSN);
		}
	}

	/**
	 * セッションの指定のキー名で保存されたデータを返す
	 * セッションが初期化されていなければ初期化する
	 * @param string キー名
	 * @param mixed 変数全て
	 */
	public static function get($argKey = NULL){
		if(FALSE === self::$_initialized){
			// 自動セッションスタート
			self::_init();
		}
		// トークンの初期化
		if(FALSE === self::$_tokenInitialized && FALSE === self::_initializeToken()){
			// エラー
			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__.PATH_SEPARATOR.Utilities::getBacktraceExceptionLine());
		}
		// XXX 標準ではセッションデータのPKeyはセッションの固有識別子
		return parent::get(self::$_identifier);
	}

	/**
	 * セッションに指定のキー名で指定のデータをしまう
	 * セッションが初期化されていなければ初期化する
	 * @param string キー名
	 * @param mixed 変数全て(PHPオブジェクトは保存出来ない！)
	 */
	public static function set($argKey, $argment){
		static $replaced = FALSE;
		if(FALSE === self::$_initialized){
			// 自動セッションスタート
			self::_init();
		}
		// トークンの初期化
		if(FALSE === self::$_tokenInitialized && FALSE === self::_initializeToken()){
			// エラー
			throw new Exception(__CLASS__.PATH_SEPARATOR.__METHOD__.PATH_SEPARATOR.__LINE__.PATH_SEPARATOR.Utilities::getBacktraceExceptionLine());
		}
		if(FALSE === $replaced){
			// Cookieの書き換えがまだなら書き換える
			self::setTokenToCookie(self::$_tokenKeyName);
			// 2度はヘッダ出力しない為に処理終了を取っておく
			$replaced = TRUE;
		}
		// XXX 標準ではセッションデータのPKeyはセッションの固有識別子
		return parent::set(self::$_identifier, $argKey, $argment);
	}

	public static function clear(){
		if(FALSE === self::$_initialized){
			// 自動セッションスタート
			self::_init();
		}
		// 8バイト以下の$_COOKIE[self::$_tokenKeyName]はセットされていてもTOKENとして認めない
		if(isset($_COOKIE[self::$_tokenKeyName]) && strlen($_COOKIE[self::$_tokenKeyName]) > 8){
			// Cookieが在る場合はCookieからトークンと固有識別子を初期化する
			$token = $_COOKIE[self::$_tokenKeyName];
			// SESSIONレコードの削除
			@MCO::delete($token, 0, self::$_DSN);
			// check無しの$identifierの特定
			$identifier = self::_tokenToIdentifier($token, TRUE);
			if(FALSE !== $identifier && strlen($identifier) > 0){
				// 該当のSessionDataも削除
				parent::clear($identifier);
			}
			logging('cookie clear!', 'session');
			// 二度処理しない為に削除する
			unset($_COOKIE[self::$_tokenKeyName]);
			setcookie(self::$_tokenKeyName, '', time() - 3600);
		}
		return TRUE;
	}
}

?>