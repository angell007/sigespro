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
          WHERE
            Semana ='.$semana_record .' AND Dia = '.$dia;
            
    $oCon= new consulta();
		$oCon->setQuery($query1);
		$oCon->setTipo("simple");
		$r = $oCon->getData();
    unset($oCon);
    
$condicion2 = ' WHERE R.Estado = "Activa" AND R.Estado_Alistamiento = 1';

if (isset($_REQUEST['codigo2']) && $_REQUEST['codigo2'] != "") {
    $condicion .= " AND R.Codigo LIKE '%$_REQUEST[codigo2]%'";
    $condicion2 .= " AND R.Codigo LIKE '%$_REQUEST[codigo1]%'";
}

if (isset($_REQUEST['origen2']) && $_REQUEST['origen2'] != "") {
    $condicion .= " AND R.Nombre_Origen LIKE '%$_REQUEST[origen2]%'";
    $condicion2 .= " AND B.Nombre LIKE '%$_REQUEST[destino1]%'";
}

if (isset($_REQUEST['destino2']) && $_REQUEST['destino2'] != "") {
    $condicion .= " AND R.Nombre_Destino LIKE '%$_REQUEST[destino2]%'";
    $condicion2 .= " AND P.Nombre  LIKE '%$_REQUEST[origen1]%'";
}
$bodegas_funcionarios = "(SELECT Id_Bodega FROM Funcionario_Bodega WHERE Identificacion_Funcionario = $funcionario)";

$condicion_principal=' WHERE R.Tipo_Origen="Bodega" AND R.Estado_Alistamiento = 1 AND R.Estado ="Pendiente" AND (R.Fase_2="0" OR R.Fase_2="'.$funcionario.'" AND R.Id_Origen IN ('.$bodegas_funcionarios.'))';
$condicion_destino='';
/*if($r['puntos']){
  $condicion_destino .= " AND R.Tipo_Destino = 'Punto_Dispensacion' AND R.Id_Destino IN (".$r['puntos'].") AND R.Tipo_Bodega !='REFRIGERADOS' ";
}*/

$condicion_destino.=" AND R.Tipo_Destino = 'Punto_Dispensacion' AND R.Id_Destino IN (SELECT Id_Punto_Dispensacion FROM Punto_Dispensacion)";



$condicion_cliente .= " AND (R.Tipo_Destino = 'Cliente' OR R.Tipo_Destino='Bodega'OR R.Tipo_Destino='Contrato'  OR R.Tipo_Bodega ='REFRIGERADOS') ";

/* $categorias=GetCategoriasFuncionarios();  */

/* if(!$categorias){
    $query = 'SELECT 
    R.*, F.Imagen
  FROM Remision R
  INNER JOIN Funcionario F
  ON F.Identificacion_Funcionario = R.Identificacion_Funcionario
  '.$condicion_principal.' AND R.Id_Categoria!=12 ' . $condicion .' ' . $condicion_destino 
  .' UNION SELECT 
    R.*, F.Imagen
  FROM Remision R
  INNER JOIN Funcionario F
  ON F.Identificacion_Funcionario = R.Identificacion_Funcionario
  '.$condicion_principal.' ' . $condicion.' ' . $condicion_cliente ;
}else{ */
/*   $con=" AND R.Id_Categoria_Nueva IN ($categorias) "; */
$con="";
  $query = 'SELECT 
            R.Id_Contrato,R.Codigo, R.Nombre_Origen, R.Nombre_Destino, R.Prioridad, R.Id_Remision, F.Imagen, "Remision" AS Tipo
          FROM Remision R
          INNER JOIN Funcionario F
          ON F.Identificacion_Funcionario = R.Identificacion_Funcionario
          '.$condicion_principal.$con.' ' . $condicion .' ' . $condicion_destino.' 
          UNION SELECT 
          R.Id_Contrato,R.Codigo, R.Nombre_Origen, R.Nombre_Destino, R.Prioridad, R.Id_Remision, F.Imagen, "Remision" AS Tipo
          FROM Remision R
          INNER JOIN Funcionario F
          ON F.Identificacion_Funcionario = R.Identificacion_Funcionario
          '.$condicion_principal.$con.' ' . $condicion.' ' . $condicion_cliente .'

          UNION ALL SELECT 
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

      
    
/* } */




$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$remisiones = $oCon->getData();
unset($oCon);

echo json_encode($remisiones);


function GetCategoriasFuncionarios(){
  global $funcionario;
  $query="SELECT  GROUP_CONCAT(BC.Id_Categoria_Nueva) as Categoria FROM Funcionario_Bodega_Nuevo FB
         INNER JOIN Bodega_Nuevo_Categoria_Nueva BC
         ON BC.Id_Bodega_Nuevo = FB.Id_Bodega_Nuevo
      WHERE Identificacion_Funcionario=$funcionario AND BC.Id_Bodega_Nuevo = 1" ;

  $oCon= new consulta();
  $oCon->setQuery($query);
  $res = $oCon->getData();
  unset($oCon);

  return $res ? $res['Categoria'] : false;
}