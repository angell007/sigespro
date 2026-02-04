<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id  = (isset($_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$idc  = (isset($_REQUEST['idc'] ) ? $_REQUEST['idc'] : '' );
$func = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$observacion = ( isset( $_REQUEST['observacion'] ) ? $_REQUEST['observacion'] : '' );

$observacion=utf8_decode($observacion);

$oItem = new complex('Remision','Id_Remision',$id);
$rem=$oItem->getData(); 

if ($rem['Estado'] == 'Facturada' || $rem['Estado'] == 'Enviada'  || $rem['Estado'] == 'Recibida'  ) {
    $response['type'] = 'error'; 
    $response['title'] = 'Operación denegada'; 
    $response['message'] = 'El estado de la remision es: '.$rem['Estado'].' por tal motivo no se puede anular';
    echo json_encode($response);
    exit; 
}

if ($rem['Tipo_Origen'] == 'Bodega' && validarBodegaInventario($rem['Id_Origen'])) {
    $response['type'] = 'error'; 
    $response['title'] = 'Operación denegada'; 
    $response['message'] = 'En este momento la bodega que seleccionó se encuentra realizando un inventario.';
    echo json_encode($response);
    exit; 
}

$oItem->Estado = "Anulada";
$oItem->Observacion_Anulacion=$observacion;
$oItem->save();
unset($oItem);

$query = 'SELECT PR.Id_Inventario, PR.Id_Inventario_Nuevo, PR.Lote, PR.Cantidad, PR.Id_Producto
FROM Producto_Remision PR 
WHERE PR.Id_Remision='.$id;   	  
 
 $oCon= new consulta();
 $oCon->setTipo('Multiple');
 $oCon->setQuery($query);
 $productos = $oCon->getData();
 unset($oCon);


if($rem['Tipo_Origen']=='Bodega' && $rem['Estado_Alistamiento']!=2){
    foreach($productos as $producto){
          if($producto['Id_Inventario']){
            $oItem=new complex('Inventario_Viejo','Id_Inventario',$producto['Id_Inventario']);
            $inv=$oItem->getData(); 
            $apartada=number_format($inv["Cantidad_Apartada"],0,"","");
            $actual = number_format($producto["Cantidad"],0,"","");
            $fin = $apartada - $actual;
            if($fin<0){
                $fin=0;
            }
            $oItem->Cantidad_Apartada=number_format($fin,0,"","");
            $oItem->save();
            $anulado = true;
            unset($oItem);

         }else if($producto['Id_Inventario_Nuevo']){
            $oItem=new complex('Inventario_Nuevo','Id_Inventario_Nuevo',$producto['Id_Inventario_Nuevo']);
            $inv=$oItem->getData(); 
            $apartada=number_format($inv["Cantidad_Apartada"],0,"","");
            $actual = number_format($producto["Cantidad"],0,"","");
            $fin = $apartada - $actual;
            if($fin<0){
                $fin=0;
            }
            $oItem->Cantidad_Apartada=number_format($fin,0,"","");
            $oItem->save();
            $anulado = true;
            unset($oItem);
             
        }else{

            $query = 'SELECT Id_Inventario_Contrato 
                        FROM  inventario_contrato IC
                        INNER JOIN remision R ON IC.Id_Contrato = R.Id_Contrato
                        WHERE IC.Id_Contrato = '.$idc. " AND R.Id_Remision = " .$id;
            $oCon= new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('simple');
            $idinventariocontrato = $oCon->getData();
            unset($oCon);
            
            $oItem=new complex('Inventario_Contrato','Id_Inventario_Contrato',$idinventariocontrato["Id_Inventario_Contrato"]);
            $inv=$oItem->getData(); 
            $apartada=number_format($inv["Cantidad_Apartada"],0,"","");
            $actual = number_format($producto["Cantidad"],0,"","");
            $fin = $apartada - $actual;
            if($fin<0){
                $fin=0;
            }
            $oItem->Cantidad_Apartada=number_format($fin,0,"","");
            $oItem->save();
            $anulado = true;
            unset($oItem); 
        }
        
    }
}elseif ($rem['Tipo_Origen']=='Punto_Dispensacion') {
    foreach($productos as $producto){
        if($producto['Id_Inventario']){
            $oItem=new complex('Inventario_Viejo','Id_Inventario',$producto['Id_Inventario']);
            $inv=$oItem->getData(); 
            $apartada=number_format($inv["Cantidad"],0,"","");
            $actual = number_format($producto["Cantidad"],0,"","");
            $fin = $apartada + $actual;
            $oItem->Cantidad=number_format($fin,0,"","");
            $oItem->save();
            $anulado = true;
            unset($oItem);
        }else if($producto['Id_Inventario_Nuevo']){
             $oItem=new complex('Inventario_Nuevo','Id_Inventario_Nuevo',$producto['Id_Inventario_Nuevo']);
              $inv=$oItem->getData(); 
            $apartada=number_format($inv["Cantidad"],0,"","");
            $actual = number_format($producto["Cantidad"],0,"","");
            $fin = $apartada + $actual;
            $oItem->Cantidad=number_format($fin,0,"","");
            $oItem->save();
            $anulado = true;
            unset($oItem);
            
        } else{

            $query = 'SELECT Id_Inventario_Contrato 
                        FROM  inventario_contrato IC
                        INNER JOIN remision R ON IC.Id_Contrato = R.Id_Contrato
                        WHERE IC.Id_Contrato = '.$idc. " AND R.Id_Remision = " .$id;
            
            $oCon= new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('simple');
            $idinventariocontrato = $oCon->getData();
            unset($oCon);

            $oItem=new complex('Inventario_Contrato','Id_Inventario_Contrato',$idinventariocontrato);
            $inv=$oItem->getData(); 
            $apartada=number_format($inv["Cantidad_Apartada"],0,"","");
            $actual = number_format($producto["Cantidad"],0,"","");
            $fin = $apartada - $actual;
            if($fin<0){
                $fin=0;
            }
            $oItem->Cantidad_Apartada=number_format($fin,0,"","");
            $oItem->save();
            $anulado = true;
            unset($oItem); 
        }   
    }
}elseif ($rem['Tipo_Origen']=='Bodega' && $rem['Estado_Alistamiento']==2) {
    foreach($productos as $producto){
        if($producto['Id_Inventario']){
            $oItem=new complex('Inventario_Viejo','Id_Inventario',$producto['Id_Inventario']);
            $inv=$oItem->getData(); 
            $apartada=number_format($inv["Cantidad"],0,"","");
            $actual = number_format($producto["Cantidad"],0,"","");
            $fin = $apartada + $actual;
            $oItem->Cantidad=number_format($fin,0,"","");
            $oItem->save();
            $anulado = true;
            unset($oItem);
        }else if($producto['Id_Inventario_Nuevo']){
            $oItem=new complex('Inventario_Nuevo','Id_Inventario_Nuevo',$producto['Id_Inventario_Nuevo']);
            $inv=$oItem->getData(); 
            $apartada=number_format($inv["Cantidad"],0,"","");
            $actual = number_format($producto["Cantidad"],0,"","");
            $fin = $apartada + $actual;
            $oItem->Cantidad=number_format($fin,0,"","");
            $oItem->save();
            $anulado = true;
            unset($oItem);
        }else{

            $query = 'SELECT Id_Inventario_Contrato 
                        FROM  inventario_contrato IC
                        INNER JOIN remision R ON IC.Id_Contrato = R.Id_Contrato
                        WHERE IC.Id_Contrato = '.$idc. " AND R.Id_Remision = " .$id;
            
            $oCon= new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('simple');
            $idinventariocontrato = $oCon->getData();
            unset($oCon);

            $oItem=new complex('Inventario_Contrato','Id_Inventario_Contrato',$idinventariocontrato);
            $inv=$oItem->getData(); 
            $apartada=number_format($inv["Cantidad_Apartada"],0,"","");
            $actual = number_format($producto["Cantidad"],0,"","");
            $fin = $apartada - $actual;
            if($fin<0){
                $fin=0;
            }
            $oItem->Cantidad_Apartada=number_format($fin,0,"","");
            $oItem->save();
            $anulado = true;
            unset($oItem); 
        } 
    }
}
 

$oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
$oItem->Id_Remision=$id;
$oItem->Identificacion_Funcionario=$func;
$oItem->Detalles="Anulo la Remision ";
$oItem->Estado="Anulada";
$oItem->save();
unset($oItem);

$response['type'] = 'success'; 
$response['title'] = 'Operación exitosa'; 
$response['message'] = 'Se anuló la remisión correctamente';

echo json_encode($response);


function validarBodegaInventario($id_bodega){

    $query = 'SELECT DOC.Id_Doc_Inventario_Fisico
    FROM Doc_Inventario_Fisico DOC 
    INNER JOIN Estiba E ON E.Id_Estiba =  DOC.Id_Estiba 
    WHERE E.Id_Bodega_Nuevo = '.$id_bodega.' AND DOC.Estado != "Terminado"';
  
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $documentos= $oCon->getData();
    return $documentos;
  }
  


?>