<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$query = 'SELECT C.Id_Cliente, C.Nombre
            FROM Cliente C
            INNER JOIN Contrato CO ON C.Id_Cliente = CO.Id_Cliente
            WHERE CO.Tipo_Contrato = "Eps" || CO.Tipo_Contrato = "Arl" && CO.Tipo_Contrato = "Activo"';         

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Clientes'] = $oCon->getData();
unset($oCon);

    
echo json_encode($resultado);



