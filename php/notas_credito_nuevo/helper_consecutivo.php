<?php 

function generarConsecutivo(){

    $query="SELECT * FROM Resolucion WHERE Modulo='NOTA CREDITO' AND Consecutivo <=Numero_Final
             AND Estado = 'Activo' AND Fecha_Fin>=CURDATE() ORDER BY Fecha_Fin ASC LIMIT 1";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resolucion = $oCon->getData();
    unset($oCon);
    
    
    if($resolucion['Id_Resolucion']){
        $oItem = new complex('Resolucion','Id_Resolucion',$resolucion['Id_Resolucion']); // Resolucion 3 para Facturas Ventas NoPos
        $nc = $oItem->getData();
        unset($oItem);
            
        $cod = getConsecutivo($nc);

        return $cod;
    }else{
        return false;
    }

}


function getConsecutivo($resolucion) {
    $cod = $resolucion['Codigo'] != '0' ? $resolucion['Codigo'] . $resolucion['Consecutivo'] : $resolucion['Consecutivo'];
    
    $oItem = new complex('Resolucion','Id_Resolucion',$resolucion['Id_Resolucion']);
    $new_cod = $oItem->Consecutivo + 1;
    $oItem->Consecutivo = number_format($new_cod,0,"","");
    $oItem->save();
    unset($oItem);
    
    return $cod;
}
