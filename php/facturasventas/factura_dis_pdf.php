<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
require_once '../../class/html2pdf.class.php';
include_once '../../class/NumeroALetra.php';

$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');
$tipo = isset($_REQUEST['Tipo']) ? $_REQUEST['Tipo'] : false;
$ruta = isset($_REQUEST['Ruta']) ? $_REQUEST['Ruta'] : '';

/* FUNCIONES BASICAS */
function fecha($str)
{
    $parts = explode(" ", $str);
    $date = explode("-", $parts[0]);
    return $date[2] . "/" . $date[1] . "/" . $date[0];
}
/* FIN FUNCIONES BASICAS*/

/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion', "Id_Configuracion", 1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */

/* DATOS DEL ARCHIVO A MOSTRAR */
$oItem = new complex("Factura", "Id_Factura", $id);
$data = $oItem->getData();
unset($oItem);

$oItem = new complex("Resolucion", "Id_Resolucion", $data['Id_Resolucion']);
$fact = $oItem->getData();
unset($oItem);

/* $oItem = new complex("Cliente","Id_Cliente",$data["Id_Cliente"]);
$cliente = $oItem->getData();
unset($oItem); */

$band_homologo = false;

$query = 'SELECT PS.numeroAutorizacion, D.EPS,
            FV.Codigo_Qr, FV.Cufe, FV.Fecha_Documento as Fecha ,
            FV.Observacion_Factura as observacion,
            FV.Codigo as Codigo,
            IF(FV.Condicion_Pago=1,"CONTADO",FV.Condicion_Pago) as Condicion_Pago ,
            FV.Fecha_Pago as Fecha_Pago , 
            FV.Tipo as tipo,
            C.Id_Cliente as IdCliente ,
            C.Nombre as NombreCliente, 
            C.Direccion as DireccionCliente, 
            (SELECT Nombre FROM Municipio WHERE Id_Municipio = C.Ciudad) as CiudadCliente,
            C.Credito as CreditoCliente, C.Cupo as CupoCliente,
            CONVERT(CAST(CONVERT( (SELECT CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido, CONCAT("- ", UPPER(IF(Id_Regimen=1,"Contributivo","Subsidiado")))) FROM Paciente WHERE Id_Paciente=D.Numero_Documento) USING LATIN1) AS BINARY) USING UTF8)  AS Nombre_Paciente,
            D.Numero_Documento, FV.Cuota, D.Codigo AS Cod_Dis,
            (SELECT Nombre FROM Tipo_Servicio WHERE Id_Tipo_Servicio = D.Id_Tipo_Servicio) AS Tipo_Servicio, C.Tipo_Valor,(SELECT  P.Eps FROM Dispensacion D INNER JOIN (SELECT EPS, Id_Paciente FROM Paciente ) P ON D.Numero_Documento=P.Id_Paciente   WHERE Id_Dispensacion=FV.Id_Dispensacion) as Eps
          FROM Factura FV
          INNER JOIN Dispensacion D ON FV.Id_Dispensacion = D.Id_Dispensacion
          LEFT JOIN Positiva_Data PS ON PS.Id_Dispensacion = D.Id_Dispensacion
          
            Inner Join Paciente P on P.Id_Paciente = D.Numero_Documento
          INNER JOIN Cliente C
          ON FV.Id_Cliente = C.Id_Cliente
          WHERE FV.Id_Factura = ' . $id;

$oCon = new consulta();
$oCon->setQuery($query);
$cliente = $oCon->getData();
unset($oCon);

if ($tipo && $tipo == 'Homologo') {
    // Consultar si tiene homologo

    $query = 'SELECT FV.Id_Factura,
    FV.Codigo_Qr, FV.Cufe, FV.Fecha_Documento as Fecha , FV.Observacion_Factura as observacion, FV.Codigo as Codigo,IF(FV.Condicion_Pago=1,"CONTADO",FV.Condicion_Pago) as Condicion_Pago , FV.Fecha_Pago as Fecha_Pago , FV.Tipo as tipo,
    C.Id_Cliente as IdCliente ,C.Nombre as NombreCliente, C.Direccion as DireccionCliente, (SELECT Nombre FROM Municipio WHERE Id_Municipio = C.Ciudad) as CiudadCliente, C.Credito as CreditoCliente, C.Cupo as CupoCliente, C.Tipo_Valor, (SELECT  P.Eps FROM Dispensacion D INNER JOIN (SELECT EPS, Id_Paciente FROM Paciente ) P ON D.Numero_Documento=P.Id_Paciente   WHERE Id_Dispensacion=FV.Id_Dispensacion) as Eps
    FROM Factura FV
    INNER JOIN Cliente C
    ON FV.Id_Cliente = C.Id_Cliente
    WHERE FV.Id_Factura_Asociada = ' . $id;

    $oCon = new consulta();
    $oCon->setQuery($query);
    $homologo = $oCon->getData();
    unset($oCon);

    $cod_factura = $cliente['Codigo'];

    if ($homologo) {

        $band_homologo = true;
        foreach ($homologo as $key => $value) {
            $cliente[$key] = $value;
        }
        $cliente['Cuota'] = 0;
        $cliente['observacion'] = "<br><strong>ESTA ES UNA FACTURA HOMOLOGO POS, QUE CORRESPONDE AL DESCUENTO APLICADO A LA FACTURA - $cod_factura </strong>";
    }
}

if (!$band_homologo) {
    $query = 'SELECT
    CONCAT_WS(" ",P.Nombre_Comercial, P.Presentacion, P.Concentracion, " (", P.Principio_Activo,") ", P.Cantidad," ", P.Unidad_Medida ) as producto,
    P.Invima,
    P.Id_Producto,
    P.Codigo_Cum as Cum,
     PFV.Fecha_Vencimiento as Vencimiento,
    IFNULL(PD.Lote, PFV.Lote) as Lote,
    PD.Id_Inventario as Id_Inventario,
    0 as Costo_unitario,
    PFV.Cantidad as Cantidad,
    PFV.Precio as Precio,
    PFV.Impuesto as Impuesto,
    PFV.Descuento as Descuento,
    PFV.Subtotal as Subtotal,
    PFV.Id_Producto_Factura as idPFV
   FROM Producto_Factura PFV
   LEFT JOIN Producto_Dispensacion PD
   ON PD.Id_Producto_Dispensacion = PFV.Id_Producto_Dispensacion
   INNER JOIN Producto P
   ON P.Id_Producto = PFV.Id_Producto
   WHERE PFV.Id_Factura =  ' . $id;

} else {
    $query = 'SELECT
    CONCAT_WS(" ",P.Nombre_Comercial, P.Presentacion, P.Concentracion, " (", P.Principio_Activo,") ", P.Cantidad," ", P.Unidad_Medida ) as producto,
    P.Invima,
    P.Id_Producto,
    P.Codigo_Cum as Cum,
     PFV.Fecha_Vencimiento as Vencimiento,
    IFNULL(PD.Lote, PFV.Lote) as Lote,
    PD.Id_Inventario as Id_Inventario,
    0 as Costo_unitario,
    PFV.Cantidad as Cantidad,
    PFV.Precio as Precio,
    PFV.Impuesto as Impuesto,
    PFV.Descuento as Descuento,
    PFV.Subtotal as Subtotal,
    PFV.Id_Producto_Factura as idPFV
   FROM Producto_Factura PFV
   LEFT JOIN Producto_Dispensacion PD
   ON PD.Id_Producto_Dispensacion = PFV.Id_Producto_Dispensacion
   INNER JOIN Producto P
   ON P.Id_Producto = PFV.Id_Producto
    WHERE PFV.Id_Factura =  ' . $cliente['Id_Factura'];

}

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$productos = $oCon->getData();
unset($oCon);

$oItem = new complex("Funcionario", "Identificacion_Funcionario", $data["Id_Funcionario"]);
$func = $oItem->getData();
unset($oItem);

/* FIN DATOS DEL ARCHIVO A MOSTRAR */

ob_start(); // Se Inicializa el gestor de PDF

/* HOJA DE ESTILO PARA PDF*/
$tipo = "Factura";
$style = '<style>

.page-content{
width:750px;
pading:0;
}
.row{
display:inlinie-block;
width:750px;
}
.td-header{
    font-size:10px;
    line-height
}
    table, th, td {
        border: 1px solid black;
        border-collapse: collapse;
      }
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/

if (!$band_homologo) {
    $query5 = 'SELECT SUM(Subtotal) as TotalFac FROM Producto_Factura WHERE Id_Factura = ' . $id;
} else {
    $query5 = 'SELECT SUM(Subtotal) as TotalFac FROM Producto_Factura WHERE Id_Factura = ' . $cliente['Id_Factura'];
}

$oCon = new consulta();
$oCon->setQuery($query5);
$totalFactura = $oCon->getData();
unset($oCon);

if ($fact["Tipo_Resolucion"] == "Resolucion_Electronica") {
    $titulo = $band_homologo ? 'Factura Electrónica de Venta (HOMOLOGO)' : 'Factura Electrónica de Venta ' . ($cliente['tipo'] == 'Homologo' ? '(HOMOLOGO)' : '');
} else {
    $titulo = $band_homologo ? 'Factura Electrónica de Venta (HOMOLOGO)' : 'Factura Electrónica de Venta ' . ($cliente['tipo'] == 'Homologo' ? '(HOMOLOGO)' : '');
}

$autorizacion = '';
if ($cliente["numeroAutorizacion"]) {
    $autorizacion = "N.Auto.:" . $cliente["numeroAutorizacion"] . " ";
}

$codigos = '
    <span style="margin:-5px 0 0 0;font-size:13px;line-height:13px;">' . $titulo . '</span>
    <h3 style="margin:0 0 0 0;font-size:15px;line-height:15px;">' . $cliente["Codigo"] . '</h3>
    <h5 style="margin:5px 0 0 0;font-size:8px;line-height:8px;">F. Expe.:' . fecha($cliente["Fecha"]) . '</h5>
    <h5 style="margin:5px 0 0 0;font-size:8px;line-height:8px;">H. Expe.:&nbsp;&nbsp;&nbsp;'.date('H:i:s', strtotime($data['Fecha_Documento'])).'</h5>
    <h4 style="margin:5px 0 0 0;font-size:8px;line-height:8px;">F. Venc.:' . fecha($cliente["Fecha_Pago"]) . '</h4>
    <h4 style="margin:0 0 0 0;font-size:13px;line-height:13px">' . $cliente["Cod_Dis"] . '</h4>
    <h4 style="margin:0 0 0 0;font-size:13px;line-height:13px">' . $cliente["Tipo_Servicio"] . '</h4>
    <h5 style="margin:5px 0 0 0;font-size:8px;line-height:8px;">' . $autorizacion . '</h5>

';

$condicion_pago = $cliente["Condicion_Pago"] == "CONTADO" ? $cliente["Condicion_Pago"] : "CREDITO A $cliente[Condicion_Pago] Días";
$metodo_pago =$cliente["Condicion_Pago"] == "CONTADO" ? "TRANSFERENCIA DÉBITO" : "TRANSFERENCIA CRÉDITO";

$nombre_paciente = (explode('-', $cliente["Nombre_Paciente"])[0]);
$nombre_paciente = $nombre_paciente? $nombre_paciente : explode('-', $cliente["Nombre_Paciente"])[0];
// echo $nombre_paciente. ".............". $cliente["Nombre_Paciente"]; exit; 
$regimen = explode('-', $cliente["Nombre_Paciente"])[1];

/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera = '<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="' . $_SERVER["DOCUMENT_ROOT"] . 'assets/images/LogoProh.jpg" style="width:50px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:330px;font-weight:thin;font-size:10px;line-height:11px;">
                    <strong>' . $config["Nombre_Empresa"] . '</strong><br>
                    N.I.T.: ' . $config["NIT"] . '<br>
                    ' . $config["Direccion"] . '<br>
                    Bucaramanga, Santander<br>
                    TEL: ' . $config["Telefono"] . '<br>
                    RESPONSABLE DE IVA
                  </td>
                  <td style="width:250px;text-align:right;">
                        ' . $codigos . '
                  </td>
                  <td style="width:130px;">';

    $nombre_fichero = '';

    if ($fact["Tipo_Resolucion"] != "Resolucion_Electronica") {
        $nombre_fichero = $_SERVER["DOCUMENT_ROOT"] . 'IMAGENES/QR/' . $data["Codigo_Qr"];
    } else {
        $nombre_fichero = $_SERVER["DOCUMENT_ROOT"] . 'ARCHIVOS/FACTURACION_ELECTRONICA/' . $data["Codigo_Qr"];
    }

    if ($data["Codigo_Qr"] == '' || !file_exists($nombre_fichero)) {
        $cabecera .= '<img src="' . $_SERVER["DOCUMENT_ROOT"] . 'assets/images/sinqr.png' . '" style="max-width:100%;margin-top:-10px;" />';
    } else {
        $cabecera .= '<img src="' . $nombre_fichero . '" style="max-width:100%;margin-top:-10px;" />';
    }

    $cabecera .= '</td>
                    </tr>
                <tr>
                     <td colspan="3" style="font-size:9px;">
                     NO SOMOS GRANDES CONTRIBUYENTES<br>
                     NO SOMOS AUTORETENEDORES DE RENTA<br>
                     POR FAVOR ABSTENERSE PRACTICAR RETENCIÓN EN LA FUENTE POR ICA,<BR>
                     SOMOS GRANDES CONTRIBUYENTES DE ICA EN BUCARAMANGA. RESOLUCIÓN 3831 DE 18/04/2022
                     </td>
                     <td colspan="1" style="font-size:9px;text-align:right;vertical-align:top;">
                     <strong >ORIGINAL &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong><br>
                     Página [[page_cu]] de [[page_nb]]
                     </td>
                </tr>
              </tbody>
            </table>

            <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin-top:8px;">
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Cliente:</strong>
                    </td>
                    <td colspan="3" style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                    ' . trim($cliente["NombreCliente"]) . '
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>N.I.T. o C.C.:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    ' . number_format($cliente["IdCliente"], 0, ",", ".") . '
                    </td>
                    
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Dirección:</strong>
                    </td>
                    <td style="font-size:8px;width:205px;vertical-align:middle;padding:2px;">
                    ' . trim($cliente["DireccionCliente"]) . '
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Teléfono:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    ' . $cliente["Telefono"] . '
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Forma de Pago:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    ' . $condicion_pago . '
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Ciudad: </strong>
                    </td>
                    <td colspan="3" style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                        ' . trim($cliente["CiudadCliente"]) . '
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Metodo de Pago:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    ' . $metodo_pago . '
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Paciente: </strong>
                    </td>
                    <td colspan="3" style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                        ' . trim($nombre_paciente) . ' - <strong>' . $regimen . ' - ' . $cliente['Eps'] . '</strong>
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Nº Documento:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    ' . $cliente["Numero_Documento"] . '
                    </td>
                </tr>
            </table>
            <hr style="border:1px dotted #ccc;width:700px;">';

$cabecera2 = '<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="' . $_SERVER["DOCUMENT_ROOT"] . '/assets/images/LogoProh.jpg" style="width:50px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:330px;font-weight:thin;font-size:10px;line-height:11px;">
                    <strong>' . $config["Nombre_Empresa"] . '</strong><br>
                    N.I.T.: ' . $config["NIT"] . '<br>
                    ' . $config["Direccion"] . '<br>
                    Bucaramanga, Santander<br>
                    TEL: ' . $config["Telefono"] . '<br>
                    RESPONSABLE DE IVA
                  </td>
                  <td style="width:250px;text-align:right">
                        ' . $codigos . '
                  </td>
                  <td style="width:130px;">';


        if ($fact["Tipo_Resolucion"] != "Resolucion_Electronica") {
            $nombre_fichero = $_SERVER["DOCUMENT_ROOT"] . 'IMAGENES/QR/' . $data["Codigo_Qr"];
        } else {
            $nombre_fichero = $_SERVER["DOCUMENT_ROOT"] . 'ARCHIVOS/FACTURACION_ELECTRONICA/' . $data["Codigo_Qr"];
        }

        if ($data["Codigo_Qr"] == '' || !file_exists($nombre_fichero)) {
            $cabecera2 .= '<img src="' . $_SERVER["DOCUMENT_ROOT"] . 'assets/images/sinqr.png' . '" style="max-width:100%;margin-top:-10px;" />';
        } else {
            $cabecera2 .= '<img src="' . $nombre_fichero . '" style="max-width:100%;margin-top:-10px;" />';
        }

        $cabecera2 .= '</td>
                </tr>
                <tr>
                <td colspan="3" style="font-size:9px;">
                NO SOMOS GRANDES CONTRIBUYENTES<br>
                NO SOMOS AUTORETENEDORES DE RENTA<br>
                POR FAVOR ABSTENERSE PRACTICAR RETENCIÓN EN LA FUENTE POR ICA,<BR>
                SOMOS GRANDES CONTRIBUYENTES DE ICA EN BUCARAMANGA. RESOLUCIÓN 3831 DE 18/04/2022
                </td>
                <td colspan="1" style="font-size:9px;text-align:right;vertical-align:top;">
                <strong>CLIENTE &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong><br>
                Página [[page_cu]] de [[page_nb]]
                </td>
           </tr>
              </tbody>
            </table>

            <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin-top:8px;">
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Cliente:</strong>
                    </td>
                    <td colspan="3" style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                    ' . trim($cliente["NombreCliente"]) . '
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>N.I.T. o C.C.:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    ' . number_format($cliente["IdCliente"], 0, ",", ".") . '
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Dirección:</strong>
                    </td>
                    <td style="font-size:8px;width:200px;vertical-align:middle;padding:2px;">
                    ' . trim($cliente["DireccionCliente"]) . '
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Teléfono:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    ' . $cliente["Telefono"] . '
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Forma de Pago:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    ' . $condicion_pago . '
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Ciudad: </strong>
                    </td>
                    <td colspan="3" style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                        ' . trim($cliente["CiudadCliente"]) . '
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Metodo de Pago:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    ' . $metodo_pago . '
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Paciente: </strong>
                    </td>
                    <td colspan="3" style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                    ' . trim($nombre_paciente) . ' - <strong>' . $regimen . '</strong>
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Nº Documento:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    ' . $cliente["Numero_Documento"] . '
                    </td>
                </tr>
            </table>
            <hr style="border:1px dotted #ccc;width:700px;">';

$cabecera3 = '<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="' . $_SERVER["DOCUMENT_ROOT"] . '/assets/images/LogoProh.jpg" style="width:50px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:330px;font-weight:thin;font-size:10px;line-height:11px;">
                    <strong>' . $config["Nombre_Empresa"] . '</strong><br>
                    N.I.T.: ' . $config["NIT"] . '<br>
                    ' . $config["Direccion"] . '<br>
                    Bucaramanga, Santander<br>
                    TEL: ' . $config["Telefono"] . '<br>
                    RESPONSABLE DE IVA
                  </td>
                  <td style="width:250px;text-align:right">
                        ' . $codigos . '
                  </td>
                  <td style="width:130px;">';

    if ($fact["Tipo_Resolucion"] != "Resolucion_Electronica") {
        $nombre_fichero = $_SERVER["DOCUMENT_ROOT"] . 'IMAGENES/QR/' . $data["Codigo_Qr"];
    } else {
        $nombre_fichero = $_SERVER["DOCUMENT_ROOT"] . 'ARCHIVOS/FACTURACION_ELECTRONICA/' . $data["Codigo_Qr"];
    }

    if ($data["Codigo_Qr"] == '' || !file_exists($nombre_fichero)) {
        $cabecera3 .= '<img src="' . $_SERVER["DOCUMENT_ROOT"] . 'assets/images/sinqr.png' . '" style="max-width:100%;margin-top:-10px;" />';
    } else {
        $cabecera3 .= '<img src="' . $nombre_fichero . '" style="max-width:100%;margin-top:-10px;" />';
    }

    $cabecera3 .= '</td>
                    </tr>
                    <tr>
                    <td colspan="3" style="font-size:9px;">
                    NO SOMOS GRANDES CONTRIBUYENTES<br>
                    NO SOMOS AUTORETENEDORES DE RENTA<br>
                    POR FAVOR ABSTENERSE PRACTICAR RETENCIÓN EN LA FUENTE POR ICA,<BR>
                    SOMOS GRANDES CONTRIBUYENTES DE ICA EN BUCARAMANGA. RESOLUCIÓN 3831 DE 18/04/2022
                    </td>
                    <td colspan="1" style="font-size:9px;text-align:right;vertical-align:top;">
                    <strong >ARCHIVO &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong><br>
                    Página [[page_cu]] de [[page_nb]]
                    </td>
               </tr>
              </tbody>
            </table>

            <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin-top:8px;">
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Cliente:</strong>
                    </td>
                    <td colspan="3" style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                    ' . trim($cliente["NombreCliente"]) . '
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>N.I.T. o C.C.:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    ' . number_format($cliente["IdCliente"], 0, ",", ".") . '
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Dirección:</strong>
                    </td>
                    <td style="font-size:8px;width:200px;vertical-align:middle;padding:2px;">
                    ' . trim($cliente["DireccionCliente"]) . '
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Teléfono:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    ' . $cliente["Telefono"] . '
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Forma de Pago:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    ' . $condicion_pago . '
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Ciudad: </strong>
                    </td>
                    <td colspan="3" style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                        ' . trim($cliente["CiudadCliente"]) . '
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Metodo de Pago:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    ' . $metodo_pago . '
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Paciente: </strong>
                    </td>
                    <td colspan="3" style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                    ' . trim($nombre_paciente) . ' - <strong>' . $regimen . '</strong>
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Nº Documento:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    ' . $cliente["Numero_Documento"] . '
                    </td>
                </tr>
            </table>
            <hr style="border:1px dotted #ccc;width:700px;">';
/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
if ($func['Firma'] != '') {
    $imagen = '<img src="' . $MY_FILE . "DOCUMENTOS/" . $func["Identificacion_Funcionario"] . "/" . $func['Firma'] . '"  width="210"><br>';
} else {
    $imagen = '<br><br>______________________________<br>';
}
/* PIE DE PAGINA */

$pie = '<table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin-top:4px;">';
if ($fact["Tipo_Resolucion"] == "Resolucion_Electronica") {
    $pie .= '<tr>
    	   <td style="font-size:10px;width:770px;background:#c6c6c6;vertical-align:middle;padding:5px;text-align:center;">
    		<strong>CUFE: ' . $cliente["Cufe"] . '</strong>
    	   </td>
    	</tr>';
}
$pie .= '<tr>
		<td style="font-size:7px;width:778px;background:#f3f3f3;vertical-align:middle;padding:1px 5px;height:10px;">
			<strong>Resolución Facturación ' . ($fact["Tipo_Resolucion"] == "Resolucion_Electronica" ? 'Electrónica' : '') . ':</strong>
			Auorizaciòn de Facturacion # ' . $fact["Resolucion"] . ' Desde ' . fecha($fact["Fecha_Inicio"]) . ' Hasta ' . fecha($fact["Fecha_Fin"]) . '
			Habilita Del No. ' . $fact["Codigo"] . $fact["Numero_Inicial"] . ' Al No. ' . $fact["Codigo"] . $fact["Numero_Final"] . ' Actividad economica principal 4645<br>
            <strong>PROVEEDOR TECNOLOGICO</strong> - Productos Hospitalarios S.A.<br>
		</td>

	</tr>
	<tr>
	   <td style="font-size:7px;width:778px;background:#c6c6c6;vertical-align:middle;padding:1px 5px;text-align:center;">
		<strong>Esta Factura se asimila en sus efectos legales a una letra de cambio Art. 774 del Codigo de Comercio</strong>
	   </td>
	</tr>
	<tr>
	   <td style="font-size:7px;width:778px;background:#f3f3f3;vertical-align:middle;padding:1px 5px;">
		<strong>Nota:</strong> No se aceptan devoluciones de ningun medicamento de cadena de frio o controlados.<br>
		<strong>Cuentas Bancarias:</strong> ' . $config['Cuenta_Bancaria'] . '
	   </td>
	</tr>
</table>
<table>
 <tr>
 	<td style="font-size:8px;width:365px;vertical-align:middle;padding:2px 5px;text-align:center;background-image:url(' . $_SERVER["DOCUMENT_ROOT"] . 'assets/images/sello-proh.png);background-repeat:no-repeat;background-size:cover;background-position:center">
' . $imagen . '
 		Elaborado Por<br>' . $func["Nombres"] . " " . $func["Apellidos"] . '
 	</td>
 	<td style="font-size:8px;width:365px;vertical-align:middle;padding:2px 5px;text-align:center;">
 	<br><br>______________________________<br>
 		Recibí Conforme<br>
 	</td>
 </tr>
</table>
';

$col_desc = '';
$width_prod = 'width:430px;';
$width_prod2 = 'width:285px;';

if (!$band_homologo) {
    $col_desc = '<td style="font-size:7px;line-height:8px;background:#c6c6c6;text-align:center;">Descuento</td>';
    $width_prod = 'width:392px;';
    $width_prod2 = 'width:225px;';
}

$contenido = '<table  cellspacing="0" cellpadding="0" >
	        	    <tr>
	        		<td style="' . $width_prod . 'font-size:8px;background:#c6c6c6;text-align:center;">Descripción</td>
	        		<td style="font-size:8px;background:#c6c6c6;text-align:center;">Lote</td>
	        		<td style="font-size:8px;background:#c6c6c6;text-align:center;">F. Venc.</td>
	        		<td style="font-size:8px;background:#c6c6c6;text-align:center;">Und</td>
	        		<td style="font-size:8px;background:#c6c6c6;text-align:center;">Iva</td>
	        		' . $col_desc . '
	        		<td style="font-size:8px;background:#c6c6c6;text-align:center;">Precio</td>
	        		<td style="font-size:8px;background:#c6c6c6;text-align:center;">Total</td>
	        	    </tr>';
$total_iva = 0;
$total_desc = 0;
$subtotal = 0;
$subtotal_acum = 0;

$decimales = 2;

if ($cliente['Tipo_Valor'] == 'Cerrada') {
    $decimales = 0;
}

foreach ($productos as $prod) {

    // el utf8 evita que impriman la descripcion cuando existe algun caracter especial
    // linea 639  '.utf8_decode((trim($prod["producto"]).' | INV: '.trim($prod['Invima']).' | CUM: '.trim($prod['Cum']))).'
    $contenido .= '<tr>
	        		<td style="' . $width_prod . 'padding:2px 3px;font-size:7px;text-align:left;border:1px solid #c6c6c6;vertical-align:middle;line-height:9px;height:auto;">
                    ' . ((trim($prod["producto"]) . ' | INV: ' . trim($prod['Invima']) . ' | CUM: ' . trim($prod['Cum']))) . '

	        		</td>

	        		<td style="padding:2px 3px;font-size:7px;text-align:center;border:1px solid #c6c6c6;width:50px;vertical-align:middle;line-height:9px;height:auto;">
	        		' . $prod["Lote"] . '
	        		</td>

	        		<td style="padding:2px 3px;font-size:7px;text-align:center;border:1px solid #c6c6c6;width:35px;vertical-align:middle;line-height:9px;height:auto;">
	        		' . fecha($prod["Vencimiento"]) . '
	        		</td>

	        		<td style="padding:2px 3px;font-size:7px;text-align:center;border:1px solid #c6c6c6;width:20px;vertical-align:middle;line-height:9px;height:auto;">
	        		' . number_format($prod["Cantidad"], 0, "", ".") . '
	        		</td>

	        		<td style="padding:2px 3px;font-size:7px;;text-align:center;border:1px solid #c6c6c6;vertical-align:middle;line-height:9px;height:auto;">
	        		' . ($prod["Impuesto"]) . '%
                    </td>';

    $descuento = 0;
    if (!$band_homologo) {
        $decimales_dcto = 2;

        if ($cliente["IdCliente"] == 890500890) { // SI ES NORTE DE SANTANDER
            $decimales_dcto = 0;
        }
        $descuento = $prod["Descuento"]; // Cambio 16/09/2019 - KENDRY | Se hace este cambio porque los de facturacion pidieron que el calculo se hiciera con decimales incluidos (si los trae) pero en la visual en el caso de IDS se le quite.
        $descuentoFormatt = number_format($prod["Descuento"], $decimales_dcto, ".", "");
        $contenido .= '<td style="padding:2px 3px;font-size:7px;;text-align:center;border:1px solid #c6c6c6;vertical-align:middle;width:50px;line-height:9px;height:auto;">
                        $ ' . number_format($descuentoFormatt, $decimales_dcto, ",", ".") . '
                        </td>';
    }

    $precio = number_format($prod['Precio'], $decimales, ".", "");
    $subtotal = $precio * $prod['Cantidad'];
    $total_iva += (($subtotal - ($descuento * $prod['Cantidad'])) * ($prod["Impuesto"] / 100));

    $contenido .= '<td style="padding:2px 3px;font-size:7px;text-align:right;border:1px solid #c6c6c6;vertical-align:middle;width:60px;line-height:9px;height:auto;">
	        		$ ' . number_format($prod["Precio"], $decimales, ",", ".") . '
	        		</td>
	        		<td style="padding:2px 3px;font-size:7px;text-align:right;border:1px solid #c6c6c6;width:60px;vertical-align:middle;line-height:9px;height:auto;">
	        		$ ' . number_format($subtotal, $decimales, ",", ".") . '
	        		</td>
                    </tr>';
    $total_desc += $descuento * $prod["Cantidad"];
    $subtotal_acum += $subtotal;
}
$total_dcto = number_format($total_desc, $decimales, ".", "");

if ($cliente["IdCliente"] == 890500890) { // SI ES NORTE DE SANTANDER
    $total_dcto = number_format($total_desc, 0, "", "");
}
$subtotal_acum = number_format($subtotal_acum, $decimales, ".", "");
$total_iva = number_format($total_iva, $decimales, ".", "");
$total = $subtotal_acum + $total_iva - $total_dcto - $cliente['Cuota'];

$numero = number_format($total, $decimales, '.', '');
$letras = NumeroALetras::convertir($numero);

$contenido .= '</table>
	             <table style="margin-top:8px;margin-bottom:0;" >
	             	<tr>
	             	   <td colspan="2" style="padding:2px 3px;font-size:8px;border:1px solid #c6c6c6;width:585px;"><strong>Valor a Letras:</strong><br>' . $letras . ' PESOS MCTE</td>
	             	   <td rowspan="3" style="padding:3px;font-size:8px;border:1px solid #c6c6c6;width:150px;">
	             	   	<table cellpadding="0" cellspacing="0">
	             	   	   <tr>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;line-height:9px;"><strong>Subtotal</strong></td>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;text-align:right;line-height:9px;">$ ' . number_format($subtotal_acum, $decimales, ",", ".") . '</td>
                            </tr>';

if (!$band_homologo) {
    $decimales_dcto = $decimales;

    if ($cliente["IdCliente"] == 890500890) { // SI ES NORTE DE SANTANDER
        $decimales_dcto = 0;
    }
    $contenido .= '<tr>
                            <td style="padding:0 3px;font-size:8px;width:80px;line-height:9px;"><strong>Dcto.</strong></td>
                            <td style="padding:0 3px;font-size:8px;width:80px;text-align:right;line-height:9px;">$ ' . number_format($total_desc, $decimales_dcto, ",", ".") . '</td>
                        </tr>';
}

$contenido .= '<tr>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;line-height:9px;"><strong>Iva 19%</strong></td>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;text-align:right;line-height:9px;">$ ' . number_format($total_iva, $decimales, ",", ".") . '</td>
	             	   	   </tr>
	             	   	   <tr>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;line-height:9px;"><strong>Cuotas Moderadora</strong></td>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;text-align:right;line-height:9px;">$ ' . number_format($cliente['Cuota'], $decimales, ",", ".") . '</td>
	             	   	   </tr>
	             	   	   <tr>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;line-height:9px;"><strong>Total</strong></td>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;text-align:right;line-height:9px;"><strong>$ ' . number_format($total, $decimales, ",", ".") . '</strong></td>
	             	   	   </tr>
	             	   	</table>
	             	   </td>
	             	</tr>
	             	<tr>
	             	   <td style="padding:2px 3px;font-size:7px;border:1px solid #c6c6c6;width:446px;line-height:8px;">
	             	   	<strong>Observaciones:</strong><br>
	             	   	' . $cliente["observacion"] . '
	             	   </td>
	             	   <td style="padding:2px 3px;font-size:7px;border:1px solid #c6c6c6;width:90px;line-height:8px;"></td>
	             	</tr>
                 </table>';

/* $contenido .= '

<div style="border:1px solid #000;width:793px;height:10px;text-align:center">

<img src="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/SelloNoPos.png" alt="Pro-H Software" />

</div>

'; */

$marca_agua = '';

// var_dump($cliente['EPS']);

if ($data['Estado_Factura'] == 'Anulada') {
    $marca_agua = 'backimg="' . $_SERVER["DOCUMENT_ROOT"] . 'assets/images/anulada.png"';
} elseif ($cliente["Tipo_Servicio"] != 'EVENTO' && $cliente['tipo'] != "Homologo" && $cliente['EPS'] != "Positiva" && $cliente['EPS'] != "POSITIVA") {
    $marca_agua = 'backimg="' . $_SERVER["DOCUMENT_ROOT"] . 'assets/images/SelloNoPos.png" backimgw="50%"';
}

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = //$style.
'<page backtop="230px" backbottom="140px" ' . $marca_agua . '>
		        <page_header>' . $cabecera . '</page_header>
                <div class="page-content"><br>' . $contenido . '</div>
		        <page_footer>' . $pie . '</page_footer>
            </page>

            <page backtop="230px" backbottom="140px" ' . $marca_agua . '>
		        <page_header>' . $cabecera2 . '</page_header>
                <div class="page-content"><br>' . $contenido . '</div>
		        <page_footer>' . $pie . '</page_footer>
            </page>

            <page backtop="230px" backbottom="140px" ' . $marca_agua . '>
            <page_header>' . $cabecera3 . '</page_header>
            <div class="page-content"><br>' . $contenido . '</div>
		        <page_footer>' . $pie . '</page_footer>
            </page>';

/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

try {
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('L', array(215.9, 170), 'Es', true, 'UTF-8', array(2, 0, 2, 0));
    $html2pdf->writeHTML($content, false);
    if ($ruta == '') {
        $direc = $cliente["Codigo"] . '.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
        $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
    } else {
        $direc = $ruta; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
        $html2pdf->Output($direc, "F");
    }

} catch (HTML2PDF_exception $e) {
    echo $e;
    exit;
}
