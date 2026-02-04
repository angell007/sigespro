<?php

use PhpParser\Node\Stmt\Echo_;

require_once('../../vendor/autoload.php');


include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.http_response.php');
include_once('../../helper/response.php');

new  ConteoUno();

use Carbon\Carbon as Carbon;

class ConteoUno
{
    public $datos;

    public function __construct()
    {
        $this->datos  = (isset($_REQUEST['data']) && ($_REQUEST['data'] != '') ? json_decode($_REQUEST['data']) : '');
        $this->bodega  = (isset($_REQUEST['bodega']) && ($_REQUEST['bodega'] != '') ? json_decode($_REQUEST['bodega']) : '');
        $this->documento  = (isset($_REQUEST['documento']) && ($_REQUEST['documento'] != '') ? json_decode($_REQUEST['documento']) : '');

        $this->init();
    }

    public function init()
    {
        try {

            foreach ($this->datos as $producto) {

            $oItem = new complex('Producto_Doc_Inventario_Auditable', 'Id_Producto_Doc_Inventario_Auditable');
            $oItem->Id_Producto = $producto->producto;
            $oItem->Id_Inventario_Nuevo = 0;
            $oItem->Primer_Conteo = ($producto->inventario->cantidad == '') ? 0 : $producto->inventario->cantidad;
            $oItem->Lote = $producto->inventario->Lote;
            $oItem->Id_Inventario_Nuevo = $producto->inventario->Id_Inventario_Nuevo;
            $oItem->Fecha_Vencimiento = $producto->inventario->Fecha_Vencimiento;
            $oItem->Fecha_Primer_Conteo = Carbon::now()->format('Y-m-d H:m');
            $oItem->Id_Doc_Inventario_Auditable = $this->documento;
            $oItem->Id_Estiba =  $producto->inventario->Id_Estiba;
            $oItem->Cantidad_Inventario = $producto->inventario->Cantidad;
            $oItem->save();
            unset($oItem);

            }

           return  $this->updateDocument();

        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }
    }

    public function updateDocument()
    {
        $oItem = new complex('Doc_Inventario_Auditable','Id_Doc_Inventario_Auditable',$this->documento);
        $oItem->getData();
        $oItem->Estado = 'Primer Conteo';
        $oItem->save();
        $response['tipo']= 'success';
        $response['title']= 'Cambio de estado exitoso';
        $response['mensaje']= 'Documento actualizado con éxito';
        show($response);
    }
}
