<?php
include_once("class.dao.php");

class lista {
	private		$error 		= 0,
				$table,
				$attribs,
				$query		= "",
				$restrict 	= array(),
				$order 		= array(),
				$itemsxpage,
				$group		= array(),
				$counter 	= 0;
				

	
	public function makeQuery($sql_query="")	{
		if ($sql_query!=""){
			$this->query = $sql_query;
		}
		else {
			if ($this->query==""){
				$this->query = "select * from $this->table";
			
				if (!empty($this->restrict)) {
					foreach ($this->restrict as $indice=>$valor) {
						if ($indice==0) {
							$this->query .= " WHERE ";
						}
						else{
							$this->query .= " AND ";
						}
						$row = $valor["row"];
						$operator = $valor["operator"];
						$value = $valor["value"];
						if ($operator=="LIKE"||$operator=="NOT LIKE"){
							$this->query = $this->query." $row $operator '%$value%'";
						}
						else if ($operator=="IN"){
							$this->query = $this->query." $row $operator ($value)";
						}
						else if ($operator=="NOT IN"){
							$this->query = $this->query." $row $operator ($value)";
						}else  if ($operator=="LIKIE"){
							$this->query = $this->query." $row LIKE '$value'";
						}
						else {
							$this->query = $this->query." $row $operator '$value'";
						}
					}
				}
				
				if (!empty($this->order)) {
					foreach ($this->order as $indice=>$valor) {
						if ($indice==0) {
							$this->query .= " ORDER BY ";
						}
						$row = $valor["row"];
						$type = $valor["type"];
						$this->query.="$row $type";	
						if ($indice<count($this->order)-1){
							$this->query .= " , ";
						}
					}
				}
			}
		}
		//echo $this->query;
	}
	
	public function makeDelete()	{
		$this->query = "delete from $this->table";
		
		if (!empty($this->restrict)) {
			foreach ($this->restrict as $indice=>$valor) {
				if ($indice==0) {
					$this->query .= " WHERE ";
				}
				else{
					$this->query .= " AND ";
				}
				$row = $valor["row"];
				$operator = $valor["operator"];
				$value = $valor["value"];
				if ($operator=="LIKE"){
					$this->query = $this->query." $row $operator '%$value%'";
				}
				else if ($operator=="IN"){
					$this->query = $this->query." $row $operator ($value)";
				}
				else if ($operator=="NOT IN"){
							$this->query = $this->query." $row $operator ($value)";
				}
				else  if ($operator=="LIKIE"){
					$this->query = $this->query." $row LIKE '$value'";
				}
				else {
					$this->query = $this->query." $row $operator '$value'";
				}
			}
		}
		
	}
	
	public function count()	{
		if ($this->counter==0){
			$oConn = $this->connect();
			$this->makeQuery();
			$this->counter=$oConn->numRows($this->query);
			$oConn->close();
		}
	}
	
	
	public function load($page="") {
	
		$oConn = $this->connect();
		$this->makeQuery();
		if ($page!=""){
			$items=$this->itemsxpage;
			$init=($page-1)*$items;
			$result=$oConn->selectLimit($this->query,$items,$init);
		}
		else{
			$result=$oConn->getAll($this->query);
		}
		echo mysql_error();
		if (!$result){
			$this->error = 1;
		} else {
			$this->attribs = $result;
			foreach ($this->attribs as $index=>$row){
				foreach ($row as $key =>$value){
					$this->attribs[$index][$key] = stripslashes($value);
				}
			}
		}
		$oConn->close();
	}
	

	public function setRestrict($row,$operator,$value) {
		$restrict = Array();
		$restrict["row"] = $row;
		$restrict["operator"] = $operator;
		$restrict["value"] = $value;
		$this->restrict[] = $restrict;
	}
	
	public function setOrder($row,$type) {
		$order = Array();
		$order["row"] = $row;
		$order["type"] = $type;
		$this->order[] = $order;
	}
	
	public function setItems($value) {
		$this->itemsxpage=$value;
	}
	
	public function getList($page="") {
		$this->load($page);
		if (!empty($this->attribs)) {
			return ($this->attribs);
		} else {
			return (array());
		}
	}
	public function setGroup($row) {
		$group = Array();
		$group["row"] = $row;
		$this->group[] = $group;
	}
	
	public function getPages() {
		$this->count();
		return ceil($this->counter/$this->itemsxpage);
	}
	
	public function getCount() {
		$this->count();
		return $this->counter;
	}
	
	public function deleteList()
	{
		$oConn = $this->connect();
		$this->makeDelete();
		$result=$oConn->execute($this->query);
		$oConn->close();
	}
	
	public function __construct() {
		$args = func_get_args();
		$num_args = func_num_args();
		$this->error = 0;
		if ($num_args == 1) {
			$this->table = $args[0];
		}
	}
	
	
	public function __destruct() {
		unset ($error);
		unset ($table);
		unset ($attribs);
		unset ($query);
		unset ($restrict);
		unset ($order);
		unset ($itemsxpage);
		unset ($counter);
	}
	
	public function connect() {
		global $MY_CONFIG;
		include("/home/sigespro/public_html/config/config.db.php");
		
		$oConn = new dao('mysql');
		$oConn->connect($db_host,$db_user,$db_password,$db_name);
		$oConn->setFetchMode("FETCH_ASSOC");
		return ($oConn);
	}
}
?>