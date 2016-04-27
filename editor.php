<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php require 'config.php'; 
session_start();?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="author" content="Gregory Hedrick" />

<?php include 'style.php'; ?>
<title>
Device Editor
</title>
</head>
<body>

<?php
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

/************** Connect to SQL Server ***************/
$con = mysql_connect($sql_server, $sql_user, $sql_pass);
if (!$con)
	die ("Could not connect to SQL Server: " . mysql_error() . "<br />");
$db_selected = mysql_select_db($sql_db, $con);
if (!$db_selected)
	die ("Could not find database. <br />");
echo "<div style='text-align:center'>";

/*************** Determine Authentication level for selected account and prevent hacking *************/
if ( isset($_GET['account']))
{
    $querystring = "SELECT FullAccess, ReadonlyAccess From Accounts WHERE Idx = {$_GET['account']}";
    $result = mysql_query($querystring, $con);
    $row = mysql_fetch_array($result);

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
    {
	$_GET['account'] = NULL;
	$authlevel = 0;
	die ("Unauthorized account access. Please contact an administrator.");
    }
}

$querystring = "SHOW COLUMNS From SubnetRecord";
$result = mysql_query($querystring, $con);
$subnetrec_cols = array();
while ( $row = mysql_fetch_array($result) )
    array_push($subnetrec_cols, $row['Field']);

$querystring2 = "SELECT * FROM SubnetRecord WHERE Idx={$_GET['item']}";
$result2 = mysql_query($querystring2, $con);
$row2 = mysql_fetch_array($result2);

$querystring = "SELECT * FROM Subnet WHERE Idx={$row2['Subnet']}";
$result = mysql_query($querystring, $con);
$row = mysql_fetch_array($result);
$networkID = $row['NetworkID'];
$subnet = $row['Idx'];
$account = $row['Account'];
$publicIP = $row['PublicIP'];

/* Update Subnet Records */
if (( $_POST['subnetchanged'] == 'yes' ) && ( $authlevel > 1 ))
{
    for ( $i = 0; $i < count($subnetrec_cols); $i++ )
    {
        $value = NULL;
        $value = mysql_real_escape_string($_POST[$subnetrec_cols[$i]]);
        if ( $subnetrec_cols[$i] == 'MAC' )
	        $value = formatMAC($value);
        if (( $subnetrec_cols[$i] == 'Idx' ) || ($subnetrec_cols[$i] == 'Subnet') || ($subnetrec_cols[$i] == 'Parent'))
            continue;    
        else if ( $value != mysql_real_escape_string($row2[$subnetrec_cols[$i]]) )
        {
    	if  (( $subnetrec_cols[$i] == 'IPNumber' ) && ( $row2['IPNumber'] == $row2['Parent'] ))
    	    {
                $querystring3 = "UPDATE SubnetRecord SET Parent='{$value}' WHERE Idx={$row2['Idx']}";
                $result3 = mysql_query($querystring3, $con);
	    }
            $querystring3 = "UPDATE SubnetRecord SET {$subnetrec_cols[$i]}='{$value}' WHERE Idx={$row2['Idx']}";
            $result3 = mysql_query($querystring3, $con);
            if ($result3)
                echo "{$subnetrec_cols[$i]} updated to " . $_POST[$subnetrec_cols[$i]] . "<br />";
        }
    }

    if ( isset( $_POST['Action'] ))
    {
        if ( $_POST['Action'] == "Delete" )
        {
	    $querystring3 = "DELETE FROM SubnetRecord WHERE Idx='{$row2['Idx']}'";
	    $result = mysql_query($querystring3, $con);
	    if ($result)
	         echo "Subnet record deleted.<br />";
        }
        else if ( substr($_POST['Action'], 0, 6) == "Linkto" )
        {
	    $dest = substr($_POST['Action'], 6);
	    $querystring3 = "UPDATE SubnetRecord SET Parent = {$dest} WHERE Idx='{$row2['Idx']}'";
            $result3 = mysql_query($querystring3, $con);
            if ($result3)
               echo "Record linked to #" . $dest . "<br />";
        }
        else if ( substr($_POST['Action'], 0, 6) == "Moveto" )
        {
	    $dest = substr($_POST['Action'], 6);
	    $querystring3 = "UPDATE SubnetRecord SET Subnet = {$dest} WHERE Idx='{$row2['Idx']}'";
            $result3 = mysql_query($querystring3, $con);
            if ($result3)
                echo "Record moved to subnet #" . $dest . "<br />";
        }
        else if ( substr($_POST['Action'], 0, 6) == "Unlink" )
        {
	    $querystring3 = "UPDATE SubnetRecord SET Parent = Idx WHERE Idx='{$row2['Idx']}'";
            $result3 = mysql_query($querystring3, $con);
            if ($result3)
               echo "Record unlinked.<br />";
        }
    }
}

$querystring2 = "SELECT * FROM SubnetRecord WHERE Idx={$_GET['item']}";
$result2 = mysql_query($querystring2, $con);
$row2 = mysql_fetch_array($result2);

echo "<h4>Device Editor</h4>";
echo "<form name='subnet' id='subnet' method='POST' action='editor.php?item={$_GET['item']}&subnet={$_GET['subnet']}&account={$_GET['account']}'>"; 
echo "<input type='hidden' name='subnetchanged' />";
if (substr($networkID, strlen($networkID) - 2) == '.0')
    $networkID = substr($networkID, 0, strlen($networkID) - 1);
echo "<table class='main'>";
$ipAddress = formatIP($networkID, $row2['IPNumber']);
if ( $row2['Port'] == 0 )
    $ipPort = '';
else
    $ipPort = $row2['Port'];
$altText = $devicetype_names[$row2['Type']] . ' ' . $ipAddress;
echo "<tr><td colspan='2'><img src='{$imagedir}{$row2['Type']}.png' alt='{$altText}' title='{$altText}' style='height:50px; width:50px' /></td></tr>";
echo "<tr><td>Device Type</td><td><select name='Type' style='font-size:10; width:150px' onchange=\"document.forms['subnet'].elements['subnetchanged'].value = 'yes';\" />";
for ( $i = 0; $i < count($devicetype_names); $i++ )
    echo "<option value='{$i}'>{$devicetype_names[$i]}</option>";
echo "</select>";
echo "<script>document.forms['subnet'].elements['Type'].selectedIndex = {$row2['Type']}</script></td></tr>";
echo "<tr><td>IP Address</td>";
echo "<td>{$networkID}<input type='text' name='IPNumber' size='4' value='{$row2['IPNumber']}' onchange=\"document.forms['subnet'].elements['subnetchanged'].value = 'yes';\" /></td></tr>";
echo "<tr><td>Description</td>";
echo "<td><input type='text' name='Description' size='20' value='{$row2['Description']}' onchange=\"document.forms['subnet'].elements['subnetchanged'].value = 'yes';\" /></td></tr>";
echo "<tr><td>Username</td>";
echo "<td><input type='text' name='Username' size='20' value='{$row2['Username']}' onchange=\"document.forms['subnet'].elements['subnetchanged'].value = 'yes';\" /></td></tr>";
echo "<tr><td>Password</td>";
echo "<td><input type='text' name='Password' size='20' value='{$row2['Password']}' onchange=\"document.forms['subnet'].elements['subnetchanged'].value = 'yes';\" /></td></tr>";
echo "<tr><td>Make</td>";
echo "<td><input type='text' name='Make' size='20' value='{$row2['Make']}' onchange=\"document.forms['subnet'].elements['subnetchanged'].value = 'yes';\" /></td></tr>";
echo "<tr><td>Model</td>";
echo "<td><input type='text' name='Model' size='20' value='{$row2['Model']}' onchange=\"document.forms['subnet'].elements['subnetchanged'].value = 'yes';\" /></td></tr>";
echo "<tr><td>MAC</td>";
echo "<td><input type='text' name='MAC' size='16' value='{$row2['MAC']}' onchange=\"document.forms['subnet'].elements['subnetchanged'].value = 'yes';\" /></td></tr>";
echo "<tr><td>Location</td>";
echo "<td><input type='text' name='Location' size='20' value='{$row2['Location']}' onchange=\"document.forms['subnet'].elements['subnetchanged'].value = 'yes';\" /></td></tr>";
echo "<tr><td>Port</td>";
echo "<td><input type='text' name='Port' size='5' value='{$row2['Port']}' onchange=\"document.forms['subnet'].elements['subnetchanged'].value = 'yes';\" /></td></tr>";
echo "<tr><td>Hostname</td>";
echo "<td><input type='text' name='Hostname' size='20' value='{$row2['Hostname']}' onchange=\"document.forms['subnet'].elements['subnetchanged'].value = 'yes';\" /></td></tr>";
echo "<tr><td>Action</td>";
echo "<td><select name='Action' style='font-size:10; width:150px' onchange=\"document.forms['subnet'].elements['subnetchanged'].value = 'yes';\" />";
echo "<option value='None'>...</option>";
echo "<option value='Delete'>Delete Device</option>";
/*	    reset($Subnets);
	    while (list($key, $val) = each($Subnets))
	    {
		if ( $row2['Subnet'] == $key )
		    continue;
		echo "<option value='Moveto{$key}'>Move to subnet: {$val}</option>";
	    }
	    if ($row2['Idx'] != $row2['Parent'])
		echo "<option value='Unlink'>Unlink</option>";
	    reset($SubnetRecords);
	    while (list($key, $val) = each($SubnetRecords))
	    {
		if (( $row2['Idx'] == $key ) || ( $row2['Parent'] == $key ))
		    continue;
		echo "<option value='Linkto{$key}'>Link to device: {$val}</option>";
	    }*/
echo "</select></td></tr>";
echo "<tr><td>Open in Browser</td><td><a href='http://{$ipAddress}:{$ipPort}' target='_blank'>Local</a>&nbsp;&nbsp;";
if ( $publicIP != NULL )
    echo "<a href='http://{$publicIP}:{$ipPort}' target='_blank'>Remote</a></td></tr>";
echo "<tr><td colspan='2'>Notes</td></tr>";
echo "<tr><td colspan='2'><textarea name='Notes' cols='40' rows='4' onchange=\"document.forms['subnet'].elements['subnetchanged'].value = 'yes';\">{$row2['Notes']}</textarea></td></tr>";
echo "<tr><td colspan='2'>Installed Software:</td></tr>";
echo "<tr><td colspan='2'><select name='InstalledSoftware' size='3'>";
$querystring = "SELECT * FROM Software WHERE InstalledOn LIKE '%{$row2['Idx']}%'";
$result = mysql_query($querystring, $con);
while ($row = mysql_fetch_array($result))
    echo "<option>{$row['Description']}</option>";
echo "</select>";
if ( $authlevel > 1 )
    echo "<tr><td colspan='2'><input type='submit' name='Submit' value='Save'></input></td></tr>";
echo "</table></form>";
echo "<p class='center'><form name='return' method='post' action='subnet.php#subnet{$subnet}'><input type='hidden' name='account' value='{$account}' />";
echo "<input type='submit' value='Return to Subnet' /></p>";

mysql_close($con);
?>
<?php include 'footer.php'; ?>

</body>
</html>

