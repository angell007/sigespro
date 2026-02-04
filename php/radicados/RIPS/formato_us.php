<?php
$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

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
(SELECT LPAD(Codigo,2,'0') FROM Departamento WHERE Id_Departamento = F.Id_Departamento) AS Cod_Departamento,
(SELECT LPAD(Codigo_Dane,3,'0') AS Codigo FROM Municipio WHERE Id_Municipio = F.Codigo_Municipio) AS Cod_Municipio,
'U' AS Zona
FROM
Radicado_Factura RF INNER JOIN (SELECT fact.Id_Factura, D.* FROM Factura fact INNER JOIN (SELECT DIS.Id_Dispensacion, P.* FROM Dispensacion DIS INNER JOIN (SELECT PC.*, TIMESTAMPDIFF(YEAR,PC.Fecha_Nacimiento,CURDATE()) AS Edad, (CASE E.Nit
            WHEN 901097473 THEN IF(PC.Id_Regimen = 1, E.Codigo_Eps, 'EPS045')
            ELSE E.Codigo_Eps
        END) AS Codigo_Eps FROM Paciente PC INNER JOIN Eps E ON PC.Nit = E.Nit) P ON DIS.Numero_Documento = P.Id_Paciente WHERE DIS.Id_Tipo_Servicio != 7 AND DIS.Estado_Facturacion = 'Facturada') D ON fact.Id_Dispensacion = D.Id_Dispensacion  WHERE fact.Estado_Factura != 'Anulada' AND fact.Nota_Credito IS NULL ) F ON RF.Id_Factura = F.Id_Factura WHERE RF.Id_Radicado = $id GROUP BY F.Id_Paciente";

//Se crea la instancia que contiene la consulta a realizar
$queryObj = new QueryBaseDatos($query);

//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
$registros = $queryObj->Consultar('Multiple');

$contenido = '';

foreach ($registros['query_result'] as $i => $value) {
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
?>