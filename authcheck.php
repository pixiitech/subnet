<?php
require "config.php";

/* User is authenticated by index.php, boot unauthorized users */
if ( !isset($_SESSION['Level']) )
{
    die ("<p>This page requires a login.</p> <i><a href='index.php'>Return to Login Page</a></i><br />");
}
if ($_SESSION['Level'] == 0)
{
    mysql_close($con);
    die ("<p>Your account has been deactivated. Contact your system administrator.</p> <i><a href='index.php'>Return to Login Page</a></i><br />");
}

if ($_SESSION['Level'] == -1)
{
    mysql_close($con);
    die ("<p>Your account has been logged out.</p> <i><a href='index.php'>Return to Login Page</a></i><br />");
}

if ($_SESSION['Expiration'] < time())
{
    mysql_close($con);
    die ("<p>You session has expired.</p> <i><a href='index.php'>Return to Login Page</a></i><br />");
}

if (!isset($_GET['quiet']))
    echo "Logged in: " . $_SESSION['Username'] . " <a href='index.php?logout=yes'><i>Logout</i></a>";

?>
