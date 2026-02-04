<?php
require_once(__DIR__ . '/../config/start.inc.php');
include_once('class.lista.php');
include_once('class.complex.php');
include_once('class.consulta.php');
require_once('class.qr.php');
require_once('class.php_mailer.php');
require_once('./helper/factura_elec_dis_helper.php');

$fact = new FacturaElectronica('Factura', 180667, 21);
echo json_encode($fact->GenerarFactura());


class FacturaElectronica
{
    private $resolucion = '', $factura = '', $configuracion = '', $productos = '', $cliente = '', $totales = '', $tipo_factura = '', $id_factura = '';

    function __construct($tipo_factura, $id_factura, $resolucion_facturacion)
    {
        $this->tipo_factura = $tipo_factura;
        $this->id_factura = $id_factura;
        self::getDatos($tipo_factura, $id_factura, $resolucion_facturacion);
    }

    function __destruct()
    {
    }

    function GenerarFactura()
    {
        $datos = $this->GeneraJson($this->tipo_factura);
        $respuesta_dian = $this->GetApi($datos);

        $cufe = $respuesta_dian["Cufe"];
        $qr = $this->GetQr($cufe);
        if (strpos($respuesta_dian["Respuesta"], "procesado anteriormente") !== false) {
            $estado = "true";
        } else {
            $estado = $respuesta_dian["Procesada"];
        }

        if ($estado == "true") {
            $oItem = new complex($this->tipo_factura, "Id_" . $this->tipo_factura, $this->id_factura);
            $oItem->Cufe = $cufe;
            $oItem->Codigo_Qr = $qr;
            $oItem->Procesada = $estado;
            $oItem->save();
            unset($oItem);

            $nit = $this->getNit();
            $ruta_nueva = $_SERVER['DOCUMENT_ROOT'] . "/ARCHIVOS/FACTURA_ELECTRONICA_PDF/" . $this->resolucion["resolution_id"];


            if (!file_exists($ruta_nueva)) {
                mkdir($ruta_nueva, 0777, true);
            }

            $nombre_factura = "fv" . $this->getNombre() . ".pdf";
            $ruta_fact =  $ruta_nueva . "/" . $nombre_factura;

            if ($this->tipo_factura == "Factura") {
                include('https://sigesproph.com.co/php/facturacion_electronica/factura_np_pdf.php?id=' . $this->id_factura . '&Ruta=' . $ruta_fact);
            } elseif ($this->tipo_factura == "Factura_Venta") {
                include('https://sigesproph.com.co/php/facturacion_electronica/factura_venta_pdf.php?id=' . $this->id_factura . '&Ruta=' . $ruta_fact);
            } elseif ($this->tipo_factura == "Factura_Capita") {
                include('https://sigesproph.com.co/php/facturacion_electronica/factura_capita_pdf.php?id=' . $this->id_factura . '&Ruta=' . $ruta_fact);
            } elseif ($this->tipo_factura == "Factura_Administrativa") {
                include('https://sigesproph.com.co/php/facturacion_electronica/factura_administrativa_pdf.php?id=' . $this->id_factura . '&Ruta=' . $ruta_fact);
            }
        }
        $respuesta["Json"] = $respuesta_dian["Json"];
        $respuesta["Enviado"] = $respuesta_dian["Enviado"];
        if ($respuesta_dian["Estado"] == "error") {

            $respuesta["Estado"] = "Error";
            $respuesta["Detalles"] = $respuesta_dian["Respuesta"];
            $data["Cufe"] = $cufe;
            $data["Qr"] = $qr;
            $respuesta["Datos"] = $data;
        } elseif ($respuesta_dian["Estado"] == "exito") {
            $respuesta["Respuesta_Correo"] = $this->EnviarMail($cufe, $qr, $respuesta_dian["Respuesta"]);

            $respuesta["Estado"] = "Exito";

            $respuesta["Detalles"] = $respuesta_dian["Respuesta"];
            $data["Cufe"] = $cufe;
            $data["Qr"] = $qr;
            $respuesta["Datos"] = $data;
        }
        return ($respuesta);
    }
    private function GetMunicipio($idMunicipio)
    {
        $query = 'SELECT municipalities_id FROM Municipio WHERE Id_Municipio = ' . $idMunicipio;
        $oCon = new consulta();
        $oCon->setQuery($query);
        $mun = $oCon->getData();
        return $mun['municipalities_id'];
    }
    private function EnviarMail($cufe, $qr, $dian)
    {

        $destino = (($this->cliente["Correo_Persona_Contacto"] != "" && $this->cliente["Correo_Persona_Contacto"] != "NULL") ? $this->cliente["Correo_Persona_Contacto"] : "facturacionelectronicacont@prohsa.com");
        //$destino="sistemas@prohsa.com";
        //$destino="augustoacarrillo@hotmail.com"; 
        $asunto = "Su Factura Electrónica: " . $this->factura["Codigo"];
        $contenido = $this->GetHtmlFactura($dian);
        $xml = $this->getXml();
        $fact = $this->getFact();

        $email = new EnviarCorreo();
        $respuesta = $email->EnviarFacturaDian($destino, $asunto, $contenido, $xml, $fact);

        return ($respuesta);
    }
    private function GetHtmlFactura($dian)
    {
        $html = '<!doctype html>
            <html>		
            <head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                <meta name="viewport" content="width=device-width" />
                
                <title>Facturación Electrónica - Productos Hospitalarios (Pro H) S.A</title>
                <style>
                    img{border:none;-ms-interpolation-mode:bicubic;max-width:100%}body{background-color:#f6f6f6;font-family:sans-serif;-webkit-font-smoothing:antialiased;font-size:14px;line-height:1.4;margin:0;padding:0;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%}.body{background-color:#f6f6f6;width:100%}.container{display:block;margin:0 auto!important;max-width:580px;padding:10px;width:580px}.content{box-sizing:border-box;display:block;margin:0 auto;max-width:580px;padding:10px}.main{background:#fff;border-radius:3px;width:100%}.wrapper{box-sizing:border-box;padding:20px}.content-block{padding-bottom:10px;padding-top:10px}.footer{clear:both;margin-top:10px;text-align:center;width:100%}.footer a,.footer p,.footer span,.footer td{color:#999;font-size:12px;text-align:center}h5{font-size:14px;font-weight:700;text-align:left;color:#3c5dc6}p{font-family:sans-serif;font-size:11px;font-weight:400;margin:0;margin-bottom:15px;text-align:justify}span{color:#000;font-family:sans-serif;font-weight:600}a{color:#3c5dc6;text-decoration:none}.logo{border:0;outline:0;text-decoration:none;display:block;text-align:center}.align-center{text-align:center!important}.preheader{color:transparent;display:none;height:0;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;visibility:hidden;width:0}.powered-by a{text-decoration:none;text-align:center!important}hr{border:0;border-bottom:1px solid #eeeef0;margin:8px 0}@media all{.ExternalClass{width:100%}.ExternalClass,.ExternalClass div,.ExternalClass font,.ExternalClass p,.ExternalClass span,.ExternalClass td{line-height:100%}.apple-link a{color:inherit!important;font-family:inherit!important;font-size:inherit!important;font-weight:inherit!important;line-height:inherit!important;text-decoration:none!important}#MessageViewBody a{color:inherit;text-decoration:none;font-size:inherit;font-family:inherit;font-weight:inherit;line-height:inherit}}
                </style>
            </head>
            
            <body class="">
                <span class="preheader">Factura Electronica ' . $this->factura["Codigo"] . '</span>
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body">
                    <tr>
                        <td>&nbsp;</td>
                        <td class="container">
                            <div class="content">		
                                <table role="presentatioran" class="main">		
                                    <tr>
                                        <td class="wrapper">
                                            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td>
                                                        <img alt="ProH" height="100" border="0" class="logo" src="https://sigesproph.com.co/assets/images/LogoProh.jpg" />
                                                        <hr>
                                                        <p>Estimado, <span>' . $this->cliente["Nombre"] . '</span></p>
                                                        <p>Ha recibido un documento electrónico generedo y enviado mediante el sistema de Facturación Electrónica de Productos Hospitalarios S.A. con la siguiente información:</p>
                                                        <hr>
                                                        <h5>Datos del Emisor</h5>
                                                        <hr>
                                                        <p><span>Nombre: </span>' . $this->configuracion["Nombre_Empresa"] . '</p>
                                                        <p><span>Identificación: </span>' . $this->configuracion["NIT"] . '</p>
                                                        <hr>
                                                        <h5>Información del Documento</h5>
                                                        <hr>
                                                        <p><span>Fecha: </span>' . $this->factura["Fecha_Documento"] . '</p>
                                                        <p><span>Tipo: Factura de Venta</span></p>
                                                        <p><span>Numero: </span>' . $this->factura["Codigo"] . '</p>
                                                        <p><span>Moneda: </span>COP</p>
                                                        <p><span>Valor Total: </span>$' . number_format(($this->totales["Total"] + $this->totales["Total_Iva"]), 2, ",", ".") . '</p>
                                                        <hr>
                                                        <h5>Respuesta de la DIAN</h5>
                                                        <hr>
                                                        <p>' . $dian . '</p>
                                                        <hr>
                                                        <p>Adjunto encontrará la representación gráfica del documento en formato PDF y el documento electrónico en formato XML.</p>
                                                        <p class="content-block powered-by">Nota: No responda este mensaje, ha sido enviado desde una dirección de correo electrónico no monitoreada.</p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
            
                                </table>
            
                                <div class="footer">
                                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td class="content-block">
                                                <span class="apple-link align-center">Productos Hospitalarios S.A</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="content-block powered-by align-center">
                                                Desarrollado por <a href="https://www.corvuslab.co/">Corvus Lab</a>.
                                            </td>
                                        </tr>
                                    </table>
                                </div>
            
                            </div>
                        </td>
                        <td>&nbsp;</td>
                    </tr>
                </table>
            </body>
            </html>';

        return ($html);
    }
    private function getXml()
    {
        $xml = '/home/sigespro/api-dian.sigesproph.com.co/api-dian/storage/app/xml/1/' . $this->resolucion["resolution_id"] . '/fv' . $this->getNombre() . '.xml';

        return ($xml);
    }
    private function getFact()
    {
        $fact =  $_SERVER['DOCUMENT_ROOT'] . "/ARCHIVOS/FACTURA_ELECTRONICA_PDF/" . $this->resolucion["resolution_id"] . "/fv" . $this->getNombre() . '.pdf';

        return ($fact);
    }
    private function GetApi($datos)
    {

        $login = 'facturacion@prohsa.com';
        $password = '804016084';
        $host = 'https://api-dian.sigesproph.com.co';
        $api = '/api';
        $version = '/ubl2.1';
        $modulo = '/invoice';
        $url = $host . $api . $version . $modulo;

        $data = json_encode($datos);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $headers = array(
            "Content-type: application/json",
            "Accept: application/json",
            "Cache-Control: no-cache",
            "Authorization: Basic " . base64_encode($login . ':' . $password),
            "Pragma: no-cache",
            "SOAPAction:\"" . $url . "\"",
            "Content-length: " . strlen($data),
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        //var_dump(curl_error($ch));
        if (curl_errno($ch)) {
            $respuesta["Estado"] = "error";
            $respuesta["Error"] = '# Error : ' . curl_error($ch);
            return  $respuesta;
        } elseif ($result) {
            $resp = json_encode($result);
            $json_output = json_decode($resp, true);
            $json_output = (array) json_decode($json_output, true);

            //var_dump($json_output);
            $mensaje = $json_output["message"];
            $respuesta["Cufe"] = $json_output["cufe"];
            $respuesta["Json"] = $json_output;
            $respuesta["Enviado"] = $datos;

            if (strpos($mensaje, "invalid") !== false) {
                $respuesta["Estado"] = "error";
                $respuesta["Respuesta"] = $json_output["errors"];
            } else {
                $r = $json_output["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"];
                $estado = $r["IsValid"];

                $respuesta["Procesada"] = $estado;
                if ($estado == "true") {
                    $respuesta["Estado"] = "exito";
                    $respuesta["Respuesta"] = $r["StatusDescription"] . " - " . $r["StatusMessage"];
                } else {
                    $respuesta["Estado"] = "error";
                    $respuesta["Respuesta"] = '';
                    foreach ($r["ErrorMessage"] as $e) {
                        $respuesta["Respuesta"] .= $e . " - ";
                    }
                    $respuesta["Respuesta"] .= $r["StatusMessage"];
                    $respuesta["Respuesta"] = trim($respuesta["Respuesta"], " - ");
                }
            }

            return $respuesta;
        }
    }

    private function ConsultaRespuesta($zipkey)
    {
        $login = 'facturacion@prohsa.com';
        $password = '804016084';
        $host = 'https://api-dian.sigesproph.com.co';
        $api = '/api';
        $version = '/ubl2.1';
        $modulo = '/status/zip/';
        $url = $host . $api . $version . $modulo . $zipkey;

        $data = json_encode($datos);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $headers = array(
            "Content-type: application/json",
            "Accept: application/json",
            "Cache-Control: no-cache",
            "Authorization: Basic " . base64_encode($login . ':' . $password),
            "Pragma: no-cache",
            "SOAPAction:\"" . $url . "\""
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $respuesta["Estado"] = "error";
            $respuesta["Error"] = '# Error : ' . curl_error($ch);
            return  $respuesta;
        }
        if ($result) {
            $resp = json_encode($result);
            $json_output = json_decode($resp, true);
            $json_output = (array) json_decode($json_output, true);

            //var_dump($json_output);

            $mensaje = $json_output["message"];

            if (strpos($mensaje, "invalid") !== false) {
                $respuesta["Estado"] = "error";
                $respuesta["Error"] = $json_output["errors"];
            } else {

                if (isset($json_output["ResponseDian"]["Envelope"]["Body"]["GetStatusZipResponse"]["GetStatusZipResult"]["DianResponse"][0])) {
                    $respu["IsValid"] = $json_output["ResponseDian"]["Envelope"]["Body"]["GetStatusZipResponse"]["GetStatusZipResult"]["DianResponse"][0]["IsValid"];
                    $respu["StatusCode"] = $json_output["ResponseDian"]["Envelope"]["Body"]["GetStatusZipResponse"]["GetStatusZipResult"]["DianResponse"][0]["StatusCode"];
                    $respu["StatusDescription"] = $json_output["ResponseDian"]["Envelope"]["Body"]["GetStatusZipResponse"]["GetStatusZipResult"]["DianResponse"][0]["StatusDescription"];
                } else {
                    $respu["IsValid"] = $json_output["ResponseDian"]["Envelope"]["Body"]["GetStatusZipResponse"]["GetStatusZipResult"]["DianResponse"]["IsValid"];
                    $respu["StatusCode"] = $json_output["ResponseDian"]["Envelope"]["Body"]["GetStatusZipResponse"]["GetStatusZipResult"]["DianResponse"]["StatusCode"];
                    $respu["StatusDescription"] = $json_output["ResponseDian"]["Envelope"]["Body"]["GetStatusZipResponse"]["GetStatusZipResult"]["DianResponse"]["StatusDescription"];
                }
                $respuesta["Estado"] = "exito";
                $respuesta["Respuesta"] = $respu;
            }

            return $respuesta;
        }
    }

    private function GeneraJson($tipo_factura)
    {

        $resultado["cufe_propio"] = $this->getCufe();
        $resultado["number"] = (int)str_replace($this->resolucion['Codigo'], "", $this->factura['Codigo']);;
        $resultado["type_document_id"] = 1;
        $resultado["resolution_id"] = $this->resolucion["resolution_id"];
        $resultado["date"] = date("Y-m-d", strtotime($this->factura["Fecha_Documento"]));
        $resultado["time"] = date("H:i:s", strtotime($this->factura["Fecha_Documento"]));
        //$resultado["send"]=true;
        $resultado["file"] = $this->getNombre();

        //nuevo
        $cliente['municipality_id'] =  (int)$this->GetMunicipio($this->cliente['Id_Municipio']);
        $cliente['country_id'] = 46;

        $cliente["identification_number"] = $this->cliente["Id_Cliente"];
        //$cliente["dv"]=$this->cliente["Id_Cliente"];

        $cliente["name"] = trim($this->cliente["Nombre"]);
        $cliente["phone"] = (($this->cliente["Telefono"] != "" && $this->cliente["Telefono"] != "NULL") ? trim($this->cliente["Telefono"]) : "0000000");
        // $cliente["phone"]="0000000";

        $cliente["type_organization_id"] = (($this->cliente["Tipo"] == "Juridico") ? 1 : 2); /* Juridica 1 - Natural 2*/
        $cliente["type_document_identification_id"] = (($this->cliente["Tipo_Identificacion"] == "NIT") ? 6 : 3); /* 6 NIT - 3 Cedula */

        if ($this->cliente["Tipo_Identificacion"] == "NIT") {
            $cliente["dv"] = $this->cliente["Digito_Verificacion"];
        }

        $cliente["type_regime_id"] = (($this->cliente["Regimen"] == "Comun") ? 2 : 1); /* 1 Simplificado - 2 Comun */
        // $cliente["type_liability_id"]=2;
        //

        $cliente["type_liability_id"] = 122;

        if ($this->cliente["Contribuyente"] == "Si") {
            $cliente["type_liability_id"] = 118;
        }

        if ($this->cliente["Regimen"] == "Simplificado") {
            $cliente["type_liability_id"] = 121;
        }


        if ($this->cliente["Autorretenedor"] == "Si") {
            $cliente["type_liability_id"] = 119;
        }


        $cliente["address"] = trim((($this->cliente["Direccion"] != "" && $this->cliente["Direccion"] != "NULL") ? trim($this->cliente["Direccion"]) : "SIN DIRECCION"));
        $cliente["email"] = trim((($this->cliente["Correo_Persona_Contacto"] != "" && $this->cliente["Correo_Persona_Contacto"] != "NULL") ? trim($this->cliente["Correo_Persona_Contacto"]) : "facturacionelectronica@prohsa.com"));
        $cliente["merchant_registration"] = "No Tiene";

        //NUEVO 
        $metodo_pago = [];
        //contado 2 efectivo 1
        $metodo_pago['payment_form_id'] = $this->factura['Condicion_Pago'] > 1 ?  2  : 1;

        $metodo_pago['payment_method_id'] = $this->factura['Condicion_Pago'] > 1 ?  30  : 31;
        $metodo_pago['payment_due_date'] = $this->factura['Fecha_Pago'];

        $metodo_pago['duration_measure'] = $this->factura['Condicion_Pago'];

        $resultado["customer"] = $cliente;

        $finales["line_extension_amount"] = number_format($this->totales["Total"], 2, ".", "");
        $finales["tax_exclusive_amount"] = number_format($this->totales["Total"], 2, ".", "");
        $finales["tax_inclusive_amount"] = number_format($this->totales["Total"] + $this->totales["Total_Iva"], 2, ".", "");
        $finales["allowance_total_amount"] = number_format($this->totales["Descuento"], 2, ".", "");
        $finales["charge_total_amount"] = 0;
        $finales["payable_amount"] = number_format($this->totales["Total"], 2, ".", "") + number_format($this->totales["Total_Iva"], 2, ".", "") - number_format($this->totales["Descuento"], 2, ".", "");

        $resultado["legal_monetary_totals"] = $finales;
        $j = -1;
        $produstos_finales = [];
        $base_imp = 0;
        $tot_imp = 0;

        $base_des = 0;
        $tot_des = 0;
        $descue = [];
        foreach ($this->productos as $pro) {
            $j++;

            $descuento = $pro["Cantidad"] * $pro["Descuento"];

            if ($tipo_factura == "Factura_Venta") {
                $tot = $pro["Cantidad"] * $pro["Precio_Venta"];
                $precio = $pro["Precio_Venta"];
            } else {
                $tot = $pro["Cantidad"] * $pro["Precio"];
                $precio = $pro["Precio"];
            }

            $descuentos[0]["charge_indicator"] = false;
            $descuentos[0]["allowance_charge_reason"] = 'Discount';
            $descuentos[0]["amount"] = number_format($descuento, 2, ".", "");
            $descuentos[0]["base_amount"] = number_format($tot, 2, ".", "");

            if ($descuento > 0) {
                $base_des += $tot;
                $tot_des += $descuento;

                $descue[$j]["discount_id"] = ($j + 1);
                $descue[$j]["charge_indicator"] = false;
                $descue[$j]["allowance_charge_reason"] = 'Discount';
                $descue[$j]["amount"] = number_format($descuento, 2, ".", "");
                $descue[$j]["base_amount"] = number_format($tot, 2, ".", "");
            }

            $imp = $tot * $pro["Impuesto"] / 100;
            if ($imp > 0) {
                $base_imp += $tot;
                $tot_imp += $imp;
            }

            $impuestos[0]["tax_id"] = 1;
            $impuestos[0]["tax_amount"] = number_format($imp, 2, ".", "");
            $impuestos[0]["taxable_amount"] = number_format($tot, 2, ".", "");
            $impuestos[0]["percent"] = $pro["Impuesto"];


            $productos_finales[$j]["unit_measure_id"] = 70;
            $productos_finales[$j]["invoiced_quantity"] = $pro["Cantidad"];
            $productos_finales[$j]["line_extension_amount"] = number_format($tot, 2, ".", "");

            $productos_finales[$j]["free_of_charge_indicator"] = false;
            $productos_finales[$j]["reference_price_id"] = 1;

            if ((int)$precio == 0) {
                $productos_finales[$j]["free_of_charge_indicator"] = true;
                $productos_finales[$j]["reference_price_id"] = 1;
                $productos_finales[$j]["price_amount"] = number_format(1, 2, ".", "");
                // $referencia["AlternativeConditionPrice"]=;
                // $productos_finales[$j]["PricingReference"]=; 

            } else {
                $productos_finales[$j]["free_of_charge_indicator"] = false;
                $productos_finales[$j]["reference_price_id"] = 1;
                $productos_finales[$j]["price_amount"] = number_format($precio, 2, ".", "");
            }

            $productos_finales[$j]["allowance_charges"] = $descuentos;


            $productos_finales[$j]["tax_totals"] = $impuestos;

            $productos_finales[$j]["description"] = trim($pro["Producto"]);
            $productos_finales[$j]["code"] = trim($pro["CUM"]);
            $productos_finales[$j]["type_item_identification_id"] = 3;

            $productos_finales[$j]["base_quantity"] = $pro["Cantidad"];
        }

        $impues[0]["tax_id"] = 1;
        $impues[0]["tax_amount"] = number_format($tot_imp, 2, ".", "");
        $impues[0]["taxable_amount"] = number_format($base_imp, 2, ".", "");
        $impues[0]["percent"] = "19";

        /*$descue[0]["charge_indicator"]=false;
                $descue[0]["discount_id"]=1;
                $descue[0]["allowance_charge_reason"]='Discount';
                $descue[0]["amount"]=number_format($tot_des,0,".","");
                $descue[0]["base_amount"]=number_format($base_des,0,".","");*/

        $this->getDataDis();
        exit;

        $resultado["tax_totals"] = $impues;
        $resultado["allowance_charges"] = $descue;
        $resultado["invoice_lines"] = $productos_finales;
        $resultado["payment_form"] = $metodo_pago;
        //var_dump($resultado);
        //exit;
        return ($resultado);
    }

    private function getCUFE()
    {
        $nit = self::getNit();
        $fecha = $this->factura['Fecha_Documento'];
        $neto = number_format($this->totales['Total'] + $this->totales['Total_Iva'], 2, ".", "");
        $variable = $this->factura['Codigo'] . "" . str_replace(" ", "", $fecha) . "-05:00" . number_format($this->totales['Total'], 2, ".", "") . "01" . number_format($this->totales['Total_Iva'], 2, ".", "") . "040.00030.00" . $neto . $nit . $this->cliente['Id_Cliente'] . $this->resolucion['Clave_Tecnica'] . '1';
        return hash('sha384', $variable);
    }

    private function getDatos($tipo_factura, $id_factura, $resolucion_facturacion)
    {

        $oItem = new complex("Resolucion", "Id_Resolucion", $resolucion_facturacion);
        $this->resolucion = $oItem->getData();
        unset($oItem);

        $oItem = new complex($tipo_factura, "Id_" . $tipo_factura, $id_factura);
        $this->factura = $oItem->getData();
        unset($oItem);

        $query = "SELECT C.*,(SELECT D.Nombre FROM Departamento D WHERE D.Id_Departamento=C.Id_Departamento) as Departamento, (SELECT M.Nombre FROM Municipio M WHERE M.Id_Municipio=C.Id_Municipio) as Ciudad FROM Configuracion C WHERE C.Id_Configuracion=1";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $this->configuracion = $oCon->getData();
        unset($oItem);

        /*  if($tipo_factura=="Factura_Administrativa"){
                    if($this->factura['Tipo_Cliente']=='Funcionario'){
                        $tipo_id = 'C.Identificacion_Funcionario';
                    }else{
                        $tipo_id = "C.Id_".$this->factura['Tipo_Cliente'];
                    }
                       $query="SELECT C.*,(SELECT D.Nombre FROM  Departamento D WHERE D.Id_Departamento=C.Id_Departamento) as Departamento,
                                (SELECT M.Nombre FROM Municipio M WHERE M.Id_Municipio=C.Id_Municipio) as Ciudad
                                FROM ".$this->factura['Tipo_Cliente']." C WHERE " .$tipo_id. " = ".$this->factura['Id_Cliente'];
           
                }else{
                 
                    $query="SELECT C.*,(SELECT D.Nombre FROM Departamento D WHERE D.Id_Departamento=C.Id_Departamento) as Departamento, (SELECT M.Nombre FROM Municipio M WHERE M.Id_Municipio=C.Id_Municipio) as Ciudad FROM Cliente C WHERE C.Id_Cliente=".$this->factura['Id_Cliente'];
           
                }
                $oCon=new consulta();
                $oCon->setQuery($query);
                $this->cliente=$oCon->getData();
                unset($oCon); */

        #CARLOS CARDONA ---------------

        if ($tipo_factura == "Factura_Administrativa") {

            $this->cliente = $this->getTercero();
        } else {

            $this->cliente = $this->getCliente();
        }




        if ($tipo_factura != "Factura_Capita" && $tipo_factura != "Factura_Administrativa") {
            $query = 'SELECT PF.*, P.Codigo_Cum as CUM, IFNULL(CONCAT(P.Nombre_Comercial, " - ",P.Principio_Activo, " ", P.Cantidad,"", P.Unidad_Medida, " " , P.Presentacion," (LAB- ", P.Laboratorio_Comercial,") ", P.Invima, " CUM:", P.Codigo_Cum, " - Lote: ",PF.Lote), CONCAT(P.Nombre_Comercial, " (LAB-", P.Laboratorio_Comercial, ") - Lote: ",PF.Lote)) as Producto 
                    FROM Producto_' . $tipo_factura . ' PF 
                    INNER JOIN Producto P ON PF.Id_Producto=P.Id_Producto 
                    WHERE Id_' . $tipo_factura . '=' . $id_factura;

            $oCon = new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
            $this->productos = $oCon->getData();
            unset($oCon);


            $tip = '';
            if ($tipo_factura == "Factura_Venta") {
                $tip = '_Venta';
            }
            $query = 'SELECT IFNULL(SUM(Cantidad*Precio' . $tip . '),0) as Total, IFNULL(SUM((Cantidad*Precio' . $tip . ')*(Impuesto/100)),0) as Total_Iva, IFNULL(SUM(ROUND(Cantidad*Descuento)),0) as Descuento, Impuesto FROM Producto_' . $tipo_factura . ' WHERE Id_' . $tipo_factura . '=' . $id_factura;
            $oCon = new consulta();
            $oCon->setQuery($query);
            $this->totales = $oCon->getData();
            unset($oCon);
        } elseif ($tipo_factura == "Factura_Capita") {

            $query = 'SELECT PF.*, IFNULL(F.Mes,"") as CUM, PF.Descripcion as Producto 
                    FROM Descripcion_' . $tipo_factura . ' PF 
                    INNER JOIN ' . $tipo_factura . ' F ON F.Id_' . $tipo_factura . ' = PF.Id_' . $tipo_factura . '
                    WHERE PF.Id_' . $tipo_factura . '=' . $id_factura;

            $oCon = new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
            $this->productos = $oCon->getData();
            unset($oCon);


            $tip = '';

            $query = 'SELECT IFNULL(SUM(Cantidad*Precio),0) as Total, IFNULL(SUM((Cantidad*Precio)*(Impuesto/100)),0) as Total_Iva, IFNULL(SUM(ROUND(Cantidad*Descuento)),0) as Descuento, Impuesto 
                    FROM Descripcion_' . $tipo_factura . '
                    WHERE Id_' . $tipo_factura . '=' . $id_factura;
            $oCon = new consulta();
            $oCon->setQuery($query);
            $this->totales = $oCon->getData();
            unset($oCon);
        } elseif ($tipo_factura == "Factura_Administrativa") {

            $query = 'SELECT PF.*, PF.Referencia as CUM, PF.Descripcion as Producto 
                    FROM Descripcion_' . $tipo_factura . ' PF 
                    INNER JOIN ' . $tipo_factura . ' F ON F.Id_' . $tipo_factura . ' = PF.Id_' . $tipo_factura . '
                    WHERE PF.Id_' . $tipo_factura . '=' . $id_factura;

            $oCon = new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
            $this->productos = $oCon->getData();
            unset($oCon);

            $tip = '';

            $query = 'SELECT IFNULL(SUM(Cantidad*Precio),0) as Total, IFNULL(SUM((Cantidad*Precio)*(Impuesto/100)),0) as Total_Iva, IFNULL(SUM(ROUND(Cantidad*Descuento)),0) as Descuento, Impuesto 
                    FROM Descripcion_' . $tipo_factura . '
                    WHERE Id_' . $tipo_factura . '=' . $id_factura;
            $oCon = new consulta();
            $oCon->setQuery($query);
            $this->totales = $oCon->getData();
            unset($oCon);
        }
    }

    private function getNombre()
    {
        $nit = self::getNit();
        $codigo = (int)str_replace($this->resolucion['Codigo'], "", $this->factura['Codigo']);
        $nombre = str_pad($nit, 10, "0", STR_PAD_LEFT) . "000" . date("y") . str_pad($codigo, 8, "0", STR_PAD_LEFT);
        return $nombre;
    }

    function getNit()
    {
        $nit = explode("-", $this->configuracion['NIT']);
        $nit = str_replace(".", "", $nit[0]);
        return $nit;
    }

    function getFecha($tipo)
    {
        $fecha = explode(" ", $this->factura['Fecha_Documento']);

        if ($tipo == 'Fecha') {
            return $fecha[0];
        } elseif ($tipo == 'Hora') {
            return $fecha[1];
        }
    }

    private function getImpuesto()
    {
        $query = 'SELECT * FROM Impuesto WHERE Valor>0 LIMIT 1';
        $oCon = new Consulta();
        $oCon->setQuery($query);
        $iva = $oCon->getData();

        return $iva['Valor'];
    }

    private function GetQr($cufe)
    {
        $fecha = str_replace(":", "", $this->factura['Fecha_Documento']);
        $fecha = str_replace("-", "", $fecha);
        $fecha = str_replace(" ", "", $fecha);

        $qr = "NumFac: " . $this->factura['Codigo'] . "\n";
        $qr .= "FecFac: " . $fecha . "\n";
        $qr .= "NitFac: " . $this->getNit() . "\n";
        $qr .= "DocAdq: " . $this->factura['Id_Cliente'] . "\n";
        $qr .= "ValFac: " . number_format($this->totales['Total'], 2, ".", "") . "\n";
        $qr .= "ValIva: " . number_format($this->totales['Total_Iva'], 2, ".", "") . "\n";
        $qr .= "ValOtroIm: 0.00 \n";
        $qr .= "ValFacIm: " . number_format(($this->totales['Total_Iva'] + $this->totales['Total']), 2, ".", "") . "\n";
        $qr .= "CUFE: " . $cufe . "\n";
        $qr = generarqrFE($qr);

        return ($qr);
    }

    private function GetTercero()
    {
        $cliente = [];
        $query = 'SELECT * FROM Factura_Administrativa WHERE Id_Factura_Administrativa = ' . $this->factura['Id_Factura_Administrativa'];
        $oCon = new consulta();
        $oCon->setQuery($query);

        $facturaAdmin = $oCon->getData();
        unset($oCon);


        $query = '';
        switch ($facturaAdmin['Tipo_Cliente']) {
            case 'Funcionario':
                $query = 'SELECT "Funcionario" AS Tipo_Tercero, Identificacion_Funcionario AS Id_Cliente , "No" as Contribuyente, "No" as Autorretenedor,
                                        CONCAT_WS(" ",Nombres,Apellidos)AS Nombre,
                                        Correo AS Correo_Persona_Contacto , Celular, "Natural" AS Tipo, "CC" AS Tipo_Identificacion,
                        "" AS Digito_Verificacion, "Simplificado" AS Regimen, Direccion_Residencia AS Direccion, Telefono,
                        Id_Municipio, 1 AS Condicion_Pago
                            FROM Funcionario WHERE Identificacion_Funcionario = ' . $facturaAdmin['Id_Cliente'];
                break;

            case 'Proveedor':
                $query = 'SELECT "Proveedor" AS Tipo_Tercero, Id_Proveedor AS Id_Cliente , "No" as Contribuyente, "No" as Autorretenedor,
                                    
                                    (CASE 
                                        WHEN Tipo = "Juridico" THEN Razon_Social
                                        ELSE  COALESCE(Nombre, CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )
                                        
                                    END) AS Nombre,
                                    Correo AS Correo_Persona_Contacto,
                                        Celular, Tipo, "NIT" AS Tipo_Identificacion,
                                    Digito_Verificacion, Regimen, Direccion ,Telefono,
                    Id_Municipio, IFNULL(Condicion_Pago , 1 ) as Condicion_Pago
                        FROM Proveedor WHERE Id_Proveedor = ' . $facturaAdmin['Id_Cliente'];
                break;

            case 'Cliente':
                return $this->getCliente();
                break;

            default:

                break;
        }

        $oCon = new consulta();
        $oCon->setQuery($query);

        $cliente = $oCon->getData();
        unset($oCon);

        return $cliente;
    }

    private function getCliente()
    {
        /*   $query="SELECT C.*,
                 (SELECT D.Nombre FROM Departamento D WHERE D.Id_Departamento=C.Id_Departamento) as Departamento,
                 (SELECT M.Nombre FROM Municipio M WHERE M.Id_Municipio=C.Id_Municipio) as Ciudad 
                 FROM Cliente C WHERE C.Id_Cliente=".$this->factura['Id_Cliente']; */
        #correo_persona_contacto

        $query = 'SELECT "Cliente" AS Tipo_Tercero, Id_Cliente, Contribuyente, Autorretenedor,
                            (CASE 
                                WHEN Tipo = "Juridico" THEN Razon_Social
                                ELSE  COALESCE(Nombre, CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )
                                
                            END) AS Nombre, 
                            Correo_Persona_Contacto,
                            Celular, Tipo, Tipo_Identificacion,
                            Digito_Verificacion, Regimen, Direccion, Telefono_Persona_Contacto AS Telefono, 
                Id_Municipio, IFNULL(Condicion_Pago , 1 ) as Condicion_Pago
                 FROM Cliente WHERE Id_Cliente =' . $this->factura['Id_Cliente'];
        $oCon = new consulta();
        $oCon->setQuery($query);
        $cliente = $oCon->getData();

        unset($oCon);
        return $cliente;
    }


    private function getDataDis()
    {
       echo json_encode(  getDatosDisHelper($this->factura['Id_Dispensacion'],$this->cliente,$this->configuracion ) ); 
        exit;
    }
}
