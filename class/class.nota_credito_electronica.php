<?php
   
    //require_once($_SERVER['DOCUMENT_ROOT'].'/config/start.inc.php');
    require_once(__DIR__.'/../config/start.inc.php');
    include_once('class.lista.php');
    include_once('class.complex.php');
    include_once('class.consulta.php');
    require_once('class.qr.php'); 
    require_once('class.php_mailer.php'); 

    class NotaCreditoElectronica{   
        private $resolucion='', $nota_credito='', $configuracion='', $productos=[], $cliente='', $totales='', $tipo_nota_credito='', $id_nota_credito='', $factura=''; 
        
		function __construct($tipo_nota_credito, $id_nota_credito,$resolucion_facturacion){
            $this->tipo_nota_credito = $tipo_nota_credito;
            $this->id_nota_credito = $id_nota_credito;
            self::getDatos($tipo_nota_credito, $id_nota_credito,$resolucion_facturacion); 
		}

		function __destruct(){
    			
        }
        
        function GenerarNota(){
            $datos = $this->GeneraJson($this->tipo_nota_credito);
            $respuesta_dian = $this->GetApi($datos);
            
            
            
            $cude=$respuesta_dian["Cude"] ?? null;
          
            $qr=$this->GetQr($cude);
            $respuestaTexto = $respuesta_dian["Respuesta"] ?? '';
            if(is_array($respuestaTexto)){
                $respuestaTexto = json_encode($respuestaTexto, JSON_UNESCAPED_UNICODE);
            }
            $estado = $respuesta_dian["Procesada"] ?? "false";
            if(is_string($respuestaTexto) && strpos($respuestaTexto,"procesado anteriormente")!==false){
                $estado = "true";
            }
            
            if($estado == "true"){
                
                $oItem=new complex($this->tipo_nota_credito, "Id_".$this->tipo_nota_credito, $this->id_nota_credito );
                $oItem->Cude=$cude;
                $oItem->Codigo_Qr = $qr;
                $oItem->Procesada = $estado;
                $oItem->save();
                unset($oItem);            
                
            }
                $respuesta["Json"]=$respuesta_dian["Json"] ?? null;
                $respuesta["Enviado"]=$respuesta_dian["Enviado"] ?? null;
                
            if($respuesta_dian["Estado"]=="error"){
                
                $respuesta["Estado"]="Error";
                $respuesta["Detalles"] =$respuesta_dian["Respuesta"];                
                $data["Cude"]=$cude;
                $data["Qr"]=$qr;
                $respuesta["Datos"]=$data;
                
            }elseif($respuesta_dian["Estado"]=="exito"){
                //$respuesta["Respuesta_Correo"] = $this->EnviarMail($cude,$qr,$respuesta_dian["Respuesta"]); 
            
                $respuesta["Estado"]="Exito";
                
                $respuesta["Detalles"] =$respuesta_dian["Respuesta"];
                $data["Cude"]=$cude;
                $data["Qr"]=$qr;
                $respuesta["Datos"]=$data;
            }
            return($respuesta);
        }

        public function GenerarNotaConHost($host){
            $datos = $this->GeneraJson($this->tipo_nota_credito);
            $respuesta_dian = $this->GetApiHost($datos, $host);

            $cude=$respuesta_dian["Cude"] ?? null;
            $qr=$this->GetQr($cude);
            $respuestaTexto = $respuesta_dian["Respuesta"] ?? '';
            if(is_array($respuestaTexto)){
                $respuestaTexto = json_encode($respuestaTexto, JSON_UNESCAPED_UNICODE);
            }
            $estado = $respuesta_dian["Procesada"] ?? "false";
            if(is_string($respuestaTexto) && strpos($respuestaTexto,"procesado anteriormente")!==false){
                $estado = "true";
            }
            
            if($estado == "true"){
                
                $oItem=new complex($this->tipo_nota_credito, "Id_".$this->tipo_nota_credito, $this->id_nota_credito );
                $oItem->Cude=$cude;
                $oItem->Codigo_Qr = $qr;
                $oItem->Procesada = $estado;
                $oItem->save();
                unset($oItem);            
                
            }
                $respuesta["Json"]=$respuesta_dian["Json"] ?? null;
                $respuesta["Enviado"]=$respuesta_dian["Enviado"] ?? null;
                
            if($respuesta_dian["Estado"]=="error"){
                
                $respuesta["Estado"]="Error";
                $respuesta["Detalles"] =$respuesta_dian["Respuesta"];                
                $data["Cude"]=$cude;
                $data["Qr"]=$qr;
                $respuesta["Datos"]=$data;
                
            }elseif($respuesta_dian["Estado"]=="exito"){
                //$respuesta["Respuesta_Correo"] = $this->EnviarMail($cude,$qr,$respuesta_dian["Respuesta"]); 
            
                $respuesta["Estado"]="Exito";
                
                $respuesta["Detalles"] =$respuesta_dian["Respuesta"];
                $data["Cude"]=$cude;
                $data["Qr"]=$qr;
                $respuesta["Datos"]=$data;
            }
            return($respuesta);
        }

        public function ProcesarCude($cude){
            if(!$cude){
                return [
                    "Estado" => "Error",
                    "Detalles" => "CUDE no encontrado.",
                ];
            }
            $qr = $this->GetQr($cude);
            $oItem = new complex($this->tipo_nota_credito, "Id_".$this->tipo_nota_credito, $this->id_nota_credito );
            $oItem->Cude = $cude;
            $oItem->Codigo_Qr = $qr;
            $oItem->Procesada = "true";
            $oItem->save();
            unset($oItem);

            return [
                "Estado" => "Exito",
                "Datos" => [
                    "Cude" => $cude,
                    "Qr" => $qr,
                ],
                "Procesada" => "true",
            ];
        }

        public function getNombreArchivo(){
            return $this->getNombre();
        }

        public function getResolucionId(){
            return $this->resolucion['resolution_id'] ?? null;
        }


        
       
    
        
        private function GetApi($datos){
            
            $login = 'facturacion@prohsa.com';
            $password='804016084';
            $host = 'https://api-dian.sigesproph.com.co';

            $api = '/api';
            $version = '/ubl2.1';
            $modulo = '/credit-note';
            $url = $host.$api.$version.$modulo;
            
            
            $data = json_encode($datos);
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            

            $headers = array(
                "Content-type: application/json",
                "Accept: application/json",
                "Cache-Control: no-cache",
                "Authorization: Basic ". base64_encode($login.':'.$password),
                "Pragma: no-cache",
                "SOAPAction:\"".$url."\"", 
                "Content-length: ".strlen($data),
            );
                
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $sanearRespuesta = function ($texto) {
                if (!is_string($texto)) {
                    return $texto;
                }
                $texto = str_ireplace(["<br />", "<br/>", "<br>"], " - ", $texto);
                $texto = strip_tags($texto);
                $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $texto = preg_replace('/\s+/', ' ', $texto);
                return trim($texto, " -\t\n\r\0\x0B");
            };
            
            if (curl_errno($ch)) {
                $respuesta["Estado"]="error";
                $respuesta["Error"] = '# Error : ' . curl_error($ch);
                $this->logDianError($datos, $respuesta);
                return  $respuesta;
            }elseif($result){    
                $json_output = $this->decodeJsonResponse($result);
                if (!is_array($json_output)) {
                    $respuesta["Estado"] = "error";
                    $respuesta["Procesada"] = "false";
                    $respuesta["Respuesta"] = "Error al decodificar respuesta de la API DIAN (HTTP " . $httpCode . "): " . $sanearRespuesta($result);
                    if (defined('FE_DEBUG_DIAN') && FE_DEBUG_DIAN) {
                        $respuesta["HttpCode"] = $httpCode;
                        $respuesta["Raw"] = $result;
                    }
                    $this->logDianError($datos, $respuesta);
                    return $respuesta;
                }
                
                $mensaje = $json_output["message"] ?? '';
                $respuesta["Cude"]=$json_output["cude"] ?? null;
                $respuesta["Json"]=$json_output;
                $respuesta["Enviado"]=$datos;
              
                
                $mensajeStr = is_array($mensaje) ? json_encode($mensaje, JSON_UNESCAPED_UNICODE) : (string)$mensaje;
                $mensajeSaneado = $sanearRespuesta($mensajeStr);
                if(strpos($mensajeStr, "invalid")!==false){
                    $respuesta["Estado"]="error";
                    $respuesta["Respuesta"] = $json_output["errors"]; 
                }elseif(!isset($json_output["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"])){
                    $respuesta["Estado"] = "error";
                    $respuesta["Procesada"] = "false";
                    $respuesta["Respuesta"] = $mensajeSaneado !== '' ? $mensajeSaneado : "Respuesta DIAN inv�lida";
                    if (
                        stripos($mensajeSaneado, "procesado anteriormente") !== false
                        || stripos($mensajeSaneado, "archivo existente") !== false
                    ) {
                        $respuesta["Estado"] = "exito";
                        $respuesta["Procesada"] = "true";
                    }
                }else{
                    $r = $json_output["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"];
                    $estado=$r["IsValid"];
                    
                    $respuesta["Procesada"]=$estado;
                    if($estado=="true"){
                    	$respuesta["Estado"]="exito";
                    	$respuesta["Respuesta"] = $r["StatusDescription"]." - ".$r["StatusMessage"]; 
                    }else{
                        
                        //var_dump($respuesta);
                    	$respuesta["Estado"]="error";
                    	$respuesta["Respuesta"] = '';
                    	foreach(($r["ErrorMessage"] ?? []) as $e){
                    	    if(is_array($e)){
                    	        foreach($e as $ei){
                    	            $respuesta["Respuesta"] .= $ei." - "; 
                    	        }
                    	    }else{
                    	        $respuesta["Respuesta"] .= $e." - ";
                    	    }
                   	   
                    	}
                    	$respuesta["Respuesta"] .= $r["StatusMessage"];
                    	$respuesta["Respuesta"] = trim($respuesta["Respuesta"]," - ");
                    }
                }
                
                if(($respuesta["Estado"] ?? '') === "error"){
                    $this->logDianError($datos, $respuesta);
                }
                
                return $respuesta;
            }
        }

        private function GetApiHost($datos, $host){
            
            $login = 'facturacion@prohsa.com';
            $password='804016084';
            $host = $host ?: 'https://api-dian.sigesproph.com.co';

            $api = '/api';
            $version = '/ubl2.1';
            $modulo = '/credit-note';
            $url = $host.$api.$version.$modulo;
            
            
            $data = json_encode($datos);
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            

            $headers = array(
                "Content-type: application/json",
                "Accept: application/json",
                "Cache-Control: no-cache",
                "Authorization: Basic ". base64_encode($login.':'.$password),
                "Pragma: no-cache",
                "SOAPAction:\"".$url."\"", 
                "Content-length: ".strlen($data),
            );
                
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $sanearRespuesta = function ($texto) {
                if (!is_string($texto)) {
                    return $texto;
                }
                $texto = str_ireplace(["<br />", "<br/>", "<br>"], " - ", $texto);
                $texto = strip_tags($texto);
                $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $texto = preg_replace('/\s+/', ' ', $texto);
                return trim($texto, " -\t\n\r\0\x0B");
            };
            
            if (curl_errno($ch)) {
                $respuesta["Estado"]="error";
                $respuesta["Error"] = '# Error : ' . curl_error($ch);
                $this->logDianError($datos, $respuesta);
                return  $respuesta;
            }elseif($result){    
                $json_output = $this->decodeJsonResponse($result);
                if (!is_array($json_output)) {
                    $respuesta["Estado"] = "error";
                    $respuesta["Procesada"] = "false";
                    $respuesta["Respuesta"] = "Error al decodificar respuesta de la API DIAN (HTTP " . $httpCode . "): " . $sanearRespuesta($result);
                    if (defined('FE_DEBUG_DIAN') && FE_DEBUG_DIAN) {
                        $respuesta["HttpCode"] = $httpCode;
                        $respuesta["Raw"] = $result;
                    }
                    $this->logDianError($datos, $respuesta);
                    return $respuesta;
                }
                
                $mensaje = $json_output["message"] ?? '';
                $respuesta["Cude"]=$json_output["cude"] ?? null;
                $respuesta["Json"]=$json_output;
                $respuesta["Enviado"]=$datos;
              
                
                $mensajeStr = is_array($mensaje) ? json_encode($mensaje, JSON_UNESCAPED_UNICODE) : (string)$mensaje;
                $mensajeSaneado = $sanearRespuesta($mensajeStr);
                if(strpos($mensajeStr, "invalid")!==false){
                    $respuesta["Estado"]="error";
                    $respuesta["Respuesta"] = $json_output["errors"]; 
                }elseif(isset($json_output["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"])){
                    $r = $json_output["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"];
                    $estado=$r["IsValid"];
                    
                    $respuesta["Procesada"]=$estado;
                    if($estado=="true"){
                    	$respuesta["Estado"]="exito";
                    	$respuesta["Respuesta"] = $r["StatusDescription"]." - ".$r["StatusMessage"]; 
                    }else{
                        
                        //var_dump($respuesta);
                    	$respuesta["Estado"]="error";
                    	$respuesta["Respuesta"] = '';
                    	foreach(($r["ErrorMessage"] ?? []) as $e){
                    	    if(is_array($e)){
                    	        foreach($e as $ei){
                    	            $respuesta["Respuesta"] .= $ei." - "; 
                    	        }
                    	    }else{
                    	        $respuesta["Respuesta"] .= $e." - ";
                    	    }
                   	   
                    	}
                    	$respuesta["Respuesta"] .= $r["StatusMessage"];
                    	$respuesta["Respuesta"] = trim($respuesta["Respuesta"]," - ");
                    }
                }else{
                    $respuesta["Estado"] = "error";
                    $respuesta["Procesada"] = "false";
                    $respuesta["Respuesta"] = $mensajeSaneado !== '' ? $mensajeSaneado : "Respuesta DIAN inv�lida";
                    if (
                        stripos($mensajeSaneado, "procesado anteriormente") !== false
                        || stripos($mensajeSaneado, "archivo existente") !== false
                    ) {
                        $respuesta["Estado"] = "exito";
                        $respuesta["Procesada"] = "true";
                    }
                }
                
                if(($respuesta["Estado"] ?? '') === "error"){
                    $this->logDianError($datos, $respuesta);
                }
                
                return $respuesta;
            }
        }
        
        private function logDianError($payload, $respuesta){
            try{
                $logDir = dirname(__DIR__).'/tmp';
                if(!is_dir($logDir)){
                    return;
                }
                $xmlBase64 = $respuesta["Json"]["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]["XmlBase64Bytes"] ?? null;
                $entry = [
                    'timestamp' => date('c'),
                    'tipo_nota' => $this->tipo_nota_credito,
                    'id_nota' => $this->id_nota_credito,
                    'resolution_id' => $this->resolucion['resolution_id'] ?? null,
                    'customization_id' => $payload['customization_id'] ?? null,
                    'detalle' => $respuesta["Respuesta"] ?? ($respuesta["Error"] ?? null),
                    'xml_base64' => $xmlBase64,
                ];
                file_put_contents($logDir.'/dian_nc_errors.log', json_encode($entry, JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND);
            }catch(\Throwable $e){
                // logging best-effort
            }
        }

        private function decodeJsonResponse($raw){
            $json = json_decode($raw, true);
            if (is_array($json)) {
                return $json;
            }

            $raw = trim((string) $raw);
            if ($raw === '') {
                return null;
            }

            $parts = preg_split('/\}\s*\{/', $raw);
            if (!is_array($parts) || count($parts) <= 1) {
                return null;
            }

            $decoded = null;
            $total = count($parts);
            foreach ($parts as $i => $part) {
                if ($i === 0) {
                    $candidate = $part . '}';
                } elseif ($i === $total - 1) {
                    $candidate = '{' . $part;
                } else {
                    $candidate = '{' . $part . '}';
                }
                $parsed = json_decode($candidate, true);
                if (is_array($parsed)) {
                    $decoded = $parsed;
                }
            }

            return $decoded;
        }
        
        private function GeneraJson($tipo_nota_credito){
            
            $resultado["cufe_propio"] = $this->getCufe();
            
            // UBL 2.1 exige CustomizationID 10 para notas cr�dito; se env�a expl�cito para evitar defaults del proveedor.
            // CustomizationID para Nota Cr�dito de Venta (UBL 2.1)
            $resultado["customization_id"] = 20;
            $resultado["profile_execution_id"] = 1;
            $resultado["profile_id"] = "DIAN 2.1";
            
            $fact["number"]=$this->factura["Codigo"];
            $fact["uuid"] = isset($this->factura["Cufe"]) ? $this->factura["Cufe"] : ($this->factura["Cuds"] ?? null);
            $fact["issue_date"]=date("Y-m-d",strtotime($this->factura["Fecha_Documento"]));
            
            $resultado["billing_reference"]=$fact;
            
            
            //$resultado["number"]=(int)str_replace($this->resolucion['Codigo'],"", $this->nota_credito['Codigo']);;
            $resultado["number"] = (int)preg_replace('/\D/', '', $this->nota_credito['Codigo']);
            $resultado["type_document_id"]=4;
            $resultado["resolution_id"]=$this->resolucion["resolution_id"];
            // Mantener fechas originales si son del d�a; si no, usar la fecha/hora actual para que coincida con la firma (CAD09e).
            $notaFecha = date("Y-m-d",strtotime($this->nota_credito["Fecha"]));
            $nowFecha = date("Y-m-d");
            $resultado["date"]= ($notaFecha === $nowFecha) ? $notaFecha : $nowFecha;
            $resultado["time"]= ($notaFecha === $nowFecha) ? date("H:i:s",strtotime($this->nota_credito["Fecha"])) : date("H:i:s");
            //$resultado["send"]=true;
            $resultado["file"] = $this->getNombre();
            
            $cliente["identification_number"] = $this->cliente["Id_Cliente"];
            //$cliente["dv"]=$this->cliente["Id_Cliente"];
            $cliente["name"]=trim($this->cliente["Nombre"]);
            #var_dump($this->cliente); exit;
            #$cliente["phone"]=(($this->cliente["Celular"] != "" && $this->cliente["Celular"] != "NULL") ? trim(str_replace("(","",str_replace("(","",$this->cliente["Celular"]))) : "0000000" );
            $cliente["phone"]="0000000";
            $cliente["type_organization_id"]=(($this->cliente["Tipo"] == "Juridico") ? 1 : 2 ); /* Juridica 1 - Natural 2*/
            $cliente["type_document_identification_id"]=(($this->cliente["Tipo_Identificacion"] == "NIT") ? 6 : 3 ); /* 6 NIT - 3 Cedula */
            
            if($this->cliente["Tipo_Identificacion"] == "NIT"){
                $cliente["dv"]=$this->cliente["Digito_Verificacion"];
            }
            
            
            
            $cliente["type_regime_id"]=(($this->cliente["Regimen"] == "Comun") ? 2 : 1 ); /* 1 Simplificado - 2 Comun */
            
            
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

            
            $cliente["address"]=trim((($this->cliente["Direccion"] != "" && $this->cliente["Direccion"] != "NULL") ? trim($this->cliente["Direccion"]) : "SIN DIRECCION" ));
            $cliente["email"]=trim((($this->cliente["Correo"] != "" && $this->cliente["Correo"] != "NULL") ? trim($this->cliente["Correo"]) : "facturacionelectronica@prohsa.com" ));
            $cliente["merchant_registration"]="No Tiene";
            
            $resultado["customer"]=$cliente;
            
            $finales["line_extension_amount"]=number_format($this->totales["Total"],0,".","");
            $finales["tax_exclusive_amount"]=number_format($this->totales["Total"],0,".","");
            $finales["tax_inclusive_amount"]=number_format($this->totales["Total"]+$this->totales["Total_Iva"],0,".","");
            $finales["allowance_total_amount"]=number_format($this->totales["Descuento"],0,".","");
            $finales["charge_total_amount"]=0;
            $finales["payable_amount"]=(INT)number_format($this->totales["Total"],0,".","")+number_format($this->totales["Total_Iva"],0,".","")-number_format($this->totales["Descuento"],0,".","");
            
            $resultado["legal_monetary_totals"] = $finales;
            
            $j=-1;
            $produstos_finales=[];
            $productos_finales=[];
            $base_imp = 0;
            $tot_imp = 0;
            
            $base_imp2 = 0;
            $tot_imp2 = 0;
            
            if(!is_array($this->productos)){
                $this->productos = [];
            }
            $base_des=0;
            $tot_des=0;
            $descue=[];
            foreach($this->productos as $pro){ $j++;
                $impuestos=[];
            
                //$descuento = $pro["Cantidad"] * $pro["Descuento"];
                
                if($tipo_nota_credito=="Nota_Credito"){
                  $tot=$pro["Cantidad"]*$pro["Precio_Venta"]; 
                  $precio = $pro["Precio_Venta"];
                }else{
                   $tot=$pro["Cantidad"]*$pro["Precio_Nota_Credito"];
                   $precio = $pro["Precio_Nota_Credito"];
                }
                    
               /* $descuentos[0]["charge_indicator"]=false;
                $descuentos[0]["allowance_charge_reason"]='Discount';
                $descuentos[0]["amount"]=number_format($descuento,0,".","");
                $descuentos[0]["base_amount"]=number_format($tot,0,".",""); 
                
                if($descuento>0){
                    $base_des+=$tot;
                    $tot_des +=$descuento;
                    
                    $descue[$j]["discount_id"]=($j+1);
                    $descue[$j]["charge_indicator"]=false;
                    $descue[$j]["allowance_charge_reason"]='Discount';
                    $descue[$j]["amount"]=number_format($descuento,0,".","");
                    $descue[$j]["base_amount"]=number_format($tot,0,".",""); 
                
                }
                */
                
                $imp = $tot * $pro["Impuesto"] / 100;
                if ($imp > 0) {
                    $base_imp += $tot;
                    $tot_imp += $imp;
                }else{
                    $base_imp2 += $tot;
                    $tot_imp2 += $imp;
                }
                
                $impuestos[0]["tax_id"]=1;
                $impuestos[0]["tax_amount"]=number_format($imp,0,".","");
                $impuestos[0]["taxable_amount"]=number_format($tot,0,".","");
                $impuestos[0]["percent"]=$pro["Impuesto"];
                
                
                
                $productos_finales[$j]["unit_measure_id"]=70;
                $productos_finales[$j]["invoiced_quantity"]=$pro["Cantidad"];
                $productos_finales[$j]["line_extension_amount"]=number_format($tot,0,".","");
                
                $productos_finales[$j]["free_of_charge_indicator"]=false;
                $productos_finales[$j]["reference_price_id"]=1;
                
               // $productos_finales[$j]["allowance_charges"]=$descuentos;
                
               
                $productos_finales[$j]["tax_totals"]=$impuestos;
                
                $productos_finales[$j]["description"]=trim($pro["Producto"]);
                $codigoItem = trim($pro["CUM"] ?? "");
                if($codigoItem === ""){
                    $codigoItem = isset($pro["Id_Producto"]) ? ('NC-'.$pro["Id_Producto"]) : 'NC-SIN-CODIGO';
                }
                $productos_finales[$j]["code"]=$codigoItem;
                $productos_finales[$j]["type_item_identification_id"]=3;
                $productos_finales[$j]["price_amount"]=number_format($precio,0,".","");
                $productos_finales[$j]["base_quantity"]=$pro["Cantidad"];
                
            }
            
            if(empty($productos_finales) && ($this->totales["Total"] > 0 || $this->totales["Total_Iva"] >= 0)){
                $fallbackTaxPercent = ($this->totales["Total"] > 0) ? round(($this->totales["Total_Iva"] * 100) / $this->totales["Total"], 2) : 0;
                $fallbackImp = [];
                if($this->totales["Total_Iva"] > 0){
                    $fallbackImp[] = [
                        "tax_id" => 1,
                        "tax_amount" => number_format($this->totales["Total_Iva"], 0, ".", ""),
                        "taxable_amount" => number_format($this->totales["Total"], 0, ".", ""),
                        "percent" => $fallbackTaxPercent,
                    ];
                }
                
                $productos_finales[] = [
                    "unit_measure_id" => 70,
                    "invoiced_quantity" => 1,
                    "line_extension_amount" => number_format($this->totales["Total"], 0, ".", ""),
                    "free_of_charge_indicator" => false,
                    "reference_price_id" => 1,
                    "tax_totals" => $fallbackImp,
                    "description" => "Ajuste nota cr�dito sin detalle de productos",
                    "code" => "NC-AJUSTE",
                    "type_item_identification_id" => 3,
                    "price_amount" => number_format($this->totales["Total"], 0, ".", ""),
                    "base_quantity" => 1,
                ];
            }
            
           $impues=[];
           if($tot_imp>0){
                $primero["tax_id"] = 1;
                $primero["tax_amount"] = number_format($tot_imp, 2, ".", "");
                $primero["taxable_amount"] = number_format($base_imp, 2, ".", "");
                $primero["percent"] = "19";
                
                $impues[]=$primero;
            }
            
            if($base_imp2>0){
                $segundo["tax_id"] = 1;
                $segundo["tax_amount"] = number_format($tot_imp2, 2, ".", "");
                $segundo["taxable_amount"] = number_format($base_imp2, 2, ".", "");
                $segundo["percent"] = "0";
                
                $impues[]=$segundo;
            }

            if(empty($impues) && $this->totales["Total"] > 0){
                $percentCalc = ($this->totales["Total_Iva"] > 0 && $this->totales["Total"] > 0) ? round(($this->totales["Total_Iva"] * 100) / $this->totales["Total"], 2) : 0;
                $impues[] = [
                    "tax_id" => 1,
                    "tax_amount" => number_format($this->totales["Total_Iva"], 2, ".", ""),
                    "taxable_amount" => number_format($this->totales["Total"], 2, ".", ""),
                    "percent" => $percentCalc,
                ];
            }
        
            
            /*$descue[0]["charge_indicator"]=false;
            $descue[0]["discount_id"]=1;
            $descue[0]["allowance_charge_reason"]='Discount';
            $descue[0]["amount"]=number_format($tot_des,0,".","");
            $descue[0]["base_amount"]=number_format($base_des,0,".","");*/
              
                
            $resultado["tax_totals"]=$impues;
            //$resultado["allowance_charges"]=$descue;
            $resultado["credit_note_lines"]=$productos_finales;
            
            //var_dump($resultado);
            //exit;
            return($resultado);
            
        }
        
        private function getCUFE1(){
            $nit=self::getNit();
            $fecha=str_replace(":","",$this->nota_credito['Fecha']);
            $fecha=str_replace("-","",$fecha);
            $fecha=str_replace(" ","",$fecha);
            $variable=$this->nota_credito['Codigo'].";".$fecha.";".number_format($this->totales['Total'],2,".","").";".number_format($this->totales['Total_Iva'],2,".","").";"."01".";".$this->totales['Impuesto'].";".$nit.";"."O-99".$this->cliente['Id_Cliente'].";".$this->resolucion['Clave_Tecnica'];
            return hash('sha1',$variable);
        }
         private function getCUFE()
        {
            $nit = self::getNit();
            $fecha = $this->nota_credito['Fecha'];
            $neto = number_format($this->totales['Total'] + $this->totales['Total_Iva'], 2, ".", "");
            $variable = $this->nota_credito['Codigo'] . "" . str_replace(" ", "", $fecha) . "-05:00" . number_format($this->totales['Total'], 2, ".", "") . "01" . number_format($this->totales['Total_Iva'], 2, ".", "") . "040.00030.00" . $neto . $nit . $this->cliente['Id_Cliente'] . $this->resolucion['Clave_Tecnica'] . '1';
            return hash('sha384', $variable);
        }
    
        private function getDatos($tipo_nota_credito, $id_nota_credito,$resolucion_facturacion){
            
            
            $oItem=new complex("Resolucion", "Id_Resolucion", $resolucion_facturacion);
            $this->resolucion=$oItem->getData();
            unset($oItem);
            //var_dump("Resolucion",$this->resolucion);
            //echo json_encode($this->resolucion);
    
            $oItem=new complex($tipo_nota_credito, "Id_".$tipo_nota_credito, $id_nota_credito ); 
            $this->nota_credito=$oItem->getData();
            unset($oItem);
            //var_dump("Nota Credito",$this->nota_credito);

            $tipoFactura = $this->tipo_nota_credito == 'Nota_Credito_Global' ? $this->nota_credito['Tipo_Factura'] : 'Factura_Venta';

          /*  var_dump($tipoFactura);
            var_dump('Id_'.$tipoFactura);
            var_dump($this->nota_credito["Id_Factura"]);*/
            $oItem=new complex($tipoFactura, 'Id_'.$tipoFactura, $this->nota_credito["Id_Factura"]);
            $this->factura=$oItem->getData();
            unset($oItem);
            
            //var_dump("Factura",$this->factura);
            #var_dump($this->factura);exit;
            // Destinatario 
            
            $query='SELECT * FROM Configuracion Limit 1';
            
            $oCon=new consulta();
            $oCon->setQuery($query);
            $this->configuracion=$oCon->getData();            
            unset($oItem);
            
            //var_dump("Configuracion",$this->configuracion);
            
            $this->cliente = $this->GetTercero($this->nota_credito);
            
            //var_dump("Cliente",$this->cliente);
            
          /*   $query="SELECT C.* FROM Cliente C WHERE C.Id_Cliente=".$this->nota_credito['Id_Cliente'];
    
            $oCon=new consulta();
            $oCon->setQuery($query);
            $this->cliente=$oCon->getData();
            unset($oCon);
 */ 
            
            if($tipo_nota_credito=='Nota_Credito' ){
                $query='SELECT PF.*, 

                IFNULL(CONCAT(P.Nombre_Comercial, " - ",P.Principio_Activo, " ", P.Cantidad,"", P.Unidad_Medida, " " , P.Presentacion," 
                (LAB- ", P.Laboratorio_Comercial,") ", P.Invima, " CUM:", P.Codigo_Cum, " - Lote: ",PF.Lote),
                CONCAT(P.Nombre_Comercial, " (LAB-", P.Laboratorio_Comercial, ") - Lote: ",PF.Lote)) as Producto ,

                P.Codigo_Cum AS CUM

                FROM Producto_'.$tipo_nota_credito.' PF 
                INNER JOIN '.$tipo_nota_credito.' F ON F.Id_'.$tipo_nota_credito.' = PF.Id_'.$tipo_nota_credito.'
                LEFT JOIN Producto P ON P.Id_Producto = PF.Id_Producto
                WHERE PF.Id_'.$tipo_nota_credito.'='.$id_nota_credito;

            }
            elseif($tipo_nota_credito=='Nota_Credito_Global' ){
                $query='SELECT PF.*, PF.Nombre_Producto as Producto , P.Codigo_Cum AS CUM

                FROM Producto_'.$tipo_nota_credito.' PF 
                INNER JOIN '.$tipo_nota_credito.' F ON F.Id_'.$tipo_nota_credito.' = PF.Id_'.$tipo_nota_credito.'
                LEFT JOIN Producto P ON P.Id_Producto = PF.Id_Producto
                WHERE PF.Id_'.$tipo_nota_credito.'='.$id_nota_credito;
                
            }else{


                if( $tipoFactura == 'Factura_Capita'){
                     //probada
                    $query='SELECT PF.*, PF.Nombre_Producto as Producto , IFNULL(PT.Mes,"") as CUM
                    FROM Producto_'.$tipo_nota_credito.' PF 
                    INNER JOIN '.$tipo_nota_credito.' F ON F.Id_'.$tipo_nota_credito.' = PF.Id_'.$tipo_nota_credito.'
                    INNER JOIN '.$tipoFactura.' PT ON PT.Id_'.$tipoFactura.' = F.Id_Factura
                    WHERE PF.Id_'.$tipo_nota_credito.'='.$id_nota_credito;

                }elseif( $tipoFactura == 'Factura_Administrativa' ){
                    //probada
                    $query='SELECT PF.*, PF.Nombre_Producto as Producto , IFNULL(DF.Referencia,"") as CUM
                    FROM Producto_'.$tipo_nota_credito.' PF 
                    INNER JOIN '.$tipo_nota_credito.' F ON F.Id_'.$tipo_nota_credito.' = PF.Id_'.$tipo_nota_credito.'
                    LEFT JOIN Descripcion_Factura_Administrativa DF ON DF.Id_Descripcion_Factura_Administrativa = PF.Id_Producto
                    WHERE PF.Id_'.$tipo_nota_credito.'='.$id_nota_credito;
                }else{
                    $query='SELECT PF.*, PF.Nombre_Producto as Producto , P.Codigo_Cum AS CUM
                    FROM Producto_'.$tipo_nota_credito.' PF 
                    INNER JOIN Producto_'.$tipoFactura.' PT ON PT.Id_Producto_'.$tipoFactura.' = PF.Id_Producto
                   
                    LEFT JOIN Producto P ON P.Id_Producto = PT.Id_Producto
                    WHERE PF.Id_'.$tipo_nota_credito.'='.$id_nota_credito;
                }

              /*   $query='SELECT PF.*, PF.Nombre_Producto as Producto 
                FROM Producto_'.$tipo_nota_credito.' PF 
                INNER JOIN '.$tipo_nota_credito.' F ON F.Id_'.$tipo_nota_credito.' = PF.Id_'.$tipo_nota_credito.'
                WHERE PF.Id_'.$tipo_nota_credito.'='.$id_nota_credito; */
        
            }
         
            $oCon=new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
            $this->productos=$oCon->getData();
            unset($oCon);
            
           
            $campoPrecio = $tipo_nota_credito == 'Nota_Credito' ? 'Precio_Venta' : 'Precio_Nota_Credito';

            $query='SELECT IFNULL(SUM(Cantidad*'.$campoPrecio.'),0) as Total, IFNULL(SUM((Cantidad*'.$campoPrecio.')*(Impuesto/100)),0) as Total_Iva,
                      0 as Descuento,  Impuesto 
            FROM Producto_'.$tipo_nota_credito.'
            WHERE Id_'.$tipo_nota_credito.'='.$id_nota_credito;
            $oCon=new consulta();
            $oCon->setQuery($query);
            $this->totales=$oCon->getData();
           
            unset($oCon);
            
        }
        
         private function getNombre(){
          $nit=self::getNit();
          //$codigo=(INT)str_replace($this->resolucion['Codigo'],"", $this->nota_credito['Codigo']);
          $codigo = (int)preg_replace('/\D/', '', $this->nota_credito['Codigo']);
          $nombre=str_pad($nit, 10, "0", STR_PAD_LEFT)."000".date("y").str_pad($codigo, 8, "0", STR_PAD_LEFT);
          return $nombre;
        }
        
            
        function getNit()
        {
            $nit = explode("-", $this->configuracion['NIT']);
            $nit = str_replace(".", "", $nit[0]);
            return $nit;
        }
        
        function getFecha($tipo){
            $fecha=explode(" ",$this->nota_credito['Fecha']);
          
            if($tipo=='Fecha'){
                return $fecha[0];
            }elseif ($tipo=='Hora') {
                return $fecha[1];
            }
        }
        
        private function getImpuesto(){
            $query='SELECT * FROM Impuesto WHERE Valor>0 LIMIT 1';
            $oCon=new Consulta();
            $oCon->setQuery($query);
            $iva=$oCon->getData();
    
            return $iva['Valor'];
            
        }
    
        private function GetQr($cude){
            $fecha=str_replace(":","",$this->nota_credito['Fecha']);
            $fecha=str_replace("-","",$fecha);
            $fecha=str_replace(" ","",$fecha);
    
            $qr="NotaCredito: ".$this->nota_credito['Codigo']."\n";
            $qr.="Fecha: ".$fecha."\n";
            $qr.="NitFac: ".$this->getNit()."\n";
            $qr.="DocAdq: ".$this->nota_credito['Id_Cliente']."\n";
            $qr.="ValFac: ".number_format($this->totales['Total'],2,".","")."\n";
            $qr.="ValIva: ".number_format($this->totales['Total_Iva'],2,".","")."\n";
            $qr.="ValOtroIm: 0.00 \n";
            $qr.="ValFacIm: ".number_format(($this->totales['Total_Iva']+$this->totales['Total']),2,".","")."\n";
            $qr.="CUDE: ".$cude."\n";
            $qr = generarqrFE($qr);
            
            return ($qr);
        }

        private function GetTercero($nota){
            $cliente = [];

         
            if($this->tipo_nota_credito == 'Nota_Credito_Global' && $nota['Tipo_Factura'] == 'Factura_Administrativa' ){
                
              
             
                $query = 'SELECT * FROM Factura_Administrativa WHERE Id_Factura_Administrativa = '.$nota['Id_Factura'];
                $oCon = new consulta();
                $oCon->setQuery($query);
                
                $facturaAdmin= $oCon->getData();
                unset($oCon);

             
                $query = '';
                switch ($facturaAdmin['Tipo_Cliente']) {
                    case 'Funcionario':
                        $query = 'SELECT "Funcionario" AS Tipo_Tercero, Identificacion_Funcionario AS Id_Cliente , 
                                        CONCAT_WS(" ",Nombres,Apellidos)AS Nombre,
                                        Correo,Celular, "Natural" AS Tipo, "CC" AS Tipo_Identificacion,
                                        "" AS Digito_Verificacion, "Simplificado" AS Regimen, Direccion_Residencia AS Direccion
                            FROM Funcionario WHERE Identificacion_Funcionario = '.$facturaAdmin['Id_Cliente'];
                        break;

                    case 'Proveedor':
                        $query = 'SELECT "Proveedor" AS Tipo_Tercero, Id_Proveedor AS Id_Cliente , 
                                   
                                    (CASE 
                                        WHEN Tipo = "Juridico" THEN Razon_Social
                                        ELSE  COALESCE(Nombre, CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )
                                        
                                    END) AS Nombre,
                                    Correo,
                                     Celular, Tipo, "NIT" AS Tipo_Identificacion,
                                    Digito_Verificacion, Regimen, Direccion 
                        FROM Proveedor WHERE Id_Proveedor = '.$facturaAdmin['Id_Cliente'];
                        break;

                    case 'Cliente':
                        return $this->getCliente($nota);
                        break;
                        
                    default:

                        break;
                }
                
                $oCon = new consulta();
                $oCon->setQuery($query);
                
                $cliente= $oCon->getData();
                unset($oCon);

                return $cliente;

            }else{
               return $this->getCliente($nota);
            }

           
        }

       private function getCliente($nota) {

    // Determinamos si es un cliente o un proveedor
    $idClienteProveedor = isset($this->factura['Id_Cliente']) ? $this->factura['Id_Cliente'] : (isset($this->factura['Id_Proveedor']) ? $this->factura['Id_Proveedor'] : null);
    
    // Si no existe un Id_Cliente ni un Id_Proveedor, retornamos un error o null
    if (!$idClienteProveedor) {
        throw new Exception('No se encontr� Id_Cliente ni Id_Proveedor en la factura.');
    }

    // Construimos la consulta para buscar el cliente o proveedor
    $query = 'SELECT "Cliente" AS Tipo_Tercero, Id_Cliente, 
                (CASE 
                    WHEN Tipo = "Juridico" THEN Razon_Social
                    ELSE  COALESCE(Nombre, CONCAT_WS(" ", Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido) )
                END) AS Nombre,
                 Correo_Persona_Contacto AS Correo,
                 Celular, Tipo, Tipo_Identificacion,
                 Digito_Verificacion, Regimen, Direccion, Contribuyente, Autorretenedor
             FROM Cliente 
             WHERE Id_Cliente = ' . intval($idClienteProveedor);
             
    $oCon = new consulta();
    $oCon->setQuery($query);
    $cliente = $oCon->getData();
    unset($oCon);

    return $cliente;
}


       

        
    }
    
  

?>
