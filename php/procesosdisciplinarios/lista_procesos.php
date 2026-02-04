<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

$punto = ( isset( $_REQUEST['punto'] ) ? $_REQUEST['punto'] : '' );
$usuario = ( isset( $_REQUEST['usuario'] ) ? $_REQUEST['usuario'] : '' );
$fecha = ( isset( $_REQUEST['fecha'] ) ? $_REQUEST['fecha'] : date("Y-m-d") );

$condicion = getCondiciones();

$query='SELECT PD.*, CONCAT(F.Nombres," ",F.Apellidos) as Funcionario  
        FROM Proceso_Disciplinario PD
        INNER JOIN Funcionario F ON F.Identificacion_Funcionario = PD.Identificacion_Funcionario
        '.$condicion.'
        ORDER By PD.Fecha_Inicio DESC';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado= $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 20; 
$numReg = count($resultado); 
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

$query= ' SELECT PD.*, CONCAT(F.Nombres," ",F.Apellidos) as Funcionario
          FROM Proceso_Disciplinario PD
          INNER JOIN Funcionario F ON F.Identificacion_Funcionario = PD.Identificacion_Funcionario
        '.$condicion.'
          ORDER By PD.Fecha_Fin DESC LIMIT '.$limit.','.$tamPag;


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['lista']= $oCon->getData();
unset($oCon);

$resultado['numReg'] = $numReg;

echo json_encode($resultado);


function getCondiciones() {
    $condicion = '';
    if (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != "") {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fechas'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fechas'])[1]);
        $condicion .= " WHERE (DATE(PD.Fecha_Inicio) BETWEEN '$fecha_inicio' AND '$fecha_fin')";
    }
    if (isset($_REQUEST['codigo']) && $_REQUEST['codigo'] != "") {
        if ($condicion != '') {
            $condicion .= " AND CONCAT('PD',PD.Id_Orden_Servicio) LIKE '%$_REQUEST[codigo]%'";
        } else {
            $condicion .= " WHERE CONCAT('PD',PD.Id_Orden_Servicio) LIKE '%$_REQUEST[codigo]%'";
        }
    }
    if (isset($_REQUEST['funcionario']) && $_REQUEST['funcionario'] != "") {
        if ($condicion != '') {
            $condicion .= " AND CONCAT(F.Nombres,' ',F.Apellidos) LIKE '%$_REQUEST[funcionario]%'";
        } else {
            $condicion .= " WHERE CONCAT(F.Nombres,' ',F.Apellidos) LIKE '%$_REQUEST[funcionario]%'";
        }
    }
    if (isset($_REQUEST['estado']) && $_REQUEST['estado'] != "") {
        if ($condicion != '') {
            $condicion .= " AND PD.Estado = '$_REQUEST[estado]'";
        } else {
            $condicion .= " WHERE PD.Estado = '$_REQUEST[estado]'";
        }
    }

    return $condicion;
    
}

?> 