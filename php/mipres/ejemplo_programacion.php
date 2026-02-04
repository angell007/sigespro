<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    //header('Content-Type: application/json');

	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');
    include_once('../../class/class.mipres.php');
    include_once('../../class/class.php_mailer.php');

    $queryObj = new QueryBaseDatos();

    $mipres= new Mipres();

    $data['ID']=11403125;
    $data['EstadoEntrega']=1;
    $data['CausaNoEntrega']=0;
    $data['ValorEntregado']=110000 ;
    $respuesta=$mipres->ReportarEntregaEfectiva($data);

     var_dump($respuesta);
     exit;
     

    $data['ID']=11403125;
    $data['CodSerTecEntregado']='04';
    $data['CantTotEntregada']='120';
    $data['EntTotal']=0;
    $data['CausaNoEntrega']=0;
    $data['FecEntrega']='2019-11-01';
    $data['NoLote']='PT12AS';
    $data['TipoIDRecibe']='CC';
    $data['NoIDRecibe']='10268813';

  /*   $isRes=11401882;
    $idEntrega=4818691; */

    $ejemplo=$mipres->ReportarEntrega($data);
    var_dump($ejemplo);

    exit;

   $data['ID']=11403125;
    $data['FecMaxEnt']='2019-11-01';
    $data['TipoIDSedeProv']='NI';
    $data['NoIDSedeProv']='804016084';
    $data['CodSedeProv']='Prov001912';
    $data['CodSerTecAEntregar']='19937957-7';
    $data['CantTotAEntregar']='1';

    var_dump($data);
    $ejemplo=$mipres->Programacion($data);
    var_dump($ejemplo);
exit;
    $query = '
			SELECT
				NIT				
			FROM Configuracion
			WHERE
				Id_Configuracion=1';

		$queryObj->SetQuery($query);
		$dato = $queryObj->ExecuteQuery('simple');

		$n=explode('-',$dato['NIT']);
		$nit=$n[0];
        $nit=str_replace('.','',$nit);
        
        var_dump($nit);
        exit;

    $id=8558904;
    $idProgramacion=11401882;

    $data['ID']=11401882;
    $data['CodSerTecEntregado']='04';
    $data['CantTotEntregada']='120';
    $data['EntTotal']=0;
    $data['CausaNoEntrega']=0;
    $data['FecEntrega']='2019-11-01';
    $data['NoLote']='PT12AS';
    $data['TipoIDRecibe']='CC';
    $data['NoIDRecibe']='10268813';

    $isRes=11401882;
    $idEntrega=4818691;

    $ejemplo=$mipres->ReportarEntrega($data);
    var_dump($ejemplo);


?>