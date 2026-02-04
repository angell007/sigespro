<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';

$cond_principal = " WHERE AI.Id_Ajuste_Individual=$id";

$query_principal = "SELECT AI.*,  E.Nombre AS Nombre_Estiba_Salida,
(SELECT CONCAT(F.Nombres,' ', F.Apellidos) FROM Funcionario F
 WHERE F.Identificacion_Funcionario=AI.Identificacion_Funcionario) AS Funcionario,
  (SELECT C.Nombre FROM Funcionario F INNER JOIN Cargo C ON F.Id_Cargo=C.Id_Cargo 
  WHERE F.Identificacion_Funcionario=AI.Identificacion_Funcionario) AS Cargo_Funcionario,
  (SELECT F.Firma FROM Funcionario F WHERE F.Identificacion_Funcionario=AI.Identificacion_Funcionario) as Firma,

  IF(AI.Origen_Destino = 'Bodega',(SELECT B.Nombre FROM Bodega_Nuevo B WHERE B.Id_Bodega_Nuevo=AI.Id_Origen_Destino),
 (SELECT B.Nombre FROM Punto_Dispensacion B WHERE B.Id_Punto_Dispensacion=AI.Id_Origen_Destino)) as Origen

 FROM `Ajuste_Individual` AI 
 
 LEFT JOIN Estiba E ON E.Id_Estiba = AI.Id_Origen_Estiba
 ";
    

$query = $query_principal.$cond_principal;
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$ajustes = $oCon->getData();
unset($oCon); 

if ($ajustes[0]['Cambio_Estiba'] ) {
   # code...
   $cond;
   if ($ajustes[0]['Id_Salida']) {
      # code...  
      $cond= 'WHERE AI.Id_Ajuste_Individual = '.$ajustes[0]['Id_Salida'];
   }else{
      $cond= 'WHERE AI.Id_Salida = '.$ajustes[0]['Id_Ajuste_Individual'];
   }
      $query = $query_principal.$cond;
         $oCon= new consulta();
         $oCon->setQuery($query);
         $entrada = $oCon->getData();
         unset($oCon); 
         if ($entrada) {
           
            array_push($ajustes,$entrada) ;  
         }

}

foreach ($ajustes as $key => $ajuste) {
   # code...
 
   $query = "SELECT P.Nombre_Comercial, E.Nombre AS Nombre_Nueva_Estiba, (PAI.Cantidad * PAI.Costo) AS Sub_Total,
    Concat_WS(' ',CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida, ' '), '\nCUM:',P.Codigo_Cum)  as Nombre_Producto,
    PAI.Lote, P.Laboratorio_Comercial, PAI.Fecha_Vencimiento, PAI.Cantidad, PAI.Observaciones, PAI.Costo
   FROM Producto_Ajuste_Individual PAI 
   INNER JOIN Producto P ON PAI.Id_Producto=P.Id_Producto
   LEFT JOIN Estiba E ON E.Id_Estiba = PAI.Id_Estiba_Acomodada
   WHERE PAI.Id_Ajuste_Individual=$ajuste[Id_Ajuste_Individual]";
      
   $oCon= new consulta();
   $oCon->setQuery($query);
   $oCon->setTipo('Multiple');
   $res = $oCon->getData();
   unset($oCon);
   $total=0;
   foreach ($res as $value) {
      $total += ($value['Costo']  * $value['Cantidad'] );
   }
   $ajustes[$key]['productos'] = $res;
   $ajustes[$key]['Total'] = $total;

}



/* $resultado['encabezado'] = $ajuste;
$resultado['productos'] = $res;
$resultado['Total'] = $total; */

echo json_encode($ajustes); 


?>