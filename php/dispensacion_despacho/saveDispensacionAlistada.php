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

    public $d;
    public $funcionario;
    public $productos;
    public $current;
    public $productoDis;

    public function __construct()
    {
        $this->id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');

        $this->funcionario = (isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : '');
        $this->productos = (isset($_REQUEST['productos']) ? $_REQUEST['productos'] : '');
        $this->productos = json_decode($this->productos);
        $this->init($this->productos);
    }


    public function init($productos)
    {

        try {

            foreach ($productos as $productoComparacion) {
                $this->current = $productoComparacion->Product->Id_Producto;
                $this->extraerProducts();
                $this->OperarDisp($productoComparacion->Product->Id_Producto_Dispensacion);
                $this->productsEquals;
            }

            $this->changeState();

            show(mysuccess(''));
        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }
    }

    public function changeState()
    {
        $query="UPDATE Dispensacion SET Estado_Alistamiento='Alistado' WHERE Id_Dispensacion=  $this->id  ";
		$oCon= new consulta();
		$oCon->setQuery($query);
		$oCon->createData();     
		unset($oCon);

    }

    public function extraerProducts()
    {
        $this->productsEquals  =    array_filter($this->productos, [$this, 'myfiltrarEquals']);
        $this->productos  =    array_filter($this->productos, [$this, 'myfiltrarLeftover']);
    }


    public function myfiltrarEquals($element)
    {
        return $element->Product->Id_Producto == $this->current;
    }

    public function myfiltrarLeftover($element)
    {
        return $element->Product->Id_Producto != $this->current;
    }

    public function OperarDisp($idProdDis)
    {
        $query = "SELECT * FROM Producto_Dispensacion WHERE Id_Producto_Dispensacion = $idProdDis ";
        $oCon = new consulta();
        $oCon->setQuery($query);
        $this->productoDis = $oCon->getData();
        unset($oCon);

        $this->createDisp();
        $this->deleteOldDist();
    }

    public function createDisp()
    {
        foreach ($this->productsEquals as  $product) {

            $oItem   = new complex('Producto_Dispensacion', "Id_Producto_Dispensacion");

            foreach ($this->productoDis as $index => $value) {

                if ($index == 'Id_Inventario_Nuevo' || ($value != "" &&  $index != 'Id_Producto_Dispensacion')) {

                    switch ($index) {

                        case 'Cantidad_Entregada':
                            $oItem->$index = $product->CantidadSeleccionada;
                            break;

                        case 'Id_Inventario_Nuevo':
                            $oItem->$index = $product->Id_Inventario_Nuevo;
                            break;

                        case 'Lote':
                            $oItem->$index = $product->Lote;
                            break;

                        default:
                            $oItem->$index = $value;
                            break;
                    }
                }
            }

            $oItem->save();
            unset($oItem);

        }
    }

    public function deleteOldDist()
    {
        $query="DELETE FROM Producto_Dispensacion WHERE Id_Producto_Dispensacion = ". $this->productoDis['Id_Producto_Dispensacion'];
		$oCon= new consulta();
		$oCon->setQuery($query);
		$oCon->deleteData();     
		unset($oCon);
    }

}
