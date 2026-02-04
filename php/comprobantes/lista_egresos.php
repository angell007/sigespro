<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = getStrCondicions();

$having = '';

if (isset($_REQUEST['cli']) && $_REQUEST['cli'] != '') {
    $having .= " HAVING (Beneficiario LIKE '$_REQUEST[cli]%' OR Tercero LIKE '$_REQUEST[cli]%')";
}

$query = "SELECT 
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
NC.Estado,
NC.Concepto,
GROUP_CONCAT(CDC.Cheque SEPARATOR ' | ') AS Cheques,
SUM(CDC.Debito) AS Total_Debe_PCGA,
SUM(CDC.Credito) AS Total_Haber_PCGA,
SUM(CDC.Deb_Niif) AS Total_Debe_NIIF,
SUM(CDC.Cred_Niif) AS Total_Haber_NIIF,
(SELECT CONCAT_WS(' ', Nombres, Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = NC.Identificacion_Funcionario) AS Funcionario
FROM Cuenta_Documento_Contable CDC INNER JOIN Documento_Contable NC ON NC.Id_Documento_Contable = CDC.Id_Documento_Contable WHERE NC.Tipo = 'Egreso' $condicion GROUP BY CDC.Id_Documento_Contable $having" ;

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
NC.Estado,
GROUP_CONCAT(CDC.Cheque SEPARATOR ' | ') AS Cheques,
SUM(CDC.Debito) AS Total_Debe_PCGA,
SUM(CDC.Credito) AS Total_Haber_PCGA,
SUM(CDC.Deb_Niif) AS Total_Debe_NIIF,
SUM(CDC.Cred_Niif) AS Total_Haber_NIIF,
(SELECT CONCAT_WS(' ', Nombres, Apellidos) FROM Funcionario WHERE Identificacion_Funcionario = NC.Identificacion_Funcionario) AS Funcionario
FROM Cuenta_Documento_Contable CDC INNER JOIN Documento_Contable NC ON NC.Id_Documento_Contable = CDC.Id_Documento_Contable WHERE NC.Tipo = 'Egreso' $condicion GROUP BY CDC.Id_Documento_Contable $having ORDER BY 1 DESC LIMIT $limit,$tamPag " ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

$resultado = agregarArchivosEgreso($resultado);

$response['Lista'] = $resultado;
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

    /* if (isset($_REQUEST['cli']) && $_REQUEST['cli'] != '') {
        $condicion .= " AND NC.Beneficiario = '$_REQUEST[cli]'";
    } */
    
    if (isset($_REQUEST['cheque']) && $_REQUEST['cheque'] != '') {
        $condicion .= " AND CDC.Cheque LIKE '%$_REQUEST[cheque]%'";
    }
    
    if (isset($_REQUEST['est']) && $_REQUEST['est'] != '') {
        $condicion .= " AND NC.Estado LIKE '$_REQUEST[est]'";
    }

    return $condicion;
}

function agregarArchivosEgreso($lista)
{
    if (!is_array($lista) || count($lista) === 0) {
        return $lista;
    }

    foreach ($lista as $index => $item) {
        if (!isset($item['Id_Documento_Contable'])) {
            $lista[$index]['Files'] = array();
            continue;
        }
        $lista[$index]['Files'] = obtenerArchivosEgreso($item['Id_Documento_Contable']);
    }

    return $lista;
}

function obtenerArchivosEgreso($id_documento_contable)
{
    $query = "SELECT
        Id_Archivos_Documentos AS Id,
        Tipo_Documento,
        Ruta_AMZ,
        Ruta
        FROM Archivo_Documento
        WHERE Id_Tipo_Documento = $id_documento_contable
        AND Tipo_Documento = 'Egreso'
        ORDER BY Id_Archivos_Documentos DESC";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    unset($oCon);

    if (!$resultado) {
        return array();
    }

    foreach ($resultado as $index => $file) {
        $ruta = '';
        if (isset($file['Ruta']) && $file['Ruta'] !== '') {
            $ruta = $file['Ruta'];
        } elseif (isset($file['Ruta_AMZ']) && $file['Ruta_AMZ'] !== '') {
            $ruta = $file['Ruta_AMZ'];
        }

        if (!isset($file['name']) || $file['name'] === '') {
            $file['name'] = $ruta ? basename($ruta) : 'Archivo';
        }

        if (!isset($file['path']) || $file['path'] === '') {
            $file['path'] = isset($file['Ruta']) ? $file['Ruta'] : '';
        }

        if (!isset($file['url']) && isset($file['Ruta_AMZ']) && $file['Ruta_AMZ'] !== '') {
            $file['url'] = $file['Ruta_AMZ'];
        }

        $resultado[$index] = $file;
    }

    return $resultado;
}

          
?>
