<?php

header('HTTP/1.1 503 Service Unavailable');
header('Retry-After: 900'); // 15 minutes in seconds

?>
<!DOCTYPE html>
<html>
<head>
	<meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' name='viewport' />
 	<meta charset="utf-8">
 	<meta name="robots" content="noindex">
	<title>Dočasně mimo provoz | Poznávačka přírody</title>
	<style>
		@import url(http://fonts.googleapis.com/css?family=Open+Sans:400,700&subset=latin,latin-ext);
		/** Color scheme: http://colorschemedesigner.com/csd-3.5/#1o42PjPu-w0w0 */
		body {
			font-family: 'Open Sans', sans-serif;
			font-size: 15px;
			background: #F7DB5E;
			padding: 0px;
			margin: 100px auto;
			width: 600px;
		}
		h1 { font-weight: bold; font-size: 47px; margin: .6em 0 }
		p { font-size: 21px; margin: 1.5em 0 }
	</style>

	</head>
<body>
	<h1>Dočasně mimo provoz</h1>

<p>Na stránce právě probíhá údržba nebo pro vás nasazujeme novou verzi.</p>
<p>Zkusím to, prosím, později :-)</p>
</body>
</html>
<?php

exit;
