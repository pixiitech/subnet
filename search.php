<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php require 'config.php'; 
session_start();?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="author" content="Gregory Hedrick" />
<?php


?>
<?php include 'style.php'; ?>
<title>
Search Page
</title>
<script src='sorttable.js'></script>
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

function formatMAC($address)
{
    $address = str_replace("-", "", $address);
    $address = str_replace(":", "", $address);
    return $address;
}
function formatIP($id, $ipnum)
{
    if ($ipnum == 0)
	return 'DHCP';
    if (substr($id, strlen($id) - 2) == '.0')
	return substr($id, 0, strlen($id) - 1) . $ipnum;
    else 
	return $id . $ipnum;
}
require 'menu.php';

/* Connect to SQL Server */
$con = mysql_connect($sql_server, $sql_user, $sql_pass);
if (!$con)
	die ("Could not connect to SQL Server: " . mysql_error() . "<br />");
$db_selected = mysql_select_db($sql_db, $con);
if (!$db_selected)
	die ("Could not find database. <br />");

if ( $_POST['search'] == 'yes' )
{
	if ( $_POST['SubnetSearch-Account'] != "")
	{
	    $_POST['SubnetSearch-Account'] = strtolower($_POST['SubnetSearch-Account']);
	    $searchstring .= "SELECT * FROM Accounts, Subnet, SubnetRecord WHERE Accounts.Name LIKE '%{$_POST['SubnetSearch-Account']}%' ";
	    $searchstring .= "AND SubnetRecord.Subnet = Subnet.Idx AND Subnet.Account = Accounts.Idx";
	}
	else
	{
	    $searchstring = "SELECT * FROM Accounts, Subnet, SubnetRecord WHERE SubnetRecord.Subnet = Subnet.Idx AND Subnet.Account = Accounts.Idx";
	}
	if ( $_SESSION['Level'] < $level_admin )
	{
	    $searchstring .= " AND (";
	    $searchstring .= "LOWER(Accounts.FullAccess) LIKE '%" . strtolower($_SESSION['Username']) . "%' OR ";
	    $searchstring .= "LOWER(Accounts.FullAccess) LIKE '%" . strtolower($_SESSION['MemberOf']) . "%' OR ";
	    $searchstring .= "LOWER(Accounts.ReadonlyAccess) LIKE '%" . strtolower($_SESSION['Username']) . "%' OR ";
	    $searchstring .= "LOWER(Accounts.ReadonlyAccess) LIKE '%" . strtolower($_SESSION['MemberOf']) . "%')";
	}
	if ( $_POST['SubnetSearch-IPNumber'] != "")
	{
	    $searchstring .= " AND";
	    $searchstring .= " SubnetRecord.IPNumber = ";
	    $searchstring .= $_POST['SubnetSearch-IPNumber'];
	}
	if ($_POST['SubnetSearch-Type'] > 0)
	{
	    $searchstring .= " AND";
	    $searchstring .= " SubnetRecord.Type = ";
	    $searchstring .= $_POST['SubnetSearch-Type'] - 1;
	}
	if ( $_POST['SubnetSearch-Description'] != "")
	{
	    $searchstring .= " AND";
	    $searchstring .= " LOWER(SubnetRecord.Description) LIKE ";
	    $searchstring .= "'%" . strtolower($_POST['SubnetSearch-Description']) . "%'";
	}
	if ( $_POST['SubnetSearch-Username'] != "")
	{

	    $searchstring .= " AND";
	    $searchstring .= " LOWER(SubnetRecord.Username) LIKE  ";
	    $searchstring .= "'%" . strtolower($_POST['SubnetSearch-Username']) . "%'";
	}
	if ( $_POST['SubnetSearch-Password'] != "")
	{

	    $searchstring .= " AND";
	    $searchstring .= " LOWER(SubnetRecord.Password) LIKE  ";
	    $searchstring .= "'%" . strtolower($_POST['SubnetSearch-Password']) . "%'";

	}
	if ($_POST['SubnetSearch-Make'] != "")
	{
		$searchstring .= " AND";
	    $searchstring .= " LOWER(SubnetRecord.Make) LIKE  ";
	    $searchstring .= "'%" . strtolower($_POST['SubnetSearch-Make']) . "%'";
	}
	if ($_POST['SubnetSearch-Model'] != "")
	{
	    
		$searchstring .= " AND";
	    $searchstring .= " LOWER(SubnetRecord.Model) LIKE  ";
	    $searchstring .= "'%" . strtolower($_POST['SubnetSearch-Model']) . "%'";
	}
	if ($_POST['SubnetSearch-MAC'] != "")
	{
	    
		$searchstring .= " AND";
	    $searchstring .= " LOWER(SubnetRecord.MAC) LIKE  ";
	    $searchstring .= "'%" . strtolower(formatMAC($_POST['SubnetSearch-MAC'])) . "%'";
	}
	if ($_POST['SubnetSearch-Location'] != "")
	{
	    
		$searchstring .= " AND";
	    $searchstring .= " LOWER(SubnetRecord.Location) LIKE  ";
 	    $searchstring .= "'%" . strtolower($_POST['SubnetSearch-Location']) . "%'";
	}
	if ($_POST['SubnetSearch-Port'] != "")
	{
	    $searchstring .= " AND";
	    $searchstring .= " SubnetRecord.Port =  ";
	    $searchstring .= $_POST['SubnetSearch-Port'];
	}
	if ($_POST['SubnetSearch-Hostname'] != "")
	{
		$searchstring .= " AND";
	    $searchstring .= " LOWER(SubnetRecord.Hostname) LIKE  ";
	    $searchstring .= "'%" . strtolower($_POST['SubnetSearch-Hostname']) . "%'";
	}
	if ($_POST['SubnetSearch-Notes'] != "")
	{
		$searchstring .= " AND";
	    $searchstring .= " LOWER(SubnetRecord.Notes) LIKE  ";
	    $searchstring .= "'%" . strtolower($_POST['SubnetSearch-Notes']) . "%'";
	}
	$result = mysql_query($searchstring, $con);
}

$w1 = 1 + $_SESSION['FieldWidth'];
$w2 = 2 + $_SESSION['FieldWidth'];
$w8 = 8 + $_SESSION['FieldWidth'];
$w12 = 12 + $_SESSION['FieldWidth'];
$w16 = 16 + $_SESSION['FieldWidth'];

/* Display search fields and create HTML form */
echo "<br /><div style='text-align:center'>";
echo "<form id='recordinput' name='recordinput' method='post' action='search.php'>";
echo "<input type='hidden' name='search' value='yes' />";
echo "<table style='margin:0px auto' border=1 cellpadding=4 ><tbody>";
$headers = "<tr><th>Account</th><th>IP#</th><th>Type</th><th>Description</th><th>Username</th><th>Password</th><th>Make</th><th>Model</th><th>MAC</th><th>Location</th><th>Port</th><th>Hostname</th><th>Notes</th></tr>";
echo $headers;
echo "<tr><td><input type='text' name='SubnetSearch-Account' size='{$w12}' value='{$_POST['SubnetSearch-Account']}' /></td><td>";
    echo "<input type='text' name='SubnetSearch-IPNumber' value='{$_POST['SubnetSearch-IPNumber']}' size='{$w2}'  />";
    echo "</td><td>";
    echo "<select name='SubnetSearch-Type' style='font-size:10' value='{$_POST['SubnetSearch-Type']}' />";
    echo "<option value='0'>All Types</option>";
    for ( $i = 0; $i < count($devicetype_names); $i++ )
    {
	$j = $i + 1;
	echo "<option value='{$j}'>{$devicetype_names[$i]}</option>";
    }
    echo "</select>";
    echo "<script>document.forms['recordinput'].elements['SubnetSearch-Type'].selectedIndex={$_POST['SubnetSearch-Type']}</script>";
    echo "</td><td>";
    echo "<input type='text' name='SubnetSearch-Description' size='{$w16}' value='{$_POST['SubnetSearch-Description']}' />";
    echo "</td><td>";
    echo "<input type='text' name='SubnetSearch-Username' size='{$w16}' value='{$_POST['SubnetSearch-Username']}' />";
    echo "</td><td>";
    echo "<input type='text' name='SubnetSearch-Password' size='{$w16}' value='{$_POST['SubnetSearch-Password']}' />";
    echo "</td><td>";
    echo "<input type='text' name='SubnetSearch-Make' size='{$w16}' value='{$_POST['SubnetSearch-Make']}' />";
    echo "</td><td>";
    echo "<input type='text' name='SubnetSearch-Model' size='{$w16}' value='{$_POST['SubnetSearch-Model']}' />";
    echo "</td><td>";
    echo "<input type='text' name='SubnetSearch-MAC' size='{$w12}' value='{$_POST['SubnetSearch-MAC']}' />";
    echo "</td><td>";
    echo "<input type='text' name='SubnetSearch-Location' size='{$w16}' value='{$_POST['SubnetSearch-Location']}' />";
    echo "</td><td>";
    echo "<input type='text' name='SubnetSearch-Port' size='{$w2}' value='{$_POST['SubnetSearch-Port']}' />";
    echo "</td><td>";
    echo "<input type='text' name='SubnetSearch-Hostname' size='{$w16}' value='{$_POST['SubnetSearch-Hostname']}' />";
    echo "</td><td>";
    echo "<textarea name='SubnetSearch-Notes' cols='{$w16}' rows='1' value='{$_POST['SubnetSearch-Notes']}' /></textarea>";
    echo "</td></tr>";
echo "<tr><td colspan='13'><input type='submit' value='Search' />";
echo "<input type='button' value='Clear' onclick='document.forms[\"recordinput\"].elements[\"SubnetSearch-Account\"].value = \"\";
					document.forms[\"recordinput\"].elements[\"SubnetSearch-Type\"].selectedIndex = 0;
					document.forms[\"recordinput\"].elements[\"SubnetSearch-IPNumber\"].value = \"\";
					document.forms[\"recordinput\"].elements[\"SubnetSearch-Description\"].value = \"\";
					document.forms[\"recordinput\"].elements[\"SubnetSearch-Username\"].value = \"\";
					document.forms[\"recordinput\"].elements[\"SubnetSearch-Password\"].value = \"\";
					document.forms[\"recordinput\"].elements[\"SubnetSearch-Make\"].value = \"\";
					document.forms[\"recordinput\"].elements[\"SubnetSearch-Model\"].value = \"\";
					document.forms[\"recordinput\"].elements[\"SubnetSearch-MAC\"].value = \"\";
					document.forms[\"recordinput\"].elements[\"SubnetSearch-Location\"].value = \"\";
					document.forms[\"recordinput\"].elements[\"SubnetSearch-Port\"].value = \"\";
					document.forms[\"recordinput\"].elements[\"SubnetSearch-Hostname\"].value = \"\";
					document.forms[\"recordinput\"].elements[\"SubnetSearch-Notes\"].value = \"\";' />";
echo "</td></tr>";
if ( $_POST['search'] == 'yes' )
{
	$headers = "<tr><th class='sorttable_nosort'>Open</th><th>Account</th><th class='sorttable_alpha'>IP#</th><th>Type</th><th 	class='sorttable_alpha'>Description</th><th class='sorttable_alpha'>Username</th><th class='sorttable_alpha'>Password</th><th class='sorttable_alpha'>Make</th><th class='sorttable_alpha'>Model</th><th class='sorttable_alpha'>MAC</th><th class='sorttable_alpha'>Location</th><th class='sorttable_numeric'>Port</th><th class='sorttable_alpha'>Hostname</th><th class='sorttable_alpha'>Notes</th></tr>";
	$count = 0;
	echo "<tr><td colspan='13'><i>Search Results:</i></td></tr></table>";
	echo "<br /><table style='margin:0px auto' border=1 cellpadding=4 class='sortable'>" . $headers;
	while ( $row = mysql_fetch_array($result))
	{
		    $subnetrec = $row['Idx'];
	    	    $altText = $devicetype_names[$row['Type']] . ' ' . $IP;
		    echo "<tr><td><a href='editor.php?item={$subnetrec}'><img src='{$imagedir}edit.png' /></a></td>";
		    echo "<td>";
		    echo $row['Name'];
		    echo "</td><td>";
		    $IP = formatIP($row['NetworkID'], $row['IPNumber']);
		    echo "<input type='text' class='label' readonly='readonly' name='SubnetRec{$subnetrec}-IPNumber' size='{$w8}' value='{$IP}' />";
		    echo "</td><td sorttable_customkey='{$altText}'>";
		    echo "<img src='{$imagedir}{$row['Type']}.png' alt='{$altText}' title='{$altText}' style='height:50px; width:50px' />";
		    echo "</td><td>";
		    echo "<input type='text' class='label' readonly='readonly' name='SubnetRec{$subnetrec}-Description' size='{$w16}' value='{$row['Description']}' sorttable_customkey='{$row['Description']}' />";
		    echo "</td><td>";
		    echo "<input type='text' class='label' readonly='readonly' name='SubnetRec{$subnetrec}-Username' size='{$w16}' value='{$row['Username']}' sorttable_customkey='{$row['Username']}' />";
		    echo "</td><td>";
		    echo "<input type='text' class='label' readonly='readonly' name='SubnetRec{$subnetrec}-Password' size='{$w16}' value='{$row['Password']}' sorttable_customkey='{$row['Password']}' />";
		    echo "</td><td>";
		    echo "<input type='text' class='label' readonly='readonly' name='SubnetRec{$subnetrec}-Make' size='{$w16}' value='{$row['Make']}' sorttable_customkey='{$row['Make']}' />";
		    echo "</td><td>";
		    echo "<input type='text' class='label' readonly='readonly' name='SubnetRec{$subnetrec}-Model' size='{$w16}' value='{$row['Model']}' sorttable_customkey='{$row['Model']}' />";
		    echo "</td><td>";
		    echo "<input type='text' class='label' readonly='readonly' name='SubnetRec{$subnetrec}-MAC' size='{$w12}' value='{$row['MAC']}' sorttable_customkey='{$row['MAC']}' />";
		    echo "</td><td>";
		    echo "<input type='text' class='label' readonly='readonly' name='SubnetRec{$subnetrec}-Location' size='{$w16}' value='{$row['Location']}' sorttable_customkey='{$row['Location']}' />";
		    echo "</td><td>";
		    echo "<input type='text' class='label' readonly='readonly' name='SubnetRec{$subnetrec}-Port' size='{$w2}' value='{$row['Port']}' sorttable_customkey='{$row['Port']}' />";
		    echo "</td><td>";
		    echo "<input type='text' class='label' readonly='readonly' name='SubnetRec{$subnetrec}-Hostname' size='{$w16}' value='{$row['Hostname']}' sorttable_customkey='{$row['Hostname']}' />";
		    echo "</td><td>";
		    echo "<textarea class='label' name='SubnetRec{$subnetrec}-Notes' cols='{$w16}' rows='1' readonly='readonly' sorttable_customkey='{$row['Notes']}' />{$row['Notes']}</textarea>";
		    echo "</td></tr>";
		    $count++;
	}
}
echo "</tbody></table></form>";
if ( $count != "" )
    echo "<br /><i>{$count} record(s) found.</i></div>";
mysql_close($con);
?>
<?php include 'footer.php'; ?>

</body>
</html>

