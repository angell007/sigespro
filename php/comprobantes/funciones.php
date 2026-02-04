<?php

function generarConsecutivo($tipo, $mes = null, $anio = null)
{
    $mes = $mes === null ? date('m') : $mes;
    $anio = $anio === null ? date('Y') : $anio;

    if ($tipo == 'Cierre_Anio') {
        $mes = '';
        $query = "SELECT * FROM Comprobante_Consecutivo WHERE Tipo = '$tipo' AND Anio = '$anio'";
    } else {
        $query = "SELECT * FROM Comprobante_Consecutivo WHERE Tipo = '$tipo' AND Mes = '$mes' AND Anio = '$anio'";
    }


    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    $prefijo = $resultado['Prefijo'];
    $consecutivo = $resultado['Consecutivo'];

    $consecutivo =  $prefijo . $anio . $mes . str_pad($consecutivo,3,'0',STR_PAD_LEFT);

    # GENERAR NUEVO CONSECUTIVO PARA EL PROXIMO DOCUMENTO.

    $oItem = new complex('Comprobante_Consecutivo', 'Id_Comprobante_Consecutivo', $resultado['Id_Comprobante_Consecutivo']);
    $nuevo_consecutivo = $oItem->Consecutivo + 1;
    $oItem->Consecutivo = number_format($nuevo_consecutivo,0,"","");
    $oItem->save();
    unset($oItem);

    return $consecutivo;
}

function obtenerProximoConsecutivo($tipo, $mes = null, $anio = null)
{
    $mes = $mes === null ? date('m') : $mes;
    $anio = $anio === null ? date('m') : $anio;

    $query = "SELECT * FROM Comprobante_Consecutivo WHERE Tipo = '$tipo' AND Mes = '$mes' AND Anio = '$anio'";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    $prefijo = $resultado['Prefijo'];
    $consecutivo = $resultado['Consecutivo'];

    $consecutivo =  $prefijo . $anio . $mes . str_pad($consecutivo,3,'0',STR_PAD_LEFT);

    return $consecutivo;
}

function generarConsecutivoCheque($banco, $consec=null)
{
    if($consec){
        $cond=" AND LOCATE(Prefijo, '$consec')>0 ";
    }
    $status = 0;
    $consecutivo  ='';

    $query = "SELECT * FROM(
        (SELECT * FROM Cheque_Consecutivo WHERE Id_Plan_Cuentas = $banco AND Estado = 'Activo' $cond )
            UNION ALL
        (SELECT * FROM Cheque_Consecutivo WHERE Id_Plan_Cuentas = $banco AND Estado = 'Activo' ))a
        LIMIT 1";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);

    if ($resultado) {
        $prefijo = $resultado['Prefijo'];
        $consecutivo = $resultado['Consecutivo'];
    
        if ($consecutivo > $resultado['Final']) { // Si el consecutivo actual es igual al consecutivo final del cheque, no se podrá generar el consecutivo.
            $status = 1;
        } else {
            $status = 2; // estado que me devolverá si el consecutivo aun está activo.
            $consecutivo = $prefijo . str_pad($consecutivo,4,'0',STR_PAD_LEFT);
        }
    
    } else {
        $status = 3; // estado que me devolverá cuando no hay ningun cheque.
    }

    if ($status == 2) { // SI TODO ESTÁ OK
        # GENERAR NUEVO CONSECUTIVO PARA EL PROXIMO CHEQUE.

        $oItem = new complex('Cheque_Consecutivo', 'Id_Cheque_Consecutivo', $resultado['Id_Cheque_Consecutivo']);
        $nuevo_consecutivo = $oItem->Consecutivo + 1;
        $oItem->Consecutivo = number_format($nuevo_consecutivo,0,"","");
        if ($nuevo_consecutivo > $resultado['Final']) { // Si el nuevo consecutivo es mayor al consecutivo final del cheque, lo inactivamos para que no lo vuelvan a tomar.
            $oItem->Estado = 'Inactivo';
        }
        $oItem->save();
        unset($oItem);
    }


    $response = [
        "status" => $status,
        "consecutivo" => $consecutivo
    ];

    return $response;


}

?>