<?php
error_reporting(E_ERROR |  E_PARSE);
class dao {

	var $conn;
	var $sgbd;
	var $dbhost;
	var $dbuser;
	var $dbpassword;
	var $dbname;
	var $fetchMode = "FETCH_ASSOC"; 
	var $error = 0;
	var $debug = false;
	
	
	function selectDB()
	{
		
		if($this->conn){
			$this->doDebug();
			//mysql_select_db($this->dbname, $this->conn);
			mysqli_select_db($this->conn, $this->dbname) or die( http_response_code(400)?'No se pudo seleccionar la base de datos' :'');
		}
       
        
	}	
	
	function doDebug($query="")
	{
		if ($this->debug){ 
			echo @mysql_errno() . " : " . @mysql_error();
			echo "\n";
			echo $query;
			echo "\n";
		}
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
		// mysqli_connect($db_host,$db_user,$db_password, $db_name )
		if ($this->conn = mysqli_connect($this->dbhost, $this->dbuser, $this->dbpassword,	$this->dbname)){
			$this->selectDB();
		}
      
      	 
      
		$this->doDebug();
	}	
	
	function close()
	{
        if($this->conn){
            mysqli_close($this->conn);
			$this->doDebug();
		}
    }
	
	function execute($query)
	{

       
      //$result = mysqli_query($link,$this->query) or die('Consulta fallida: ' . mysqli_error($link));
      
		$result = mysqli_query($this->conn,$query );
		$this->doDebug($query);
		if($result) {
			$return = $result;
			@mysqli_free_result($result);
			return $return;
		    
		}else{
			die(http_response_code(400)?'Consulta no válida: ' . mysqli_error($this->conn) ."\n $query":'');
		}
    }	
	
	function getAll($query)
	{
       $result = mysqli_query($this->conn,$query );

		$this->doDebug($query);
		if($result) {
			$return = Array();
			if ($this->fetchMode=="FETCH_ASSOC"){
				while ($row = mysqli_fetch_assoc($result)){
					$return[] = $row;
				}
			}
			if ($this->fetchMode=="FETCH_FIELD"){
				while ($row = mysqli_fetch_field($result)){
					$return[] = $row;
				}
			}
			if ($this->fetchMode=="FETCH_OBJECT"){
				while ($row = mysqli_fetch_object($result)){
					$return[] = $row;
				}
			}
			if ($this->fetchMode=="FETCH_ROW"){
				while ($row = mysqli_fetch_row($result)){
					$return[] = $row;
				}
			}
			@mysqli_free_result($result);
			return $return;
		}
    }	
	
	function selectLimit($query,$items,$init)
	{
     
        $query = $query . " limit " . $init . "," .$items;
		$result = mysqli_query($this->conn,$query);
		$this->doDebug($query);
		if($result) {
			$return = Array();
			if ($this->fetchMode=="FETCH_ASSOC"){
				while ($row = mysqli_fetch_assoc($result)){
					$return[] = $row;
				}
			}
			if ($this->fetchMode=="FETCH_FIELD"){
				while ($row = mysqli_fetch_field($result)){
					$return[] = $row;
				}
			}
			if ($this->fetchMode=="FETCH_OBJECT"){
				while ($row = mysqli_fetch_object($result)){
					$return[] = $row;
				}
			}
			if ($this->fetchMode=="FETCH_ROW"){
				while ($row = mysqli_fetch_row($result)){
					$return[] = $row;
				}
			}
			@mysqli_free_result($result);
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
      // $result = mysqli_query($this->conn,$query );
		$result = mysqli_query($this->conn,$query);
		$this->doDebug($query);
		if ($result){ 
			$return = mysqli_num_rows($result);
			@mysqli_free_result($result);
			return $return;
		}
	}	
	
   	function insertID()
	{
		return mysqli_insert_id($this->conn);
	}
	
}

?>