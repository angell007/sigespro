<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.costo_promedio.php');
include_once('../ajuste_individual_nuevo/helper.ajuste_individual.php');

$id_acta_recepcion = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
$productos =   isset($_REQUEST['productos']) ? $_REQUEST['productos'] : false;
$funcionario = isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : false;
$tipo_acta = (isset($_REQUEST['tipo_acta'])) ? $_REQUEST['tipo_acta'] : false;

$cambio_estiba = (isset($_REQUEST['cambio_estiba'])) ? $_REQUEST['cambio_estiba'] : false;

$productos = (array) json_decode($productos, true);

if ($id_acta_recepcion && $productos && $tipo_acta) {
    //validar si el acta ya fue acomodada

    $actaExistente=validarActa();
   
    if (!$actaExistente) {
        foreach ($productos as $producto) {
            //elimar espacios existentes
            $producto['Lote']=trim($producto['Lote']);
    
            //buscar si el producto está previamente guardado
            $query = "SELECT Id_Inventario_Nuevo FROM Inventario_Nuevo
                      WHERE Id_Producto=$producto[Id_Producto] 
                      AND Lote='$producto[Lote]'
                      # AND Fecha_Vencimiento='$producto[Fecha_Vencimiento]'
                      AND Id_Estiba=$producto[Id_Estiba]";  
            $oCon = new consulta();
            $oCon->setQuery($query);
            $inventario = $oCon->getData();
            unset($oCon);

            if ($inventario) {
              //Prev 100
                $oItem = new complex('Inventario_Nuevo', 'Id_Inventario_Nuevo', $inventario['Id_Inventario_Nuevo']);
                $cantidad = number_format($producto["Cantidad"], 0, "", "");
                $cantidad_final = $oItem->Cantidad + $cantidad;
                $oItem->Cantidad = $cantidad_final;
              #  $oItem->Costo = $producto['Precio'];
                $id_inventario = $oItem->Id_Inventario_Nuevo;
                if(isset($producto['Fecha_Vencimiento']) && $producto['Fecha_Vencimiento']!=''){
                    $oItem->Fecha_Vencimiento = $producto['Fecha_Vencimiento'];
                }

                // 50 => 150 
                //cantDisp = I.Cantidad - (Apartada, Seleccionada) - Contrato(Contrato.Cantidad - (Apartada, Seleccionada))
                //150-50 = 100

            }else{    
                $oItem = new complex('Inventario_Nuevo', 'Id_Inventario_Nuevo');
                $oItem->Codigo = substr(hexdec(uniqid()), 2, 12);
                $oItem->Cantidad = $producto["Cantidad"];
                $oItem->Id_Producto = $producto["Id_Producto"];
                $oItem->Codigo_CUM = $producto["Codigo_Cum"];
                $oItem->Lote = strtoupper($producto["Lote"]);
                $oItem->Fecha_Vencimiento = $producto["Fecha_Vencimiento"];
                $oItem->Id_Estiba = $producto["Id_Estiba"];
                #$oItem->Costo = $producto['Precio'];
                $oItem->Identificacion_Funcionario = $funcionario;   
            }
            $oItem->save();
            $id_inventario = $oItem->getId();
            unset($oItem);


            //valida existencia del contrato
            $validarContrato=validarContrato();
    
            if ($validarContrato &&  $validarContrato['Id_Contrato']) {
                $idcontrato = $validarContrato['Id_Contrato'];
                
               //buscar si el producto de este contrato fue previamente guardado
                    $query = "SELECT IC.Id_Inventario_Contrato, PC.Id_Producto_Contrato
                                FROM Inventario_Contrato IC
                                INNER JOIN Producto_Contrato PC 
                                WHERE IC.Id_Contrato=$idcontrato AND PC.Id_Producto=$producto[Id_Producto]";                
                    $oCon = new consulta();
                    $oCon->setQuery($query);
                    $inventariocontrato = $oCon->getData();
                    unset($oCon);
            
                if ($inventariocontrato) {
                    $oItem = new complex('Inventario_Contrato', 'Id_Inventario_Contrato', $inventariocontrato['Id_Inventario_Contrato']);
                    $cantidad = number_format($producto["Cantidad"], 0, "", "");
                    $cantidad_final = $oItem->Cantidad + $cantidad;
                    $oItem->Cantidad = $cantidad_final;
                    // $oItem->Id_Inventario_Nuevo = $id_inventario;
                }else{    
                    $oItem = new complex('Inventario_Contrato', 'Id_Inventario_Contrato');
                    $oItem->Id_Contrato = $idcontrato;
                    $oItem->Id_Inventario_Nuevo = $id_inventario;
                    $oItem->Id_Producto_Contrato = $inventariocontrato["Id_Producto_Contrato"];
                    $oItem->Cantidad = $producto["Cantidad"];
                    // $oItem->Cantidad_Apartada = $producto["Fecha_Vencimiento"];
                    // $oItem->Cantidad_Seleccionada = $producto["Id_Estiba"];
                }
                $oItem->save();
                unset($oItem);
        
            }
           

            if ($tipo_acta == 'Ajuste_Individual') {
                if (!$cambio_estiba) {             
                    # actulizar costos
                    $costopromedio =  new Costo_Promedio($producto["Id_Producto"],$producto["Cantidad"],$producto["Costo"]);
                    $costopromedio->actualizarCostoPromedio(); 
                }           
               #guardar donde se acomodó
                $oItem = new complex('Producto_Ajuste_Individual', 'Id_Producto_Ajuste_Individual', $producto['Id_Producto_Ajuste_Individual']);
                $oItem->Id_Estiba_Acomodada = $producto["Id_Estiba"];
                $oItem->save();
                unset($oItem);   
            }
        }
    
        if ($tipo_acta == 'Ajuste_Individual') {
            # Actividad Ajuste...
            guardarActividad( $id_acta_recepcion, $funcionario,'Se acomodó en las estibas el ajuste individual','Acomodada');
        } 
        if ($id_inventario) {

            actualizarActa();
            if ($tipo_acta=='Acta_Recepcion') {
                guardarActividadActa();
            }
            $resultado['Titulo'] = "Operación Exitosa";
            $resultado['Mensaje'] = "Se han acomodado e ingresado correctamente el acta al inventario";
            $resultado['Tipo'] = "success";
    
        }else{
            $resultado['Titulo'] = "Error";
            $resultado['Mensaje'] = "Ha ocurrido un error inesperado. Por favor intentelo de nuevo";
            $resultado['Tipo'] = "error";
        } 
    }else{
        $resultado['Titulo'] = "Error Acta Acomodada";
        $resultado['Mensaje'] = "Esta acta ya ha sido acomodada previamente, por favor verifique";
        $resultado['Tipo'] = "error";
    }
    
    echo json_encode($resultado);

} else {
    $resultado['Titulo'] = "Ha ocurrido un error inesperado";
    $resultado['Mensaje'] = "Faltan datos necesarios. Por favor intentelo de nuevo";
    $resultado['Tipo'] = "error";
}

    function guardarActividadActa(){
        global $id_acta_recepcion,$funcionario,$tipo_acta;
        //Consultar el codigo del acta y el id de la orden de compra
        $acta_data=consultarCodigoActa();
        //Guardando paso en el seguimiento del acta en cuestion
        $oItem = new complex('Actividad_Orden_Compra', "Id_Acta_Recepcion_Compra");
        $oItem->Id_Orden_Compra_Nacional = $acta_data['Id_Orden_Compra_Nacional'];
        $oItem->Id_Acta_Recepcion_Compra = $id_acta_recepcion;
        $oItem->Identificacion_Funcionario = $funcionario;
        $oItem->Detalles = "Se acomodó y se ingreso el Acta con codigo " . $acta_data['Codigo'];
        $oItem->Fecha = date("Y-m-d H:i:s");
        $oItem->Estado = 'Acomodada';
        $oItem->save();
        unset($oItem);

    }

    function consultarCodigoActa(){
        //Consultar el codigo del acta y el id de la orden de compra
        global $id_acta_recepcion;

        $query_codido_acta = 'SELECT 
        Codigo,
        Id_Orden_Compra_Nacional
        FROM
        Acta_Recepcion
        WHERE
        Id_Acta_Recepcion = ' . $id_acta_recepcion;

        $oCon = new consulta();
        $oCon->setQuery($query_codido_acta);
        $acta_data = $oCon->getData();
        unset($oCon);
        return $acta_data;
    }

    function actualizarActa(){
        global $id_acta_recepcion,$tipo_acta;
        //acutalizar acta

        $oItem = new complex($tipo_acta, 'Id_'.$tipo_acta, $id_acta_recepcion);
        if ($tipo_acta=='Ajuste_Individual') {
            # code...
            $oItem->Estado_Entrada_Bodega = 'Acomodada';
        }else{
            $oItem->Estado = 'Acomodada';
        }
    
        $oItem->save();
        unset($oItem);
    }

    function validarActa(){
        global $id_acta_recepcion, $tipo_acta;
        $query='SELECT Id_'.$tipo_acta.' FROM '.$tipo_acta .' WHERE Id_'.$tipo_acta.' = '.$id_acta_recepcion.' AND Estado = "Acomodada"';
        $oCon = new consulta();
        $oCon->setQuery($query);
        $acta=$oCon->getData();
        
        return $acta;
    }
    function validarContrato(){

        global $id_acta_recepcion;

        $query = "SELECT PC.Id_Contrato 
                    FROM Acta_Recepcion AR
                    INNER JOIN Orden_Compra_Nacional OCN ON AR.Id_Orden_Compra_Nacional = OCN.Id_Orden_Compra_Nacional
                    INNER JOIN Pre_Compra PC ON OCN.Id_Pre_Compra = PC.Id_Pre_Compra
                    WHERE AR.Id_Acta_Recepcion = $id_acta_recepcion "; 
                    
        $oCon = new consulta();
        $oCon->setQuery($query);
        $contrato = $oCon->getData();
        unset($oCon);
      
        return $contrato;
    }


