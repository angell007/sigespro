<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
require_once('../../vendor/autoload.php');

include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta_paginada.php');
include_once('../../helper/response.php');
include_once('../../helper/makeConditions.php');

new  GetProductosDispensacion();

class GetProductosDispensacion
{
    public $response;
    private $queryObj;
    public $productos;

    public function __construct()
    {
        $this->Id_Dispensacion =  (isset($_REQUEST['Id_Dispensacion']) && $_REQUEST['Id_Dispensacion'] != '') ? $_REQUEST['Id_Dispensacion'] : '';
        $this->Id_Punto_Dispenscion =  (isset($_REQUEST['Id_Punto_Dispenscion']) && $_REQUEST['Id_Punto_Dispenscion'] != '') ? $_REQUEST['Id_Punto_Dispenscion'] : '';
     
        $this->queryObj = new consulta();
        $this->init();
    }

    public function init()
    {
        try {
            $this->GetProductos();
            $this->validarProductos();

            show($this->response);
        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }
    }

    public function GetProductos()
    {
        $query = 'SELECT PD.*, P.Nombre_Comercial
                    FROM Producto_Dispensacion PD 
                    INNER JOIN Producto P ON P.Id_Producto = PD.Id_Producto
                    WHERE PD.Id_Dispensacion = ' .  $this->Id_Dispensacion;

        $this->queryObj->SetQuery($query);
        $this->queryObj->setTipo('Multiple');

        $this->productos = $this->queryObj->getData();

    }

    public function validarProductos()
    {
        $PuntoConProductos= [];
        $PuntoSinProductos= [];
        $productos=[];
        $p=[];
        $i=-1;
        
        $bandera = true;

        foreach ($this->productos['data'] as  $prod) {$i++;
            
            $query = 'SELECT SUM(INU.Cantidad) as Cant
                        FROM Producto P
                        INNER JOIN Inventario_Nuevo INU ON P.Id_Producto = INU.Id_Producto   
                        INNER JOIN Estiba E ON E.Id_Estiba = INU.Id_Estiba     
                        WHERE  P.Id_Producto = ' . $prod['Id_Producto'].  " AND E.Id_Punto_Dispensacion = ". $this->Id_Punto_Dispenscion."  GROUP BY INU.Id_Producto";

            $this->queryObj->SetQuery($query);
            $this->queryObj->setTipo('Multiple');

            $p = $this->queryObj->getData();
           
            if ($p['data'][$i]['Cant'] <= 0) {
       
                $bandera = false;                 
                break;     
            }
        }
        $this->response['bandera'] = $bandera;
    }
}