<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');

$query = "SELECT MC.Numero_Comprobante, MC.Fecha_Movimiento, MC.Id_Modulo, MC.Id_Registro_Modulo, SUM(MC.Debe) AS Debe, SUM(MC.Haber) AS Haber, SUM(MC.Debe_Niif) AS Debe_Niif, SUM(MC.Haber_Niif) AS Haber_Niif, (SUM(MC.Debe) - SUM(MC.Haber)) AS Diferencia_PCGA, (SUM(MC.Debe_Niif) - SUM(MC.Haber_Niif)) AS Diferencia_NIIF 
FROM Movimiento_Contable  MC 
INNER JOIN Plan_Cuentas PC ON PC.Id_Plan_Cuentas = MC.Id_Plan_Cuenta 
WHERE MC.Estado != 'Anulado' 
AND DATE(MC.Fecha_Movimiento) BETWEEN '2023-01-01' AND '2023-12-31' 
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
        
        $queryFactura = "SELECT * FROM Nacionalizacion_Parcial N WHERE N.Estado = 'Acomodada' AND Id_Nacionalizacion_Parcial =".$value["Id_Registro_Modulo"];
        $oItem->SetQuery($queryFactura);
        $parcial = $oItem->ExecuteQuery('Simple');
        
        $queryInsert = "DELETE FROM Movimiento_Contable WHERE Id_Registro_Modulo=" . $value["Id_Registro_Modulo"];
        $oCon = new consulta();
        $oCon->setQuery($queryInsert);
        $oCon->deleteData();
        unset($oCon);
        
        
        $productos = getProductosParciales($parcial['Id_Nacionalizacion_Parcial']);
        $gastos = getGastosParcial($parcial['Id_Nacionalizacion_Parcial']);
        $datos_movimiento_contable['Modelo'] = $parcial;
        $datos_movimiento_contable['Productos'] = $productos;
        $datos_movimiento_contable['Otros_Gastos'] = $gastos;
        $datos_movimiento_contable['Porcentaje_Flete_Internacional'] = $productos[0]['Porcentaje_Flete'];
        $datos_movimiento_contable['Porcentaje_Seguro_Internacional'] = $productos[0]['Porcentaje_Seguro'];
        $datos_movimiento_contable['Tasa_Dolar_Parcial'] = $parcial['Tasa_Cambio'];
        $datos_movimiento_contable['Id_Registro'] = $parcial['Id_Nacionalizacion_Parcial'];
        $oItem = new QueryBaseDatos();
        $contabilizacion = new Contabilizar();
        $contabilizacion->CrearMovimientoContable('Parcial Acta Internacional', $datos_movimiento_contable);
        unset($oItem);
        

      }

    echo "Finalizado exitosamente.";

} else {
    echo "No se encontraron movimientos descuadrados.";
}
          
?>