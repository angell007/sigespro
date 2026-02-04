<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$tipo_comprobante = (isset($_REQUEST['tipo_comprobante']) && $_REQUEST['tipo_comprobante']) ?$_REQUEST['tipo_comprobante'] : '';

$condicion = '';

if (isset($_REQUEST['cod']) && $_REQUEST['cod']) {
    $condicion .= "WHERE C.Codigo LIKE '%$_REQUEST[cod]%'";
}
if ($condicion != "") {
    if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
        $condicion .= " AND FP.Nombre='$_REQUEST[tipo]'";
    }
} else {
    if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
        $condicion .= "WHERE FP.Nombre='$_REQUEST[tipo]'";
    }
}
if ($condicion != "") {
    if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
        $condicion .= " AND C.Fecha_Comprobante BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
} else {
    if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
        $condicion .= "WHERE C.Fecha_Comprobante BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    } 
}
if ($condicion != "") {
    if (isset($_REQUEST['cli']) && $_REQUEST['cli'] != "") {
        $condicion .= " AND CL.Nombre LIKE '%$_REQUEST[cli]%'";
    }
} else {
    if (isset($_REQUEST['cli']) && $_REQUEST['cli'] != "") {
        $condicion .= "WHERE CL.Nombre LIKE '%$_REQUEST[cli]%'";
    }
}

if ($condicion != "") {
    if (isset($_REQUEST['est']) && $_REQUEST['est'] != "") {
        $condicion .= " AND C.Estado LIKE '$_REQUEST[est]'";
    }
} else {
    if (isset($_REQUEST['est']) && $_REQUEST['est'] != "") {
        $condicion .= "WHERE C.Estado LIKE '$_REQUEST[est]'";
    }
}

if ($condicion != "") {
    if ($tipo_comprobante != '') {
        $condicion .= " AND C.Tipo='".ucwords($tipo_comprobante)."'";
    }
} else {
    if ($tipo_comprobante != '') {
        $condicion .= " WHERE C.Tipo='".ucwords($tipo_comprobante)."'";
    }
}

$query = 'SELECT 
			C.*, 
			(SELECT F.Imagen FROM Funcionario F WHERE F.Identificacion_Funcionario=C.Id_Funcionario ) as Imagen , 
			FP.Nombre as Forma_Pago ,
			IFNULL(CL.Nombre, (SELECT CONCAT_WS(" ",Nombres,Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = C.Id_Cliente)) as Cliente  ,
			IFNULL(P.Nombre, (SELECT CONCAT_WS(" ",Nombres,Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = C.Id_Proveedor)) as Proveedor
			FROM Comprobante C 
LEFT JOIN Cliente CL
ON C.Id_Cliente=CL.Id_Cliente
LEFT JOIN Proveedor P
ON C.Id_Proveedor=P.Id_Proveedor 
INNER JOIN Forma_Pago FP
ON C.Id_Forma_Pago=FP.Id_Forma_Pago '.$condicion;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 30; 
$numReg = count($total); 
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


$query = 'SELECT 
			C.*, 
			(SELECT F.Imagen FROM Funcionario F WHERE F.Identificacion_Funcionario=C.Id_Funcionario ) as Imagen ,
			(SELECT F.Imagen FROM Funcionario F WHERE F.Identificacion_Funcionario=C.Id_Funcionario ) as Imagen , 
			FP.Nombre as Forma_Pago ,
			IFNULL(CL.Nombre, (SELECT CONCAT_WS(" ",Nombres,Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = C.Id_Cliente)) as Cliente  ,
			IFNULL(P.Nombre, (SELECT CONCAT_WS(" ",Nombres,Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = C.Id_Proveedor)) as Proveedor,
			FP.Nombre as Forma_Pago 
		FROM Comprobante C 
LEFT JOIN Cliente CL
ON C.Id_Cliente=CL.Id_Cliente 
LEFT JOIN Proveedor P
ON C.Id_Proveedor=P.Id_Proveedor 
INNER JOIN Forma_Pago FP
ON C.Id_Forma_Pago=FP.Id_Forma_Pago '.$condicion.' ORDER BY C.Fecha_Registro DESC LIMIT '.$limit.','.$tamPag;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

$datos['Lista']=$resultado;
$datos['numReg'] = $numReg;

echo json_encode($datos);


?>