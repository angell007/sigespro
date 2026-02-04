<?php

function fecha($fecha) {
    return date('d/m/Y', strtotime($fecha));
}

function numberFormat($num) {
    return number_format($num,2,",","");
}

function calcularDepreciacionMes($mes, $year, $id_activo, $mesReporte, $porcentaje, $costo, $vida_util, $vida_util_acum, $fecha_compra,$depreciacion_acum,$tipo_dep) {
    $valorDepreciacion = '0';
    //$anio = date('Y');
    $anio = $year;
    $mesReporte = (int)$mesReporte;
     $mes = (int)$mes;
    $depreciadoTotal = $vida_util == $vida_util_acum ? true: false;
    $anio_compra = date('Y', strtotime($fecha_compra));
    $anio_mes_compra = date('Y-m', strtotime($fecha_compra));
    $ultimo_mes_anio_anterior = strUltimoMesAnioAnterior();

  //  if( floatval($costo) > floatval($depreciacion_acum)  ){
                 
        if (!$depreciadoTotal || $vida_util == 1) { // Solo se depreciaran los de este año, o los que se compraron en diciembre del año anterior.
  
            $estaDepreciado = activoDepreciado($mesReporte,$anio,$id_activo,$tipo_dep);
     
            if ($vida_util != 1) {
                
                if ($mes <= $mesReporte) { // 3 <= 1 --> NO
             
                    if (($estaDepreciado && ($mesReporte < $mes)) || ($mes == $mesReporte  && floatval($costo) > floatval($depreciacion_acum) )) {
                            
                        $porcentaje = $porcentaje / 100;
                        $valorDepreciacion = $costo * $porcentaje;
                        if( ($valorDepreciacion + $depreciacion_acum ) > $costo){
                            $valorDepreciacion  =   $costo - $depreciacion_acum ;
                        }
                     //   var_dump($porcentaje);
                    //    var_dump($valorDepreciacion);
                        
                    } 
                } elseif (($mesReporte < $mes) && $estaDepreciado) { // 1 < 3
                 
                    $porcentaje = $porcentaje / 100;
                    $valorDepreciacion = $costo * $porcentaje;
                    $valorDepreciacion = $estaDepreciado['Valor_Depreciacion'] ? $estaDepreciado['Valor_Depreciacion'] : 0;
                }
            } else {
               
              
                $mes_compra = date('n', strtotime($fecha_compra));
                $mes_siguiente = $mes_compra == 12 ? 1 : $mes_compra + 1;
        
                if ($mes_siguiente == $mesReporte  &&   $mesReporte <= $mes) {
                    $valorDepreciacion = $costo - $depreciacion_acum ;
                    //316
                    /*if($id_activo == 320){       
                        var_dump('en3');
                                    var_dump($mes);
                    var_dump($mesReporte);
                    //  $mes_compra = date('n', strtotime($fecha_compra));
                        //   var_dump($fecha_compra);
                             var_dump($mes_compra);
                             var_dump($valorDepreciacion);
                    exit;
                    }*/
                }
        
            }
        }
       
    //}
    return $valorDepreciacion;
}

function getDatosTiposActivos($tipo,$mes,$year,$guardar=false) {

    $tipos_activos = getTiposActivos($mes,$year);
   
    foreach ($tipos_activos as $i => $tipo_act) {
        $activos_fijos = activosFijosDepreciar($tipo_act['ID'], $tipo_act['Vida_Util_'.$tipo], $tipo,$mes,$year,$guardar);
   
        if (count($activos_fijos) > 0) {
            $tipos_activos[$i]['activos_fijos'] = $activos_fijos;
        } else {
            unset($tipos_activos[$i]);
        }
    
    }
  
    return $tipos_activos;
}

function strUltimoMesAnioAnterior() {
    $anio_anterior = intval(date('Y')) - 1;

    return $anio_anterior . "-12";
}

?>