<?php
$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

$query = "SELECT 
F.Codigo AS Factura,
'680010399501' AS Cod_Hab,
'13' AS Cod_Concepto,
F.Q_Productos AS Cant_Servicios,
F.Total_Precio,
(F.Total_Factura - F.Cuota_Moderadora) AS Neto_Factura
FROM
Radicado_Factura RF INNER JOIN (SELECT fact.Id_Factura, fact.Codigo, fact.Cuota AS Cuota_Moderadora, prod_fact.Q_Productos, prod_fact.Total_Precio, prod_fact.Total_Factura FROM Factura fact INNER JOIN (SELECT Id_Factura, COUNT(Id_Producto_Factura) AS Q_Productos, SUM(Precio) AS Total_Precio, ((SUM(Subtotal)-(SUM(Cantidad*Descuento)))*(1+(Impuesto/100))) AS Total_Factura FROM Producto_Factura GROUP BY Id_Factura) prod_fact ON fact.Id_Factura = prod_fact.Id_Factura WHERE fact.Estado_Factura != 'Anulada' AND fact.Nota_Credito IS NULL )  F ON RF.Id_Factura = F.Id_Factura WHERE RF.Id_Radicado = $id GROUP BY RF.Id_Factura";

//Se crea la instancia que contiene la consulta a realizar
$queryObj = new QueryBaseDatos($query);

//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
$formato_ad = count($queryObj->Consultar('Multiple')['query_result']);

$query = "SELECT 
'680010399501' AS Cod_Hab,
'PRODUCTOS HOSPITALARIOS S.A.' AS Empresa,
'NI' AS Tipo_Nit,
'804016084' AS Nit,
F.Codigo,
DATE_FORMAT(F.Fecha_Factura,'%d/%m/%Y') AS Fecha_Factura,
F.Fecha_Dis AS Fecha_Inicio,
F.Fecha_Dis AS Fecha_Fin,
F.Codigo_Eps,
F.EPS,
'' AS Contrato,
UPPER(F.Regimen) AS Regimen,
'' AS Poliza,
F.Cuota_Moderadora,
F.Total_Iva,
F.Total_Descuentos,
F.Total_Factura

FROM
Radicado_Factura RF INNER JOIN (SELECT fact.Id_Factura, fact.Codigo, fact.Fecha_Documento AS Fecha_Factura, fact.Cuota AS Cuota_Moderadora, prod_fact.Total_Iva, prod_fact.Total_Descuentos, prod_fact.Total_Factura, D.* FROM Factura fact INNER JOIN (SELECT Id_Factura, SUM(Subtotal*(Impuesto/100)) AS Total_Iva, SUM(Cantidad*Descuento) AS Total_Descuentos, ((SUM(Subtotal)-(SUM(Cantidad*Descuento)))*(1+(Impuesto/100))) AS Total_Factura FROM Producto_Factura GROUP BY Id_Factura) prod_fact ON fact.Id_Factura = prod_fact.Id_Factura INNER JOIN (SELECT DIS.Id_Dispensacion, DATE_FORMAT(DIS.Fecha_Actual, '%d/%m/%Y') AS Fecha_Dis, DIS.EPS, P.* FROM Dispensacion DIS INNER JOIN (SELECT PC.Id_Paciente, IF(PC.Id_Regimen=1,'Contributivo','Subsidiado') AS Regimen, (CASE E.Nit
            WHEN 901097473 THEN IF(PC.Id_Regimen = 1, E.Codigo_Eps, 'EPS045')
            ELSE E.Codigo_Eps
        END) AS Codigo_Eps FROM Paciente PC INNER JOIN Eps E ON PC.Nit = E.Nit) P ON DIS.Numero_Documento = P.Id_Paciente WHERE DIS.Id_Tipo_Servicio != 7 AND DIS.Estado_Facturacion = 'Facturada') D ON fact.Id_Dispensacion = D.Id_Dispensacion WHERE fact.Estado_Factura != 'Anulada') F ON RF.Id_Factura = F.Id_Factura WHERE RF.Id_Radicado = $id GROUP BY RF.Id_Factura";

//Se crea la instancia que contiene la consulta a realizar
$queryObj = new QueryBaseDatos($query);

//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
$formato_af = count($queryObj->Consultar('Multiple')['query_result']);

$query = "SELECT 
F.Tipo_Documento,
F.Id_Paciente,
F.Codigo_Eps,
F.Id_Regimen,
F.Primer_Apellido,
F.Segundo_Apellido,
F.Primer_Nombre,
F.Segundo_Nombre,
F.Edad,
'1' AS Unidad_Edad,
UPPER(SUBSTRING(F.Genero,1,1)) AS Sexo,
(SELECT Codigo FROM Departamento WHERE Id_Departamento = F.Id_Departamento) AS Cod_Departamento,
(SELECT Codigo_Dane AS Codigo FROM Municipio WHERE Id_Municipio = F.Codigo_Municipio) AS Cod_Municipio,
'U' AS Zona
FROM
Radicado_Factura RF INNER JOIN (SELECT fact.Id_Factura, D.* FROM Factura fact INNER JOIN (SELECT DIS.Id_Dispensacion, P.* FROM Dispensacion DIS INNER JOIN (SELECT PC.*, TIMESTAMPDIFF(YEAR,PC.Fecha_Nacimiento,CURDATE()) AS Edad, (CASE E.Nit
            WHEN 901097473 THEN IF(PC.Id_Regimen = 1, E.Codigo_Eps, 'EPS045')
            ELSE E.Codigo_Eps
        END) AS Codigo_Eps FROM Paciente PC INNER JOIN Eps E ON PC.Nit = E.Nit) P ON DIS.Numero_Documento = P.Id_Paciente WHERE DIS.Id_Tipo_Servicio != 7 AND DIS.Estado_Facturacion = 'Facturada') D ON fact.Id_Dispensacion = D.Id_Dispensacion WHERE fact.Estado_Factura != 'Anulada' AND fact.Nota_Credito IS NULL ) F ON RF.Id_Factura = F.Id_Factura WHERE RF.Id_Radicado = $id GROUP BY F.Id_Paciente";

//Se crea la instancia que contiene la consulta a realizar
$queryObj = new QueryBaseDatos($query);

//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
$formato_us = count($queryObj->Consultar('Multiple')['query_result']);

$query = "SELECT F.Codigo, PCT.Codigo_Eps, PCT.Tipo_Documento, PCT.Id_Paciente, D.Numero_Autorizacion, REPLACE(P.Codigo_Cum,'-','') AS Codigo_Cum, '' AS Tipo_Medicamento, P.Nombre_Comercial AS Nombre_Generico, P.Forma_Farmaceutica, P.Cantidad AS Concentracion, P.Unidad_Medida, F.Cantidad, F.Precio, F.Total FROM Radicado_Factura RF INNER JOIN Radicado R ON RF.Id_Radicado = R.Id_Radicado INNER JOIN (SELECT PF.Id_Producto_Factura, PF.Id_Producto, PF.Cantidad, PF.Precio, ((PF.Subtotal-(PF.Cantidad*PF.Descuento))*(1+(PF.Impuesto/100))) AS Total, FT.Codigo, FT.Id_Factura, Id_Dispensacion FROM Producto_Factura PF INNER JOIN Factura FT ON FT.Id_Factura = PF.Id_Factura WHERE FT.Estado_Factura != 'Anulada' AND FT.Nota_Credito IS NULL ) F ON F.Id_Factura = RF.Id_Factura LEFT JOIN (SELECT PD.Id_Producto, DP.Id_Dispensacion, PD.Numero_Autorizacion, DP.Numero_Documento AS Id_Paciente FROM Producto_Dispensacion PD INNER JOIN Dispensacion DP ON DP.Id_Dispensacion = PD.Id_Dispensacion WHERE DP.Id_Tipo_Servicio != 7 AND DP.Estado_Facturacion = 'Facturada') D ON (F.Id_Dispensacion = D.Id_Dispensacion AND F.Id_Producto = D.Id_Producto) INNER JOIN (SELECT PC.Id_Paciente, PC.Tipo_Documento, (CASE E.Nit WHEN 901097473 THEN IF(PC.Id_Regimen = 1, E.Codigo_Eps, 'EPS045') ELSE E.Codigo_Eps END) AS Codigo_Eps FROM Paciente PC INNER JOIN Eps E ON E.Nit = PC.Nit) PCT ON PCT.Id_Paciente = D.Id_Paciente INNER JOIN Producto P ON P.Id_Producto = F.Id_Producto INNER JOIN Categoria CT ON CT.Id_Categoria = P.Id_Categoria WHERE R.Id_Radicado = $id AND CT.Nombre NOT IN ('MATERIALES','PANALES','COSMETICOS') GROUP BY F.Id_Producto_Factura, D.Id_Dispensacion";

//Se crea la instancia que contiene la consulta a realizar
$queryObj = new QueryBaseDatos($query);

//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
$formato_am = count($queryObj->Consultar('Multiple')['query_result']);

$query = "SELECT F.Codigo, PCT.Codigo_Eps, PCT.Tipo_Documento, PCT.Id_Paciente, D.Numero_Autorizacion, P.Codigo_Cum, '' AS Tipo_Medicamento, P.Nombre_Comercial AS Nombre_Generico, P.Forma_Farmaceutica, P.Cantidad AS Concentracion, P.Unidad_Medida, F.Cantidad, F.Precio, (
    CASE C.Tipo_Valor
    WHEN 'Cerrada' THEN (ROUND((F.Total)))
    ELSE
        (F.Total)
    END
    ) AS Total FROM Radicado_Factura RF INNER JOIN Radicado R ON RF.Id_Radicado = R.Id_Radicado INNER JOIN (SELECT PF.Id_Producto_Factura, PF.Id_Producto, PF.Cantidad, PF.Precio, ((PF.Subtotal-(PF.Cantidad*PF.Descuento))*(1+(PF.Impuesto/100))) AS Total, FT.Codigo, FT.Id_Factura, FT.Id_Cliente, Id_Dispensacion FROM Producto_Factura PF INNER JOIN Factura FT ON FT.Id_Factura = PF.Id_Factura WHERE FT.Estado_Factura != 'Anulada' AND FT.Nota_Credito IS NULL ) F ON F.Id_Factura = RF.Id_Factura INNER JOIN Cliente C ON C.Id_Cliente = F.Id_Cliente LEFT JOIN (SELECT PD.Id_Producto, DP.Id_Dispensacion, PD.Numero_Autorizacion, DP.Numero_Documento AS Id_Paciente FROM Producto_Dispensacion PD INNER JOIN Dispensacion DP ON DP.Id_Dispensacion = PD.Id_Dispensacion WHERE DP.Id_Tipo_Servicio != 7 AND DP.Estado_Facturacion = 'Facturada') D ON (F.Id_Dispensacion = D.Id_Dispensacion AND F.Id_Producto = D.Id_Producto) INNER JOIN (SELECT PC.Id_Paciente, PC.Tipo_Documento, (CASE E.Nit WHEN 901097473 THEN IF(PC.Id_Regimen = 1, E.Codigo_Eps, 'EPS045') ELSE E.Codigo_Eps END) AS Codigo_Eps FROM Paciente PC INNER JOIN Eps E ON E.Nit = PC.Nit) PCT ON PCT.Id_Paciente = D.Id_Paciente INNER JOIN Producto P ON P.Id_Producto = F.Id_Producto INNER JOIN Categoria CT ON CT.Id_Categoria = P.Id_Categoria WHERE R.Id_Radicado = $id AND CT.Nombre IN ('MATERIALES','PANALES','COSMETICOS') GROUP BY F.Id_Producto_Factura, D.Id_Dispensacion";

//Se crea la instancia que contiene la consulta a realizar
$queryObj = new QueryBaseDatos($query);

//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
$formato_at = count($queryObj->Consultar('Multiple')['query_result']);

$contenido = '';

$registros = registrosFormatoCT();

foreach ($registros as $i => $value) {
    $count = count($value);
    $i = 0;
    foreach ($value as $columna => $valor) {
        if (isNumeric($columna)) {
            $contenido .= number_format($valor,2,".","");
        } else{
            $contenido .= str_replace("\t","",$valor);
        }
        $contenido .= ($i == ($count-1)) ? "\r\n" : ","; // Separador
        $i++;
    }
}

echo $contenido;

function registrosFormatoCT() {
    global $formato_ad;
    global $formato_af;
    global $formato_us;
    global $formato_am;
    global $formato_at;
    global $datos;
    global $tipo;

    $tipos = ["AD","AF","US","AM","AT"];
    $formatos = [$formato_ad,$formato_af,$formato_us,$formato_am,$formato_at]; // Se repite a AM porque el CT siempre va a tener los mismos registros.

    $registros = [];

    for ($i=0; $i < count($tipos); $i++) {
        $tipo = $tipos[$i]; 
        $registros[] = [
            "Cod_Hab" => "680010399501",
            "Fecha" => date('d/m/Y'),
            "Consecutivo" => consecutivoRips($datos['query_result']['Codigo']),
            "Registros" => $formatos[$i]
        ];
    }

    return $registros;
}
?>