<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../helper/response.php');

new  GetroductsDispensacion();

class GetroductsDispensacion
{

    public $id;
    public $response = [];

    public function __construct()
    {
        $this->id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');
        $this->init();
    }

    public function init()
    {
        try {

                if (!$this->id) {
                  show(myerror('Id no se encuentra'));
                }

                $this->getProductos();
                $this->getInventario();
                show($this->normilizeResponse());
            
        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }

    }

    public function getProductos()
        {
            $desde = 'Dispensacion';

            $query = 'SELECT 
            PD.Cantidad_Formulada, BN.Nombre, PD.Id_Producto_Dispensacion,
            P.Nombre_Comercial,P.Embalaje, P.Laboratorio_Comercial, P.Laboratorio_Generico, 
            P.Peso_Presentacion_Minima, P.Peso_Presentacion_Regular, P.Imagen,
            P.Peso_Presentacion_Maxima,P.Codigo_Barras,P.Presentacion, P.Id_Subcategoria,  P.Id_Producto,

            IFNULL(CONCAT(P.Principio_Activo, " ",
            P.Presentacion, " ",
            P.Concentracion, " - ", 
            P.Cantidad," ",
            P.Unidad_Medida), P.Nombre_Comercial) AS Nombre_Producto
            FROM Producto_'.$desde.' PD
             INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto
             INNER JOIN Dispensacion As D  ON D.Id_Dispensacion = PD.Id_Dispensacion
             INNER JOIN Punto_Dispensacion As PuD  ON PuD.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion 
             INNER JOIN Bodega_Nuevo As BN  ON BN.Id_Bodega_Nuevo = PuD.Id_Bodega_Despacho 
             WHERE PD.Id_'.$desde.' =' . $this->id . '
             ORDER BY Nombre_Producto ASC';
             
             
             $oCon = new consulta();
             $oCon->setQuery($query);
             $oCon->setTipo('Multiple');

             $this->productos = $oCon->getData();
             unset($oCon);
             return $this;
             
        }

        public function getInventario()
        {

                foreach ($this->productos as $pos => $producto) {

                            $query = 'SELECT 
                            I.Lote, I.Cantidad, I.Fecha_Vencimiento,
                            I.Id_Inventario_Nuevo,
                            P.Codigo_Barras As CodeProducto,
                            E.Codigo_Barras As CodeEstiba,
                            IFNULL(CONCAT(P.Principio_Activo, " ",
                            P.Presentacion, " ",
                            P.Concentracion, " - ", 
                            P.Cantidad," ",
                            P.Unidad_Medida), P.Nombre_Comercial) AS Nombre_Producto,
                            I.Alternativo,
                            E.Nombre AS Nombre_Estiba, E.Id_Estiba,
                            (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada) As CantidadDisponible
                            FROM Producto P
                            INNER JOIN Inventario_Nuevo I ON I.Id_Producto = P.Id_Producto
                            INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba
                            WHERE  P.Id_Producto = '.$producto['Id_Producto'].'
                             AND (I.Cantidad-I.Cantidad_Apartada-I.Cantidad_Seleccionada)  <> 0 
                             AND DATE(I.Fecha_Vencimiento)  >  DATE_ADD(CURRENT_DATE(), INTERVAL 89 DAY)
                             ORDER BY I.Fecha_Vencimiento ASC , E.Nombre DESC, Nombre_Producto ASC';
                             

                            $oCon = new consulta();
                            $oCon->setQuery($query);
                            $oCon->setTipo('Multiple');

                            $this->complementProduct($oCon->getData(), $producto, $pos );
                            unset($oCon);
                }

                return $this;
        }


        public function complementProduct($complements, $product, $pos)
        {

            $cantidad = +$product['Cantidad_Formulada'];
            
            foreach ($complements as  $complement) {


                $complement['Bodega'] = $product['Nombre'];
                
                if($cantidad > 0 ){
                    
                    if ($complement['CantidadDisponible'] >=  $cantidad) {
                        $complement['CantidadSeleccionada'] = $cantidad;
                        $cantidad = 0 ;
                    }
                    
                    if ($complement['CantidadDisponible'] <  $cantidad) {
                        $complement['CantidadSeleccionada'] = $complement['CantidadDisponible'];
                        $cantidad -=  $complement['CantidadDisponible'];
                    }
                }

                $complement["Habilitado"] = "true";
                $complement["Clase"] = "blur";
                $complement["Validado"] = false;
                $complement["Codigo_Validado"] = false;
                $complement['Product'] =  $product;

                array_push($this->response, $complement);

            } 
        }       


        public function normilizeResponse()
        {
                $cantidad=0;
                foreach ($this->response as $key => $producto) {

                    if ($producto['Nombre_Estiba'] != $this->response[$key-1]['Nombre_Estiba'] || $key==0) {
                        
                        foreach($this->response as $productoComparacion){

                            if ($producto['Nombre_Estiba'] == $productoComparacion['Nombre_Estiba']) {
                                $cantidad++;     
                            }
                        }
                    
                        $this->response[$key]['Cantidades_Productos_Estiba'] = $cantidad;
                        $cantidad=0;
                    }
                }

                return $this;
         }       
    }

    