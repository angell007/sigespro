<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$sin_inventario = $_REQUEST['sin_inventario'];
$condicion_sin_inventario = '';

if ($sin_inventario == "false") {
    $condicion_sin_inventario = " AND (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) > 0";
}else if($sin_inventario == "true"){
    $condicion_sin_inventario = "";
}else if($sin_inventario == ""){
    $condicion_sin_inventario = "";
}

$condicion = '';

if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != "") {
    $condicion .= " AND (PRD.Principio_Activo LIKE '%$_REQUEST[nom]%' OR PRD.Presentacion LIKE '%$_REQUEST[nom]%' OR PRD.Concentracion LIKE '%$_REQUEST[nom]%' OR PRD.Nombre_Comercial LIKE '%$_REQUEST[nom]%')";
}

if (isset($_REQUEST['lab']) && $_REQUEST['lab'] != "") {
    $condicion .= " AND PRD.Laboratorio_Comercial LIKE '%$_REQUEST[lab]%'";
}

if (isset($_REQUEST['lab_gen']) && $_REQUEST['lab_gen'] != "") {
    $condicion .= " AND PRD.Laboratorio_Generico LIKE '%$_REQUEST[lab_gen]%'";
}


if (isset($_REQUEST['lote']) && $_REQUEST['lote'] != "") {
    $condicion .= " AND Lote LIKE '%$_REQUEST[lote]%'";
}


if (isset($_REQUEST['cum']) && $_REQUEST['cum'] != "") {
    $condicion .= " AND I.Codigo_CUM LIKE '%$_REQUEST[cum]%'";
}

if (isset($_REQUEST['bod']) && $_REQUEST['bod'] != "") {
    $condicion .= " AND b.Nombre LIKE '%$_REQUEST[bod]%'";
}


if (isset($_REQUEST['cant']) && $_REQUEST['cant'] != "") {
    $condicion .= " AND (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada)=$_REQUEST[cant]";
}

if (isset($_REQUEST['cant_apar']) && $_REQUEST['cant_apar'] != "") {
    $condicion .= " AND I.Cantidad_Apartada=$_REQUEST[cant_apar]";
}

if (isset($_REQUEST['cant_sel']) && $_REQUEST['cant_sel'] != "") {
    $condicion .= " AND I.Cantidad_Seleccionada=$_REQUEST[cant_sel]";
}


if (isset($_REQUEST['costo']) && $_REQUEST['costo'] != "") {
    $condicion .= " AND I.Costo=$_REQUEST[costo]";
}

if (isset($_REQUEST['invima']) && $_REQUEST['invima'] != "") {
    $condicion .= " AND PRD.Invima LIKE '%$_REQUEST[invima]%'";
}
if (isset($_REQUEST['lista']) && $_REQUEST['lista'] != "") {
    $condicion .= " AND PLG.Id_Lista_Ganancia=$_REQUEST[lista]";
}
if (isset($_REQUEST['iva']) && $_REQUEST['iva'] != "") {
    $condicion .= " AND PRD.Gravado='$_REQUEST[iva]'";
}


if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
    $condicion .= " AND I.Fecha_Vencimiento BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}

$condicion_principal = '';

if (isset($_REQUEST['id']) && ($_REQUEST['id'] != "" && $_REQUEST['id'] != "0")) {
    $condicion_principal = " WHERE I.Id_Bodega=".$_REQUEST['id'];
} else {
    $condicion_principal = " WHERE I.Id_Bodega!=0";
}

$query='SELECT COUNT(*) AS Total
FROM Inventario I
INNER JOIN Producto PRD
On I.Id_Producto=PRD.Id_Producto
INNER JOIN Bodega b ON I.Id_Bodega=b.Id_Bodega
LEFT JOIN Producto_Lista_Ganancia PLG ON PRD.Codigo_Cum=PLG.Cum
' . $condicion_principal . ' ' . $condicion.$condicion_sin_inventario;

$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 20; 
$numReg = $total["Total"]; 
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

if ($sin_inventario == "false") {
    $condicion_sin_inventario = " HAVING (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) > 0";
}

$query='SELECT I.*, IF((I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada)<0, 0, (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada)) AS Cantidad_Disponible, PRD.Laboratorio_Generico , CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida, " ") as Nombre_Producto, PRD.Tipo,
PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, PRD.Invima,CONCAT(PRD.Embalaje, ". Categoria: ", C.Nombre) as  Embalaje, b.Nombre, IFNULL(PLG.Precio, 0) as Precio_Lista, PRD.Gravado
FROM Inventario I
INNER JOIN Producto PRD
On I.Id_Producto=PRD.Id_Producto
INNER JOIN Categoria C ON PRD.Id_Categoria=C.Id_Categoria
INNER JOIN Bodega b ON I.Id_Bodega=b.Id_Bodega
LEFT JOIN Producto_Lista_Ganancia PLG ON PRD.Codigo_Cum=PLG.Cum
'. $condicion_principal . ' ' . $condicion.' GROUP BY I.Id_Inventario, Lote'.$condicion_sin_inventario.' ORDER BY PRD.Nombre_Comercial LIMIT '.$limit.','.$tamPag;



$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$inventario['inventarios'] = $oCon->getData();
unset($oCon);
$i=-1;

$inventario['numReg'] = $numReg;

echo json_encode($inventario);
?>