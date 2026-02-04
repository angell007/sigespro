<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
require_once('../class/class.configuracion.php');
include_once('../class/class.consulta.php');
require_once('../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */
include_once('../class/class.contabilizar.php');

$configuracion = new Configuracion();
$contabilizacion = new Contabilizar(true);

$query = 'SELECT * FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion IN (3,7,24,25,26,28,29,44,46,55,57,59,62,64,65,69,71,74,80,86,89,91,92,93,99,100,110,111,112,113,116,120,121,122,125,127,128,129,130,151,153,163,164,165,166)';
//$query = 'SELECT * FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion IN (123)';
 
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$puntos = $oCon->getData();
unset($oCon);

$final=0;
foreach($puntos as $p){
    
    echo $p["Id_Punto_Dispensacion"]." - ".$p["Nombre"]."<br>";

    $query = 'SELECT * FROM Inventario_Nuevo I WHERE I.Cantidad !=0 AND I.Id_Punto_Dispensacion = '.$p["Id_Punto_Dispensacion"];
    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $productos = $oCon->getData();
    unset($oCon);
    
    if(count($productos)>0){
        $cod = $configuracion->Consecutivo('Ajuste_Individual');

        $oItem = new complex('Ajuste_Individual', 'Id_Ajuste_Individual');
        $oItem->Fecha = "2020-10-31 12:00:00";
        $oItem->Identificacion_Funcionario = 49658182;
        $oItem->Codigo = $cod;
        $oItem->Tipo = "Salida";
        $oItem->Id_Clase_Ajuste_Individual = 1;
        $oItem->Origen_Destino = "Punto";
        $oItem->Id_Origen_Destino = $p['Id_Punto_Dispensacion'];
        $oItem->save();
        $id_ajuste = $oItem->getId();
        unset($oItem);
        
        /* AQUI GENERA QR */
        $qr = generarqr('ajusteindividual',$id_ajuste,'IMAGENES/QR/');
        $oItem = new complex("Ajuste_Individual","Id_Ajuste_Individual",$id_ajuste);
        $oItem->Codigo_Qr=$qr;
        $oItem->save();
        unset($oItem);
        /* HASTA AQUI GENERA QR */
    }
    
    
    $costo = 0;
    foreach($productos as $prod){
        
        $oItem = new complex('Inventario_Nuevo','Id_Inventario_Nuevo', $prod["Id_Inventario_Nuevo"]);
        $oItem->Cantidad=number_format(0,0,"","");
        $oItem->save();
        unset($oItem);
        
        $oItem = new complex('Producto_Ajuste_Individual','Id_Producto_Ajuste_Individual');
        $oItem->Id_Ajuste_Individual = $id_ajuste;
        $oItem->Id_Producto = $prod["Id_Producto"];
        $oItem->Id_Inventario_Nuevo = $prod["Id_Inventario_Nuevo"];
        $oItem->Lote = $prod['Lote'];
        $oItem->Fecha_Vencimiento = $prod['Fecha_Vencimiento'];
        $oItem->Observaciones = "SE AJUSTA POR ORDEN DE SRA MARIELA, ULICES Y FREDDY PORQUE ESAS BODEGAS QUEDARON DUPLICADAS POR CREACION DE NUEVAS (31-OCTUBRE-2020 02:00am AugCar)";
        $oItem->Cantidad = $prod['Cantidad'];
        $oItem->Costo = number_format((FLOAT)$prod['Costo'],0,"","");
        $oItem->save();
        unset($oItem);


        $costo+=($prod["Cantidad"]*$prod["Costo"]);
        echo "---".$prod["Id_Inventario_Nuevo"]." - Cant: ".$prod["Cantidad"]." Costo: $".number_format(($prod["Cantidad"]*$prod["Costo"]),0,",",".")."<br>";
    }
    if(count($productos)>0){
        $datos_movimiento_contable['Id_Registro'] = $id_ajuste;
        $datos_movimiento_contable['Nit'] = 804016084;
        $datos_movimiento_contable['Tipo'] = "Salida";
        $datos_movimiento_contable['Clase_Ajuste'] = 1;
        $datos_movimiento_contable['Productos'] = $productos;
        
        $contabilizacion->CrearMovimientoContable('Ajuste Individual',$datos_movimiento_contable);
    }
    $final+=$costo;
    echo "Costo Total: ".number_format($costo,0,",",".")."<br><br>";
    
}
echo "<br><br>Costo Final: ".number_format($final,0,",",".")."<br><br>";

?>