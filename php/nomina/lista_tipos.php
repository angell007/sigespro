<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');
    
    require_once('../../config/start.inc.php');
    include_once('../../class/class.lista.php');
    include_once('../../class/class.complex.php');
    include_once('../../class/class.consulta.php');
    
    $oLista = new lista("Tipo_Ingreso");
    $oLista->setRestrict("Tipo","=","Prestacional");
    $ingresoSalarial=$oLista->getList();
    unset($oLista);
    
    $oLista = new lista("Tipo_Ingreso");
    $oLista->setRestrict("Tipo","=","No_Prestacional");
    $ingresoNoSalarial=$oLista->getList();
    unset($oLista);
    
    $oLista = new lista("Tipo_Egreso");
    $egresos=$oLista->getList();
    unset($oLista);
    
    $resultado["IngresosSalarial"]=$ingresoSalarial;
    $resultado["IngresosNoSalarial"]=$ingresoNoSalarial;
    $resultado["Egresos"]= $egresos;
    
    echo json_encode($resultado);
?>