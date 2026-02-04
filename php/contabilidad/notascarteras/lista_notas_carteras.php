<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$condicion = getStrCondicions();

$having = '';

if (isset($_REQUEST['tercero']) && $_REQUEST['tercero'] != '') {
    $having .= " HAVING (Beneficiario LIKE '$_REQUEST[tercero]%' OR Tercero LIKE '$_REQUEST[tercero]%')";
}

$query = "
SELECT 
DATE_FORMAT(NC.Fecha_Documento, '%d/%m/%Y') AS Fecha,
NC.Codigo,
NC.Beneficiario,
(
CASE
NC.Tipo_Beneficiario
WHEN 'Cliente' THEN (SELECT Nombre FROM Cliente WHERE Id_Cliente = NC.Beneficiario)
WHEN 'Proveedor' THEN (SELECT Nombre FROM Proveedor WHERE Id_Proveedor = NC.Beneficiario)
WHEN 'Funcionario' THEN (SELECT CONCAT_WS(' ', Nombres, Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = NC.Beneficiario)
END
) AS Tercero,
NC.Concepto,
GROUP_CONCAT(CDC.Cheque SEPARATOR ' | ') AS Cheques,
SUM(CDC.Debito) AS Total_Debe_PCGA,
SUM(CDC.Credito) AS Total_Haber_PCGA,
SUM(CDC.Deb_Niif) AS Total_Debe_NIIF,
SUM(CDC.Cred_Niif) AS Total_Haber_NIIF,
(SELECT CONCAT_WS(' ', Nombres, Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = NC.Identificacion_Funcionario) AS Funcionario
FROM Cuenta_Documento_Contable CDC INNER JOIN Documento_Contable NC ON NC.Id_Documento_Contable = CDC.Id_Documento_Contable WHERE NC.Tipo = 'Nota Cartera' $condicion GROUP BY CDC.Id_Documento_Contable $having" ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 20; 
$numReg = count($resultado); 
$paginas = ceil($numReg/$tamPag); 
$limit = ""; 
$paginaAct = "";

if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') { 
    $paginaAct = 1; 
    $limit = 0; 
} else { 
    $paginaAct = $_REQUEST['pag']; 
    $limit = ($paginaAct-1) * $tamPag; 
}

$query = "
SELECT 
CDC.Id_Documento_Contable,
NC.Estado,
DATE_FORMAT(NC.Fecha_Documento, '%d/%m/%Y') AS Fecha,
NC.Codigo,
NC.Beneficiario,
(
    CASE
    NC.Tipo_Beneficiario
    WHEN 'Cliente' THEN (SELECT IF(Nombre IS NULL OR Nombre = '', CONCAT_WS(' ', Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido), Nombre) FROM Cliente WHERE Id_Cliente = NC.Beneficiario)
    WHEN 'Proveedor' THEN (SELECT IF(Nombre IS NULL OR Nombre = '', CONCAT_WS(' ', Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido), Nombre) FROM Proveedor WHERE Id_Proveedor = NC.Beneficiario)
    WHEN 'Funcionario' THEN (SELECT CONCAT_WS(' ', Nombres, Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = NC.Beneficiario)
    END
    ) AS Tercero,
NC.Concepto,
GROUP_CONCAT(CDC.Cheque SEPARATOR ' | ') AS Cheques,
SUM(CDC.Debito) AS Total_Debe_PCGA,
SUM(CDC.Credito) AS Total_Haber_PCGA,
SUM(CDC.Deb_Niif) AS Total_Debe_NIIF,
SUM(CDC.Cred_Niif) AS Total_Haber_NIIF,
(SELECT CONCAT_WS(' ', Nombres, Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = NC.Identificacion_Funcionario) AS Funcionario
FROM Cuenta_Documento_Contable CDC INNER JOIN Documento_Contable NC ON NC.Id_Documento_Contable = CDC.Id_Documento_Contable WHERE NC.Tipo = 'Nota Cartera' $condicion GROUP BY CDC.Id_Documento_Contable $having ORDER BY NC.Fecha_Registro DESC LIMIT $limit,$tamPag " ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

$response['Notas'] = $resultado;
$response['numReg'] = $numReg;

echo json_encode($response);

function getStrCondicions() {
    $condicion = '';

    if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != '') {
        $condicion .= " AND NC.Codigo LIKE '%$_REQUEST[cod]%'";
    }

    if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
        $condicion .= " AND (DATE(NC.Fecha_Documento) BETWEEN '$fecha_inicio' AND '$fecha_fin')";
    }

    /* if (isset($_REQUEST['tercero']) && $_REQUEST['tercero'] != '') {
        $condicion .= " AND NC.Beneficiario = '$_REQUEST[tercero]'";
    } */
    if (isset($_REQUEST['est']) && $_REQUEST['est'] != '') {
        $condicion .= " AND NC.Estado = '$_REQUEST[est]'";
    }

    return $condicion;
}

          
?>