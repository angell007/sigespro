<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
//include_once('../../class/class.consulta.php');
include_once('../../class/class.consulta_paginada.php');
// Cache disabled for this endpoint due to performance/staleness concerns.

$condiciones1 = [];
$condiciones2 = [];


if (isset($_REQUEST['cod_fact']) && $_REQUEST['cod_fact'] != "") {
    array_push($condiciones1, "F.Codigo LIKE '%$_REQUEST[cod_fact]%'");
    array_push($condiciones2, "FC.Codigo LIKE '%$_REQUEST[cod_fact]%'");
}
if (isset($_REQUEST['estado_fact']) && $_REQUEST['estado_fact'] &&  $_REQUEST['estado_fact']  != 'Nota_Credito') {
    array_push($condiciones1, "F.Estado_Factura='$_REQUEST[estado_fact]'");
    array_push($condiciones2, "FC.Estado_Factura='$_REQUEST[estado_fact]'");
}

if (isset($_REQUEST['estado_fact']) && $_REQUEST['estado_fact'] &&  $_REQUEST['estado_fact']  == 'Nota_Credito') {

    array_push($condiciones1, "F.Nota_Credito = 'Si'");
    array_push($condiciones2, "FC.Nota_Credito = 'Si'");
}

if (isset($_REQUEST['facturador']) && $_REQUEST['facturador']) {
    array_push($condiciones1, "(FU.Nombres LIKE '%$_REQUEST[facturador]%' OR FU.Apellidos LIKE '%$_REQUEST[facturador]%')");
    array_push($condiciones2, "(FU.Nombres LIKE '%$_REQUEST[facturador]%' OR FU.Apellidos LIKE '%$_REQUEST[facturador]%')");
}
if (isset($_REQUEST['cliente']) && $_REQUEST['cliente']) {
    array_push($condiciones1, "C.Nombre LIKE '%$_REQUEST[cliente]%'");
    array_push($condiciones2, "C.Nombre LIKE '%$_REQUEST[cliente]%'");
}
if (isset($_REQUEST['fecha_fact']) && $_REQUEST['fecha_fact'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha_fact'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha_fact'])[1]);

    array_push($condiciones1, "F.Fecha_Documento BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59'");
    array_push($condiciones2, "FC.Fecha_Documento BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59'");
}
if (isset($_REQUEST['tipo']) && $_REQUEST['tipo']) {
    array_push($condiciones1,"D.Id_Tipo_Servicio = $_REQUEST[tipo]");
    
    $tipo = $_REQUEST['tipo'];
    if ($tipo == '7') {
        array_push($condiciones2,"'Capita' LIKE '%Capita%'");
    } else {
        array_push($condiciones2,"'Capita' LIKE '%$tipo%'");
    }
}

#$condicion .= ' AND F.Nota_Credito IS NOT NULL';
#$condicion2 .= ' AND FC.Nota_Credito IS NOT NULL';

$condicion  = "WHERE ". (count($condiciones1)>0? implode(" AND ", $condiciones1):"1");
$condicion2  = "WHERE ". (count($condiciones2)>0? implode(" AND ", $condiciones2):"1");


$query2 = '(SELECT CONCAT_WS(" ",FU.Nombres,FU.Apellidos) as Funcionario, C.Nombre as Cliente , F.Id_Factura as Id_Factura, F.Id_Factura_Asociada, F.Fecha_Documento , F.Estado_Factura , F.Codigo, F.Tipo AS Modalidad, (SELECT CONCAT(S.Nombre,"-",T.Nombre) FROM Tipo_Servicio T INNER JOIN Servicio S ON T.Id_Servicio=S.Id_Servicio WHERE T.Id_Tipo_Servicio=D.Id_Tipo_Servicio ) as Tipo, F.Tipo AS Tipo_Fact 
FROM Factura F 
INNER JOIN Cliente C ON C.Id_Cliente = F.Id_Cliente 
INNER JOIN Funcionario FU ON F.Id_Funcionario = FU.Identificacion_Funcionario 
INNER JOIN Dispensacion D ON F.Id_Dispensacion=D.3Id_Dispensacion '.$condicion.') 

UNION (SELECT CONCAT_WS(" ",FU.Nombres,FU.Apellidos) as Funcionario, C.Nombre as Cliente, FC.Id_Factura_Capita as Id_Factura, "" AS Id_Factura_Asociada, FC.Fecha_Documento, FC.Estado_Factura, FC.Codigo, "Factura" AS Modalidad, "Capita" AS Tipo, "Capita" AS Tipo_Fact 
FROM Factura_Capita FC 
INNER JOIN Cliente C ON C.Id_Cliente = FC.Id_Cliente 
INNER JOIN Funcionario FU ON FC.Identificacion_Funcionario = FU.Identificacion_Funcionario '.$condicion2.') ORDER BY Fecha_Documento DESC' ;

/*
$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$total = $oCon->getData();
unset($oCon);
*/
####### PAGINACIÓN ######## 
$tamPag = 20; 
/*

$numReg = count($total); 
$paginas = ceil($numReg/$tamPag); 
$limit = ""; 
$paginaAct = "";
*/
if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') { 
    $paginaAct = 1; 
    $limit = 0; 
} else { 
    $paginaAct = $_REQUEST['pag']; 
    $limit = ($paginaAct-1) * $tamPag; 
} 


/*
$query2 = '  (
 SELECT CONCAT_WS(" ",FU.Nombres,FU.Apellidos) as Funcionario, C.Nombre as Cliente , F.Id_Factura as Id_Factura, F.Id_Factura_Asociada, F.Fecha_Documento , F.Estado_Factura , F.Codigo, F.Tipo AS Modalidad,	 (SELECT CONCAT(S.Nombre,"-",T.Nombre) FROM Tipo_Servicio T INNER JOIN Servicio S ON T.Id_Servicio=S.Id_Servicio WHERE T.Id_Tipo_Servicio=D.Id_Tipo_Servicio ) as Tipo, F.Tipo AS Tipo_Fact, F.Nota_Credito
	FROM Factura F 
	INNER JOIN Cliente C ON C.Id_Cliente = F.Id_Cliente 
	INNER JOIN Funcionario FU ON F.Id_Funcionario = FU.Identificacion_Funcionario 
	INNER JOIN Dispensacion D ON F.Id_Dispensacion=D.Id_Dispensacion '.$condicion.')
	

 UNION ( SELECT   CONCAT_WS(" ",FU.Nombres,FU.Apellidos) as Funcionario, C.Nombre as Cliente, FC.Id_Factura_Capita as Id_Factura, "" AS Id_Factura_Asociada, FC.Fecha_Documento, FC.Estado_Factura, FC.Codigo, 		FC.Nota_Credito,  "Factura" AS Modalidad, "Pos-Capita" AS Tipo, "Capita" AS Tipo_Fact 
  	FROM Factura_Capita FC 
  	INNER JOIN Cliente C ON C.Id_Cliente = FC.Id_Cliente 
  	INNER JOIN Funcionario FU ON FC.Identificacion_Funcionario = FU.Identificacion_Funcionario '.$condicion2.'
  )   ORDER BY Fecha_Documento DESC LIMIT '.$limit.','.$tamPag ;

$sqlTotal = "SELECT FOUND_ROWS() as total";




$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$resultado["Facturas"] = $oCon->getData();
*/

 
// Cache disabled for this endpoint.

// Si no está en cache, ejecutar la query
$query2 = ' SELECT  SQL_CALC_FOUND_ROWS  * FROM ( 
    (SELECT 
        CONCAT_WS(" ", FU.Nombres, FU.Apellidos) as Funcionario, 
        C.Nombre as Cliente, 
        F.Id_Factura as Id_Factura, 
        F.Id_Factura_Asociada, 
        F.Fecha_Documento, 
        F.Estado_Factura, 
        F.Codigo, 
        F.Tipo AS Modalidad, 
        (SELECT CONCAT(S.Nombre, "-", T.Nombre) 
         FROM Tipo_Servicio T 
         INNER JOIN Servicio S 
         ON T.Id_Servicio = S.Id_Servicio 
         WHERE T.Id_Tipo_Servicio = D.Id_Tipo_Servicio) as Tipo, 
        F.Tipo AS Tipo_Fact, 
        F.Nota_Credito, 
        F.Procesada,
        F.Id_Resolucion 
    FROM Factura F 
    INNER JOIN Cliente C ON C.Id_Cliente = F.Id_Cliente 
    INNER JOIN Funcionario FU ON F.Id_Funcionario = FU.Identificacion_Funcionario 
    INNER JOIN Dispensacion D ON F.Id_Dispensacion = D.Id_Dispensacion '.$condicion.') 
UNION 
    (SELECT 
        CONCAT_WS(" ", FU.Nombres, FU.Apellidos) as Funcionario, 
        C.Nombre as Cliente, 
        FC.Id_Factura_Capita as Id_Factura, 
        "" AS Id_Factura_Asociada, 
        FC.Fecha_Documento, 
        FC.Estado_Factura, 
        FC.Codigo, 
        "Factura" AS Modalidad, 
        "Pos-Capita" AS Tipo, 
        "Capita" AS Tipo_Fact, 
        FC.Nota_Credito, 
        FC.Procesada,
        FC.Id_Resolucion 
    FROM Factura_Capita FC 
    INNER JOIN Cliente C ON C.Id_Cliente = FC.Id_Cliente 
    INNER JOIN Funcionario FU ON FC.Identificacion_Funcionario = FU.Identificacion_Funcionario '.$condicion2.') 
) F 
ORDER BY Fecha_Documento DESC 
LIMIT '.$limit.','.$tamPag;

$oCon = new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$res = $oCon->getData();
unset($oCon);

$resultado["Facturas"] = $res['data'];
$resultado['numReg'] = $res['total'];

echo json_encode($resultado);

