<?php
require __DIR__ . '/../vendor/autoload.php';
$insta = new \InstaCA\InstaCA;

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Testing inicio de sesi&oacute;n</title>
</head>
<body>
	<p><?php echo $insta->userAgent; ?></p>
</body>
</html>
