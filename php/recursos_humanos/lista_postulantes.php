<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != "") {
    if($condicion==''){
        $condicion .= " WHERE CONCAT(P.Nombres,' ',P.Apellidos) LIKE '%$_REQUEST[nom]%'";
    }

}
if (isset($_REQUEST['iden']) && $_REQUEST['iden'] != "") {
    if($condicion==''){
        $condicion .= " WHERE P.Identificacion LIKE '%$_REQUEST[iden]%'";
    }else{
        $condicion .= " AND P.Identificacion LIKE '%$_REQUEST[iden]%'";
    }

}
if (isset($_REQUEST['cargo']) && $_REQUEST['cargo'] != "") {
    if($condicion==''){
        $condicion .= " WHERE P.Id_Cargo LIKE '%$_REQUEST[cargo]%'";
    }else{
        $condicion .= " AND P.Id_Cargo LIKE '%$_REQUEST[cargo]%'";
    }

}
if (isset($_REQUEST['dep']) && $_REQUEST['dep'] != "") {
    if($condicion==''){
        $condicion .= " WHERE D.Nombre LIKE '%$_REQUEST[dep]%'";
    }else{
        $condicion .= " AND D.Nombre LIKE '%$_REQUEST[dep]%'";
    }

}
if (isset($_REQUEST['depen']) && $_REQUEST['depen'] != "") {
    if($condicion==''){
        $condicion .= " WHERE P.Id_Dependencia LIKE '%$_REQUEST[depen]%'";
    }else{
        $condicion .= " AND P.Id_Dependencia LIKE '%$_REQUEST[depen]%'";
    }

}
if (isset($_REQUEST['punto']) && $_REQUEST['punto'] != "") {
    if($condicion==''){
        $condicion .= " WHERE PD.Nombre LIKE '%$_REQUEST[punto]%'";
    }else{
        $condicion .= " AND PD.Nombre LIKE '%$_REQUEST[punto]%'";
    }

}

$query=' SELECT COUNT(*) AS Total FROM Postulante P
            INNER JOIN Departamento D
            On P.Id_Departamento=D.Id_Departamento
            INNER JOIN Punto_Dispensacion PD
            ON PD.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion '.$condicion;

$oCon= new consulta();

$oCon->setQuery($query);
$total = $oCon->getData();
// var_dump($total);
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

$query = 'SELECT P.*, PD.Nombre as Punto, D.Nombre as Departamento, CONCAT_WS(" ",P.Nombres,P.Apellidos) as Postulante,
            (SELECT C.Nombre FROM Cargo C WHERE C.Id_Cargo=P.Id_Cargo) as Cargo, (SELECT C.Nombre FROM Dependencia C WHERE C.Id_Dependencia=P.Id_Dependencia) as Dependencia, (SELECT C.Nombre FROM Grupo C WHERE C.Id_Grupo=P.Id_Grupo) as Grupo
            FROM Postulante P
            INNER JOIN Departamento D
            On P.Id_Departamento=D.Id_Departamento
            INNER JOIN Punto_Dispensacion PD
            ON PD.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion'.$condicion.' ORDER BY Postulante ASC LIMIT '.$limit.','.$tamPag;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$datos['Lista'] = $oCon->getData();
unset($oCon);

$datos['numReg'] = $numReg;

echo json_encode($datos);

?>

