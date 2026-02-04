<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);
class dao {

	private $conn;
	private $sgbd;
	private $dbhost;
	private $dbuser;
	private $dbpassword;
	private $dbname;
	private $fetchMode = "FETCH_ASSOC"; 
	private $error = 0;
	private $debug = false;
	
	
	function selectDB()
	{
		
		if($this->conn){
			$this->doDebug();
			mysql_select_db($this->dbname, $this->conn);
		}
	}	
	
	function doDebug($query="")
	{
		/*if ($this->debug){ 
			echo @mysql_errno() . " : " . @mysql_error();
			echo "\n";
			echo $query;
			echo "\n";
		}*/
	}
	
	function __construct($sgbd)
	{
		$this->sgbd = $sgbd;
		//Aqui ira la seleccion del sgbd
	}
	
	function connect($dbhost,$dbuser,$dbpassword,$dbname)
	{	
		$this->dbhost = $dbhost;
		$this->dbuser = $dbuser;
		$this->dbpassword = $dbpassword;
		$this->dbname = $dbname;
		
		/*if ($this->conn = mysql_connect($this->dbhost, $this->dbuser, $this->dbpassword)){
			$this->selectDB();
		}
		$this->doDebug();*/


		 try {
			$this->conn = new PDO("mysql:host=".$this->dbhost.";dbname=".$this->dbname, $this->dbuser, $this->dbpassword );
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch (PDOException $e) {
			print("Error connecting to MySQL .");
			die(print_r($e));
		} 
	}	
	
	function close()
	{
        /* if($this->conn){
            mysql_close($this->conn);
			$this->doDebug();
		} */
		$this->conn=null;
    }
	
	function execute($query)
	{
		//$result = mysql_query($query, $this->conn);
		$result=$this->conn->prepare($query);
		$this->doDebug($query);
		
		try {
			$return = $result->execute();
			$result->closeCursor();
			return $return;
		} catch (PDOException $e) {
			die('Consulta no valida: '. $e->getMessage());
		}
    }	
	
	function getAll($query)
	{
		$result = $this->conn->prepare($query);
		$result->execute();

		$this->doDebug($query);
		if($result) {
			$return = Array();
			if ($this->fetchMode=="FETCH_ASSOC"){
				/* while ($row = mysql_fetch_assoc($result)){
					$return[] = $row;
				} */
				$return = $result->fetchAll();
			}
			if ($this->fetchMode=="FETCH_FIELD"){
				while ($row = mysql_fetch_field($result)){
					$return[] = $row;
				}
			}
			if ($this->fetchMode=="FETCH_OBJECT"){
				/* while ($row = mysql_fetch_object($result)){
					$return[] = $row;
				} */
				$return = $result->fetchObject();
			}
			if ($this->fetchMode=="FETCH_ROW"){
				/* while ($row = mysql_fetch_row($result)){
					$return[] = $row;
				} */
				$return = $result->fetch();
			}
			// @mysql_free_result($result);
			$result->closeCursor();
			return $return;
		}
    }	
	
	function selectLimit($query,$items,$init)
	{
        $query = $query . " limit " . $init . "," .$items;
		$result = mysql_query($query, $this->conn);
		$this->doDebug($query);
		if($result) {
			$return = Array();
			if ($this->fetchMode=="FETCH_ASSOC"){
				while ($row = mysql_fetch_assoc($result)){
					$return[] = $row;
				}
			}
			if ($this->fetchMode=="FETCH_FIELD"){
				while ($row = mysql_fetch_field($result)){
					$return[] = $row;
				}
			}
			if ($this->fetchMode=="FETCH_OBJECT"){
				while ($row = mysql_fetch_object($result)){
					$return[] = $row;
				}
			}
			if ($this->fetchMode=="FETCH_ROW"){
				while ($row = mysql_fetch_row($result)){
					$return[] = $row;
				}
			}
			@mysql_free_result($result);
			return $return;
		}
    }
	
	function getRow($query)
	{
        $return = $this->getAll($query);
		return $return;
    }
	
	function setFetchMode($fetchmode)
	{
		$this->fetchmode=$fetchmode;
	}	
	
	function numRows($query)
	{
		// $result = mysql_query($query, $this->conn);
		$result = $this->conn->prepare($query);
		$result->execute();
		$this->doDebug($query);
		if ($result){ 
			$return = count($result->fetchAll());
			// $return = mysql_num_rows($result);
			// @mysql_free_result($result);
			$result->closeCursor();
			return $return;
		}
	}	
	
   	function insertID()
	{
		return $this->conn->lastInsertId();
		// return mysql_insert_id();
	}
	
}

?>