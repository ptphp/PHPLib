<?php
class PtDb extends PdoDb{
	
	
}

/**
 * Pdo_mysql类
 * @author Joseph
 * 
 * PtDb::$config['default'] = array(
	'type'=>'msyql',
	'host'=>"localhost",
	'port'=>3306,
	'dbname'=>"",
	'dbuser'=>"root",
	'dbpass'=>"root",
	'charset'=>"utf8",
);

 */
class PdoDb {	
	private static $_obj = array();
	private $conn;               //连接对象 
    private $stm;                //操作对象 
    public static $config = array(
					    			'default'=>array(
					    						'type'=>'mysql',
					    						'host'=>'localhost',
					    						'port'=>3306,
					    						'dbname'=>'',
					    						'dbuser'=>'root',
					    						'dbpass'=>'',
					    						'charset'=>'utf8',
					    					),
						    		'sqlite'=>array(
						    				'type'=>'sqlite',
						    				'dbname'=>'',
						    				'charset'=>'utf8',
						    		),
					    		);
	
	private function __construct($key)
	{				
		$this->config($key);
	}
	
	public static function init($key = 'default')
	{
		if(!key_exists($key, self::$_obj))
		{
			$class = __class__;
			self::$_obj[$key] = new $class($key);
		}
			
		return self::$_obj[$key];
	}
	
	public function config($key){
		$config = self::$config[$key];
		try{			
			if(!isset($config['type'])){
				$config['type'] = 'mysql';
			}
			if($config['type'] == 'sqlite'){
				$dsn = $config['type'].":".$config['dbname'];
				$this->conn = new PDO($dsn);
			}else{
				//PHP版本小于5.3.6，我需要在连接字符串中添加charset参数。
				$dsn = $config['type'].":host=".$config['host'].";charset=".$config['charset'].";dbname=".$config['dbname'].";port=".$config['port'];
				$this->conn = new PDO($dsn,$config['dbuser'],$config['dbpass']);
			}
			
			if(!isset($config['charset'])){
				$config['charset'] = 'utf8';
			}
			$this->conn->query("set names ".$config['charset'].";");
			//禁用仿真预处理，使用真正的预处理。这样确保语句在发送给MySQL服务器前没有通过PHP解析，不给攻击者注入SQL的机会
			$this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch (PDOException $e) {			
			print_r($e->getMessage());
			exit;
		}
		
	}
	
	function __destruct() {		
		$this->conn = null;
		$this->stm  = null;		
	}
	
	public function __clone()
	{
		trigger_error('Clone is not allow' ,E_USER_ERROR);
		exit;
	}
	
	//执行并无返回值记录
	public function runSql($sql,$args=array()) {
		return $this->run($sql,$args,FALSE);
	}
	
	//返回插入ID
	public function lastId() {
		return $this->conn->lastInsertId();
	}
	
	//返回一维数组
	public function getOne($sql,$args = array()) {		
		return $this->run($sql,$args,TRUE,'one');
	}
	
	//返回二维数组
	public function getAll($sql,$args = array()) {         
		return $this->run($sql,$args,TRUE,'all');
	}
	
	//绑定参数
	public function bindParams($args) {
		foreach ($args as $name=>&$value){
			$this->stm->bindParam(":".$name, $value);
		}
	}	
	public function query($sql){
		return $this->conn->query($sql);
	}
	public function exec($sql){
		return $this->conn->exec($sql);
	}
	public function run($sql,$args = array(),$return = FALSE,$returnType = 'one',$fetcheType = PDO::FETCH_ASSOC) {
		
		try{
			$this->stm = $this->conn->prepare($sql);
			if($args){				
				//$this->bindParams($args);
				$this->stm->execute($args);
			}else{

				$this->stm->execute();
			}	
			
		} catch (PDOException $e) {
			$pre = $e->getTrace();
			print_r($e->getMessage());
			print_r($pre);
			exit;
		}
		
		if($return){			
			if($returnType == 'one'){
				$RSarray = $this->stm->fetch($fetcheType);
			}else{
				$RSarray = $this->stm->fetchAll($fetcheType);
			}
			return $RSarray;
		}else{
			return $this->lastId();
		}
		
	}
	public function getSchema($table){
		$schema = array();
		
		$sql = 'SHOW CREATE TABLE '. $table;
		$stm = $this->conn->prepare($sql);
		$result = $this->stm->execute();
		$columnname = 'Create Table';
		while( $row = $this->stm->fetchObject() ){
			$schema[$table] = $row->$columnname;
		}
		//print_pre($schema);
		return $schema;
		
	}
	
	public function listTables($like = ''){
		$sql = "show tables";
		if($like){
			$sql .= " like '".$like."'";
		}
		$result = $this->conn->query($sql);
		$tables = array();
		while ($row = $result->fetch(PDO::FETCH_NUM)) {
			$tables[] = $row[0];
		}
		//print_r($tables);
		return $tables;
	}
	
	public function descTable($table,$type="mysql"){
		
		//sqlite  PRAGMA table_info(admin)
		//msyql   SHOW COLUMNS FROM admin
		//msyql   desc admin
		
		if($type == 'mysql'){
			$sql = "SHOW COLUMNS FROM " . $table;
		}else{
			$sql = "PRAGMA table_info(" . $table . ")";
		}	
		//echo $sql;
		//exit;	
		$result = $this->conn->query($sql);
		$table_fields = array();
		$table_fields = $result->fetchAll(PDO::FETCH_ASSOC);

		return $table_fields;
	}
	
	public function showCreateTable($table){
		$sql = 'show create table `'.$table."`";
		$result = $this->conn->query($sql);
		return $result->fetch(PDO::FETCH_ASSOC);
	}
	
	public function querySql($sql){
		$result = $this->conn->query($sql);
		return $result->fetchAll(PDO::FETCH_ASSOC);
	}
	
}