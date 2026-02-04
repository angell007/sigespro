<?php
$codigos_rem='';
function crearRem($grupos , $cabecera){
     global $codigos_rem;
     foreach ($grupos as $keyGrupo => $grupo) {
          # code...

          $item_remision=GetLongitudRemision();
          $remisiones=array_chunk($grupo['Productos'],$item_remision);
     
          foreach ($remisiones as  $value) {
               $id_remision=SaveEncabezado($cabecera, $grupo);       
               SaveProductoRemision($id_remision,$value);
          }
     }
     return $codigos_rem;
}



function SaveEncabezado($cabecera,$grupo ){
     global  $bodega , $codigos_rem;

     $configuracion = new Configuracion();
     $oItem = new complex("Remision","Id_Remision");
     $oItem->Fecha = date("Y-m-d H:i:s");
     $oItem->Meses = 1;
     $oItem->Tipo = 'Cliente';
     $oItem->Prioridad = 1;
     $oItem->Meses = 4;
     $oItem->Nombre_Destino = $cabecera['cliente']['Nombre'];
     $oItem->Nombre_Origen = $bodega['Nombre'];
     $oItem->Identificacion_Funcionario = $cabecera['Identificacion_Funcionario'];
     $oItem->Observaciones = $cabecera['observaciones'];
     $oItem->Tipo_Origen = 'Bodega';
     $oItem->Tipo_Destino = 'Cliente';
     $oItem->Id_Origen = $bodega['Id_Bodega_Nuevo'];
     $oItem->Id_Destino = $cabecera['cliente']['Id_Cliente'];
     $oItem->Estado = 'Pendiente';
     $oItem->Estado_Alistamiento = 0;
     $oItem->Id_Lista = 1;
     $oItem->Costo_Remision = $grupo['Totales']['Costo'];
     $oItem->Subtotal_Remision = $grupo['Totales']['Subtotal'];
     $oItem->Descuento_Remision = 0;
     $oItem->Impuesto_Remision = $grupo['Totales']['Impuesto'];
     $oItem->Entrega_Pendientes = 'No';
     $oItem->Id_Grupo_Estiba  = $grupo['Id_Grupo_Estiba'];
     $oItem->Id_Orden_Pedido  = $cabecera['Id_Orden_Pedido'];
     $codigo = $configuracion->getConsecutivo('Remision','Remision');
     $oItem->Codigo = $codigo;
     $oItem->save();
     $id_remision = $oItem->getId();
     unset($oItem);

   /*  $qr = generarqr('remision',$id_remision,'/IMAGENES/QR/');
     $oItem = new complex("Remision","Id_Remision",$id_remision);
     $oItem->Codigo_Qr=$qr;*/
    /*  $oItem->save();
     unset($oItem); */
     $codigos_rem.= $codigo.',';
     GuardarActividadRemision($id_remision,$codigo);
     
     unset($configuracion);
     return $id_remision;

}

function SaveProductoRemision($id_remision,$productos){      

     foreach ($productos as $producto) {
         
          $p=new complex('Producto_Remision',"Id_Producto_Remision");
          $p->Id_Inventario_Nuevo = $producto['Id_Inventario_Nuevo'];
          $p->Id_Producto = $producto['Id_Producto'];
          $p->Lote = $producto['Lote'];
          $p->Cantidad = $producto['Cantidad'];
          $p->Fecha_Vencimiento = $producto['Fecha_Vencimiento'];
          
          $subtotal=($producto['Cantidad']*$producto['Precio']);

          $p->Subtotal=number_format($subtotal,2,".","");               
          $p->Total_Descuento= number_format($subtotal,2,".","");      

          $subtotal=($producto['Cantidad']*$producto['Precio'])*($producto['Impuesto']/100);
          $p->Total_Impuesto =number_format($subtotal,2,".","");                  

          $p->Impuesto  = $producto['Impuesto'];
          $p->Descuento  = 0 ;
          $p->Cantidad_Total  = $producto['Cantidad'];
          
          $p->Id_Remision=$id_remision;
          //unset($p['Cantidad']);
          /*   $oItem->Cantidad=$p['Cantidad_Seleccionada']; */
          $p->Precio=number_format($producto['Precio'],2,".","");
          $p->Costo=number_format((int)$producto['Costo'],2,".","");
          
          $p->save();
          unset($p);
         // GuardarPendientes($producto,$id_remision);
     }

 
}


function GetLongitudRemision(){

     $query = "SELECT Max_Item_Remision FROM Configuracion WHERE Id_Configuracion=1";
     $oCon = new consulta();
     $oCon->setQuery($query);
     $rem = $oCon->getData();

     return $rem['Max_Item_Remision'];

}



function  GuardarActividadRemision($id_remision, $codigo){

     global $cabecera;

     $oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
     $oItem->Id_Remision=$id_remision;
     $oItem->Identificacion_Funcionario=$cabecera["Identificacion_Funcionario"];
     $oItem->Detalles="Se creo la remision con codigo ".$codigo;
     $oItem->Fecha=date("Y-m-d H:i:s");
     $oItem->save();
     unset($oItem);

}

function GetCodigoRem($id_remision){

     global $codigos_rem;
     $query = "SELECT Codigo FROM Remision WHERE Id_Remision=$id_remision";
     $oCon = new consulta();
     $oCon->setQuery($query);
     $rem = $oCon->getData();

     $codigos_rem.= $rem['Codigo'].',';
     return $rem['Codigo'];

}


?>