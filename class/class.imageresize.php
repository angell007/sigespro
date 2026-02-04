<?php
/**
* Clase que crea una copia de una imagen, de un tamaño distinto, a través de distintos métodos
* Ejemplo de uso:
* <code>
* $o=new ImageResize($imagen_origen);
* $o->resizeWidth(100);
* $o->save($imagen_destino);
* </code>
* TODO: 
* - Definir de manera automática el formato de salida.
* - Definir otros tipos de formato de entrada, aparte de gif, jpg y png
*/
class ImageResize {
    private $file_s = "";
    private $gd_s;
    private $gd_d;
    private $width_s;
    private $height_s;
    private $width_d;
    private $height_d;
    private $aCreateFunctions = 'imagecreatefromjpeg';
	private $type = 'jpg';

 /**
    * @param    string  Nombre del archivo
    */
    public function __construct($source) 
    {
        $this->file_s = $source;
        list($this->width_s, $this->height_s, $type, $attr) = getimagesize($source, $info2);
		
		
		for($i=strlen($source)-1;$i>0;$i--){
			if (substr($source,$i,1)=="."){
				$tipo=substr($source,$i+1);
				break;
			}
		}
		$tipo = strtolower($tipo);
		$this->type=$tipo;
		
		
		switch($this->type){
			case "jpg":
			$this->aCreateFunctions='imagecreatefromjpeg';
			break;
			case "png":
			$this->aCreateFunctions='imagecreatefrompng';
			break;
			case "gif":
			$this->aCreateFunctions='imagecreatefromgif';
			break;
		}
		
        $createFunc = $this->aCreateFunctions;
        if($createFunc) {
            $this->gd_s = $createFunc($source);
        }
    }
    /**
    * Redimensiona la imagen de forma proporcional, a partir del ancho
    * @param    int     ancho en pixel
    */
    public function resizeWidth($width_d) 
    {
        $height_d = floor(($width_d*$this->height_s) /$this->width_s);
        $this->resizeWidthHeight($width_d, $height_d);
    }
    /**
    * Redimensiona la imagen de forma proporcional, a partir del alto
    * @param    int     alto en pixel
    */
     public function resizeHeight($height_d) 
    {
        $width_d = floor(($height_d*$this->width_s) /$this->height_s);
        $this->resizeWidthHeight($width_d, $height_d);
    }
    /**
    * Redimensiona la imagen de forma proporcional, a partir del porcentaje del área
    * @param    int     porcentaje de área
    */
     public function resizeArea($perc) 
    {
        $factor = sqrt($perc/100);
        $this->resizeWidthHeight($this->width_s*$factor, $this->height_s*$factor);
    }
    /**
    * Redimensiona la imagen, a partir de un ancho y alto determinado
    * @param    int     porcentaje de área
    */
     public function resizeWidthHeight($width_d, $height_d) 
    {
        $this->gd_d = imagecreatetruecolor($width_d, $height_d);
        // desactivo el procesamiento automatico de alpha
        imagealphablending($this->gd_d, false);
        // hago que el alpha original se grabe en el archivo destino
        imagesavealpha($this->gd_d, true);
        imagecopyresampled($this->gd_d, $this->gd_s, 0, 0, 0, 0, $width_d, $height_d, $this->width_s, $this->height_s);
    }
	
	public function cropHeight($height_d)
	{
		$pos = ($this->height_s - $height_d)/2;
		
		 $this->gd_d = imagecreatetruecolor($this->width_s, $height_d);
        // desactivo el procesamiento automatico de alpha
        imagealphablending($this->gd_d, false);
        // hago que el alpha original se grabe en el archivo destino
        imagesavealpha($this->gd_d, true);
        imagecopyresampled($this->gd_d, $this->gd_s, 0, 0, 0, $pos, $this->width_s, $height_d, $this->width_s, $this->height_s);
	}
	
	public function cropWidth($width_d)
	{
		$pos = ($this->width_s - $width_d)/2;
		
		 $this->gd_d = imagecreatetruecolor($width_d, $this->height_s);
        // desactivo el procesamiento automatico de alpha
        imagealphablending($this->gd_d, false);
        // hago que el alpha original se grabe en el archivo destino
        imagesavealpha($this->gd_d, true);
        imagecopyresampled($this->gd_d, $this->gd_s, 0, 0, $pos, 0, $width_d, $this->height_s, $this->width_s, $this->height_s);
	}
	
    /**
    * Graba la imagen a un archivo de destino
    * @param    string  Nombre del archivo de salida
    */
     public function save($file_d) 
    {
		switch($this->type){
			case "jpg":
			imagejpeg($this->gd_d, $file_d);
			break;
			case "png":
			imagepng($this->gd_d, $file_d);
			break;
			case "gif":
			imagegif($this->gd_d, $file_d);
			break;
		}
        imagedestroy($this->gd_d);
    }
}
?>
