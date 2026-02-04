<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');


include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_factura = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';

if ($id_factura) {
    $query = 'SELECT * 
        FROM Factura_Administrativa 
        WHERE Id_Factura_Administrativa = ' . $id_factura;

    $oCon = new consulta();
    $oCon->setQuery($query);
    $factura = $oCon->getData();
    unset($oCon);
    if ($factura) {
        $query = 'SELECT * 
        FROM Descripcion_Factura_Administrativa 
        WHERE Id_Factura_Administrativa = ' . $id_factura;

        $oCon = new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query);
        $descripciones = $oCon->getData();
        unset($oCon);
        $total=0;
        $iva = 0;
        $descuento = 0;
        $subtotal = 0;
        foreach ($descripciones as $key => $descripicion) {
            # code...
            $subtotallocal =  (float) $descripicion['Cantidad'] *  (float) $descripicion['Precio']  ;
            $descuentolocal = (float) ( $descripicion['Descuento'] * (float) $descripicion['Cantidad'] );
            $impuestolocal =  (float) ( $descripicion['Precio']  * (float) $descripicion['Cantidad'] ) * ( $descripicion['Impuesto'] /100 );
            $descripciones[$key]['Subtotal'] = ($subtotallocal - $descuentolocal) + $impuestolocal;
            $descripciones[$key]['Subtotal'] = ($subtotallocal - $descuentolocal) + $impuestolocal;
            $subtotal +=  $subtotallocal  ;
            $descuento += $descuentolocal;
            $impuesto += $impuestolocal;

          
           $descripciones[$key]['Descripcion'] =  utf8_decode($descripciones[$key]['Descripcion']); 
        }

        $total = ($subtotal - $descuento) + $impuesto;


        $query = queryClientes($factura);
        $oCon = new consulta();
        $oCon->setQuery($query);
        $cliente = $oCon->getData();
        unset($oCon);

        $query = "SELECT * FROM Resolucion WHERE Id_Resolucion = ".$factura['Id_Resolucion'];

        $oCon = new consulta();
        $oCon->setQuery($query);
        $resolucion = $oCon->getData();
        unset($oCon);
    }

    $response['Datos'] = $factura;
    $response['Descripciones'] = $descripciones;
    $response['Cliente'] = $cliente;
    $response['Resolucion'] = $resolucion;
    $response['Total'] = $total;
    $response['Subtotal'] = $subtotal;
    $response['Descuento'] = $descuento;
    $response['Impuesto'] = $impuesto;
}




echo json_encode($response);


function queryClientes($factura)
{
    $query = 'SELECT ';
    if ($factura['Tipo_Cliente'] == 'Funcionario') {
        $query .= ' IFNULL(CONCAT(C.Primer_Nombre," ",C.Primer_Apellido),C.Nombres)  AS Nombre_Cliente ,
                C.Identificacion_Funcionario AS Id_Cliente, 
                C.Direccion_Residencia AS Direccion_Cliente,
                IFNULL(C.Telefono,C.Celular) AS Telefono,
                " " AS Ciudad_Cliente,
                "1" AS  Condicion_Pago
                FROM ' . $factura['Tipo_Cliente'] . '  C
                WHERE Identificacion_Funcionario = ' . $factura['Id_Cliente'] ;
     
    } else if ($factura['Tipo_Cliente'] == 'Cliente') {
        $query .= ' C.Nombre  AS Nombre_Cliente ,
        C.Id_Cliente AS Id_Cliente, 
        C.Direccion AS Direccion_Cliente,
        IFNULL(C.Telefono_Pagos,C.Celular) AS Telefono,
        M.Nombre AS Ciudad_Cliente,
        IFNULL(C.Condicion_Pago,1) AS  Condicion_Pago
        FROM Cliente  C
        INNER JOIN Municipio M ON M.Id_Municipio = C.Id_Municipio 
        WHERE Id_' . $factura['Tipo_Cliente'] . ' = ' . $factura['Id_Cliente'] ;

    } else if ($factura['Tipo_Cliente'] == 'Proveedor') {
        $query .= ' C.Nombre  AS Nombre_Cliente ,
        C.Id_Proveedor AS Id_Cliente, 
        C.Direccion AS Direccion_Cliente,
        IFNULL(C.Telefono,C.Celular) AS Telefono,
        M.Nombre AS Ciudad_Cliente,
        IFNULL(C.Condicion_Pago,1) AS  Condicion_Pago
        FROM Proveedor  C
        INNER JOIN Municipio M ON M.Id_Municipio = C.Id_Municipio 
        WHERE Id_' . $factura['Tipo_Cliente'] . ' = ' . $factura['Id_Cliente'] ;
    }


    return $query;
}
