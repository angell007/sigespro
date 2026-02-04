<?php
include_once(__DIR__ . "/class.dao.php");
include_once(__DIR__ . "/../config/config.db.php");


class consulta2
{
    protected    $query      = 0,
        $tipo       = 'simple',
        $bandera    = "Consulta",
        $key = '',
        $resultado  = [];


    public function connect()
    {
        #include_once("../../config/config.inc.php");
        global $db_host, $db_user, $db_password, $db_name;
        //require($MY_CONFIG."config.db.php");

        $link =  mysqli_connect($db_host, $db_user, $db_password, $db_name) or die('No se pudo conectar: ' . mysqli_error($link));
        mysqli_select_db($link, $db_name) or die('No se pudo seleccionar la base de datos');

        $result = mysqli_query($link, $this->query) or die('Consulta fallida: ' . mysqli_error($link) . $this->query);
        if ($this->bandera == 'Consulta') {
            if ($this->tipo == 'simple') {
                $vari = mysqli_fetch_assoc($result);
                if (is_array($vari)) {
                    $this->resultado = array_map('utf8_encode', $vari);
                } else {
                    $this->resultado = $vari;
                }
            } else {
                while ($lista = mysqli_fetch_assoc($result)) {
                    $lista = array_map('utf8_encode', $lista);
                    $this->resultado[] = $lista;
                }
            }
        } else {
            $this->resultado = '';
        }

        @mysqli_free_result($result);
        mysqli_close($link);
    }

    public function connect2()
    {
        global $MY_CONFIG;
        global $db_host, $db_user, $db_password, $db_name;
        //echo "conect2:".$this->query;

        $link =  mysqli_connect($db_host, $db_user, $db_password, $db_name) or die('No se pudo conectar: ' . mysqli_error($link));
        mysqli_select_db($link, $db_name) or die('No se pudo seleccionar la base de datos');

        $result = mysqli_query($link, $this->query) or die('Consulta fallida: ' . mysqli_error($link));

        $this->key = mysqli_insert_id($link);
        mysqli_close($link);
    }

    public function connect3($db)
    {
        global $db_host, $db_user, $db_password;
        $link =  mysqli_connect($db_host, $db_user, $db_password, $db) or die('No se pudo conectar: ' . mysqli_error($link));
        mysqli_select_db($link, $db) or die('No se pudo seleccionar la base de datos');

        //echo "conect3:".$this->query;

        $result = mysqli_query($link, $this->query) or die('Consulta fallida: ' . mysqli_error($link));
        $this->key = mysqli_insert_id($link);
        if ($this->bandera == 'Consulta') {
            if ($this->tipo == 'simple') {
                $vari = mysqli_fetch_assoc($result);
                if (is_array($vari)) {
                    $this->resultado = array_map('utf8_encode', $vari);
                } else {
                    $this->resultado = $vari;
                }
            } else {
                while ($lista = mysqli_fetch_assoc($result)) {
                    $lista = array_map('utf8_encode', $lista);
                    //   $this->resultado[]=$lista;

                    $this->resultado['data'][] = $lista;
                }
                $this->resultado['total'] = $rowTotal['total'];
            }
        } else {
            $this->resultado = '';
        }


        @mysqli_free_result($result);
        mysqli_close($link);
    }


    public function setQuery($query)
    {
        $this->query = $query;
        //   var_dump($query);exit;
        //   echo '<br><br>';
    }

    public function setTipo($arg)
    {
        $this->tipo = $arg;
    }

    public function getData()
    {
        self::connect();
        return $this->resultado;
    }
    public function getID()
    {
        return $this->key;
    }

    public function getData2($db)
    {
        self::connect3($db);
        return $this->resultado;
    }

    public function deleteData()
    {
        self::connect2();
        return $this->resultado;
    }

    public function createData()
    {
        $bandera = "Insertar";
        self::connect2();
        return $this->resultado;
    }

    public function __construct()
    {
    }

    public function __destruct()
    {
        unset($query);
        unset($resultado);
    }
}
