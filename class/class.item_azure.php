<?php
include_once("class.dao_azure.php");

class item {
	protected	$error 		= 0,
				$table,
				$pk_value 	= 0,
				$pk,
				$attribs	= array(),
				$fields		= array(),
				$diff		= array(),
				$restricts	= array(),
				$sRestricts = "";

	public function connect() {
		global $MY_CONFIG;
		include($MY_CONFIG."config.db.php");
		$oConn = new dao('mysql');
		$oConn->connect($db_host,$db_user,$db_password,$db_name);
		$oConn->setFetchMode("FETCH_ASSOC");

		return ($oConn);
	}
	
	public function setRestrict($campo,$condicion,$valor) {
		if ( array_key_exists($key,$this->fields) ) {
			$this->restricts[] = array("key"=>$key,"cond"=>$condicion,"value"=>$valor);
			$this->sRestricts = self::getRestricts();
		}
	}
	
	protected function getRestricts() {
		foreach($this->restricts as $indice=>$valor) {
			$return .= $valor["key"].$valor["cond"]."'".$valor["value"]."' AND ";
		}
		$this->sRestricts = substr($return,1,strlen($return)-5);
	}
	
	public function load()	{
		$oConn = self::connect();
		
		$sqlSelect="SELECT * FROM $this->table WHERE $this->pk='".$this->pk_value."'";
		$result=$oConn->getRow($sqlSelect);

		if (!$result){
			$this->error = 1;
			$this->pk_value = 0;
		} else {
			$this->attribs = $result[0];
			foreach ($this->attribs as $key=>$value){
				$this->attribs[$key] = $value;
			}
		}
		$oConn->close();
	}
	
	protected function save_insert() {
		$oConn = self::connect();
		$oConn->setDebug=true;
		$actual = 0;
		$keys="";
		$values="";
		$total=count($this->attribs);

		foreach ($this->attribs as $key=>$value)
		{
		   $keys.="$key";
		   $values.="'".$value."'";
		   $actual++;
		   if ($actual<$total){
		   		$keys.=",";
				$values.=",";
		   }
		}
		$sql="INSERT INTO $this->table ($keys) VALUES ($values)";
		$oConn->execute($sql);
		$this->pk_value = $oConn->insertID();
		$oConn->close();
	}

	protected function save_update() {
		$oConn = self::connect();

		$actual=0;
		$action="UPDATE $this->table set";
		$condition=" where ".$this->pk."=".$this->pk_value;
		$allocation="";
		$total=count($this->attribs);
		foreach ($this->attribs as $key=>$value)
		{
		   if($value!=""){
		      $allocation.=" $key = '".$value."'"; 
		   }
		   $actual++;
		   if ($actual<$total&&$value!=""){
		   		$allocation.=",";
		   }
		}
		$allocation=trim($allocation,",");
		$sql=$action.$allocation.$condition;
		//echo $sql;
		$oConn->execute($sql);

		$oConn->close();
	}

	public function save()	{
		if ($this->pk_value == 0) {
			self::save_insert();
		} else {
			self::save_update();
		}
	}

	public function delete() {
		$oConn = self::connect();

		$sql="DELETE FROM $this->table WHERE $this->pk=$this->pk_value";
		$oConn->execute($sql);

		$oConn->close();
	}

	public function getData() {
		return ($this->attribs);
	}
	
	public function getId() {
		return($this->pk_value);
	}
	
	public function clon() {
	/*	resetea la PK para hacer un nuevo save() en un nuevo elemento	*/
		$return = false;
		if ( $this->pk_value != 0 ) {
			$this->pk_value = 0;
			$return = true;
		}
	}
	
	public function compare($array) {
	/*	Compara los valores de la tabla con los valores de un array
		devuelve cierto si no hay diferencias
		devuelve falso si las hay y ademas almacena las diferencias en una var 	*/
		
		$return = false;
		
		if ( ($this->pk_value!=0) && (self::compareArray($array)) ) {
			$diff = array_diff_assoc($array,$this->attribs);
			if ( empty($diff)  ) $return = true;
			else $this->diff = $diff;
		}
		
		return($return);
	}
	
	public function getDiff() {
	/*	devuelve el array de diferencias (o array vacio)	*/
		return($this->diff);
	}
	
	public function import($array) {
	/*	importa los datos de un array relacional a campos	*/
		if ( is_array($array) ) {
			foreach($array as $key=>$value) {
				self::__set($key,$value);
			}
		}
	}
	
	protected function compareArray($array) {
	/*	comprueba que $array contiene solo valores de campos validos
		de la tabla	(lo hace suponiendo que las key son campos)	*/
		$return = false;
		
		$diff = array_diff_key($array,$this->attribs);
		if ( empty($diff) ) {
			$return = true;
		}
		return($return);
	}
	
	protected function importFields() {
	/*	importa los campos de la tabla para evitar que se introduzcan
		campos inexistentes (se comprueba en __set)	*/
		$oConn = self::connect();
		
		$sqlSelect="SHOW COLUMNS FROM $this->table";
		$result=$oConn->getAll($sqlSelect);

		if (!$result){
			$this->error = 2;
		} else {
			foreach ($result as $key=>$value) {
				$this->fields[$value["Field"]] = "";
			}
		}
		$oConn->close();	
	}
	
	public function __construct() {

		$args = func_get_args();
		$num_args = func_num_args();
		$this->error = 0;
		
		if ($num_args == 2) {
			$this->table = $args[0];
			$this->pk = $args[1];
			$this->pk_value=0;	/*	added by mate	*/
		}
		
		if ($num_args == 3) {
			$this->table = $args[0];
			$this->pk = $args[1];
			$this->pk_value = $args[2];
			$this->load();
		}
		
		if ( $num_args >= 1 ) {
			self::importFields();
		}
	}

	public function __destruct() {
		unset ($error);
		unset ($table);
		unset ($pk);
		unset ($pk_value);
		unset ($attribs);
	}
	
	public function __set($key,$value)	{
		if ( array_key_exists($key,$this->fields) ) {	/*	added by mate	*/
			$this->attribs[$key] = $value;
		}
	}

	public function __get($key) {
		if ($this->pk_value == 0) {
			return (NULL);
		} else {
			return stripslashes($this->attribs[$key]);
		}
	}

	public function getFields() {
		return $this->fields;
	}
}
?>