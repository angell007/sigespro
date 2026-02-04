<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/NumeroALetra.php');




/* FUNCIONES BASICAS */
function fecha($str)
{
	$parts = explode(" ",$str);
	$date = explode("-",$parts[0]);
	return $date[2] . "/". $date[1] ."/". $date[0];
}
/* FIN FUNCIONES BASICAS*/

/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$style='<style>
   
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
    line-height: 11px;
}
</style>'; 

$query="SELECT Id_Factura,Tipo FROM Factura WHERE Codigo IN ('NP33230','NP33234','NP33235','NP33237','NP33238','NP33240','NP33241','NP33246','NP33247','NP33248','NP33249','NP33254','NP33255','NP33256','NP33257','NP33261','NP33264','NP33267','NP33268','NP33269','NP33270','NP33271','NP33275','NP33277','NP33278','NP33280','NP33281','NP33282','NP33283','NP33284','NP33286','NP33288','NP33289','NP33290','NP33291','NP33295','NP33296','NP33297','NP33300','NP33301','NP33303','NP33308','NP33309','NP33312','NP33314','NP33316','NP33318','NP33323','NP33325','NP33326','NP33328','NP33330','NP33333','NP33335','NP33340','NP33342','NP33345','NP33355','NP33357','NP33358','NP33359','NP33363','NP33365','NP33369','NP33370','NP33371','NP33372','NP33373','NP33376','NP33378','NP33379','NP33381','NP33383','NP33384','NP33386','NP33390','NP33393','NP33395','NP33399','NP33400','NP33401','NP33402','NP33404','NP33406','NP33410','NP33415','NP33416','NP33417','NP33418','NP33419','NP33423','NP33425','NP33426','NP33428','NP33429','NP33430','NP33431','NP33432','NP33434','NP33435','NP33437','NP33438','NP33439','NP33441','NP33442','NP33443','NP33445','NP33449','NP33452','NP33454','NP33455','NP33458','NP33459','NP33461','NP33462','NP33474','NP33582','NP33583','NP33587','NP33594','NP33595','NP33596','NP33597','NP33598','NP33607','NP33612','NP33613','NP33616','NP33617','NP33618','NP33619','NP33620','NP33622','NP33623','NP33625','NP33626','NP33627','NP33630','NP33631','NP33632','NP33633','NP33634','NP33636','NP33638','NP33639','NP33640','NP33641','NP33647','NP33651','NP33653','NP33654','NP33655','NP33656','NP33657','NP33658','NP33659','NP33660','NP33661','NP33662','NP33663','NP33664','NP33665','NP33666','NP33667','NP33668','NP33669','NP33670','NP33671','NP33672','NP33674','NP33675','NP33676','NP33677','NP33678','NP33679','NP33680','NP33681','NP33682','NP33683','NP33685','NP33686','NP33687','NP33688','NP33689','NP33690','NP33691','NP33694','NP33695','NP33696','NP33697','NP33699','NP33700','NP33701','NP33702','NP33703','NP33705','NP33707','NP33709','NP33710','NP33712','NP33713','NP33724','NP33725','NP33726','NP33727','NP33729','NP36003','NP36154','NP36158','NP36197','NP36203','NP36206','NP36218','NP36233','NP36234','NP36242','NP36285','NP36289','NP36309','NP36320','NP36327','NP36330','NP36331','NP36332','NP36336','NP36352','NP36359','NP36363','NP36379','NP36382','NP36399','NP36401','NP36403','NP36412','NP36414','NP36415','NP36417','NP36424','NP36427','NP36428','NP36429','NP36435','NP36438','NP36440','NP36442','NP36445','NP36446','NP36447','NP36449','NP36450','NP36452','NP36454','NP36457','NP36459','NP36460') LIMIT 240,40"; 
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$facturas = $oCon->getData();
unset($oCon);
foreach ($facturas as $key => $factura) {

    $tipo=$factura['Tipo'];
   /* DATOS DEL ARCHIVO A MOSTRAR */
$oItem = new complex("Factura","Id_Factura",$$factura['Id_Factura']);
$data = $oItem->getData();
unset($oItem);

$oItem = new complex("Resolucion","Id_Resolucion",$data['Id_Resolucion']);
$fact = $oItem->getData();
unset($oItem);

/* $oItem = new complex("Cliente","Id_Cliente",$data["Id_Cliente"]);
$cliente = $oItem->getData();
unset($oItem); */

$band_homologo = false;

$query = 'SELECT 
            FV.Codigo_Qr, FV.Fecha_Documento as Fecha , FV.Observacion_Factura as observacion, FV.Codigo as Codigo,IF(FV.Condicion_Pago=1,"CONTADO",FV.Condicion_Pago) as Condicion_Pago , FV.Fecha_Pago as Fecha_Pago , FV.Tipo as tipo,
            C.Id_Cliente as IdCliente ,C.Nombre as NombreCliente, C.Direccion as DireccionCliente, (SELECT Nombre FROM Municipio WHERE Id_Municipio = C.Ciudad) as CiudadCliente, C.Credito as CreditoCliente, C.Cupo as CupoCliente, (SELECT CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido, CONCAT("- ", UPPER(IF(Id_Regimen=1,"Contributivo","Subsidiado")))) FROM Paciente WHERE Id_Paciente=D.Numero_Documento) AS Nombre_Paciente, D.Numero_Documento, FV.Cuota, D.Codigo AS Cod_Dis, (SELECT Nombre FROM Tipo_Servicio WHERE Id_Tipo_Servicio = D.Id_Tipo_Servicio) AS Tipo_Servicio, C.Tipo_Valor,(SELECT  P.Eps FROM Dispensacion D INNER JOIN (SELECT EPS, Id_Paciente FROM Paciente ) P ON D.Numero_Documento=P.Id_Paciente   WHERE Id_Dispensacion=FV.Id_Dispensacion) as Eps
          FROM Factura FV
          INNER JOIN Dispensacion D ON FV.Id_Dispensacion = D.Id_Dispensacion 
          INNER JOIN Cliente C
           ON FV.Id_Cliente = C.Id_Cliente
          WHERE FV.Id_Factura = '.$factura['Id_Factura'] ;

$oCon= new consulta();
$oCon->setQuery($query);
$cliente = $oCon->getData();
unset($oCon);

if ($tipo && $tipo == 'Homologo') {
    // Consultar si tiene homologo

    $query = 'SELECT FV.Id_Factura,
    FV.Codigo_Qr, FV.Fecha_Documento as Fecha , FV.Observacion_Factura as observacion, FV.Codigo as Codigo,IF(FV.Condicion_Pago=1,"CONTADO",FV.Condicion_Pago) as Condicion_Pago , FV.Fecha_Pago as Fecha_Pago , FV.Tipo as tipo,
    C.Id_Cliente as IdCliente ,C.Nombre as NombreCliente, C.Direccion as DireccionCliente, (SELECT Nombre FROM Municipio WHERE Id_Municipio = C.Ciudad) as CiudadCliente, C.Credito as CreditoCliente, C.Cupo as CupoCliente, C.Tipo_Valor, (SELECT  P.Eps FROM Dispensacion D INNER JOIN (SELECT EPS, Id_Paciente FROM Paciente ) P ON D.Numero_Documento=P.Id_Paciente   WHERE Id_Dispensacion=FV.Id_Dispensacion) as Eps
    FROM Factura FV
    INNER JOIN Cliente C
    ON FV.Id_Cliente = C.Id_Cliente
    WHERE FV.Id_Factura_Asociada = '.$factura['Id_Factura'] ;

    $oCon= new consulta();
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
    IFNULL(I.Fecha_Vencimiento, PFV.Fecha_Vencimiento) as Vencimiento, 
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
   LEFT JOIN Inventario I
   ON I.Id_Inventario = PD.Id_Inventario  WHERE PFV.Id_Factura =  '.$factura['Id_Factura'] ;
} else {
    $query = 'SELECT 
    CONCAT_WS(" ",P.Nombre_Comercial, P.Presentacion, P.Concentracion, " (", P.Principio_Activo,") ", P.Cantidad," ", P.Unidad_Medida ) as producto, 
    P.Invima,
    P.Id_Producto, 
    P.Codigo_Cum as Cum, 
    IFNULL(I.Fecha_Vencimiento, PFV.Fecha_Vencimiento) as Vencimiento, 
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
   LEFT JOIN Inventario I
   ON I.Id_Inventario = PD.Id_Inventario  WHERE PFV.Id_Factura =  '.$cliente['Id_Factura'] ;
}

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$productos = $oCon->getData();
unset($oCon); 


$oItem = new complex("Funcionario","Identificacion_Funcionario",$data["Id_Funcionario"]);
$func = $oItem->getData();
unset($oItem);


/* FIN DATOS DEL ARCHIVO A MOSTRAR */

ob_start(); // Se Inicializa el gestor de PDF

/* HOJA DE ESTILO PARA PDF*/
$tipo="Factura";

/* FIN HOJA DE ESTILO PARA PDF*/

if (!$band_homologo) {
    $query5 = 'SELECT SUM(Subtotal) as TotalFac FROM Producto_Factura WHERE Id_Factura = '.$factura['Id_Factura'] ;
} else {
    $query5 = 'SELECT SUM(Subtotal) as TotalFac FROM Producto_Factura WHERE Id_Factura = '.$cliente['Id_Factura'] ;
}

$oCon= new consulta();
$oCon->setQuery($query5);
$totalFactura = $oCon->getData();
unset($oCon);

$titulo = $band_homologo ? 'Factura de Venta (HOMOLOGO)' : 'Factura de Venta ' . ($cliente['tipo'] == 'Homologo' ? '(HOMOLOGO)' : '');

$codigos ='
    <span style="margin:-5px 0 0 0;font-size:13px;line-height:13px;">'.$titulo.'</span>
    <h3 style="margin:0 0 0 0;font-size:15px;line-height:15px;">'.$cliente["Codigo"].'</h3>
    <h5 style="margin:5px 0 0 0;font-size:8px;line-height:8px;">F. Expe.:'.fecha($cliente["Fecha"]).'</h5>
    <h4 style="margin:5px 0 0 0;font-size:8px;line-height:8px;">F. Venc.:'.fecha($cliente["Fecha_Pago"]).'</h4>
    <h4 style="margin:0 0 0 0;font-size:13px;line-height:13px">'.$cliente["Cod_Dis"].'</h4>
    <h4 style="margin:0 0 0 0;font-size:13px;line-height:13px">'.$cliente["Tipo_Servicio"].'</h4>
';


$condicion_pago = $cliente["Condicion_Pago"] == "CONTADO" ? $cliente["Condicion_Pago"] : $cliente["Condicion_Pago"] . " Días";

$nombre_paciente = utf8_decode(explode('-',$cliente["Nombre_Paciente"])[0]);
$regimen = explode('-',$cliente["Nombre_Paciente"])[1];

        
/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/LogoProh.jpg" style="width:50px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:460px;font-weight:thin;font-size:10px;line-height:11px;">
                    <strong>'.$config["Nombre_Empresa"].'</strong><br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    Bucaramanga, Santander<br>
                    TEL: '.$config["Telefono"].'<br>
                    REGIMEN COMÚN
                  </td>
                  <td style="width:150px;text-align:right;">
                        '.$codigos.'
                  </td>
                  <td style="width:100px;">
                  <img src="'.($cliente["Codigo_Qr"] =='' ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$cliente["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-8px;" />
                  </td>
                </tr>
                <tr>
                     <td colspan="2" style="font-size:9px;">
                     NO SOMOS GRANDES CONTRIBUYENTES<br>
                     NO SOMOS AUTORETENEDORES DE RENTA
                     </td>
                     <td colspan="2" style="font-size:9px;text-align:right;vertical-align:top;">
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
                    <td style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                    '.trim($cliente["NombreCliente"]).'
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>N.I.T. o C.C.:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    '.number_format($cliente["IdCliente"],0,",",".").'
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Dirección:</strong>
                    </td>
                    <td style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                    '.trim($cliente["DireccionCliente"]).'
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Teléfono:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    '.$cliente["Telefono"].'
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Ciudad: </strong>
                    </td>
                    <td style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                        '.trim($cliente["CiudadCliente"]).'
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Cond. Pago:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    '.$condicion_pago .'
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Paciente: </strong>
                    </td>
                    <td style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                        '.trim($nombre_paciente) . ' - <strong>' .$regimen.' - ' .$cliente['Eps'].'</strong>
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Nº Documento:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    '. $cliente["Numero_Documento"] .'
                    </td>
                </tr>
            </table>
            <hr style="border:1px dotted #ccc;width:700px;">';
            
   $cabecera2='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'/assets/images/LogoProh.jpg" style="width:50px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:460px;font-weight:thin;font-size:10px;line-height:11px;">
                    <strong>'.$config["Nombre_Empresa"].'</strong><br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    Bucaramanga, Santander<br>
                    TEL: '.$config["Telefono"].'<br>
                    REGIMEN COMÚN
                  </td>
                  <td style="width:150px;text-align:right">
                        '.$codigos.'
                  </td>
                  <td style="width:100px;">
                  <img src="'.($cliente["Codigo_Qr"] =='' ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$cliente["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-8px;" />
                  </td>
                </tr>
                <tr>
                     <td colspan="2" style="font-size:9px">
                     NO SOMOS GRANDES CONTRIBUYENTES<br>
                     NO SOMOS AUTORETENEDORES DE RENTA
                     </td>
                     <td colspan="2" style="font-size:9px;text-align:right;">
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
                    <td style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                    '.trim($cliente["NombreCliente"]).'
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>N.I.T. o C.C.:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    '.number_format($cliente["IdCliente"],0,",",".").'
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Dirección:</strong>
                    </td>
                    <td style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                    '.trim($cliente["DireccionCliente"]).'
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Teléfono:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    '.$cliente["Telefono"].'
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Ciudad: </strong>
                    </td>
                    <td style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                        '.trim($cliente["CiudadCliente"]).'
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Cond. Pago:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    '.$condicion_pago.'
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Paciente: </strong>
                    </td>
                    <td style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                    '.trim($nombre_paciente) . ' - <strong>' .$regimen.'</strong>
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Nº Documento:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    '.$cliente["Numero_Documento"].'
                    </td>
                </tr>
            </table>
            <hr style="border:1px dotted #ccc;width:700px;">';
            
            $cabecera3='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'/assets/images/LogoProh.jpg" style="width:50px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:460px;font-weight:thin;font-size:10px;line-height:11px;">
                    <strong>'.$config["Nombre_Empresa"].'</strong><br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    Bucaramanga, Santander<br>
                    TEL: '.$config["Telefono"].'<br>
                    REGIMEN COMÚN
                  </td>
                  <td style="width:150px;text-align:right">
                        '.$codigos.'
                  </td>
                  <td style="width:100px;">
                  <img src="'.($cliente["Codigo_Qr"] =='' ? $_SERVER["DOCUMENT_ROOT"].'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"].'IMAGENES/QR/'.$cliente["Codigo_Qr"] ).'" style="max-width:100%;margin-top:-8px;" />
                  </td>
                </tr>
                <tr>
                     <td colspan="2" style="font-size:9px">
                     NO SOMOS GRANDES CONTRIBUYENTES<br>
                     NO SOMOS AUTORETENEDORES DE RENTA
                     </td>
                     <td colspan="2" style="font-size:9px;text-align:right;">
                     <strong>ARCHIVO &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong><br>
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
                    <td style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                    '.trim($cliente["NombreCliente"]).'
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>N.I.T. o C.C.:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    '.number_format($cliente["IdCliente"],0,",",".").'
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Dirección:</strong>
                    </td>
                    <td style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                    '.trim($cliente["DireccionCliente"]).'
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Teléfono:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    '.$cliente["Telefono"].'
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Ciudad: </strong>
                    </td>
                    <td style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                        '.trim($cliente["CiudadCliente"]).'
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Cond. Pago:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    '.$condicion_pago.'
                    </td>
                </tr>
                <tr>
                    <td style="font-size:8px;width:60px;vertical-align:middle;padding:2px;">
                    <strong>Paciente: </strong>
                    </td>
                    <td style="font-size:8px;width:510px;vertical-align:middle;padding:2px;">
                    '.trim($nombre_paciente) . ' - <strong>' .$regimen.'</strong>
                    </td>
                    <td style="font-size:8px;width:85px;vertical-align:middle;padding:2px;">
                    <strong>Nº Documento:</strong>
                    </td>
                    <td style="font-size:8px;width:110px;vertical-align:middle;padding:2px;">
                    '.$cliente["Numero_Documento"].'
                    </td>
                </tr>
            </table>
            <hr style="border:1px dotted #ccc;width:700px;">'; 
/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
if($func['Firma']!=''){
    $imagen='<img src="'.$MY_FILE . "DOCUMENTOS/".$func["Identificacion_Funcionario"]."/".$func['Firma'].'"  width="110"><br>';
}else{
    $imagen='<br><br>______________________________<br>';
}
/* PIE DE PAGINA */

$pie='<table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin-top:4px;">
	<tr>
		<td style="font-size:7px;width:778px;background:#f3f3f3;vertical-align:middle;padding:1px 5px;height:10px;">
			<strong>Resolución Facturación:</strong> Autorizacion de Facturacion # '.$fact["Resolucion"].' Desde '.fecha($fact["Fecha_Inicio"]).' Hasta '.fecha($fact["Fecha_Fin"]).' Habilita Del No. '.$fact["Numero_Inicial"].' Al No. '.$fact["Numero_Final"].' Actividad economica principal 4645<br>
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
		<strong>Cuentas Bancarias:</strong> Banco Corpbanca Cta Cte 229 032 776 - Banco Occidente 657 034 583 - Bancolombia Cta Cte 302 786 049 52
	   </td>
	</tr>
</table>
<table>
 <tr>
 	<td style="font-size:8px;width:365px;vertical-align:middle;padding:2px 5px;text-align:center;background-image:url('.$_SERVER["DOCUMENT_ROOT"].'assets/images/sello-proh.png);background-repeat:no-repeat;background-size:cover;background-position:center">
'.$imagen.'
 		Elaborado Por<br>'.$func["Nombres"]." ".$func["Apellidos"].'
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
	        		<td style="'.$width_prod.'font-size:8px;background:#c6c6c6;text-align:center;">Descripción</td>
	        		<td style="font-size:8px;background:#c6c6c6;text-align:center;">Lote</td>
	        		<td style="font-size:8px;background:#c6c6c6;text-align:center;">F. Venc.</td>
	        		<td style="font-size:8px;background:#c6c6c6;text-align:center;">Und</td>
	        		<td style="font-size:8px;background:#c6c6c6;text-align:center;">Iva</td>
	        		'.$col_desc.'
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
                
	        	    foreach($productos as $prod){
                        
	        	    	$contenido.='<tr>
	        		<td style="'.$width_prod.'padding:2px 3px;font-size:7px;text-align:left;border:1px solid #c6c6c6;vertical-align:middle;line-height:9px;height:auto;">
                    '.utf8_decode((trim($prod["producto"]).' | INV: '.trim($prod['Invima']).' | CUM: '.trim($prod['Cum']))).'
	        		</td>
	        		<td style="padding:2px 3px;font-size:7px;text-align:center;border:1px solid #c6c6c6;width:50px;vertical-align:middle;line-height:9px;height:auto;"> 
	        		'.$prod["Lote"].'
	        		</td>
	        		<td style="padding:2px 3px;font-size:7px;text-align:center;border:1px solid #c6c6c6;width:35px;vertical-align:middle;line-height:9px;height:auto;">
	        		'.fecha($prod["Vencimiento"]).'
	        		</td>
	        		<td style="padding:2px 3px;font-size:7px;text-align:center;border:1px solid #c6c6c6;width:20px;vertical-align:middle;line-height:9px;height:auto;">
	        		'.number_format($prod["Cantidad"],0,"",".").'
	        		</td>
	        		<td style="padding:2px 3px;font-size:7px;;text-align:center;border:1px solid #c6c6c6;vertical-align:middle;line-height:9px;height:auto;">
	        		'.($prod["Impuesto"]).'%
                    </td>';
                    
                    $descuento = 0;
                    if (!$band_homologo) {
                        $decimales_dcto = 2;

                        if ($cliente["IdCliente"] == 890500890) { // SI ES NORTE DE SANTANDER
                            $decimales_dcto = 0;
                        }
                        $descuento = $prod["Descuento"]; // Cambio 16/09/2019 - KENDRY | Se hace este cambio porque los de facturacion pidieron que el calculo se hiciera con decimales incluidos (si los trae) pero en la visual en el caso de IDS se le quite.
                        $descuentoFormatt = number_format($prod["Descuento"],$decimales_dcto,".","");
                        $contenido .= '<td style="padding:2px 3px;font-size:7px;;text-align:center;border:1px solid #c6c6c6;vertical-align:middle;width:50px;line-height:9px;height:auto;">
                        $ '.number_format($descuentoFormatt,$decimales_dcto,",",".").'
                        </td>';
                    }

                    $precio = number_format($prod['Precio'],$decimales,".","");
                    $subtotal = $precio * $prod['Cantidad'];
                    $total_iva += (($subtotal-($descuento*$prod['Cantidad']))*($prod["Impuesto"]/100));
	        		
	        		$contenido .= '<td style="padding:2px 3px;font-size:7px;text-align:right;border:1px solid #c6c6c6;vertical-align:middle;width:60px;line-height:9px;height:auto;">
	        		$ '.number_format($prod["Precio"],$decimales,",",".").'
	        		</td>
	        		<td style="padding:2px 3px;font-size:7px;text-align:right;border:1px solid #c6c6c6;width:60px;vertical-align:middle;line-height:9px;height:auto;">
	        		$ '.number_format($subtotal,$decimales,",",".").'
	        		</td>
                    </tr>';
                    $total_desc += $descuento * $prod["Cantidad"];
                    $subtotal_acum += $subtotal;
                    }
                    $total_dcto = number_format($total_desc,$decimales,".","");

                    if ($cliente["IdCliente"] == 890500890) { // SI ES NORTE DE SANTANDER
                        $total_dcto = number_format($total_desc,0,"","");
                    }
                    $subtotal_acum = number_format($subtotal_acum,$decimales,".","");
                    $total_iva = number_format($total_iva,$decimales,".","");
                    $total = $subtotal_acum+$total_iva-$total_dcto-$cliente['Cuota'];
                    
                    $numero = number_format($total, $decimales, '.','');
                    $letras = NumeroALetras::convertir($numero);
                    

	             $contenido.='</table>
	             <table style="margin-top:8px;margin-bottom:0;" >
	             	<tr>
	             	   <td colspan="2" style="padding:2px 3px;font-size:8px;border:1px solid #c6c6c6;width:585px;"><strong>Valor a Letras:</strong><br>'.$letras.' PESOS MCTE</td>
	             	   <td rowspan="3" style="padding:3px;font-size:8px;border:1px solid #c6c6c6;width:150px;">
	             	   	<table cellpadding="0" cellspacing="0">
	             	   	   <tr>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;line-height:9px;"><strong>Subtotal</strong></td>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;text-align:right;line-height:9px;">$ '.number_format($subtotal_acum,$decimales,",",".").'</td>
                            </tr>';
                               
                        if (!$band_homologo) {
                            $decimales_dcto = $decimales;

                            if ($cliente["IdCliente"] == 890500890) { // SI ES NORTE DE SANTANDER
                                $decimales_dcto = 0;
                            }
                            $contenido .= '<tr>
                            <td style="padding:0 3px;font-size:8px;width:80px;line-height:9px;"><strong>Dcto.</strong></td>
                            <td style="padding:0 3px;font-size:8px;width:80px;text-align:right;line-height:9px;">$ '.number_format($total_desc,$decimales_dcto,",",".").'</td>
                        </tr>';
                        }
	             	   	   
                        
	             	   	 $contenido .='<tr>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;line-height:9px;"><strong>Iva 19%</strong></td>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;text-align:right;line-height:9px;">$ '.number_format($total_iva,$decimales,",",".").'</td>
	             	   	   </tr>
	             	   	   <tr>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;line-height:9px;"><strong>Cuotas Moderadora</strong></td>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;text-align:right;line-height:9px;">$ '.number_format($cliente['Cuota'],$decimales,",",".").'</td>
	             	   	   </tr>
	             	   	   <tr>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;line-height:9px;"><strong>Total</strong></td>
	             	   		<td style="padding:0 3px;font-size:8px;width:80px;text-align:right;line-height:9px;"><strong>$ '.number_format($total,$decimales,",",".").'</strong></td>
	             	   	   </tr>
	             	   	</table>
	             	   </td>
	             	</tr>
	             	<tr>
	             	   <td style="padding:2px 3px;font-size:7px;border:1px solid #c6c6c6;width:446px;line-height:8px;">
	             	   	<strong>Observaciones:</strong><br>
	             	   	'.$cliente["observacion"].'
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

if ($data['Estado_Factura'] == 'Anulada') {
    $marca_agua = 'backimg="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/anulada.png"';
} elseif ($cliente["Tipo_Servicio"] != '' && $cliente['tipo']!="Homologo") {
    $marca_agua = 'backimg="'.$_SERVER["DOCUMENT_ROOT"].'assets/images/SelloNoPos.png" backimgw="50%"';
}             
	             
/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="190px" backbottom="105px" '.$marca_agua.'>
		<page_header>'.
                    $cabecera.
		'</page_header>
		<page_footer>'.$pie.'</page_footer>
                <div class="page-content">
                <br>
	             '.$contenido.'
               </div>
            </page>
            
            <page backtop="190px" backbottom="105px" '.$marca_agua.'>
		<page_header>'.
                    $cabecera2.
		'</page_header>
		<page_footer>'.$pie.'</page_footer>
                <div class="page-content"><br>
	             '.$contenido.'
               </div>
            </page>
            
            <page backtop="190px" backbottom="105px" '.$marca_agua.'>
		<page_header>'.
                    $cabecera3.
		'</page_header>
		<page_footer>'.$pie.'</page_footer>
                <div class="page-content"><br>
	             '.$contenido.'
               </div>
            </page>';
            
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/


try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
   $html2pdf = new HTML2PDF('L', array(215.9,140), 'Es', true, 'UTF-8', array(2, 0, 2, 0));
   $html2pdf->writeHTML($content);
   $direc = $_SERVER["DOCUMENT_ROOT"].'/ARCHIVOS/IMPRIMIR/'. $cliente["Codigo"].'.pdf';
    // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
   $html2pdf->Output($direc,'F'); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

}
?>