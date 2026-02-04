<?php
    //header('Content-Type: text/xml');
    include_once('../../class/class.consulta.php');
    $id_factura = isset( $_REQUEST['Id_Factura'] ) ? $_REQUEST['Id_Factura'] : false;
    $tipo_factura = isset( $_REQUEST['Tipo_Factura'] ) ? $_REQUEST['Tipo_Factura'] : false;
    
    $resolucion = [];
    $factura = [];
    $configuracion = [];
    getDatos($tipo_factura,$id_factura);

    if($resolucion){

    $xml = 'https://api-dian.sigesproph.com.co/api-dian/storage/app/xml/1/' . $resolucion["resolution_id"] . '/fv' . getNombre() . '.xml';

       header('Content-Disposition: attachment; filename=fv'.getNombre().'.xml'); //descargar
       readfile("$xml");
    }else{
        #echo 'No existe informaci贸n';
    }
    
    function getNombre(){
        global $resolucion, $factura;
        $nit=getNit();
  
        $codigo=(INT)str_replace($resolucion['Codigo'],"", $factura['Codigo']);
        $nombre=str_pad($nit, 10, "0", STR_PAD_LEFT)."000".date("y",strtotime($factura['Fecha_Documento'])).str_pad($codigo, 8, "0", STR_PAD_LEFT);
        return $nombre;
   }
   
   
    function getNit(){
        global $configuracion;
        $nit=explode("-",$configuracion['NIT']);
        $nit=str_replace(".","", $nit[0]);
        return $nit;
    }
      


    function getDatos($tipo_factura, $id_factura){
        global $factura, $resolucion,$configuracion; 
        $query = 'SELECT * FROM '.$tipo_factura.' WHERE Id_'.$tipo_factura.' = '.$id_factura;
        $oCon = new consulta();
        $oCon->setQuery($query);
        $factura = $oCon->getData();
        unset($oCon);
        
        $query = 'SELECT * FROM Resolucion WHERE Id_Resolucion = '.$factura['Id_Resolucion'];
        $oCon = new consulta();
        $oCon->setQuery($query);
        $resolucion = $oCon->getData();
        
        $query="SELECT C.*,(SELECT D.Nombre FROM Departamento D WHERE D.Id_Departamento=C.Id_Departamento) as Departamento,
        (SELECT M.Nombre FROM Municipio M WHERE M.Id_Municipio=C.Id_Municipio) as Ciudad FROM Configuracion C WHERE C.Id_Configuracion=1";
    
        $oCon=new consulta();
        $oCon->setQuery($query);
        $configuracion=$oCon->getData();            
        unset($oItem);
    }
    
?>