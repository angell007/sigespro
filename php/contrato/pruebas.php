<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$query = 'SELECT INU.Id_Inventario_Nuevo, 
		 INU.Id_Producto, 
		 INU.Codigo_CUM, 
		 INU.Lote, 
		 INU.Cantidad, 
		 INU.Cantidad_Apartada, 
		 INU.Cantidad_Seleccionada,
		 INU.Cantidad_Pendientes
FROM Inventario_Nuevo INU
WHERE INU.Cantidad_Apartada > 0' ;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos = $oCon->getData();
unset($oCon);

$ProductosActualizar = [];
foreach ($productos as &$p)
{
    $query = 'SELECT SUM(PR.Cantidad) AS c ,GROUP_CONCAT(R.Id_Contrato) as D,GROUP_CONCAT(R.Fecha)as F, GROUP_CONCAT(R.Id_Remision) as R
                FROM Producto_Remision PR
                INNER JOIN Remision R ON PR.Id_Remision = R.Id_Remision
                WHERE PR.Id_Inventario_Nuevo = '.$p["Id_Inventario_Nuevo"].'
                AND 
               
                  
                  (
                #   (R.Estado = "Alistada"  AND Fase_2 = 1)             
                #   OR R.Estado = "Facturada"  
                #   OR R.Estado = "Enviada"
                #   OR R.Estado = "Recibida"
                   
                    (R.Estado <> "Alistada" AND  Fase_2 <> 1)             
                   AND R.Estado <> "Facturada"  
                   AND R.Estado <> "Enviada"
                   AND R.Estado <> "Anulada"
                   AND R.Estado <> "Recibida"
                   )
                
                AND DATE(R.Fecha) >= "2021-06-15"
             
               # AND R.Id_Contrato IS NOT NULL
                
                GROUP BY PR.Id_Inventario_Nuevo,PR.lote';
    $oCon= new consulta();
    //   AND 
    //  (
    //               (R.Estado <> "Alistada" AND  Fase_2 <> 1)             
    //               AND R.Estado <> "Facturada"  
    //               AND R.Estado <> "Enviada"
    //               AND R.Estado <> "Anulada"
    //               AND R.Estado <> "Recibida"
    //$oCon->setTipo('Multiple');
    //and DATE(R.Fecha) BETWEEN "2021-06-15" AND "2018-02-28"
    $oCon->setQuery($query);
    $produc = $oCon->getData();
    $p["CantidadRemision"] = $produc["c"];
    $p["Id_Contrato"] = $produc["D"];
    $p["Fecha"] = $produc["F"];
    $p["Id_Rem"] = $produc["R"];
    unset($oCon);
    
    if((int)$p["Cantidad_Apartada"] != (int)$produc["c"])
    {
       $ProductosActualizar[] = $p;
    }
}

echo json_encode($ProductosActualizar);
exit;

//Actualizar 

    foreach($ProductosActualizar as $p){
       
        $oItem = new complex('Inventario_Nuevo','Id_Inventario_Nuevo', $p["Id_Inventario_Nuevo"]);
        $inv=$oItem->getData();
        $cantidad=number_format($inv["Cantidad"],0,"","");
        $apartada=number_format($inv["Cantidad_Apartada"],0,"","");
         
        $final = $cantidad - $p["CantidadRemision"];
        $fin = $apartada - $p["CantidadRemision"];
        
        if($final<0){$final=0;}
        if($fin<0){$fin=0;}
        
        $oItem->Cantidad=number_format($final,0,"","");
        $oItem->Cantidad_Apartada=number_format($fin,0,"","");
        //   $oItem->Cantidad_Apartada=number_format(0,0,"","");
       
        // $oItem->save();
        $data = $oItem->getData();

       unset($oItem);
        
        echo json_encode($data);
        
    }






