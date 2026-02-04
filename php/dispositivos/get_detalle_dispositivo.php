<?php

use PhpParser\Node\Stmt\Echo_;

require_once('../../vendor/autoload.php');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.consulta.php');
require_once('../../helper/response.php');

new  Dispositivo();

use Carbon\Carbon as Carbon;

class Dispositivo
{
    public $request;

    public function __construct()
    {

        $this->request  = (isset($_REQUEST['id']) && ($_REQUEST['id'] != '0') ? json_decode($_REQUEST['id'], true) : '');
        $this->init();

    }

    public function init()
    {
        try {

            $query="SELECT  Dispositivo.*, PD.Nombre AS Punto  FROM Dispositivo_Radicacion As Dispositivo
            LEFT JOIN Punto_Dispensacion As PD ON Dispositivo.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion
            WHERE Id_Dispositivo_Radicacion = $this->request  ";
            $oCon=new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Simple');
            $data = $oCon->getData()[0];
            unset($oCon);
            show(mysuccess($data));

        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }

    }
}
