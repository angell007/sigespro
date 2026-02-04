<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$query = 'SELECT * 
                FROM Contrato 
                WHERE Estado = "Activo" AND tipo_contrato = "General" AND Fecha_Fin < CURDATE()';         

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Contratos'] = $oCon->getData();
unset($oCon);

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

        $query = 'SELECT * 
                  FROM Contrato WHERE Id_Contrato = "'.$id.'"';         
        $oCon= new consulta();
        $oCon->setTipo('Simple');
        $oCon->setQuery($query);
        $resultado['Contrato'] = $oCon->getData();

    
echo json_encode($resultado);



