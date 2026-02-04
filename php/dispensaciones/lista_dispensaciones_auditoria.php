<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
    $condicion .= " AND D.Codigo LIKE '%$_REQUEST[cod]%'";
}

if (isset($_REQUEST['depar']) && $_REQUEST['depar'] != "") {
  $condicion .= " AND L.Id_Departamento = $_REQUEST[depar]";
}

if (isset($_REQUEST['cliente']) && $_REQUEST['cliente'] != "") {

  $condicion .= " AND D.EPS LIKE '%$_REQUEST[cliente]%'";
}

if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
    $id_servicio = GetIdServicio($_REQUEST['tipo']);
    $condicion .= " AND D.Id_Servicio=$id_servicio AND D.Id_Tipo_Servicio = $_REQUEST[tipo]";
    //$condicion .= " AND D.Tipo='$_REQUEST[tipo]'";
}else{

    $id_tipo_servicio = GetIdServicioCapita();
    $condicion .= " AND D.Id_Tipo_Servicio != $id_tipo_servicio";
}
if (isset($_REQUEST['facturador']) && $_REQUEST['facturador'] != "") {
    $condicion .= " AND IFNULL(CONCAT(F.Nombres, ' ', F.Apellidos),'No Asignado') LIKE '%$_REQUEST[facturador]%'";
}
if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
    $condicion .= " AND DATE_FORMAT(Fecha_Actual, '%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}



$query = "SELECT COUNT(*) AS Total
          FROM
          (
            SELECT D.Id_Dispensacion,
            D.Codigo, D.Pendientes,
            (select SUM(PD.Cantidad_Entregada) from Producto_Dispensacion PD Where PD.Id_Dispensacion = D.Id_Dispensacion ) as Entregas
            FROM Dispensacion D
            LEFT JOIN Funcionario F
            on D.Facturador_Asignado=F.Identificacion_Funcionario
            INNER JOIN Punto_Dispensacion P
            on D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
            INNER JOIN Departamento L
            on P.Departamento=L.Id_Departamento
            WHERE D.Estado_Facturacion = 'Sin Facturar' AND D.Estado_Dispensacion != 'Anulada'
            $condicion
            HAVING 
            (If(D.Codigo LIKE 'PEN%' AND D.Pendientes >0, 0, Entregas ))>0 # las dispensaciones de tipo parciales, deben estar entregadas totalmente para poderse facturar
          )D";

$oCon= new consulta();

$oCon->setQuery($query);
$dispensaciones = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 10; 
$numReg = $dispensaciones["Total"]; 
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


$query = "SELECT D.*, DATE_FORMAT(D.Fecha_Actual, '%d/%m/%Y') as Fecha_Dis, L.Nombre AS NombreDepartamento,
          IFNULL(CONCAT(F.Nombres, ' ', F.Apellidos),'No Asignado') as Funcionario, 
          P.Nombre as Punto_Dispensacion, L.Nombre as Departamento,
          (SELECT CONCAT(S.Nombre,' - ',T.Nombre) as Nombre FROM Tipo_Servicio T INNER JOIN Servicio S on T.Id_Servicio=S.Id_Servicio WHERE T.Id_Tipo_Servicio = D.Id_Tipo_Servicio) AS Servicio,
          (select SUM(PD.Cantidad_Entregada) from Producto_Dispensacion PD Where PD.Id_Dispensacion = D.Id_Dispensacion ) as Entregas
          FROM Dispensacion D
          LEFT JOIN Funcionario F
          on D.Facturador_Asignado=F.Identificacion_Funcionario
          INNER JOIN Punto_Dispensacion P
          on D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
          INNER JOIN Departamento L
          on P.Departamento=L.Id_Departamento
          WHERE D.Estado_Facturacion = 'Sin Facturar' AND D.Estado_Dispensacion != 'Anulada'
          $condicion
          HAVING 
          (If(D.Codigo LIKE 'PEN%' AND D.Pendientes >0, 0, Entregas ))>0 # las dispensaciones de tipo parciales, deben estar entregadas totalmente para poderse facturar
          ORDER BY D.Id_Dispensacion DESC LIMIT $limit, $tamPag";
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$dispensaciones["dispensaciones"] = $oCon->getData();
unset($oCon);

$dispensaciones["numReg"] = $numReg;

echo json_encode($dispensaciones);

//27/08/2019 - Franklin Guerra - 11:08am
function GetIdServicio($idTipoServicio){
  $query = '
    SELECT 
      Id_Servicio
    FROM Tipo_Servicio
    WHERE 
      Id_Tipo_Servicio = '.$idTipoServicio;

  $oCon= new consulta();
  $oCon->setTipo('simple');
  $oCon->setQuery($query);
  $servicio= $oCon->getData();
  unset($oCon);

  return $servicio['Id_Servicio'];
}

function GetIdServicioCapita(){
  $query = '
    SELECT 
      Id_Tipo_Servicio
    FROM Tipo_Servicio
    WHERE 
      Nombre = "CAPITA"';

  $oCon= new consulta();
  $oCon->setTipo('simple');
  $oCon->setQuery($query);
  $servicio= $oCon->getData();
  unset($oCon);

  return $servicio['Id_Tipo_Servicio'];
}
?>