<?php

class ValidarCufe
{
    private $cufe;
    private $Factura;
    private $Eventos;
    private $url = "https://catalogo-vpfe.dian.gov.co/Document/Details?trackId=";

    public function __construct($cufe)
    {
        $this->cufe = strtolower($cufe);
        $this->url .= $this->cufe;
        if($this->cufe !=''){
            $this->consultarCufe();
        }

    }

    private function consultarCufe()
    {

        $cc = curl_init($this->url);
        curl_setopt($cc, CURLOPT_FAILONERROR, true); // Required for HTTP error codes to be reported via our call to curl_error($ch)
        curl_setopt($cc, CURLOPT_RETURNTRANSFER, 1);
        $url_content = curl_exec($cc);

        if (curl_errno($cc)) {
            return "error";

        }
        curl_close($cc);

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($url_content, 1);
        libxml_clear_errors();
        $a = $dom->getElementById('html-gdoc');
        $eventos = $dom->getElementById('container1');

        $array_eventos = explode("\n", str_replace(["\r", ""], '', $eventos->nodeValue));

        $array_eventos = array_map('trim', $array_eventos);
        foreach ($array_eventos as $key => $value) {
            if ($value == "") {
                unset($array_eventos[$key]);
            }
        }
        $array_eventos = array_values($array_eventos);
        $eventos_factura=[];
        $j = -1;
        foreach ($array_eventos as $key => $evento) {
            if (utf8_decode($evento) == 'Código' || $evento == 'Código') {
                $index = 0;
                for ($i = ($key + 7); $i < count($array_eventos); $i += 8) {
                    $eventos_factura[$index]['Codigo'] = $array_eventos[$i];
                    $index++;
                }
            }
            if (utf8_decode($evento) == 'Descripción' || $evento == 'Descripción') {
            // echo json_encode(utf8_decode($evento));

                $index = 0;
                for ($i = ($key + 7); $i < count($array_eventos); $i += 8) {
                    $eventos_factura[$index]['Description'] = $array_eventos[$i];
                    $index++;
                }
            }
            if (utf8_decode($evento) == 'Fecha' || $evento == 'Fecha') {
                // echo json_encode($array_eventos); exit;
                $index = 0;
                for ($i = ($key + 7); $i < count($array_eventos); $i += 8) {
                    $eventos_factura[$index]['Fecha'] = $array_eventos[$i];
                    $index++;
                }
            }
            if (utf8_decode($evento) == 'Nit Emisor' || $evento == 'Nit Emisor') {
                $index = 0;
                for ($i = ($key + 7); $i < count($array_eventos); $i += 8) {
                    $eventos_factura[$index]['Nit_Emisor'] = $array_eventos[$i];
                    $index++;
                }
            }
            if (utf8_decode($evento) == 'Emisor' || $evento == 'Emisor') {
                $index = 0;
                for ($i = ($key + 7); $i < count($array_eventos); $i += 8) {
                    $eventos_factura[$index]['Emisor'] = $array_eventos[$i];
                    $index++;
                }
            }
            if (utf8_decode($evento) == 'Nit Receptor' || $evento == 'Nit Receptor') {
                $index = 0;
                for ($i = ($key + 7); $i < count($array_eventos); $i += 8) {
                    $eventos_factura[$index]['Nit_Receptor'] = $array_eventos[$i];
                    $index++;
                }
            }
            if (utf8_decode($evento) == 'Receptor' || $evento == 'Receptor') {
                $index = 0;
                for ($i = ($key + 7); $i < count($array_eventos); $i += 8) {
                    // echo "+$i,";
                    $eventos_factura[$index]['Receptor'] = $array_eventos[$i];
                    $index++;
                }
            }
            $j++;
        }
        $this->Eventos = $eventos_factura;

        /** Validacion de la informacion general de la factura */
        $respuesta = [];

        $array = explode("\n", str_replace(["\r", ""], '', $a->nodeValue));
        $array = array_map('trim', $array);

        foreach ($array as $key => $value) {
            if ($value == "") {
                unset($array[$key]);
            }
        }
        $array = array_values($array);
        foreach ($array as $key => $value) {
            if ($value == "CUFE:") {
                $respuesta['CUFE'] = $array[$key + 1];
            } else if (strpos($value, 'Folio') !== false) {
                $valores = explode('Folio:', $value);
                $folio = trim($valores[1]);
                $valores = explode('Serie:', $valores[0]);
                $prefijo = count($valores) > 1 ? trim($valores[1]) : '';
                $respuesta['Factura']['Prefijo'] = $prefijo;
                $respuesta['Factura']['Consecutivo'] = $folio;
                $respuesta['Codigo'] = "$prefijo$folio";
            } else if (strpos($value, 'EMISOR') !== false) {
                $nit = trim(explode('NIT:', $array[$key + 1])[1]);
                $nombre = trim(explode('Nombre:', $array[$key + 2])[1]);
                $respuesta['Proveedor']['NIT'] = $nit;
                $respuesta['Proveedor']['Nombre'] = $nombre;
            } else if (strpos($value, 'RECEPTOR') !== false) {
                $nit = trim(explode('NIT:', $array[$key + 1])[1]);
                $nombre = trim(explode('Nombre:', $array[$key + 2])[1]);
                $respuesta['Cliente']['NIT'] = $nit;
                $respuesta['Cliente']['Nombre'] = $nombre;
            }
        }
        $this->Factura = $respuesta;

    }
    public function getEstructura()
    {
      $this->Factura['Eventos']=(array)$this->Eventos;
      return $this->Factura;
    }
}
