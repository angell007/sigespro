<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';
if (isset($_REQUEST['cod_nota'])) {
    $condicion .= ' WHERE Codigo_Nota LIKE "%' . $_REQUEST['cod_nota'] . '%"';
}
if (isset($_REQUEST['fecha_nota']) && $_REQUEST['fecha_nota'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha_nota'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha_nota'])[1]);
    if ($condicion) {
        $condicion .= " AND Fecha_Nota BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    } else {
        $condicion .= " WHERE DATE(Fecha_Nota) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
}

if (isset($_REQUEST['cliente'])) {
    if ($condicion) {
        $condicion .= ' AND Cliente LIKE "%' . $_REQUEST['cliente'] . '%" ';
    } else {
        $condicion .= ' WHERE Cliente LIKE "%' . $_REQUEST['cliente'] . '%" ';
    }
}

if (isset($_REQUEST['funcionario'])) {
    if ($condicion) {
        $condicion .= ' AND Funcionario LIKE "%' . $_REQUEST['funcionario'] . '%" ';
    } else {
        $condicion .= ' WHERE Funcionario LIKE "%' . $_REQUEST['funcionario'] . '%" ';
    }
}

if (isset($_REQUEST['cod_factura'])) {
    if ($condicion) {
        $condicion .= ' AND Codigo_Factura LIKE "%' . $_REQUEST['cod_factura'] . '%" ';
    } else {
        $condicion .= ' WHERE Codigo_Factura LIKE "%' . $_REQUEST['cod_factura'] . '%" ';
    }
}

$query_consulta = "
SELECT F.Codigo AS Codigo_Factura, F.Id_Factura_Venta AS Id_Factura, 'Factura_Venta' AS Tipo_Factura, NT.Codigo AS Codigo_Nota, NT.Fecha AS Fecha_Nota, NT.Id_Nota_Credito_Global, C.Nombre AS Cliente, 
       IFNULL(CONCAT(FU.Primer_Nombre, ' ', FU.Primer_Apellido), FU.Nombres) AS Funcionario, F.Id_Resolucion, NT.Procesada
FROM Factura_Venta F
INNER JOIN Nota_Credito_Global NT ON NT.Id_Factura = F.Id_Factura_Venta AND NT.Tipo_Factura = 'Factura_Venta'
INNER JOIN Cliente C ON C.Id_Cliente = F.Id_Cliente 
INNER JOIN Funcionario FU ON FU.Identificacion_Funcionario = NT.Id_Funcionario

UNION ALL

SELECT F.Codigo AS Codigo_Factura, F.Id_Factura AS Id_Factura, 'Factura' AS Tipo_Factura, NT.Codigo AS Codigo_Nota, NT.Fecha AS Fecha_Nota, NT.Id_Nota_Credito_Global, C.Nombre AS Cliente, 
       IFNULL(CONCAT(FU.Primer_Nombre, ' ', FU.Primer_Apellido), FU.Nombres) AS Funcionario, F.Id_Resolucion, NT.Procesada
FROM Factura F
INNER JOIN Nota_Credito_Global NT ON NT.Id_Factura = F.Id_Factura AND NT.Tipo_Factura = 'Factura'
INNER JOIN Cliente C ON C.Id_Cliente = F.Id_Cliente 
INNER JOIN Funcionario FU ON FU.Identificacion_Funcionario = NT.Id_Funcionario

UNION ALL

SELECT F.Codigo AS Codigo_Factura, F.Id_Factura_Capita AS Id_Factura, 'Factura_Capita' AS Tipo_Factura, NT.Codigo AS Codigo_Nota, NT.Fecha AS Fecha_Nota, NT.Id_Nota_Credito_Global, C.Nombre AS Cliente, 
       IFNULL(CONCAT(FU.Primer_Nombre, ' ', FU.Primer_Apellido), FU.Nombres) AS Funcionario, F.Id_Resolucion, NT.Procesada
FROM Factura_Capita F
INNER JOIN Nota_Credito_Global NT ON NT.Id_Factura = F.Id_Factura_Capita AND NT.Tipo_Factura = 'Factura_Capita'
INNER JOIN Cliente C ON C.Id_Cliente = F.Id_Cliente 
INNER JOIN Funcionario FU ON FU.Identificacion_Funcionario = NT.Id_Funcionario 

UNION ALL 

SELECT F.Codigo AS Codigo_Factura, F.Id_Factura_Administrativa AS Id_Factura, 'Factura_Administrativa' AS Tipo_Factura, NT.Codigo AS Codigo_Nota, NT.Fecha AS Fecha_Nota, NT.Id_Nota_Credito_Global, C.Nombre AS Cliente, 
       CONCAT_WS(' ', FN.Nombres, FN.Apellidos) Funcionario, F.Id_Resolucion, NT.Procesada
FROM Factura_Administrativa F
INNER JOIN Funcionario FN ON FN.Identificacion_Funcionario = F.Identificacion_Funcionario
INNER JOIN (
    SELECT 'Funcionario' AS Tipo_Tercero, Identificacion_Funcionario AS Id_Proveedor, 'No' AS Contribuyente, 'No' AS Autorretenedor,
           CONCAT_WS(' ', Nombres, Apellidos) AS Nombre, Correo AS Correo_Persona_Contacto, Celular, 'Natural' AS Tipo, 'CC' AS Tipo_Identificacion,
           '' AS Digito_Verificacion, 'Simplificado' AS Regimen, Direccion_Residencia AS Direccion, Telefono,
           IFNULL(Id_Municipio, 99) AS Id_Municipio, 1 AS Condicion_Pago
    FROM Funcionario 
    UNION ALL 
    SELECT 'Proveedor' AS Tipo_Tercero, Id_Proveedor AS Id_Proveedor, 'No' AS Contribuyente, 'No' AS Autorretenedor,
           (CASE WHEN Tipo = 'Juridico' THEN Razon_Social ELSE COALESCE(Nombre, CONCAT_WS(' ', Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido)) END) AS Nombre,
           Correo AS Correo_Persona_Contacto, Celular, Tipo, 'NIT' AS Tipo_Identificacion,
           Digito_Verificacion, Regimen, Direccion, Telefono, Id_Municipio, IFNULL(Condicion_Pago, 1) AS Condicion_Pago
    FROM Proveedor
    UNION ALL 
    SELECT 'Cliente' AS Tipo_Tercero, Id_Cliente AS Id_Proveedor, Contribuyente, Autorretenedor,
           (CASE WHEN Tipo = 'Juridico' THEN Razon_Social ELSE COALESCE(Nombre, CONCAT_WS(' ', Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido)) END) AS Nombre,
           Correo_Persona_Contacto, Celular, Tipo, Tipo_Identificacion,
           Digito_Verificacion, Regimen, Direccion, Telefono_Persona_Contacto AS Telefono,
           Id_Municipio, IFNULL(Condicion_Pago, 1) AS Condicion_Pago
    FROM Cliente
) C ON C.Id_Proveedor = F.Id_Cliente AND BINARY C.Tipo_Tercero LIKE BINARY F.Tipo_Cliente
INNER JOIN Nota_Credito_Global NT ON NT.Id_Factura = F.Id_Factura_Administrativa AND NT.Tipo_Factura = 'Factura_Administrativa'

UNION ALL (
    SELECT D.Codigo AS Codigo_Factura, D.Id_Documento_No_Obligados AS Id_Factura, 'Documento_Soporte' AS Tipo_Factura, NT.Codigo AS Codigo_Nota, NT.Fecha AS Fecha_Nota, NT.Id_Nota_Credito_Global, C.Nombre AS Cliente, 
           CONCAT_WS(' ', FN.Nombres, FN.Apellidos) Funcionario, D.Id_Resolucion, NT.Procesada
    FROM Documento_No_Obligados D
    INNER JOIN Funcionario FN ON FN.Identificacion_Funcionario = D.Id_Funcionario
    INNER JOIN (
        SELECT 'Funcionario' AS Tipo_Tercero, Identificacion_Funcionario AS Id_Proveedor, 'No' AS Contribuyente, 'No' AS Autorretenedor,
               CONCAT_WS(' ', Nombres, Apellidos) AS Nombre, Correo AS Correo_Persona_Contacto, Celular, 'Natural' AS Tipo, 'CC' AS Tipo_Identificacion,
               '' AS Digito_Verificacion, 'Simplificado' AS Regimen, Direccion_Residencia AS Direccion, Telefono,
               IFNULL(Id_Municipio, 99) AS Id_Municipio, 1 AS Condicion_Pago
        FROM Funcionario 
        UNION ALL 
        SELECT 'Proveedor' AS Tipo_Tercero, Id_Proveedor AS Id_Proveedor, 'No' AS Contribuyente, 'No' AS Autorretenedor,
               (CASE WHEN Tipo = 'Juridico' THEN Razon_Social ELSE COALESCE(Nombre, CONCAT_WS(' ', Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido)) END) AS Nombre,
               Correo AS Correo_Persona_Contacto, Celular, Tipo, 'NIT' AS Tipo_Identificacion,
               Digito_Verificacion, Regimen, Direccion, Telefono, Id_Municipio, IFNULL(Condicion_Pago, 1) AS Condicion_Pago
        FROM Proveedor
        UNION ALL 
        SELECT 'Cliente' AS Tipo_Tercero, Id_Cliente AS Id_Proveedor, Contribuyente, Autorretenedor,
               (CASE WHEN Tipo = 'Juridico' THEN Razon_Social ELSE COALESCE(Nombre, CONCAT_WS(' ', Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido)) END) AS Nombre,
               Correo_Persona_Contacto, Celular, Tipo, Tipo_Identificacion,
               Digito_Verificacion, Regimen, Direccion, Telefono_Persona_Contacto AS Telefono,
               Id_Municipio, IFNULL(Condicion_Pago, 1) AS Condicion_Pago
        FROM Cliente
    ) C ON C.Id_Proveedor = D.Id_Proveedor AND BINARY C.Tipo_Tercero LIKE BINARY D.Tipo_Proveedor
    INNER JOIN Nota_Credito_Global NT ON NT.Id_Factura = D.Id_Documento_No_Obligados AND NT.Tipo_Factura = 'Documento_No_Obligados'
)";

$query = "SELECT COUNT(*) AS Total FROM ($query_consulta) AS Notas $condicion";
$oCon = new consulta();
$oCon->setQuery($query);
$numReg = $oCon->getData();
unset($oCon);
$currentPage = '';
$numReg = $numReg['Total'];
$perPage = 15;
$from = "";
$to = "";

if (isset($_REQUEST['pag'])) {
    $currentPage = $_REQUEST['pag'];
    $from = ($currentPage - 1) * $perPage;
} else {
    $currentPage = 1;
    $from = 0;
}

$query = "SELECT * FROM ($query_consulta) AS Notas
    $condicion ORDER BY Notas.Fecha_Nota DESC
 LIMIT $from, $perPage";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$facturas = $oCon->getData();
unset($oCon);

$response['Notas_Credito'] = $facturas;
$response['numReg'] = $numReg;

echo json_encode($response);
