<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$idproducto  = isset($_REQUEST['Id_Producto']) ? $_REQUEST['Id_Producto'] : false;
$idcontrato  = isset($_REQUEST['Id_Contrato']) ? $_REQUEST['Id_Contrato'] : false;

$query = 'SELECT A.*, CONCAT(F.Nombres," ",F.Apellidos) as Nombre_Funcionario,CONCAT(P.Nombre_Comercial," ",P.Laboratorio_Comercial) as Nombre_Producto
            FROM Actividad_Producto_Contrato A
            INNER JOIN Producto P ON A.Id_Producto = P.Id_Producto
            INNER JOIN Funcionario F ON F.Identificacion_Funcionario = A.Identificacion_Funcionario
            WHERE A.Id_Producto = '.$idproducto.'  AND A.Id_Contrato = '.$idcontrato.' ORDER BY A.Fecha DESC';
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$actividades= $oCon->getData();

echo json_encode($actividades);


$query = 'SELECT A.*, F.Nombre as Nombre_Funcionario, CONCAT(P.Nombre_Comercial," ",P.Laboratorio_Comercial) as Nombre_Producto
            FROM Actividad_Producto_Contrato A
            INNER JOIN Producto P ON A.Id_Producto = P.Id_Producto
            INNER JOIN Funcionario F ON F.Identificacion_Funcionario = A.Identificacion_Funcionario
            WHERE A.Id_Producto = '.$idproducto.'  AND A.Id_Contrato = '.$idcontrato.' ORDER BY A.Fecha DESC';
