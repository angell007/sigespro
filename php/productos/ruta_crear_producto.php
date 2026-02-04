<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

$rutas = array('wqeu-3uhz.json','994u-gm46.json','8tya-2uai.json','6nr4-fx8r.json','7c5e-muu4.json');
$cum = isset($_REQUEST['cum']) ? $_REQUEST['cum'] : false;
$result = [];

if($cum) {
    $cum = explode('-', $cum);
} else {
    $cum = [];
}

if (count($cum) > 1) {
    for ($i=0; $i < 3; $i++) { 

        if ($i < 3) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => 'https://www.datos.gov.co/resource/'.$rutas[$i].'?expediente=' . $cum[0] . '&consecutivocum=' . $cum[1],
                CURLOPT_USERAGENT => 'Codular Sample cURL Request'
            ));
            $resp   = curl_exec($curl);
            $resp = (array) json_decode($resp, true);

            if (curl_getinfo($curl,CURLINFO_HTTP_CODE) == 200 && count($resp)>0) {
                $result = $resp;
                break;
            }
            curl_close($curl);
        }
    }    
} else {
    for ($i=3; $i < count($rutas); $i++) { 

        if ($i >2) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => 'https://www.datos.gov.co/resource/'.$rutas[$i].'?expediente=' . $cum[0],
                CURLOPT_USERAGENT => 'Codular Sample cURL Request'
            ));
            $resp   = curl_exec($curl);
            $resp = (array) json_decode($resp, true);

            if (curl_getinfo($curl,CURLINFO_HTTP_CODE  ) == 200 && count($resp)>0) {
                $result = $resp;
                break;
            }
            curl_close($curl);
        }
    }
}

echo json_encode($result);

?>