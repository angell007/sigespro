<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');
include_once('../../helper/response.php');

$contabilizar = new Contabilizar();

$response = array();

// $http_response = new HttpResponse();

$funcionario = isset($_REQUEST['id_funcionario']) ? $_REQUEST['id_funcionario'] : false;
$inventarios = isset($_REQUEST['inventarios']) ? $_REQUEST['inventarios'] : false;
$productos = isset($_REQUEST['productos']) ? $_REQUEST['productos'] : false;

$listado_inventario = (array) json_decode($productos, true);

$ids_docs_inventarios;

$ids_estibas;


foreach ($listado_inventario as $res) {

  $i++;

    $ids_docs_inventarios = ' ' . $inventarios . ' ,';

  if (!strpos($ids_estibas, $res['Id_Estiba'])) {
    $ids_estibas .= ' ' . $res['Id_Estiba'] . ' ,';
  }
 
  $query = 'SELECT Id_Inventario_Nuevo FROM Inventario_Nuevo WHERE Id_Producto=' . $res["Id_Producto"] . ' 
     AND Id_Estiba=' . $res['Id_Estiba'] . ' AND Lote="' . $res["Lote"] . '" LIMIT 1';

  $oCon = new consulta();
  $oCon->setQuery($query);
  $inven = $oCon->getData();

  if (isset($res['Cantidad_Auditada']) && $res['Cantidad_Auditada'] != '') {
    $cantidad = number_format($res["Cantidad_Auditada"], 0, "", "");
     ActualizarProductoDocumento($res['Id_Producto_Doc_Inventario_Auditable'],$cantidad);
  }else{
    $cantidad = number_format($res["Segundo_Conteo"], 0, "", "");
  }


  if ($inven) {

    $oItem = new complex('Inventario_Nuevo', 'Id_Inventario_Nuevo', $inven['Id_Inventario_Nuevo']);

    $oItem->Cantidad = $cantidad;
    $oItem->Id_Estiba = $res['Id_Estiba'];

    $oItem->Lote = strtoupper($res["Lote"]);
    $oItem->Fecha_Vencimiento = $res["Fecha_Vencimiento"];
    $oItem->Id_Producto = $res["Id_Producto"];

    $oItem->Identificacion_Funcionario = $funcionario;
    $oItem->Id_Punto_Dispensacion = 0;
    $oItem->Cantidad_Apartada = '0';
    $oItem->Cantidad_Seleccionada = '0';

  } else {
    $oItem = new complex('Inventario_Nuevo', 'Id_Inventario_Nuevo');
   $oItem->Cantidad = $cantidad;
    $oItem->Id_Estiba = $res['Id_Estiba'];

    $oItem->Lote = strtoupper($res["Lote"]);
    $oItem->Fecha_Vencimiento = $res["Fecha_Vencimiento"];
    $oItem->Id_Producto = $res["Id_Producto"];
    
    $oItem->Identificacion_Funcionario = $funcionario;
    $oItem->Id_Punto_Dispensacion = 0;
    $oItem->Cantidad_Apartada = '0';
    $oItem->Cantidad_Seleccionada = '0';
  }

  $oItem->save();
  unset($oItem);

}

$ids_docs_inventarios = substr($ids_docs_inventarios, 0, -1);
$ids_estibas = substr($ids_estibas, 0, -1);

$query2 = "UPDATE Doc_Inventario_Auditable SET Estado ='Terminado', Fecha_Fin='" . date('Y-m-d H:i:s') . "' , Funcionario_Autorizo='$funcionario'
 WHERE  Id_Doc_Inventario_Auditable IN ('$ids_docs_inventarios')";

$oCon = new consulta();
$oCon->setQuery($query2);
$oCon->createData();
unset($oCon);

$query2 = 'UPDATE Estiba
 SET Estado = "Disponible"
 WHERE  Id_Estiba IN (' . $ids_estibas . ')';

$oCon = new consulta();
$oCon->setQuery($query2);
$oCon->createData();
unset($oCon);

$resultado['titulo'] = "Registro Exitoso";
$resultado['mensaje'] = "Se ha guardado el inventario exitosamente!";
$resultado['tipo'] = "success";

ActualizarBodegaState($inventarios);
show($resultado);

function ActualizarProductoDocumento($id_producto_doc_inventario,$cantidad){

  global $funcionario;

 $query = 'UPDATE Producto_Doc_Inventario_Auditable SET Cantidad_Auditada ='.$cantidad .' , Funcionario_Cantidad_Auditada = ' .$funcionario.' 
        WHERE Id_Producto_Doc_Inventario_Auditable = '. $id_producto_doc_inventario;
  $oCon = new consulta();
  $oCon->setQuery($query);
  $oCon->createData();
}



function ActualizarBodegaState($idBodega){  

  $query = "UPDATE  Estiba As Es  SET Es.Estado = 'Disponible' 
                   WHERE ES.Id_Bodega_Nuevo  = $idBodega AND  Es.Estado = 'Inventario'";

                   
  $oCon = new consulta();
  $oCon->setQuery($query);
  $oCon->createData();
  
}