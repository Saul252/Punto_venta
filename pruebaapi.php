<?php

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://sandbox.factura.com/api/v4/cfdi40/create',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
 
 CURLOPT_POSTFIELDS => '{
  "Receptor": {
    "UID": "697be2a401cec"
  },
  "TipoDocumento": "ingreso",
  "RegimenFiscal": 605,
  "UsoCFDI": "S01",
  "Serie": 5491692,
  "FormaPago": "03",
  "MetodoPago": "PUE",
  "Moneda": "MXN",
  "LugarExpedicion": "77020",
  "EnviarCorreo": false,
  "Conceptos": [
    {
      "ClaveProdServ": "81112101",
      "Cantidad": 1,
      "ClaveUnidad": "E48",
      "Unidad": "Unidad de servicio",
      "Descripcion": "Desarrollo a la medida",
      "ValorUnitario": 229.90,
      "ObjetoImp": "02",
      "Impuestos": {
        "Traslados": [
          {
            "Base": 229.90,
            "Impuesto": "002",
            "TipoFactor": "Tasa",
            "TasaOCuota": "0.16",
            "Importe": 36.78
          }
        ],
        "Locales": [
          {
            "Base": 229.90,
            "Impuesto": "ISH",
            "TipoFactor": "Tasa",
            "TasaOCuota": "0.03",
            "Importe": 6.90
          }
        ]
      }
    }
  ]
}'

,
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'F-PLUGIN: 9d4095c8f7ed5785cb14c0e3b033eeb8252416ed',
    'F-Api-Key: JDJ5JDEwJFVrYWlnRnltT09xUEtzVnZSOUJvSnVCSGNPb1JNUU9Yb2p6Zjh1bnBxUThXV0tBZDJudXJ5',
'F-Secret-Key: JDJ5JDEwJHlBYUxMMW5WTXg2cjh0M3hXY3FzYXVzbXpFalk5MHZyRDZyMUcxZUpyS1Viay42dmp6WG4y'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;