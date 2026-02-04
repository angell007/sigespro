 <?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');
    
    require_once('../../config/start.inc.php');
    include_once('../../class/class.complex.php');
    include_once('../../class/class.consulta.php');
    
    $Campo = (isset($_REQUEST['Campo']) ? $_REQUEST['Campo'] : false);

    if($Campo){
        $Select = $Campo;
    }else{
        $Select = '*';        
    }

    $query = " SELECT $Select FROM Configuracion C LIMIT 1 ";

	$oCon = new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$CamposConfiguracion = $oCon->getData();
	unset($oCon);

	echo json_encode($CamposConfiguracion);

 ?>