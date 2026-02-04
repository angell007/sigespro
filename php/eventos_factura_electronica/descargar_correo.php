<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');
ini_set('max_execution_time', 0);
date_default_timezone_set('America/Bogota');

require_once '/home/sigesproph/public_html/class/class.php_mailer.php';
require_once '/home/sigesproph/public_html/class/class.complex.php';
require_once '/home/sigesproph/public_html/php/eventos_factura_electronica/class.uncompress.php';
try {

    $correo = new EnviarCorreo();
    $zip = new ZipArchive;
    $data = $correo->DescargarCorreo(' ');
    $connection = $data[0];
    $emails = $data[1];


    if ($emails) {
        $max = max_Correo();
        $array = array_slice($emails, $max - 1);
        $index = 0;

        foreach ($array as $email) {
            $oItem = new complex('Correos_Cargados', 'Id', 1);
            $data = $oItem->getData();
            $oItem->Id_Correo = $email;
            $oItem->Fecha_Creado = date('Y-m-d H:i:s');
            $overview = imap_fetch_overview($connection, $email, 0);
            $asunto = imap_utf8($overview[0]->subject);
            $asunto = str_replace(['Undeliverable:', 'Fwd:', 'RV:'], '', $asunto);
            $asunto = explode(';', $asunto);
            $oItem->save();
            if (count($asunto) > 1 && trim($asunto[3]) == '01' && trim($asunto[0]) != '804016084') {
                $structure = imap_fetchstructure($connection, $email);
                $attachments = array();
                if (isset($structure->parts) && count($structure->parts)) {
                    for ($i = 0; $i < count($structure->parts); $i++) {
                        $attachments[$i] = array(
                            'is_attachment' => false,
                            'filename' => '',

                            'name' => '',
                            'attachment' => '',
                        );
                        if ($structure->parts[$i]->ifdparameters) {
                            foreach ($structure->parts[$i]->dparameters as $object) {
                                if (strtolower($object->attribute) == 'filename') {
                                    $attachments[$i]['is_attachment'] = true;
                                    $attachments[$i]['filename'] = $object->value;
                                }
                            }
                        }
                        if ($structure->parts[$i]->ifparameters) {
                            foreach ($structure->parts[$i]->parameters as $object) {
                                if (strtolower($object->attribute) == 'name') {
                                    $attachments[$i]['is_attachment'] = true;
                                    $attachments[$i]['name'] = $object->value;
                                }
                            }
                        }
                        if ($attachments[$i]['is_attachment']) {
                            $attachments[$i]['attachment'] = imap_fetchbody($connection, $email, $i + 1);
                            if ($structure->parts[$i]->encoding == 3) { // 3 = BASE64
                                $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                            } elseif ($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                                $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                            }
                        }
                    }
                }

                foreach ($attachments as $adjunto) {
                    if ($adjunto['is_attachment']) {
                        $nombreFichero = $adjunto['filename'];
                        $adj = $adjunto['attachment'];
                        if ($adj) {
                            $archivo = uniqid();
                            $ruta_archivo = "$archivo.zip'";
                            file_put_contents($ruta_archivo, $adj);
                            $unzip = new Unzip($ruta_archivo);
                            $rsp = $unzip->uncompress();
                            unlink($ruta_archivo);
                            echo json_encode($rsp);
                        }
                    }
                }

                // Unset desired flag
                imap_clearflag_full($connection, $email, "\\Seen");
            }
        }
    }
    imap_close($connection);
} catch (\Throwable $th) {
    $oItem = new complex('Correos_Cargados', 'Id', 2);
    $oItem->Fecha_Creado = date('Y-m-d H:i:s');
    $oItem->Asunto = $th->getMessage();
    $oItem->save();
}
function max_Correo()
{
    $O = new complex('Correos_Cargados', 'Id', 1);
    $max = $O->getData()['Id_Correo'];
    return $max;
        
}
