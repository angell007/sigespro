<?php
include_once("class.dao_azure.php");

class consulta {
	protected	$query      = 0,
                $tipo       = 'simple',
                $bandera    = "Consulta",
                $resultado  = [];
				

	public function connect() {
        global $MY_CONFIG;
        //echo $MY_CONFIG;
		include($MY_CONFIG . "config.db.php");
		
	    /* $link = mysql_connect($db_host,$db_user, $db_password) or die('No se pudo conectar: ' . mysql_error());
        mysql_select_db($db_name) or die('No se pudo seleccionar la base de datos');
        $result = mysql_query($this->query) or die('Consulta fallida: ' . mysql_error()); */


        try {
            
            $link = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_password,$db_options );
            $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $result = $link->prepare($this->query);
            $result->execute();
		}
		catch (PDOException $e) {
			print("Error connecting to MySQL .");
			die(print_r($e));
		}

        if($this->bandera=='Consulta'){
        if($this->tipo == 'simple'){
            //    $vari=mysql_fetch_assoc($result);
            $vari = $result->fetch();
                    $this->resultado=$vari;
               
                
            }else{
                /* while($lista=mysql_fetch_assoc($result)){
                    $lista = array_map('utf8_encode', $lista);
                    $this->resultado[]=$lista;
                }   */ 
                $lista = $result->fetchAll();
              
               // $lista = array_map('utf8_encode', $lista);
                $this->resultado=$lista;
            }
            // @mysql_free_result($this->resultado);
            $result->closeCursor();
        }else{
        $this->resultado='';
        }
     
       
        
        $link = null;
	}
	
	public function connect2() {
	    global $MY_CONFIG;
		include($MY_CONFIG . "config.db.php");
		
	    /* $link = mysql_connect($db_host,$db_user, $db_password) or die('No se pudo conectar: ' . mysql_error());
        mysql_select_db($db_name) or die('No se pudo seleccionar la base de datos');
        $result = mysql_query($this->query) or die('Consulta fallida: ' . mysql_error());
        
        mysql_close($link); */

        try {
			$link = new PDO("mysql:$db_host;dbname=$db_name", $db_user, $db_password );
            $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $result = $link->prepare($this->query);
            $result->execute();
            $result->closeCursor();

            $link = null;
		}
		catch (PDOException $e) {
			print("Error connecting to MySQL .");
			die(print_r($e));
		}
	}
	

    public function setQuery($query){
        $this->query = $query;
    }
    
    public function setTipo($arg){
         $this->tipo = $arg; 
    }

    public function getData(){
        self::connect();
        return $this->resultado;
    }
    
    public function deleteData(){
        self::connect2();
        return $this->resultado;
    }
    
    public function createData(){
    	$bandera="Insertar";
        self::connect2();
        return $this->resultado;
    }

	public function __construct() {
	}

	public function __destruct() {
		unset ($query);
		unset ($resultado);
	}
	
	

}
?>