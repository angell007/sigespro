<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$EntregaActual = ( isset( $_REQUEST['EntregaActual'] ) ? $_REQUEST['EntregaActual'] : '' );
$idPaciente = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$fechaformula = ( isset( $_REQUEST['fechaformula'] ) ? $_REQUEST['fechaformula'] : '' );
$numeroentrega = ( isset( $_REQUEST['numeroentrega'] ) ? $_REQUEST['numeroentrega'] : '' );

$query = 'SELECT D.*, DATE_FORMAT(D.Fecha_Actual, "%Y-%m-%d") AS Fecha_Dis 
FROM Dispensacion D 
WHERE D.Fecha_Formula ="'.$fechaformula.'"
AND D.Entrega_Actual='.$EntregaActual.'
AND D.Cantidad_Entregas='.$numeroentrega.'
AND D.Numero_Documento='.$idPaciente ;

$oCon= new consulta();
$oCon->setQuery($query);
//$oCon->setTipo('Multiple');
$entregasnumero = $oCon->getData();
unset($oCon); 

if($entregasnumero===false){
    $query2 ='SELECT DF.*, D.Entrega_Actual
    FROM Dispensacion_Fecha_Entrega DF
    INNER JOIN Dispensacion D
    ON D.Id_Dispensacion=DF.Id_Dispensacion
    WHERE 
    DF.Id_Dispensacion=D.Id_Dispensacion
    AND DF.Fecha_Formula="'.$fechaformula.'"
    AND DF.Entrega_Actual='.$EntregaActual.'
    AND DF.Entrega_Total='.$numeroentrega.'
    AND DF.Id_Paciente='.$idPaciente ;
    
    $oCon= new consulta();
    $oCon->setQuery($query2);
    //$oCon->setTipo('Multiple');
    $fechaentregas = $oCon->getData();
    unset($oCon);
    
    $hoy=date("Y-m-d");
    $nuevafecha = strtotime ( '+8 day' , strtotime ( $hoy ) ) ;
     $nuevafecha = date ( 'Y-m-d' , $nuevafecha );
     
    if($hoy>=$fechaentregas["Fecha_Entrega"] && $hoy<=$nuevafecha ){
        
        $resultado["aceptardis"]="Si";
        
    }else if($fechaentregas["Fecha_Entrega"]>$hoy){
        
        $resultado["aceptardis"]="Falta";
       
    }else{
         $resultado["aceptardis"]="Si"; // Modificado para eliminar restricciones hasta enero de fechas de entrega vieja Era No
    }
    
}


$resultado["entregavieja"]=$entregasnumero;
$resultado["entreganueva"]=$fechaentregas;

echo json_encode($resultado);
?>