<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

    $condicion = '';

    if (isset($_REQUEST['nombre_dependencia']) && $_REQUEST['nombre_dependencia'] != "") {
        $condicion .= " WHERE d.Nombre LIKE '%$_REQUEST[nombre_dependencia]%'";
    }

    if (isset($_REQUEST['id_grupo']) && $_REQUEST['id_grupo']) {
        if ($condicion != "") {
            $condicion .= " AND d.Id_Grupo = $_REQUEST[id_grupo]";
        } else {
            $condicion .=  " WHERE d.Id_Grupo = $_REQUEST[id_grupo]";
        }
    }

    $query='SELECT COUNT(*) AS Total
            FROM Dependencia d 
            INNER JOIN Grupo g
            on g.Id_Grupo = d.Id_Grupo'.$condicion;

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

    $query = 'SELECT d.Id_Dependencia as Id_Dependencia , d.Nombre as NombreDependencia  , g.Nombre as NombreGrupo
            FROM Dependencia d 
            INNER JOIN Grupo g
            on g.Id_Grupo = d.Id_Grupo'.$condicion.' LIMIT '.$limit.','.$tamPag ;

    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $resultado['Dependencias'] = $oCon->getData();
    unset($oCon);

    $resultado['numReg'] = $numReg;

    echo json_encode($resultado);
?>