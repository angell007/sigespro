<?php
include_once("class.dao.php");

class table {
	protected	$error = 0,
				$table,
				$exist 	= 0,
				$pk,
				$columns	= array(),
				$oldcolumns	= array(),
				$diff		= array(),
				$restricts	= array(),
				$drops 		= array(),
				$sRestricts = "",
				$newtable = "";
				
				
		public function connect() {
		global $MY_CONFIG;
		include($MY_CONFIG . "config.db.php");
		$oConn = new dao('mysql');
		$oConn->connect($db_host,$db_user,$db_password,$db_name);
		$oConn->setFetchMode("FETCH_ASSOC");

		return ($oConn);
	}
				
    	public function __construct() {
		$args = func_get_args();
		$num_args = func_num_args();

		$this->error = 0;
		if ($num_args == 1) {
			$oConn = self::connect();
			
			$db_host =$oConn->dbhost;
			$db_user =$oConn->dbuser;
			$db_password =$oConn->dbpassword;
			$db_name =$oConn->dbname;

			$link =  mysqli_connect($db_host,$db_user,$db_password, $db_name ) or die('No se pudo conectar: ' . mysqli_error($link));
			mysqli_select_db($link,$db_name) or die('No se pudo seleccionar la base de datos');

			$consulta = "show tables like '$args[0]'";

			$result = mysqli_query($link,$consulta) or die('Consulta fallida: ' . mysqli_error($link));

			$existe = mysqli_num_rows($result);

			$oConn->close();
			$this->table = $args[0];
			if($existe){
				$this->exist=1;
			}else{
				$this->exist=0;
				
			}
		}
	}

	
	protected function save_alter() {
		$oConn = self::connect();
		$oConn->setDebug=true;
		$actual = 0;
		$coma = "";
		if($this->newtable !="")
		{	$coma = "si";
			$keys="RENAME ".$this->newtable.",";
			$key="Id_".$this->table;
			$newkey="Id_".$this->newtable;
			$keys.="CHANGE ".$key." ".$newkey." int NOT NULL AUTO_INCREMENT"; 
		}
		$total=count($this->oldcolumns); 

		foreach ($this->oldcolumns as $column)
		{
		   if($actual== 0&&$coma=="si"){ $keys.=","; }
		   $keys.="CHANGE ".$column['oldname']." ".$column['name']." ".$column['type'];
		   $actual++;
		   if ($actual<$total){
		   		$keys.=",";
		   }
		}
		$total2=count($this->columns); 
		$actual2= 0 ;

		foreach ($this->columns as $column)
		{
		   if($actual2== 0&&$coma=="si"){ $keys.=",";  }
		   $keys.="ADD ".$column['name']." ".$column['type'];
		   $actual2++;
		   if ($actual2<$total2){
		   		$keys.=",";
		   }
		}
		$total3=count($this->drops); 
		$actual3= 0 ;

		foreach ($this->drops as $column)
		{
		   if($actual3== 0&&$coma=="si"){ $keys.=",";  }
		   $keys.="DROP ".$column['name'];
		   $actual3++;
		   if ($actual3<$total3){
		   		$keys.=",";
		   }
		}
		$sql="ALTER TABLE $this->table $keys";
		//echo  $sql;
		$oConn->execute($sql);;
		$oConn->close();
	}
	
	protected function save_create() {
		$oConn = self::connect();
		$oConn->setDebug=true;
		$actual = 0;
		$key="Id_".$this->table;
		$keys=$key." int NOT NULL AUTO_INCREMENT, Timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
		
		$total=count($this->columns);

		foreach ($this->columns as $column)
		{
		   if($actual==0){ $keys.=","; }
		   $keys.=$column['name']." ".$column['type'];
		   $actual++;
		   if ($actual<$total){
		   		$keys.=",";
		   }
		}
		$keys.=", PRIMARY KEY ($key)";
		$sql="CREATE TABLE $this->table ($keys)";
		$oConn->execute($sql);;
		$oConn->close();
	}
	public function save()	{
		if($this->exist == 0) {
			self::save_create();
		}else{
			self::save_alter(); 
		}
	}
	public function dropColumn($name){
			
		$drop["name"]=$name;
		$this->drops[] = $drop;
		
	}	
		
	public function addColumn($name,$type){
		$column = Array();
		$column["name"] = $name;
		$column["type"] = $type;
		$this->columns[] = $column;
	}	
			
	public function setName($newname){
		
		$this->newtable = $newname; 
	}
	public function setColumn($oldname,$name,$type){
		$oldcolumn = Array();
		$oldcolumn["name"] = $name;
		$oldcolumn["type"] = $type;
		$oldcolumn["oldname"] = $oldname;
		$this->oldcolumns[] = $oldcolumn;
	}	
	
	public function delete() {
		$oConn = self::connect();
		$sql="DROP TABLE $this->table";
		$oConn->execute($sql);
		$oConn->close();
	}
	public function clear() {
		$oConn = self::connect();
		$sql="TRUNCATE TABLE $this->table";
		$oConn->execute($sql);
		$oConn->close();
	}


	public function __destruct() {
		unset ($error);
		unset ($table);
		unset ($pk);
		unset ($pk_value);
		unset ($attribs);
	}	
}
?>