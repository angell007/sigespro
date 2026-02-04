<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if ($condicion != "") {
    if (isset($_REQUEST['tipo_tecnologia']) && $_REQUEST['tipo_tecnologia'] != "") {
        $condicion .= " AND TTM.Nombre LIKE '%$_REQUEST[tipo_tecnologia]%'";
    }
} else {
    if (isset($_REQUEST['tipo_tecnologia']) && $_REQUEST['tipo_tecnologia'] != "") {
        $condicion .= "WHERE TTM.Nombre LIKE '%$_REQUEST[tipo_tecnologia]%'";
    }
}

if ($condicion != "") {
    if (isset($_REQUEST['cod_anterior']) && $_REQUEST['cod_anterior'] != "") {
        $condicion .= " AND PTTM.Codigo_Anterior = '$_REQUEST[cod_anterior]'";
    }
} else {
    if (isset($_REQUEST['cod_anterior']) && $_REQUEST['cod_anterior'] != "") {
        $condicion .= "WHERE PTTM.Codigo_Anterior = '$_REQUEST[cod_anterior]'";
    }
}

if ($condicion != "") {
    if (isset($_REQUEST['cod_actual']) && $_REQUEST['cod_actual'] != "") {
        $condicion .= " AND PTTM.Codigo_Actual = '$_REQUEST[cod_actual]'";
    }
} else {
    if (isset($_REQUEST['cod_actual']) && $_REQUEST['cod_actual'] != "") {
        $condicion .= "WHERE PTTM.Codigo_Actual = '$_REQUEST[cod_actual]'";
    }
}

if ($condicion != "") {
    if (isset($_REQUEST['descripcion']) && $_REQUEST['descripcion'] != "") {
        $condicion .= " AND PTTM.Descripcion LIKE '%$_REQUEST[descripcion]%'";
    }
} else {
    if (isset($_REQUEST['descripcion']) && $_REQUEST['descripcion'] != "") {
        $condicion .= "WHERE PTTM.Descripcion LIKE '%$_REQUEST[descripcion]%'";
    }
}

if ($condicion != "") {
    if (isset($_REQUEST['producto']) && $_REQUEST['producto'] != "") {
        $condicion .= " AND P.Nombre_Comercial LIKE '%$_REQUEST[producto]%'";
    }
} else {
    if (isset($_REQUEST['producto']) && $_REQUEST['producto'] != "") {
        $condicion .= "WHERE P.Nombre_Comercial LIKE '%$_REQUEST[producto]%'";
    }
}

$query = 'SELECT COUNT(*) AS Total FROM Producto_Tipo_Tecnologia_Mipres PTTM
INNER JOIN Tipo_Tecnologia_Mipres TTM ON TTM.Id_Tipo_Tecnologia_Mipres=PTTM.Id_Tipo_Tecnologia_Mipres
INNER JOIN Producto P ON P.Id_Producto=PTTM.Id_Producto ' . $condicion;

$oCon= new consulta();

$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 15; 
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

$query = 'SELECT PTTM.Id_Producto_Tipo_Tecnologia_Mipres, PTTM.Codigo_Anterior, PTTM.Codigo_Actual, PTTM.Descripcion, CONCAT(TTM.Nombre," (",TTM.Codigo,")") AS Tipo_Tecnologia, P.Nombre_Comercial AS Producto
FROM Producto_Tipo_Tecnologia_Mipres PTTM
INNER JOIN Tipo_Tecnologia_Mipres TTM ON TTM.Id_Tipo_Tecnologia_Mipres=PTTM.Id_Tipo_Tecnologia_Mipres
INNER JOIN Producto P ON P.Id_Producto=PTTM.Id_Producto  '.$condicion.' ORDER BY PTTM.Id_Producto_Tipo_Tecnologia_Mipres DESC LIMIT '.$limit.','.$tamPag;

$oCon= new consulta();

$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$tecnologia['tecnologias'] = $oCon->getData();
unset($oCon);

$tecnologia['numReg'] = $numReg;

echo json_encode($tecnologia);
?>