<?php

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/NumeroALetra.php');
class FacturaVentaPdf
{
    private $id;
    private $ruta;

    /* FUNCIONES BASICAS */
    public function fecha($str)
    {
        $parts = explode(" ", $str);
        $date = explode("-", $parts[0]);
        return $date[2] . "/" . $date[1] . "/" . $date[0];
    }
    /* FIN FUNCIONES BASICAS*/
    public function __construct($id, $ruta)
    {
        $this->id = $id;
        $this->ruta = $ruta;
    }
    public function generarPdf()
    {
        /* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
        $oItem = new complex('Configuracion', "Id_Configuracion", 1);
        $config = $oItem->getData();
        unset($oItem);
        /* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */


        /* DATOS DEL ARCHIVO A MOSTRAR */
        $oItem = new complex("Factura_Administrativa", "Id_Factura_Administrativa", $this->id);
        $data = $oItem->getData();
        unset($oItem);

        /* $oItem = new complex("Resolucion","Id_Resolucion",2);
$fact = $oItem->getData();
unset($oItem); */

        $query = "SELECT * FROM Resolucion WHERE Id_Resolucion=" . $data["Id_Resolucion"];

        $oCon = new consulta();
        $oCon->setQuery($query);
        $fact = $oCon->getData();
        unset($oCon);

        /* $oItem = new complex("Cliente","Id_Cliente",$data["Id_Cliente"]);
$cliente = $oItem->getData();
unset($oItem); */

        $query = 'SELECT 
FV.Fecha as Fecha, FV.Cufe, FV.Observaciones as observacion, FV.Codigo as Codigo, FV.Codigo_Qr, IF(FV.Condicion_Pago=1,"CONTADO",FV.Condicion_Pago) as Condicion_Pago , FV.Fecha_Pago as Fecha_Pago,
C.Id_Cliente as IdCliente ,C.Nombre as NombreCliente, C.Direccion as DireccionCliente, M.Nombre as CiudadCliente, C.Credito as CreditoCliente, C.Celular AS Telefono, FV.Id_Factura_Administrativa
FROM Factura_Administrativa FV
INNER JOIN Cliente C ON FV.Id_Cliente = C.Id_Cliente
INNER JOIN Municipio M ON C.Ciudad=M.Id_Municipio
AND FV.Id_Factura_Administrativa =' . $this->id;

        $oCon = new consulta();
        $oCon->setQuery($query);
        $cliente = $oCon->getData();
        unset($oCon);



        $query = 'SELECT 
PFV.Descripcion as producto, 
PFV.Cantidad,
PFV.Precio,
PFV.Subtotal,
PFV.Descuento,
PFV.Id_Descripcion_Factura_Administrativa as idPFV,
CONCAT(PFV.Impuesto,"%") as Impuesto
FROM Descripcion_Factura_Administrativa PFV
WHERE PFV.Id_Factura_Administrativa =' . $this->id;

        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo("Multiple");
        $productos = $oCon->getData();
        unset($oCon);


        $oItem = new complex("Funcionario", "Identificacion_Funcionario", $data["Identificacion_Funcionario"]);
        $func = $oItem->getData();
        unset($oItem);


        /* FIN DATOS DEL ARCHIVO A MOSTRAR */

        ob_start(); // Se Inicializa el gestor de PDF

        /* HOJA DE ESTILO PARA PDF*/
        $tipo = "Factura";
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

        $query5 = 'SELECT SUM(Subtotal) as TotalFac FROM Descripcion_Factura_Administrativa WHERE Id_Factura_Administrativa = ' . $this->id;
        $oCon = new consulta();
        $oCon->setQuery($query5);
        $totalFactura = $oCon->getData();
        unset($oCon);



        $codigos = '
    <span style="margin:-5px 0 0 0;font-size:16px;line-height:16px;">Factura Electrónica de Venta</span>
    <h3 style="margin:0 0 0 0;font-size:22px;line-height:22px;">' . $data["Codigo"] . '</h3>
    <h5 style="margin:5px 0 0 0;font-size:11px;line-height:11px;">F. Expe.:' . $this->fecha($data["Fecha_Documento"]) . '</h5>
    <h5 style="margin:5px 0 0 0;font-size:11px;line-height:11px;">H. Expe.:&nbsp;&nbsp;&nbsp;'.date('H:i:s', strtotime($data['Fecha_Documento'])).'</h5>
    <h4 style="margin:5px 0 0 0;font-size:11px;line-height:11px;">F. Venc.:' . $this->fecha($data["Fecha_Pago"]) . '</h4>
';

        $condicion_pago = $cliente["Condicion_Pago"] == "CONTADO" ? $cliente["Condicion_Pago"] : " CREDITO A $cliente[Condicion_Pago] Días";
        $metodo_pago = $cliente["Condicion_Pago"] == "CONTADO" ? "TRANSFERENCIA DÉBITO" : "TRANSFERENCIA CRÉDITO";

        /* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
        $cabecera = '<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="' . $_SERVER["DOCUMENT_ROOT"] . 'assets/images/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:310px;font-weight:thin;font-size:13px;line-height:18px;">
                    <strong>' . $config["Nombre_Empresa"] . '</strong><br> 
                    N.I.T.: ' . $config["NIT"] . '<br> 
                    ' . $config["Direccion"] . '<br> 
                    Bucaramanga, Santander<br>
                    TEL: ' . $config["Telefono"] . '
                  </td>
                  <td style="width:250px;text-align:right">
                        ' . $codigos . '
                  </td>
                  <td style="width:150px;">
                  <img src="' . ($data["Codigo_Qr"] !== '' ? $_SERVER["DOCUMENT_ROOT"] . 'assets/images/sinqr.png' : $_SERVER["DOCUMENT_ROOT"] . 'ARCHIVOS/FACTURACION_ELECTRONICA/' . $data["Codigo_Qr"]) . '" style="max-width:100%;margin-top:-10px;" />
                  </td>
                </tr>
                <tr>
                <tr>
                    <td colspan="3" style="font-size:11px;">
                        NO SOMOS GRANDES CONTRIBUYENTES<br>
                        NO SOMOS AUTORETENEDORES DE RENTA<br>
                        POR FAVOR ABSTENERSE PRACTICAR RETENCIÓN EN LA FUENTE POR ICA,<BR>
                        SOMOS GRANDES CONTRIBUYENTES DE ICA EN BUCARAMANGA. RESOLUCIÓN 3831 DE 18/04/2022
                    </td>
                     <td colspan="1" style="font-size:11px;text-align:right;vertical-align:top;">
                     <strong >ORIGINAL &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong><br>
                     Página [[page_cu]] de [[page_nb]]
                     </td>
                </tr>
                </tr>
              </tbody>
            </table>
            
            <table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin-top:20px;">
                <tr>
                    <td style="font-size:10px;width:60px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Cliente:</strong>
                    </td>
                    <td colspan="3" style="font-size:10px;width:490PX;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    ' . trim($cliente["NombreCliente"]) . '
                    </td>
                    <td style="font-size:10px;width:90px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>N.I.T. o C.C.:</strong>
                    </td>
                    <td style="font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    ' . number_format($cliente["IdCliente"], 0, ",", ".") . '
                    </td>
                </tr>
                <tr>
                    <td style="font-size:10px;width:60px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Dirección:</strong>
                    </td>
                    <td style="font-size:10px;width:200px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    ' . trim($cliente["DireccionCliente"]) . '
                    </td>
                    <td style="font-size:10px;width:90px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Teléfono:</strong>
                    </td>
                    <td style="font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    ' . $cliente["Telefono"] . '
                    </td>
                    <td style="font-size:10px;width:90px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>FORMA DE Pago:</strong>
                    </td>
                    <td style="font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    ' . $condicion_pago . '
                    </td>
                </tr>
                <tr>
                    <td style="font-size:10px;width:60px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>Ciudad: </strong>
                    </td>
                    <td colspan="3"  style="font-size:10px;width:490PX;background:#f3f3f3;vertical-align:middle;padding:3px;">
                        ' . trim($cliente["CiudadCliente"]) . '
                    </td>
                    <td style="font-size:10px;width:90px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    <strong>MEDIO DE Pago:</strong>
                    </td>
                    <td style="font-size:10px;width:100px;background:#f3f3f3;vertical-align:middle;padding:3px;">
                    ' . $metodo_pago . '
                    </td>
                </tr>
            </table>
            <hr style="border:1px dotted #ccc;width:730px;">';

        /* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/


        /* PIE DE PAGINA */


        $pie = '<table cellspacing="0" cellpadding="0" style="text-transform:uppercase;margin:0px;">';
        if ($fact["Tipo_Resolucion"] == "Resolucion_Electronica") {
            $pie .= '<tr>
    	   <td style="font-size:10px;width:770px;background:#c6c6c6;vertical-align:middle;padding:5px;text-align:center;">
    		<strong>CUFE: ' . $cliente["Cufe"] . '</strong>
    	   </td>
    	</tr>';
        }
        $pie .= '
	<tr>
		<td style="font-size:10px;width:770px;background:#f3f3f3;vertical-align:middle;padding:5px;height:40px;">
			<strong>Resolución Facturación ' . ($fact["Tipo_Resolucion"] == "Resolucion_Electronica" ? 'Electrónica' : '') . ':</strong><br>
			Autorizacion de Facturacion # ' . $fact["Resolucion"] . '<br>
			Desde ' . $this->fecha($fact["Fecha_Inicio"]) . ' Hasta ' . $this->fecha($fact["Fecha_Fin"]) . '<br>
			Habilita Del No. ' . $fact["Codigo"] . $fact["Numero_Inicial"] . ' Al No. ' . $fact["Codigo"] . $fact["Numero_Final"] . '<br>
			Actividad economica principal 4645<br>
		</td>
	
	</tr>
	<tr>
	   <td style="font-size:10px;width:770px;background:#c6c6c6;vertical-align:middle;padding:5px;text-align:center;">
		<strong>Esta Factura se asimila en sus efectos legales a una letra de cambio Art. 774 del Codigo de Comercio</strong>
	   </td>
	</tr>
	<tr>
	   <td style="font-size:10px;width:770px;background:#f3f3f3;vertical-align:middle;padding:5px;">
		<strong>Cuentas Bancarias:</strong>' . $config['Cuenta_Bancaria'] . '
	   </td>
	</tr>
</table>
<table>
 <tr>
 	<td style="font-size:10px;width:355px;vertical-align:middle;padding:5px;text-align:center;">
 	<br><br>______________________________<br>
 		Elaborado Por<br>' . $func["Nombres"] . " " . $func["Apellidos"] . '
 	</td>
 	<td style="font-size:10px;width:355px;vertical-align:middle;padding:5px;text-align:center;">
 	<br><br>______________________________<br>
 		Recibí Conforme<br>
 	</td>
 </tr>
</table>
';

        $contenido = '<table  cellspacing="0" cellpadding="0" >
	        	    <tr>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">Descripción</td>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">Cant</td>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">Precio</td>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">Descuento</td>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">Iva</td>
	        		<td style="font-size:10px;background:#c6c6c6;text-align:center;">Total</td>
	        	    </tr>';
        $total_iva = 0;
        foreach ($productos as $prod) {
            $total_iva += ((($prod["Cantidad"] * $prod["Precio"]) - ($prod["Cantidad"] * $prod["Descuento"])) * (str_replace("%", "", $prod["Impuesto"]) / 100));
            $contenido .= '<tr>
	        		<td style="padding:4px;font-size:9px;text-align:left;border:1px solid #c6c6c6;width:460px;vertical-align:middle;">
	        		' . $prod["producto"] . '
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:center;border:1px solid #c6c6c6;width:20px;vertical-align:middle;">
	        		' . number_format($prod["Cantidad"], 0, "", ".") . '
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:right;border:1px solid #c6c6c6;vertical-align:middle;width:60px;">
	        		$ ' . number_format($prod["Precio"], 0, ",", ".") . '
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:right;border:1px solid #c6c6c6;vertical-align:middle;width:60px;">
	        		$ ' . number_format($prod["Descuento"], 0, ",", ".") . '
	        		</td>
	        		<td style="padding:4px;font-size:9px;;text-align:center;border:1px solid #c6c6c6;vertical-align:middle;">
	        		' . $prod["Impuesto"] . '
	        		</td>
	        		<td style="padding:4px;font-size:9px;text-align:right;border:1px solid #c6c6c6;width:60px;vertical-align:middle;">
	        		$ ' . number_format($prod["Subtotal"], 0, ",", ".") . '
	        		</td>
	        	    </tr>';
        }
        $total = $totalFactura['TotalFac'] + $total_iva;
        $numero = number_format($total, 0, '.', '');
        $letras = NumeroALetras::convertir($numero);
        $contenido .= '</table>
	             <table style="margin-top:20px;margin-bottom:0;">
	             	<tr>
	             	   <td colspan="2" style="padding:4px;font-size:9px;border:1px solid #c6c6c6;width:586px;"><strong>Valor a Letras:</strong><br>' . $letras . ' PESOS MCTE</td>
	             	   <td rowspan="3" style="padding:4px;font-size:9px;border:1px solid #c6c6c6;width:130px;">
	             	   	<table cellpadding="0" cellspacing="0">
	             	   	   <tr>
	             	   		<td style="padding:4px;font-size:9px;width:60px;"><strong>Subtotal</strong></td>
	             	   		<td style="padding:4px;font-size:9px;width:60px;text-align:right;">$ ' . number_format($totalFactura['TotalFac'] - $total_iva, 0, ",", ".") . '</td>
	             	   	   </tr>
	             	   	   <tr>
	             	   		<td style="padding:4px;font-size:9px;width:60px;"><strong>Dcto.</strong></td>
	             	   		<td style="padding:4px;font-size:9px;width:60px;text-align:right;">$ 0</td>
	             	   	   </tr>
	             	   	   <tr>
	             	   		<td style="padding:4px;font-size:9px;width:60px;"><strong>Iva 19%</strong></td>
	             	   		<td style="padding:4px;font-size:9px;width:60px;text-align:right;">$ ' . number_format($total_iva, 0, ",", ".") . '</td>
	             	   	   </tr>
	             	   	   <tr>
	             	   		<td style="padding:4px;font-size:9px;width:60px;"><strong>Total</strong></td>
	             	   		<td style="padding:4px;font-size:9px;width:60px;text-align:right;"><strong>$ ' . number_format($totalFactura['TotalFac'], 0, ",", ".") . '</strong></td>
	             	   	   </tr>
	             	   	</table>
	             	   </td>
	             	</tr>
	             	<tr>
	             	   <td style="padding:4px;font-size:9px;border:1px solid #c6c6c6;width:486px;">
	             	   	<strong>Obsrvaciones:</strong><br>
	             	   	' . $cliente["observacion"] . '
	             	   </td>
	             	   <td style="padding:4px;font-size:9px;border:1px solid #c6c6c6;width:90px;"></td>
	             	</tr>
               </table>';


        $marca_agua = '';

        if ($data['Estado'] == 'Anulada') {
            $marca_agua = 'backimg="' . $_SERVER["DOCUMENT_ROOT"] . 'assets/images/anulada.png"';
        }


        /* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
        $content = '<page backtop="305px" backbottom="185px" ' . $marca_agua . '>
                <page_header>' . $cabecera . '</page_header>
		        <page_footer>' . $pie . '</page_footer>
                <div class="page-content">' . $contenido . '</div>
            </page>';

        /* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/


        try {
            $html2pdf = new HTML2PDF('P', 'LETTER', 'Es', true, 'UTF-8', array(4, 4, 4, 4));
            $html2pdf->writeHTML($content);
            $direc = $this->ruta; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
            ob_clean();

            $html2pdf->Output($_SERVER['DOCUMENT_ROOT'] . $direc, "F"); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
        } catch (\Throwable $e) {
            echo $e->getMessage();
            exit;
        }
    }
}
