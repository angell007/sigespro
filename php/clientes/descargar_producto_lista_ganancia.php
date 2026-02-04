<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="Reporte_Lista_Ganancia.xls"');
    header('Cache-Control: max-age=0'); 

    include_once('../../class/class.consulta.php');
    #include_once('../../class/class.complex.php');
    require_once('./helper_lista_precio/funciones_producto_lista.php');
    $id = isset($_REQUEST['Id_Lista_Ganancia']) ? $_REQUEST['Id_Lista_Ganancia'] : false;

    if ($id) {
        # code...
        $query = ' SELECT PL.* ,
                        P.Nombre_Comercial,
                        IFNULL(CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida)
                        ,CONCAT(P.Nombre_Comercial," ",P.Laboratorio_Comercial)) as Nombre_Producto
                FROM  Producto_Lista_Ganancia PL
                INNER JOIN Producto P ON P.Codigo_Cum = PL.Cum
                WHERE  Id_Lista_Ganancia = '.$id.'
                ORDER BY Ultima_Actualizacion DESC
                ';
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $result = $oCon->getData();
    

        
       $data=' <table class="table w-100">
        <thead class="thead-light">
            <tr style="background:gray;padding:20px;" >
                <th style="width: 5%;">#</th>
                <th style="width: 10%;">Cod. Cum</th>
                <th style="width: 10%;">Nombre Comercial</th>
                <th style="width: 30%;">Producto</th>
                <th style="width: 10%;">Precio</th>
                <th style="width: 10%;">Precio Anterior</th>
                <th style="width: 20%;">Ultima Actualizacion</th>
 
            </tr>
        </thead>
        <tbody>';

        foreach ($result as $key => $producto) {
            # code...
            $data.='<tr '.($producto['Estado']=='Anulado' ? 'style="background:red;"' : ''  ).'>   
            <th>'.($key+=1).'</th>
            <td>'.$producto['Cum'].'</td>
            <td>'.$producto['Nombre_Comercial'].'</td>
            <td>'.$producto['Nombre_Producto'].'</td>
            <td>'.$producto['Precio'].'</td>
            <td>'.$producto['Precio_Anterior'].'</td>
            <td>'.$producto['Ultima_Actualizacion'].'</td>

            </tr>   
            ';

        }

        $data.='    
        </tbody>
    </table>';


        echo ($data);

    }else{
        echo 'Se necesita el Id de la lista';
    }