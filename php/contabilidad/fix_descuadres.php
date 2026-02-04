<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = "SELECT MC.Numero_Comprobante, MC.Fecha_Movimiento, MC.Id_Modulo, MC.Id_Registro_Modulo, SUM(MC.Debe) AS Debe, SUM(MC.Haber) AS Haber, SUM(MC.Debe_Niif) AS Debe_Niif, SUM(MC.Haber_Niif) AS Haber_Niif, (SUM(MC.Debe) - SUM(MC.Haber)) AS Diferencia_PCGA, (SUM(MC.Debe_Niif) - SUM(MC.Haber_Niif)) AS Diferencia_NIIF 
FROM Movimiento_Contable  MC 
INNER JOIN Plan_Cuentas PC ON PC.Id_Plan_Cuentas = MC.Id_Plan_Cuenta 
WHERE MC.Estado != 'Anulado' 
AND DATE(MC.Fecha_Movimiento) BETWEEN '2024-01-01' AND '2024-12-31' 
AND MC.Numero_Comprobante LIKE '%PAI%'
GROUP BY MC.Numero_Comprobante 
HAVING (Debe != Haber OR Debe_Niif != Haber_Niif)";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$movimientos = $oCon->getData();
unset($oCon);

$valuesInsert = [];

if ($movimientos) {
    foreach ($movimientos as $i => $value) {
        $debe = 0;
        $haber = 0;
        $haber_niif = 0;
        $debe_niif = 0;
        echo "<br>".($i+1)."<br>";
        if ($value['Diferencia_PCGA'] > 0) {
            $haber = number_format(abs($value['Diferencia_PCGA']),2,".","");
            $debe = 0;
             echo "POSITIVO PCGA- ".$value["Fecha_Movimiento"]." - ".$value["Numero_Comprobante"]." - ".$value["Debe"]. " - ".$value["Haber"]." - ".$value["Debe_Niif"]." - ".$value["Haber_Niif"]." - ".$value["Diferencia_PCGA"]." - ".$value["Diferencia_NIIF"]."<br><br>";
        } elseif ($value['Diferencia_PCGA'] < 0) {
            $debe = number_format(abs($value['Diferencia_PCGA']),2,".","");
            $haber = 0;
            echo "NEGATIVO PCGA - ".$value["Fecha_Movimiento"]." - ".$value["Numero_Comprobante"]." - ".$value["Debe"]. " - ".$value["Haber"]." - ".$value["Debe_Niif"]." - ".$value["Haber_Niif"]." - ".$value["Diferencia_PCGA"]." - ".$value["Diferencia_NIIF"]."<br><br>";
           
        }
        if ($value['Diferencia_NIIF'] > 0) {
            $haber_niif = number_format(abs($value['Diferencia_NIIF']),2,".","");
            $debe_niif = 0;
             echo "POSITIVO NIIF - ".$value["Fecha_Movimiento"]." - ".$value["Numero_Comprobante"]." - ".$value["Debe"]. " - ".$value["Haber"]." - ".$value["Debe_Niif"]." - ".$value["Haber_Niif"]." - ".$value["Diferencia_PCGA"]." - ".$value["Diferencia_NIIF"]."<br><br>";
           
        } elseif ($value['Diferencia_NIIF'] < 0) {
            $debe_niif = (INT)number_format(abs($value['Diferencia_NIIF']),0,".","");
            $haber_niif = 0;
             echo "NEGATIVO NIIF - ".$value["Fecha_Movimiento"]." - ".$value["Numero_Comprobante"]." - ".$value["Debe"]. " - ".$value["Haber"]." - ".$value["Debe_Niif"]." - ".$value["Haber_Niif"]." - ".$value["Diferencia_PCGA"]." - ".$value["Diferencia_NIIF"]."<br><br>";
           
        }
        echo $debe_niif."<br>";
        if(($debe>0&&$debe<=500)||($debe_niif>0 && $debe_niif<=500)){
            echo "entro al debe: ".$debe." y ".$debe_niif."<br>";
            $valuesInsert[] = "(NULL,656,'$value[Fecha_Movimiento]',$value[Id_Modulo],$value[Id_Registro_Modulo],$debe,$haber,$debe_niif,$haber_niif,804016084,'Cliente','Activo','$value[Numero_Comprobante]','AJUSTE AL PESO POR DESCUADRE',NOW(),NULL,'No','$value[Numero_Comprobante]')";
        }
        
        if(($haber>0&&$haber<=500)||($haber_niif>0&&$haber_niif<=500)){
            echo "entro al haber: ".$haber." y ".$haber_niif."<br>";
            $valuesInsert[] = "(NULL,390,'$value[Fecha_Movimiento]',$value[Id_Modulo],$value[Id_Registro_Modulo],$debe,$haber,$debe_niif,$haber_niif,804016084,'Cliente','Activo','$value[Numero_Comprobante]','AJUSTE AL PESO POR DESCUADRE',NOW(),NULL,'No','$value[Numero_Comprobante]')";
        }
      }
    
   /* if(count($valuesInsert)>0){
        
    $queryInsert = "INSERT INTO Movimiento_Contable VALUES " . implode(',',$valuesInsert);

    $oCon = new consulta();
    $oCon->setQuery($queryInsert);
    $oCon->createData();
    unset($oCon);
    
    }*/

    echo "Finalizado exitosamente.";

} else {
    echo "No se encontraron movimientos descuadrados.";
}
          
?>