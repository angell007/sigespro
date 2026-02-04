<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';

$id_factura = isset($_REQUEST['id_factura']) ? $_REQUEST['id_factura'] : '';
$tipo_factura = isset($_REQUEST['tipo_factura']) ? $_REQUEST['tipo_factura'] : '';
if ($id_factura) {
    $query = 'SELECT * FROM Nota_Credito_Global
            WHERE Id_Factura = ' . $id_factura . ' AND  Tipo_Factura = "' . $tipo_factura . '"';
    $oCon = new consulta();

    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $notas_credito = $oCon->getData();
    unset($oCon);

    foreach ($notas_credito as $key => $nota_credito) {
        # code...
        $notas_credito[$key]['Observaciones'] = utf8_decode($notas_credito['Observaciones']);
        if ($nota_credito) {
            /*  $nota_credito['Observaciones'] = utf8_decode($nota_credito['Observaciones'] ); */

            $query = ' SELECT P.* , C.Nombre AS Motivo FROM Producto_Nota_Credito_Global P
                         LEFT JOIN Causal_No_Conforme C ON C.Id_Causal_No_Conforme = P.Id_Causal_No_Conforme
                      WHERE P.Id_Nota_Credito_Global = ' . $nota_credito['Id_Nota_Credito_Global'];
            $oCon = new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
            $notas_credito[$key]['Productos_Nota'] = $oCon->getData();
            unset($oCon);

            //decodificar caracteres especiales
            /*     foreach ($descripciones_nota as $key => $descripcion) {
            # code...
            $descripciones_nota[$key]['Observacion'] = utf8_decode($descripcion['Observacion']);
            } */

            /*  $response['Nota_Credito'] = $nota_credito;

        $response['Productos_Nota'] = $descripciones_nota; */

        }

    }
    #Factura datos
    $tercero = 'Cliente';
    if ($tipo_factura == 'Documento_No_Obligados') {
        $tercero = 'Proveedor';
    }
    $query = "SELECT Id_$tipo_factura AS Id_Factura, Codigo , Fecha_Documento,  Id_$tercero";
    
    if ($tipo_factura == 'Factura_Administrativa' || $tipo_factura == 'Documento_No_Obligados') {
        #
        $query .= ", Tipo_$tercero ";
        
    }

    #dato factura
    $query .= ' FROM ' . $tipo_factura . '
     WHERE Id_' . $tipo_factura . ' = ' . $id_factura;
    $oCon = new consulta();
    $oCon->setQuery($query);

    $factura = $oCon->getData();

    unset($oCon);

    #dato cliente

        if ($tipo_factura == 'Factura_Administrativa') {
            #
            $query = queryClientesFacturaAdministrativa($factura['Tipo_Cliente'], $factura['Id_Cliente']);
        } else if ($tipo_factura == 'Documento_No_Obligados') {

            $query = queryClientesFacturaAdministrativa($factura['Tipo_Proveedor'], $factura['Id_Proveedor']);
        } else {
            $query = queryClientesFacturaAdministrativa('Cliente', $factura['Id_Cliente']);
        }

    #dato factura

    $oCon = new consulta();
    $oCon->setQuery($query);

    $cliente = $oCon->getData();
    unset($oCon);
    $response['Notas'] = $notas_credito;
    $response['Cliente'] = $cliente;
    $response['Factura'] = $factura;
    echo json_encode($response);

}
function queryClientesFacturaAdministrativa($tipoCliente, $id_cliente)
{
    $query = 'SELECT ';
    if ($tipoCliente == 'Funcionario') {
        $query .= ' IFNULL(CONCAT(C.Primer_Nombre," ",C.Primer_Apellido),C.Nombres)  AS Nombre_Cliente ,
                C.Identificacion_Funcionario AS Id_Cliente,
                C.Direccion_Residencia AS Direccion_Cliente,
                IFNULL(C.Telefono,C.Celular) AS Telefono,
                " " AS Ciudad_Cliente,
                "1" AS  Condicion_Pago
                FROM ' . $tipoCliente . '  C
                WHERE Identificacion_Funcionario = ' . $id_cliente;

    } else if ($tipoCliente == 'Cliente') {
        $query .= ' C.Nombre  AS Nombre_Cliente ,
        C.Id_Cliente AS Id_Cliente,
        C.Direccion AS Direccion_Cliente,
        IFNULL(C.Telefono_Pagos,C.Celular) AS Telefono,
        M.Nombre AS Ciudad_Cliente,
        IFNULL(C.Condicion_Pago,1) AS  Condicion_Pago
        FROM Cliente  C
        INNER JOIN Municipio M ON M.Id_Municipio = C.Id_Municipio
        WHERE Id_' . $tipoCliente . ' = ' . $id_cliente;

    } else if ($tipoCliente == 'Proveedor') {
        $query .= ' C.Nombre  AS Nombre_Cliente ,
        C.Id_Proveedor AS Id_Cliente,
        C.Direccion AS Direccion_Cliente,
        IFNULL(C.Telefono,C.Celular) AS Telefono,
        M.Nombre AS Ciudad_Cliente,
        IFNULL(C.Condicion_Pago,1) AS  Condicion_Pago
        FROM Proveedor  C
        INNER JOIN Municipio M ON M.Id_Municipio = C.Id_Municipio
        WHERE Id_' . $tipoCliente . ' = ' . $id_cliente;
    }

    return $query;
}

