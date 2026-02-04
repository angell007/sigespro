<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');




//Se actualiza todo el invenatrio del punto a cantidades en cero 
$queryinventario='UPDATE Inventario SET Cantidad =0, Cantidad_Apartada=0, Cantidad_Seleccionada=0
WHERE Id_Punto_Dispensacion =153';

$oCon= new consulta();
$oCon->setQuery($queryinventario);     
$oCon->createData();     
unset($oCon);



  

$query='SELECT PIFP.*, IFP.Id_Punto_Dispensacion
FROM Producto_Inventario_Fisico_Punto PIFP
INNER JOIN Inventario_Fisico_Punto IFP 
ON IFP.Id_Inventario_Fisico_Punto = PIFP.Id_Inventario_Fisico_Punto
WHERE PIFP.Id_Inventario_Fisico_Punto IN (216,217,218 )';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);
//Se agrega a inventario 
        foreach($resultado as $res){ $i++;
            $query = 'SELECT Id_Inventario,Cantidad FROM Inventario WHERE Id_Producto='.$res["Id_Producto"].' AND Lote="'.$res["Lote"].'" AND Fecha_Vencimiento="'.$res['Fecha_Vencimiento'].'" AND Id_Punto_Dispensacion=153';

            $oCon= new consulta();
            $oCon->setQuery($query);
            $inventario = $oCon->getData();
            unset($oCon);
    
            if ($inventario) {
                $oItem = new complex('Inventario','Id_Inventario', $inventario['Id_Inventario']);
                $cantidad = number_format($res["Cantidad_Final"],0,"","");
                $cantidad_inventario=number_format($inventario['Cantidad'],0,"","");
                $total=$cantidad+$cantidad_inventario;
                $oItem->Cantidad = number_format($total,0,"","");
            } else {
                $query="SELECT Codigo_Cum FROM Producto WHERE Id_Producto=".$res["Id_Producto"];
                $oCon= new consulta();
                $oCon->setQuery($query);
                $producto_cum = $oCon->getData();
                unset($oCon);

                $oItem = new complex('Inventario','Id_Inventario');
                $oItem->Cantidad=number_format($res["Cantidad_Final"],0,"","");
                $oItem->Id_Producto=$res["Id_Producto"];
                $oItem->Lote=$res["Lote"];
                $oItem->Codigo_CUM=$producto_cum["Codigo_Cum"];
                $oItem->Fecha_Vencimiento=$res["Fecha_Vencimiento"];
                $oItem->Id_Punto_Dispensacion=$invenatrio['Id_Punto_Dispensacion'];
                $oItem->Id_Bodega=0;
                $oItem->Identificacion_Funcionario =$invenatrio['Funcionario_Digita'];
                $oItem->Costo=number_format((INT)getCosto($res['Id_Producto']),2,".","");
            }
            $oItem->save();
            unset($oItem);
        }




echo "Termino ---------------------------";


function getCosto($id_producto){
    $query="SELECT Precio FROM Producto_Acta_Recepcion WHERE Id_Producto=".$id_producto.' ORDER BY Id_Producto_Acta_Recepcion DESC LIMIT 1 ';
    $oCon= new consulta();
    $oCon->setQuery($query);
    $costo = $oCon->getData();
    unset($oCon);

    if(!$costo){
        $query="SELECT ROUND(AVG(Costo)) as Precio FROM Inventario WHERE Id_Producto=$id_producto AND Id_Bodega!=0 AND Costo> 0";
        $oCon= new consulta();
        $oCon->setQuery($query);
        $costo = $oCon->getData();
        unset($oCon); 

        if(!$costo){
            $costo['Precio']='0';
        }
    }

    return $costo['Precio'];
}
?>