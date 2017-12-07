<?php
//======================================================================
// Sistema de templates para el Radar
//======================================================================



//-----------------------------------------------------
// Importar librerias
//-----------------------------------------------------

// composer require twig/twig:~1.0
// composer require twig/extensions
require_once '../../vendor/autoload.php';



//-----------------------------------------------------
// Parametros globales desde la URL
//-----------------------------------------------------

if(isset($_GET["categoria"])){
	$categoria = $_GET["categoria"];
}



//-----------------------------------------------------
// Twig
//-----------------------------------------------------

$loader = new Twig_Loader_Filesystem('../../templates');
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

$feedsJson = file_get_contents("archivo.json");
//global $feeds;
$feeds = json_decode( $feedsJson, true ); // 'true' devuelve  array
print_r($feeds);


//-----------------------------------------------------
// Filtros para las entradas
//-----------------------------------------------------

#?categoria[]=arte&categoria[]=web&categoria[]=videogames

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
// Render
//-----------------------------------------------------

echo $index->render($feeds);




?>
