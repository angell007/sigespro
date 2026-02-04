<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$cliente = ( isset( $_REQUEST['nombre'] ) ? $_REQUEST['nombre'] : '' );

$meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
$final=[];
 
$fecha=date('Y');

$i=-1;

foreach ($meses as $key => $value) {
  # code...
    $query = 'SELECT 
    SUM( IFNULL( Valor_Medicamentos , 0) ) AS Medicamento,
    SUM( IFNULL( Valor_Materiales  , 0 ) ) AS Material
  FROM Metas M
  INNER JOIN Metas_Zonas MZ ON MZ.Id_Meta = M.Id_Metas
  INNER JOIN Objetivos_Meta OM ON OM.Id_Metas_Zonas = MZ.Id_Metas_Zonas

  WHERE M.Anio='.$fecha.' AND OM.Mes="'.$value.'"'; 
/*   echo $query;exit; */
  $oCon= new consulta();
  $oCon->setQuery($query);
  $total = $oCon->getData();
  unset($oCon);

  $final[$key]["Medicamento"] = number_format((float)$total['Medicamento'],0,"","");
  $final[$key]["Material"] = number_format((float)$total['Material'],0,"","");
  $final[$key]["Mes"] = $meses[$key];

}
/* 
for($h=1;$h<=12; $h++){ $i++;

} */

echo json_encode($final);

