<?php
class Cache{
	static $tmpSwitch = true;
	static $_playerTmpData = [];
	static $_guildTmpData = [];
	static $_campTmpData = [];
	static $_connection = [];
	static $redis = [];
    static $redisSwitchType = 0;//游戏服1 登录服2 pk服3
    //redis服务器白名单Begin
    static $loginServerClassNameWhiteList = ['ServerList', 'PlayerServerList', 'LoginServerConfig', 'PlayerLastServer', 'AndroidChannel'];//登录服相关表
    static $pkServerClassNameWhiteList    = ['Pk', 'PkPlayerInfo', 'PkPlayerGeneral', 'PkReport', 'PkGeneralStatistic'];//pk服相关表
    //redis服务器白名单End
    /**
     * 指定池
     * 
     * @param <type> $pool 默认：cache
     * 
     * @return redisConnection
     */
	public static function db($pool=CACHEDB_PLAYER, $className=''){
		global $config;
        if($config->is_login_server) {//登录服特殊处理
            return self::loginServerDb($pool);
        }
		$index = self::dbname2id($pool, $className);
    $currentClassRedisSwitchType = Cache::getRedisSwitchType($className);

		if(!isset(self::$redis[$currentClassRedisSwitchType])){
            $REDIS = self::$redis[$currentClassRedisSwitchType] = getnewredisconnect($className);
		} else {
            $REDIS = self::$redis[$currentClassRedisSwitchType];
        }
		try{
            $REDIS->select($index);
		} catch (Exception $e) {
			try{
				self::close();
                $REDIS = getnewredisconnect($className);
                $REDIS->select($index);
			} catch (Exception $e) {
			}
		}
		//$redis = $di['redis'];
		return $REDIS;
	}

    /**
     * 获取redis切换类型
     *
     * @param $className
     *
     * @return int
     */
    public static function getRedisSwitchType($className){
        $rst = 1;
        if(in_array($className, self::$loginServerClassNameWhiteList)) {
            $rst = 2;
        } elseif(in_array($className, self::$pkServerClassNameWhiteList)) {
            $rst = 3;
		} elseif(substr($className, 0, 5) == 'Cross'){
			$rst = 4;
		} elseif(substr($className, 0, 10) == 'CityBattle' || in_array($className, ['City', 'Camp'])){
			$rst = 5;
        }
        return $rst;
    }
    /**
     * 获取redis config
     * @param $className
     *
     * @return mixed
     */
	public static function getServerConfig($className){
        global $config;
        if(in_array($className, self::$loginServerClassNameWhiteList)) {//登录服
            $c = $config->login_server->redis->toArray();
        }
        elseif(in_array($className, self::$pkServerClassNameWhiteList)) {//pk服
            $c = $config->pk_server->redis->toArray();
        }
		elseif(substr($className, 0, 5) == 'Cross'){
			$c = $config->cross_server->redis->toArray();
        }
		elseif(substr($className, 0, 10) == 'CityBattle' || in_array($className, ['City', 'Camp'])){
			$c = $config->citybattle_server->redis->toArray();
        }
		else {
            $c = $config->redis->toArray();
        }
        return $c;
    }
    /**
     * 登录服redis
     */
    public static function loginServerDb($pool='login_server'){
        global $config;
        $redis = new Redis();
        $c = $config->login_server->redis->toArray();
        $redis->connect($c['host'], $c['port'], $c['timeout']);
        $redis->setOption(Redis::OPT_PREFIX, $c['prefix']);
        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

        $index = self::dbname2id($pool);
        $redis->select($index);
        return $redis;
    }
	public static function dbname2id($pool=CACHEDB_PLAYER, $className=''){
        $c = self::getServerConfig($className);
		if(!isset($c['index'][$pool])){
			$pool = CACHEDB_PLAYER;
		}
		return $index = $c['index'][$pool];
	}
	
    /**
     * 锁定
     * 
     * @param <type> $key 
     * @param <type> $timeout 
     * @param <type> $loopSec
     *
     * @return <type>
     */
	public static function lock($key, $timeout=10, $pool=CACHEDB_PLAYER, $loopSec=60, $className=''){
		$key = self::lockkey($key);
		$time = time();
		while(!self::db($pool, $className)->setnx($key, 1)){
			usleep(1000);
            $testQA = 0;
            if($testQA) {
                debug("{$key}- 被锁中。。。", 1);
            }
			if(time() - $time >= $loopSec){
				exit;
			}
		}
		if(false !== $timeout)
			self::db($pool, $className)->setTimeout($key, $timeout);
	}
	
    /**
     * 解锁
     * 
     * @param <type> $key 
     * 
     * @return <type>
     */
	public static function unlock($key, $pool=CACHEDB_PLAYER, $className=''){
		$key = self::lockkey($key);
		self::db($pool, $className)->delete($key);
	}
	
    /**
     * 锁名
     * 
     * @param <type> $key 
     * 
     * @return <type>
     */
	public static function lockkey($key){
		return 'LOCK:'.$key;
	}
	
    /**
     * 设置玩家数据包
     * 
     * @param <type> $playerId 
     * @param <type> $key 
     * @param <type> $value 
     * @param <type> $db
     *
     * @return <type>
     */
	public static function setPlayer($playerId, $key, $value, $db=CACHEDB_PLAYER){
		$dataKey = self::getPlayerKey($playerId);
		self::db($db, $key)->hSet($dataKey, $key, $value);
		if(self::$tmpSwitch){
			@self::$_playerTmpData[$playerId][$key] = $value;
			if(count(self::$_playerTmpData) > 50)
				self::$_playerTmpData = array_slice(self::$_playerTmpData, -50, 50, true);
		}
	}
	
    /**
     * 获取玩家数据包
     * 
     * @param <type> $playerId 
     * @param <type> $key 
     * @param <type> $db
     *
     * @return <type>
     */
	public static function getPlayer($playerId, $key, $db=CACHEDB_PLAYER){
		$ret = @self::$_playerTmpData[$playerId][$key];
		if(!$ret){
			$dataKey = self::getPlayerKey($playerId);
			$ret = self::db($db, $key)->hGet($dataKey, $key);
			if(self::$tmpSwitch){
				@self::$_playerTmpData[$playerId][$key] = $ret;
				if(count(self::$_playerTmpData) > 50)
					self::$_playerTmpData = array_slice(self::$_playerTmpData, -50, 50, true);
			}
		}
		return $ret;
	}
	
    /**
     * 删除玩家数据包
     * 
     * @param <type> $playerId 
     * @param <type> $key 
     * @param <type> $db
     *
     * @return <type>
     */
	public static function delPlayer($playerId, $key, $db=CACHEDB_PLAYER){
		$dataKey = self::getPlayerKey($playerId);
		self::db($db, $key)->hDel($dataKey, $key);
		unset(self::$_playerTmpData[$playerId][$key]);
	}
	
    /**
     * 删除玩家所有数据包
     * 
     * @param <type> $playerId 
     * 
     * @return <type>
     */
	public static function delPlayerAll($playerId){
		$dataKey = self::getPlayerKey($playerId);
        self::db()->del($dataKey);
		self::db(self::$loginServerClassNameWhiteList[0])->del($dataKey);
		self::db(self::$pkServerClassNameWhiteList[0])->del($dataKey);
		self::db('cache', 'Cross')->del($dataKey);
		self::db('cache', 'CityBattle')->del($dataKey);
		unset(self::$_playerTmpData[$playerId]);
	}
	
    /**
     * 获取玩家数据包key
     * 
     * @param <type> $playerId 
     * 
     * @return <type>
     */
	public static function getPlayerKey($playerId){
		return 'data_I'.$playerId;
	}

	 /**
     * 设置玩家数据包
     * 
     * @param <type> $playerId 
     * @param <type> $key 
     * @param <type> $value 
     * 
     * @return <type>
     */
	public static function setGuild($guildId, $key, $value){
		$dataKey = self::getGuildKey($guildId);
		self::db(CACHEDB_PLAYER, $key)->hSet($dataKey, $key, $value);
		if(self::$tmpSwitch){
			@self::$_guildTmpData[$guildId][$key] = $value;
			if(count(self::$_guildTmpData) > 50)
				self::$_guildTmpData = array_slice(self::$_guildTmpData, -50, 50, true);
		}
	}
	
	/**
     * 获取联盟数据包
     * 
     * @param <type> $guildId 
     * @param <type> $key 
     * 
     * @return <type>
     */
	public static function getGuild($guildId, $key){
		$ret = @self::$_guildTmpData[$guildId][$key];
		if(!$ret){
			$dataKey = self::getGuildKey($guildId);
			$ret = self::db(CACHEDB_PLAYER, $key)->hGet($dataKey, $key);
			if(self::$tmpSwitch){
				@self::$_guildTmpData[$guildId][$key] = $ret;
				if(count(self::$_guildTmpData) > 50)
					self::$_guildTmpData = array_slice(self::$_guildTmpData, -50, 50, true);
			}
		}
		return $ret;
	}
	
	/**
     * 删除联盟数据包
     * 
     * @param <type> $playerId 
     * @param <type> $key 
     * 
     * @return <type>
     */
	public static function delGuild($guildId, $key){
		$dataKey = self::getGuildKey($guildId);
		self::db(CACHEDB_PLAYER, $key)->hDel($dataKey, $key);
		unset(self::$_guildTmpData[$guildId][$key]);
	}
	
    /**
     * 删除联盟所有数据包
     * 
     * @param <type> $playerId 
     * 
     * @return <type>
     */
	public static function delGuildAll($guildId){
		$dataKey = self::getGuildKey($guildId);
        self::db()->del($dataKey);
        self::db(self::$loginServerClassNameWhiteList[0])->del($dataKey);
        self::db(self::$pkServerClassNameWhiteList[0])->del($dataKey);
		self::db('cache', 'Cross')->del($dataKey);
		self::db('cache', 'CityBattle')->del($dataKey);
		unset(self::$_guildTmpData[$guildId]);
	}
	
	/**
     * 获取联盟数据包key
     * 
     * @param <type> $playerId 
     * 
     * @return <type>
     */
	public static function getGuildKey($guildId){
		return 'data_G'.$guildId;
	}
	
	
	 /**
     * 设置阵营数据包
     * 
     * @param <type> $playerId 
     * @param <type> $key 
     * @param <type> $value 
     * 
     * @return <type>
     */
	public static function setCamp($campId, $key, $value){
		$dataKey = self::getCampKey($campId);
		self::db(CACHEDB_PLAYER, $key, 'CityBattle')->hSet($dataKey, $key, $value);
		if(self::$tmpSwitch){
			@self::$_campTmpData[$campId][$key] = $value;
			if(count(self::$_campTmpData) > 50)
				self::$_campTmpData = array_slice(self::$_campTmpData, -50, 50, true);
		}
	}
	
	/**
     * 获取阵营数据包
     * 
     * @param <type> $campId 
     * @param <type> $key 
     * 
     * @return <type>
     */
	public static function getCamp($campId, $key){
		$ret = @self::$_campTmpData[$campId][$key];
		if(!$ret){
			$dataKey = self::getCampKey($campId);
			$ret = self::db(CACHEDB_PLAYER, $key, 'CityBattle')->hGet($dataKey, $key);
			if(self::$tmpSwitch){
				@self::$_campTmpData[$campId][$key] = $ret;
				if(count(self::$_campTmpData) > 50)
					self::$_campTmpData = array_slice(self::$_campTmpData, -50, 50, true);
			}
		}
		return $ret;
	}
	
	/**
     * 删除联盟数据包
     * 
     * @param <type> $playerId 
     * @param <type> $key 
     * 
     * @return <type>
     */
	public static function delCamp($campId, $key){
		$dataKey = self::getCampKey($campId);
		self::db(CACHEDB_PLAYER, $key, 'CityBattle')->hDel($dataKey, $key);
		unset(self::$_campTmpData[$campId][$key]);
	}
	
    /**
     * 删除联盟所有数据包
     * 
     * @param <type> $playerId 
     * 
     * @return <type>
     */
	public static function delCampAll($campId){
		$dataKey = self::getCampKey($campId);
        self::db()->del($dataKey);
        self::db(self::$loginServerClassNameWhiteList[0])->del($dataKey);
        self::db(self::$pkServerClassNameWhiteList[0])->del($dataKey);
		self::db('cache', 'Cross')->del($dataKey);
		self::db('cache', 'CityBattle')->del($dataKey);
		unset(self::$_campTmpData[$campId]);
	}
	
	/**
     * 获取联盟数据包key
     * 
     * @param <type> $playerId 
     * 
     * @return <type>
     */
	public static function getCampKey($campId){
		return 'data_C'.$campId;
	}
	
	/**
	* 清cache
	* @param  boolean $serverFlag 是否清swoole服务相关cache
	*/
	public static function clearAllCache($serverFlag=false){
		self::$_playerTmpData = [];
		self::$_guildTmpData = [];
		self::$_campTmpData = [];
		if($serverFlag) {
		   Cache::db('server')->flushDB();
		}
		Cache::db('cache')->flushDB();
		Cache::db('static')->flushDB();
		Cache::db('bufftemp')->flushDB();
		Cache::db('chat')->flushDB();
		Cache::db('dispatcher')->flushDB();
        Cache::db('map')->flushDB();
		
		Cache::db('cache', 'Pk')->flushDB();
		Cache::db('static', 'Pk')->flushDB();

		Cache::db('cache', 'Cross')->flushDB();
		Cache::db('static', 'Cross')->flushDB();
		Cache::db('dispatcher', 'Cross')->flushDB();
		
		Cache::db('cache', 'CityBattle')->flushDB();
		Cache::db('static', 'CityBattle')->flushDB();
		Cache::db('dispatcher', 'CityBattle')->flushDB();
	}
	
	public static function clearPlayerCache(){
		self::$_playerTmpData = [];
		self::$_guildTmpData = [];
		self::$_campTmpData = [];
		Cache::db('cache')->flushDB();
		Cache::db('bufftemp')->flushDB();
	}

	public static function close(){
        try{
            foreach(self::$redis as $r) {
                $r->close();
            }
            self::$redis = [];
        } catch (Exception $e) {
        }
	}
}
