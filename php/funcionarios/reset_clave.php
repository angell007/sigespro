<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');
require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.php_mailer.php';

$user = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');
$fecha = date('Y-m-d H:i:s');
$codigo = generarCodigoRecuperacion();

$oItem = new complex("Funcionario", "Identificacion_Funcionario", $user);
$funcionario = $oItem->getData();

// $resultado["Mensaje"] = "Para Realizar cambio de clave, ingrese el codigo enviado a su correo";
// $resultado["Tipo"] = "success";
// $resultado["Usuario"] = $funcionario['Correo'];
// echo json_encode($funcionario);exit;

if ($funcionario) {

    $asunto = "SOLICITUD DE CAMBIO DE CONTRASEÑA";

    $mensaje = getHtml();
    $mail = new EnviarCorreo();

    $cambio = $mail->enviarMailCambioClave($funcionario['Correo'], $asunto, $mensaje);
    if ($cambio["Estado"] == "Exito") {
        $oItem->Fecha_Codigo_Recuperacion = $fecha;
        $oItem->Codigo_Recuperacion = str_replace(' ', '', $codigo);
        $oItem->save();
        unset($oItem);
        $resultado["Mensaje"] = "Para Realizar cambio de clave, ingrese el codigo enviado a su correo";
        $resultado["Tipo"] = "success";
        $resultado["Usuario"] = $funcionario['Correo'];
    }

} else {
    $resultado['Tipo'] = "error";
    $resultado['Mensaje'] = "Funcionario no existe";
    $resultado['Usuario'] =null;
}

echo json_encode($resultado);exit;

function getHtml()
{
    global $funcionario, $codigo, $fecha;
    $html = '<!doctype html>
      <html>
        <head>
          <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
          <meta name="viewport" content="width=device-width">
          <style>img {border: none;-ms-interpolation-mode: bicubic;max-width: 100%;}
                body {background-color: #f6f6f6;font-family: sans-serif;-webkit-font-smoothing: antialiased;font-size: 14px;line-height: 1.4;margin: 0;padding: 0;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;}
                .body {background-color: #f6f6f6;width: 100%}
                .container {display: block;margin: 0 auto !important;max-width: 580px;padding: 10px;width: 580px}
                .content {box-sizing: border-box;display: block;margin: 0 auto;max-width: 580px;padding: 10px;}
                .main {background: #fff;border-radius: 3px;width: 100%;}
                .wrapper {box-sizing: border-box;padding: 20px;}
                .content-block {padding-bottom: 10px;padding-top: 10px;}
                .footer {clear: both;margin-top: 10px;text-align: center;width: 100%;}
                .footer a,.footer p,.footer span,.footer td {color: #999;font-size: 12px;text-align: center;}
                h5 {font-size: 14px;font-weight: 700;text-align: left;color: #3c5dc6;}
                h1 {font-weight: 700;text-align: center;color: #3c5dc6;}
                p {font-family: sans-serif;font-size: 11px;font-weight: 400;margin: 0;margin-bottom: 15px;text-align: justify;}
                span {color: #000;font-family: sans-serif;font-weight: 600;}
                a {color: #3c5dc6;text-decoration: none;}
                .logo {border: 0;outline: 0;text-decoration: none;display: block;text-align: center;}
                .align-center {text-align: center !important;}
                .preheader {color: transparent;display: none;height: 0;max-height: 0;max-width: 0;opacity: 0;overflow: hidden;mso-hide: all;visibility: hidden;width: 0;}
                .powered-by a {text-decoration: none;text-align: center !important;}
                hr {border: 0;border-bottom: 1px solid #eeeef0;margin: 8px 0;}
                @media all {.ExternalClass {  width: 100%;}.ExternalClass,.ExternalClass div,.ExternalClass font,.ExternalClass p,.ExternalClass span,.ExternalClass td {  line-height: 100%}.apple-link a {  color: inherit !important;  font-family: inherit !important;  font-size: inherit !important;  font-weight: inherit !important;  line-height: inherit !important;  text-decoration: none !important;}#MessageViewBody a {  color: inherit;  text-decoration: none;  font-size: inherit;  font-family: inherit;  font-weight: inherit;  line-height: inherit;}}
          </style>
        </head>
        <body class=""><span class="preheader">SOLICITUD DE RESTABLECIMIENTO DE CLAVE</span>
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
                            <td><img alt="ProH" height="100" border="0" class="logo" src="https://sigesproph.com.co/assets/images/LogoProh.jpg">
                              <hr>
                              <p>Estimado, <span>' . $funcionario["Nombres"] . '</span></p>
                              <p>RECIBIMOS UNA SOLICITUD DE RESTABLECIMIENTO DE CONTRASEÑA</p>
                              <hr>
                              <h5>DETALLE</h5>
                              <hr>
                              <p><span>Hora: </span>' . $fecha . '</p>
                              <p><span>Codigo: </span></p><h1>' . $codigo . '</h1>
                              <h5>NOTA:</h5>
                              <p>El codigo tiene una validez de 5 minutos para usarse, después de este tiempo debes solicitar uno nuevo</p>
                              <p class="content-block powered-by"> No comparta el codigo con terceros</p>
                              <p class="content-block powered-by">No responda este mensaje, ha sido enviado de forma automática</p>
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                  <div class="footer">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                      <tr>
                        <td class="content-block"><span class="apple-link align-center">Productos Hospitalarios S.A</span></td>
                      </tr>
                      <tr>
                        <td class="content-block powered-by align-center">Desarrollado por<a href="https://www.corvuslab.co/">Corvus Lab</a>.</td>
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
    return $html;
}

function generarCodigoRecuperacion()
{
    return rand(100, 999) . ' ' . rand(100, 999);
}
