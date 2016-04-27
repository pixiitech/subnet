<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php require 'config.php'; 
session_start();?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="author" content="Gregory Hedrick" />
<script src='sorttable.js'></script>
<?php
$lUser = strtolower($_SESSION['Username']);
//Update color scheme and field width immediately
if ( isset( $_POST[$lUser . '-colorscheme'] ) && ( $_POST[$lUser . '-colorscheme'] <> $_SESSION['ColorScheme'] ))
    $_SESSION['ColorScheme'] = $_POST[$lUser . '-colorscheme'];
if ( isset( $_POST[$lUser . '-fieldwidth'] ) && ( $_POST[$lUser . '-fieldwidth'] <> $_SESSION['FieldWidth'] ))
    $_SESSION['FieldWidth'] = $_POST[$lUser . '-fieldwidth'];
?>
<?php include 'style.php'; ?>
<title>
Login Management
</title>
</head>
<body>

<?php

function setField($ID, $val)
{
    echo "<script>document.forms['recordinput'].elements['{$ID}'].value = '{$val}';</script>";
}

function hasSpaces($txt)
{
    if ( strpos($txt, ' ') != false )
	return true;
    if ( strpos($txt, '\n') != false )
	return true;
    if ( strpos($txt, '\r') != false )
	return true;
    return false;
}

require 'menu.php';
echo "<br />";
if ( $_SESSION['Level'] < $level_user )
{
    die ("You do not have authorization to view this page.<br />");
}

/* Connect to SQL Server */
$con = mysql_connect($sql_server, $sql_user, $sql_pass);
if (!$con)
	die ("Could not connect to SQL Server: " . mysql_error() . "<br />");
$db_selected = mysql_select_db($sql_db, $con);
if (!$db_selected)
	die ("Could not find database. <br />");

/* Create newly submitted user */

if ( isset( $_POST['username'] ) && ( $_POST['username'] != "New User" ) && ( $_POST['username'] != "" ))
{
    $_POST['username'] = strtolower($_POST['username']);
    $querystring = "SELECT Username FROM Login WHERE LOWER(Username) = '{$_POST['username']}'";
    if ($_SESSION['Level'] == $level_developer)
	echo $querystring . "<br />";
    if (mysql_fetch_array(mysql_query($querystring, $con)))
	echo "User {$_POST['username']} already exists, please choose a different username.<br />";
    else if ( hasSpaces($_POST['username']))
	echo "Error: Username contains spaces.";
    else
    {
        $cryptpass = mysql_real_escape_string(crypt($_POST['password'],$encryption_salt));
	$querystring = "INSERT INTO Login (Username, Password, Name, Level, MemberOf, ColorScheme, FieldWidth, IsGroup) VALUES ('{$_POST['username']}', ";
	$querystring .= "'{$cryptpass}', '{$_POST['name']}', '{$_POST['level']}',  '{$_POST['memberof']}', {$_POST['colorscheme']}, {$_POST['fieldwidth']}, 0)";
	if ( $_SESSION['Level'] == $level_developer )
	    echo $querystring . "<br />";
	$result = mysql_query($querystring, $con);
	if ($result)
	    echo "Add new user {$_POST['username']} succeeded.<br />";
        else
	    echo "Add new user {$_POST['username']} failed.<br />";
    }
}

/* Create newly submitted group */

if ( isset( $_POST['newgroupname'] ) && ( $_POST['newgroupname'] != "New Group" ) && ( $_POST['newgroupname'] != "" ))
{
    $_POST['newgroupname'] = strtolower($_POST['newgroupname']);
    $querystring = "SELECT Username FROM Login WHERE LOWER(Username) = '{$_POST['newgroupname']}'";
    if ($_SESSION['Level'] == $level_developer)
	echo $querystring . "<br />";
    if (mysql_fetch_array(mysql_query($querystring, $con)))
	echo "Name {$_POST['newgroupname']} already exists, please choose a different group name.<br />";
    else if ( hasSpaces($_POST['newgroupname']))
	echo "Error: Group name contains spaces.";
    else
    {
	$querystring = "INSERT INTO Login (Username, IsGroup) VALUES ('{$_POST['newgroupname']}', 1)";
	if ( $_SESSION['Level'] == $level_developer )
	    echo $querystring . "<br />";
	$result = mysql_query($querystring, $con);
	if ($result)
	    echo "Add new group {$_POST['username']} succeeded.<br />";
        else
	    echo "Add new group {$_POST['username']} failed.<br />";
    }
}

/* Build Group List */
$groups = array();
$querystring = "SELECT Username FROM Login WHERE IsGroup = 1";
$result = mysql_query($querystring, $con);
while ( $row = mysql_fetch_array($result) ) {
    array_push($groups, $row['Username']);
}

/* Update users that were changed */
$querystring = "SELECT * FROM Login";
$result = mysql_query($querystring, $con);
while ( $row = mysql_fetch_array($result) )
{
    $lUser = strtolower($row['Username']);
    if ( isset( $_POST[$lUser . '-clearpass'] ))
    {
	$nullpass = mysql_real_escape_string(crypt('',$encryption_salt));
	$querystring2 = "UPDATE Login SET Password='{$nullpass}' WHERE LOWER(Username) = '{$lUser}'";
	if ($_SESSION['Level'] == $level_developer)
	    echo $querystring2 . "<br />";
	$result2 = mysql_query($querystring2, $con);
	if ($result2)
	    echo "Cleared password of user " . $row['Username'] . ".<br />";
	else
	    echo "Failed clear password of user " . $row['Username'] . ".<br />";
    }
    if (( isset( $_POST[$lUser . '-password'] )) && ( $_POST[$lUser . '-password'] != '' ))
    {
	$encryptedpass = mysql_real_escape_string(crypt($_POST[$lUser . '-oldpass'], $encryption_salt));
	if ($encryptedpass != $row['Password']) {
	    echo "Old password entered does not match current password.<br />";
	}
	else if ($_POST[$lUser . '-password'] != $_POST[$lUser . '-confpass']) {
	    echo "New password and confirm password do not match.<br />";
	}
	else {
	    $pass = mysql_real_escape_string(crypt($_POST[$lUser . '-password'],$encryption_salt));
	    $querystring2 = "UPDATE Login SET Password='{$pass}' WHERE LOWER(Username) = '{$lUser}'";
	    if ($_SESSION['Level'] == $level_developer)
	        echo $querystring2 . "<br />";
	    $result2 = mysql_query($querystring2, $con);
	    if ($result2)
	        echo "Set password of user " . $row['Username'] . ".<br />";
	    else
	        echo "Failed set password of user " . $row['Username'] . ".<br />";
	}
    }
    if ( isset( $_POST[$lUser . '-level'] ) && ( $_POST[$lUser . '-level'] <> $row['Level']))
    {
	$querystring2 = "UPDATE Login SET Level='{$_POST[$lUser . '-level']}' WHERE LOWER(Username) = '{$lUser}'";
	if ($_SESSION['Level'] == $level_developer)
	    echo $querystring2 . "<br />";
	$result2 = mysql_query($querystring2, $con);
	if ($result2)
	    echo "Set level of user " . $row['Username'] . " to {$_POST[$lUser . '-level']}.<br />";
	else
	    echo "Failed to set level of user " . $row['Username'] . ".<br />";
    }
    if ( isset( $_POST[$lUser . '-memberof'] ) && ( $_POST[$lUser . '-memberof'] <> $row['MemberOf']))
    {
	$querystring2 = "UPDATE Login SET MemberOf='{$_POST[$lUser . '-memberof']}' WHERE LOWER(Username) = '{$lUser}'";
	if ($_SESSION['Level'] == $level_developer)
	    echo $querystring2 . "<br />";
	$result2 = mysql_query($querystring2, $con);
	if ($result2)
	    echo "Set group of user " . $row['Username'] . " to {$_POST[$lUser . '-memberof']}.<br />";
	else
	    echo "Failed to set group of user " . $row['Username'] . ".<br />";
    }
    if ( isset( $_POST[$lUser . '-name'] ) && ( $_POST[$lUser . '-name'] <> $row['Name']))
    {
	$querystring2 = "UPDATE Login SET Name='{$_POST[$lUser . '-name']}' WHERE LOWER(Username) = '{$lUser}'";
	if ($_SESSION['Level'] == $level_developer)
	    echo $querystring2 . "<br />";
	$result2 = mysql_query($querystring2, $con);
	if ($result2)
	    echo "Set name of user " . $row['Username'] . " to #{$_POST[$lUser . '-name']}.<br />";
	else
	    echo "Failed to set name of user " . $row['Username'] . ".<br />";
    }
    if ( isset( $_POST[$lUser . '-colorscheme'] ) && ( $_POST[$lUser . '-colorscheme'] <> $row['ColorScheme']))
    {
	$querystring2 = "UPDATE Login SET ColorScheme='{$_POST[$lUser . '-colorscheme']}' WHERE LOWER(Username) = '{$lUser}'";
	if ($_SESSION['Level'] == $level_developer)
	    echo $querystring2 . "<br />";
	$result2 = mysql_query($querystring2, $con);
	if ($result2)
	    echo "Set colorscheme of user " . $row['Username'] . " to #{$_POST[$lUser . '-colorscheme']}.<br />";
	else
	    echo "Failed to set colorscheme of user " . $row['Username'] . ".<br />";
    }
    if ( isset( $_POST[$lUser . '-fieldwidth'] ) && ( $_POST[$lUser . '-fieldwidth'] <> $row['FieldWidth']))
    {
	$querystring2 = "UPDATE Login SET FieldWidth='{$_POST[$lUser . '-fieldwidth']}' WHERE LOWER(Username) = '{$lUser}'";
	if ($_SESSION['Level'] == $level_developer)
	    echo $querystring2 . "<br />";
	$result2 = mysql_query($querystring2, $con);
	if ($result2)
	    echo "Set fieldwidth of user " . $row['Username'] . " to #{$_POST[$lUser . '-fieldwidth']}.<br />";
	else
	    echo "Failed to set fieldwidth of user " . $row['Username'] . ".<br />";
    }
    if ( isset( $_POST[$lUser . '-delete'] ))
    {
	$querystring2 = "DELETE FROM Login WHERE LOWER(Username) = '{$lUser}'";
	if ($_SESSION['Level'] == $level_developer)
	    echo $querystring2 . "<br />";
	$result2 = mysql_query($querystring2, $con);
	if ($result2)
        {
	    echo "Deleted user " . $row['Username'] . ".<br />";
	}
	else
	    echo "Failed to delete user " . $row['Username'] . ".<br />";
    }
    else if ( isset( $_POST[$lUser . '-deletegroup'] ))
    {
	$querystring2 = "DELETE FROM Login WHERE LOWER(Username) = '{$lUser}'";
	if ($_SESSION['Level'] == $level_developer)
	    echo $querystring2 . "<br />";
	$result2 = mysql_query($querystring2, $con);
	if ($result2)
        {
	    echo "Deleted group " . $row['Username'] . ".<br />";
	}
	else
	    echo "Failed to delete group " . $row['Username'] . ".<br />";
    }

}

/* Display user list and create HTML form */
echo "<br /><div style='text-align:center'>";
echo "<form id='recordinput' name='recordinput' method='post' action='loginmgr.php'>";
if ($_SESSION['Level'] < $level_admin) {
    echo "<h4>Profile</h4>";
}
else {
    echo "<h4>Users</h4>";
}
echo "<table class='sortable' style='margin:0px auto' border=1 cellpadding=4 ><thead><tr><th>Username</th><th>Password</th>";
echo "<th>Access Level</th><th>Member Of</th><th>Name</th><th>Color Scheme</th><th>Field Width</th>";
if ($_SESSION['Level'] >= $level_admin) {
    echo "<th>Delete User</th>";
}
echo "</tr></thead><tbody>";
$querystring = "SELECT * FROM Login WHERE IsGroup = 0";
$result = mysql_query($querystring, $con);
while ( $row = mysql_fetch_array($result) )
{
    if ( ($row['Username'] != $_SESSION['Username'] ) && ($_SESSION['Level'] < $level_admin) )
	continue;

    echo "<tr><td>{$row['Username']}</td>";
    $lUser = strtolower($row['Username']);
    echo "<td>";

    if ( $row['Username'] == $_SESSION['Username'] ) {
	echo "<input type='button' onclick='document.getElementById(\"currentUserChangePw\").style.display=\"block\"; this.style.display=\"none\";' value='Change PW' />";
	echo "<span id='currentUserChangePw' style='display: none'>";
	echo "<i>Old Pass: </i><input type='password' name='{$lUser}-oldpass' id='{$lUser}-oldpass' value='' size='10' /><br />";
	echo "<i>New Pass: </i><input type='password' name='{$lUser}-password' id='{$lUser}-password' value='' size='10' /><br />";
	echo "<i>Confirm : </i><input type='password' name='{$lUser}-confpass' id='{$lUser}-confpass' value='' size='10' />";
	echo "</span>";
    }
    else if ($_SESSION['Level'] >= $level_admin)
        echo "<input type='checkbox' name='{$lUser}-clearpass' /> Clear";

    echo "</td><td>";
    if ($_SESSION['Level'] < $level_admin)
	echo $levels[$row['Level']];
    else
    {
	echo "<select name='{$lUser}-level'>";
        for ( $i = 0; $i < count($levels); $i++ )
    	    echo "<option value='{$i}'>{$levels[$i]}</option>";
	echo "</select><script>document.forms['recordinput'].elements['{$lUser}-level'].selectedIndex = {$row['Level']};</script>";
    }
    echo "</td><td>";

    if ( $_SESSION['Level'] >= $level_admin )
    {
	echo "<select name='{$lUser}-memberof'>";
	echo "<option value=''";
	if (( $row['MemberOf'] == NULL ) || ( $row['MemberOf'] == '' )) {
	    echo " selected='selected'";
	}
	echo ">(none)</option>";
        for ( $i = 0; $i < count($groups); $i++ )
	{
    	    echo "<option value='{$groups[$i]}'";
	    if ( $row['MemberOf'] == $groups[$i] ) {
		echo " selected='selected'";
	    }
	    echo ">{$groups[$i]}</option>";
	}
	echo "</select>";
    }
    else if (($row['MemberOf'] != NULL) && ($row['MemberOf'] != "")) {
        echo "{$row['MemberOf']}";
    }
    else {
	echo "(none)";
    }
    echo "</td><td>";

    if (( $row['Username'] == $_SESSION['Username'] ) || ( $_SESSION['Level'] >= $level_admin ))
        echo "<input type='text' size='25' name='{$lUser}-name' value='{$row['Name']}' />";
    else
        echo "{$row['Name']}";
    echo "</td><td>";

    if (( $row['Username'] == $_SESSION['Username'] ) || ( $_SESSION['Level'] >= $level_admin ))
    {
	echo "<select name='{$lUser}-colorscheme'>";
	for ( $i = 0; $i < count($colorschemes); $i++ )
	    echo "<option value='{$i}'>{$colorschemes[$i]}</option>";
	echo "<script>document.forms['recordinput'].elements['{$lUser}-colorscheme'].selectedIndex = {$row['ColorScheme']};</script>";
    }
    echo "</td><td>";
    if (( $row['Username'] == $_SESSION['Username'] ) || ( $_SESSION['Level'] >= $level_admin ))
    {
        echo "<select name='{$lUser}-fieldwidth'>";
        for ($i = 0; $i < 11; $i++)
	    echo "<option value='{$i}'>{$i}</option>";
        echo "<script>document.forms['recordinput'].elements['{$lUser}-fieldwidth'].selectedIndex = {$row['FieldWidth']};</script>";
    }
    echo "</td>";
    if ( $_SESSION['Level'] >= $level_admin )
        echo "<td><input type='checkbox' name='{$lUser}-delete' /></td>";
    echo "</tr>";
}
echo "</tbody>";
//Add new user box
if ( $_SESSION['Level'] >= $level_admin )
{
    echo "<tfoot><tr><td><input type='text' name='username' size='10' value='New User' onclick='this.value=\"\"' /></td>";
    echo "<td><input type='password' name='password' size='10' value='' /></td><td>";
    echo "<select name='level'>";
    for ( $i = 0; $i <= $level_developer; $i++ )
	echo "<option value='{$i}'>{$levels[$i]}</option>";
    echo "</select>";
    echo "</td><td>";
    echo "<select name='memberof'>";
    echo "<option value=''>(none)</option>";
        for ( $i = 0; $i < count($groups); $i++ )
	{
    	    echo "<option value='{$groups[$i]}'>{$groups[$i]}</option>";
	}
	echo "</select>";
    echo "</td><td>";
    echo "<input type='text' size='25' name='name' value=''/></td><td>";
    echo "<select name='colorscheme'>";
    for ( $i = 0; $i < count($colorschemes); $i++ )
	echo "<option value='{$i}'>{$colorschemes[$i]}</option>";
    echo "</select>";
    echo "<script>document.forms['recordinput'].elements['colorscheme'].selectedIndex = 4;</script>";
    echo "</td><td>";
    echo "<select name='fieldwidth'>";
    for ($i = 0; $i < 11; $i++)
	echo "<option value='{$i}'>{$i}</option>";
    echo "<script>document.forms['recordinput'].elements['fieldwidth'].selectedIndex = 4;</script>";
    echo "</select>";
    echo "</td><td></td></tr></tfoot>";
}

echo "</table>";
echo "<input type='submit' value='Save' style='text-align:center' /></form></div>";


/* Display group list and create HTML form */
if ( $_SESSION['Level'] >= $level_admin ) {
	echo "<br /><div style='text-align:center'>";
	echo "<form id='recordinput' name='recordinput' method='post' action='loginmgr.php'>";
	echo "<h4>User Groups</h4>";
	echo "<table class='sortable' style='margin:0px auto' border=1 cellpadding=4 ><thead><tr><th>Group Name</th><th>Members</th><th>Delete Group</th></tr></thead><tbody>";
	$querystring = "SELECT * FROM Login WHERE IsGroup = 1";
	$result = mysql_query($querystring, $con);
	while ( $row = mysql_fetch_array($result) )
	{
    	    $lUser = strtolower($row['Username']);
    	    echo "<tr><td>{$row['Username']}</td>";
    	    echo "<td>";
    	    $querystring2 = "SELECT Username FROM Login WHERE MemberOf = '{$row['Username']}'";
    	    $result2 = mysql_query($querystring2, $con);
    	    while ( $row2 = mysql_fetch_array($result2) ) {
		echo $row2['Username'] . " ";
    	    }
    	    echo "</td><td>";
    	    if ( $_SESSION['Level'] >= $level_admin )
        	echo "<input type='checkbox' name='{$lUser}-deletegroup' />";
    	     echo "</td></tr>";
	}
	echo "</tbody>";
	//Add new group box
	if ( $_SESSION['Level'] >= $level_admin )
	{
    	    echo "<tfoot><tr><td><input type='text' name='newgroupname' size='10' value='New Group' onclick='this.value=\"\"' /></td>";
    	    echo "<td></td>";
    	    echo "<td></td></tr></tfoot>";
	}
	echo "</table>";
	echo "<input type='submit' value='Save' style='text-align:center' /></form></div>";
}

mysql_close($con);
?>
<?php include 'footer.php'; ?>

</body>
</html>

