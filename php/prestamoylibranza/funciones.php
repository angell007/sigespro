<?php

function proyeccionAmortizacionLibranza($prestamo, $cuota_mensual,$tipo_descuento="Mensual",$primer_pago='') {
    $saldo = $prestamo;
    $total_cuotas = 0;
    $proyeccion = [];
    if($primer_pago==''){
        $primer_pago = date("Y-m-d");
    }
    while ($saldo > 0) {
        // $interes = $prestamo * ($porcentaje_interes/100);
        $cuota = $saldo >= $cuota_mensual ? $cuota_mensual : ($saldo);
        $amortizacion = $saldo < $cuota ? $saldo : ($cuota);
        $saldo = $saldo - $amortizacion;
        $total_cuotas++;
        $fecha = calcularFechaDescuento($primer_pago,$total_cuotas,$tipo_descuento);
        $data = [
            "Cuota" => $total_cuotas,
            "Amortizacion" => number_format($amortizacion,2,".",""),

            // "Intereses" =>  number_format($interes,2,".",""),
            "Valor_Cuota" => number_format($cuota,2,".",""),
            "Saldo" => number_format($saldo,2,".",""),
            "Fecha" => $fecha
        ];
        $proyeccion[] = $data;
    }

    $response['Proyeccion'] = $proyeccion;
    $response['Total_Cuotas'] = $total_cuotas;

    return $response; 
}
function proyeccionAmortizacionL($prestamo, $cuota_mensual,$tipo_descuento="Mensual",$primer_pago='') {
    $saldo = $prestamo;
    $total_cuotas = 0;
    $proyeccion = [];
    if($primer_pago==''){
        $primer_pago = date("Y-m-d");
    }
    while ($saldo > 0) {
        // $interes = $saldo * ($porcentaje_interes/100);
        $cuota = $saldo >= $cuota_mensual ? $cuota_mensual : ($saldo);
        $amortizacion = $saldo < $cuota ? $saldo : ($cuota);
        $saldo = $saldo - $amortizacion;
        $total_cuotas++;
        $fecha = calcularFechaDescuento($primer_pago,$total_cuotas,$tipo_descuento);
        $data = [
            "Cuota" => $total_cuotas,
            "Amortizacion" => number_format($amortizacion,2,".",""),
            // "Intereses" => number_format($interes,2,".",""),
            "Valor_Cuota" => number_format($cuota,2,".",""),
            "Saldo" => number_format($saldo,2,".",""),
            "Fecha" => $fecha
        ];
        $proyeccion[] = $data;
    }

    $response['Proyeccion'] = $proyeccion;
    $response['Total_Cuotas'] = $total_cuotas;

    return $response; 
}

function proyeccionAmortizacionAPrestamo($prestamo, $cuota_mensual, $porcentaje_interes,$tipo_descuento="Mensual",$primer_pago='') {
    $saldo = $prestamo;
    $total_cuotas = 0;
    $proyeccion = [];
    if($primer_pago==''){
        $primer_pago = date("Y-m-d");
    }
    while ($saldo > 0) {
        $interes = $prestamo * ($porcentaje_interes/100);
        $cuota = $saldo >= $cuota_mensual ? $cuota_mensual : ($saldo+$interes);
        $amortizacion = $saldo < $cuota ? $saldo : ($cuota-$interes);
        $saldo = $saldo - $amortizacion;
        $total_cuotas++;
        $fecha = calcularFechaDescuento($primer_pago,$total_cuotas,$tipo_descuento);
        $data = [
            "Cuota" => $total_cuotas,
            "Amortizacion" => number_format($amortizacion,2,".",""),

            "Intereses" =>  number_format($interes,2,".",""),
            "Valor_Cuota" => number_format($cuota,2,".",""),
            "Saldo" => number_format($saldo,2,".",""),
            "Fecha" => $fecha
        ];
        $proyeccion[] = $data;
    }

    $response['Proyeccion'] = $proyeccion;
    $response['Total_Cuotas'] = $total_cuotas;

    return $response; 
}
function proyeccionAmortizacion($prestamo, $cuota_mensual, $porcentaje_interes,$tipo_descuento="Mensual",$primer_pago='') {
    $saldo = $prestamo;
    $total_cuotas = 0;
    $proyeccion = [];
    if($primer_pago==''){
        $primer_pago = date("Y-m-d");
    }
    while ($saldo > 0) {
        $interes = $saldo * ($porcentaje_interes/100);
        $cuota = $saldo >= $cuota_mensual ? $cuota_mensual : ($saldo+$interes);
        $amortizacion = $saldo < $cuota ? $saldo : ($cuota-$interes);
        $saldo = $saldo - $amortizacion;
        $total_cuotas++;
        $fecha = calcularFechaDescuento($primer_pago,$total_cuotas,$tipo_descuento);
        $data = [
            "Cuota" => $total_cuotas,
            "Amortizacion" => number_format($amortizacion,2,".",""),
            "Intereses" => number_format($interes,2,".",""),
            "Valor_Cuota" => number_format($cuota,2,".",""),
            "Saldo" => number_format($saldo,2,".",""),
            "Fecha" => $fecha
        ];
        $proyeccion[] = $data;
    }

    $response['Proyeccion'] = $proyeccion;
    $response['Total_Cuotas'] = $total_cuotas;

    return $response; 
}
function getTotales($data, $tipo) {
    $datos = array_column($data, $tipo);
    $total = array_sum($datos);
    return $total;
}
function calcularFechaDescuento($fecha_descuento, $cuota, $tipo="Mensual") {
    $mes = ($cuota-1);
    if($tipo=="Mensual"){
        $suma="+$mes months";
    }else{
        $num=$mes*15;
        $suma="+$num days";
    }
    if ($cuota == 1) {
        $response = $fecha_descuento;
    } else {        
        $nueva_fecha = strtotime($suma,strtotime($fecha_descuento));
        $response = date('Y-m-d', $nueva_fecha);
    }
    $f = explode("-",$response);
    $nuevo_mes1 = strtotime($f[0]."-".$f[1]);
    if($f[2]>15){
        $response=date('Y-m-', $nuevo_mes1).date("d",(mktime(0,0,0,date("m", $nuevo_mes1)+1,1,date("Y", $nuevo_mes1))-1));
    }else{
        $response=date('Y-m-15', $nuevo_mes1);
    }  

    return $response;
    
}