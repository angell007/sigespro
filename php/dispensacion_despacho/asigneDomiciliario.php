<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../helper/response.php');
include_once('./../../class/class.consulta.php');

new saveDispensacion();

class saveDispensacion
{
    public $dis;
    public $domiciliario;
    public $entrega;
    public $EntregaExterna;
    public $Punto_Dispensacion;

    public function __construct()
    {
        $this->dis = (isset($_REQUEST['dis']) ? $_REQUEST['dis'] : '');
        $this->domiciliario = (isset($_REQUEST['domiciliario']) ? $_REQUEST['domiciliario'] : '');
        $this->EntregaExterna = (isset($_REQUEST['EntregaExterna']) ? $_REQUEST['EntregaExterna'] : '');
        $this->Punto_Dispensacion = (isset($_REQUEST['Punto_Dispensacion']) ? $_REQUEST['Punto_Dispensacion'] : '');
        $this->init();
    }

    public function init()
    {
        try {

            if (!empty($this->dis)) {

                $dom  = $this->domiciliario ? $this->domiciliario : '(NULL)';
                $punt = $this->Punto_Dispensacion ? $this->Punto_Dispensacion : '(NULL)';
                $ext  =  $this->EntregaExterna ? $this->EntregaExterna : '(NULL)';

                $query = "UPDATE Dispensacion 
                          SET Id_Domiciliario  = $dom,
                              Id_Punto_Dispensacion_Entrega= $punt,
                              Entrega_Externa= '$ext'
                          WHERE Id_Dispensacion=  $this->dis";
                $oCon = new consulta();
                $oCon->setQuery($query);
                $oCon->createData();
                unset($oCon);


                $query = "UPDATE Dispensacion_Domicilio SET Estado = 'Asignado', Id_Domiciliario = $dom  WHERE Id_Dispensacion =  $this->dis ";
                $oCon = new consulta();
                $oCon->setQuery($query);
                $oCon->createData();
                unset($oCon);
                // show(mysuccess(''));


                
            }
            $resultado['mensaje'] = "Se Asignó el tipo de Entrega correctamente";
            $resultado['tipo'] = "success";
            $resultado['titulo'] = "Asignado Tipo Entrega";

            
            echo json_encode($resultado);

            // show(myerror('No existe Domiciliario'));
        } catch (\Throwable $th) {
            // show(myerror($th->getMessage()));
        }
    }
}
