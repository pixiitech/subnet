<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php require 'config.php'; 
session_start();?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="author" content="Gregory Hedrick" />
<script src='sorttable.js'></script>
<?php include 'style.php'; ?>
<title>
Account Management
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

/* Create newly submitted account */

if ( isset( $_POST['NewName'] ) && ( $_POST['NewName'] != "New Account" ) && ( $_POST['NewName'] != "" ))
{
    $lName = strtolower($_POST['NewName']);
    $querystring = "SELECT Name FROM Accounts WHERE LOWER(Name) = '{$lName}'";
    if ($_SESSION['Level'] == $level_developer)
	echo $querystring . "<br />";
    if (mysql_fetch_array(mysql_query($querystring, $con)))
	echo "Account {$_POST['NewName']} already exists, please choose a different name.<br />";
    else
    {
	$querystring = "INSERT INTO Accounts (Name, Owner, Address, ContactName, ContactPhone, OfficePhone) VALUES ('{$_POST['NewName']}', '{$_SESSION['NewContactName']}', '{$_POST['NewAddress']}', '{$_POST['NewContactName']}', {$_POST['NewContactPhone']}, {$_POST['NewOfficePhone']})";
	if ( $_SESSION['Level'] == $level_developer )
	    echo $querystring . "<br />";
	$result = mysql_query($querystring, $con);
	if ($result)
	    echo "Add new account {$_POST['NewName']} succeeded.<br />";
        else
	    echo "Add new account {$_POST['NewName']} failed.<br />";
    }
}

/* Build column list */
$querystring = "SHOW COLUMNS From Accounts";
$result = mysql_query($querystring, $con);
$account_cols = array();
while ( $row = mysql_fetch_array($result) )
    array_push($account_cols, $row['Field']);

/* Update accounts that were changed */
$querystring = "SELECT * FROM Accounts";
$result = mysql_query($querystring, $con);
while ( $row = mysql_fetch_array($result) )
{
	$account = $row['Idx'];
	/*************** Determine Authentication level for each account and prevent hacking *************/
	if ($_SESSION['Level'] >= $level_admin)
	    $authlevel = 2;
	else if (stristr($row['FullAccess'], $_SESSION['Username']))
	    $authlevel = 2;
	else if (stristr($row['ReadonlyAccess'], $_SESSION['Username']))
	    $authlevel = 1;
	else
	    $authlevel = 0;

	if ( $authlevel < 2 )
	    continue;
	if ( !isset($_POST['Account' . $row['Idx'] . '-Changed'] ))
	    continue;
	if ($_POST['Account' . $row['Idx'] . '-Changed'] != 1)
	    continue;

        for ( $i = 0; $i < count($account_cols); $i++ )
        {

	    if ( !isset($_POST['Account' . $row['Idx'] . '-' . $account_cols[$i]]) )
		continue;
	    $value = mysql_real_escape_string($_POST['Account' . $row['Idx'] . '-' . $account_cols[$i]]);
	    $mysql = mysql_real_escape_string($row[$account_cols[$i]]);
	    if (( $account_cols[$i] == 'Idx' ) || ( $account_cols[$i] == 'Type' ) || ( $account_cols[$i] == 'Owner' ))
	        continue;
	    else if ( $value != mysql_real_escape_string($row[$account_cols[$i]]))
	    {
	        $querystring2 = "UPDATE Accounts SET {$account_cols[$i]}='{$value}' WHERE Idx={$row['Idx']}";
		if ($_SESSION['Level'] == $level_developer)
		    echo $querystring2 . "<br />";
                $result2 = mysql_query($querystring2, $con);
	        if ($result2)
	            echo "{$account_cols[$i]} updated to " . $_POST['Account' . $row['Idx'] . '-' . $account_cols[$i]] . "<br />";
            }
        }
    if ( isset( $_POST['Account' . $row['Idx'] . '-Delete'] ))
    {
	$querystring2 = "DELETE FROM Accounts WHERE Idx = {$row['Idx']}";
	if ($_SESSION['Level'] == $level_developer)
	    echo $querystring2 . "<br />";
	$result2 = mysql_query($querystring2, $con);
	if ($result2)
        {
	    echo "Deleted account " . $row['Name'] . ".<br />";
	}
	else
	    echo "Failed to delete account " . $row['Name'] . ".<br />";
    }
    if (( isset( $_POST['Account' . $row['Idx'] . '-Archive'] )) && ( $row['Type'] != 1 ))
    {
	$querystring2 = "UPDATE Accounts SET Type = 1 WHERE Idx = {$row['Idx']}";
	if ($_SESSION['Level'] == $level_developer)
	    echo $querystring2 . "<br />";
	$result2 = mysql_query($querystring2, $con);
	if ($result2)
        {
	    echo "Archived account " . $row['Name'] . ".<br />";
	}
	else
	    echo "Failed to archive account " . $row['Name'] . ".<br />";
    }
    if (( !isset( $_POST['Account' . $row['Idx'] . '-Archive'] )) && ( $row['Type'] == 1 ))
    {
	$querystring2 = "UPDATE Accounts SET Type = 0 WHERE Idx = {$row['Idx']}";
	if ($_SESSION['Level'] == $level_developer)
	    echo $querystring2 . "<br />";
	$result2 = mysql_query($querystring2, $con);
	if ($result2)
        {
	    echo "Unarchived account " . $row['Name'] . ".<br />";
	}
	else
	    echo "Failed to unarchive account " . $row['Name'] . ".<br />";
    }

}

/* Display account list and create HTML form */
echo "<br /><div style='text-align:center'><h1>Account Management</h1>";
echo "<form id='recordinput' name='recordinput' method='post' action='acctmgr.php'>";
echo "<script>function updateFlag(acct) { 
		document.forms['recordinput'].elements['Account' + acct + '-Changed'].value = 1; }</script>";
echo "<table style='margin:0px auto' border=1 cellpadding=4 class='sortable'><tbody><tr><th class='sorttable_nosort'>Open</th><th>Account Name</th><th>Address</th>";
echo "<th class='sorttable_alpha'>Contact</th><th class='sorttable_numeric'>Contact Phone</th><th class='sorttable_numeric'>Office Phone</th><th class='sorttable_nosort'>Archived</th><th class='sorttable_nosort'>Delete</th></tr>";
$querystring = "SELECT * FROM Accounts";
$result = mysql_query($querystring, $con);
while ( $row = mysql_fetch_array($result) )
{
	    /*************** Determine Authentication level for each account and prevent hacking *************/
	    if ($_SESSION['Level'] >= $level_admin)
		$authlevel = 2;
	    else if (stristr($row['FullAccess'], $_SESSION['Username']))
		$authlevel = 2;
	    else if (stristr($row['FullAccess'], $_SESSION['MemberOf']))
		$authlevel = 2;
	    else if (stristr($row['ReadonlyAccess'], $_SESSION['Username']))
		$authlevel = 1;
	    else if (stristr($row['ReadonlyAccess'], $_SESSION['MemberOf']))
		$authlevel = 1;
	    else
		continue;

	    $lName = strtolower($row['Name']);
	    if ( $authlevel == 2 )
	    {
		    echo "<tr>";
		    echo "<td><a href='subnet.php?account={$row['Idx']}'><img src='{$imagedir}edit.png' style='height:20px; width:20px' alt=\"{$row['Name']}\" title=\"{$row['Name']}\" /></a></td>";
		    echo "<td sorttable_customkey=\"{$row['Name']}\">";
		    echo "<input name='Account{$row['Idx']}-Changed' hidden='hidden' />";
		    echo "<input name='Account{$row['Idx']}-Name' value=\"{$row['Name']}\" size='20' onchange='updateFlag({$row['Idx']});' /></td>";
		    echo "<td sorttable_customkey=\"{$row['Address']}\"><input name='Account{$row['Idx']}-Address' value=\"{$row['Address']}\" size='40' onchange='updateFlag({$row['Idx']});' /></td>";
		    echo "<td sorttable_customkey=\"{$row['ContactName']}\"><input name='Account{$row['Idx']}-ContactName' value=\"{$row['ContactName']}\" size='20' onchange='updateFlag({$row['Idx']});' /></td>";
		    echo "<td sorttable_customkey=\"{$row['ContactPhone']}\"><input name='Account{$row['Idx']}-ContactPhone' value=\"{$row['ContactPhone']}\" size='14' onchange='updateFlag({$row['Idx']});' /></td>";
		    echo "<td sorttable_customkey=\"{$row['OfficePhone']}\"><input name='Account{$row['Idx']}-OfficePhone' value=\"{$row['OfficePhone']}\" size='14' onchange='updateFlag({$row['Idx']});' /></td>";
		    echo "<td>";
		    echo "<input type='checkbox' name='Account{$row['Idx']}-Archive' ";
		    if ( $row['Type'] == 1 )
			echo "checked='checked'";
		    echo " onchange='updateFlag({$row['Idx']});' />";
		    echo "</td>";
		    echo "<td>";
		    echo "<input type='checkbox' name='Account{$row['Idx']}-Delete' onchange='updateFlag({$row['Idx']});' />";
		    echo "</td></tr>";
	    }
	    else if ( $authlevel == 1 )
	    {
		    echo "<tr>";
		    echo "<td><a href='subnet.php?account={$row['Idx']}'><img src='{$imagedir}edit.png' style='height:20px; width:20px' alt=\"{$row['Name']}\" title=\"{$row['Name']}\" /></a></td>";
		    echo "<td>{$row['Name']}</td>";
		    echo "<td>{$row['Address']}</td>";
		    echo "<td>{$row['ContactName']}</td>";
		    echo "<td>{$row['ContactPhone']}</td>";
		    echo "<td>{$row['OfficePhone']}</td>";
		    echo "<td>";
		    if ( $row['Type'] == 1 )
			echo "X";
		    echo "</td>";
		    echo "<td>";
		    echo "</td></tr>";
	    }

}

echo "</tbody></table>";
echo "<input type='submit' value='Save' style='text-align:center' /></form></div>";
mysql_close($con);
?>
<?php include 'footer.php'; ?>

</body>
</html>

