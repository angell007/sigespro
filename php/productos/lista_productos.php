<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != '') {
    $condicion .= 'WHERE (P.Principio_Activo LIKE "%'.$_REQUEST['nom'].'%" OR P.Presentacion LIKE "%'.$_REQUEST['nom'].'%" OR P.Concentracion LIKE "%'.$_REQUEST['nom'].'%" OR P.Nombre_Comercial LIKE "%'.$_REQUEST['nom'].'%" OR P.Cantidad LIKE "%'.$_REQUEST['nom'].'%" OR P.Unidad_Medida LIKE "%'.$_REQUEST['nom'].'%")';
}

if ($condicion != "") {
    if (isset($_REQUEST['cum']) && $_REQUEST['cum'] != '') {
        $condicion .= ' AND Codigo_Cum LIKE "'.$_REQUEST['cum'].'%"';
    }
} else {
    if (isset($_REQUEST['cum']) && $_REQUEST['cum'] != '') {
        $condicion .= 'WHERE Codigo_Cum LIKE "%'.$_REQUEST['cum'].'%"';
    }
}

if ($condicion != "") {
    if (isset($_REQUEST['lab']) && $_REQUEST['lab'] != '') {
        $condicion .= ' AND Laboratorio_Generico LIKE "'.$_REQUEST['lab'].'%"';
    }
} else {
    if (isset($_REQUEST['lab']) && $_REQUEST['lab'] != '') {
        $condicion .= 'WHERE Laboratorio_Generico LIKE "'.$_REQUEST['lab'].'%"';
    }
}

if ($condicion != "") {
    if (isset($_REQUEST['nom_com']) && $_REQUEST['nom_com']) {
       $condicion .= " AND Nombre_Comercial LIKE '%$_REQUEST[nom_com]%'";
    }
} else {
    if (isset($_REQUEST['nom_com']) && $_REQUEST['nom_com']) {
       $condicion .= "WHERE Nombre_Comercial LIKE '%$_REQUEST[nom_com]%'";
    }
}

if ($condicion != "") {
    if (isset($_REQUEST['lab_gral']) && $_REQUEST['lab_gral']) {
       $condicion .= " AND Laboratorio_Comercial LIKE '%$_REQUEST[lab_gral]%'";
    }
} else {
    if (isset($_REQUEST['lab_gral']) && $_REQUEST['lab_gral']) {
       $condicion .= "WHERE Laboratorio_Comercial LIKE '%$_REQUEST[lab_gral]%'";
    }
}

if ($condicion != "") {
    if (isset($_REQUEST['inv']) && $_REQUEST['inv']) {
       $condicion .= " AND Invima LIKE '%$_REQUEST[inv]%'";
    }
} else {
    if (isset($_REQUEST['inv']) && $_REQUEST['inv']) {
       $condicion .= "WHERE Invima LIKE '%$_REQUEST[inv]%'";
    }
}

if ($condicion != "") {
    if (isset($_REQUEST['tipo']) && $_REQUEST['tipo']) {
       $condicion .= " AND Tipo='$_REQUEST[tipo]'";
    }
} else {
    if (isset($_REQUEST['tipo']) && $_REQUEST['tipo']) {
       $condicion .= "WHERE Tipo='$_REQUEST[tipo]'";
    }
}

$query = 'SELECT 
            COUNT(*) AS Total
          FROM Producto P
          '.$condicion.'
          Order by P.Codigo_Cum ASC' ;

$oCon= new consulta();

$oCon->setQuery($query);
$productos = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 20; 
$numReg = $productos["Total"]; 
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
            CONCAT( P.Principio_Activo, " ",
            P.Presentacion, " ",
            P.Concentracion, " (",
            P.Nombre_Comercial,") ",
            P.Cantidad," ",
            P.Unidad_Medida," EMB: ", P.Embalaje
            ) as Nombre, P.Codigo_Cum as Cum, 
            P.Laboratorio_Generico as Generico, 
            P.Laboratorio_Comercial as Comercial, 
            P.Invima as Invima, 
            P.Imagen as Foto, 
            P.Nombre_Comercial as Nombre_Comercial, 
            P.Id_Producto,
            P.Embalaje,
            P.Tipo as Tipo, P.Estado
          FROM Producto P
          '.$condicion.'
          Order by P.Codigo_Cum ASC LIMIT '.$limit.','.$tamPag ;
// echo $query;exit;
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado['productos'] = $oCon->getData();
unset($oCon);
$i=-1;
foreach($resultado['productos'] as $resultados){$i++;
    //echo $inventarios["Nombre_Comercial"];
    if($resultados["Tipo"]=="Material" || $resultados['Nombre'] == ""){
        
        $resultado['productos'][$i]["Nombre"]=$resultados["Nombre_Comercial"]." EMB: ".$resultados['Embalaje'];
    }
}

$resultado['numReg'] = $numReg;

echo json_encode($resultado);

?>