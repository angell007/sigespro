<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$TotalSumar = ( isset( $_REQUEST['TotalSumar'] ) ? $_REQUEST['TotalSumar'] : '' );
$Id_Contrato = ( isset( $_REQUEST['Id_Contrato'] ) ? $_REQUEST['Id_Contrato'] : '' );
$Id_Producto = ( isset( $_REQUEST['Id_Producto'] ) ? $_REQUEST['Id_Producto'] : '' );
$Codigo_CUM = ( isset( $_REQUEST['Codigo_CUM'] ) ? $_REQUEST['Codigo_CUM'] : '' );
$Id_Inventario_Nuevo = ( isset( $_REQUEST['Id_Inventario_Nuevo'] ) ? $_REQUEST['Id_Inventario_Nuevo'] : '' );
$Id_Estiba = ( isset( $_REQUEST['Id_Estiba'] ) ? $_REQUEST['Id_Estiba'] : '' );
$Id_Bodega = ( isset( $_REQUEST['Id_Bodega'] ) ? $_REQUEST['Id_Bodega'] : '' );
$Lote = ( isset( $_REQUEST['Lote'] ) ? $_REQUEST['Lote'] : '' );

$query = ' SELECT SUM(IC.Cantidad - (IC.Cantidad_Apartada+IC.Cantidad_Seleccionada)) as CantidadDisponibleContrato
                FROM Inventario_Contrato IC
                INNER JOIN Producto_Contrato PR  ON IC.Id_Producto_Contrato = PR.Id_Producto_Contrato
                WHERE IC.Id_Contrato = '.$Id_Contrato.' AND PR.Id_Producto = '.$Id_Producto.'
                GROUP BY IC.Id_Contrato';
$oCon= new consulta();
$oCon->setQuery($query);
$CantidadDisponibleContrato = $oCon->getData();
unset($oCon);

$CantidadContrato = $CantidadDisponibleContrato["CantidadDisponibleContrato"];

$query2 = 'SELECT SUM(PR.Cantidad) as CantidadDispensada
                FROM Producto_Remision PR
                INNER JOIN Remision R ON PR.Id_Remision = R.Id_Remision
                WHERE R.Id_Contrato = '.$Id_Contrato.' AND PR.Id_Producto = '.$Id_Producto.'
                GROUP BY R.Id_Contrato';

$oCon= new consulta();
$oCon->setQuery($query2);
$CantidadDispensada = $oCon->getData();
unset($oCon);

$CantidadDispensada = $CantidadDispensada["CantidadDispensada"];

$query='SELECT PC.Cantidad as CantidadDisponible
            FROM Producto_Contrato PC
            INNER JOIN Producto P ON P.Codigo_Cum = PC.Cum
            WHERE PC.Id_Contrato = '.$Id_Contrato.' AND P.Id_Producto = '.$Id_Producto.'';
$oCon= new consulta();
$oCon->setQuery($query);
$InventarioC = $oCon->getData();
unset($oCon);

$CantidadCompromiso = $InventarioC["CantidadDisponible"];

VerificarCantidades($CantidadContrato,$CantidadDispensada, $CantidadCompromiso);

function VerificarCantidades($CantidadContrato,$CantidadDispensada, $CantidadCompromiso){
    global $TotalSumar;

    //disponible traslado maximo 
    $consumida = $CantidadCompromiso - ($CantidadContrato+$CantidadDispensada);

    if($TotalSumar < $consumida){
        $resultado["Tipo"]="success";   
    }else{
        $resultado["Tipo"]="error";                 
    }
    
    echo json_encode($resultado);
  
}

















// echo json_encode($response);

/***************************************************************************/


// $query = 'SELECT IC.Cantidad - (IC.Cantidad_Apartada+IC.Cantidad_Seleccionada) as CantidadDisponibleContrato
//             FROM Inventario_Contrato IC
//             WHERE IC.Id_Contrato = '.$Id_Contrato.'
//             AND IC.Id_Inventario_Nuevo = '.$Id_Inventario_Nuevo.'';
// $query='SELECT PR.Cantidad - (IC.Cantidad + IC.Cantidad_Apartada + Cantidad_Seleccionada) as CantidadPermitida
//             FROM Inventario_Contrato IC
//             INNER JOIN remision R ON IC.Id_Contrato = R.Id_Contrato
//             INNER JOIN producto_remision PR ON R.Id_Remision = PR.Id_Remision
//             WHERE IC.Id_Contrato = '.$Id_Contrato.'
//             AND IC.Id_Inventario_Nuevo = '.$Id_Inventario_Nuevo.' ';
// echo $query;





/***************************************************************************/
        
// $query = 'SELECT INU.Cantidad - (INU.Cantidad_Apartada+INU.Cantidad_Seleccionada) as CantidadDisponible, 
//             FROM inventario_nuevo INU 
//             WHERE INU.Id_Inventario_Nuevo = 5383 
//             AND INU.Id_Estiba = 14  AND INU.Codigo_CUM = "47203-3" 
//             AND Lote = 18042' ;

// function getProductoByCum($cum,$Id_Contrato){
//     $query = 'SELECT *
//                 FROM Producto_Contrato         
//                 WHERE Id_Contrato = "'.$Id_Contrato.'" AND Cum = "'.$cum.'" ';
    
//     $oCon= new consulta();
//     $oCon->setTipo('Simple');
//     $oCon->setQuery($query);
//     $response = $oCon->getData();
//     unset($oCon);
    
//     echo json_encode($response);





// }