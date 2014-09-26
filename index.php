<?php

// flag to ensure, that includes aren't called directly
define('EXECUTE', true);

// load config
require("config.inc.php");

// initialize debug mode
if(DEBUG_MODE) {
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
}

// 404 function
function response404() {
	header('HTTP/1.0 404 Not Found');
    echo "<h1>404 Not Found</h1>";
    echo "The page that you have requested could not be found.";
    exit();	
}

// split URL if "?" contained
if(strpos($_SERVER['REQUEST_URI'], "?")) {
	list($path, $parameter) = explode('?', $_SERVER['REQUEST_URI']);
} else {
	$path = $_SERVER['REQUEST_URI'];
}

// if URL contains unvailed characters send 404
if(preg_match('/[^a-zA-Z0-9\/\-\_]/', $path)) {
	response404();
}

// trim URL and remove path to this file (index.php)
$path = explode('/', rtrim($path,"/") );
$scriptName = explode('/', $_SERVER['SCRIPT_NAME']);
for($i= 0;$i < sizeof($scriptName);$i++) {
	if(isset($path[$i]) && $path[$i] == $scriptName[$i]) {
		unset($path[$i]);
	}
}
$path = array_values($path);

// set home if no path
if( empty($path[0]) ) {
	$path[0] = "home";
}

// set system path to the page include
$page = ABSPATH . "pages/" . implode("/", $path) . ".inc.php";

// if page doesn't exist, send 404
if( !file_exists($page) ) {
	response404();
}

// check if there is a index.inc.php along the path, otherwise use $page
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
	<script type="text/javascript" src="<?php echo BASE_URL; ?>js/central.js"></script>

</body>
</html>