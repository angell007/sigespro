<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
require_once '../../class/html2pdf.class.php';

$tipo = (isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : '');
$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');

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
$oItem = new complex($tipo, "Id_" . $tipo, $id);
$data = $oItem->getData();
unset($oItem);

$query = "SELECT *, (SELECT CONCAT(F.Nombres,' ',F.Apellidos) FROM Funcionario F WHERE F.Identificacion_Funcionario=R.Fase_1) as Fase1, (SELECT CONCAT(F.Nombres,' ',F.Apellidos) FROM Funcionario F WHERE F.Identificacion_Funcionario=R.Fase_2) as Fase2,(SELECT Firma FROM Funcionario WHERE Identificacion_Funcionario=R.Fase_1) as Firma1, (SELECT Firma FROM Funcionario WHERE Identificacion_Funcionario=R.Fase_2) as Firma2 FROM Remision R WHERE R.Id_Remision=$id";

$oCon = new consulta();
$oCon->setQuery($query);
$data = $oCon->getData();
unset($oCon);
/* FIN DATOS DEL ARCHIVO A MOSTRAR */

ob_start(); // Se Inicializa el gestor de PDF

/* HOJA DE ESTILO PARA PDF*/
$style = '<style>
.page-content{
width:750px;
}
.row{
display:inlinie-block;
width:750px;
}
.td-header{
    font-size:15px;
    line-height: 20px;
}
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/

/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */
switch ($tipo) {
    case 'Remision':{
            $query = "SELECT
        PR.Lote,
        PR.Fecha_Vencimiento,
        IFNULL(CONCAT(P.Principio_Activo, ' ', P.Presentacion, ' ', P.Concentracion, ' (', P.Nombre_Comercial, ') ', P.Cantidad, ' ', P.Unidad_Medida,                        ' '),
                CONCAT(P.Nombre_Comercial, ' LAB-', P.Laboratorio_Comercial)) AS Nombre_Producto,
        PR.Cantidad,
        PR.Precio,
        PR.Descuento,
        PR.Impuesto,
        PR.Subtotal,
        P.Laboratorio_Generico,
        P.Embalaje,
        I.Grupo
        FROM
            Producto_Remision PR
        INNER JOIN Producto P ON PR.Id_Producto = P.Id_Producto
        LEFT JOIN
            (SELECT G.Nombre as Grupo, I.Id_Inventario_Nuevo
                FROM  Inventario_Nuevo I
                INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba
                INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba = E.Id_Grupo_Estiba
            ) I ON I.Id_Inventario_Nuevo = PR.Id_Inventario_Nuevo
        WHERE
            PR.Id_Remision = $id
        ORDER BY Nombre_Producto";

            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $productos = $oCon->getData();
            unset($oCon);

            // header("Content-type:application/json");

            // echo json_encode($productos); exit;

            $productos_ = array_filter($productos, function ($k, $v) {
                return $k['Grupo'] !== 'NEVERA';
            }, ARRAY_FILTER_USE_BOTH);
            $productos_nev = array_filter($productos, function ($k, $v) {
                return $k['Grupo'] == 'NEVERA';
            }, ARRAY_FILTER_USE_BOTH);
            $productos = array_values($productos_);
            $productos_nev = array_values($productos_nev);

            $oItem = new complex($data["Tipo_Origen"], "Id_" . $data["Tipo_Origen"], $data["Id_Origen"]);
            $origen = $oItem->getData();
            unset($oItem);

            $oItem = new complex($data["Tipo_Destino"], "Id_" . $data["Tipo_Destino"], $data["Id_Destino"]);
            $destino = $oItem->getData();
            unset($oItem);

            $oItem = new complex('Funcionario', "Identificacion_Funcionario", $data["Identificacion_Funcionario"]);
            $elabora = $oItem->getData();
            unset($oItem);

            $codigos = '
            <h3 style="margin:5px 0 0 0;font-size:22px;line-height:22px;">' . $data["Codigo"] . '</h3>
            <h5 style="margin:5px 0 0 0;font-size:16px;line-height:16px;">' . fecha($data["Fecha"]) . '</h5>
            <h4 style="margin:5px 0 0 0;font-size:14px;line-height:14px;">Tipo ' . $data["Tipo"] . '</h4>
        ';
            $contenido = '<table style="">
            <tr>
                <td style="width:350px; padding-right:10px;">
                    <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;">
                        <tr>
                            <td colspan="2" style="font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Origen</td>
                        </tr>
                        <tr>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;">
                            ' . $origen["Nombre"] . '
                            </td>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;">
                            ' . $origen["Direccion"] . '
                            </td>
                        </tr>
                        <tr>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;">
                            <strong>Tel.:</strong> ' . $origen["Telefono"] . '
                            </td>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;">
                            <strong>Correo:</strong> ' . $origen["Correo"] . '
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width:350px; padding-leftt:10px;">
                    <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;">
                        <tr>
                            <td colspan="2" style="font-size:10px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Destino</td>
                        </tr>
                        <tr>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;">
                            ' . $destino["Nombre"] . '
                            </td>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;">
                            ' . $destino["Direccion"] . '
                            </td>
                        </tr>
                        <tr>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;">
                            <strong>Tel.:</strong> ' . $destino["Telefono"] . '
                            </td>
                            <td style="font-size:10px;width:175px;background:#f3f3f3;border:1px solid #cccccc;">
                            <strong>Correo:</strong> ' . $destino["Correo"] . '
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table style="margin-top:10px">
            <tr>
                <td style="font-size:10px;width:710px;background:#e9eef0;border-radius:5px;padding:8px;">
                    <strong>Observaciones</strong><br>
                    ' . $data["Observaciones"] . '
                </td>
            </tr>
        </table>
        <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th style="width:10px;background:#cecece;;border:1px solid #cccccc;"></th>
                    <th style="width:400px;max-width:400px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                        Producto
                    </th>
                    <th style="width:100px;max-width:100px;font-weight:bold;background:#cecece;;border:1px solid #cccccc;">
                        Laboratorio
                    </th>
                    <th style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                        Lote
                    </th>
                    <th style="width:70px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                        F. Vencimiento
                    </th>
                    <th style="width:50px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">
                        Cant.
                    </th>
                </tr>
            </thead>
            <tbody>
            ';

            $max = 0;
            foreach ($productos as $prod) {$max++;
                $contenido .= '<tr>
                    <td scope="row" style="width:10px;background:#f3f3f3;border:1px solid #cccccc;text-align:center;">' . $max . '</td>
                    <td style="padding:3px 2px;width:400px;max-width:400px;font-size:9px;text-align:left;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;">' . $prod["Nombre_Producto"] . '<strong> EMB: </strong>' . $prod["Embalaje"] . '</td>
                    <td style="padding:3px 2px;width:100px;max-width:100px;font-size:9px;text-align:left;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;">' . $prod["Laboratorio_Generico"] . '</td>
                    <td style="width:70px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">' . $prod["Lote"] . '</td>
                    <td style="width:70px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">' . $prod["Fecha_Vencimiento"] . '</td>
                    <td style="width:50px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">' . $prod["Cantidad"] . '</td>
                </tr>';
            }
            if (count($productos_nev) > 0) {
                // $contenido .= '</tbody>';
                // $contenido .= '</table>';
                $contenido .= ' 
        
                            <tr>
                                <td colspan="6" style="font-weight:bold;background:#cecece;text-align:center;border-top:1px solid #ffffff;">
                                    NEVERA
                                </td>
                            </tr>
                        ';
                foreach ($productos_nev as $prod) {$max++;
                    $contenido .= '<tr>
                    <td scope="row" style="width:10px;background:#f3f3f3;border:1px solid #cccccc;text-align:center;">' . $max . '</td>
                    <td style="padding:3px 2px;width:400px;max-width:400px;font-size:9px;text-align:left;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;">' . $prod["Nombre_Producto"] . '<strong> EMB: </strong>' . $prod["Embalaje"] . '</td>
                     <td style="padding:3px 2px;width:100px;max-width:100px;font-size:9px;text-align:left;background:#f3f3f3;border:1px solid #cccccc;word-break: break-all !important;">' . $prod["Laboratorio_Generico"] . '</td>
                    <td style="width:70px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">' . $prod["Lote"] . '</td>
                    <td style="width:70px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">' . $prod["Fecha_Vencimiento"] . '</td>
                    <td style="width:50px;font-size:9px;word-wrap: break-word;text-align:center;background:#f3f3f3;border:1px solid #cccccc;">' . $prod["Cantidad"] . '</td>
                </tr>';
                }
            }
            $contenido .= '</tbody>';
            $contenido .= '</table>';

            $imagen = '';
            $firma1 = '';
            $firma2 = '';

            if ($elabora['Firma'] != '' ) {
                $imagen = '<img src="' . $MY_FILE . "DOCUMENTOS/" . $elabora["Identificacion_Funcionario"] . "/" . $elabora['Firma'] . '"  width="230">';
            } else {
                $imagen = '<br><br><br><br>';
            }
            if ($data['Firma1'] != '' && $data['Fase_1'] != '') {

                $firma1 = '<img src="' . $MY_FILE . "DOCUMENTOS/" . $data["Fase_1"] . "/" . $data['Firma1'] . '"  width="230">';
            } else {
                $firma1 = '<br><br><br><br>';
            }
            if ($data['Firma2'] != '' && $data['Fase_2'] != '') {
                $firma2 = '<img src="' . $MY_FILE . "DOCUMENTOS/" . $data["Fase_2"] . "/" . $data['Firma2'] . '"  width="230">';
            } else {
                $firma2 = '<br><br><br><br>';
            }

            $contenido .= '<table style="margin-top:10px;font-size:10px;">
                        <tr>
                            <td style="width:240px;border:1px solid #cccccc;">
                                <strong>Persona Elabor贸</strong><br>' . $imagen . '<br>
                                ' . $elabora["Nombres"] . " " . $elabora["Apellidos"] . '
                            </td>
                            <td style="width:240px;border:1px solid #cccccc;">
                                    <strong>Alistamiento Fase 1</strong>' . $firma1 . '<br>
                                    ' . $data["Fase1"] . '
                            </td>
                            <td style="width:240px;border:1px solid #cccccc;">
                                <strong>Alistamiento Fase 2</strong>' . $firma2 . '<br> ' . $data["Fase2"] . '
                            </td>
                        </tr>
                        </table>';

            break;
        }
}
/* FIN SWITCH*/
// echo $contenido; exit;
/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera = '<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="' . $_SERVER["DOCUMENT_ROOT"] . 'IMAGENES/LOGOS/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:410px;font-weight:thin;font-size:14px;line-height:20px;">
                    ' . $config["Nombre_Empresa"] . '<br>
                    N.I.T.: ' . $config["NIT"] . '<br>
                    ' . $config["Direccion"] . '<br>
                    TEL: ' . $config["Telefono"] . '
                  </td>
                  <td style="width:150px;text-align:right">
                        ' . $codigos . '
                  </td>
                  <td style="width:100px;">
                  <img src="' . (($data["Codigo_Qr"] == '' || !file_exists($_SERVER["DOCUMENT_ROOT"] . 'IMAGENES/QR/' . $data["Codigo_Qr"])) ? $_SERVER["DOCUMENT_ROOT"] . 'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"] . 'IMAGENES/QR/' . $data["Codigo_Qr"]) . '" style="max-width:100%;margin-top:-10px;" />
                  </td>
                </tr>
              </tbody>
            </table><hr style="border:1px dotted #ccc;width:730px;">';
/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="0mm" backbottom="0mm">
                <div class="page-content" >' .
                    $cabecera .
                    $contenido . '
                </div>
            </page>';
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
// echo $content;exit;
try {
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('P', 'A4', 'Es', true, 'UTF-8', array(5, 5, 5, 5));
    $html2pdf->writeHTML($content);
    $direc = $data["Codigo"] . '.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
} catch (HTML2PDF_exception $e) {
    echo $e;
    exit;
}
