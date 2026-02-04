<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$funcionario=( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

$semana_1 = date("W", strtotime("first monday ".date('Y-m'))); 
$semana_actual = date("W");

$semana_mes = ($semana_actual - $semana_1) ;
$semana_record =  $semana_mes % 2 == 0 ? 2 : 1;
$dia = date('w');

$query1 = 'SELECT 
            GROUP_CONCAT(PCR.Id_Punto) as puntos
          FROM  Cronograma_Remision CR
          INNER JOIN Punto_Cronograma_Remision PCR ON CR.Id_Cronograma_Remision = PCR.Id_Cronograma
          WHERE Semana ='.$semana_record .' AND Dia = '.$dia;

    $oCon= new consulta();
		$oCon->setQuery($query1);
		$oCon->setTipo("simple");
		$r = $oCon->getData();
    unset($oCon);

    $condicion2 =' WHERE R.Estado = "Activa" AND R.Estado_Alistamiento = 0 ';

if (isset($_REQUEST['codigo1']) && $_REQUEST['codigo1'] != "") {
    $condicion .= " AND R.Codigo LIKE '%$_REQUEST[codigo1]%'";
    $condicion2 .= " AND R.Codigo LIKE '%$_REQUEST[codigo1]%'";
    
}

if (isset($_REQUEST['origen1']) && $_REQUEST['origen1'] != "") {
    $condicion .= " AND R.Nombre_Origen LIKE '%$_REQUEST[origen1]%'";
    $condicion2 .= " AND B.Nombre LIKE '%$_REQUEST[destino1]%'";
    
}

if (isset($_REQUEST['destino1']) && $_REQUEST['destino1'] != "") {
    $condicion .= " AND R.Nombre_Destino LIKE '%$_REQUEST[destino1]%'";
    $condicion2 .= " AND P.Nombre  LIKE '%$_REQUEST[origen1]%'";
}

$condicion_principal=' WHERE R.Tipo_Origen="Bodega" AND R.Estado_Alistamiento = 0 AND R.Estado ="Pendiente" AND (R.Fase_1="0" OR R.Fase_1="'.$funcionario.'")';
$condicion_destino='';

$condicion_destino.=" AND R.Tipo_Destino = 'Punto_Dispensacion' AND R.Id_Destino IN (SELECT Id_Punto_Dispensacion FROM Punto_Dispensacion)";
$condicion_cliente .= " AND (R.Tipo_Destino = 'Cliente' OR R.Tipo_Destino='Bodega'  OR R.Tipo_Destino='Contrato' )";

//if(!$categorias){
  $query = 'SELECT 
             R.Id_Contrato, R.Codigo, R.Nombre_Origen, R.Nombre_Destino, R.Prioridad, R.Id_Remision, F.Imagen, "Remision" AS Tipo
             FROM Remision R
             INNER JOIN Funcionario F
              ON F.Identificacion_Funcionario = R.Identificacion_Funcionario
              '.$condicion_principal.'   ' . $condicion.' ' . $condicion_destino
              .' 
              UNION SELECT 
              R.Id_Contrato,R.Codigo, R.Nombre_Origen, R.Nombre_Destino, R.Prioridad, R.Id_Remision, F.Imagen , "Remision" AS Tipo
              FROM Remision R
              INNER JOIN Funcionario F
              ON F.Identificacion_Funcionario = R.Identificacion_Funcionario
          '.$condicion_principal.' ' . $condicion.' ' . $condicion_cliente.'

              UNION SELECT 
              0,R.Codigo, B.Nombre AS Nombre_Origen, IFNULL(P.Nombre,CONCAT(P.Primer_Nombre, " " , P.Segundo_Nombre) ) AS Nombre_Destino,
              "1" AS Prioridad, R.Id_Devolucion_Compra AS Id_Remision, F.Imagen, "Devolucion" AS Tipo
              FROM Devolucion_Compra R
              INNER JOIN Funcionario F 
              ON F.Identificacion_Funcionario = R.Identificacion_Funcionario
              INNER JOIN Proveedor P 
              ON P.Id_Proveedor = R.Id_Proveedor

              INNER JOIN Bodega_Nuevo B
              ON  B.Id_Bodega_Nuevo = R.Id_Bodega_Nuevo
     
              '.$condicion2.'

          ';
// echo $query;
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$remisiones = $oCon->getData();
unset($oCon);

echo json_encode($remisiones);

function GetCategoriasFuncionarios(){
  global $funcionario;
  $query="SELECT  GROUP_CONCAT(BC.Id_Categoria_Nueva) as Categoria 
          FROM Funcionario_Bodega_Nuevo FB
          INNER JOIN Bodega_Nuevo_Categoria_Nueva BC
          ON BC.Id_Bodega_Nuevo = FB.Id_Bodega_Nuevo
          WHERE Identificacion_Funcionario=$funcionario AND BC.Id_Bodega_Nuevo = 1" ;

  $oCon= new consulta();
  $oCon->setQuery($query);
  $res = $oCon->getData();
  unset($oCon);

  return $res ? $res['Categoria'] : false;
}
?>