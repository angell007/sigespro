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
    


if (isset($_REQUEST['codigo2']) && $_REQUEST['codigo2'] != "") {
    $condicion .= " AND R.Codigo LIKE '%$_REQUEST[codigo2]%'";
}

if (isset($_REQUEST['origen2']) && $_REQUEST['origen2'] != "") {
    $condicion .= " AND R.Nombre_Origen LIKE '%$_REQUEST[origen2]%'";
}

if (isset($_REQUEST['destino2']) && $_REQUEST['destino2'] != "") {
    $condicion .= " AND R.Nombre_Destino LIKE '%$_REQUEST[destino2]%'";
}
$bodegas_funcionarios = "(SELECT Id_Bodega FROM Funcionario_Bodega WHERE Identificacion_Funcionario = $funcionario)";

$condicion_principal=' WHERE R.Tipo_Origen="Bodega" AND R.Estado_Alistamiento = 1 AND R.Estado ="Pendiente" AND (R.Fase_2="0" OR R.Fase_2="'.$funcionario.'" AND R.Id_Origen IN ('.$bodegas_funcionarios.'))';
$condicion_destino='';
/*if($r['puntos']){
  $condicion_destino .= " AND R.Tipo_Destino = 'Punto_Dispensacion' AND R.Id_Destino IN (".$r['puntos'].") AND R.Tipo_Bodega !='REFRIGERADOS' ";
}*/

$condicion_destino.=" AND R.Tipo_Destino = 'Punto_Dispensacion' AND R.Id_Destino IN (SELECT Id_Punto_Dispensacion FROM Punto_Dispensacion)";



$condicion_cliente .= " AND (R.Tipo_Destino = 'Cliente' OR R.Tipo_Destino='Bodega' OR R.Tipo_Bodega ='REFRIGERADOS') ";

$categorias=GetCategoriasFuncionarios(); 

if(!$categorias){
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
}else{
  $con=" AND R.Id_Categoria IN ($categorias) ";
  $query = 'SELECT 
            R.*, F.Imagen
          FROM Remision R
          INNER JOIN Funcionario F
          ON F.Identificacion_Funcionario = R.Identificacion_Funcionario
          '.$condicion_principal.$con.' ' . $condicion .' ' . $condicion_destino.' UNION SELECT 
    R.*, F.Imagen
  FROM Remision R
  INNER JOIN Funcionario F
  ON F.Identificacion_Funcionario = R.Identificacion_Funcionario
  '.$condicion_principal.$con.' ' . $condicion.' ' . $condicion_cliente ;
          ;

}




$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$remisiones = $oCon->getData();
unset($oCon);

echo json_encode($remisiones);

function GetCategoriasFuncionarios(){
  global $funcionario;
  $query="SELECT  GROUP_CONCAT(Id_Categoria) as Categoria FROm Funcionario_Categoria WHERE Identificacion_Funcionario=$funcionario";

  $oCon= new consulta();
  $oCon->setQuery($query);
  $res = $oCon->getData();
  unset($oCon);

  return $res ? $res['Categoria'] : false;
}
?>