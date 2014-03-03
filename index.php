<?php

// flag das sicherstellt, dass keine includes direkt aufgerufen werden
define('EXECUTE', true);

// errors als exceptions behandeln
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
	if($errno != E_NOTICE) { // E_NOTICE is ok
		throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
	}
}
set_error_handler("exception_error_handler");

// umschließendes try/catch
try {

// config laden
require("config.inc.php");

// debug mode initialisieren
if(DEBUG_MODE) {
	error_reporting(E_ALL);
}

// session starten für fontorder, muss vor der ersten ausgabe passieren
session_start();

// 404 function
function response404() {
	header('HTTP/1.0 404 Not Found');
    echo "<h1>404 Not Found</h1>";
    echo "The page that you have requested could not be found.";
    exit();	
}

// URL splitten wenn "?" enthalten
if(strpos($_SERVER['REQUEST_URI'], "?")) {
	list($path, $parameter) = explode('?', $_SERVER['REQUEST_URI']);
} else {
	$path = $_SERVER['REQUEST_URI'];
}

// URL prüfen, bei nicht alpha-numerischen Zeichen 404 senden
if(preg_match('/[^a-zA-Z0-9\/\-]/', $path)) {
	response404();
}

// URL trimmen und Pfad zu dieser Datei (index.php) entfernen
$path = explode('/', rtrim($path,"/") );
$scriptName = explode('/', $_SERVER['SCRIPT_NAME']);
for($i= 0;$i < sizeof($scriptName);$i++) {
	if(isset($path[$i]) && $path[$i] == $scriptName[$i]) {
		unset($path[$i]);
	}
}
$path = array_values($path);

// Homepage setzen wenn kein Pfad übergeben wurde
if( empty($path[0]) ) {
	$path[0] = "home";
}

// Systempfad zu der zu inkludierenden Datei setzen
$page = ABSPATH . "pages/" . implode("/", $path) . ".inc.php";

// Prüfen, ob Datei vorhanden ist, sonst 404 ausgeben
if( !file_exists($page) ) {
	response404();
}

// Prüfen, ob entlang des Pfades eine abfangende index.inc.php liegt sonst $page verwenden
$path_partial = "";
for($i = 0; $i<count($path)-1; $i++) {
	$path_partial .= $path[$i] . "/";
	if( file_exists(ABSPATH . "pages/" . $path_partial . "index.inc.php") ) {
		$include = ABSPATH . "pages/" . $path_partial . "index.inc.php";
	}
}
if(empty($include)) {
	$include = $page;
}

?><!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Site Frame</title>
	<link rel="icon" href="favicon.ico"/>
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
	<link href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css" rel="stylesheet">
	<link href="<?php echo BASE_URL; ?>css/central.css" rel="stylesheet">
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
	<script type="text/javascript">
		base_url = "<?php echo BASE_URL; ?>";
	</script>
</head>
<body>

	<div class="container">
	
		<div class="header">
			<h1><a href="<?php echo BASE_URL; ?>"><img src="<?php echo BASE_URL; ?>img/logo.png" alt="Site Frame"></a></h1>
		</div>
		
		<?php include $include; ?>
	
	</div> <!-- container -->
	
	<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="<?php echo BASE_URL; ?>bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="<?php echo BASE_URL; ?>js/central.js"></script>

</body>
</html>

<?php

} catch (Exception $e) {
	
	if(DEBUG_MODE) {
		
		error_log($e);
		echo nl2br($e);
	
	} else {
			
		echo "<p>Sorry, there has been an error ...</p>";
		
	}
	
	if(MAIL_ERRORS) {
		
		error_log($e);
		
		// fileformat: "[timestamp] [n errors since last email]"
		$flag_file_path = ABSPATH . "mail_error.flag";
		
		if(!file_exists($flag_file_path)) {
			file_put_contents($flag_file_path, "0 0");
		} else {
			list($time, $num) = explode(" ", file_get_contents($flag_file_path));
			
			// if last error mail was send less then one hour ago just increase the number flag
			// ... otherwise reset the time and send the error mail
			if(time() - $time < 3600) {
				file_put_contents($flag_file_path, $time . " " . ($num + 1));
			} else {
				file_put_contents($flag_file_path, time() . " 0");
				
				$emailtext = $num . " errors occured since the last error mail\r\n\r\n";
				$emailtext .= "ERROR: " . $e . "\r\n\r\n";
				$emailtext .= "POST-DATA:\r\n";
				foreach ($_POST as $key => $value){
					$emailtext .= $key . " = " . $value ."\r\n";
				}
				$emailtext .= "\r\n" .
					'REMOTE_ADDR: ' . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "") . "\r\n" .
					'REMOTE_HOST: ' . (isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : "") . "\r\n" .
					'HTTP_USER_AGENT: ' . (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "") . "\r\n" .
					'HTTP_REFERER: ' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "") . "\r\n" .
					'REQUEST_METHOD: ' . (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : "") . "\r\n" .
					'REQUEST_URI: ' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "") . "\r\n" .
					'PATH_INFO: ' . (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : "") . "\r\n" .
					'QUERY_STRING: ' . (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : "");
			
				mail(
					"j@sights.de",
					"Fehler",
					$emailtext,
					"From: noreply@siteframe.de"
				);		
			}
		}	
	}
}