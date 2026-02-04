<?php
require_once __DIR__ . '/../config/start.inc.php';
include_once 'class.lista.php';
include_once 'class.complex.php';
include_once 'class.consulta.php';
require_once 'class.qr.php';

class DocumentoElectronico
{
    private $resolucion = '', $documento = '', $configuracion = '', $productos = [], $proveedor = '', $totales = '', $id_documento = '';
    private $log_prefix = '[DSE]'; // Documento Soporte Electronico

    private function log($message, $data = null)
    {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "{$this->log_prefix} [{$timestamp}] {$message}";
        if ($data !== null) {
            if (is_array($data) || is_object($data)) {
                $log_message .= "\n" . print_r($data, true);
            } else {
                $log_message .= " => " . $data;
            }
        }
        error_log($log_message);
    }

    public function __construct($id_documento, $resolucion_facturacion)
    {
        $this->log("========== INICIO CONSTRUCTOR ==========");
        $this->log("ID Documento", $id_documento);
        $this->log("Resolucion Facturacion", $resolucion_facturacion);
        
        $this->id_documento = $id_documento;
        self::getDatos($id_documento, $resolucion_facturacion);
        
        $this->log("========== FIN CONSTRUCTOR ==========");
    }

    public function __destruct() {}
    
    function GenerarDocumento()
    {
        $this->log("========== INICIO GenerarDocumento ==========");
        
        $this->log("PASO 1: Generando JSON...");
        $datos = $this->GenerarJson();
        $this->log("JSON generado correctamente");

        $this->log("PASO 2: Informacion de Resolucion");
        $this->log("  - Resolucion ID DB", $this->resolucion['Id_Resolucion']);
        $this->log("  - Resolution ID API", $this->resolucion['resolution_id']);
        $this->log("  - Consecutivo actual", $this->resolucion['Consecutivo']);
        $this->log("  - Codigo documento", $this->documento['Codigo']);
        $this->log("JSON a enviar:", json_encode($datos, JSON_PRETTY_PRINT));

        $this->log("PASO 3: Enviando a API DIAN...");
        $respuesta_dian = $this->GetApi($datos);
        $this->log("Respuesta recibida de API DIAN:", $respuesta_dian);

        $this->log("PASO 4: Procesando respuesta DIAN...");
        $aplication_response = isset($respuesta_dian["Json"]["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]["XmlBase64Bytes"]) 
            ? $respuesta_dian["Json"]["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]["XmlBase64Bytes"] 
            : null;
        
        if ($aplication_response) {
            $aplication_response = base64_decode($aplication_response);
            $this->log("Application Response decodificado correctamente");
        } else {
            $this->log("ADVERTENCIA: No se encontro XmlBase64Bytes en la respuesta");
        }

        $cuds = isset($respuesta_dian["Cuds"]) ? $respuesta_dian["Cuds"] : null;
        $this->log("CUDS obtenido", $cuds);
        
        $qr = $this->GetQr($cuds);
        $this->log("QR generado", substr($qr, 0, 100) . "...");
        
        $this->log("PASO 5: Evaluando estado de respuesta...");
        if (isset($respuesta_dian["Respuesta"]) && strpos($respuesta_dian["Respuesta"], "procesado anteriormente") !== false) {
            $estado = "true";
            $this->log("Documento procesado anteriormente - Estado: true");
        } else {
            $estado = isset($respuesta_dian["Procesada"]) ? $respuesta_dian["Procesada"] : "false";
            $this->log("Estado de procesamiento", $estado);
        }

        if ($estado == "true") {
            $this->log("PASO 6: Actualizando documento en BD (estado exitoso)...");
            $query = "UPDATE Documento_No_Obligados SET Cuds = '$cuds', Codigo_Qr= '$qr', Procesada= '$estado' Where Id_Documento_No_Obligados = '$this->id_documento'";
            $this->log("Query UPDATE", $query);
            
            $oItem = new consulta;
            $oItem->setQuery($query);
            $oItem->getData();
            unset($oItem);
            $this->log("BD actualizada correctamente");

            $respuesta["Documento"] = $this->documento['Codigo'];
            $respuesta["Procesada"] = $respuesta_dian['Respuesta'];
            $this->log("========== FIN GenerarDocumento (EXITOSO) ==========");
            return ($respuesta);
        }

        $this->log("PASO 6: Preparando respuesta (documento no procesado exitosamente)...");
        $respuesta["Json"] = $respuesta_dian["Json"];
        $respuesta["Enviado"] = $respuesta_dian["Enviado"];

        if (isset($respuesta_dian["Estado"]) && $respuesta_dian["Estado"] == "error") {
            $this->log("Estado: ERROR");
            $this->log("Detalles del error", $respuesta_dian["Respuesta"]);
            
            $respuesta["Estado"] = "Error";
            $respuesta["Detalles"] = $respuesta_dian["Respuesta"];
            $data["Cuds"] = $cuds;
            $data["Qr"] = $qr;
            $respuesta["Datos"] = $data;
        } elseif (isset($respuesta_dian["Estado"]) && $respuesta_dian["Estado"] == "exito") {
            $this->log("Estado: EXITO");
            $respuesta["Respuesta_Correo"] = "CORREO NO ENVIADO";
            $respuesta["Estado"] = "Exito";
            $respuesta["Detalles"] = $respuesta_dian["Respuesta"];
            $data["Cufe"] = $cuds; // Nota: variable $cufe no definida, usando $cuds
            $data["Qr"] = $qr;
            $respuesta["Datos"] = $data;
        } else {
            $this->log("Estado no reconocido", isset($respuesta_dian["Estado"]) ? $respuesta_dian["Estado"] : "NO DEFINIDO");
        }
        
        $this->log("Respuesta final", $respuesta);
        $this->log("========== FIN GenerarDocumento ==========");
        return ($respuesta);
    }
    function GetQr($cufe)
    {
        $this->log("GetQr - Generando QR para CUDS", $cufe);
        
        $qr_url = 'https://catalogo-vpfe.dian.gov.co/Document/ShowDocumentToPublic/' . $cufe;
        $this->log("GetQr - URL", $qr_url);
        
        $qr = generarqrFE($qr_url);
        $this->log("GetQr - QR generado", strlen($qr) . " caracteres");

        return ($qr);
    }
    private function GetApi($datos)
    {
        $this->log("---------- INICIO GetApi ----------");

        $login = 'facturacion@prohsa.com';
        $password = '804016084';
        $host = "https://api-dian.sigesproph.com.co";
        $api = '/api';
        $version = '/ubl2.1';
        $modulo = '/support-document';
        $url = $host . $api . $version . $modulo;

        $this->log("URL de API", $url);
        $this->log("Login", $login);

        $data = json_encode($datos);
        $this->log("Tamaño del payload", strlen($data) . " bytes");

        $ch = curl_init($url);
        $this->log("CURL inicializado");

        curl_setopt($ch, CURLOPT_SSLVERSION, 4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_VERBOSE, true);

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
        $this->log("Headers configurados", count($headers) . " headers");

        $this->log("Ejecutando peticion CURL...");
        $start_time = microtime(true);
        $result = curl_exec($ch);
        $end_time = microtime(true);
        $execution_time = round(($end_time - $start_time) * 1000, 2);
        
        $this->log("Peticion completada en", $execution_time . " ms");
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->log("HTTP Code", $http_code);
        $this->log("Tamaño respuesta", strlen($result) . " bytes");
        $this->log("RESPUESTA RAW", $result);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            $this->log("ERROR CURL #{$errno}", $error);
            curl_close($ch);
            
            $respuesta["Estado"] = "error";
            $respuesta["Error"] = '# Error : ' . $error;
            $this->log("---------- FIN GetApi (ERROR CURL) ----------");
            return $respuesta;
        }
        
        curl_close($ch);
        
        $sanear_respuesta = function ($texto) {
            if (!is_string($texto)) {
                return $texto;
            }
            $texto = str_ireplace(["<br />", "<br/>", "<br>"], " - ", $texto);
            $texto = strip_tags($texto);
            $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $texto = preg_replace('/\s+/', ' ', $texto);
            return trim($texto, " -\t\n\r\0\x0B");
        };

        if ($result) {
            $this->log("Decodificando respuesta JSON...");
            $json_output = json_decode($result, true);
            if (!is_array($json_output)) {
                $json_error = json_last_error_msg();
                $this->log("ERROR JSON decode", $json_error);
                $respuesta["Estado"] = "error";
                $respuesta["Procesada"] = "false";
                $respuesta["Respuesta"] = "Error al decodificar respuesta de la API DIAN (HTTP {$http_code}): " . $sanear_respuesta($result);
                $respuesta["Json"] = [];
                $respuesta["Enviado"] = $datos;
                $this->log("---------- FIN GetApi (ERROR JSON) ----------");
                return $respuesta;
            }

            $this->log("JSON decodificado", $json_output);

            $mensaje = isset($json_output["message"]) ? $json_output["message"] : "";
            $this->log("Mensaje de respuesta", $mensaje);
            
            $respuesta["Cuds"] = isset($json_output["cuds"]) ? $json_output["cuds"] : null;
            $this->log("CUDS", $respuesta["Cuds"]);
            
            $respuesta["Json"] = $json_output;
            $respuesta["Enviado"] = $datos;

            $mensaje_saneado = $sanear_respuesta($mensaje);
            if ($mensaje_saneado !== "" && strpos($mensaje_saneado, "invalid") !== false) {
                $this->log("VALIDACION FALLIDA - Mensaje contiene 'invalid'");
                $respuesta["Estado"] = "error";
                $errors = isset($json_output["errors"]) ? $json_output["errors"] : "Sin errores especificos";
                $respuesta["Respuesta"] = $sanear_respuesta(is_array($errors) ? implode(" | ", $errors) : $errors);
                $this->log("Errores de validacion", $errors);
            } else {
                $this->log("Procesando ResponseDian...");
                if (isset($json_output["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"])) {
                    $r = $json_output["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"];
                    $estado = isset($r["IsValid"]) ? $r["IsValid"] : "false";
                    $this->log("IsValid", $estado);

                    $respuesta["Procesada"] = $estado;
                    if ($estado == "true") {
                        $respuesta["Estado"] = "exito";
                        $respuesta["Respuesta"] = ($r["StatusDescription"] ?? "") . " - " . ($r["StatusMessage"] ?? "");
                        $this->log("EXITO - StatusDescription", $r["StatusDescription"] ?? "N/A");
                        $this->log("EXITO - StatusMessage", $r["StatusMessage"] ?? "N/A");
                    } else {
                        $respuesta["Estado"] = "error";
                        $respuesta["Respuesta"] = '';
                        if (isset($r["ErrorMessage"]) && is_array($r["ErrorMessage"])) {
                            foreach ($r["ErrorMessage"] as $e) {
                                $respuesta["Respuesta"] .= $e . " - ";
                                $this->log("ErrorMessage item", $e);
                            }
                        }
                        $respuesta["Respuesta"] .= $r["StatusMessage"] ?? "";
                        $respuesta["Respuesta"] = trim($respuesta["Respuesta"], " - ");
                        $this->log("ERROR - Respuesta completa", $respuesta["Respuesta"]);
                    }
                } else {
                    $this->log("ADVERTENCIA: Estructura ResponseDian no encontrada en la respuesta");
                    $respuesta["Estado"] = "error";
                    $respuesta["Procesada"] = "false";
                    $respuesta["Respuesta"] = $mensaje_saneado !== "" ? $mensaje_saneado : "Estructura de respuesta inesperada";
                    if (
                        stripos($respuesta["Respuesta"], "procesado anteriormente") !== false
                        || stripos($respuesta["Respuesta"], "archivo existente") !== false
                    ) {
                        $respuesta["Estado"] = "exito";
                        $respuesta["Procesada"] = "true";
                    }
                }
            }

            $this->log("---------- FIN GetApi ----------");
            return $respuesta;
        }
        
        $this->log("ADVERTENCIA: Respuesta vacia de la API");
        $this->log("---------- FIN GetApi (SIN RESPUESTA) ----------");
        return ["Estado" => "error", "Respuesta" => "Sin respuesta de la API"];
    }


    public function getJson()
    {
        $this->log("========== INICIO getJson ==========");
        $datos = $this->GenerarJson();
        $this->log("========== FIN getJson ==========");
        return ($datos);
    }

    private function GetMunicipio($idMunicipio)
    {
        $this->log("GetMunicipio - Buscando municipio", $idMunicipio);
        
        $query = 'SELECT municipalities_id FROM Municipio WHERE Id_Municipio = ' . $idMunicipio;
        $oCon = new consulta();
        $oCon->setQuery($query);
        $mun = $oCon->getData();
        
        $this->log("GetMunicipio - municipalities_id encontrado", $mun['municipalities_id'] ?? 'N/A');
        
        return $mun['municipalities_id'];
    }

    private function GenerarJson()
    {
        $this->log("---------- INICIO GenerarJson ----------");

        $resultado["type_document_id"] = 11;
        $this->log("type_document_id", 11);
        
        $resultado["cuds_propio"] = $this->getCUDS();
        $this->log("cuds_propio generado", substr($resultado["cuds_propio"], 0, 30) . "...");

        $numero_doc = (int) str_replace($this->resolucion['Codigo'], "", $this->documento['Codigo']);
        $this->log("Numero documento calculado", $numero_doc);
        $this->log("Rango permitido", "[{$this->resolucion['Numero_Inicial']} - {$this->resolucion['Numero_Final']}]");
        
        if ($numero_doc < $this->resolucion['Numero_Inicial'] || $numero_doc > $this->resolucion['Numero_Final']) {
            $this->log("ERROR: Numero $numero_doc fuera de rango [{$this->resolucion['Numero_Inicial']}-{$this->resolucion['Numero_Final']}]");
        }

        if ($numero_doc < $this->resolucion['Consecutivo']) {
            $this->log("ADVERTENCIA: Numero $numero_doc ya fue usado. Consecutivo actual: {$this->resolucion['Consecutivo']}");
        }

        $resultado["resolution_id"] = $this->resolucion["resolution_id"];
        $this->log("resolution_id", $resultado["resolution_id"]);

        $resultado["code"] = $this->documento['Codigo'];
        $resultado["prefix"] = $this->resolucion['Codigo'];
        $resultado["number"] = (int) str_replace($this->resolucion['Codigo'], "", $this->documento['Codigo']);
        $resultado["file"] = $this->documento['Codigo'];

        $resultado["issue_date"] = date("Y-m-d", strtotime($this->documento["Fecha_Adquirido"]));
        $resultado['due_date'] = date("Y-m-d", strtotime($this->documento["Fecha_Adquirido"]));

        $fecha_inicio = strtotime($this->resolucion['Fecha_Inicio']);
        $fecha_fin = strtotime($this->resolucion['Fecha_Fin']);
        $fecha_doc = strtotime($resultado["issue_date"]);

        if ($fecha_doc < $fecha_inicio || $fecha_doc > $fecha_fin) {
            error_log("ERROR: Fecha del documento ({$resultado['issue_date']}) fuera del rango de la resolucion [{$this->resolucion['Fecha_Inicio']} - {$this->resolucion['Fecha_Fin']}]");
        }

        $proveedor["type_document_identification_id"] = 6;/*(($this->proveedor["Tipo_Identificacion"] == "NIT") ? 6 : 3); /* 6 NIT - 3 Cedula */
        $proveedor["dv"] = $this->proveedor["Digito_Verificacion"];
        // if ($this->proveedor["Tipo_Identificacion"] == "NIT") {
        // }

        $proveedor["identification_number"] = $this->proveedor["Id_Proveedor"];
        $proveedor["type_regime_id"] = (($this->proveedor["Regimen"] == "Comun") ? 2 : 1); /* 1 Simplificado - 2 Comun */
        $proveedor["tax_id"] = ($this->proveedor["Regimen"] == "Comun") ? 1 : 16; /*1: iva, 16: no aplica*/

        $proveedor["address"] = trim((($this->proveedor["Direccion"] != "" && $this->proveedor["Direccion"] != "NULL") ? trim($this->proveedor["Direccion"]) : "SIN DIRECCION"));
        $proveedor["email"] = trim(((trim($this->proveedor["Correo_Persona_Contacto"]) != "" && $this->proveedor["Correo_Persona_Contacto"] != "NULL") ? $this->proveedor["Correo_Persona_Contacto"] : "notiene@notiene.com"));
        $proveedor["merchant_registration"] = "No Tiene";

        if ($this->proveedor['Id_Municipio'] != '') {
            $proveedor['municipality_id'] = (int) $this->GetMunicipio($this->proveedor['Id_Municipio']);
        }

        $proveedor['country_id'] = 46;
        $proveedor['language_id'] = 25;

        $proveedor["type_liability_id"] = 122;

        if ($this->proveedor["Contribuyente"] == "Si") {
            $proveedor["type_liability_id"] = 118;
        }

        if ($this->proveedor["Regimen"] == "Simplificado") {
            $proveedor["type_liability_id"] = 121;
        }

        if ($this->proveedor["Autorretenedor"] == "Si") {
            $proveedor["type_liability_id"] = 119;
        }

        $telefono = explode('-',  $this->proveedor["Telefono"]);
        $telefono = $telefono[0] ? str_replace(' ', '', $telefono[0]) : '';

        $proveedor["name"] = trim($this->proveedor["Nombre"]);
        $proveedor["phone"] = ($telefono !== '' ? str_replace(' ', '', $telefono) : "0000000");
        $proveedor["type_organization_id"] = (($this->proveedor["Tipo"] == "Juridico") ? 1 : 2); /* Juridica 1 - Natural 2*/

        $resultado['payment_form']['payment_form_id'] = $this->documento['Forma_Pago'];
        $resultado['payment_form']['payment_method_id'] = 75;
        if ($this->documento['Forma_Pago'] == '2') {
            $resultado['payment_form']['payment_due_date'] = $this->documento['Fecha_Vencimiento'];
        }

        // if ($this->documento['Orden_Compra'] != '') {
        $resultado['origin_reference']['code'] = $this->documento['Orden_Compra'] ? $this->documento['Orden_Compra'] : '';
        // if ($this->documento['Fecha_Orden_Compra'] != '') {
        $resultado['origin_reference']['date'] = $this->documento['Orden_Compra'] != '' ? $this->documento['Fecha_Orden_Compra'] : '';
        // }
        // }

        $resultado['customer'] = $proveedor;
        $i = 0;
        $base_imp = 0;
        $total_impuesto = 0;
        $percent = 0;
        foreach ($this->productos as $producto) {
            # code...

            $base = $producto['Cantidad'] * $producto['Precio'];

            $base_grabable = $base * (1 - $producto['Descuento'] / 100);

            $impuesto = $base_grabable * ($producto['Impuesto'] / 100);

            $descuento = $base * ($producto['Descuento'] / 100);

            $descuentos[0]["charge_indicator"] = false;
            $descuentos[0]["allowance_charge_reason"] = 'Discount';
            $descuentos[0]["amount"] = number_format($descuento, 2, ".", "");
            $descuentos[0]["base_amount"] = number_format($base, 2, ".", "");


            $invoice_line['allowance_charges'] = $descuento > 0 ? $descuentos : [];

            $invoice_line['tax_totals'][0]['tax_id'] = 1;
            $invoice_line['tax_totals'][0]['tax_amount'] = number_format($impuesto, 2, '.', '');
            $invoice_line['tax_totals'][0]['taxable_amount'] = number_format($base_grabable, 2, '.', '');
            $invoice_line['tax_totals'][0]['percent'] = number_format($producto['Porcentaje'], 2, '.', '');

            $base_imp += $base_grabable;
            if ($impuesto > 0) {
                $total_impuesto += $impuesto;
                if ($percent == 0) {
                    $percent = $producto['Impuesto'];
                }
            }

            $invoice_line['invoiced_quantity'] = $producto['Cantidad'];
            $invoice_line['line_extension_amount'] = $base_grabable;

            if ((int) $producto['Precio'] == 0) {
                $invoice_line["free_of_charge_indicator"] = true;
                $invoice_line["reference_price_id"] = 1;
                $invoice_line["price_amount"] = number_format(1, 2, ".", "");
            } else {

                $invoice_line['free_of_charge_indicator'] = false;
                $invoice_line["reference_price_id"] = 1;
                $invoice_line['price_amount'] = number_format($producto['Precio'], 2, '.', '');
            }

            $invoice_line['code'] = $producto['Codigo_Producto_Servicio'];
            $invoice_line['description'] = $producto['Descripcion'];
            $invoice_line['type_item_identification_id'] = 3;
            $invoice_line['base_quantity'] = $producto['Cantidad'];
            $invoice_line['unit_measure_id'] = 70;
            $invoice_line['note'] = $producto['Nota'] !== '' ? $producto['Nota'] : 'N/A';
            // if ($producto['Paquete_Cantidad'] != '') {
            $invoice_line['pack_size_numeric'] = $producto['Paquete_Cantidad'] != '' ? $producto['Paquete_Cantidad'] : "1";
            // }

            if ($producto['Marca'] != '') {
                $invoice_line['brand_name'] = $producto['Marca'] ? $producto['Marca'] : '';
            }

            if ($producto['Referencia'] != '') {
                $invoice_line['model_name'] = $producto['Referencia'] ? $producto['Referencia'] : '';
            }

            if ($this->documento['Tipo_Reporte'] == '1') {
                $invoice_line['invoice_period']['date'] =  date("Y-m-d", strtotime($this->documento["Fecha_Adquirido"]));
                $invoice_line['invoice_period']['description_code'] = $this->documento['Tipo_Reporte'];
                $invoice_line['invoice_period']['description'] = "Por operación";
            } else {
                $invoice_line['invoice_period']['date'] =  date("Y-m-d", strtotime($producto['Fecha_Compra']));
                $invoice_line['invoice_period']['description_code'] = $this->documento['Tipo_Reporte'];
                $invoice_line['invoice_period']['description'] = "Acumulado semanal";
            }
            // $invoice_line['sellers_item_identification']['id']='';
            // $invoice_line['sellers_item_identification']['code']='';
            $invoice_line['withholding_tax_totals'] = [];


            $resultado['invoice_lines'][$i] = $invoice_line;
            $i++;
            unset($invoice_line);
        }

        $tax_totals["tax_id"] = 1;
        $tax_totals["tax_amount"] = number_format($total_impuesto, 2, ".", "");
        $tax_totals["taxable_amount"] = number_format($base_imp, 2, ".", "");
        $tax_totals["percent"] = "$percent";

        $resultado['tax_totals'][0] = $tax_totals;

        $legal_monetary_totals['line_extension_amount'] = number_format($this->totales['Total'] - $this->totales['Descuento'], 2, '.', '');
        $legal_monetary_totals['tax_exclusive_amount'] = number_format($this->totales['Total'] - $this->totales['Descuento'], 2, '.', '');
        $legal_monetary_totals['tax_inclusive_amount'] = number_format($this->totales['Total'] - $this->totales['Descuento'] + $this->totales['Total_Iva'], 2, '.', '');
        $legal_monetary_totals['allowance_total_amount'] = number_format(0, 2, '.', '');
        $legal_monetary_totals['charge_total_amount'] = number_format(0, 2, '.', '');
        $legal_monetary_totals['payable_amount'] = number_format($this->totales['Total'] - $this->totales['Descuento'] + $this->totales['Total_Iva'], 2, '.', '');

        $resultado['legal_monetary_totals'] = $legal_monetary_totals;
        $resultado['withholding_tax_totals'] = [];
        
        $this->log("legal_monetary_totals", $legal_monetary_totals);
        $this->log("Total invoice_lines generadas", count($resultado['invoice_lines']));
        $this->log("---------- FIN GenerarJson ----------");
        
        return $resultado;
    }

    private function getCUDS()
    {
        $this->log("---------- INICIO getCUDS ----------");
        
        $nit = self::getNit();
        $this->log("NIT empresa", $nit);
        
        $fecha = date("Y-m-d", strtotime($this->documento['Fecha_Adquirido'])) . "T00:00:00";
        $this->log("Fecha formateada", $fecha);
        
        $neto = number_format($this->totales['Total'] + $this->totales['Total_Iva'] - $this->totales['Descuento'], 2, ".", "");
        $this->log("Valor neto", $neto);
        
        $base = number_format($this->totales['Total'] - $this->totales['Descuento'], 2, ".", "");
        $iva = number_format($this->totales['Total_Iva'], 2, ".", "");
        
        $this->log("Componentes CUDS", [
            'Codigo' => $this->documento['Codigo'],
            'Fecha' => $fecha . "-05:00",
            'Base' => $base,
            'IVA' => $iva,
            'Neto' => $neto,
            'NIT_Empresa' => $nit,
            'NIT_Proveedor' => $this->proveedor['Id_Proveedor'],
            'Clave_Tecnica' => substr($this->resolucion['Clave_Tecnica'] ?? '', 0, 20) . "..."
        ]);
        
        $variable = $this->documento['Codigo'] . "" . $fecha . "-05:00" . $base . "01" . $iva . "040.00030.00" . $neto . $nit . $this->proveedor['Id_Proveedor'] . $this->resolucion['Clave_Tecnica'] . '1';
        $this->log("String para hash (parcial)", substr($variable, 0, 100) . "...");
        
        $cuds = hash('sha384', $variable);
        $this->log("CUDS generado", $cuds);
        $this->log("---------- FIN getCUDS ----------");
        
        return $cuds;
    }

    private function getDatos($id_documento, $resolucion_facturacion)
    {
        $this->log("---------- INICIO getDatos ----------");
        $this->log("Parametros: id_documento={$id_documento}, resolucion_facturacion={$resolucion_facturacion}");

        // 1. Cargar Resolucion
        $this->log("Cargando Resolucion...");
        $oItem = new complex("Resolucion", "Id_Resolucion", $resolucion_facturacion);
        $this->resolucion = $oItem->getData();
        unset($oItem);
        $this->log("Resolucion cargada", [
            'Id_Resolucion' => $this->resolucion['Id_Resolucion'] ?? 'N/A',
            'Codigo' => $this->resolucion['Codigo'] ?? 'N/A',
            'resolution_id' => $this->resolucion['resolution_id'] ?? 'N/A',
            'Consecutivo' => $this->resolucion['Consecutivo'] ?? 'N/A',
            'Numero_Inicial' => $this->resolucion['Numero_Inicial'] ?? 'N/A',
            'Numero_Final' => $this->resolucion['Numero_Final'] ?? 'N/A',
            'Fecha_Inicio' => $this->resolucion['Fecha_Inicio'] ?? 'N/A',
            'Fecha_Fin' => $this->resolucion['Fecha_Fin'] ?? 'N/A'
        ]);

        // 2. Cargar Documento
        $this->log("Cargando Documento_No_Obligados...");
        $oItem = new complex("Documento_No_Obligados", "Id_Documento_No_Obligados", $id_documento);
        $this->documento = $oItem->getData();
        unset($oItem);
        $this->log("Documento cargado", [
            'Id_Documento_No_Obligados' => $this->documento['Id_Documento_No_Obligados'] ?? 'N/A',
            'Codigo' => $this->documento['Codigo'] ?? 'N/A',
            'Fecha_Adquirido' => $this->documento['Fecha_Adquirido'] ?? 'N/A',
            'Tipo_Proveedor' => $this->documento['Tipo_Proveedor'] ?? 'N/A',
            'Id_Proveedor' => $this->documento['Id_Proveedor'] ?? 'N/A',
            'Forma_Pago' => $this->documento['Forma_Pago'] ?? 'N/A',
            'Tipo_Reporte' => $this->documento['Tipo_Reporte'] ?? 'N/A'
        ]);

        // 3. Cargar Configuracion
        $this->log("Cargando Configuracion...");
        $query = "SELECT C.*,(SELECT D.Nombre FROM Departamento D WHERE D.Id_Departamento=C.Id_Departamento) as Departamento, (SELECT M.Nombre FROM Municipio M WHERE M.Id_Municipio=C.Id_Municipio) as Ciudad FROM Configuracion C WHERE C.Id_Configuracion=1";
        $oCon = new consulta();
        $oCon->setQuery($query);
        $this->configuracion = $oCon->getData();
        unset($oCon);
        $this->log("Configuracion cargada", [
            'NIT' => $this->configuracion['NIT'] ?? 'N/A',
            'Razon_Social' => $this->configuracion['Razon_Social'] ?? 'N/A',
            'Ciudad' => $this->configuracion['Ciudad'] ?? 'N/A'
        ]);

        // 4. Cargar Proveedor/Tercero
        $this->log("Cargando Tercero (tipo: {$this->documento['Tipo_Proveedor']})...");
        $this->proveedor = $this->getTercero();
        $this->log("Tercero cargado", [
            'Tipo_Tercero' => $this->proveedor['Tipo_Tercero'] ?? 'N/A',
            'Id_Proveedor' => $this->proveedor['Id_Proveedor'] ?? 'N/A',
            'Nombre' => $this->proveedor['Nombre'] ?? 'N/A',
            'Tipo' => $this->proveedor['Tipo'] ?? 'N/A',
            'Regimen' => $this->proveedor['Regimen'] ?? 'N/A',
            'Digito_Verificacion' => $this->proveedor['Digito_Verificacion'] ?? 'N/A'
        ]);

        // 5. Cargar Productos
        $this->log("Cargando Productos...");
        $query = "SELECT D.*, Ifnull(D.Descripcion, PS.Nombre) as Descripcion from Descripcion_Documento_No_Obligados D
                  left join Producto_Servicio PS on D.Codigo_Producto_Servicio= PS.Codigo_Producto
                   WHERE D.Id_Documento_No_Obligados = $id_documento";
        $oCon = new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query);
        $this->productos = $oCon->getData();
        unset($oCon);
        $this->log("Productos cargados", count($this->productos) . " productos");
        foreach ($this->productos as $idx => $prod) {
            $this->log("  Producto #{$idx}", [
                'Codigo' => $prod['Codigo_Producto_Servicio'] ?? 'N/A',
                'Descripcion' => substr($prod['Descripcion'] ?? '', 0, 50),
                'Cantidad' => $prod['Cantidad'] ?? 'N/A',
                'Precio' => $prod['Precio'] ?? 'N/A',
                'Impuesto' => $prod['Impuesto'] ?? 'N/A'
            ]);
        }

        // 6. Cargar Totales
        $this->log("Calculando Totales...");
        $query = "SELECT SUM(DS.Cantidad * DS.Precio) AS Total,
                  SUM(DS.Cantidad * DS.Precio * DS.Descuento/100) AS Descuento,
                  SUM(DS.Cantidad * DS.Precio *(1- DS.Descuento/100)* DS.Impuesto/100)AS Total_Iva
                  FROM Descripcion_Documento_No_Obligados DS
                  WHERE DS.Id_Documento_No_Obligados = $id_documento";
        $oCon = new consulta();
        $oCon->setQuery($query);
        $this->totales = $oCon->getData();
        unset($oCon);
        $this->log("Totales calculados", [
            'Total' => $this->totales['Total'] ?? 'N/A',
            'Descuento' => $this->totales['Descuento'] ?? 'N/A',
            'Total_Iva' => $this->totales['Total_Iva'] ?? 'N/A'
        ]);

        $this->log("---------- FIN getDatos ----------");
    }
    private function limpiarString($nit)
    {

        $car1 = ['.', '-'];
        $clean = ['', ''];

        return str_replace($car1, $clean, $nit);
    }


    private function getNombre()
    {
        $nit = self::getNit();
        $codigo = (int) str_replace($this->resolucion['Codigo'], "", $this->documento['Codigo']);
        $nombre = str_pad($nit, 10, "0", STR_PAD_LEFT) . "000" . date("y") . str_pad($codigo, 8, "0", STR_PAD_LEFT);
        return $nombre;
    }

    private function getNit()
    {
        $nit = explode("-", $this->configuracion['NIT']);
        $nit = str_replace(".", "", $nit[0]);
        return $nit;
    }

    private function getFecha($tipo)
    {
        $fecha = explode(" ", $this->documento['Fecha_Documento']);

        if ($tipo == 'Fecha') {
            return $fecha[0];
        } elseif ($tipo == 'Hora') {
            return $fecha[1];
        }
    }

    private function GetTercero()
    {
        $this->log("---------- INICIO GetTercero ----------");
        $this->log("Tipo_Proveedor", $this->documento['Tipo_Proveedor']);
        $this->log("Id_Proveedor", $this->documento['Id_Proveedor']);
        
        $query = '';
        switch ($this->documento['Tipo_Proveedor']) {
            case 'Funcionario':
                $this->log("Procesando como Funcionario...");
                $nit = $this->limpiarString($this->documento['Id_Proveedor']);
                $this->log("NIT limpio", $nit);
                
                $totalSum = 0;
                $nrosPrimos = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
                $carLength = strlen($nit);
                $j = 0;
                for ($i = ($carLength - 1); $i >= 0; $i--) {
                    $nro = $nit[$i];
                    $totalSum += ($nro * $nrosPrimos[$j]);
                    $j++;
                }
                $mod = $totalSum % 11;
                $digito_verificacion = $mod > 1 ? (11 - $mod) : $mod;
                $this->log("Digito verificacion calculado", $digito_verificacion);

                $query = "SELECT 'Funcionario' AS Tipo_Tercero, Identificacion_Funcionario AS Id_Proveedor , 'No' as Contribuyente, 'No' as Autorretenedor,
                  CONCAT_WS(' ',Nombres,Apellidos)AS Nombre,
                  Correo AS Correo_Persona_Contacto , Celular, 'Natural' AS Tipo, 'CC' AS Tipo_Identificacion,
                  '$digito_verificacion' AS Digito_Verificacion, 'Simplificado' AS Regimen, Direccion_Residencia AS Direccion, Telefono,
                  IFNULL(Id_Municipio,99) AS Id_Municipio , 1 AS Condicion_Pago
                  FROM Funcionario WHERE Identificacion_Funcionario = " . $this->documento['Id_Proveedor'];
                break;

            case 'Proveedor':
                $this->log("Procesando como Proveedor...");
                $query = 'SELECT "Proveedor" AS Tipo_Tercero, Id_Proveedor AS Id_Proveedor , "No" as Contribuyente, "No" as Autorretenedor,

                  (CASE
                  WHEN Tipo = "Juridico" THEN Razon_Social
                  ELSE  COALESCE(Nombre, CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )

                  END) AS Nombre,
                  Correo AS Correo_Persona_Contacto,
                  Celular, Tipo, "NIT" AS Tipo_Identificacion,
                  Digito_Verificacion, Regimen, Direccion ,Telefono,
                  Id_Municipio, IFNULL(Condicion_Pago , 1 ) as Condicion_Pago
                  FROM Proveedor WHERE Id_Proveedor = ' . $this->documento['Id_Proveedor'];
                break;

            case 'Cliente':
                $this->log("Procesando como Cliente...");
                $this->log("---------- FIN GetTercero (delegando a getCliente) ----------");
                return $this->getCliente();
                break;

            default:
                $this->log("ERROR: Tipo_Proveedor no reconocido", $this->documento['Tipo_Proveedor']);
                echo "error";
                exit;
                break;
        }

        $this->log("Query tercero", $query);
        
        $oCon = new consulta();
        $oCon->setQuery($query);
        $proveedor = $oCon->getData();
        unset($oCon);

        $this->log("Tercero encontrado", $proveedor ? "SI" : "NO");
        $this->log("---------- FIN GetTercero ----------");
        
        return $proveedor;
    }

    private function getCliente()
    {
        $this->log("---------- INICIO getCliente ----------");
        $this->log("Buscando cliente con Id", $this->documento['Id_Proveedor']);
        
        $query = 'SELECT "Cliente" AS Tipo_Tercero, Id_Cliente as Id_Proveedor, Contribuyente, Autorretenedor,
            (CASE
            WHEN Tipo = "Juridico" THEN Razon_Social
            ELSE  COALESCE(Nombre, CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )

            END) AS Nombre,
            Correo_Persona_Contacto,
            Celular, Tipo, Tipo_Identificacion,
            Digito_Verificacion, Regimen, Direccion, Telefono_Persona_Contacto AS Telefono,
            Id_Municipio, IFNULL(Condicion_Pago , 1 ) as Condicion_Pago
            FROM Cliente WHERE Id_Cliente =' . $this->documento['Id_Proveedor'];
        
        $this->log("Query cliente", $query);
        
        $oCon = new consulta();
        $oCon->setQuery($query);
        $cliente = $oCon->getData();
        unset($oCon);
        
        $this->log("Cliente encontrado", $cliente ? "SI" : "NO");
        if ($cliente) {
            $this->log("Datos cliente", [
                'Nombre' => $cliente['Nombre'] ?? 'N/A',
                'Tipo' => $cliente['Tipo'] ?? 'N/A',
                'Regimen' => $cliente['Regimen'] ?? 'N/A'
            ]);
        }
        
        $this->log("---------- FIN getCliente ----------");
        return $cliente;
    }
}
