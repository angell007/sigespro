<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['categoria']) && $_REQUEST['categoria'] != "") {
    $condicion .= " WHERE C.Id_Categoria_Nueva = $_REQUEST[categoria]";
}

if (isset($_REQUEST['departamento']) && $_REQUEST['departamento']) {
    if ($condicion != "") {
        $condicion .= " AND D.Id_Departamento = $_REQUEST[departamento]";
    } else {
        $condicion .= " WHERE D.Id_Departamento = $_REQUEST[departamento]";
    }
}

if (isset($_REQUEST['municipio']) && $_REQUEST['municipio']) {
    if ($condicion != "") {
        $condicion .= " AND M.Nombre LIKE '%$_REQUEST[municipio]%'";
    } else {
        $condicion .= " WHERE M.Nombre LIKE '%$_REQUEST[municipio]%'";
    }
}

if (isset($_REQUEST['direccion']) && $_REQUEST['direccion']) {
    if ($condicion != "") {
        $condicion .= " AND C.Direccion LIKE '%$_REQUEST[direccion]%'";
    } else {
        $condicion .= " WHERE C.Direccion LIKE '%$_REQUEST[direccion]%'";
    }
}

if (isset($_REQUEST['telefono']) && $_REQUEST['telefono']) {
    if ($condicion != "") {
        $condicion .= " AND C.Telefono LIKE '%$_REQUEST[telefono]%'";
    } else {
        $condicion .= " WHERE C.Telefono LIKE '%$_REQUEST[telefono]%'";
    }
}

$query = 'SELECT COUNT(*) AS Total 
          FROM Categoria_Nueva C 
          INNER JOIN Departamento D 
          ON D.Id_Departamento = C.Departamento 
          LEFT JOIN Municipio M 
          ON C.Municipio = M.Id_Municipio'.$condicion;

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

$query = 'SELECT C.* , D. Nombre as NombreDepartamento , M.Nombre as NombreMunicipio 
          FROM Categoria_Nueva C
          INNER JOIN Departamento D 
          ON D.Id_Departamento = C.Departamento 
          LEFT JOIN Municipio M 
          ON C.Municipio = M.Id_Municipio '.$condicion.' LIMIT '.$limit.','.$tamPag;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['Categorias'] = $oCon->getData();
unset($oCon);

$resultado['numReg'] = $numReg;

echo json_encode($resultado);

?>