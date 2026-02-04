<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');
include_once('../../class/class.http_response.php');

$contabilizar = new Contabilizar();
$response = array();
$http_response = new HttpResponse(); 

$listado_inventario = isset($_REQUEST['listado_inventario']) ? $_REQUEST['listado_inventario'] : false;
$funcionario = isset($_REQUEST['id_funcionario']) ? $_REQUEST['id_funcionario'] : false;
$inventarios = isset($_REQUEST['inventarios']) ? $_REQUEST['inventarios'] : false;
 
$listado_inventario = (array) json_decode($listado_inventario, true);


foreach ($listado_inventario as $value) {
    // Registrar (actualizar) el conteo final en el producto de inventario físico
    if($value['Id_Producto_Inventario_Fisico']!=0){
        $id_inventario=explode(",",$value['Id_Producto_Inventario_Fisico']);
        for ($i=0; $i < count( $id_inventario) ; $i++) { 
            if($i!=0){
                $oItem = new complex('Producto_Inventario_Fisico', 'Id_Producto_Inventario_Fisico', $id_inventario[$i]);
                $oItem->delete();
                unset($oItem);
             }else{
                $oItem = new complex('Producto_Inventario_Fisico', 'Id_Producto_Inventario_Fisico', $id_inventario[$i]);
                $cantidad = number_format((INT)$value['Cantidad_Final'],0,'',''); // parseando
                $conteo1 = number_format((INT)$value['Cantidad_Encontrada'],0,'',''); // parseando
                $oItem->Segundo_Conteo = $cantidad;
                $oItem->Primer_Conteo = $conteo1;
                $oItem->Cantidad_Inventario =$value['Cantidad_Inventario'];
                $oItem->Fecha_Segundo_Conteo= date('Y-m-d');
                $oItem->save();
                unset($oItem);
             }
        } 
    
    }else{
        
        $oItem = new complex('Producto_Inventario_Fisico', 'Id_Producto_Inventario_Fisico');
        $cantidad = number_format((INT)$value['Cantidad_Final'],0,'',''); // parseando
        $oItem->Segundo_Conteo = $cantidad;
        $oItem->Id_Producto =$value['Id_Producto'];
        $oItem->Id_Inventario =$value['Id_Inventario'];
        $oItem->Primer_Conteo ="0";
        $oItem->Fecha_Primer_Conteo = date('Y-m-d');
        $oItem->Fecha_Segundo_Conteo = date('Y-m-d');
        $oItem->Cantidad_Inventario = number_format($value['Cantidad_Inventario'],0,"","");
        $oItem->Id_Inventario_Fisico = AsignarIdInventarioFisico($inventarios);
        $oItem->Lote = strtoupper($value['Lote']);
        $oItem->Fecha_Vencimiento = $value['Fecha_Vencimiento'];
        $oItem->save();
        unset($oItem);
    }
    
    //Actualizar la cantidad con la cantidad final (segundo conteo) en el inventario.
   /* $oItem = new complex('Inventario', 'Id_Inventario', $value['Id_Inventario']);
    $oItem->Cantidad = $cantidad;
    $oItem->save();
    unset($oItem);*/

}

$query2='UPDATE Inventario_Fisico
SET Estado ="Terminado", Fecha_Fin="'.date('Y-m-d H:i:s').'" , Funcionario_Autorizo='.$funcionario.'
WHERE  Id_Inventario_Fisico IN ('.$inventarios.')';
$oCon= new consulta();
$oCon->setQuery($query2);     
$oCon->createData();     
unset($oCon);



/*$queryinventario='UPDATE Inventario SET Cantidad =0, Cantidad_Apartada=0, Cantidad_Seleccionada=0
WHERE Id_Bodega ='.$inventario['Bodega'];

$oCon= new consulta();
$oCon->setQuery($queryinventario);     
$oCon->createData();     
unset($oCon);*/

$query2='UPDATE Producto_Inventario_Fisico
SET Segundo_Conteo = Primer_Conteo
WHERE Segundo_Conteo IS NULL AND Id_Inventario_Fisico IN ('.$inventarios.')';
$oCon= new consulta();
$oCon->setQuery($query2);     
$oCon->createData();     
unset($oCon);


$query='SELECT COUNT(Lote) as Conteo, Id_Producto, Lote, SUM(Segundo_Conteo) as Cantidad_Total, SUM(Primer_Conteo) as Cantidad_Inicial, GROUP_CONCAT(Id_Producto_Inventario_Fisico) as Id_Producto_Inventario_Fisico
   FROM Producto_Inventario_Fisico
   WHERE Id_Inventario_Fisico IN ('.$inventarios.')
   GROUP BY Id_Producto, Lote
   HAVING Conteo > 1';
   $oCon= new consulta();
   $oCon->setTipo('Multiple');
   $oCon->setQuery($query);
   $lotesRepetidos = $oCon->getData();
   unset($oCon);

   //Se eliminan los lotes repetidos
   foreach ($lotesRepetidos as $value) {
    $id_inventario=explode(",",$value['Id_Producto_Inventario_Fisico']);
    for ($i=0; $i < count( $id_inventario) ; $i++) { 
        if($i!=0){
            $oItem = new complex('Producto_Inventario_Fisico', 'Id_Producto_Inventario_Fisico', $id_inventario[$i]);
            $oItem->delete();
            unset($oItem);
         }else{
            $oItem = new complex('Producto_Inventario_Fisico', 'Id_Producto_Inventario_Fisico', $id_inventario[$i]);
            $cantidad = number_format((INT)$value['Cantidad_Total'],0,'',''); // parseando
            $conteo1 = number_format((INT)$value['Cantidad_Inicial'],0,'',''); // parseando
            $oItem->Primer_Conteo = $conteo1;
            $oItem->Segundo_Conteo = $cantidad;
            $oItem->Fecha_Segundo_Conteo = date('Y-m-d');
            $oItem->save();
            unset($oItem);

         }
    } 
}

$query='SELECT PIF.*, I.Bodega
FROM Producto_Inventario_Fisico PIF
INNER JOIN Inventario_Fisico I
ON I.Id_Inventario_Fisico = PIF.Id_Inventario_Fisico
WHERE PIF.Id_Inventario_Fisico IN ('.$inventarios.')
GROUP BY Id_Producto,Lote  
ORDER BY `PIF`.`Fecha_Vencimiento`  ASC';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);


foreach($resultado as $res){ $i++;
    $query = 'SELECT Id_Inventario FROM Inventario WHERE Id_Producto='.$res["Id_Producto"].' AND Id_Bodega='.$res['Bodega'].' AND Lote="'.$res["Lote"].'" LIMIT 1' ;

    $oCon= new consulta();
    $oCon->setQuery($query);
    $inven = $oCon->getData();
    unset($oCon);

    if ($inven) {
        $oItem = new complex('Inventario','Id_Inventario', $inven['Id_Inventario']);
        $cantidad = number_format($res["Segundo_Conteo"],0,"","");
        $oItem->Cantidad = number_format($cantidad,0,"","");
        $oItem->Id_Bodega=$res['Bodega'];
        $oItem->Identificacion_Funcionario =$funcionario;
        $oItem->Id_Punto_Dispensacion=0;
        $oItem->Cantidad_Apartada='0';
        $oItem->Cantidad_Seleccionada='0';
    } else {
        $oItem = new complex('Inventario','Id_Inventario');
        $oItem->Cantidad=number_format($res["Segundo_Conteo"],0,"","");
        $oItem->Id_Producto=$res["Id_Producto"];
        $oItem->Lote=strtoupper($res["Lote"]);
        $oItem->Fecha_Vencimiento=$res["Fecha_Vencimiento"];
        $oItem->Id_Punto_Dispensacion=0;
        $oItem->Id_Bodega=$res['Bodega'];
        $oItem->Identificacion_Funcionario =$funcionario;
        $oItem->Cantidad_Apartada='0';
        $oItem->Cantidad_Seleccionada='0';
        $oItem->Costo=GetCosto($res["Id_Producto"]);
        $oItem->Codigo_CUM=GetCum($res["Id_Producto"]);
    }
    $oItem->save();
    unset($oItem);
}



$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha guardado el inventario exitosamente!');
$response = $http_response->GetRespuesta();

echo json_encode($response);

function GetCosto($id_producto){
    $query = 'SELECT IFNULL((SELECT Precio FROM Producto_Acta_Recepcion WHERE Id_Producto='.$id_producto.' Order BY Id_Producto_Acta_Recepcion DESC LIMIT 1 ), 0) as Costo  ' ;

    $oCon= new consulta();
    $oCon->setQuery($query);
    $costo = $oCon->getData();
    unset($oCon);

    return $costo['Costo'];
}

function AsignarIdInventarioFisico($inventarios){
    $inv=explode(',',$inventarios);

    return $inv[0];
}

function GetCum($id_producto){
    $query = 'SELECT Codigo_Cum FROM Producto WHERE Id_Producto= '.$id_producto;

    $oCon= new consulta();
    $oCon->setQuery($query);
    $cum = $oCon->getData();
    unset($oCon);

    return $cum['Codigo_Cum'];
}
?>