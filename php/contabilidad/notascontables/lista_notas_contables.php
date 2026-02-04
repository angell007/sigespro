<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta_paginada.php');

$condicion = getStrCondicions();
$having = '';

if (isset($_REQUEST['tercero']) && $_REQUEST['tercero'] != '') {
    $tercero = str_replace(' ', '%', $_REQUEST['tercero']);
    $having .= " HAVING (Beneficiario LIKE '$tercero%' OR Tercero LIKE '$tercero%')";
}

$query = "SELECT 		
            COUNT(NC.Id_Documento_Contable) AS Total
        FROM (SELECT 
            NC.Id_Documento_Contable, 
            COALESCE(
		   	(IF(C.Nombre IS NULL OR C.Nombre = '', if(C.Primer_Nombre IS NOT NULL, CONCAT_WS(' ', C.Primer_Nombre, C.Segundo_Nombre, C.Primer_Apellido, C.Segundo_Apellido), null),C.Nombre)),
            (IF(P.Nombre IS NULL OR P.Nombre = '', if ( P.Primer_Nombre IS NOT NULL, CONCAT_WS(' ', P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido), NULL), P.Nombre)),
            CONCAT(FB.Nombres,' ', FB.Apellidos))  AS Tercero, 
            NC.Beneficiario
        FROM Cuenta_Documento_Contable CDC 
        INNER JOIN Documento_Contable NC ON NC.Id_Documento_Contable = CDC.Id_Documento_Contable 
        inner join Funcionario F on F.Identificacion_Funcionario = NC.Identificacion_Funcionario
        Left Join Funcionario FB on FB.Identificacion_Funcionario = NC.Beneficiario and NC.Tipo_Beneficiario = 'Funcionario'
        LEFT JOIN Proveedor P  ON P.Id_Proveedor = NC.Beneficiario and NC.Tipo_Beneficiario = 'Proveedor'
        LEFT JOIN Cliente C on C.Id_Cliente = NC.Beneficiario and NC.Tipo_Beneficiario = 'Cliente'

        WHERE NC.Tipo = 'Nota Contable' $condicion 
        GROUP BY CDC.Id_Documento_Contable 
        $having) NC" ;
/*
$oCon= new consulta();
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);
*/
####### PAGINACIÓN ######## 
$tamPag = 20; 
$numReg = $resultado['Total']; 
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


$query = "SELECT
        SQL_CALC_FOUND_ROWS 
        NC.Estado, 
        CDC.Id_Documento_Contable,
        DATE_FORMAT(NC.Fecha_Documento, '%d/%m/%Y') AS Fecha,
        NC.Codigo,
        NC.Beneficiario,
        COALESCE(
        (IF(C.Nombre IS NULL OR C.Nombre = '', if(C.Primer_Nombre IS NOT NULL, CONCAT_WS(' ', C.Primer_Nombre, C.Segundo_Nombre, C.Primer_Apellido, C.Segundo_Apellido), null),C.Nombre)),
        (IF(P.Nombre IS NULL OR P.Nombre = '', if ( P.Primer_Nombre IS NOT NULL, CONCAT_WS(' ', P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido), NULL), P.Nombre)),
        CONCAT(FB.Nombres,' ', FB.Apellidos))  AS Tercero, 
        NC.Concepto,
        GROUP_CONCAT(CDC.Cheque SEPARATOR ' | ') AS Cheques,
        SUM(CDC.Debito) AS Total_Debe_PCGA,
        SUM(CDC.Credito) AS Total_Haber_PCGA,
        SUM(CDC.Deb_Niif) AS Total_Debe_NIIF,
        SUM(CDC.Cred_Niif) AS Total_Haber_NIIF,
        CONCAT_WS(' ', F.Nombres, F.Apellidos) AS Funcionario
        FROM Cuenta_Documento_Contable CDC 
        INNER JOIN Documento_Contable NC ON NC.Id_Documento_Contable = CDC.Id_Documento_Contable 
        inner join Funcionario F on F.Identificacion_Funcionario = NC.Identificacion_Funcionario
        Left Join Funcionario FB on FB.Identificacion_Funcionario = NC.Beneficiario and NC.Tipo_Beneficiario = 'Funcionario'
        LEFT JOIN Proveedor P  ON P.Id_Proveedor = NC.Beneficiario and NC.Tipo_Beneficiario = 'Proveedor'
        LEFT JOIN Cliente C on C.Id_Cliente = NC.Beneficiario and NC.Tipo_Beneficiario = 'Cliente'
        WHERE NC.Tipo = 'Nota Contable' $condicion 
        GROUP BY CDC.Id_Documento_Contable $having
        ORDER BY NC.Fecha_Registro DESC LIMIT $limit,$tamPag " ;

// echo $query; exit;
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

$resultado['data'] = isset($resultado['data']) ? $resultado['data'] : array();
$resultado['data'] = agregarArchivosNotasContables($resultado['data']);

$response['Notas'] = $resultado['data'];
$response['numReg'] = $resultado['total'];

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

function agregarArchivosNotasContables($lista)
{
    if (!is_array($lista) || count($lista) === 0) {
        return $lista;
    }

    foreach ($lista as $index => $item) {
        if (!isset($item['Id_Documento_Contable'])) {
            $lista[$index]['Files'] = array();
            continue;
        }

        $lista[$index]['Files'] = obtenerArchivosNotaContable($item['Id_Documento_Contable']);
    }

    return $lista;
}

function obtenerArchivosNotaContable($id_documento_contable)
{
    $query = "SELECT
        Id_Archivos_Documentos AS Id,
        Tipo_Documento,
        Ruta_AMZ,
        Ruta
        FROM Archivo_Documento
        WHERE Id_Tipo_Documento = $id_documento_contable
        AND Tipo_Documento = 'Nota Contable'
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
