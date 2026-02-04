<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');


include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion= '';
if (isset($_REQUEST['cod_fact'])) {
    $condicion .= ' WHERE Codigo like "%'.$_REQUEST['cod_fact'].'%"';
}
if (isset($_REQUEST['fecha_fact']) && $_REQUEST['fecha_fact'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha_fact'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha_fact'])[1]);
    if ($condicion) {
        $condicion .= "AND Fecha_Documento BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }else{
        $condicion .= "WHERE  DATE(Fecha_Documento) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
    
} 

if (isset($_REQUEST['cliente'])) {

    if ($condicion) {
        $condicion .= 'AND Cliente LIKE "%'.$_REQUEST['cliente'].'%" ';
    }else{
        $condicion .='WHERE Cliente LIKE "%'.$_REQUEST['cliente'].'%" ';
    }
  
}



if (isset($_REQUEST['facturador'])) {

    if ($condicion) {
        $condicion .= 'AND Funcionario LIKE "%'.$_REQUEST['facturador'].'%" ';
    }else{
        $condicion .=' WHERE Funcionario LIKE "%'.$_REQUEST['facturador'].'%" ';
    }
  
}

if (isset($_REQUEST['estado_fact'])) {

    if ($condicion) {
        $condicion .= 'AND Estado_Factura LIKE "%'.$_REQUEST['estado_fact'].'%" ';
    }else{
        $condicion .=' WHERE Estado_Factura LIKE "%'.$_REQUEST['estado_fact'].'%" ';
    }
  
}



$query = 'SELECT COUNT(*) AS Total FROM(
    SELECT  FA.* , IFNULL(CONCAT(F.Primer_Nombre," ",F.Primer_Apellido),F.Nombres) AS Cliente,
  IFNULL(CONCAT(F.Primer_Nombre," ",F.Primer_Apellido),F.Nombres) AS Funcionario
    FROM Factura_Administrativa FA
    INNER JOIN Funcionario F ON F.Identificacion_Funcionario = FA.Identificacion_Funcionario
    WHERE Tipo_Cliente = "Funcionario" 
    
    UNION ALL

    SELECT  FA.* , C.Nombre AS Cliente,
    IFNULL(CONCAT(F.Primer_Nombre," ",F.Primer_Apellido),F.Nombres) AS Funcionario
    FROM Factura_Administrativa FA
    INNER JOIN Cliente C ON C.Id_Cliente = FA.Id_Cliente
    INNER JOIN Funcionario F ON F.Identificacion_Funcionario = FA.Identificacion_Funcionario
    WHERE Tipo_Cliente = "Cliente" 
    
    UNION ALL
    
    SELECT  FA.* , P.Nombre AS Cliente,
    IFNULL(CONCAT(F.Primer_Nombre," ",F.Primer_Apellido),F.Nombres) AS Funcionario
    FROM Factura_Administrativa FA
    INNER JOIN Proveedor P ON P.Id_Proveedor = FA.Id_Cliente
    INNER JOIN Funcionario F ON F.Identificacion_Funcionario = FA.Identificacion_Funcionario
    WHERE Tipo_Cliente = "Proveedor" 
    )  AS Facturas
     
     '.$condicion;

$oCon = new consulta();
$oCon->setQuery($query);
$numReg = $oCon->getData();
unset($oCon);
$currentPage='';
$numReg = $numReg['Total'];
$perPage = 15;
$from = "";
$to = "";



if (isset($_REQUEST['pag'])) {
    $currentPage = $_REQUEST['pag'];
    $from= ($currentPage - 1) * $perPage;
}else{
    $currentPage = 1;
    $from=0;
}



$query = 'SELECT * FROM(
    SELECT  FA.* , IFNULL(CONCAT(C.Primer_Nombre," ",C.Primer_Apellido),C.Nombres) AS Cliente,
  IFNULL(CONCAT(F.Primer_Nombre," ",F.Primer_Apellido),F.Nombres) AS Funcionario
    FROM Factura_Administrativa FA
    INNER JOIN Funcionario F ON F.Identificacion_Funcionario = FA.Identificacion_Funcionario
    INNER JOIN Funcionario C ON C.Identificacion_Funcionario = FA.Id_Cliente
    WHERE Tipo_Cliente = "Funcionario" 
    
    UNION ALL

    SELECT  FA.* , C.Nombre AS Cliente,
    IFNULL(CONCAT(F.Primer_Nombre," ",F.Primer_Apellido),F.Nombres) AS Funcionario
    FROM Factura_Administrativa FA
    INNER JOIN Cliente C ON C.Id_Cliente = FA.Id_Cliente
    INNER JOIN Funcionario F ON F.Identificacion_Funcionario = FA.Identificacion_Funcionario
    WHERE Tipo_Cliente = "Cliente" 
    
    UNION ALL
    
    SELECT  FA.* , P.Nombre AS Cliente,
    IFNULL(CONCAT(F.Primer_Nombre," ",F.Primer_Apellido),F.Nombres) AS Funcionario
    FROM Factura_Administrativa FA
    INNER JOIN Proveedor P ON P.Id_Proveedor = FA.Id_Cliente
    INNER JOIN Funcionario F ON F.Identificacion_Funcionario = FA.Identificacion_Funcionario
    WHERE Tipo_Cliente = "Proveedor" 
    ) AS Facturas
 '.$condicion.'  ORDER BY Facturas.Id_Factura_Administrativa  DESC
 LIMIT '.$from.' , '.$perPage.'

            
';
 

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$facturas = $oCon->getData();
unset($oCon);


$response['Facturas'] = $facturas;
$response['numReg'] = $numReg;


echo json_encode($response);