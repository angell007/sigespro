<?php
ini_set("memory_limit","32000M");
ini_set('max_execution_time', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');

include_once('../class/class.contabilizar.php');

include_once('../class/class.facturacion_electronica.php');

$funcionario = (isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : '12345');
$cliente = (isset($_REQUEST['cliente']) ? $_REQUEST['cliente'] : '860011153');
$centroCosto = (isset($_REQUEST['centroCosto']) ? $_REQUEST['centroCosto'] : '1');
$tipoCliente = (isset($_REQUEST['tipoCliente']) ? $_REQUEST['tipoCliente'] : 'Cliente');

$switch_activos = (isset($_REQUEST['switch_activos']) ? $_REQUEST['switch_activos'] : 'No');

$productos = (isset($_REQUEST['productos']) ? $_REQUEST['productos'] : '');
$productos = json_decode($productos, true);

$oItem = new complex('Cliente', 'Id_Cliente', $cliente);
$cliente_db = $oItem->getData();
unset($oItem);
$fechaPago = date("Y-m-d"); 
$codicionPago = '1';

$facturas = getFacturas();

//$fe1 = new FacturaElectronica("Factura_Administrativa",3, 19); 
//$datos_fac = $fe1->GenerarFactura();

//var_dump($facturas);
//exit;

echo "<table border='1'><tr><td>ID</td><td>DESCRIPCION</td><td>VALOR</td><td>CODIGO FACT</td><td>CONTABILIZAR</td><td>DIAN</td><td>CUFE</td></tr>";
foreach($facturas as $fact){
    echo "<tr><td>".$fact["ID"]."</td><td>".$fact["Concepto"]."</td><td>".$fact["Subtotal"]."</td>";
        $datos = buscarDatosFactura();
        if ($datos) {
            echo "<td>".$datos['Codigo']."</td>";
            $query = 'INSERT INTO Factura_Administrativa 
            ( Activos_Fijos, Id_Cliente, Tipo_Cliente, Id_Resolucion, Codigo, Id_Centro_Costo, Identificacion_Funcionario,Observaciones,
            Estado_Factura, Procesada, Condicion_Pago,Fecha_Pago) 
            VALUES("'. $switch_activos . '",' . $cliente . ',"'.$tipoCliente.'",' . $datos['Id_Resolucion'] . ',"' . $datos['Codigo'] . '",' . $centroCosto . ',' . $funcionario . ',"' . $fact["Observacion"] . '",
             "Pagada","false",' . $codicionPago . ',"' . $fechaPago . '")';
   
            $prods[0]=$fact;
    
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->createData();
            $idFactura = $oCon->getID();
            unset($oCon);
    
    
           guardarDescripcionesGenerales($prods);
           
            
            if($idFactura != ""){
                
                ActualizaLinea($fact["ID"],$datos['Codigo']);
        
                $datos_movimiento_contable['Id_Registro'] = $idFactura; 
                $datos_movimiento_contable['Nit'] = $cliente;
           
                $contabilizar = new Contabilizar();
                $contabilizar->CrearMovimientoContable('Factura Administrativa', $datos_movimiento_contable);
                echo "<td>CONTABILIZADA</td>";
    
    
                if($datos["Tipo_Resolucion"]=="Resolucion_Electronica"){
                   $fe1 = new FacturaElectronica("Factura_Administrativa",$idFactura, $datos["Id_Resolucion"]); 
                   $datos_fac = $fe1->GenerarFactura(); 
                   
                   
                   if($datos_fac["Estado"]=="Exito"){
                       echo "<td>PROCESADA DIAN OK</td><td>".$datos_fac["Datos"]["Cufe"]."</td>";
                   }else{
                       echo "<td>NO PROCESADA DIAN</td><td>".$datos_fac["Detalles"]."</td>";
                   }
                 
                }
              
            }else{
                echo "<td>NO CONTABILIZADA</td><td>NO ENVIADA DIAN</td><td>NO APLICA</td>";
            }   
        }else{
            echo "<td colspan='3'>NO HAY RESOLUICION</td>";
        }
        echo "</tr>";
}
echo "</table>";


#funciones

function guardarDescripcionesGenerales($productos){

    global $idFactura;
    foreach ($productos as $producto) {
        $descripcion = Quitar_Espacios($producto['Concepto']); 
        
        $referencia = $producto['Referencia'];

       $query = 'INSERT INTO Descripcion_Factura_Administrativa 
            (ID_Factura_Administrativa, Id_Plan_Cuenta,Descripcion,Referencia,
            Cantidad, Precio, Descuento, Impuesto,Subtotal) 
        VALUES(' . $idFactura . ',429,"' . $descripcion . '","'.$referencia.'",'
            . $producto['Cantidad'] . ',' . number_format($producto['Valor'],2,".","") . ',' . number_format(0,2,".","") . ',0,' . number_format($producto['Subtotal'],2,".","") . ' )';
        
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->createData();
        unset($oCon);
    }
}

function buscarDatosFactura()
{
    $query = "SELECT * FROM Resolucion WHERE Modulo = 'Administrativo' AND Fecha_Fin > CURDATE() AND Consecutivo <=Numero_Final ORDER BY Fecha_Fin LIMIT 1";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resolucion = $oCon->getData();
    unset($oCon);


    if ($resolucion['Id_Resolucion']) {
        $oItem = new complex('Resolucion', 'Id_Resolucion', $resolucion['Id_Resolucion']);
        $nc = $oItem->getData();

        $oItem->Consecutivo = $oItem->Consecutivo + 1;
        $oItem->save();

        unset($oItem);

        $cod = $nc["Codigo"] . $nc["Consecutivo"];

        $datos['Codigo'] = $cod;
        $datos['Id_Resolucion'] = $resolucion['Id_Resolucion'];
        $datos['Tipo_Resolucion'] = $resolucion['Tipo_Resolucion'];
        return $datos;
    } else {

        return false;
    }
}

function ActualizaLinea($id, $new){
    
    /*$query="UPDATE Factura_Administrativa SET Estado_Factura='Anulada' WHERE Codigo='".$cod."'";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);
    */
    
    $query="UPDATE Masiva_Freddy2 SET Estado=1, Nueva='".$new."' WHERE Id_Masiva_Freddy2=".$id;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);
				
}

function getFacturas(){
    $query = "SELECT  MF.Id_Masiva_Freddy2 AS ID, MF.Valor AS Valor, 1 AS Cantidad, MF.Valor AS Subtotal, CONCAT_WS(' ', 'VALOR DEJADO DE COBRAR FACTURA',MF.Factura,'CORRESPONDIENTE A MEDICAMENTO',MF.Producto, 'USUARIO', P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido,'ID',D.Numero_Documento) AS Concepto, MF.Tipo AS Observacion, P.Id_Paciente AS Referencia


FROM Masiva_Freddy2 MF 
INNER JOIN Factura F ON F.Codigo = MF.Factura
INNER JOIN Dispensacion D ON D.Id_Dispensacion = F.Id_Dispensacion 
INNER JOIN Paciente P ON P.Id_Paciente = D.Numero_Documento;"; 

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $facturas = $oCon->getData();
    unset($oCon);
    
    
    return $facturas;
    
    /*
    SELECT  M.ID, M.Subtotal AS Valor, 1 AS Cantidad, M.Subtotal, CONCAT_WS(' ', 'VALOR DEJADO DE COBRAR FACTURA',MF.Factura,'CORRESPONDIENTE A MEDICAMENTO',MF.Producto, 'USUARIO', P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido,'ID',D.Numero_Documento) AS Concepto, M.Observacion, P.Id_Paciente AS Referencia, M.Factura AS Factura_Administrativa


FROM Masiva_Freddy M
INNER JOIN Masiva_Freddy3 MF ON MF.Id_Masiva_Freddy3=M.Original
INNER JOIN Factura F ON F.Codigo = MF.Factura
INNER JOIN Dispensacion D ON D.Id_Dispensacion = F.Id_Dispensacion 
INNER JOIN Paciente P ON P.Id_Paciente = D.Numero_Documento 

WHERE M.Glosa='Si' AND M.Estado_Dos=0 
LIMIT 0,500;

*/
}

function Quitar_Espacios($cadena)
{
    return preg_replace(['/\s+/','/^\s|\s$/'],[' ',''], $cadena);
}
