<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$nombreCliente = ( isset( $_REQUEST['Cliente'] ) ? $_REQUEST['Cliente'] : '' );

$query = 'SELECT 
                R.Id_Remision as idRemision, R.Codigo as Codigo, R.Fecha as Fecha, CONCAT(F.Nombres," ", F.Apellidos) as Nombre
          FROM 
            Remision_Antigua R 
          INNER JOIN 
                    Cliente C 
            ON C.Id_Cliente = R.Id_Destino 
          INNER JOIN
                Funcionario F
          ON F.Identificacion_Funcionario = R.Identificacion_Funcionario
          WHERE 
                C.Nombre = "'.$nombreCliente.'"
          AND R.Tipo = "Cliente"'  ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);

?>