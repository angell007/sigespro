<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.mipres.php');
$mipres = new Mipres();

$facturas = getFacturas();
//calculate();

echo "<table border='1' cellspacing='1' cellpadding='1'><tr><td>#</td><td>#Factura</td><td>Prescripcion</td><td>ID</td><td>Id Programacion</td><td>Id Entrega</td><td>Id Reporte Entrega</td><td>Id Factura</td><td>Estado</td></tr>";
$facturas = getData($facturas);

echo "</table>";

//echo json_encode($facturas);
function getFacturas()
{
    $query = '
           SELECT
            F.*
            FROM  Factura F
            WHERE 
                F.Estado_Factura != "Anulada" 
                AND F.Nota_Credito IS NULL
                AND F.Tipo = "Factura"
                AND F.Id_Cliente = 900226715 
                AND 
                EXISTS(
                	SELECT PF.Id_Producto_Factura FROM Producto_Factura PF 
                    INNER JOIN Producto_Dispensacion PD ON
                    	PD.Id_Producto_Dispensacion = PF.Id_Producto_Dispensacion
                    INNER JOIN Producto_Dispensacion_Mipres PDM ON
                    	PDM.Id_Producto_Dispensacion_Mipres = PD.Id_Producto_Dispensacion_Mipres 
                    WHERE PF.Id_Factura = F.Id_Factura
                        AND PDM.IdReporteEntrega > 0 
                        #AND PDM.IdFactura IS NULL
                        AND PDM.ConTec > 0 
                          ' . getContSpecial() . '
                )                            
                GROUP BY F.Id_Factura 
                #ORDER BY F.Id_Factura DESC
                ';
    
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    return $oCon->getData();
}
function getData($facturas)
{
    global $mipres;

    foreach ($facturas as $key => &$factura) {
        # code...

        $productos = getProducts($factura['Id_Factura']);
        $cuotaTemporal =  number_format($factura['Cuota'], 2, ",", "");
        foreach ($productos as $key => &$producto) {
            # code...
            $data = [];
            echo '<pre>';

            $precioProducto =  number_format(($producto["Precio"] * $producto["Cantidad"]) + ($producto["Precio"] * ($producto["Cantidad"] * $producto["Impuesto"] / 100)), 2, ",", "");
            if ($cuotaTemporal > 0) {

                $diff = $cuotaTemporal - $precioProducto;

                if ($diff > 0) {
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
            $data['NoIDPaciente'] = $producto['Id_Paciente'];
            $data['NoEntrega'] = (int)$producto['Numero_Entrega'];
            //NoSubEntrega
            $data['NoFactura'] = $factura['Cufe'];
            $data['NoIDEPS'] = $producto['NoIDEPS'];
            $data['CodEPS'] = $producto['CodEPS'];

            $data['CodSerTecAEntregado'] = $producto['CodSerTecAEntregar'];
            $data['CantUnMinDis'] = (int)$producto['Cantidad'] * (int) $producto['CantUnMinDis'];
            $data['ValorUnitFacturado'] = number_format($producto["Precio"] + ($producto["Precio"] *    ($producto["Impuesto"] / 100)), 2, ",", "");
            $data['ValorTotFacturado'] = $precioProducto;
            $data['CuotaModer'] = $factura['Cuota'];
            $data['Copago'] = "0";

            $respuesta = [];
            $respuesta = $mipres->ReporteFacturacion($data);
            echo "<tr><td>$producto[Id_Producto_Dispensacion_Mipres]</td><td>$producto[Id_Factura]</td><td>" . $producto["NoPrescripcion"] . "</td><td>" . $producto["ID"] . "</td><td>" . $producto["IdProgramacion"] . "</td><td>" . $producto["IdEntrega"] . "</td><td>" . $producto["IdReporteEntrega"] . "</td>";
            

            if ($respuesta[0]['IdFacturacion']) {
                $oItem = new complex('Producto_Dispensacion_Mipres', 'Id_Producto_Dispensacion_Mipres', $producto['Id_Producto_Dispensacion_Mipres']);
                $oItem->IdFactura = $respuesta[0]['IdFacturacion'];
                $oItem->Fecha_Factura = date("Y-m-d H:i:s");
                $oItem->save();
                unset($oItem);
                echo "<td>" . $respuesta[0]['IdFacturacion'] . "</td><td>REPORTE FACTURA EXITOSO</td>";
            } else {

                echo "<td>0</td><td>" . $respuesta["Errors"][0] . "</td>";
            }


            echo "</tr>";
        }

        $factura['Productos']  = $productos;
    }
    return $facturas;
}



function getProducts($id)
{
    $query = '
    SELECT
    SUM(PF.Cantidad) AS Cantidad, SUM(PF.Precio) AS Precio, PF.Impuesto,

    #Producto Dispensacion M
    PDM.NoPrescripcion ,  PDM.Tipo_Tecnologia ,  PDM.ConTec , PDM.Cum_Reportado,
    PDM.Id_Producto_Dispensacion_Mipres, PDM.IdProgramacion, PDM.IdEntrega,
    PDM.IdReporteEntrega, PDM.ID, PDM.CodSerTecAEntregar,
    
    #Dispensacion M
    DM.Numero_Entrega ,  DM.NoIDEPS ,  DM.CodEPS,

    #paciente
    P.Id_Paciente, P.Tipo_Documento,

    #product
    PO.CantUnMinDis, PO.Codigo_Cum,
    
    PF.Id_Factura

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
            AND PDM.NoPrescripcion != "20190723118013317202"
            ' . getContSpecial() . '
        GROUP BY PDM.Id_Producto_Dispensacion_Mipres
            ';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    return $oCon->getData();
}


function  getContSpecial()
{
    return '';
}
