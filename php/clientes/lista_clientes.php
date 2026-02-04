<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['ced']) && $_REQUEST['ced'] != "") {
    $condicion .= "WHERE c.Id_Cliente LIKE '%$_REQUEST[ced]%'";
}

if (isset($_REQUEST['nom']) && $_REQUEST['nom']) {
    if ($condicion != "") {
        $condicion .= " AND c.Nombre LIKE '%$_REQUEST[nom]%'";
    } else {
        $condicion .= "WHERE c.Nombre LIKE '%$_REQUEST[nom]%'";
    }
}

if (isset($_REQUEST['dir']) && $_REQUEST['dir']) {
    if ($condicion != "") {
        $condicion .= " AND c.Direccion LIKE '%$_REQUEST[dir]%'";
    } else {
        $condicion .= "WHERE c.Direccion LIKE '%$_REQUEST[dir]%'";
    }
}

if (isset($_REQUEST['ciu']) && $_REQUEST['ciu']) {
    if ($condicion != "") {
        $condicion .= " AND d.Nombre LIKE '%$_REQUEST[ciu]%'";
    } else {
        $condicion .= "WHERE d.Nombre LIKE '%$_REQUEST[ciu]%'";
    }
}

if (isset($_REQUEST['nat_jur']) && $_REQUEST['nat_jur']) {
    if ($condicion != "") {
        $condicion .= " AND c.Tipo LIKE '$_REQUEST[nat_jur]%'";
    } else {
        $condicion .= "WHERE c.Tipo LIKE '$_REQUEST[nat_jur]%'";
    }
}
if (isset($_REQUEST['zona']) && $_REQUEST['zona']) {
    if ($condicion != "") {
        $condicion .= " AND c.Id_Zona LIKE '$_REQUEST[zona]'";
    } else {
        $condicion .= "WHERE c.Id_Zona LIKE '$_REQUEST[zona]'";
    }
}

$query='SELECT COUNT(*) AS Total
        FROM Cliente c 
        INNER JOIN Municipio d 
        ON d.Id_Municipio = c.Ciudad ' . $condicion;

$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 10; 
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

        
$query='SELECT 
        c.Id_Cliente as Id_Cliente,  
        c.Nombre as Nombre ,
        c.Direccion as Direccion ,
        d.Nombre as Ciudad, c.Tipo as Tipo, 
        (SELECT Nombre FROM Zona WHERE Id_Zona=c.Id_Zona) as Zona, 
        c.Rut, c.Estado, 
        c.Fecha_Registro
        FROM Cliente c 
        INNER JOIN Municipio d 
        ON d.Id_Municipio = c.Ciudad '.$condicion.'
        ORDER BY Fecha_Registro DESC
        LIMIT '.$limit.','.$tamPag;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$clientes['clientes'] = $oCon->getData();
unset($oCon);

$clientes['numReg'] = $numReg;

echo json_encode($clientes);
?>