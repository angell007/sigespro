<?php
	require_once("config.inc_cron.php");
	setlocale(LC_ALL, 'spanish');

	global $DEVELOP_ENVIRONMENT;
	
	if ($DEVELOP_ENVIRONMENT==true) {
		error_reporting(E_ALL);
	} else {
		error_reporting(0);
	}
	
function error_manager($errno, $errstr, $errfile, $errline){
	//echo $errno . "-" . $errstr. "-" .  $errfile. "-" .  $errline;
	$idiomas = array("es", "en", "de", "fr", "it", "pt");

	$idioma  = (isset($_REQUEST['idioma'] ) ? strtolower($_REQUEST['idioma']) : "en" );
	if (!in_array($idioma, $idiomas)) {
		$idioma  = "en";
	}
    if (!(error_reporting() & $errno)) {
        // Este código de error no está incluido en error_reporting
        return;
    }

    switch ($errno) {
    case E_USER_ERROR:
        header("Location: 404.php?idioma=$idioma"); 
        break;

    case E_USER_WARNING:
        header("Location: 404.php?idioma=$idioma"); 
        break;

    case E_USER_NOTICE:
        header("Location: 404.php?idioma=$idioma"); 
        break;

    default:
        header("Location: 404.php?idioma=$idioma"); 
        break;
    }

    return true;
}


//$error_customed = set_error_handler("error_manager");
?>