<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');

$query = "SELECT PIFP.*, IFP.Id_Punto_Dispensacion, IFP.Funcionario_Digita, IFP.Fecha_Fin FROM Producto_Inventario_Fisico_Punto PIFP INNER JOIN Inventario_Fisico_Punto IFP ON PIFP.Id_Inventario_Fisico_Punto = IFP.Id_Inventario_Fisico_Punto WHERE PIFP.Id_Inventario_Fisico_Punto > 62 GROUP BY PIFP.Lote, IFP.Id_Punto_Dispensacion ORDER BY PIFP.Lote ASC";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

/* echo "<pre>";
var_dump($resultado);
echo "</pre>"; */
$i = 0;
foreach ($resultado as $res) {
    
    $query = 'SELECT Id_Inventario,Cantidad FROM Inventario WHERE Id_Producto='.$res["Id_Producto"].' AND Lote="'.$res["Lote"].'" AND Fecha_Vencimiento="'.$res['Fecha_Vencimiento'].'" AND Id_Punto_Dispensacion='.$res['Id_Inventario_Fisico_Punto'].' AND Fecha_Carga<"'.$res["Fecha_Fin"].'" '; 
    
     
    $oCon= new consulta();
    $oCon->setQuery($query);
    $inventario2 = $oCon->getData();
    unset($oCon);

    if (!$inventario2) { 
        
        $query = 'SELECT I.Id_Inventario, I.Fecha_Carga, 
        (SELECT GROUP_CONCAT(AR.Id_Punto_Dispensacion) FROM Acta_Recepcion_Remision AR WHERE AR.Fecha BETWEEN  DATE_ADD(I.Fecha_Carga, INTERVAL "-5.0" SECOND_MICROSECOND) AND DATE_ADD(I.Fecha_Carga, INTERVAL "+5.0" SECOND_MICROSECOND)) as Id_Punto_Dispensacion
        FROM Inventario I 
        
        WHERE I.Id_Producto='.$res["Id_Producto"].' AND I.Lote="'.$res["Lote"].'" AND I.Fecha_Vencimiento="'.$res['Fecha_Vencimiento'].'" AND I.Id_Punto_Dispensacion='.$res['Id_Punto_Dispensacion'].' AND I.Fecha_Carga<"'.$res["Fecha_Fin"].'" ';

       // echo $query."<br>==============================<br>";

        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $inventario1 = $oCon->getData();
        unset($oCon); 

        if($inventario1){ 

            $oItem = new complex('Inventario','Id_Inventario');
            $oItem->Cantidad=number_format($res["Cantidad_Final"],0,"","");
            $oItem->Id_Producto=$res["Id_Producto"];
            $oItem->Lote=$res["Lote"];
            $oItem->Fecha_Vencimiento=$res["Fecha_Vencimiento"];
            $oItem->Id_Punto_Dispensacion=$res['Id_Punto_Dispensacion'];
            $oItem->Id_Bodega=0;
            $oItem->Identificacion_Funcionario =$res['Funcionario_Digita'];
            $oItem->Fecha_Carga =$res['Fecha_Fin'];
            //$oItem->save();
            //$id_inventario = $oItem->getId();
            unset($oItem);

            foreach($inventario1 as $inv){
                $i++;
            $oItem = new complex("Inventario","Id_Inventario",$inv["Id_Inventario"]);
            $oItem->Id_Punto_Dispensacion = (INT)$inv["Id_Punto_Dispensacion"];
            //$oItem->save(); 
            unset($oItem);

            

            /*$query = "UPDATE Producto_Dispensacion PD INNER JOIN Dispensacion D ON PD.Id_Dispensacion = D.Id_Dispensacion SET PD.Id_Inventario = $id_inventario WHERE D.Id_Punto_Dispensacion = $res[Id_Punto_Dispensacion] AND PD.Id_Inventario = $inv[Id_Inventario]";

            $oCon= new consulta();
            $oCon->setQuery($query);
            $oCon->createData();
            unset($oCon);*/

            $query = "SELECT COUNT(*) AS Total FROM Producto_Dispensacion PD INNER JOIN Dispensacion D ON PD.Id_Dispensacion = D.Id_Dispensacion WHERE D.Id_Punto_Dispensacion = $res[Id_Punto_Dispensacion] AND PD.Id_Inventario = $inv[Id_Inventario]";

            $oCon= new consulta();
            $oCon->setQuery($query);
            $total = $oCon->getData();
            unset($oCon);

            echo "($i) | " . $inv["Fecha_Carga"]. " | " . $inv["Id_Inventario"]." | ".$res["Id_Punto_Dispensacion"]." | ".$total["Total"]. " | ".(INT)$res['Id_Inventario_Fisico_Punto']." | ".$inv["Id_Punto_Dispensacion"]."<br>"; 
            }
        }
    }

}

?>