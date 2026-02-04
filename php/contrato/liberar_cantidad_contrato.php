<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

include_once('../../class/class.consulta.php');
include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');


$queryObj = new QueryBaseDatos();
$response = array();
$http_response = new HttpResponse();

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$productos = (array) json_decode($datos, true);


foreach ($productos as $product){

    $query = "SELECT Id_Inventario_Contrato 
                FROM Inventario_Contrato
                WHERE Id_Inventario_Contrato= '$product[Id_Inventario_Contrato]'";              
    $oCon = new consulta();
    $oCon->setQuery($query);
    $inventario = $oCon->getData();
    unset($oCon);
 
    if ($inventario) {
          $oItem = new complex('Inventario_Contrato', 'Id_Inventario_Contrato', $inventario['Id_Inventario_Contrato']);
          $cantidad         = number_format($product["CantidadL"], 0, "", "");
          $cantidad_final   = $oItem->Cantidad - $cantidad;
          $oItem->Cantidad  =  $cantidad_final.'';
    }
      $oItem->save();
      unset($oItem);

      $resultado['Titulo'] = "Operación Exitosa";
      $resultado['Mensaje'] = "Cantidades Liberadas Correctamente";
      $resultado['Tipo'] = "success";
}
    echo json_encode($resultado);




