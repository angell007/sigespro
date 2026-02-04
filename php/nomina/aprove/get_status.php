<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../../class/class.consulta.php');
#/home/sigespro/public_html/php/nomina/aprove/get_status.php/php
$date = ( isset( $_REQUEST['date'] ) ? $_REQUEST['date'] : '' );
if($date){
    
    
    $q = ' SELECT * 
    FROM Aprobacion_Nomina
    WHERE DATE( Periodo)  = "'.$date.'"' ;
    
    $oCon = new consulta();
    $oCon->setQuery($q);
    $aproves = $oCon->getData();
    unset($oCon);
    if( $aproves ){
            $q = ' SELECT * 
            FROM Actividad_Aprobacion_Nomina
            WHERE Id_Aprobacion_Nomina  = '.$aproves['Id_Aprobacion_Nomina'] ;
            
            $oCon = new consulta();
            $oCon->setQuery($q);
            $oCon->setTipo('Multiple');
            $actities = $oCon->getData();
            unset($oCon);
            $aproves['Activities'] = $actities;
            
    }
    
    echo json_encode($aproves);
}else{
    echo 'fecha obligatoria';
}

