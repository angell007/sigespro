<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';

$id_nota = isset($_REQUEST['id_nota_credito']) ? $_REQUEST['id_nota_credito'] : '';

if ($id_nota) {
    $query = 'SELECT * FROM Nota_Credito_Global
            WHERE Id_Nota_Credito_Global = ' . $id_nota;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $nota_credito = $oCon->getData();

    unset($oCon);
    if ($nota_credito) {

        $query = ' SELECT P.* ,( (P.Impuesto)/100) * ( P.Cantidad * (P.Precio_Nota_Credito) ) as Total_Impuesto , C.Nombre AS Motivo FROM Producto_Nota_Credito_Global P
                   LEFT JOIN Causal_No_Conforme C ON C.Id_Causal_No_Conforme = P.Id_Causal_No_Conforme
                 WHERE P.Id_Nota_Credito_Global = ' . $nota_credito['Id_Nota_Credito_Global'];
        $oCon = new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query);
        $descripciones_nota = $oCon->getData();
        unset($oCon);

        #Factura datos
        $tercero = 'Cliente';
        if ($nota_credito['Tipo_Factura'] == 'Documento_No_Obligados') {
            $tercero = 'Proveedor';
        }
        $query = "SELECT Id_$nota_credito[Tipo_Factura] AS Id_Factura, Codigo , Fecha_Documento,  Id_$tercero";

        if ($nota_credito['Tipo_Factura'] == 'Factura_Administrativa' || $nota_credito['Tipo_Factura'] == 'Documento_No_Obligados') {
            #
            $query .= ", Tipo_$tercero ";

        }

        #dato factura
        $query .= ' FROM ' . $nota_credito['Tipo_Factura'] . '
        WHERE Id_' . $nota_credito['Tipo_Factura'] . ' = ' . $nota_credito['Id_Factura'];
        $oCon = new consulta();
        $oCon->setQuery($query);

        $nota_credito['Factura'] = $oCon->getData();
        unset($oCon);

        #dato cliente

        if ($nota_credito['Tipo_Factura'] == 'Factura_Administrativa') {
            #
            $query = queryClientesFacturaAdministrativa($nota_credito['Factura']['Tipo_Cliente'], $nota_credito['Factura']['Id_Cliente']);
        } else if ($nota_credito['Tipo_Factura'] == 'Documento_No_Obligados') {

            $query = queryClientesFacturaAdministrativa($nota_credito['Factura']['Tipo_Proveedor'], $nota_credito['Factura']['Id_Proveedor']);
        } else {
            $query = queryClientesFacturaAdministrativa('Cliente', $nota_credito['Factura']['Id_Cliente']);
        }

        #dato factura

        $oCon = new consulta();
        $oCon->setQuery($query);

        $nota_credito['Cliente'] = $oCon->getData();
        unset($oCon);

        $response['Nota_Credito'] = $nota_credito;
        $response['Productos_Nota'] = $descripciones_nota;

    }

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
