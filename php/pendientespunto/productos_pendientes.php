<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$idpunto = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$ident = ( isset( $_REQUEST['ident'] ) ? $_REQUEST['ident'] : '' );


$condicion ='';

if (isset($_REQUEST['id']) && $_REQUEST['id'] != "") {
    $condicion .= " AND D.Id_Punto_Dispensacion=$_REQUEST[id] ";
}
if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != "") {
    $condicion .= " AND (P.Principio_Activo LIKE '%$_REQUEST[nom]%' OR P.Presentacion LIKE '%$_REQUEST[nom]%' OR P.Concentracion LIKE '%$_REQUEST[nom]%' OR P.Nombre_Comercial LIKE '%$_REQUEST[nom]%')";
}

if(isset($_REQUEST['ident']) && $_REQUEST['ident'] != ""){
    $str = strtoupper($_REQUEST['ident']);
    // $condicion .= "AND PD.AFnumeroDocumento LIKE '%".$str."%' ";
    $condicion .= "AND D.Numero_Documento LIKE '%".$str."%'  OR D.Paciente LIKE '%".$str."%' ";
}
if(isset($_REQUEST['dis']) && $_REQUEST['dis'] != ""){
    $str = strtoupper($_REQUEST['dis']);
    // $condicion .= "AND PD.AFnumeroDocumento LIKE '%".$str."%' ";
    $condicion .= "AND D.Codigo LIKE '%".$str."%' ";
}

$condiciones_inicial = 'FROM Producto_Dispensacion PD
INNER JOIN Dispensacion D ON D.Id_Dispensacion=PD.Id_Dispensacion
INNER JOIN Punto_Dispensacion PDI on PDI.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
INNER JOIN Departamento DEP ON DEP.Id_Departamento = PDI.Departamento
INNER JOIN Municipio MUN ON MUN.Id_Municipio = PDI.Municipio
INNER JOIN Auditoria A on (A.Id_Dispensacion = D.Id_Dispensacion AND  A.Estado like "Aceptar")
INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto
WHERE DATE(D.Fecha_Actual) > "2021-08-01" and  D.Estado_Dispensacion != "Anulada" and PDI.Estado = "Activo" and PD.Id_Producto IS NOT NULL';
$query =
    "SELECT  COUNT(PD.Id_Producto_Dispensacion) as Total
$condiciones_inicial
AND ( ( PD.Cantidad_Formulada - Cantidad_Entregada) != 0 )
" . $condicion;

//Se coloca la fa fecha 2021-08-01 porque en esta fecha comienza contrato positiva

$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 30; 
$numReg = $total['Total']; 
$paginas = ceil($numReg/$tamPag); 
$limit = ""; 
$paginaAct = "";

if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') { 
    $paginaAct = 1; 
    $limit = 0; 
} else { 
    $paginaAct = $_REQUEST['pag']; 
    $limit = ($paginaAct-1) * $tamPag; 
}
/*
$query = 'SELECT  IFNULL(CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida),CONCAT(P.Nombre_Comercial," ",P.Laboratorio_Comercial)) as Nombre_Producto, PR.Cantidad, P.Nombre_Comercial, P.Laboratorio_Comercial, (SELECT PD.Nombre FROM Punto_Dispensacion PD WHERE PD.Id_Punto_Dispensacion=PR.Id_Punto_Dispensacion) as Punto
FROM Producto_Pendientes_Remision PR
INNER JOIN Producto P ON PR.Id_Producto=P.Id_Producto
WHERE PR.Id_Producto IS NOT NULL
'.$condicion.' ORDER BY Nombre_Producto, PR.Id_Producto LIMIT '.$limit.','.$tamPag;
*/

$query = 'SELECT IFNULL(CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida),CONCAT(P.Nombre_Comercial," ",P.Laboratorio_Comercial)) as Nombre_Producto, 
(PD.Cantidad_Formulada - Cantidad_Entregada) as Cantidad, D.Observaciones,
P.Nombre_Comercial, P.Laboratorio_Comercial, D.Paciente,D.Fecha_Formula as Fecha, D.Numero_Documento,DEP.Nombre as Departamento, MUN.Nombre as Municipio, D.Id_Dispensacion,
D.Codigo as Punto, 
A.Estado as Auditoria
' . $condiciones_inicial . '
AND ( ( PD.Cantidad_Formulada - Cantidad_Entregada) != 0 )
'.$condicion.' ORDER BY Nombre_Producto, PD.Id_Producto LIMIT '.$limit.','.$tamPag;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos = $oCon->getData();
unset($oCon);

$resultado["Productos"]=$productos;
$resultado["numReg"]=$numReg;
echo json_encode($resultado);

// ID_PRODUCTO ID_PUNTO
?>