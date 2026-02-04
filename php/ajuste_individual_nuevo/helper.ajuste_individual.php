<?php
function guardarActividad($id_ajuste, $funcionario, $detalle, $estado){
  $oItem = new complex('Actividad_Ajuste_Individual','Id_Actividad_Ajuste_Individual');
  $oItem->Id_Ajuste_Individual = $id_ajuste;
  $oItem->Identificacion_Funcionario = $funcionario;
  $oItem->Detalle = $detalle;
  $oItem->Estado = $estado;

  $oItem->save();
}

function validarBodegaInventario($id_bodega){

  $query = 'SELECT DOC.Id_Doc_Inventario_Fisico
  FROM Doc_Inventario_Fisico DOC 
  INNER JOIN Estiba E ON E.Id_Estiba =  DOC.Id_Estiba 
  WHERE DOC.Estado != "Terminado" AND E.Id_Bodega_Nuevo = '.$id_bodega;
  $oCon = new consulta();
  $oCon->setQuery($query);
  $oCon->setTipo('Multiple');
  $documentos= $oCon->getData();
  return $documentos;
}

/*function setResponse($type,$title,$text){
    $response['type'] = $type;
    $response['title'] = $title;
    $response['text'] = $text;
    return $response;
}*/