<?php

use PhpParser\Node\Stmt\Echo_;

require_once('../../vendor/autoload.php');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.http_response.php');
include_once('../../helper/response.php');

$inventrio = new  Inventario;

use Carbon\Carbon as Carbon;

class Inventario
{
    public $idBodega;

    public function __construct()
    {
        $this->idBodega = (isset($_REQUEST['bodega']) && ($_REQUEST['bodega'] != '0') ? $_REQUEST['bodega'] : '');
        $this->init();
    }

    public function init()
    {
        if ($this->idBodega != '') {
            show(mysuccess($this->anexarInventario($this->getData())));
        }
        show(myerror('No se logra identificar la Bodega'));
    }

    public function getData()
    {
        $query = "SELECT 
        P.Nombre_Comercial , P.Laboratorio_Comercial, P.Laboratorio_Generico, P.Codigo_Cum, PR.Id_Producto , SUM(PR.Cantidad) As Cant, 

        CONCAT(
            IFNULL(P.Principio_Activo, '  '), ' ',
            P.Presentacion,' ',
            IFNULL(P.Concentracion, '  '), ' ',
            P.Cantidad,' ', 
            P.Unidad_Medida,  ' LAB: ', 
            P.Laboratorio_Comercial
            ) AS Nombre_Producto

        FROM Remision As R
        INNER JOIN Producto_Remision As PR ON PR.Id_Remision = R.Id_Remision
        INNER JOIN Producto As P ON P.Id_Producto = PR.Id_Producto
        INNER JOIN Remision As  RE ON PR.Id_Remision = RE.Id_Remision
        WHERE RE.Tipo_Origen = 'Bodega' AND RE.Id_Origen = $this->idBodega 
        AND Cast(RE.Fecha As Date) >=  '" .Carbon::now()->subDays(150)->format('Y-m-d'). "'
        GROUP BY PR.Id_Producto ORDER BY Cant DESC LIMIt 50";
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $data = $oCon->getData();
        unset($oCon);
        return $data;

    }

    public function anexarInventario($productos)
    {
        foreach ($productos as $key => $producto) {
            $productos[$key]['Inventario'] = $this->getInventario($producto['Id_Producto'], $producto['Codigo_Cum'], (int)$this->getEstiba($producto['Id_Producto']));
            if (count($productos[$key]['Inventario']) == 0) {
                unset($productos[$key]);
            }
        }

        return $this->transformData(array_values($productos));
    }

    public function getEstiba($IdProducto)
    {
        $query = "SELECT SUM(PR.Cantidad) As Cant, InN.Id_Estiba FROM Producto_Remision As PR  
        INNER JOIN Inventario_Nuevo As  InN ON PR.Id_Inventario_Nuevo = InN.Id_Inventario_Nuevo
        INNER JOIN Remision As  RE ON PR.Id_Remision = RE.Id_Remision
        INNER JOIN Estiba As E ON E.Id_Estiba =  InN.Id_Estiba
        INNER JOIN Bodega_Nuevo As B ON B.Id_Bodega_Nuevo =  E.Id_Bodega_Nuevo
        WHERE InN.Id_Producto =  $IdProducto AND PR.Id_Producto =  $IdProducto AND RE.Fecha <=  
        '" . Carbon::now()->subDays(150)->format('Y-m-d') . "'
        AND B.Id_Bodega_Nuevo = $this->idBodega
        GROUP BY InN.Id_Estiba ORDER BY Cant DESC LIMIt 1
        ";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('simple');
        $data = $oCon->getData()['Id_Estiba'];
        unset($oCon);
        return $data;
    }
    
    public function getInventario($IdProducto, $cum, $IdEstiba)
    {

        $query = "SELECT B.Nombre As Bodega, E.Nombre As Estiba, InN.Lote, InN.Fecha_Vencimiento, InN.Cantidad, InN.Codigo_Cum, InN.Id_Inventario_Nuevo, E.Id_Estiba,   

        CONCAT(
            IFNULL(P.Principio_Activo, '  '), ' ',
            P.Presentacion,' ',
            IFNULL(P.Concentracion, '  '), ' ',
            P.Cantidad,' ', 
            P.Unidad_Medida,  ' LAB: ', 
            P.Laboratorio_Comercial
            ) AS Nombre_Producto

        FROM Inventario_Nuevo As InN
        INNER JOIN Estiba As E ON E.Id_Estiba =  InN.Id_Estiba
        INNER JOIN Bodega_Nuevo As B ON B.Id_Bodega_Nuevo =  E.Id_Bodega_Nuevo
        INNER JOIN Producto As P ON P.Id_Producto =  InN.Id_Producto

        WHERE InN.Id_Producto = $IdProducto AND InN.Codigo_Cum = '$cum'  
        AND B.Id_Bodega_Nuevo = $this->idBodega  
        AND  InN.Cantidad <> 0 
        AND E.Id_Estiba = $IdEstiba
        ";

    
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $data = $oCon->getData();
        unset($oCon);
        return $data;

    }

    public function transformData($productos)
    {

        $temporal = [];
        if (count($productos) > 0) {
            $ramdon = array_rand($productos,  5);
            for ($i = 0; $i < count($ramdon); $i++) {
                array_push($temporal, $productos[$ramdon[$i]]);
            }
        }
        return $temporal;
    }
}
