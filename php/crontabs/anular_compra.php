<?php
    include_once '../../class/class.consulta.php';
    include_once '../../class/class.complex.php';


    $oItem = new complex('Configuracion','Id_Configuracion',1);
    $config = $oItem->getData();
    $dias = $config['Dias_Anulacion_Orden_Compra'];
    $responsable = $config['Responsable_Anulacion_Orden_Compra'];
    


    $query = ' SELECT OC.Id_Orden_Compra_Nacional AS Id
                FROM Orden_Compra_Nacional OC 
                WHERE NOT EXISTS  ( SELECT Id_Acta_Recepcion 
                    FROM Acta_Recepcion AC
                    where OC.Id_Orden_Compra_Nacional = AC.Id_Orden_Compra_Nacional
                 )
                 And DATE(Fecha) <= DATE_SUB(NOW(),INTERVAL '. $dias.' DAY)
                 AND Estado = "Pendiente" AND Aprobación != "Rechazada"
                 order by   OC.Id_Orden_Compra_Nacional desc
           
                ';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $actas= $oCon->getData();
    unset($oCon);
   
    $cont=' con el siguiente motivo: Ha excedido el limite de  dias ('.$dias.') sin acta de recepcion';

    foreach ($actas as $key => $acta) {
        # code...
     
        $oItem = new complex('Orden_Compra_Nacional','Id_Orden_Compra_Nacional',$acta['Id']);
        $oItem->Estado = 'Anulada';
        $oItem->save();
        unset($oItem);

        $oItem = new complex('Actividad_Orden_Compra',"Id_Acta_Recepcion_Compra");
        $oItem->Id_Orden_Compra_Nacional=$acta['Id'];
        $oItem->Identificacion_Funcionario=$responsable;
        $oItem->Detalles="La Orden de Compra ha sido Anulada".$cont;
        $oItem->Fecha=date("Y-m-d H:i:s");
        $oItem->Estado ='Anulada';
        $oItem->save();
        unset($oItem);
        
    }
