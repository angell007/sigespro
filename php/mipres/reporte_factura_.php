<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$facturas = getFacturas();
//calculate();

$facturas = getData($facturas);
echo json_encode($facturas);

function getFacturas()
{
    $query = 'SELECT
            F.*
            FROM  Factura F
            INNER JOIN Producto_Factura PF ON
                PF.Id_Factura = F.Id_Factura
            INNER JOIN Producto_Dispensacion PD ON
                PD.Id_Producto_Dispensacion = PF.Id_Producto_Dispensacion
            INNER JOIN Producto_Dispensacion_Mipres PDM ON
                PDM.Id_Producto_Dispensacion_Mipres = PD.Id_Producto_Dispensacion_Mipres 
            WHERE 
                    F.Estado_Factura != "Anulada" 
                AND PDM.IdReporteEntrega > 0  and F.Cuota > 0 ORDER BY F.Cuota DESC,  Id_Factura DESC  LIMIT 100';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    return $oCon->getData();
}
function getData($facturas)
{
    foreach ($facturas as $key => &$factura) {
        # code...

        $productos = getProducts($factura['Id_Factura']);
               
        $cuotaTemporal =  number_format($factura['Cuota'], 2, ",", "");
        foreach ($productos as $key => &$producto) {
            # code...
            $data = [];

            if ($cuotaTemporal > 0) {

                $precioProducto =  number_format(($producto["Precio"] * $producto["Cantidad"]) + ($producto["Precio"] * ($producto["Cantidad"] * $producto["Impuesto"] / 100)), 2, ",", "");

                $diff= $cuotaTemporal - $precioProducto;

                if ($diff> 0) {
                    $producto['Cuota_Reporte'] = $precioProducto;
                } else {
                    $producto['Cuota_Reporte'] = $cuotaTemporal;
                }
                $cuotaTemporal -= $precioProducto;
            } else {
                $producto['Cuota_Reporte'] = 0;
            }

            $data['NoPrescripcion'] = $producto['NoPrescripcion'];
            $data['TipoTec'] = $producto['Tipo_Tecnologia'];
            $data['ConTec'] = (int)$producto['ConTec'];
            $data['TipoIDPaciente'] = $producto['Tipo_Documento'];
            $data['NoIDPaciente'] = $producto['Numero_Documento'];
            $data['NoEntrega'] = (int)$producto['Numero_Entrega'];
            //NoSubEntrega
            $data['NoFactura'] = $factura['Codigo'];
            $data['NoIDEPS'] = $producto['NoIDEPS'];
            $data['CodEPS'] = $producto['CodEPS'];

            $data['CodSerTecAEntregado'] = $producto['Cum_Reportado'];
            $data['CantUnMinDis'] = (int) $producto['Cantidad'] * (int) $producto['CantUnMinDis'] ;
            $data['ValorUnitFacturado'] = number_format( $producto["Precio"] + ($producto["Precio"] *    ($producto["Impuesto"] / 100)    ), 2, ",", "");
            $data['ValorTotFacturado'] = $precioProducto;
            $data['CuotaModer'] = $factura['Cuota'];
            $data['Copago'] = ""; 



            
            /*     $respuesta=$mipres->ReporteFacturacion($data);
       
            if($respuesta[0]['Id']){
                
                $oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$dir['Id_Producto_Dispensacion_Mipres']);
                $oItem->IdReporteEntrega=$respuesta[0]['IdReporteEntrega'];
                $oItem->Fecha_Reporte_Entrega=date("Y-m-d H:i:s");
                $oItem->save();
                unset($oItem);
                echo "<td>".$respuesta[0]['IdReporteEntrega']."</td><td>".$valor."</td><td>REPORTE ENTREGA EFECTIVA EXITOSO</td>";
            }else{
                
                echo "<td>0</td><td>".$valor."</td><td>".$respuesta["Errors"][0]."</td>";
            } */


        }
        $factura['Productos']  = $productos;
    }
    return $facturas;
}



function getProducts($id)
{
    $query = '
    SELECT
    PF.*,

    #Producto Dispensacion M
    PDM.NoPrescripcion ,  PDM.Tipo_Tecnologia ,  PDM.ConTec , PDM.Cum_Reportado,
    
    #Dispensacion M
    DM.Numero_Entrega ,  DM.NoIDEPS ,  DM.CodEPS,

    #paciente
    P.Id_Paciente, P.Tipo_Documento,

    #product
    PO.CantUnMinDis

    FROM
    Producto_Factura PF 
        INNER JOIN Producto_Dispensacion PD ON
            PD.Id_Producto_Dispensacion = PF.Id_Producto_Dispensacion
        INNER JOIN Producto_Dispensacion_Mipres PDM ON
            PDM.Id_Producto_Dispensacion_Mipres = PD.Id_Producto_Dispensacion_Mipres 
        INNER JOIN Producto PO ON 
            PO.Id_Producto = PF.Id_Producto
        INNER JOIN Dispensacion_Mipres DM ON
            DM.Id_Dispensacion_Mipres = PDM.Id_Dispensacion_Mipres 
        INNER JOIN Paciente P ON
            P.Id_Paciente = DM.Id_Paciente
        WHERE 
            PF.Id_Factura = ' . $id . '
            AND PDM.IdReporteEntrega > 0
            ';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    return $oCon->getData();
}

