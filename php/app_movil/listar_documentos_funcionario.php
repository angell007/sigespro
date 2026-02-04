<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    include_once('../../class/class.querybasedatos.php');
    include_once('../../class/class.paginacion.php');
    include_once('../../class/class.http_response.php');
    include_once('../../class/class.utility.php');
    
    $util = new Utility();
    $queryObj = new QueryBaseDatos();

	$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

    $files = GetFuncionarioFiles($funcionario);

    echo json_encode($files);

    function GetFuncionarioFiles($id_funcionario){
        global $MY_FILE;

        $archivos = array();

        $path    = __DIR__ . "/../../DOCUMENTOS/" . $id_funcionario;
        
        $files = scandir($path);
        $files = array_diff(scandir($path), array('.', '..'));
        $files = array_values($files);


        foreach ($files as $key => $filename) {
            $file = array('ruta' => 'https://192.168.40.201/DOCUMENTOS/'.$id_funcionario.'/'.$filename, 'nombre' => explode('.', $filename)[0]);
            // $files[$key] = $path.'/'.$filename;
            // $files[$key]['Nombre'] = $filename;
            array_push($archivos, $file);
        }

        return $archivos;
    }
?>