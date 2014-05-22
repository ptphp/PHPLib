<?php
class PtTable {
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
	function __construct($table = '',$key="default") {
		if($table){
			$this->table($table);
		}
		$this->driver = PtDb::init($key);
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
