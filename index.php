<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<?php 
session_start();
require 'config.php';
$loginFail = false;
$goHome = false;
if ( isset( $_POST["Username"] ) )
{
    	$con = mysql_connect($sql_server, $sql_user, $sql_pass);
	if (!$con)
    		die ("Could not connect to SQL Server: " . mysql_error() . "<br />");
	$db_selected = mysql_select_db($sql_db, $con);
	if (!$db_selected)
    		die ("Could not find database. <br />");
	$lUsername = strtolower($_POST["Username"]);

	$encryptedpass = mysql_real_escape_string(crypt($_POST['Password'], $encryption_salt));

	$query = "SELECT * FROM Login WHERE LOWER(Username) = '{$lUsername}' AND Password = '{$encryptedpass}'";

	$result = mysql_query($query, $con);
	$row = mysql_fetch_array($result);
	if ( $row != FALSE )
	{
	    $_SESSION['Level'] = $row['Level'];
	    $_SESSION['Username'] = $row['Username'];
	    $_SESSION['ColorScheme'] = $row['ColorScheme'];
	    $_SESSION['FieldWidth'] = $row['FieldWidth'];
	    $_SESSION['Expiration'] = time() + $session_expiration;
	    $_SESSION['MemberOf'] = $row['MemberOf'];
	    $goHome = true;
	}
	else
	    $loginFail = true;
	mysql_close($con);
}
if ( isset( $_GET['logout']) && ( $_GET['logout'] == 'yes' ) )
{
	session_destroy();
}
?>

<html>
<head>
<?php 
$homepage = "subnet.php";

if ( $goHome )
{
    echo "Navigating to home screen...<br /><br />";
    echo "<script>window.location.href='{$homepage}';</script>";
}
else if ( isset( $_GET['logout']) && ( $_GET['logout'] == 'yes' ) )
{
    echo "You have successfully been logged out.<br /><br />";
}
?>
<link rel="stylesheet" type="text/css" src="mystyle.css" />

<title>Login Page</title>

</head>

<body>

<br /><br />

<h2 style="text-align:center">Login Page</h2><br />

<form method="post" action="index.php">
<table class="main"><tbody><tr><td>
Username:<input name="Username" type="text" required="required" />  
</td></tr><tr><td>
Password:<input name="Password" type="password" /><br />
</td></tr><tr><td>
<?php if ($loginFail) echo "<span style='color:#FF0000'><i>Invalid username or password.</i></span><br />"; ?>
<input type="submit" value="Login" />

<input type="reset" value="Cancel" />
</td></tr></tbody></table>

</form>
</body>
</html>

