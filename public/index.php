<?php
//======================================================================
// Sistema de templates para el Radar
//======================================================================



//-----------------------------------------------------
// Importar librerias
//-----------------------------------------------------

// composer require twig/twig:~1.0
// composer require twig/extensions
require_once '../vendor/autoload.php';

/*
* Esto va si se isntala de a mano
* require_once '../vendor/Twig-1.24.0/lib/Twig/Autoloader.php';
* Twig_Autoloader::register();
*/



//-----------------------------------------------------
// Parametros globales desde la URL
//-----------------------------------------------------

if(isset($_GET["categoria"])){
	$categoria = $_GET["categoria"];
}



//-----------------------------------------------------
// Twig
//-----------------------------------------------------

$loader = new Twig_Loader_Filesystem('../templates');
$twig = new Twig_Environment($loader);
$twig->addExtension(new Twig_Extensions_Extension_Intl());

// Custom Filter 'slug' 
$filter = new Twig_SimpleFilter('slug', function ($string) {
	$string = transliterator_transliterate("Any-Latin; NFD; [:Nonspacing Mark:] 
		Remove; NFC; [:Punctuation:] Remove; Lower();", $string);
    $string = preg_replace('/[-\s]+/', '-', $string);
    $string = trim($string, '-');
    return $string;
});
$twig->addFilter($filter);

// Templates 
$index = $twig->loadTemplate('index.html.twig');



//-----------------------------------------------------
// Cargar Feeds .json
//-----------------------------------------------------

$feedsJson = file_get_contents("../hoy.json");
//global $feeds;
$feeds = json_decode($feedsJson,true); // 'true' devuelve  array


//-----------------------------------------------------
// Filtros para las entradas
//-----------------------------------------------------

//?categoria[]=arte&categoria[]=web&categoria[]=videogames

if(isset($categoria)){
	if(!is_array($categoria)){
		$categoria = array($categoria);
	}

	if (array_intersect($categoria, $feeds["categories_main"])) {
		$twig->addGlobal('cats', $categoria);
	}

}else{
	$twig->addGlobal('cats', array("all"));
}


//-----------------------------------------------------
// Guardar entrada en el archivo
//-----------------------------------------------------
function buscar_id( $arrayref, $buscado ){
    $archivo_file = "destacados/archivo.json";
    $archivo = json_decode( file_get_contents( $archivo_file ), true );

    $s = explode("_", $buscado);
    $feed_id = $s[0];
    $entry_id = $s[1];
    
    $feed = $arrayref[feeds][$feed_id];
    $feed_uid = md5( $feed[url] );

    $destacado = $feed[entries][$entry_id];
    $destacado_uid = md5( $destacado[link] );
    if(isset($destacado)){
        if( isset( $archivo[feeds][$feed_uid] ) ){
            $archivo[feeds][$feed_uid][entries][$destacado_uid] = $destacado;
        }else{
            // crear nuevo feed_tmp con 
            $feed_tmp = $feed; 
            $feed_tmp[entries] = array();

            //empujar el $destacado a $feed[entries] 
            $feed_tmp[entries][$destacado_uid] = $destacado; 

            //empujar $feed entero $archivo[entries] 
            $archivo[feeds][$feed_uid] = $feed_tmp; 
        }
        
        $salida_archivo_grl = file_put_contents(
            $archivo_file,
            json_encode( $archivo, JSON_PRETTY_PRINT ).PHP_EOL, 
            LOCK_EX 
        );
    }
}

if (isset($_GET["id"])){
    if ( isset($_GET["pass"]) and ($_GET["pass"] === 'pipopipo') ){
        global $feeds;
        $entrada_destacada = buscar_id( $feeds, $_GET["id"] ); 
    }
}

//-----------------------------------------------------
// Render
//-----------------------------------------------------

echo $index->render($feeds);




?>
