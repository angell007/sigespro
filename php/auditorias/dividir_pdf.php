<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.dividir_pdf.php');

   

    $query = 'SELECT A.Archivo, A.Id_Auditoria, D.Codigo  FROM Auditoria A INNER JOIN Dispensacion D ON A.Id_Dispensacion =D.Id_Dispensacion  WHERE A.Id_Auditoria=49299 ';    

    $oCon= new consulta();
    $oCon->setQuery($query);
    $auditoria = $oCon->getData();
    unset($oCon);

    $pag=GetPaginas($auditoria['Id_Auditoria']);

   

    $ruta=$MY_FILE .'IMAGENES/AUDITORIAS/'.$auditoria['Id_Auditoria'];
    $archivo=$auditoria['Archivo'];
    $dis=$auditoria['Codigo'];
   
    if (!file_exists( $MY_FILE.'SOPORTES/'.$dis)){
        mkdir($MY_FILE.'SOPORTES/'.$dis, 0777, true);
    }
   

    foreach ($pag as $key => $value) {
           
        $p=explode('-',$value['Paginas']);
        $nombre=$value['Nombre'];
        $pdf=new Separar_Pdf();
        $pdf->dividir_pdf($archivo,$ruta,$dis,$nombre,$p);
      
    }

     


function GetPaginas($id){
        $query = 'SELECT Paginas,CONCAT(Tipo_Soporte,".pdf") as Nombre  FROM Soporte_Auditoria WHERE Id_Auditoria='.$id;    

        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $paginas = $oCon->getData();
        unset($oCon);

        

        return $paginas;
}
    

          
?>