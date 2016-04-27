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
<script src="jquery-2.1.1.min.js"></script>
<script>
$(document).ready(function(){
    $(".ShowAvailableComputers").click({function(){
	alert("TEST");
	$(".AvailableComputers").fadeIn(500);
    });
});

</script>
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

$querystring = "SHOW COLUMNS From Software";
$result = mysql_query($querystring, $con);
$software_cols = array();
while ( $row = mysql_fetch_array($result) )
    array_push($software_cols, $row['Field']);

$querystring2 = "SELECT * FROM Software WHERE Idx={$_GET['item']}";
$result2 = mysql_query($querystring2, $con);
$row2 = mysql_fetch_array($result2);

$querystring = "SELECT * FROM SoftwareLibrary WHERE Idx={$row2['SoftwareLibrary']}";
$result = mysql_query($querystring, $con);
$row = mysql_fetch_array($result);
$softwarelibrary = $row['Idx'];
$account = $row['Account'];

/* Update SoftwareLibrary Records */
if (( $_POST['softwarechanged'] == 'yes' ) && ( $authlevel > 1 ))
{
    for ( $i = 0; $i < count($software_cols); $i++ )
    {
        $value = NULL;
        $value = mysql_real_escape_string($_POST[$software_cols[$i]]);
        if (( $software_cols[$i] == 'Idx' ) || ($software_cols[$i] == 'SoftwareLibrary') || ($software_cols[$i] == 'Parent'))
            continue;    
        else if ( $value != mysql_real_escape_string($row2[$software_cols[$i]]) )
        {
            $querystring3 = "UPDATE Software SET {$software_cols[$i]}='{$value}' WHERE Idx={$row2['Idx']}";
            $result3 = mysql_query($querystring3, $con);
            if ($result3)
                echo "{$software_cols[$i]} updated to " . $_POST[$software_cols[$i]] . "<br />";
        }
    }
}

$querystring2 = "SELECT * FROM Software WHERE Idx={$_GET['item']} ORDER BY Description";
$result2 = mysql_query($querystring2, $con);
$row2 = mysql_fetch_array($result2);

echo "<h4>Software Editor</h4>";
echo "<form name='software' id='software' method='POST' action='editor-software.php?item={$_GET['item']}&software={$_GET['software']}&account={$_GET['account']}'>"; 
echo "<script>function UpdateSoftwareLibraryFlag(){ document.forms['software'].elements['softwarechanged'].value = 'yes'; }</script>";
echo "<input type='hidden' name='softwarechanged' />";
echo "<input type='hidden' name='InstalledOn' value='{$row2['InstalledOn']}' />";
echo "<table class='main'>";
	    echo "<tr><td>";
	    echo "Description<br />";
	    echo "<input type='text' name='Description' size='{$w20}' value='{$row2['Description']}' onchange=\"UpdateSoftwareLibraryFlag();\" />";
	    echo "</td><td rowspan='10'>";
    	    echo "<script>function UpdateInstallList()
	    {
		 InstalledOnList=document.forms[\"software\"].elements[\"InstalledOnList\"];
		 AvailableComputers=document.forms[\"software\"].elements[\"AvailableComputers\"];
		 InstalledOnString = \"\";
		 for (i=0; i<InstalledOnList.length; i++)
		 {
		     InstalledOnString += InstalledOnList.options.item(i).value;
		     InstalledOnString += \" \";
		 }		 
		 document.forms[\"software\"].elements[\"InstalledOn\"].value = InstalledOnString;
		 UpdateSoftwareLibraryFlag();
	    }</script>";
	    echo "Installed on:<br />";
	    $installedOn = $row2['InstalledOn'];
	    echo "<select name='InstalledOnList' size='10' style='font-size:10; width:170px' />";
	    while ( strstr($installedOn, " ") )
	    {
		$idx = strstr($installedOn, " ", true);
		$installedOn = substr(strstr($installedOn, " "), 1);
	    	$querystring3 = "SELECT Idx, Description From SubnetRecord WHERE Idx={$idx}";
		$result3 = mysql_query($querystring3, $con);
		$row3 = mysql_fetch_array($result3);
		echo "<option value='{$row3['Idx']}'>{$row3['Description']}</option>";
	    }
	    echo "</select><br />";
    	    if ( $authlevel > 1 )
    		echo "<button type='button' onclick='InstalledOnList=document.forms[\"software\"].elements[\"InstalledOnList\"];
					 AvailableComputers=document.forms[\"software\"].elements[\"AvailableComputers\"];
					 item=InstalledOnList.options[InstalledOnList.selectedIndex];
					 InstalledOnList.remove(InstalledOnList.selectedIndex);
					 AvailableComputers.add(item);
					 UpdateInstallList();'> \/ </button>";
    	    if ( $authlevel > 1 )
    		echo "<button type='button' onclick='InstalledOnList=document.forms[\"software\"].elements[\"InstalledOnList\"];
					 AvailableComputers=document.forms[\"software\"].elements[\"AvailableComputers\"];
					 item=AvailableComputers.options[AvailableComputers.selectedIndex];
					 AvailableComputers.remove(AvailableComputers.selectedIndex);
					 InstalledOnList.add(item);
					 UpdateInstallList();
					 '> /\ </button>";
	    echo "<br />Available Machines:<br />";
	    echo "<select class='AvailableComputers' name='AvailableComputers' size='10' style='font-size:10; width:170px' />";
	    $querystring4 = "SELECT SubnetRecord.Idx, SubnetRecord.Description FROM Subnet, SubnetRecord WHERE Subnet.Account = {$account} AND SubnetRecord.Subnet = Subnet.Idx AND (SubnetRecord.Type=0 OR SubnetRecord.Type=1)";
	    $result4 = mysql_query($querystring4, $con);
	    while ( $row4 = mysql_fetch_array($result4))
	    {
	    	if ( strstr( $row2['InstalledOn'], $row4['Idx'] ) )
		    continue;
		echo "<option value='{$row4['Idx']}'>{$row4['Description']}</option>";
	    }
	    echo "</select><br />";


	    echo "</td></tr><tr><td>";
	    echo "Username<br />";
	    echo "<input type='text' name='Username' size='{$w16}' value='{$row2['Username']}' onchange=\"UpdateSoftwareLibraryFlag();\" />";
	    echo "</td></tr><tr><td>";
	    echo "Password<br />";
	    echo "<input type='text' name='Password' size='{$w16}' value='{$row2['Password']}' onchange=\"UpdateSoftwareLibraryFlag();\" />";
	    echo "</td></tr><tr><td>";
	    echo "Serial Number<br />";
	    echo "<input type='text' name='Serial' size='{$w36}' value='{$row2['Serial']}' onchange=\"UpdateSoftwareLibraryFlag();\" />";
	    echo "</td></tr><tr><td>";
	    echo "License Key<br />";
	    echo "<input type='text' name='LicenseKey' size='{$w36}' value='{$row2['LicenseKey']}' onchange=\"UpdateSoftwareLibraryFlag();\" />";
	    echo "</td></tr><tr><td>";
	    echo "Developer<br />";
	    echo "<input type='text' name='Developer' size='{$w16}' value='{$row2['Developer']}' onchange=\"UpdateSoftwareLibraryFlag();\" />";
	    echo "</td></tr><tr><td>";
	    echo "Support Phone #<br />";
	    echo "<input type='text' name='SupportNum' size='{$w16}' value='{$row2['SupportNum']}' onchange=\"UpdateSoftwareLibraryFlag();\" />";
	    echo "</td></tr><tr><td>";
	    echo "Website<br />";
	    echo "<input type='text' name='Website' size='{$w36}' value='{$row2['Website']}' onchange=\"UpdateSoftwareLibraryFlag();\" />";
	    echo "</td></tr><tr><td>";
	    echo "Notes<br />";
	    echo "<textarea name='Notes' cols='30' rows='1' onchange=\"UpdateSoftwareLibraryFlag();\">{$row2['Notes']}</textarea>";
	    echo "</td></tr><tr><td>";
	    echo "Delete<br />";
	    echo "<input type='checkbox' name='Delete' onchange=\"UpdateSoftwareLibraryFlag();\" />";
	    echo "</td></tr><tr><td colspan='2'>";
	    echo "<input type='submit' name='Save Changes' value='Save' /></td></tr>";
	    echo "</table></form>";
	    echo "<p class='center'><form name='return' method='post' action='subnet.php#softwarelibrary{$softwarelibrary}'><input type='hidden' name='account' value='{$account}' />";
	    echo "<input type='submit' value='Return to Subnet' /></p>";

mysql_close($con);
?>
<?php include 'footer.php'; ?>

</body>
</html>

