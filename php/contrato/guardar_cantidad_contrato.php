<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$productos = (array) json_decode($datos, true);

foreach ($productos as $product){
    $query = "SELECT Id_Inventario_Contrato FROM Inventario_Contrato
                WHERE Id_Contrato=$product[Id_Contrato] 
                AND Id_Inventario_Nuevo='$product[Id_Inventario_Nuevo]' ";
                
    $oCon = new consulta();
    $oCon->setQuery($query);
    $inventario = $oCon->getData();
    unset($oCon);
    
    
 
    if ($inventario) {
          $oItem = new complex('Inventario_Contrato', 'Id_Inventario_Contrato', $inventario['Id_Inventario_Contrato']);
          $cantidad         = number_format($product["CantidadA"], 0, "", "");
          $cantidad_final   = $oItem->Cantidad + $cantidad;
          $oItem->Cantidad  = $cantidad_final;

    }else{
         $query = "SELECT PC.Id_Producto_Contrato FROM Producto_Contrato PC
                    INNER JOIN Producto P ON P.Codigo_Cum = PC.Cum
                    WHERE PC.Id_Contrato=$product[Id_Contrato] 
                    AND P.Id_Producto = '$product[Id_Producto]' ";
                
    $oCon = new consulta();
    $oCon->setQuery($query);
    $p = $oCon->getData();
    unset($oCon);
  
    
          $oItem = new complex('Inventario_Contrato', 'Id_Inventario_Contrato');
          $oItem->Id_Contrato           = $product["Id_Contrato"];
          $oItem->Id_Inventario_Nuevo   = $product["Id_Inventario_Nuevo"];
          $oItem->Id_Producto_Contrato  = $p["Id_Producto_Contrato"];
          $oItem->Cantidad              = $product["CantidadA"];         
    }
      $oItem->save();
      unset($oItem);

      $resultado['Titulo'] = "Operación Exitosa";
      $resultado['Mensaje'] = "Cantidades Agregadas Correctamente";
      $resultado['Tipo'] = "success";
}
    echo json_encode($resultado);



