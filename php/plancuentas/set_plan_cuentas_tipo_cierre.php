<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../class/class.complex.php');

    $id_plan_cuenta = isset($_REQUEST['id_plan_cuenta']) ? $_REQUEST['id_plan_cuenta'] : false;

    # MENSUAL O ANUAL
    $tipo_cierre = isset($_REQUEST['tipo_cierre']) ? $_REQUEST['tipo_cierre'] : false;

    #Costos - Gastos - Ingresos - Sin Asignar
    $valor_actualizar = isset($_REQUEST['valor_actualizar']) &&  $_REQUEST['valor_actualizar'] != '' ? $_REQUEST['valor_actualizar'] : 'Sin Asignar';

    $input  = 'Tipo_Cierre_'.$tipo_cierre;
    $oItem = new complex('Plan_Cuentas','Id_Plan_Cuentas',$id_plan_cuenta);
    $oItem->$input = $valor_actualizar;
    $oItem->save();
    
