<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['iden']) && $_REQUEST['iden'] != "") {
    $condicion .= "WHERE p.Id_Proveedor=$_REQUEST[iden]";
}
if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != "") {
    if ($condicion != "") {
        $condicion .= " AND p.Nombre LIKE '%$_REQUEST[nom]%'";
    } else {
        $condicion .= "WHERE p.Nombre LIKE '%$_REQUEST[nom]%'";
    }
}
if (isset($_REQUEST['dir']) && $_REQUEST['dir'] != "") {
    if ($condicion != "") {
        $condicion .= " AND p.Direccion LIKE '%$_REQUEST[dir]%'";
    } else {
        $condicion .= "WHERE p.Direccion LIKE '%$_REQUEST[dir]%'";
    }
}
if (isset($_REQUEST['ciu']) && $_REQUEST['ciu'] != "") {
    if ($condicion != "") {
        $condicion .= " AND M.Nombre LIKE '%$_REQUEST[ciu]%'";
    } else {
        $condicion .= "WHERE M.Nombre LIKE '%$_REQUEST[ciu]%'";
    }
}
if (isset($_REQUEST['correo']) && $_REQUEST['correo'] != "") {
    if ($condicion != "") {
        $condicion .= " AND p.Correo LIKE '%$_REQUEST[correo]%'";
    } else {
        $condicion .= "WHERE p.Correo LIKE '%$_REQUEST[correo]%'";
    }
}
if (isset($_REQUEST['reg']) && $_REQUEST['reg'] != "") {
    if ($condicion != "") {
        $condicion .= " AND p.Regimen LIKE '%$_REQUEST[reg]%'";
    } else {
        $condicion .= "WHERE p.Regimen LIKE '%$_REQUEST[reg]%'";
    }
}

$query='SELECT COUNT(*) AS Total
        FROM Proveedor p 
        INNER JOIN Municipio M
        ON p.Id_Municipio=M.Id_Municipio ' . $condicion;

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

$query="SELECT M.Nombre as Ciudad,
        p.Id_Proveedor , 
        p.Nombre, 
        p.Direccion, 
        p.Celular , 
        p.Correo , 
        p.Regimen, 
        IFNULL((SELECT CONCAT_WS(' ',Nombres,Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = p.Identificacion_Funcionario),'Sin funcionario') AS Funcionario, 
        p.Rut, 
        p.Fecha_Registro,
        p.Estado
        FROM Proveedor p 
        INNER JOIN Municipio M ON p.Id_Municipio=M.Id_Municipio 
        $condicion
        Order By Fecha_Registro desc
        LIMIT $limit, $tamPag";

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$proveedores['proveedores'] = $oCon->getData();
unset($oCon);

$proveedores['numReg'] = $numReg;

echo json_encode($proveedores);
?>