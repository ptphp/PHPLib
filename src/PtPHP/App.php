<?php
defined('PATH_LIB_PTPHP') or define('PATH_LIB_PTPHP',__DIR__);
defined('PATH_PTAPP') or define('PATH_PTAPP',__DIR__);
defined("DEBUG") or define('DEBUG', FALSE);
@header("content-Type: text/html; charset=utf-8"); //语言强制
@date_default_timezone_set('Asia/Shanghai');//此句用于消除时间差
if(DEBUG){
	@ini_set('display_errors', 'On');
	@error_reporting(E_ALL);
}else{
	@ini_set('display_errors', 'Off');
	@error_reporting(0);
}
session_start();
ob_start();
include "PtPHP/Lib/PtDb.php";
include "PtPHP/Lib/PtTree.php";

class PtApp{
	static function isSAE(){
		if(isset($_SERVER['HTTP_APPNAME']))
		{
			return TRUE;
		}else{
			return FALSE;
		}
	}
	public static $config = array();

	static function init($config){
		PtReq::init();
		self::setConfig($config);
	}

	static function setConfig($config){		
		global $dbconfig;	
		if(isset($dbconfig)){
			PtDb::$config = $dbconfig;	
		}
			
		if(is_array($config)){
			self::$config = $config;
			PtView::$config = $config['view'];
			PtHtml::$v = $config['version'];
		}else{
			$config = array();
		}
	}
	static function run(){
		if(isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO']){
			if($_SERVER['PATH_INFO'] == '/'){
				$mod = 'index';
			}else{
				$mod = ltrim($_SERVER['PATH_INFO'],'/');						
			}
			
		}else{
			if(isset($_GET['_c']) && $_GET['_c']){
				$mod = $_GET['_c'];
			}else{
				$mod = 'index';
			}
		}
		$mod = strtolower(htmlentities($mod,ENT_QUOTES,'UTF-8'));
		if(substr($mod,strlen($mod)-1) == '/'){
			$mod = $mod.'index';
		}		
		include PtView::view($mod);
	}
	static function admin($module){
		$mod = PtReq::get($module['key']);		
		if(empty($mod) || !in_array($mod, $module['list'])){
			$mod = $_GET['mod'] = $module['default'];			
		}

		if(PtReq::get('ajax')){
			$admin = new PtAdmin();
			$admin->$mod();
		}else{
			if($mod != 'login'){
				PtAdmin::auth();
			}	
			include PtView::admin($mod);	
		}
		
	}
}
class pt{
	static function pre($param){		
		$trace = debug_backtrace();
		$info = $trace[0]['file'].' | line : '.$trace[0]['line'];
		echo "Debug info : ".$info;
		echo "<br><pre>";
		
		if (is_array($param)) {
			print_r($param);
		}elseif (is_object($param)){
			print_r($param);
		}else{
			var_dump($param);
		}
		echo "</pre>";
		exit;
	}
}
class PtSes{
	static function auth($type = ''){
		if(!$type){	
			if(!isset($_SESSION['user'])){
				header('Content-Type:text/html;charset=utf-8');
				die("<script>alert('您没有登陆！');location.href='/';</script>");				
			}
		}else{
			if(!isset($_SERVER['PHP_AUTH_USER']) || ($_SERVER['PHP_AUTH_PW']!= PtApp::$config['admin']['password'] || $_SERVER['PHP_AUTH_USER'] != PtApp::$config['admin']['username'])|| !$_SERVER['PHP_AUTH_USER']) 
			{ 
			    header('WWW-Authenticate: Basic realm="ADMIN auth"'); 
			    header('HTTP/1.0 401 Unauthorized'); 
			    echo 'Auth failed'; 
			    exit; 
			}
			//PtSes::set(array('admin'=>array('username'=>$_SERVER['PHP_AUTH_USER'])));

		}
	}
	static function get($key){
		if(isset($_SESSION[$key]) && $_SESSION[$key]){
			return $_SESSION[$key];
		}else{
			return '';
		}
	}
	static function set($key,$value){
		$_SESSION[$key] = $value;
	}
	static function user($key){
		if(isset($_SESSION['user'][$key]) && $_SESSION['user'][$key]){
			return $_SESSION['user'][$key];
		}else{
			return '';
		}
	}
	static function admin($key){
		if(isset($_SESSION['admin'][$key]) && $_SESSION['admin'][$key]){
			return $_SESSION['admin'][$key];
		}else{
			return '';
		}
	}

}

class PtCoo{
	static function get($key){
		if(isset($_COOKIE[$key]) && $_COOKIE[$key]){
			return $_COOKIE[$key];
		}else{
			return '';
		}
	}
}

class ModelDatabase extends PtModel{
	function __construct(){
		parent::__construct('project');
	}
	
	
	static function getCreateTableSql($desc){
		$keys = array_keys($desc);
		$sql = $desc[$keys[1]];
		return $sql;
	}
	static function getTableInfo($desc){
		
		$sql = self::getCreateTableSql($desc);
		$t = explode("\n",$sql);		
		$l = array_pop($t);
		$tt = explode(" ",$l);
		$info = array();
		foreach($tt as $v){
			$v = trim($v);
			if(strpos($v,'=')!==false){
				$vv = explode("=",$v);
				$info[strtolower($vv[0])] = trim($vv[1],"'");
			}
		}
		if(!isset($info['charset'])) $info['charset'] = '';
		if(!isset($info['engine'])) $info['engine'] = '';
		if(!isset($info['auto_increment'])) $info['auto_increment'] = '';
		if(!isset($info['comment'])) $info['comment'] = '';
		
		return $info;
	}

	function remove_row(){
		$ids = $_POST['ids'];
		$table = PtReq::get('table');

		if($table =='tables' || $table == 'schema'){
			$this->table($table)->delete($ids);
		}else{
			PdoDb::getObj()->runSql('delete from '.$table.' where id in ('.$ids.')');
		}

	}
	function schema(){
		$table = PtReq::get('table');	
		$desc = PdoDb::getObj('default')->descTable($table);	
		//print_r($desc);
		foreach ($desc as $key => $value) {
			//print_r($value);
			//echo "<br>";
			//$row['type'] = $value['Type'];
			//$row['max'] = $value['Type'];
			$row['name'] = $value['Field'];
			$row['pk'] = $value['Key']?1:0;
			$row['ac'] = ($value['Extra'] == 'auto_increment ')?1:0;
			
			//echo $row['name'];
			$r = $this->getOne("select * from schema where tblname = '".$table."' and name = '".$row['name']."'");
			//echo "select * from schema where name = '".$row['name']."'";
			//echo "<br>\n";

			if(!$r){
				//echo $row['name'];
				$row['title'] = $row['name'];
				$row['tblname'] = $table;
				$row['list'] = 0;
				if($row['pk']){
					$row['list'] = 1;
				}

				$this->table("schema")->insert($row);
			}else{
				//echo $row['name'];
				//print_r($row);
				//echo "<br>";
				$this->table("schema")->where("name='".$row['name']."'")->update($row);
			}
		}

		$rows = $this->getAll("select * from schema where `tblname` = '".$table."'");
		//print_r($rows);
		PtRes::json_rpc_suc(array('rows'=>$rows));

	}
	function record(){
		$model = PtReq::get('model');
		$table = PtReq::get('table');

		$res = $this->getOne("select title from `tables` where name = '".$table."'");
		$scehma = $this->getAll("select list,name,title,listedit,ord from `schema` where `tblname` = '".$table."'");
		//print_r($scehma);
		$nav = array(
			array('name'=>'项目','click'=>'','active'=>0,'href'=>''),
			array('name'=>'数据表','click'=>'return $(this).modelClick()','active'=>0,'href'=>'/admin.php?model=database&flag=1&func=tables'),
			array('name'=>$res['title'],'click'=>'','active'=>1,'href'=>''),
			);
		$sql = "select * from `".$table."` limit 30";
		//echo $sql;
		$rows = PdoDb::getObj()->getAll($sql);
		PtRes::json_rpc_suc(array('rows'=>$rows,'table'=>$table,'tab'=>1,'model'=>$model,'nav'=>$nav,'schema'=>$scehma));
	}
	function updateField(){
		$table = PtReq::get('table');

		$id = PtReq::get('id');
		//print_r($_POST);

		if($table == 'tables' || $table == 'schema'){
			echo $this->table($table)->where($id)->update($_POST);	
		}else{
			$obj = new PtModel();			
			echo $obj->table($table)->where($id)->update($_POST);
		}	
	}
	function tables(){		
		$model = PtReq::get('model');
		$tables = PdoDb::getObj('default')->listTables();	
		$rows = array();
		foreach($tables as $table){
			$row = array();
			$res = $this->getOne('select * from `tables` where name = "'.$table.'"');
			$desc = PdoDb::getObj('default')->showCreateTable($table);				
			$info = self::getTableInfo($desc);
			$info['title'] = $info['comment']?$info['comment']:$table;
			unset($info['comment']);

			if(!$res){				
				$row['title'] = $info['title'];
				$info['name'] = $table;
				
				$row['id']  = $this->table('tables')->insert($info);			
			}else{				
				$row['id'] = $res['id'];				
			}

			$row['name'] = $table;			
			$row['charset'] = $info['charset'];
			$row['engine'] = $info['engine'];
			$row['auto_increment'] = $info['auto_increment'];
			$row['title'] = $info['title'];
			$row['ctl']  = '<a class="btn btn-mini btn-primary" href="/admin.php?model=database&flag=1&func=record&table='.$row['name'].'" onclick="return $(this).modelClick();" class="btn">记录</a> ';
			$rows[] = $row;
		}

		$schema = array(
				array('name'=>'name','title'=>'Name','list'=>1,'listedit'=>0,'ord'=>''),
				array('name'=>'title','title'=>'名称','list'=>1,'listedit'=>1,'ord'=>''),
				array('name'=>'charset','title'=>'编码','list'=>1,'listedit'=>0,'ord'=>''),
				array('name'=>'engine','title'=>'引擎','list'=>1,'listedit'=>0,'ord'=>''),
				array('name'=>'auto_increment','title'=>'自增','list'=>0,'listedit'=>0,'ord'=>''),
			);

		$nav = array('parent'=>'项目','name'=>'数据表');
		$nav = array(
			array('name'=>'项目','click'=>'','active'=>0,'href'=>''),
			array('name'=>'数据表','click'=>'','active'=>1,'href'=>''),
			);

		PtRes::json_rpc_suc(array('rows'=>$rows,'table'=>'tables','tab'=>0,'model'=>$model,'nav'=>$nav,'schema'=>$schema));
	}
	static function getSchemas($model){
		return PdoDb::getObj('project')->getAll("select name,title,`list`,`ord`,listedit,type,element from `schema` where `tblname` = '".$model."'");
	}
}

class PtAdmin{
	static function auth(){
		if(!PtSes::get('admin')){
			header("Location:/admin.php?c=login");
			exit;
		}
	}
	static function login(){
		if(PtReq::post('username') == PtApp::$config['admin']['username'] 
				&& PtReq::post('password') == PtApp::$config['admin']['password']){
			PtRes::json_rpc_suc(array('username'=>PtReq::post('username')));
			PtSes::set('admin',array('username'=>PtReq::post('username')));
		}else{
			PtRes::json_rpc_err(1,"口令不正确！");
		}
	}
	static function getModel(){
		$model = PtReq::get('model');
		$flag = PtReq::get('flag');
		$func = PtReq::get('func');
		
		if($flag && $func){

			$m = "Model".ucfirst($model);
			if(class_exists($m)){

			}else{
				include PATH_PTAPP.'/Model/'.ucfirst($model).".model.php";
			}
			$mObj = new $m;
			return $mObj->$func();
		}
		
		if(PtReq::get('id')){
			if($model == 'tables' || $model == 'schema'){
				$res = PdoDb::getObj('project')->getOne("select * from `".$model."` where id = ".PtReq::get('id'));
			}else{
				$res = PdoDb::getObj()->getOne("select * from `".$model."` where id = ".PtReq::get('id'));
			}
			PtRes::json_rpc_suc(array('row'=>$res,'model'=>$model,'schema'=>array()));

		}else{
			$obj = new PtModel();			
			$schema = ModelDatabase::getSchemas($model);			
			$res = $obj->table($model)->limit(30)->rows();
			PtRes::json_rpc_suc(array('rows'=>$res,'model'=>$model,'schema'=>$schema));
		}
		
	}
	static function logout(){
		unset($_SESSION['admin']);
	}
}
class PtReq{
	static public $ajax = false;
	static public $method = false;

	static function init(){
		self::$ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']);
		self::$method = strtolower($_SERVER['REQUEST_METHOD']);
	}
	static function get($key = '',$type = 'string'){
		return self::getVal($key,$type,'get');
	}
	static function post($key = '',$type = 'string'){
		return self::getVal($key,$type,'post');
	}
	static function getVal($key,$type,$method){	

		if($method == 'post'){
			if(empty($_POST[$key]))
				return '';
			else
				$v = $_POST[$key];
		}else{
			if(empty($_GET[$key]))
				return '';
			else
				$v = $_GET[$key];
		}

		switch ($type) {
			case 'int':
				$v = intval($v);
				break;
			case 'float':
				$v = floatval($v);
				break;
			case 'bool':
				$v = boolval($v);
				break;
			case 'html':
				//ENT_NOQUOTES 
				//ENT_QUOTES
				$v = htmlentities(trim($v),ENT_NOQUOTES,"UTF-8");
				break;
			case 'array':
				$v = $v;
				break;
			default:				
				$v = trim($v);
				break;
		}
		return $v;
	}
}

class PtVali{
	static function regx($val,$title = '' ,$regx = ''){	
		var_dump(preg_match($regx, $val));
		if(!preg_match($regx, $val)){
			die('error|'.$title.'不合法');
		}
	}

	static function len($val,$title,$max,$min = 0){
		$l = mb_strlen($val,'utf-8');
		if($min > 0 && $l < $min){
			die('error|'.$title.'长度不能小于 '.$min);
		}

		if($l > $max && $max >0){
			die('error|'.$title.'长度不能大于 '.$max);
		}
	}

	static function word($val,$title,$max,$min = 0){
		self::len($val,$title,$max,$min);
		if(!preg_match("/^[\w]+$/", $val)){
			die('error|'.$title.'不合法');
		}
	}

	static function url($val,$title,$max,$min = 0){
		self::len($val,$title,$max,$min);
		if(!filter_var($val, FILTER_VALIDATE_URL)){
			die('error|'.$title.'不合法 ');
		}
	}
	
	static function cn($val,$title,$max,$min = 0){
		self::len($val,$title,$max,$min);
		if(!preg_match("/^[\x{4e00}-\x{9fa5}]+$/u", $val)){
			die('error|'.$title.'不合法');
		}

	}
	static function ip($val,$title,$max,$min = 0){
		self::len($val,$title,$max,$min);
		if(!filter_var($val, FILTER_VALIDATE_IP)){
			die('error|'.$title.'不合法 ');
		}
	}
	static function date($val,$title){
		if(!self::checkDate($val)){
			die('error|'.$title.'不合法 ');
		}
	}
	static function datetime($val,$title){
		if(!self::checkDateTime($val)){
			die('error|'.$title.'不合法 ');
		}
	}

	static function email($val,$max,$min = 0){
		$title = '邮箱';
		self::len($val,$title,$max,$min);

		if(!filter_var($val, FILTER_VALIDATE_EMAIL)){
			die('error|'.$title.'不合法 ');
		}
	}
	static function idcard($val){
		$title = '身份证';
		self::len($val,$title,18,15);
		if(!PtIdcard::valid($val)){
			die('error|'.$title.'不合法 ');
		}
	}
	static function zip(){
		$title = '邮箱';
		self::len($val,$title,6,6);
		if(!preg_match("/^[0-9]d{5}$/",$str)){
			die('error|'.$title.'不合法 ');
		}
	}
	static function mobile($val){
		$title = '手机号';
		self::len($val,$title,11,11);
		//13[0-9]{1}[0-9]{8}$|15[0189]{1}[0-9]{8}$|18[0-9]{9}
		if(!preg_match("/^13[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{9}$/",$val)){    
		   die('error|'.$title.'不合法 ');
		} 
	}

	static function checkDateTime($str) {
	    if (date('Y-m-d H:i:s', strtotime($str)) == $str) {
	        return true;
	    } else {
	        return false;
	    }
	}
	static function checkDate($str) 
	{ 	  
	  $stamp = strtotime($str); 
	  if (!is_numeric($stamp)) 
	     return FALSE; 
	  //echo $stamp," : ",date('m', $stamp),"-", date('d', $stamp),"-", date('Y', $stamp);
	  //checkdate(month, day, year) 
	  if ( checkdate(date('m', $stamp), date('d', $stamp), date('Y', $stamp)) ) 
	  { 
	     return TRUE; 
	  } 
	  return FALSE; 
	} 
	static function validEmail($email)
	{
	   $isValid = true;
	   $atIndex = strrpos($email, "@");
	   if (is_bool($atIndex) && !$atIndex)
	   {
	      $isValid = false;
	   }
	   else
	   {
	      $domain = substr($email, $atIndex+1);
	      $local = substr($email, 0, $atIndex);
	      $localLen = strlen($local);
	      $domainLen = strlen($domain);
	      if ($localLen < 1 || $localLen > 64)
	      {
	         // local part length exceeded
	         $isValid = false;
	      }
	      else if ($domainLen < 1 || $domainLen > 255)
	      {
	         // domain part length exceeded
	         $isValid = false;
	      }
	      else if ($local[0] == '.' || $local[$localLen-1] == '.')
	      {
	         // local part starts or ends with '.'
	         $isValid = false;
	      }
	      else if (preg_match('/\\.\\./', $local))
	      {
	         // local part has two consecutive dots
	         $isValid = false;
	      }
	      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
	      {
	         // character not valid in domain part
	         $isValid = false;
	      }
	      else if (preg_match('/\\.\\./', $domain))
	      {
	         // domain part has two consecutive dots
	         $isValid = false;
	      }
	      else if(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
	                 str_replace("\\\\","",$local)))
	      {
	         // character not valid in local part unless 
	         // local part is quoted
	         if (!preg_match('/^"(\\\\"|[^"])+"$/',
	             str_replace("\\\\","",$local)))
	         {
	            $isValid = false;
	         }
	      }
	      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
	      {
	         // domain not found in DNS
	         $isValid = false;
	      }
	   }
	   return $isValid;
	}
}
class PtIdcard {
    static public function valid($idNum) {
        if (strlen($idNum) == 18) {
            return self::idcardCheckSum18($idNum);
        } elseif ((strlen($idNum) == 15))    {
            $idNum = self::idcard_15to18($idNum);
            return self::idcardCheckSum18('"' . $idNum . '"');
        } else    {
            return false;
        }
    }

    // 计算身份证校验码，根据国家标准GB 11643-1999
    static public function idcardVerifyNumber($idcardBase)
    {
        if(strlen($idcardBase) != 17)
        {
            return false;
        }

        //加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);

        //校验码对应值
        $verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        $checksum = 0;
        for ($i = 0; $i < strlen($idcardBase); $i++)
        {
            $checksum += substr($idcardBase, $i, 1) * $factor[$i];
        }
        $mod = $checksum % 11;
        $verify_number = $verify_number_list[$mod];
        return $verify_number;
    }

    // 将15位身份证升级到18位
    static public function idcard_15to18($idcard){
        if (strlen($idcard) != 15){
            return false;
        }else{

            // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
            if (array_search(substr($idcard, 12, 3), array('996', '997', '998', '999')) !== false){
            $idcard = substr($idcard, 0, 6) . '18'. substr($idcard, 6, 9);
            }else{
            $idcard = substr($idcard, 0, 6) . '19'. substr($idcard, 6, 9);
            }
        }
        $idcard = $idcard . self::idcard_verify_number($idcard);
        return $idcard;
    }

    // 18位身份证校验码有效性检查
    static public function idcardCheckSum18($idcard){
        if (strlen($idcard) != 18){ return false; }
        $idcard_base = substr($idcard, 0, 17);
        if (self::idcardVerifyNumber($idcard_base) != strtoupper(substr($idcard, 17, 1))){
            return false;
        }else{
            return true;
        }
    }
}
class PtRegx{
	static function test($str,$type){			
		switch ($type) {
			case 'email':
				return intV($str);
				break;
			
			default:
				# code...
				break;
		}
	}
	static function intV($int){
		if(is_int($int)){

		}
	}
}
class PtRes{
	//{"jsonrpc": "2.0", "method": "subtract", "params": [42, 23], "id": 1}
	static function json_rpc_suc($result,$id = ''){
		$res = array(
				'jsonrpc'=>'2.0',
				'id'=>$id,
				'result'=>$result
			);
		echo json_encode($res);
	}
	static function json_rpc_err($code,$message,$id = ''){		
		$res = array(
				'jsonrpc'=>'2.0',
				'id'=>$id,
				'error'=>array(
						'code'=>$code,
						'message'=>$message,
					)
			);
		echo json_encode($res);
	}
}
class PtView{
	static $config;
	static function admin($filename){
		return self::view($filename,'admin');
	}
	
	static function tpl($path,$theme = ''){
		if(!$theme){
			$theme = self::$config['theme'];
		}
		
		$path = PATH_PTAPP.'/Tpl//'.self::$config['theme'].'/'.$path.'.php';
		//die($path);
		return $path;
	}
	
	static function view($filename){	
		$path = PATH_PTAPP.'/View/'.$filename.'.php';
		//print_r($_SERVER['REQUEST_URI']);
		//die($path);
		if(DEBUG){
			if(!file_exists($path)){				
				if(PtReq::get('__c')){
					//echo $path;
					PtPath::mkdir(dirname($path));
					file_put_contents($path, "<?php\n");
				}else{
					die("<button onclick='location.href=\"".$_SERVER['REQUEST_URI']."?__c=1\"'>create:".$path."</button>");
				}
			}
		}
		return $path;
	}
}
class PtHtml{
	static $v = "0.0.1";
	static function detailTable($schema,$row = array()){
		ob_start();
		include PtView::admin('ui/table_detail');
		return ob_get_clean();
	}
	static function addTable($schema){
		ob_start();
		include PtView::admin('ui/table_add');
		return ob_get_clean();
	}
	static function adminTable($sql,$schema,$option = array('detail'=>true,'add'=>true,'del'=>true),$table = ''){
		if($table){
			$obj = new PtModel();
			$page = PtReq::get('page','int')?PtReq::get('page','int'):1;
			$data = $obj->table($table)->select("*")->pager(30,$page);
			$rows = $data['rows'];
			$pager = $data['pager'];
		}else{
			$rows = PdoDb::getObj()->getAll($sql);		
			$pager = '';
		}
		
		ob_start();
		include PtView::admin('ui/table');
		return ob_get_clean(); 
		
	}
	static function link($name,$rel='stylesheet',$type='text/css'){

		if(strpos($name, 'http://') === 0){
			$href = $name;
		}else{				
			if(PtApp::$config['static_host']){
				$href = rtrim(PtApp::$config['static_host'],'/').'/'.ltrim($name,'/');
			}else{
				$href = $name;
			}			
		}
		if(PtApp::$config['debug']){
			self::$v = time();
		}

		return '<link rel="'.$rel.'" type="'.$type.'" href="'.$href.'?v='.self::$v.'">'."\n";
	}
	static function script($name){
		if(strpos($name, 'http://') === 0){
			$src = $name;
		}else{				
			if(PtApp::$config['static_host']){
				$src = rtrim(PtApp::$config['static_host'],'/').'/'.ltrim($name,'/');
			}else{
				$src = $name;
			}			
		}	
		return '<script src="'.$src.'?v='.self::$v.'"></script>'."\n";
	}
	static function table($sql,$parame){
		$res = PdoDb::getObj()->getAll($sql);
		//echo $sql;
		//print_r($res);
		$html = '<table class="table table-hover table-striped table-bordered table-condensed"><thead><tr>';
		$head = '';
		$body = array();
		foreach ($parame as $key => $value) {
			$html .= '<th>'.$value.'</th>';
			$body[] = $key;
		}

		$html .= '<th>操作</th></tr></thead><tbody>';


		foreach ($res as $k => $v) {
			$html .='<tr>';
			foreach ($body as $vv) {
				$html .= '<td>'.$v[$vv].'</td>';		
			}
			$html .='<td><button class="btn btn-mini btn-primary" onclick="location.href=\'/index.php?mod=agent&rid='.$v['id'].'\'">详细</button></td></tr>';
		}

		$html .= '</tbody></table>';
		echo $html;
	}
}

class PtPath{
	/**
	 * __mkdirs
	 *
	 * 循环建立目录的辅助函数
	 *
	 * @param dir    目录路径
	 * @param mode    文件权限
	 */
	static function mkdir($dir, $mode = 0777)
	{	
		//echo $dir;
		//exit;
		if (!is_dir($dir)) {
			self::mkdir(dirname($dir), $mode);
			return mkdir($dir, $mode);
		}
		return true;
	}
}

class PtModel {
	var $driver;
	var $field = "*";
	var $where = "";
	var $order = "";
	var $limit = '';
	var $total = 0;
	var $args = array();
	var $countField = '';
	var $join = '';
	var $joinField = '';
	static $infos;
	function __construct($key="default") {
		$this->driver = PdoDb::getObj($key);
	}
	public function init(){
		$this->driver->init();
	}
	
	public function getSchema($table){		
		$this->driver->getSchema($table);
	}
	
	/**
	 * 直接运行SQL 无返回
	 */	
	public function runSql($sql,$args = array())
	{
		$this->driver->runSql($sql,$args);
	}
	
	/**
	 * 直接运行sql 返回单行
	 */
	 
	public function getOne($sql,$args = array())
	{
		return $this->driver->getOne($sql,$args);
	}
	/**
	 * 直接运行sql 返回多行
	 */
	 
	public function getAll($sql,$args = array())
	{
		return $this->driver->getAll($sql,$args);
	}
	
	/**
	 * 设置TABLE
	 */
	public function table($table)
	{
		$this->table = $table;
		return $this;
	}
	
	/**
	 *  格式化 limit
	 */	 
	public function limit($offset=0,$size = 0)
	{
		$offset = intval($offset);
		$size= intval($size);
		if(!$size){
			$this->limit = ' limit '.$offset;
		}else{
			$this->limit = ' limit '.$offset.','.$size;
		}
		return $this;
	}
	
	public function field($field)
	{
		if($field){
			if(is_array($field)){
				$field = implode(',', $field);
			}
			$this->field = $field;
		}
	
		return $this;
	}
	public function from($table)
	{		
		$this->table = $table;	
		$this->_as = substr($table, 0,1);
		return $this;
	}
	public function fromAs($_as)
	{
		$this->_as = $_as;
		return $this;
	}
	public function leftjoin($table,$_as,$_on,$field = '')
	{
		return $this->joinTable('left', $table, $_as, $_on,$field);
	}
	public function rightjoin($table,$_as,$_on,$field = '')
	{
		
		return $this->joinTable('right',$table, $_as, $_on,$field);
	}
	public function joinTable($type,$table,$_as,$_on,$field = '')
	{
		$this->join .= ' '.$type.' join '.$table.' as '.$_as.' on '.$_on.' ';
	
		if($field){
			$this->joinField .= ','.$this->parseField($field, $_as);
		}
		return $this;
	}
	public function select($field = '*')
	{
		if($field){
			$this->field = $field;
		}
	
		return $this;
	}
	/**
	 *  格式化 where 
	 */	 
	public function where($condition = '')
	{
		//$this->where = ' where id in ('.trim($id,',').')';
		if(!$condition){
			return $this;
		}
		$where = " where ";
		$whereArgs = array();
		if(is_array($condition)){			
			foreach ($condition as $key => $value) {
				if($this->join){
					$key = $this->_as."_".$key;
				}
				
				$value = trim($value," ");
				$opt = substr($value, 0,2);
				if(in_array($opt, array(">=","<=","in"))){
					$where .= $key.' '.$opt.' :w_'.$key." and";
					$whereArgs['w_'.$key] = str_replace($opt, '', $value);	
					continue;
				}
				$opt = substr($value, 0,1);
				if(in_array($opt, array(">","<","="))){
					$where .= $key.' '.$opt.' :w_'.$key." and";
					$whereArgs['w_'.$key] = str_replace($opt, '', $value);	
					continue;
				}
				$opt = "=";
				$where .= $key.' '.$opt.' :w_'.$key." and";
				$whereArgs['w_'.$key] = $value;				
			}			
			$where = rtrim($where,"and");
		}
		elseif(strpos($condition,',') !== false){	
			$where .= 'id in ('.$condition.')';
		}
		elseif (is_numeric($condition) || intval($condition) > 0){
			$where .= " ".$this->table.".id = :w_id";
			if(!key_exists('w_id', $whereArgs)){
				$whereArgs['w_id'] = intval($condition);
			}			
		}else{
			$where .= $condition;
		}
		
		$this->where = $where;
		$this->args = array_merge($this->args,$whereArgs);		
		return $this;
	}

	/**
	 *  格式化 order 
	 */	 
	public function order($item = '')
	{
		if($item){
			$order = "order by ";
			if(is_array($item)){
				//todo
			}else{
				if($this->join){
					$order .= $this->_as.'.'.$item;
				}else{
					$order .= $item;
				}
				
			}			
			$this->order = $order;
		}		
		return $this;
	}
	
	function parseField($field,$_as){
		$res = "";
		if(strpos($field, ',')!==False){
			$tmp = str_replace(',', ','.$_as.'.', trim($field, ' '));
			$res = $_as.".".$tmp;
		}else{
			$res = $_as.".".$field;
		}		
		
		return $res;
	}
	function getRows($type = 0){		
		if($this->join){
			$from = '`'.$this->table.'` as '. $this->_as;
			$join = $this->join;		
			$joinField = '';
			if($this->joinField){
				$joinField = ' '.$this->joinField;
			}
			$this->field = $this->parseField($this->field,$this->_as).$joinField;	
			
		}else{
			$from = '`'.$this->table.'`';
			$join = '';
		}
		
		
		if($this->countField){
			$sqlCount = 'select '.$this->countField.' from '.$from.$join.' '.$this->where.' '.$this->order;
		}
		
		$sql = 'select '.$this->field.' from '.$from.$join.' '.$this->where.' '.$this->order.$this->limit;
		//print_pre($sql);
		if($type){
			$res = $this->driver->getOne($sql,$this->args);			
		}else{
			if($this->countField){
				//echo $sqlCount;
				$count = $this->driver->getOne($sqlCount,$this->args);
				//print_pre($count);
				$this->total = $count['total'];
			}
			
			$res = $this->driver->getAll($sql,$this->args);
		}
		
		//print_pre($res);
		return $res;
	}
	/**
	 * 查询数据
	 * 用于查询单行
	 */	
	function row($id = ''){
		if($id){
			$this->where($id);
		}
		$res = $this->getRows(1);
		$this->where = $this->order = $this->limit= "";				
		$this->field = "*";	
		$this->args = array();;
		return $res;
	}
	
	function get($id = ''){
		return $this->row($id);
	}
	
	/**
	 * 查询数据
	 * 用于查询多行
	 */
	function rows($id = ''){
		if($id){
			$this->where($id);
		}
		$res = $this->getRows();
		$this->where = $this->order = $this->limit= "";		
		$this->field = "*";
		$this->args = array();
		//	print_pre($res);
		return $res;
	}
	
	public function count($field = 'id')
	{		
		if($this->join){
			$t = $this->_as;
		}else{
			$t = $this->table;
		}
		
		if(!$field){
			$this->countField = 'count('.$t.'.id'.') as total';
		}else{
			$this->countField =  'count('.$t.'.'.$field.') as total';
		}
		
		return $this;
	}
	
	public function pager($pageSize = 10,$pageCur=0,$pageKey = "page")
	{
		if(!$this->countField){
			$this->count();
		}		
		if($pageCur){
			$pageCur = $pageCur;
		}else{
			if(isset($_GET[$pageKey])){
				$pageCur = intval($_GET[$pageKey]);
			}else{
				$pageCur = 1;
			}			
		}
		
		if(!$pageCur){
			$pageCur = 1;
		}
		
		$offset = ($pageCur-1)*$pageSize;
		$this->limit($offset,$pageSize);
		$res = array();
		$res['rows'] =  $this->rows();
		$res['total'] = $this->total;
		$pager = new PtPager($pageSize,$res['total'],$pageCur,10,2);
		$res['pager'] = $pager->pageBar;
		$this->countField = '';
		$this->total = 0;
		$this->where = $this->order = $this->limit= "";		
		$this->field = "*";
		$this->args = array();
		//print_pre($res);
		return $res;
	}	
	
	/**
	 * 删除行
	 */
	public function delete($condition = '')
	{	
		$this->where($condition);		
		if($this->where){
			$sql = 'delete from '.$this->table.$this->where;
			$this->runSql($sql,$this->args);
		}
		$this->where = '';
		$this->args = array();
	}
	
	/**
	 *
	 * 判断是否存在
	 * @param int $data
	 * or
	 * @param string $data such as :"id=1"
	 */
	public function exists($data)
	{	
		if(is_numeric($data)){
			$res = $this->row($data);
		}elseif(is_array($data)){				
			$res = $this->where($data)->row();
		}
		else{
			$res = $this->where($data)->row();
		}
		return $res?$res:FALSE;
	}
	/**
	 * 生成 Insert SQL
	 */
	public function parseInsertSql($rows)
	{		
		$field = '';
		$val = '';
		foreach ($rows as $key) {
			$field .= ' `'.$key.'` ,';
			$val   .= ' :'.$key.' ,';
		}
		$val = rtrim($val,',');
		$val = rtrim($val,' ');
		$field = rtrim($field,',');
		$field = rtrim($field,' ');
		$sql = 'INSERT INTO '.$this->table.' ('.$field.') values('.$val.');';
		return $sql;		
	}
	/**
	 * 插入表
	 */
	public function insert($row)
	{
		
		$sql = $this->parseInsertSql(array_keys($row));	
		//print_r($row);
		$this->driver->runSql($sql,$row);
		return $this->driver->lastInsertId();
	}	
	
	public function parseUpdateSql($field)
	{
		$ff = "";
		$sql = "update ".$this->table;
		foreach ($field as $key) {
			$ff .= ' `'.$key."`= :".$key." ,";
		}
		$ff = rtrim($ff,",");
		$ff = rtrim($ff," ");
		$sql .= " set".$ff." ";
		return $sql;
	}
	/**
	 * 更新表
	 */
	public function update($field)
	{
		$this->args = array_merge($this->args,$field);
		
		if(is_array($field)){
			$sql = $this->parseUpdateSql(array_keys($field)).$this->where;
		}else{
			$sql = 'update '.$this->table.' set ' .$field . $this->where;
		}		
		//print_pre($this->args);
		$this->driver->runSql($sql,$this->args);
		$this->where = "";		
		$this->args = array();		
		
	}
	
	/**
	 *  生成 CREATE TAIBLE sql 行
	 *  `id` int(11) default NULL,
	 */
	static public function genCreateTableSqlRow($name,$property,$type = 'sqlite')
	{				
		$rowstr = "";
		if($type == 'sqlite'){			
			if($property['type'] == 'int' || $property['type'] == 'tinyint' || $property['type'] == 'smallint' || $property['type'] == 'mediumint' || $property['type'] == 'bigint'){
				$field_type = 'INTEGER';
			}else{
				$field_type = 'TEXT';
			}			
			//switch ($property['type']) {
				//case 'int':
					//$field_type = 'INTEGER';
					//break;		
				//case 'vachar':
					//$field_type = 'TEXT';
					//break;
				//default:
					//$field_type = 'TEXT';
				//break;
			//}
			
			$rowstr = '`'.$name.'` '.strtoupper($field_type);
			
		}else{		
				
			//if($property['type'] == 'datetime' || $property['type'] == 'date'){
			if(intval($property['max']) == 0){
				//echo 11;
				$type = strtoupper($property['type']);		
			}else{
				$type = strtoupper($property['type']).'('.$property['max'].')';		
			}
			
			$rowstr = '`'.$name.'` '.$type;			
			if($property['min']){
				$rowstr .= ' NOT NULL';
			}
			if($property['ac']){				             
				$rowstr .= ' AUTO_INCREMENT';
			}					
		}
		if(!$property['pk']){
			if($property['desc']){
				$rowstr .= " COMMENT '".$property['desc']."'";
			}else{
				$rowstr .= " COMMENT '".$property['title']."'";
			}
		}
		
		$rowstr .=',';
		return $rowstr;
	}
	/**
	 * 生成 CREATE TAIBLE sql
	 * @param unknown $tableName
	 * @param unknown $table
	 * @param string $type
	 * @return string
	 */
	static public function genCreateTableSql($table,$type = 'sqlite')
	{
		if(!$table['schema']){			
			return "未添加字段";
		}
		$tableName = $table['name'];
		//$sql = "\nDROP TABLE IF EXISTS `".$tableName."`;";
		$sql = "\n".'CREATE TABLE IF NOT EXISTS `'.$tableName.'` ('."\n\t";
		ksort($table['schema']);
		//print_pre($table['schema']);
		foreach ($table['schema'] as $key => $value) {
			$sql .= self::genCreateTableSqlRow($value['name'],$value,$type)."\n\t";
		}
		
		if($table['pk']){
			$pk = $table['pk'][0];
			$sql .= 'PRIMARY KEY(`'.$pk.'`)'."\n";
		}else{
			//$sql .= "\n";
			$sql = substr($sql, 0,-3)."\n";
		}
		
		$desc = $table['desc'];
		
		if(!$desc){
			$desc = $table['title'];
		}
		if($type=="mysql"){
			$sql .= ")ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='".$desc."'"."\n";
		}else{
			$sql .= ');'."\n";
		}
		//echo $sql;
		return $sql;
	}
	
}


class PtPager{
	private  $pageSize;//每页显示的条目数  
	private  $total;//总条目数  
	private  $pageCur;//当前被选中的页  
	private  $pageMaxNum;//每次显示的页数  
	private  $pageNums;//总页数  
	private  $pageArray = array();//用来构造分页的数组  
	private  $pageStyle;//显示分页的类型  
	public   $pageBar = "";
	private  $pageUrl = '';
	/* 
	__construct是SubPages的构造函数，用来在创建类的时候自动运行. 
	@$each_disNums   每页显示的条目数 
	@nums     总条目数 
	@current_num     当前被选中的页 
	@sub_pages       每次显示的页数 
	@subPage_link    每个分页的链接 
	@subPage_type    显示分页的类型 
	
	当@subPage_type=1的时候为普通分页模式 
	     example：   共4523条记录,每页显示10条,当前第1/453页 [首页] [上页] [下页] [尾页] 
	     当@subPage_type=2的时候为经典分页样式 
	     example：   当前第1/453页 [首页] [上页] 1 2 3 4 5 6 7 8 9 10 [下页] [尾页] 
	*/ 		
	function __construct($pageSize,$total,$pageCur,$pageMaxNum,$pageStyle =1){  
		$this->pageSize = intval($pageSize);  
		$this->total   = intval($total);  
		
		if(!$pageCur){  
			$this->pageCur = 1;  
		}else{
			$this->pageCur = intval($pageCur);  
		}
		
		$this->pageMaxNum = intval($pageMaxNum);
		
		$this->pageNums   = ceil($total/$pageSize);
		$curl = preg_replace("/&?page=[\d]*/", '', $_SERVER['REQUEST_URI']);
	
		/*
		if(strpos($curl, '&page=') !== FALSE){
			//$t = explode('page=', $curl);
			//$this->pageUrl = $t[0].'page=';
			$curl = str_replace('&page=', '', $curl);
		}
		if(strpos($curl, 'page=') !== FALSE){
			//$t = explode('page=', $curl);
			//$this->pageUrl = $t[0].'page=';
			$curl = str_replace('page=', '', $curl);
		}
		
		*/
		if(strpos($curl, '?') !== FALSE){
			$this->pageUrl = $curl.'&page=';
		}else{
			$this->pageUrl = $curl.'?page=';
		}
		$this->showPages($pageStyle); 
		
	}  
	
	
	/* 
	destruct析构函数，当类不在使用的时候调用，该函数用来释放资源。 
	*/ 
	function __destruct(){  
		
	}

	/* 
	show_SubPages函数用在构造函数里面。而且用来判断显示什么样子的分页   
	*/ 
	function showPages($type){  
		if($type == 1){  
			$this->pageCss1();  
		}elseif ($type == 2){  
			$this->pageCss2();  
		}  
	}  
	
	
	/* 
	用来给建立分页的数组初始化的函数。 
	*/ 
	
	function initArray(){  
		for($i = 0; $i < $this->pageMaxNum; $i++){  
			$this->pageArray[$i] = $i;  
		}  
		return $this->pageArray;  
	}  
	
	
	/* 
	construct_num_Page该函数使用来构造显示的条目 
	即使：[1][2][3][4][5][6][7][8][9][10] 
	*/ 
	function construct_num_Page(){  
		if( $this->pageNums < $this->pageMaxNum ){
			  
			$current_array=array();  
			for( $i=0 ; $i < $this->pageNums; $i++){   
				$current_array[$i]=$i+1;  
			}
			
		}else{
			  
			$current_array = $this->initArray();  
			if($this->pageCur <= 3){  
				for($i=0;$i<count($current_array);$i++){  
					$current_array[$i]=$i+1;  
				}
				
			}elseif ($this->pageCur <= $this->pageNums && $this->pageCur > $this->pageNums - $this->pageMaxNum + 1 ){  
				for($i=0;$i<count($current_array);$i++){  
					$current_array[$i]=($this->pageNums)-($this->pageMaxNum)+1+$i;  
				}  
			}else{  
				for($i=0;$i<count($current_array);$i++){  
					$current_array[$i]=$this->pageCur-2+$i;  
				}  
			}  
		}
		
		return $current_array;  
	}  
	
	/* 
	构造普通模式的分页 
	共4523条记录,每页显示10条,当前第1/453页 [首页] [上页] [下页] [尾页] 
	*/ 
	function pageCss1(){  
		$subPageCss1Str="<div id=\"pages\">";  
		$subPageCss1Str.="<a class=\"a1\">共".$this->total."条记录</a>";  
		$subPageCss1Str.="<a class=\"a1\">每页显示".$this->pageSize."条</a>";  
		$subPageCss1Str.="当前第".$this->pageCur."/".$this->pageNums."页 ";  
		if($this->pageCur > 1){  
			$firstPageUrl= $this->pageUrl."1";  
			$prewPageUrl=$this->pageUrl.($this->pageCur-1);  
			$subPageCss1Str.="<a href='$firstPageUrl'>首页</a> ";  
			$subPageCss1Str.="<a href='$prewPageUrl'>上一页</a> ";  
		}else {  
			$subPageCss1Str.="首页 ";  
			$subPageCss1Str.="上一页 ";  
		}  
		
		if($this->pageCur < $this->pageNums){  
			$lastPageUrl=$this->pageUrl.$this->pageNums;  
			$nextPageUrl=$this->pageUrl.($this->pageCur+1);  
			$subPageCss1Str.=" <a href='$nextPageUrl'>下一页</a> ";  
			$subPageCss1Str.="<a href='$lastPageUrl'>尾页</a> ";  
		}else {  
			$subPageCss1Str.="下一页 ";  
			$subPageCss1Str.="尾页 ";  
		}  		
		
		$this->pageBar =  $subPageCss1Str.'</div>';  	
	}  
	
	
	/* 
	构造经典模式的分页 
	当前第1/453页 [首页] [上页] 1 2 3 4 5 6 7 8 9 10 [下页] [尾页] 
	*/ 
	function pageCss2(){
		$subPageCss2Str="<div class=\"pagination pagination-right\"><ul>";  
		if($this->pageCur > 1){  
			$subPageCss2Str.="<li class=\"disabled\"><a>当前第".$this->pageCur."页 / 共".$this->pageNums."页</a></li>";
			$firstPageUrl=$this->pageUrl."1";  
			$prewPageUrl=$this->pageUrl.($this->pageCur-1);  
			
			$subPageCss2Str.='<li><a href='.$firstPageUrl.'>首页</a></li>';
			$subPageCss2Str.='<li><a href='.$prewPageUrl.'><<</a></li>';
		}else {  
			$subPageCss2Str.="<li class=\"disabled\"><a>当前第".$this->pageCur."页 / 共".$this->pageNums."页</a></li>";
			$subPageCss2Str.="<li class=\"disabled\"><a>首页</a></li> ";  
			$subPageCss2Str.="<li class=\"disabled\"><a><<</a></li> ";  
		}  
		
		$a=$this->construct_num_Page();  
		for($i=0;$i<count($a);$i++){  
			$s=$a[$i];  
			if($s == $this->pageCur ){  
				$subPageCss2Str.="<li class=\"disabled\"><a>".$s."</a></li>";  
			}else{  
				$url=$this->pageUrl.$s;  
				$subPageCss2Str.="<li><a href='$url'>".$s."</a></li>";  
			}  
		}  
		
		if($this->pageCur < $this->pageNums){  
			$lastPageUrl=$this->pageUrl.$this->pageNums;  
			$nextPageUrl=$this->pageUrl.($this->pageCur+1);  			 
			$subPageCss2Str.='<li><a href='.$nextPageUrl.' >>></a></li> ';
			$subPageCss2Str.='<li><a href='.$lastPageUrl.' >尾页</a></li> ';
			
		}else {  
			//$subPageCss2Str.="[下一页] ";  
			//$subPageCss2Str.="<a class=\"a1\">[尾页]</a> ";  
			$subPageCss2Str.="<li class=\"disabled\"><a>>></a></li> ";
			$subPageCss2Str.="<li class=\"disabled\"><a>尾页</a></li> ";
		}  
		$this->pageBar =  $subPageCss2Str."</ul></div>";
	}
}

/**
$type = $_GET['dir'];
$dir =  $_GET['dir'];		
$upload_field = 'imgFile';

$save_url = '/static/upload/';
$save_path = __DIR__.'/static/upload/';
$res = false;


$upload = new PtUpload($upload_field,$type,$dir,$save_url,$save_path,$res);

if($upload->errno){
	echo json_encode(array('error'=>1,'message'=>$upload->errmsg));
}else{
	echo json_encode(array('error'=>0,'url'=>$upload->url));
}
*/
class PtUpload{
	public $errno = False;
	public $errmsg = '';
	public $url = "";
	function PtUpload($field,$type="image",$dir_name = 'image',$save_url = '/',$save_path = '/',$res=false) {
		//定义允许上传的文件扩展名
		$ext_arr = array(
			'image' => array('gif', 'jpg', 'jpeg', 'png', 'bmp'),
			'flash' => array('swf', 'flv'),
			'media' => array('swf', 'flv', 'mp3', 'wav', 'wma', 'wmv', 'mid', 'avi', 'mpg', 'asf', 'rm', 'rmvb'),
			'file' => array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'htm', 'html', 'txt', 'zip', 'rar', 'gz', 'bz2'),
		);
		//最大文件大小
		$max_size = 1000000;

		//PHP上传失败
		if (!empty($_FILES[$field]['error'])) {
			switch($_FILES[$field]['error']){
				case '1':
					$error = '超过php.ini允许的大小。';
					break;
				case '2':
					$error = '超过表单允许的大小。';
					break;
				case '3':
					$error = '图片只有部分被上传。';
					break;
				case '4':
					$error = '请选择图片。';
					break;
				case '6':
					$error = '找不到临时目录。';
					break;
				case '7':
					$error = '写文件到硬盘出错。';
					break;
				case '8':
					$error = 'File upload stopped by extension。';
					break;
				case '999':
				default:
					$error = '未知错误。';
			}
			return $this->alert($error);
		}

		//有上传文件时
		if (empty($_FILES) === false) {
			//原文件名
			$file_name = $_FILES[$field]['name'];
			//服务器上临时文件名
			$tmp_name = $_FILES[$field]['tmp_name'];
			//文件大小
			$file_size = $_FILES[$field]['size'];
			//检查文件名
			if (!$file_name) {
				return $this->alert("请选择文件。");

			}

			
			//检查是否已上传
			if (@is_uploaded_file($tmp_name) === false) {
				return $this->$this->alert("上传失败。");
			}
			//检查文件大小
			if ($file_size > $max_size) {
				return $this->alert("上传文件大小超过限制。");
			}
			//检查目录名
			if (empty($ext_arr[$type])) {
				return $this->alert("上传类型不正确。");
			}
			//获得文件扩展名
			$temp_arr = explode(".", $file_name);
			$file_ext = array_pop($temp_arr);
			$file_ext = trim($file_ext);
			$file_ext = strtolower($file_ext);
			//检查扩展名
			if (in_array($file_ext, $ext_arr[$type]) === false) {
				$this->alert("上传文件扩展名是不允许的扩展名。\n只允许" . implode(",", $ext_arr[$type]) . "格式。");
			}
			
		
			$save_path .= $dir_name . "/";
			$save_url .= $dir_name . "/";


			$y = date("Y");
			$m = date("m");
			$d = date("d");
			if(!$res){						
				$save_path .= $y."/".$m."/".$d. "/";
				$save_url .= $y."/".$m."/".$d. "/";
			}

			//创建文件夹
			self::mkdir($save_path);
			
			//if (!function_exists('__mkdirs')) {
				//echo "system __mkdirs";
				
			//}else{
				//self::mkdir($save_path);
			//}
			//检查目录写权限
			if (@is_writable($save_path) === false) {
				return $this->alert("上传目录没有写权限。");
			}

			//alert_json($save_path);

			if(!$res){						
				$new_file_name = date("His") . '_' . rand(10000, 99999) . '.' . $file_ext;
			}else{
				$new_file_name = $file_name;
			}
			//新文件名
			

			//移动文件
			$file_path = $save_path . $new_file_name;
			if (move_uploaded_file($tmp_name, $file_path) === false) {
				return $this->alert("上传文件失败。");
			}
			//print_pre($file_path);
			@chmod($file_path, 0644);
			$this->url = $save_url . $new_file_name;
			//print_pre($this->url);
		}

		
	}
	/**
	 * __mkdirs
	 *
	 * 循环建立目录的辅助函数
	 *
	 * @param dir    目录路径
	 * @param mode    文件权限
	 */
	static function mkdir($dir, $mode = 0777)
	{	
		//echo $dir;
		//exit;
		if (!is_dir($dir)) {
			self::mkdir(dirname($dir), $mode);
			return mkdir($dir, $mode);
		}
		return true;
	}
	function alert($msg){
		$this->errno = True;
		$this->errmsg = $msg;
		return $this->errmsg;
	}
}
