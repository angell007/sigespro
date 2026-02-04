<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../helper/response.php');

new  GetDetalles();

class GetDetalles
{
    public $Id_Dipensacion;

    public function __construct()
    {
        $this->Id_Dispensacion = (isset($_REQUEST['Id_Dispensacion']) ? $_REQUEST['Id_Dispensacion'] : '');

        $this->init();
    }   

    public function init() 
    {
        $query='SELECT INU.Cantidad, PD.Cantidad_Formulada as Requerida,
                CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida) as NombreProducto 
                FROM Dispensacion D 
                INNER JOIN Producto_Dispensacion PD ON PD.Id_Dispensacion = D.Id_Dispensacion
                INNER JOIN Producto P ON PD.Id_Producto = P.Id_Producto
                LEFT JOIN Inventario_Nuevo INU ON INU.Id_Producto = PD.Id_Producto 
                WHERE D.Id_Dispensacion = ' .$this->Id_Dispensacion.' GROUP BY PD.Id_Producto ';
		$oCon= new consulta();
		$oCon->setQuery($query);
		$oCon->setTipo('Multiple');
		show($oCon->getData());     
		unset($oCon);
    }
}
