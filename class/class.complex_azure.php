<?php
include_once("class.item_azure.php");

class complex extends item {

	public function getVersion($table,$fk,$q='',$q_value='') {
		$oConn = self::connect();
		
		$sqlSelect="SELECT * FROM $table WHERE $fk='".$this->pk_value."'";
		if ($q_value!=''){
			$sqlSelect.=" AND $q='".$q_value."'";
		}
		$result=$oConn->getAll($sqlSelect);
		
		$return = Array();
		if ($result){
			foreach ($result as $key=>$row){
				$qualifier = $row[$q];
				foreach ($row as $key2=>$value2){
					$row[$key2] = stripslashes($value2);
				}

				$return[$qualifier]=$row;
			}
		}
		$oConn->close();
		
		return $return;
	}
	
	public function setVersion($table,$fk,$q,$q_value,$data) {
		$result = $this->getVersion($table,$fk,$q,$q_value);
		if (empty($result)){
			$actual = 0;
			$keys="";
			$values="";
			$total=count($data);
			foreach ($data as $key=>$value)
			{
			   $keys.="$key";
			   $values.="'".addslashes($value)."'";
			   $actual++;
			   if ($actual<$total){
			   		$keys.=",";
					$values.=",";
			   }
			}
			$sql="INSERT INTO $table ($keys) values($values)";
		}
		else{
			$actual=0;
			$action="UPDATE $table SET";
			$condition=" WHERE $fk='".$this->pk_value . "' and $q='".$q_value."'";
			$allocation="";
			$total=count($data);
			foreach ($data as $key=>$value)
			{
			   $allocation.=" $key = '".addslashes($value)."'";
			   $actual++;
			   if ($actual<$total){
			   		$allocation.=",";
			   }
			}
			$sql=$action.$allocation.$condition;
		}
		$oConn = $this->connect();
		$oConn->execute($sql);
		$oConn->close();
	}
	
	public function deleteVersion($table,$fk,$q='',$q_value='')	{
		$oConn = $this->connect();

		$sql="DELETE FROM $table WHERE $fk='".$this->pk_value . "'";
		if ($q_value!=''){
			$sql.=" and $q='".$q_value."'";
		}
		
		$oConn->execute($sql);

		$oConn->close();
	}
	
	public function getDependent($table,$fk) {
		$oConn = $this->connect();
		
		$sqlSelect="SELECT * FROM $table WHERE $fk='".$this->pk_value."'";
		
		$result=$oConn->getAll($sqlSelect);
		
		$return = Array();
		if ($result){
			
			foreach ($result as $index=>$row){
				foreach ($row as $key=>$value){
					$result[$index][$key] = stripslashes($value);
				}
			}
			$return = $result;
		}
		$oConn->close();
		
		return $return;
	}
	
	public function setDependent($table,$fk,$data,$id='',$id_value='')	{
		if ( empty($id) ){
			$actual = 0;
			$keys="";
			$values="";
			$total=count($data);
			
			foreach ($data as $key=>$value){
				$keys.="$key";
				$values.="'".addslashes($value)."'";
				$actual++;
				if ($actual<$total){
				    $keys.=",";
					$values.=",";
				}
			}
			$sql="INSERT INTO $table ($keys) VALUES ($values)";
		}
		else {
			$actual=0;
			$action="update $table set";
			$condition=" where $id='".$id_value . "'";
			$allocation="";
			$total=count($data);
			foreach ($data as $key=>$value)
			{
			   $allocation.=" $key = '".addslashes($value)."'";
			   $actual++;
			   if ($actual<$total){
			   		$allocation.=",";
			   }
			}
			$sql=$action.$allocation.$condition;
		}
		$oConn = $this->connect();
		$oConn->execute($sql);
		$oConn->close();
	}
	
	public function deleteDependent($table,$fk,$id='',$id_value='') {
		$oConn = $this->connect();

		$sql="DELETE FROM $table WHERE $fk='".$this->pk_value . "'";
		if ($id_value!=''){
			$sql.=" and $id='".$id_value."'";
		}
		
		$oConn->execute($sql);

		$oConn->close();
	}
	
	public function __construct() {
		$args = func_get_args();
		
		$parentClass = get_parent_class($this);
		call_user_func_array(array($parentClass,"__construct"),$args);

	}

	public function __destruct() {
		parent::__destruct();
	}
	
}

?>