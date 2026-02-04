<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');


$query = "SELECT
D.Id_Dispensacion,D.Codigo, D.Fecha_Actual, D.Id_Tipo_Servicio, DM.Tipo_Tecnologia, DM.Id_Dispensacion_Mipres, D.Estado_Dispensacion,
(SELECT COUNT(*) FROM Producto_Dispensacion PD WHERE PD.Id_Dispensacion=D.Id_Dispensacion) as Conteo,
(SELECT COUNT(*) FROM Producto_Dispensacion_Mipres PDM WHERE PDM.Id_Dispensacion_Mipres=DM.Id_Dispensacion_Mipres) as Conteo_Mipres
FROM Dispensacion D
INNER JOIN Dispensacion_Mipres DM ON DM.Id_Dispensacion_Mipres=D.Id_Dispensacion_Mipres
WHERE D.Estado_Dispensacion != 'Anulada' 
HAVING Conteo = 0  
ORDER BY DM.Tipo_Tecnologia ASC";

// WHERE D.Estado_Dispensacion != 'Anulada' AND DM.Tipo_Tecnologia = 'M'

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$dispensaciones = $oCon->getData();
unset($oCon);

?>
<table>
    <tr>
        <td>Codigo</td>
        <td>Fecha Actual</td>
        <td>ID Tipo Servicio</td>
        <td>Tipo Tecnologia</td>
        <td>Estado Dispensacion</td>
    </tr>

<?php 
$i=0;
foreach($dispensaciones as $dis){ $i++;
    $oLista = new Lista("Producto_Dispensacion_Mipres");
    $oLista->setRestrict("Id_Dispensacion_Mipres","=",$dis["Id_Dispensacion_Mipres"]);
    $productos = $oLista->getList();
    unset($oLista);
    
    foreach($productos as $prod){
        $oItem = new complex("Producto_Dispensacion","Id_Producto_Dispensacion");
        $oItem->Id_Dispensacion = $dis["Id_Dispensacion"]; 
        $oItem->Id_Producto = $prod["Id_Producto"];
        $oItem->Lote = "Pendiente";
        $oItem->Cum = $prod["Codigo_Cum"];
        $oItem->Cantidad_Formulada = $prod["Cantidad"];
        $oItem->Cantidad_Entregada = 0;
        $oItem->Numero_Prescripcion = $prod["NoPrescripcion"];
        $oItem->Id_Producto_Mipres = $prod["Id_Producto_Dispensacion_Mipres"];
        $oItem->save();
        unset($oItem);
    }
    
?>
<tr>
    <td><?php echo $i; ?></td>
    <td><?php echo $dis["Codigo"]; ?></td>
    <td><?php echo $dis["Fecha_Actual"]; ?></td>
    <td><?php echo $dis["Id_Tipo_Servicio"]; ?></td>
    <td><?php echo $dis["Tipo_Tecnologia"]; ?></td>
    <td><?php echo $dis["Estado_Dispensacion"]; ?></td>
</tr>
<?php
}
?>
</table>