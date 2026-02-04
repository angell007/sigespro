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
$tipo = isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : false;
$id_funcionario = isset($_REQUEST['id_funcionario']) ? $_REQUEST['id_funcionario'] : '';

$listado_inventario = (array) json_decode($listado_inventario, true);
$datos = (array) json_decode($datos, true);

$datos_movimiento_contable = array();

$datos_movimiento_contable['Id_Registro'] = "6";
$datos_movimiento_contable['Nit'] = $id_funcionario;
$datos_movimiento_contable['Productos'] = $listado_inventario;

$contabilizar->CrearMovimientoContable('Inventario Fisico Punto', $datos_movimiento_contable);

echo("Movimientos de inventario fisico guardados");
exit;


// foreach ($listado_inventario as $value) {
//     if($value['Id_Producto_Inventario_Fisico']=='0'){
//         $oItem = new complex('Producto_Inventario_Fisico_Punto', 'Id_Producto_Inventario_Fisico');
//         $cantidad = number_format((INT)$value['Cantidad_Final'],0,'',''); // parseando
//         $conteo1 = number_format((INT)$value['Cantidad_Encontrada'],0,'',''); // parseando
//         $conteo2 = number_format((INT)$value['Segundo_Conteo'],0,'',''); // parseando
//         $oItem->Cantidad_Final = $cantidad;
//         $oItem->Primer_Conteo = $conteo1;
//         $oItem->Segundo_Conteo = $conteo2;
//         $oItem->Cantidad_Inventario = number_format($value['Segundo_Conteo'],0,"","");
//         $oItem->Fecha_Segundo_Conteo = date('Y-m-d');
//         $oItem->Fecha_Primer_Conteo = date('Y-m-d');
//         $oItem->Id_Producto = $value['Id_Producto'];
//         $oItem->Fecha_Vencimiento = $value['Fecha_Vencimiento'];
//         $oItem->Lote = $value['Lote'];
//         $oItem->Id_Inventario = $value['Id_Inventario'];
//         $oItem->Id_Inventario_Fisico_Punto = $datos['Id_Inventario_Fisico'];
//         $oItem->save();
//         unset($oItem);

//     }else{
//         $id_inventario=explode(",",$value['Id_Producto_Inventario_Fisico']);
//         // Registrar (actualizar) el conteo final en el producto de inventario físico
        
//             for ($i=0; $i < count( $id_inventario) ; $i++) { 
//                 if($i!=0){
//                     $oItem = new complex('Producto_Inventario_Fisico_Punto', 'Id_Producto_Inventario_Fisico', $id_inventario[$i]);
//                     $oItem->delete();
//                     unset($oItem);
//                  }else{
//                     $oItem = new complex('Producto_Inventario_Fisico_Punto', 'Id_Producto_Inventario_Fisico', $id_inventario[$i]);
//                     $cantidad = number_format((INT)$value['Cantidad_Final'],0,'',''); // parseando
//                     $conteo1 = number_format((INT)$value['Cantidad_Encontrada'],0,'',''); // parseando
//                     $conteo2 = number_format((INT)$value['Segundo_Conteo'],0,'',''); // parseando
//                     $oItem->Cantidad_Final = $cantidad;
//                     $oItem->Primer_Conteo = $conteo1;
//                     $oItem->Segundo_Conteo = $conteo2;
//                     $oItem->Fecha_Segundo_Conteo = date('Y-m-d');
//                     $oItem->save();
//                     unset($oItem);
//                  }
//             }
//     }      
// }
    
// //Actualizar la cantidad con la cantidad final (segundo conteo) en el inventario.
//    /* $oItem = new complex('Inventario', 'Id_Inventario', $value['Id_Inventario']);
//     $oItem->Cantidad = $cantidad;
//     $oItem->save();
//     unset($oItem);*/


// $query='SELECT I.*
// FROM Inventario_Fisico_Punto I 
// WHERE I.Id_Inventario_Fisico_Punto='.$datos['Id_Inventario_Fisico'];

// $oCon= new consulta();
// //$oCon->setTipo('Multiple');
// $oCon->setQuery($query);
// $invenatrio = $oCon->getData();
// unset($oCon);

// $query='SELECT I.*
// FROM Inventario_Fisico_Punto I 
// WHERE I.Id_Punto_Dispensacion='.$invenatrio['Id_Punto_Dispensacion'].' AND I.Fecha_Inicio LIKE "%'.date("Y-m-d",strtotime($invenatrio["Fecha_Inicio"])).'%"';

// $oCon= new consulta();
// $oCon->setTipo('Multiple');
// $oCon->setQuery($query);
// $inventarios= $oCon->getData();
// unset($oCon); 
// $inicio = date("Y-m-d H:i:s");

// if( count($inventarios)>1){
//     foreach ($inventarios as $item) {
//         $oItem = new complex('Inventario_Fisico_Punto','Id_Inventario_Fisico_Punto',$item['Id_Inventario_Fisico_Punto']);
//         $oItem->Estado='Terminado';
//         $oItem->Fecha_Fin=$inicio;
//         $oItem->save();
//         unset($oItem);
//     }
// }else {
//     $oItem = new complex('Inventario_Fisico_Punto','Id_Inventario_Fisico_Punto',$invenatrio['Id_Inventario_Fisico_Punto']);
//     $oItem->Estado='Terminado';
//     $oItem->Fecha_Fin=$inicio;
//     $oItem->save();
//     unset($oItem);
// }

// //Se actualiza todo el invenatrio del punto a cantidades en cero 
// $queryinventario='UPDATE Inventario SET Cantidad =0, Cantidad_Apartada=0, Cantidad_Seleccionada=0
// WHERE Id_Punto_Dispensacion ='.$invenatrio['Id_Punto_Dispensacion'];

// $oCon= new consulta();
// $oCon->setQuery($queryinventario);     
// $oCon->createData();     
// unset($oCon);


// foreach ($inventarios as $item) {
//    $query='SELECT COUNT(Lote) as Conteo, Id_Producto, Lote, SUM(Segundo_Conteo) as Cantidad_Total, SUM(Primer_Conteo) as Cantidad_Inicial, GROUP_CONCAT(Id_Producto_Inventario_Fisico) as Id_Producto_Inventario_Fisico
//    FROM Producto_Inventario_Fisico_Punto
//    WHERE Id_Inventario_Fisico_Punto ='.$item['Id_Inventario_Fisico_Punto'].'
//    GROUP BY Lote, Id_Producto
//    HAVING Conteo > 1';

//     $oCon= new consulta();
//     $oCon->setTipo('Multiple');
//     $oCon->setQuery($query);
//     $lotesRepetidos = $oCon->getData();
//     unset($oCon);
// //Se eliminan los lotes repetidos
//     foreach ($lotesRepetidos as $value) {
//         $id_inventario=explode(",",$value['Id_Producto_Inventario_Fisico']);
//         for ($i=0; $i < count( $id_inventario) ; $i++) { 
//             if($i!=0){
//                 $oItem = new complex('Producto_Inventario_Fisico_Punto', 'Id_Producto_Inventario_Fisico', $id_inventario[$i]);
//                 $oItem->delete();
//                 unset($oItem);
//              }else{
//                 $oItem = new complex('Producto_Inventario_Fisico_Punto', 'Id_Producto_Inventario_Fisico', $id_inventario[$i]);
//                 $cantidad = number_format((INT)$value['Cantidad_Total'],0,'',''); // parseando
//                 $conteo1 = number_format((INT)$value['Cantidad_Inicial'],0,'',''); // parseando
//                 $conteo2 = number_format((INT)$value['Cantidad_Total'],0,'',''); // parseando
//                 $oItem->Cantidad_Final = $cantidad;
//                 $oItem->Primer_Conteo = $conteo1;
//                 $oItem->Segundo_Conteo = $conteo2;
//                 $oItem->Fecha_Segundo_Conteo = date('Y-m-d');
//                 $oItem->save();
//                 unset($oItem);
//              }
//         } 
//     }
// //se actualizan las cantidades finales con la cantidad del segundo conteo 
// if($tipo=="No"){
//     $query2='UPDATE Producto_Inventario_Fisico_Punto 
//     SET Cantidad_Final = Segundo_Conteo
//     WHERE Cantidad_Final IS NULL AND Id_Inventario_Fisico_Punto ='.$item['Id_Inventario_Fisico_Punto'];
//     $oCon= new consulta();
//     $oCon->setQuery($query2);     
//     $oCon->createData();     
//     unset($oCon);

// }elseif ($tipo=="Si") {
//     $query2='UPDATE Producto_Inventario_Fisico_Punto 
//     SET Cantidad_Final = Primer_Conteo
//     WHERE Cantidad_Final IS NULL AND Id_Inventario_Fisico_Punto ='.$item['Id_Inventario_Fisico_Punto'];
//     $oCon= new consulta();
//     $oCon->setQuery($query2);     
//     $oCon->createData();     
//     unset($oCon);
// }
  

//     $query='SELECT PIFP.*, IFP.Id_Punto_Dispensacion
//     FROM Producto_Inventario_Fisico_Punto PIFP
//     INNER JOIN Inventario_Fisico_Punto IFP 
//     ON IFP.Id_Inventario_Fisico_Punto = PIFP.Id_Inventario_Fisico_Punto
//     WHERE PIFP.Id_Inventario_Fisico_Punto = '.$item['Id_Inventario_Fisico_Punto'].'
//     GROUP BY Id_Producto,Lote  
//     ORDER BY `PIFP`.`Fecha_Vencimiento`  ASC';

//     $oCon= new consulta();
//     $oCon->setQuery($query);
//     $oCon->setTipo('Multiple');
//     $resultado = $oCon->getData();
//     unset($oCon);
// //Se agrega a inventario 
//         foreach($resultado as $res){ $i++;
//             $query = 'SELECT Id_Inventario,Cantidad FROM Inventario WHERE Id_Producto='.$res["Id_Producto"].' AND Lote="'.$res["Lote"].'" AND Fecha_Vencimiento="'.$res['Fecha_Vencimiento'].'" AND Id_Punto_Dispensacion='.$item['Id_Punto_Dispensacion'];

//             $oCon= new consulta();
//             $oCon->setQuery($query);
//             $inventario = $oCon->getData();
//             unset($oCon);
    
//             if ($inventario) {
//                 $oItem = new complex('Inventario','Id_Inventario', $inventario['Id_Inventario']);
//                 $cantidad = number_format($res["Cantidad_Final"],0,"","");
//                 $cantidad_inventario=number_format($inventario['Cantidad'],0,"","");
//                 $total=$cantidad+$cantidad_inventario;
//                 $oItem->Cantidad = number_format($total,0,"","");
//             } else {
//                 $oItem = new complex('Inventario','Id_Inventario');
//                 $oItem->Cantidad=number_format($res["Cantidad_Final"],0,"","");
//                 $oItem->Id_Producto=$res["Id_Producto"];
//                 $oItem->Lote=$res["Lote"];
//                 $oItem->Fecha_Vencimiento=$res["Fecha_Vencimiento"];
//                 $oItem->Id_Punto_Dispensacion=$invenatrio['Id_Punto_Dispensacion'];
//                 $oItem->Id_Bodega=0;
//                 $oItem->Identificacion_Funcionario =$invenatrio['Funcionario_Digita'];
//             }
//             $oItem->save();
//             unset($oItem);
//         }

// }

// if($id_inventario){
//     $resultado['titulo'] = "Operacion Exitosa";
//     $resultado['mensaje'] = "Se ha finalizado el inventario correctamente";
//     $resultado['tipo'] = "success";
// }else{
//     $resultado['titulo'] = "Error";
//     $resultado['mensaje'] = "Ha ocurrido un error inesperado.";
//     $resultado['tipo'] = "error";
// }

// echo json_encode($resultado);

?>