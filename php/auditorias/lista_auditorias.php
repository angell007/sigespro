<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if(isset($_REQUEST['sin_dis']) && $_REQUEST['sin_dis'] != ""){
  if($_REQUEST['sin_dis']=="true"){
    $condicion.=" WHERE A.Id_Dispensacion IS NULL ";
  }
 
}

if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
  if ($condicion != "") {
    $condicion .= "AND  A.Id_Auditoria='".str_replace("AUD00","",$_REQUEST['cod'])."'";
  }else{
    $condicion .= "WHERE A.Id_Auditoria='".str_replace("AUD00","",$_REQUEST['cod'])."'";
  }
 
}



if ($condicion != "") {
  if (isset($_REQUEST['pac']) && $_REQUEST['pac'] != "") {
    $condicion .= " AND A.Id_Paciente LIKE '%$_REQUEST[pac]%'";
  }
} else {
  if (isset($_REQUEST['pac']) && $_REQUEST['pac'] != "") {
    $condicion .= " WHERE A.Id_Paciente LIKE '%$_REQUEST[pac]%'";
  }
}

if ($condicion != "") {
  if (isset($_REQUEST['punto']) && $_REQUEST['punto'] != "") {
    $condicion .= " AND A.Punto_Pre_Auditoria = $_REQUEST[punto]";
  }
} else {
  if (isset($_REQUEST['punto']) && $_REQUEST['punto'] != "") {
    $condicion .= " WHERE A.Punto_Pre_Auditoria = $_REQUEST[punto]";
  }
}

if ($condicion != "") {
  if (isset($_REQUEST['serv']) && $_REQUEST['serv'] != "") {
    $condicion .= " AND A.Id_Tipo_Servicio = $_REQUEST[serv]";
  }
} else {
  if (isset($_REQUEST['serv']) && $_REQUEST['serv'] != "") {
    $condicion .= " WHERE A.Id_Tipo_Servicio = $_REQUEST[serv]";
  }
}

if ($condicion != "") {
  if (isset($_REQUEST['dis']) && $_REQUEST['dis'] != "") {
    $condicion .= " AND D.Codigo LIKE '%$_REQUEST[dis]%'";
  }
} else {
  if (isset($_REQUEST['dis']) && $_REQUEST['dis'] != "") {
    $condicion .= " WHERE D.Codigo LIKE '%$_REQUEST[dis]%'";
  }
}

if ($condicion != "") {
  if (isset($_REQUEST['eps']) && $_REQUEST['eps'] != "") {
    $condicion .= " AND P.EPS LIKE '%$_REQUEST[eps]%'";
  }
} else {
  if (isset($_REQUEST['eps']) && $_REQUEST['eps'] != "") {
    $condicion .= " WHERE P.EPS LIKE '%$_REQUEST[eps]%'";
  }
}

if ($condicion != "") {
  if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "" && $_REQUEST['fecha'] != "undefined") {
      $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
      $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
      $condicion .= " AND A.Fecha_Preauditoria BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59'";
  }
} else {
  if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "" && $_REQUEST['fecha'] != "undefined") {
      $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
      $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
      $condicion .= "WHERE A.Fecha_Preauditoria BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59'";
  } 
}

$query = 'SELECT COUNT(*) AS Total

FROM Auditoria A
LEFT JOIN Paciente P
ON A.Id_Paciente=P.Id_Paciente
LEFT JOIN Dispensacion D
ON A.Id_Dispensacion=D.Id_Dispensacion
INNER JOIN Tipo_Servicio TS
ON TS.Id_Tipo_Servicio = A.Id_Tipo_Servicio
'.$condicion.'
ORDER BY A.Id_Auditoria DESC';

$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 30; 
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


$query = 'SELECT 
CONCAT_WS(" ",P.Primer_Nombre,P.Primer_Apellido) as Paciente, 
A.Estado, 
(SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion = A.Punto_Pre_Auditoria) as Nombre_Punto, 
(SELECT Imagen FROM Funcionario WHERE Identificacion_Funcionario=A.Funcionario_Preauditoria) as Imagen1,
A.Fecha_Preauditoria, 
DATE_FORMAT(NOW(),"%Y-%m-%d") AS Hoy, 
A.Fecha_Auditoria,
A.Id_Auditoria,
(SELECT CONCAT(S.Nombre,"-",T.Nombre) FROM Tipo_Servicio T INNER JOIN Servicio S ON T.Id_Servicio=S.Id_Servicio WHERE T.Id_Tipo_Servicio=A.Id_Tipo_Servicio ) as TipoServicio,
A.Id_Dispensacion,
D.Codigo as DIS,
A.Id_Paciente,
P.EPS
FROM Auditoria A
LEFT JOIN Paciente P
ON A.Id_Paciente=P.Id_Paciente
LEFT JOIN Dispensacion D
ON A.Id_Dispensacion=D.Id_Dispensacion
'.$condicion.'
ORDER BY A.Id_Auditoria DESC LIMIT '.$limit.','.$tamPag;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado["Auditorias"] = $oCon->getData();
unset($oCon);

$resultado['Registros'] = $numReg;


echo json_encode($resultado);
?>