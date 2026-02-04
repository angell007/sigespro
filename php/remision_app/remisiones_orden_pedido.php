<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');

$query = 'SELECT 
            R.Codigo AS Remision, R.Fecha, R.Id_Remision, OP.Id_Orden_Pedido , R.Nombre_Origen,
            COALESCE(C.Razon_Social,C.Nombre) as Nombre_Cliente, CONCAT_WS(" ",AG.Nombres, AG.Apellidos) Nombre_Agente 
            FROM Remision R 
            INNER JOIN Orden_Pedido OP ON OP.Id_Orden_Pedido = R.Id_Orden_Pedido
            INNER JOIN Cliente C ON C.Id_Cliente = OP.Id_Cliente
            INNER JOIN Agentes_Cliente AG ON AG.Id_Agentes_Cliente =  OP.Id_Agentes_Cliente
            WHERE R.Estado = "Validacion"
            ';
;
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$res = $oCon->getData();

echo json_encode($res);