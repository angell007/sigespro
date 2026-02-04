<?php
include_once('class.lista.php');
include_once('class.complex.php');
include_once('class.consulta.php');

class Configuracion
{

    function prefijoConsecutivo($index)
    {

        $oItem = new complex('Configuracion', 'Id_Configuracion', 1);
        $nc = $oItem->getData();
        unset($oItem);

        $prefijo = $nc["Prefijo_" . $index];


        return $prefijo;
    }

    function guardarConsecutivoConfig($index, $consecutivo)
    {

        $oItem = new complex('Configuracion', 'Id_Configuracion', 1);
        $nc = $oItem->getData();
        $oItem->$index = $consecutivo += 1;
        $oItem->save();

        unset($oItem);
    }

    function getConsecutivo($mod, $tipo_consecutivo)
    {
        # $query = "SELECT  MAX(Codigo)  AS Codigo FROM $mod ";
        $prefijo = $this->prefijoConsecutivo($tipo_consecutivo);
        sleep(strval(rand(2, 8)));
        $inicio = strlen($prefijo) + 1;
        $query = "SELECT MAX(N.Codigo) AS Codigo FROM ( SELECT CONVERT(SUBSTRING(Codigo, $inicio,10), DECIMAL) AS Codigo FROM $mod ORDER BY Id_$mod DESC )N ";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $res = $oCon->getData();
        unset($oCon);

        $NumeroCodigo = $res["Codigo"] + 1;

        $cod = $prefijo . $NumeroCodigo;

        $query = "SELECT Id_$mod AS ID FROM $mod WHERE Codigo = '$cod'";
        $oCon = new consulta();
        $oCon->setQuery($query);
        $res2 = $oCon->getData();
        unset($oCon);

        if ($res2["ID"]) {
            sleep(strval(rand(0, 3)));
            $this->getConsecutivo($mod, $tipo_consecutivo);
        }

        $this->guardarConsecutivoConfig($tipo_consecutivo, $NumeroCodigo);

        return $cod;
    }

    function Consecutivo($index)
    {
        $oItem = new complex('Configuracion', 'Id_Configuracion', 1);
        $nc = $oItem->getData();
        $consecutivo = number_format((int) $oItem->$index, 0, "", "");
        $oItem->$index = $consecutivo + 1;
        $oItem->save();
        $num_cotizacion = $nc[$index];
        unset($oItem);

        $cod = $nc["Prefijo_" . $index] . sprintf("%05d", $num_cotizacion);

        return $cod;
    }
}


/* LÓGICA ANTERIOR!!


class Configuracion {
    
   

function getConsecutivo($mod,$tipo_consecutivo) {
    $cod = $this->Consecutivo($tipo_consecutivo);

    sleep(strval( rand(2, 5)));
    $query = "SELECT Id_$mod AS ID FROM $mod WHERE Codigo = '$cod'";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $res = $oCon->getData();
    

    if($res["ID"]){     
        $this->getConsecutivo($mod,$tipo_consecutivo);
    }

    return $cod;
}

} 
*/
