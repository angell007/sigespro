<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');

$contabilizar = new Contabilizar();
$listado_inventario = isset($_REQUEST['listado_inventario']) ? $_REQUEST['listado_inventario'] : false;
$datos = isset($_REQUEST['datos']) ? $_REQUEST['datos'] : false;
$id_funcionario = isset($_REQUEST['id_funcionario']) ? $_REQUEST['id_funcionario'] : false;
 
$listado_inventario = (array) json_decode($listado_inventario, true);
$datos = (array) json_decode($datos, true);

$datos_movimiento_contable = array();

$datos_movimiento_contable['Id_Registro'] = "5";
$datos_movimiento_contable['Nit'] = $id_funcionario;
$datos_movimiento_contable['Productos'] = $listado_inventario;

$contabilizar->CrearMovimientoContable('Inventario Fisico', $datos_movimiento_contable);

echo("Movimientos de inventario fisico guardados");
exit;


// foreach ($listado_inventario as $value) {
//     // Registrar (actualizar) el conteo final en el producto de inventario físico
//     if($value['Id_Producto_Inventario_Fisico']!=0){
//         $id_inventario=explode(",",$value['Id_Producto_Inventario_Fisico']);
//         for ($i=0; $i < count( $id_inventario) ; $i++) { 
//             if($i!=0){
//                 $oItem = new complex('Producto_Inventario_Fisico', 'Id_Producto_Inventario_Fisico', $id_inventario[$i]);
//                 $oItem->delete();
//                 unset($oItem);
//              }else{
//                 $oItem = new complex('Producto_Inventario_Fisico', 'Id_Producto_Inventario_Fisico', $id_inventario[$i]);
//                 $cantidad = number_format((INT)$value['Cantidad_Final'],0,'',''); // parseando
//                 $conteo1 = number_format((INT)$value['Cantidad_Encontrada'],0,'',''); // parseando
//                 $oItem->Segundo_Conteo = $cantidad;
//                 $oItem->Fecha_Segundo_Conteo= date('Y-m-d');
//                 $oItem->save();
//                 unset($oItem);
//              }
//         } 

    
//     }else{
        
//         $oItem = new complex('Producto_Inventario_Fisico', 'Id_Producto_Inventario_Fisico');
//         $cantidad = number_format((INT)$value['Cantidad_Final'],0,'',''); // parseando
//         $oItem->Segundo_Conteo = $cantidad;
//         $oItem->Id_Producto =$value['Id_Producto'];
//         $oItem->Id_Inventario =$value['Id_Inventario'];
//         $oItem->Primer_Conteo ="0";
//         $oItem->Fecha_Primer_Conteo = date('Y-m-d');
//         $oItem->Fecha_Segundo_Conteo = date('Y-m-d');
//         $oItem->Cantidad_Inventario = number_format($value['Cantidad_Inventario'],0,"","");
//         $oItem->Id_Inventario_Fisico = $value['Id_Inventario_Fisico'];
//         $oItem->Lote = $value['Lote'];
//         $oItem->Fecha_Vencimiento = $value['Fecha_Vencimiento'];
//         $oItem->save();
//         unset($oItem);
//     }
    
//     //Actualizar la cantidad con la cantidad final (segundo conteo) en el inventario.
//    /* $oItem = new complex('Inventario', 'Id_Inventario', $value['Id_Inventario']);
//     $oItem->Cantidad = $cantidad;
//     $oItem->save();
//     unset($oItem);*/


// }
// $oItem = new complex('Inventario_Fisico', 'Id_Inventario_Fisico', $datos['Id_Inventario_Fisico']);
// $oItem->Estado = "Terminado";
// $oItem->Fecha_Fin = date('Y-m-d H:i:s');
// $oItem->Funcionario_Autorizo = $datos['Funcionario_Autorizo'];
// $id_inventario = $oItem->Id_Inventario_Fisico;
// $oItem->save();
// unset($oItem);

// $query='SELECT I.* FROM Inventario_Fisico I WHERE I.Id_Inventario_Fisico='.$datos['Id_Inventario_Fisico'];
// $oCon= new consulta();
// $oCon->setQuery($query);
// $inventario= $oCon->getData();
// unset($oCon);

// /*$queryinventario='UPDATE Inventario SET Cantidad =0, Cantidad_Apartada=0, Cantidad_Seleccionada=0
// WHERE Id_Bodega ='.$inventario['Bodega'];

// $oCon= new consulta();
// $oCon->setQuery($queryinventario);     
// $oCon->createData();     
// unset($oCon);*/

// $query2='UPDATE Producto_Inventario_Fisico
// SET Segundo_Conteo = Primer_Conteo
// WHERE Segundo_Conteo IS NULL AND Id_Inventario_Fisico ='.$datos['Id_Inventario_Fisico'];
// $oCon= new consulta();
// $oCon->setQuery($query2);     
// $oCon->createData();     
// unset($oCon);


// $query='SELECT COUNT(Lote) as Conteo, Id_Producto, Lote, SUM(Segundo_Conteo) as Cantidad_Total, SUM(Primer_Conteo) as Cantidad_Inicial, GROUP_CONCAT(Id_Producto_Inventario_Fisico) as Id_Producto_Inventario_Fisico
//    FROM Producto_Inventario_Fisico
//    WHERE Id_Inventario_Fisico ='.$inventario['Id_Inventario_Fisico'].'
//    GROUP BY Lote, Id_Producto
//    HAVING Conteo > 1';
//    $oCon= new consulta();
//    $oCon->setTipo('Multiple');
//    $oCon->setQuery($query);
//    $lotesRepetidos = $oCon->getData();
//    unset($oCon);

//    //Se eliminan los lotes repetidos
//    foreach ($lotesRepetidos as $value) {
//     $id_inventario=explode(",",$value['Id_Producto_Inventario_Fisico']);
//     for ($i=0; $i < count( $id_inventario) ; $i++) { 
//         if($i!=0){
//             $oItem = new complex('Producto_Inventario_Fisico', 'Id_Producto_Inventario_Fisico', $id_inventario[$i]);
//             $oItem->delete();
//             unset($oItem);
//          }else{
//             $oItem = new complex('Producto_Inventario_Fisico', 'Id_Producto_Inventario_Fisico', $id_inventario[$i]);
//             $cantidad = number_format((INT)$value['Cantidad_Total'],0,'',''); // parseando
//             $conteo1 = number_format((INT)$value['Cantidad_Inicial'],0,'',''); // parseando
//             $oItem->Primer_Conteo = $conteo1;
//             $oItem->Segundo_Conteo = $cantidad;
//             $oItem->Fecha_Segundo_Conteo = date('Y-m-d');
//             $oItem->save();
//             unset($oItem);

//          }
//     } 
// }

// $query='SELECT PIF.*, I.Bodega
// FROM Producto_Inventario_Fisico PIF
// INNER JOIN Inventario_Fisico I
// ON I.Id_Inventario_Fisico = PIF.Id_Inventario_Fisico
// WHERE PIF.Id_Inventario_Fisico= '.$datos['Id_Inventario_Fisico'].'
// GROUP BY Id_Producto,Lote  
// ORDER BY `PIF`.`Fecha_Vencimiento`  ASC';

// $oCon= new consulta();
// $oCon->setQuery($query);
// $oCon->setTipo('Multiple');
// $resultado = $oCon->getData();
// unset($oCon);


// foreach($resultado as $res){ $i++;
    

//     if ($res['Id_Inventario']!=0) {
//         $oItem = new complex('Inventario','Id_Inventario', $res['Id_Inventario']);
//         $cantidad = number_format($res["Segundo_Conteo"],0,"","");
//         $oItem->Cantidad = number_format($cantidad,0,"","");
//         $oItem->Id_Bodega=$res['Bodega'];
//         $oItem->Identificacion_Funcionario =$datos['Funcionario_Autorizo'];
//         $oItem->Id_Punto_Dispensacion=0;
//         $oItem->Cantidad_Apartada=0;
//         $oItem->Cantidad_Seleccionada=0;
//     } else {
//         $oItem = new complex('Inventario','Id_Inventario');
//         $oItem->Cantidad=number_format($res["Segundo_Conteo"],0,"","");
//         $oItem->Id_Producto=$res["Id_Producto"];
//         $oItem->Lote=$res["Lote"];
//         $oItem->Fecha_Vencimiento=$res["Fecha_Vencimiento"];
//         $oItem->Id_Punto_Dispensacion=0;
//         $oItem->Id_Bodega=$res['Bodega'];
//         $oItem->Identificacion_Funcionario =$datos['Funcionario_Autorizo'];
//         $oItem->Cantidad_Apartada=0;
//         $oItem->Cantidad_Seleccionada=0;
//     }
//     $oItem->save();
//     unset($oItem);
// }

// if($id_inventario){
//     $resultado1['titulo'] = "Operacion Exitosa";
//     $resultado1['mensaje'] = "Se ha finalizado el inventario correctamente";
//     $resultado1['tipo'] = "success";
// }else{
//     $resultado1['titulo'] = "Error";
//     $resultado1['mensaje'] = "Ha ocurrido un error inesperado.";
//     $resultado1['tipo'] = "error";
// }

// echo json_encode($resultado1);

?>