<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
include_once('../../../class/class.contabilizar.php');
include_once('../../../class/class.http_response.php');

$contabilizar = new Contabilizar();
$response = array();
$http_response = new HttpResponse();

$funcionario = isset($_REQUEST['id_funcionario']) ? $_REQUEST['id_funcionario'] : false;
$inventarios = isset($_REQUEST['inventarios']) ? $_REQUEST['inventarios'] : false;

$productos = isset($_REQUEST['productos']) ? $_REQUEST['productos'] : false;

$listado_inventario = (array) json_decode($productos, true);

$ids_docs_inventarios;
$ids_estibas;
foreach ($listado_inventario as $res) {
  $i++;
  
  $res["Lote"] = trim($res["Lote"] );

  
  if (!strpos($ids_docs_inventarios, $res['Id_Doc_Inventario_Fisico'])) {
    $ids_docs_inventarios .= ' ' . $res['Id_Doc_Inventario_Fisico'] . ' ,';
  }

  // filtrar los ids de las estibas para cambiarle el estado
  if (!strpos($ids_estibas, $res['Id_Estiba'])) {
    $ids_estibas .= ' ' . $res['Id_Estiba'] . ' ,';
  }

  $query = 'SELECT Id_Inventario_Nuevo FROM Inventario_Nuevo WHERE Id_Producto=' . $res["Id_Producto"] . ' 
     AND Id_Estiba=' . $res['Id_Estiba'] . ' AND Lote="' . $res["Lote"] . '" LIMIT 1';


  $oCon = new consulta();
  $oCon->setQuery($query);
  $inven = $oCon->getData();

  if (isset($res['Cantidad_Auditada']) && $res['Cantidad_Auditada'] !== '') {
    $cantidad = number_format($res["Cantidad_Auditada"], 0, "", "");
     ActualizarProductoDocumento($res['Id_Producto_Doc_Inventario_Fisico'],$cantidad);
  }else{
    $cantidad = number_format($res["Segundo_Conteo"], 0, "", "");
 
  }

  if ($inven) {
    $oItem = new complex('Inventario_Nuevo', 'Id_Inventario_Nuevo', $inven['Id_Inventario_Nuevo']);

    $oItem->Cantidad = $cantidad;
    $oItem->Id_Estiba = $res['Id_Estiba'];
    $oItem->Identificacion_Funcionario = $funcionario;
    $oItem->Id_Punto_Dispensacion = 0;
    $oItem->Cantidad_Apartada = '0';
    $oItem->Cantidad_Seleccionada = '0';
  } else {
    $oItem = new complex('Inventario_Nuevo', 'Id_Inventario_Nuevo');
    $oItem->Cantidad = $cantidad;
    $oItem->Id_Producto = $res["Id_Producto"];
    $oItem->Lote = strtoupper($res["Lote"]);
    $oItem->Fecha_Vencimiento = $res["Fecha_Vencimiento"];
    $oItem->Id_Punto_Dispensacion = 0;
    $oItem->Id_Estiba = $res['Id_Estiba'];
    $oItem->Identificacion_Funcionario = $funcionario;
    $oItem->Cantidad_Apartada = '0';
    $oItem->Cantidad_Seleccionada = '0';
    $oItem->Costo = GetCosto($res["Id_Producto"]);
    $oItem->Codigo_CUM = GetCum($res["Id_Producto"]);
  }


  $oItem->save();
  unset($oItem);
}

#quitarle la última coma (,) de la cadena de texto para que funcione la consulta
$ids_docs_inventarios = substr($ids_docs_inventarios, 0, -1);
$ids_estibas = substr($ids_estibas, 0, -1);

$oItem = new complex('Inventario_Fisico_Nuevo', 'Id_Inventario_Fisico_Nuevo');
$oItem->Funcionario_Autoriza = $funcionario;
$oItem->Id_Bodega_Nuevo = $listado_inventario[0]['Id_Bodega_Nuevo'];
$oItem->Id_Grupo_Estiba = $listado_inventario[0]['Id_Grupo_Estiba'];
$oItem->Fecha = date('Y-m-d');
$oItem->save();
$inventario = $oItem->getId();

unset($oItem);


#cambiar el estado del doc_invetario_fisico
$query2 = 'UPDATE Doc_Inventario_Fisico
 SET Estado ="Terminado", Fecha_Fin="' . date('Y-m-d H:i:s') . '" , Funcionario_Autorizo=' . $funcionario . ',
     Id_Inventario_Fisico_Nuevo=' . $inventario . '
 WHERE  Id_Doc_Inventario_Fisico IN (' . $ids_docs_inventarios . ')';


$oItem = new complex('Inventario_Nuevo', 'Id_Inventario_Nuevo');
$oItem->Cantidad = number_format($res["Segundo_Conteo"], 0, "", "");
$oItem->Id_Producto = $res["Id_Producto"];
$oItem->Lote = strtoupper($res["Lote"]);
$oItem->Fecha_Vencimiento = $res["Fecha_Vencimiento"];
$oItem->Id_Punto_Dispensacion = 0;

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
echo json_encode($resultado);

function GetCosto($id_producto)
{
  $query = 'SELECT IFNULL((SELECT Precio FROM Producto_Acta_Recepcion WHERE Id_Producto=' . $id_producto . ' Order BY Id_Producto_Acta_Recepcion DESC LIMIT 1 ), 0) as Costo  ';

  $oCon = new consulta();
  $oCon->setQuery($query);
  $costo = $oCon->getData();
  unset($oCon);

  return $costo['Costo'];
}

function AsignarIdInventarioFisico($inventarios)
{
  $inv = explode(',', $inventarios);

  return $inv[0];
}

function GetCum($id_producto)
{
  $query = 'SELECT Codigo_Cum FROM Producto WHERE Id_Producto= ' . $id_producto;

  $oCon = new consulta();
  $oCon->setQuery($query);
  $cum = $oCon->getData();
  unset($oCon);

  return $cum['Codigo_Cum'];
}

function ActualizarProductoDocumento($id_producto_doc_inventario,$cantidad){
  global $funcionario;
 // actualizar el documento con la cantidad ingresada por el auditor
 $query = 'UPDATE Producto_Doc_Inventario_Fisico SET Cantidad_Auditada ='.$cantidad .' , Funcionario_Cantidad_Auditada = ' .$funcionario.' 
        WHERE Id_Producto_Doc_Inventario_Fisico = '. $id_producto_doc_inventario;
  $oCon = new consulta();
  $oCon->setQuery($query);
  $oCon->createData();

}
