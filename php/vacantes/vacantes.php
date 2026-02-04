<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$fecha_inicial = isset($_REQUEST['fechainicio']) ? $_REQUEST['fechainicio'] : false;
$fecha_final = isset($_REQUEST['fechafin']) ? $_REQUEST['fechafin'] : false;

$condicion = '';

if ($condicion != "") {
    if (isset($_REQUEST['titulo']) && $_REQUEST['titulo'] != "") {
        $condicion .= " AND Titulo_Vacante LIKE '%$_REQUEST[titulo]%'";
    }
} else {
    if (isset($_REQUEST['titulo']) && $_REQUEST['titulo'] != "") {
        $condicion .= " WHERE Titulo_Vacante LIKE '%$_REQUEST[titulo]%'";
    } 
}

if ($condicion != "") {
    if (isset($_REQUEST['dependencia']) && $_REQUEST['dependencia'] != "") {
        $condicion .= " AND D.Nombre LIKE '%$_REQUEST[dependencia]%'";
    }
} else {
    if (isset($_REQUEST['dependencia']) && $_REQUEST['dependencia'] != "") {
        $condicion .= " WHERE D.Nombre LIKE '%$_REQUEST[dependencia]%'";
    } 
}

if ($condicion != "") {
  if (isset($_REQUEST['cargo']) && $_REQUEST['cargo'] != "") {
    $condicion .= " AND C.Nombre LIKE '%$_REQUEST[cargo]%'";
  }
} else {
  if (isset($_REQUEST['cargo']) && $_REQUEST['cargo'] != "") {
    $condicion .= "WHERE C.Nombre LIKE '%$_REQUEST[cargo]%'";
  }
}

if ($condicion != "") {
    if (isset($_REQUEST['departamento']) && $_REQUEST['departamento'] != "") {
        $condicion .= " AND DT.Nombre LIKE '%$_REQUEST[departamento]%'";
    }
} else {
    if (isset($_REQUEST['departamento']) && $_REQUEST['departamento'] != "") {
        $condicion .= " WHERE DT.Nombre LIKE '%$_REQUEST[departamento]%'";
    } 
}

if ($condicion != "") {
    if (isset($_REQUEST['municipio']) && $_REQUEST['municipio'] != "") {
        $condicion .= " AND M.Nombre LIKE '%$_REQUEST[municipio]%'";
    }
} else {
    if (isset($_REQUEST['municipio']) && $_REQUEST['municipio'] != "") {
        $condicion .= " WHERE M.Nombre LIKE '%$_REQUEST[municipio]%'";
    } 
}

if ($condicion != "") {
    if (isset($_REQUEST['estado']) && $_REQUEST['estado'] != "") {
        $condicion .= " AND Estado LIKE '%$_REQUEST[estado]%'";
    }
} else {
    if (isset($_REQUEST['estado']) && $_REQUEST['estado'] != "") {
        $condicion .= " WHERE Estado LIKE '%$_REQUEST[estado]%'";
    } 
}

if ($condicion != "") {
    if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
        $condicion .= " AND Fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
} else {
    if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
        $condicion .= "WHERE Fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    } 
}

if ($fecha_inicial && $fecha_final) {
    if ($condicion != "") {
    
        $condicion .= " AND Fecha_Inicio >=  '$fecha_inicial' AND Fecha_Fin <=  '$fecha_final' ";
    } else {
        $condicion .= " WHERE Fecha_Inicio >= '$fecha_inicial' AND Fecha_Fin <=  '$fecha_final'";
    }
}

$query = 'SELECT COUNT(*) AS Total FROM Vacante v LEFT JOIN Cargo C 
ON v.Cargo = C.Id_Cargo
LEFT JOIN Dependencia D 
ON v.Dependencia = D.Id_Dependencia 
LEFT JOIN Departamento DT 
ON v.Departamento = DT.Id_Departamento
LEFT JOIN Municipio M 
ON v.Municipio = M.Id_Municipio '.$condicion;


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

$query = 'SELECT v.*, DATE_FORMAT(Fecha_Inicio, "%d/%m/%Y") AS Fecha_Inicio, DATE_FORMAT(Fecha_Fin, "%d/%m/%Y") AS Fecha_Fin, DATE_FORMAT(Fecha, "%d/%m/%Y") AS Fecha, C.Nombre as NCargo,
D.Nombre as NDependencia,
DT.Nombre as NDepartamento,
M.Nombre as NMunicipio
FROM Vacante v 
LEFT JOIN Cargo C 
ON v.Cargo = C.Id_Cargo
LEFT JOIN Dependencia D 
ON v.Dependencia = D.Id_Dependencia
LEFT JOIN Departamento DT 
ON v.Departamento = DT.Id_Departamento
LEFT JOIN Municipio M 
ON v.Municipio = M.Id_Municipio '. $condicion.' 
ORDER BY  Fecha DESC LIMIT '.$limit.','.$tamPag.'';


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$vacante['vacantes'] = $oCon->getData();
unset($oCon);


$vacante['numReg'] = $numReg;

echo json_encode($vacante);