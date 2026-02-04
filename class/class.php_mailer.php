<?php

require_once __DIR__ . '/../class_new/service_account_mailer.php';
require_once __DIR__ . '/../class_new/vendor/autoload.php';


/**
 * Adaptador que mantiene la interfaz de PHPMailer pero usa Gmail Service Account
 */
class EnviarCorreoAdapter
{
    private $serviceAccount;
    private $addresses = [];
    private $ccAddresses = [];
    private $bccAddresses = [];
    private $attachments = [];
    private $subject = '';
    private $body = '';
    private $isHtml = true;
    private $charset = 'UTF-8';
    private $fromEmail = '';
    private $fromName = '';
    private $errorInfo = '';
    private $useServiceAccount = true; // Solo usa Service Account, sin fallback SMTP

    // Configuraciones por defecto para diferentes tipos de envío
    private $configs = [
        'default' => [
            'user' => 'sistemas@prohsa.com',
            'name' => 'ProH S.A. - Sistemas'
        ],
        'facturacion' => [
            'user' => 'facturacionelectronicacont@prohsa.com', 
            'name' => 'ProH S.A. - Facturación'
        ],
        'productos' => [
            'user' => 'sistemas@prohsa.com',
            'name' => 'ProH S.A. - Productos'
        ]
    ];

    public function __construct($tipo = 'default')
    {
        try {

            // Cargar configuración
            $mailConfig = require __DIR__ . '/../config/config.email.php';
            $config = $this->configs[$tipo] ?? $this->configs['default'];

            // Verificar credenciales
            if (!file_exists($mailConfig['gmail_api']['credentials_path'])) {
                throw new Exception(
                    "Archivo de credenciales no encontrado en: " .
                        $mailConfig['gmail_api']['credentials_path']
                );
            }

            $this->serviceAccount = new EnviarCorreoServiceAccount(
                $mailConfig['gmail_api']['credentials_path'],
                $config['user']
            );

            $this->fromEmail = $config['user'];
            $this->fromName = $config['name'];
        } catch (Exception $e) {
            error_log("Error inicializando Service Account: " . $e->getMessage());
            throw new Exception("Service Account no disponible: " . $e->getMessage());
        }
    }

    // Métodos para mantener compatibilidad con PHPMailer
    public function AddAddress($email, $name = '')
    {
        $this->addresses[] = $email;
    }

    public function AddCC($email, $name = '')
    {
        $this->ccAddresses[] = $email;
    }

    public function addBCC($email, $name = '')
    {
        $this->bccAddresses[] = $email;
    }

    public function AddAttachment($path, $name = '')
    {
        if (file_exists($path)) {
            $this->attachments[] = [
                'path' => $path,
                'name' => $name ?: basename($path)
            ];
        }
    }

    public function setFrom($email, $name = '')
    {
        $this->fromEmail = $email;
        if ($name) {
            $this->fromName = $name;
        }
    }

    public function IsHTML($ishtml = true)
    {
        $this->isHtml = $ishtml;
    }

    public function Send()
    {
        try {
            if (empty($this->addresses)) {
                throw new Exception("No se ha especificado ningún destinatario");
            }

            // Preparar destinatarios
            $allRecipients = array_merge($this->addresses, $this->ccAddresses, $this->bccAddresses);
            $primaryRecipient = !empty($this->addresses) ? $this->addresses[0] : $allRecipients[0];

            // Validar formato de email
            if (!filter_var($primaryRecipient, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("El correo destinatario no es válido: " . $primaryRecipient);
            }
            // Si hay un solo archivo adjunto, usar el método simple
            $attachment = '';
            if (!empty($this->attachments)) {
                $attachment = $this->attachments[0]['path'];
            }

            $result = $this->serviceAccount->EnviarMail(
                $primaryRecipient,
                $this->subject,
                $this->body,
                $attachment
            );

            if ($result['Estado'] === 'Exito') {
                $this->limpiarDatos();
                return true;
            } else {
                $this->errorInfo = $result['Respuesta'];
                return false;
            }
        } catch (Exception $e) {
            $this->errorInfo = $e->getMessage();
            return false;
        }
    }

    // Método mejorado para envío con múltiples destinatarios
    public function SendToMultiple()
    {
        $results = [];
        $allRecipients = array_merge($this->addresses, $this->ccAddresses, $this->bccAddresses);

        foreach ($allRecipients as $recipient) {
            try {
                $attachment = !empty($this->attachments) ? $this->attachments[0]['path'] : '';

                $result = $this->serviceAccount->EnviarMail(
                    $recipient,
                    $this->subject,
                    $this->body,
                    $attachment
                );

                $results[] = [
                    'recipient' => $recipient,
                    'success' => $result['Estado'] === 'Exito',
                    'message' => $result['Respuesta']
                ];
            } catch (Exception $e) {
                $results[] = [
                    'recipient' => $recipient,
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        $this->limpiarDatos();
        return $results;
    }

    // Método específico para facturas DIAN
    public function EnviarFacturaDian($des, $subject, $msg, $zipFile)
    {
        try {
            // Usar el remitente configurado para este tipo de correo
            if (!empty($this->fromEmail)) {
                $this->serviceAccount->cambiarUsuario($this->fromEmail);
            }

            $result = $this->serviceAccount->EnviarMail($des, $subject, $msg, $zipFile);

            if ($result['Estado'] === 'Exito') {
                return [
                    'Estado' => 'Exito',
                    'Respuesta' => 'Factura DIAN enviada correctamente via Service Account',
                    'Metodo' => 'Gmail API - Service Account'
                ];
            } else {
                return [
                    'Estado' => 'Error',
                    'Respuesta' => $result['Respuesta']
                ];
            }
        } catch (Exception $e) {
            return [
                'Estado' => 'Error',
                'Respuesta' => 'Error enviando factura: ' . $e->getMessage()
            ];
        }
    }

    // Método para cambio de contraseña
    public function enviarMailCambioClave($destino, $asunto, $mensaje)
    {
        try {
            $result = $this->serviceAccount->EnviarMailCambioClave($destino, $asunto, $mensaje);

            if ($result['Estado'] === 'Exito') {
                return [
                    'Estado' => 'Exito',
                    'Respuesta' => 'Correo de cambio de clave enviado correctamente'
                ];
            } else {
                return [
                    'Estado' => 'Error',
                    'Respuesta' => $result['Respuesta']
                ];
            }
        } catch (Exception $e) {
            return [
                'Estado' => 'Error',
                'Respuesta' => 'Error enviando correo: ' . $e->getMessage()
            ];
        }
    }

    // Método para verificar el estado del Service Account
    public function verificarEstado()
    {
        return $this->serviceAccount->verificarPermisos();
    }

    // Método para cambiar usuario dinámicamente


    private function limpiarDatos()
    {
        $this->addresses = [];
        $this->ccAddresses = [];
        $this->bccAddresses = [];
        $this->attachments = [];
        $this->subject = '';
        $this->body = '';
    }

    // Getters y setters para compatibilidad
    public function __set($property, $value)
    {
        switch (strtolower($property)) {
            case 'subject':
                $this->subject = $value;
                break;
            case 'body':
                $this->body = $value;
                break;
            case 'charset':
                $this->charset = $value;
                break;
            case 'fromname':
                $this->fromName = $value;
                break;
        }
    }

    public function __get($property)
    {
        switch (strtolower($property)) {
            case 'errorinfo':
                return $this->errorInfo;
            case 'subject':
                return $this->subject;
            case 'body':
                return $this->body;
            default:
                return null;
        }
    }

    // Método para verificar el estado del Service Account


    // Método para cambiar usuario dinámicamente
    public function cambiarUsuario($usuario)
    {
        $this->serviceAccount->cambiarUsuario($usuario);
        $this->fromEmail = $usuario;
    }
}

/**
 * Clase actualizada que reemplaza EnviarCorreo manteniendo compatibilidad
 */
class EnviarCorreo extends EnviarCorreoAdapter
{
    public function __construct($tipo = 'default')
    {
        parent::__construct($tipo);
    }

    public function EnviarMail($des, $subject, $msg, $file = '')
    {
        $this->subject = $subject;
        $this->body = $msg;
        $this->IsHTML(true);

        if ($des) {
            $this->AddAddress($des);
        }

        // Mantener compatibilidad con el CC automático
        $this->AddAddress('desarrollo@prohsa.com');
        //$this->AddAddress('talentohumano@prohsa.com');

        if ($file) {
            $this->AddAttachment($file);
        }

        return $this->Send();
    }

    public function EnviarMailProductos($des, $subject, $msg, $file = '')
    {
        $this->cambiarUsuario('sistemas@prohsa.com');
        $this->subject = $subject;
        $this->body = $msg;
        $this->IsHTML(true);

        if (is_array($des) && count($des) > 0) {
            foreach ($des as $d) {
                $this->AddAddress($d);
            }
        }

        $this->AddAddress('desarrollo@prohsa.com');

        if ($file) {
            $this->AddAttachment($file);
        }

        return $this->SendToMultiple();
    }

    // Mantener métodos originales para funcionalidad IMAP (si es necesaria)
    public function DescargarCorreo($busqueda)
    {
        throw new Exception("Funcionalidad IMAP no disponible en Service Account. Use la clase original si necesita esta función.");
    }

    public function DescargarCorreo2($busqueda)
    {
        throw new Exception("Funcionalidad IMAP no disponible en Service Account. Use la clase original si necesita esta función.");
    }
}
