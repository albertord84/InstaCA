<?php
require __DIR__ . '/../vendor/autoload.php';
$insta = new \InstaCA\InstaCA;
$username = in_array('username', array_keys($_REQUEST)) ?
	$_REQUEST['username'] : null;
$password = in_array('password', array_keys($_REQUEST)) ?
	$_REQUEST['password'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Testing inicio de sesi&oacute;n</title>
	<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
	<?php if ($username === null || trim($username) === '') { ?>
		<div class="login-form">
			<form method="post">
				<div class="form-group">
					<input type="text" class="username" name="username"
						placeholder="Username" autocomplete="off">
					<input type="password" class="password" name="password"
						placeholder="Password" autocomplete="off">
				</div>
				<div class="form-footer">
					<button type="submit" class="btn btn-primary">Submit</button>
				</div>
			</form>
		</div>
	<?php } ?>
	<div class="results">
		<?php
		$response = null;
		if (trim($username) !== '') {
			try {
				$response = $insta->login();
			}
			catch (\Exception $ex) {
				$response = $ex->getMessage();
			}
		}
		?>
		<textarea rows="20"><?php echo json_encode($response['cookies']); ?></textarea>
	</div>
</body>
</html>
