<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
include_once '../../class/class.lista.php';

$id = $_REQUEST['id'] ? $_REQUEST['id'] : '';

$oCon = new complex('Documento_No_Obligados', 'Id_Documento_No_Obligados', $id);
$documento = $oCon->getData();
unset($oCon);

$oCon = new complex('Resolucion', 'Id_Resolucion', $documento['Id_Resolucion']);
$resolucion = $oCon->getData();
unset($oCon);

$oCon = new complex('Funcionario', 'Identificacion_Funcionario', $documento['Id_Funcionario']);
$funcionario = $oCon->getData();
unset($oCon);

$proveedor = GetTercero();

$query = "SELECT PS.Nombre as Descripcion , D.* from Descripcion_Documento_No_Obligados D
      left join Producto_Servicio PS on D.Codigo_Producto_Servicio= PS.Codigo_Producto
      WHERE D.Id_Documento_No_Obligados = $id";

$oCon = new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos = $oCon->getData();


$query = "SELECT SUM(DS.Cantidad * DS.Precio) AS Subtotal,
      SUM(DS.Cantidad * DS.Precio * DS.Descuento/100) AS Descuento,
      SUM(DS.Cantidad * DS.Precio *(1- DS.Descuento/100)* DS.Impuesto/100)AS Total_Iva,
      SUM(DS.Subtotal) as Total
      FROM Descripcion_Documento_No_Obligados DS
      WHERE DS.Id_Documento_No_Obligados =$id";
 $oCon = new consulta();
 $oCon->setQuery($query);
 $totales = $oCon->getData();
 unset($oCon);
// $totales['Total']=$totales['Subtotal']+$totales['Total_Iva']-$totales['Descuento'];

$respuesta['Documento'] = $documento;
$respuesta['Resolucion'] = $resolucion;
$respuesta['Proveedor'] = $proveedor;
$respuesta['Totales'] = $totales;
$respuesta['Descripciones'] = $productos;
$respuesta['Funcionario'] = "$funcionario[Nombres] $funcionario[Apellidos]";

echo json_encode($respuesta);

function GetTercero()
{
    global $documento;
    $query = '';
    switch ($documento['Tipo_Proveedor']) {
        case 'Funcionario':
            $query = 'SELECT "Funcionario" AS Tipo_Tercero, Identificacion_Funcionario AS Id_Proveedor , "No" as Contribuyente, "No" as Autorretenedor,
              CONCAT_WS(" ",Nombres,Apellidos)AS Nombre,
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