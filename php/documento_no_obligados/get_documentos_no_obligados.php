<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

// require_once '../../config/start.inc.php';
// include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';

$codigo = (isset($_REQUEST['codigo']) ? $_REQUEST['codigo'] : '');
$fechas = (isset($_REQUEST['fechas']) ? $_REQUEST['fechas'] : '');
$proveedor = (isset($_REQUEST['proveedor']) ? $_REQUEST['proveedor'] : '');
$id = (isset($_REQUEST['id_documento']) ? $_REQUEST['id_documento'] : '');
$pag = (isset($_REQUEST['pag']) ? $_REQUEST['pag'] : 1);
$tam = (isset($_REQUEST['tam']) ? $_REQUEST['tam'] : 15);

$limit = ($pag - 1) * $tam;

$query = getQuery();
$oCon = new consulta();
$oCon->setQuery($query . " LIMIT $limit, $tam");
$oCon->setTipo('Multiple');

$documentos = $oCon->getData();

unset($oCon);
$query = "SELECT Count(*) as Total From ($query)D";
$oCon = new consulta();
$oCon->setQuery($query);

$total = $oCon->getData();

$respuesta['Documentos'] = $documentos;
$respuesta['TotalItems'] = $total['Total'];

echo json_encode($respuesta);

function getQuery()
{
    global $codigo, $fechas, $proveedor, $id;

    $condiciones = [];

    if ($codigo != '') {
        array_push($condiciones, "DNO.Codigo like '%$codigo%'");
    }
    if ($fechas != '') {
        $fechas = str_replace(" - ", " 00:00:00' AND '", $fechas) . ' 23:59:00';
        array_push($condiciones, "DNO.Fecha_Documento BETWEEN '$fechas'");
    }
    if ($id != '') {
        array_push($condiciones, "DNO.Id_Documento_Soporte BETWEEN '$fechas'");
    }
    if ($proveedor != '') {
        $proveedor = str_replace(" ", "%", $proveedor);
        $having = "HAVING Nombre like '%$proveedor%'";
    }

    $condiciones = count($condiciones) > 0 ? "WHERE " . implode(" AND ", $condiciones) : '';

    $query = "SELECT DNO.Codigo,
      DNO.Id_Documento_No_Obligados,
      DNO.Fecha_Adquirido,
      DNO.Fecha_Documento as Fecha,
      DNO.Forma_Pago,
      DNO.Tipo_Proveedor,
      DNO.Id_Proveedor,
      DNO.Id_Resolucion,
      DNO.Procesada,
      CASE 
        WHEN DNO.Tipo_Proveedor='Proveedor' THEN IF(P.Tipo='Juridico', P.Razon_Social, COALESCE(P.Nombre, CONCAT_WS(' ',P.Primer_Nombre,P.Segundo_Nombre,P.Primer_Apellido,P.Segundo_Apellido)))
        WHEN DNO.Tipo_Proveedor='Funcionario' THEN CONCAT_WS(' ',F.Nombres,F.Apellidos)
        WHEN DNO.Tipo_Proveedor='Cliente' THEN IF(C.Tipo = 'Juridico', C.Razon_Social, COALESCE(C.Nombre, CONCAT_WS(' ',C.Primer_Nombre,C.Segundo_Nombre,C.Primer_Apellido,C.Segundo_Apellido) ))
      END as Nombre
      FROM Documento_No_Obligados DNO
      LEFT JOIN Proveedor P ON P.Id_Proveedor = DNO.Id_Proveedor AND DNO.Tipo_Proveedor='Proveedor'
      LEFT JOIN Funcionario F ON F.Identificacion_Funcionario = DNO.Id_Proveedor AND DNO.Tipo_Proveedor='Funcionario'
      LEFT JOIN Cliente C ON C.Id_Cliente = DNO.Id_Proveedor AND DNO.Tipo_Proveedor='Cliente'
      $condiciones
      $having
      ORDER BY DNO.Id_Documento_No_Obligados DESC
      ";
    // echo $query; exit;

    return "$query";
}

function GetTercero()
{
    global $documento;
    $query = '';
    switch ($documento['Tipo_Proveedor']) {
        case 'Funcionario':
            $query = 'SELECT "Funcionario" AS Tipo_Tercero, Identificacion_Funcionario AS Id_Proveedor , "No" as Contribuyente, "No" as Autorretenedor,
              AS Nombre,
              Correo AS Correo_Persona_Contacto , Celular, "Natural" AS Tipo, "CC" AS Tipo_Identificacion,
              "" AS Digito_Verificacion, "Simplificado" AS Regimen, Direccion_Residencia AS Direccion, Telefono,
              IFNULL(Id_Municipio,99) AS Id_Municipio , 1 AS Condicion_Pago
              FROM Funcionario WHERE Identificacion_Funcionario = ' . $documento['Id_Proveedor'];
            break;

        case 'Proveedor':
            $query = 'SELECT "Proveedor" AS Tipo_Tercero, Id_Proveedor AS Id_Proveedor , "No" as Contribuyente, "No" as Autorretenedor,

              (CASE
              WHEN Tipo = "Juridico" THEN Razon_Social
              ELSE  COALESCE(Nombre, CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )

              END) AS Nombre,
              Correo AS Correo_Persona_Contacto,
              Celular, Tipo, "NIT" AS Tipo_Identificacion,
              Digito_Verificacion, Regimen, Direccion ,Telefono,
              Id_Municipio, IFNULL(Condicion_Pago , 1 ) as Condicion_Pago
              FROM Proveedor WHERE Id_Proveedor = ' . $documento['Id_Proveedor'];
            break;

        case 'Cliente':
            return getCliente();
            break;

        default:
            echo "error";exit;
            break;
    }

    $oCon = new consulta();
    $oCon->setQuery($query);

    $proveedor = $oCon->getData();
    unset($oCon);

    return $proveedor;
}

function getCliente()
{
      global $documento;
    $query = 'SELECT "Cliente" AS Tipo_Tercero, Id_Cliente as Id_Proveedor, Contribuyente, Autorretenedor,
        (CASE
        WHEN Tipo = "Juridico" THEN Razon_Social
        ELSE  COALESCE(Nombre, CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )

        END) AS Nombre,
        Correo_Persona_Contacto,
        Celular, Tipo, Tipo_Identificacion,
        Digito_Verificacion, Regimen, Direccion, Telefono_Persona_Contacto AS Telefono,
        Id_Municipio, IFNULL(Condicion_Pago , 1 ) as Condicion_Pago
        FROM Cliente WHERE Id_Cliente =' . $documento['Id_Proveedor'];
    $oCon = new consulta();
    $oCon->setQuery($query);
    $proveedor = $oCon->getData();

    unset($oCon);
    return $proveedor;
}
