<?php
$url = 'https://api-dian.sigesproph.com.co/api/ubl2.1/support-document';
$login = 'facturacion@prohsa.com';
$password = '804016084';

$datos = [
    "type_document_id" => 11,
    "cuds_propio" => "a3314f22fe9a17945d371fb029b360a835f8f7cd9791400358e217d77bd03f14e7671e932b5f16444674fca9d98d4d80",
    "resolution_id" => "31",  // En lugar de 36
    "code" => "CUDS1725",     // Siguiente de la resolución 41
    "prefix" => "CUDS",
    "number" => 2001,
    "file" => "CUDS2001",
    "issue_date" => "2025-12-04",
    "due_date" => "2025-12-04",
    "payment_form" => [
        "payment_form_id" => "1",
        "payment_method_id" => 75
    ],
    "origin_reference" => [
        "code" => "",
        "date" => ""
    ],
    "customer" => [
        "type_document_identification_id" => 6,
        "dv" => "5",
        "identification_number" => "80007438",
        "type_regime_id" => 1,
        "tax_id" => 16,
        "address" => "DG 49 85 17 TO 2 APTO 203",
        "email" => "cercury@hotmail.com",
        "merchant_registration" => "No Tiene",
        "municipality_id" => 149,
        "country_id" => 46,
        "language_id" => 25,
        "type_liability_id" => 122,
        "name" => "CESAR LEONARDO DIAZ BETANCOURT",
        "phone" => "6014772365",
        "type_organization_id" => 2
    ],
    "invoice_lines" => [
        [
            "allowance_charges" => [],
            "tax_totals" => [
                [
                    "tax_id" => 1,
                    "tax_amount" => "0.00",
                    "taxable_amount" => "400000.00",
                    "percent" => "0.00"
                ]
            ],
            "invoiced_quantity" => "1",
            "line_extension_amount" => 400000,
            "free_of_charge_indicator" => false,
            "reference_price_id" => 1,
            "price_amount" => "400000.00",
            "code" => "10101501",
            "description" => "CANON BODEGA SOACHA CORRESPONDIENTE MES NOVIEMBRE 2025",
            "type_item_identification_id" => 3,
            "base_quantity" => "1",
            "unit_measure_id" => 70,
            "note" => "CANON BODEGA SOACHA CORRESPONDIENTE MES NOVIEMBRE 2025",
            "pack_size_numeric" => "1",
            "model_name" => "CANON BODEGA SOACHA CORRESPONDIENTE MES NOVIEMBRE 2025",
            "invoice_period" => [
                "date" => "2025-12-04",
                "description_code" => "1",
                "description" => "Por operacion"
            ],
            "withholding_tax_totals" => []
        ]
    ],
    "tax_totals" => [
        [
            "tax_id" => 1,
            "tax_amount" => "0.00",
            "taxable_amount" => "400000.00",
            "percent" => "0"
        ]
    ],
    "legal_monetary_totals" => [
        "line_extension_amount" => "400000.00",
        "tax_exclusive_amount" => "400000.00",
        "tax_inclusive_amount" => "400000.00",
        "allowance_total_amount" => "0.00",
        "charge_total_amount" => "0.00",
        "payable_amount" => "400000.00"
    ],
    "withholding_tax_totals" => []
];

$data = json_encode($datos, JSON_UNESCAPED_UNICODE);

echo "<h3>JSON que se enviará:</h3>";
echo "<pre>" . htmlspecialchars($data) . "</pre>";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-type: application/json",
    "Accept: application/json",
    "Authorization: Basic " . base64_encode($login . ':' . $password),
    "Content-length: " . strlen($data),
]);

$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "<h3>HTTP Code: $http_code</h3>";
echo "<h3>Respuesta RAW:</h3>";
echo "<pre>" . htmlspecialchars($result) . "</pre>";

if (curl_errno($ch)) {
    echo "<h3>cURL Error:</h3>";
    echo "<pre>" . curl_error($ch) . "</pre>";
}

$json = json_decode($result, true);
echo "<h3>JSON Decodificado:</h3>";
echo "<pre>" . print_r($json, true) . "</pre>";

curl_close($ch);
?>