<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');
$dias="2";
$hoy=date("Y-m-01");
$fecha=strtotime ( '+'.$dias.' months' , strtotime ( $hoy) ) ;
$nuevafecha= date('Y-m-t', $fecha);


$query='SELECT I.*, PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, PRD.Laboratorio_Generico,(SELECT B.Nombre FROM Bodega B WHERE B.Id_Bodega=I.Id_Bodega) as Bodega
FROM Inventario I 
INNER JOIN Producto PRD ON I.Id_Producto=PRD.Id_Producto
WHERE I.Fecha_Vencimiento<="'.$nuevafecha.'" AND I.Id_Bodega IN (1,2,7,8,5) AND I.Cantidad>0 AND I.Cantidad_Apartada=0
 ';


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);





$i=0;
echo "<table>
<tr>
<td>Nombre Comercial</td>
<td>Laboratorio Comercial</td>
<td> Laboratorio Generico</td>
<td> Lote</td>
<td>F. Vencimiento</td>
<td> Cantidad</td>
<td> Bodega </td>
<td> Id_Producto </td>
<td> Id_Inventario </td>
</tr>";
foreach($resultado as $res){ $i++;

     
        echo '<tr>
        <td>'.$res['Nombre_Comercial'].'</td>
        <td>'.$res['Laboratorio_Comercial'].'</td>
        <td>'.$res['Laboratorio_Generico'].'</td>
        <td>'.$res['Lote'].'</td>
        <td>'.$res['Fecha_Vencimiento'].'</td>
        <td>'.$res['Cantidad'].'</td>
        <td>'.$res['Bodega'].'</td>
        <td>'.$res['Id_Producto'].'</td>
        <td>'.$res['Id_Inventario'].'</td>
        </tr>';

        $query='SELECT I.*
        FROM Inventario I 
        WHERE I.Lote="'.$res['Lote'].'" AND I.Fecha_Vencimiento="'.$res['Fecha_Vencimiento'].'" AND I.Id_Producto='.$res['Id_Producto'].' AND I.Id_Bodega=6';


        $oCon= new consulta();
        $oCon->setQuery($query);
        $inv = $oCon->getData();
        unset($oCon);

        if($inv['Id_Producto']){
        $cantidad=$inv['Cantidad'];
        $cantidad_selecionada=$res['Cantidad_Seleccionada'];
        $cantidad_apartada=$res['Cantidad_Apartada'];
        $cantidad_entrante=$res['Cantidad'];
        $cantidad_total=$cantidad+$cantidad_entrante;

        $oItem = new complex("Inventario","Id_Inventario", $inv['Id_Inventario']);
        $oItem->Cantidad=number_format($cantidad_total,0,"","");
    // $oItem->save();
        unset($oItem);

        echo "Actualice el lote ".$res['Lote'].' Tenia una Cantidad de '.$cantidad." le sumo ".$cantidad_entrante." Queda un total de ".$cantidad_total."<br>";
        
        $oItem = new complex("Inventario","Id_Inventario", $res['Id_Inventario']);
        $oItem->Cantidad=number_format(0,0,"","");
        $oItem->Cantidad_Seleccionada=number_format(0,0,"","");
        $oItem->Cantidad_Apartada=number_format(0,0,"","");
     //$oItem->save();
        unset($oItem);
        echo $res['Id_Inventario']."<br>";

        }else{

        $oItem = new complex("Inventario","Id_Inventario");
        $oItem->Id_Bodega=6;
        $oItem->Cantidad=number_format($res['Cantidad'],0,"","");
        $oItem->Costo=$res['Costo'];
        $oItem->Id_Producto=$res['Id_Producto'];
        $oItem->Codigo_CUM=$res['Codigo_CUM'];
        $oItem->Fecha_Vencimiento=$res['Fecha_Vencimiento'];
        $oItem->Lote=$res['Lote'];
        $oItem->Id_Punto_Dispensacion=0;
     // $oItem->save();
        unset($oItem);

        $oItem = new complex("Inventario","Id_Inventario", $res['Id_Inventario']);
        echo $res['Id_Inventario']."<br>";
        $oItem->Cantidad=number_format(0,0,"","");
        $oItem->Cantidad_Seleccionada=number_format(0,0,"","");
        $oItem->Cantidad_Apartada=number_format(0,0,"","");
    // $oItem->save();
        unset($oItem);

        echo "Creo el lote ".$res['Lote'].' Con una Cantidad de '.$res['Cantidad']."<br>";
        }
             

}
echo  '</table>';
?>