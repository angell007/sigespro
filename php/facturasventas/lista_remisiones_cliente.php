<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$nombreCliente = ( isset($_REQUEST['Cliente']) ? $_REQUEST['Cliente'] : '' );

// QUERY con LIKE en vez de igualdad estricta
$query = '
    SELECT 
        R.Id_Remision AS idRemision, 
        R.Codigo AS Codigo, 
        R.Estado, 
        R.Fecha AS Fecha, 
        CONCAT(F.Nombres, " ", F.Apellidos) AS Nombre
    FROM Remision R
    INNER JOIN Cliente C ON C.Id_Cliente = R.Id_Destino 
    INNER JOIN Funcionario F ON F.Identificacion_Funcionario = R.Identificacion_Funcionario
    WHERE 
        C.Nombre LIKE "%'.$nombreCliente.'%"
        AND R.Tipo = "Cliente" 
        AND R.Estado = "Enviada"

    UNION ALL

    SELECT
        R.Id_Remision AS idRemision,
        R.Codigo AS Codigo,
        R.Estado,
        R.Fecha AS Fecha,
        CONCAT(F.Nombres, " ", F.Apellidos) AS Nombre
    FROM Remision R
    INNER JOIN Contrato CO ON R.Id_Contrato = CO.Id_Contrato
    INNER JOIN Cliente C ON C.Id_Cliente = CO.Id_Cliente
    INNER JOIN Funcionario F ON F.Identificacion_Funcionario = R.Identificacion_Funcionario
    WHERE
        C.Nombre LIKE "%'.$nombreCliente.'%"
        AND R.Tipo = "Contrato" 
        AND R.Estado = "Enviada"
';

//echo $query; // para verificar la query exacta

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);

?>
