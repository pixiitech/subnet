<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php require 'config.php'; 
session_start();?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="author" content="Gregory Hedrick" />

<?php include 'style.php'; ?>

<title>
Subnet Organizer
</title>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<script src="jquery-2.1.1.min.js"></script>
<script src="sorttable.js"></script>
<script>
//jQuery Scripts

//Show new item rows
$(document).ready(function(){
  $(".newSubnetRecordButton").click(function(){
    $subnet = $(this).attr("id");
    $(".newSubnetRecord" + $subnet).fadeIn(500);
  });
  $(".AddlSubnetInfoButton").click(function(){
    $subnet = $(this).attr("id");
    if ( $(this).attr("src") == "images/minus.png" )
    {
        $(".AddlSubnetInfo" + $subnet).fadeOut(500);
	$(this).attr("src", "images/plus.png");
    }
    else
    {
        $(".AddlSubnetInfo" + $subnet).fadeIn(500);
	$(this).attr("src", "images/minus.png");
    }
  });
  $(".newDVRChannelButton").click(function(){
    $dvr = $(this).attr("id");
    $(".newDVRChannel" + $dvr).fadeIn(500);
  });
  $(".newUserButton").click(function(){
    $userlist = $(this).attr("id");
    $(".newUser" + $userlist).fadeIn(500);
  });
  $(".newSoftwareButton").click(function(){
    $software = $(this).attr("id");
    $(".newSoftware" + $software).fadeIn(500);
  });
});

var saved = true;
window.onbeforeunload = function() {
    if ( !saved )
        return "You have unsaved changes. Do you want to leave this page?";
}
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
/************* Display Menu and Authenticate ************/
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

/***************Search, Create, Delete, and Load accounts *****************/
switch ( $_POST['fn'] )
{
    case 'new':
    {
	if (( $_POST['search'] != "" ) && ( $_POST['search'] != "Search Accounts" ))
	    $newname = $_POST['search'];
	else
	    $newname = 'New Account';
	$querystring = "INSERT INTO Accounts (Name, Owner, FullAccess) VALUES ('{$newname}', '{$_SESSION['Username']}', '{$_SESSION['Username']}')";
	$result = mysql_query($querystring, $con);
	if ( $result )
	{	
	   echo "New account added.<br />";
	   $querystring2 = "SELECT MAX(Idx) FROM Accounts";
	   $result2 = mysql_query($querystring2, $con);
	   $row2 = mysql_fetch_array($result2);
	   $account = $row2['MAX(Idx)'];
	}
	break;
    }
    case 'delete':
    {
        $querystring = "SELECT FullAccess, ReadonlyAccess From Accounts WHERE Idx = ". $_POST['acctlist'];
        $result = mysql_query($querystring, $con);
        $row = mysql_fetch_array($result);

        if (($_SESSION['Level'] >= $level_admin) || (stristr($row['FullAccess'], $_SESSION['Username'])))
	{
	    $querystring = "DELETE FROM Accounts WHERE Idx = " . $_POST['acctlist'];
	    $result = mysql_query($querystring, $con);
	    if ( $result )
	       echo "Account deleted.<br />";
	}
	else
	    echo "You are not authorized to delete this account.<br />";
	break;
    }
    case 'load':
    {
	if (( $_POST['search'] != "" ) && ( $_POST['search'] != "Search Accounts" ))
	{
    	    $search = strtolower($_POST['search']);
    	    $querystring = "SELECT Idx, Name FROM Accounts WHERE LOWER(Name) LIKE '%{$search}%'";
    	    $result = mysql_query($querystring, $con);
    	    $row = mysql_fetch_array($result);
    	    $account = $row['Idx'];
	}
	else if ( $_POST['acctlist'] != 0 )
	    $account = $_POST['acctlist'];
	break;
    }
    default:
}

if ( isset($_POST['account'] ))
    $account = $_POST['account'];
if ( isset($_GET['account'] ))
    $account = $_GET['account'];

/*************** Determine Authentication level for selected account and prevent hacking *************/
if ( isset($account))
{
    $querystring = "SELECT FullAccess, ReadonlyAccess From Accounts WHERE Idx = {$account}";
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
	$account = NULL;
	$authlevel = 0;
	die ("Unauthorized account access. Please contact an administrator.");
    }
}

/**************** Define Column Table ****************/


/**************** UPDATE DATABASE *******************/
/* Update basic account info that changed */
$querystring = "SHOW COLUMNS From Accounts";
$result = mysql_query($querystring, $con);
$account_cols = array();
while ( $row = mysql_fetch_array($result) )
    array_push($account_cols, $row['Field']);
if (( $_POST['changed'] == 'Y' ) && ($authlevel >= 2))
{
    $querystring = "SELECT * From Accounts WHERE Idx = {$_POST['account']}";
    $result = mysql_query($querystring, $con);
    $row = mysql_fetch_array($result);
    for ( $i = 0; $i < count($account_cols); $i++ )
    {
	$value = mysql_real_escape_string($_POST[$account_cols[$i]]);
	if (( $account_cols[$i] == 'Idx' ) || ( $account_cols[$i] == 'Owner' )) 
	    continue;
	if (( $account_cols[$i] == 'ReadonlyAccess' ) || ( $account_cols[$i] == 'FullAccess' )) 
	    continue;

	else if ( $value != mysql_real_escape_string($row[$account_cols[$i]]) )
	{
	    $querystring2 = "UPDATE Accounts SET {$account_cols[$i]}='{$value}' WHERE Idx={$_POST['account']}";
            $result = mysql_query($querystring2, $con);
	    if ($result)
	        echo "{$account_cols[$i]} updated to " . $_POST[$account_cols[$i]] . "<br />";
        }
    }
}

/* Read database fields and create arrays for dynamic updating */
$querystring = "SHOW COLUMNS From Subnet";
$result = mysql_query($querystring, $con);
$subnet_cols = array();
while ( $row = mysql_fetch_array($result) )
    array_push($subnet_cols, $row['Field']);
$querystring = "SHOW COLUMNS From SubnetRecord";
$result = mysql_query($querystring, $con);
$subnetrec_cols = array();
while ( $row = mysql_fetch_array($result) )
    array_push($subnetrec_cols, $row['Field']);
$querystring = "SHOW COLUMNS From DVR";
$result = mysql_query($querystring, $con);
$dvr_cols = array();
while ( $row = mysql_fetch_array($result) )
    array_push($dvr_cols, $row['Field']);
$querystring = "SHOW COLUMNS From DVRChannel";
$result = mysql_query($querystring, $con);
$dvrchan_cols = array();
while ( $row = mysql_fetch_array($result) )
    array_push($dvrchan_cols, $row['Field']);
$querystring = "SHOW COLUMNS From UserList";
$result = mysql_query($querystring, $con);
$userlist_cols = array();
while ( $row = mysql_fetch_array($result) )
    array_push($userlist_cols, $row['Field']);
$querystring = "SHOW COLUMNS From User";
$result = mysql_query($querystring, $con);
$user_cols = array();
while ( $row = mysql_fetch_array($result) )
    array_push($user_cols, $row['Field']);
$querystring = "SHOW COLUMNS From Note";
$result = mysql_query($querystring, $con);
$note_cols = array();
while ( $row = mysql_fetch_array($result) )
    array_push($note_cols, $row['Field']);
$querystring = "SHOW COLUMNS From SoftwareLibrary";
$result = mysql_query($querystring, $con);
$softwarelibrary_cols = array();
while ( $row = mysql_fetch_array($result) )
    array_push($softwarelibrary_cols, $row['Field']);
$querystring = "SHOW COLUMNS From Software";
$result = mysql_query($querystring, $con);
$software_cols = array();
while ( $row = mysql_fetch_array($result) )
    array_push($software_cols, $row['Field']);

/* Update Subnet info */
$querystring = "SELECT * FROM Subnet WHERE Account={$account}";
$result = mysql_query($querystring, $con);
while ( $row = mysql_fetch_array($result) )
{
	$subnet = $row['Idx'];
	if ( $authlevel < 2 )
	    break;
	if ( $_POST['subnetchanged'] != $subnet )
	    continue;

        for ( $i = 0; $i < count($subnet_cols); $i++ )
        {
	    //$value = htmlentities($_POST['Subnet' . $row['Idx'] . '-' . $subnet_cols[$i]]);
	    //$value = mysql_real_escape_string($value);
	    $value = mysql_real_escape_string($_POST['Subnet' . $row['Idx'] . '-' . $subnet_cols[$i]]);
	    if (( $subnet_cols[$i] == 'Idx' ) || ( $subnet_cols[$i] == 'Account' ))
	        continue;
	    else if ( $value != mysql_real_escape_string($row[$subnet_cols[$i]]) )
	    {
	        $querystring2 = "UPDATE Subnet SET {$subnet_cols[$i]}='{$value}' WHERE Idx={$row['Idx']}";
                $result = mysql_query($querystring2, $con);
	        if ($result)
	            echo "{$subnet_cols[$i]} updated to " . $_POST['Subnet' . $row['Idx'] . '-' . $subnet_cols[$i]] . "<br />";
            }
        }

	/* Update Subnet Records */
	$querystring2 = "SELECT * FROM SubnetRecord WHERE Subnet={$subnet}";
        $result2 = mysql_query($querystring2, $con);
        while ( $row2 = mysql_fetch_array($result2) )
        {
            for ( $i = 0; $i < count($subnetrec_cols); $i++ )
            {
	        $value = NULL;
	        $value = mysql_real_escape_string($_POST['SubnetRec' . $row2['Idx'] . '-' . $subnetrec_cols[$i]]);
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
	                echo "{$subnetrec_cols[$i]} updated to " . $_POST['SubnetRec' . $row2['Idx'] . '-' . $subnetrec_cols[$i]] . "<br />";
                }
            }
	    if ( isset( $_POST['SubnetRec' . $row2['Idx'] . '-Action'] ))
	    {
		if ( $_POST['SubnetRec' . $row2['Idx'] . '-Action'] == "Delete" )
		{
		    $querystring3 = "DELETE FROM SubnetRecord WHERE Idx='{$row2['Idx']}'";
		    $result = mysql_query($querystring3, $con);
		    if ($result)
		        echo "Subnet record deleted.<br />";
		}
		else if ( substr($_POST['SubnetRec' . $row2['Idx'] . '-Action'], 0, 6) == "Linkto" )
		{
		    $dest = substr($_POST['SubnetRec' . $row2['Idx'] . '-Action'], 6);
		    $querystring3 = "UPDATE SubnetRecord SET Parent = {$dest} WHERE Idx='{$row2['Idx']}'";
                    $result3 = mysql_query($querystring3, $con);
	            if ($result3)
	                echo "Record linked to #" . $dest . "<br />";
		}
		else if ( substr($_POST['SubnetRec' . $row2['Idx'] . '-Action'], 0, 6) == "Moveto" )
		{
		    $dest = substr($_POST['SubnetRec' . $row2['Idx'] . '-Action'], 6);
		    $querystring3 = "UPDATE SubnetRecord SET Subnet = {$dest} WHERE Idx='{$row2['Idx']}'";
                    $result3 = mysql_query($querystring3, $con);
	            if ($result3)
	                echo "Record moved to subnet #" . $dest . "<br />";
		}
		else if ( substr($_POST['SubnetRec' . $row2['Idx'] . '-Action'], 0, 6) == "Unlink" )
		{
		    $querystring3 = "UPDATE SubnetRecord SET Parent = Idx WHERE Idx='{$row2['Idx']}'";
                    $result3 = mysql_query($querystring3, $con);
	            if ($result3)
	                echo "Record unlinked.<br />";
		}

	    }
        }
}

/* Add new Subnet Records */
if (( $authlevel >= 2) &&
( (($_POST['SubnetRecNew-Description'] != "New Device") && ($_POST['SubnetRecNew-Description'] != "")) || ( $_POST['SubnetRecNew-IPNumber'] != '' ) ))
{
    if (stristr($_POST['SubnetRecNew-IPNumber'], '-'))
    {
	$dash = strpos($_POST['SubnetRecNew-IPNumber'], '-');
	$startnum = substr($_POST['SubnetRecNew-IPNumber'], 0, $dash);
	$endnum = substr($_POST['SubnetRecNew-IPNumber'], $dash + 1);
    }
    else
	$startnum = $endnum = $_POST['SubnetRecNew-IPNumber'];

    for ( $_POST['SubnetRecNew-IPNumber'] = $startnum; $_POST['SubnetRecNew-IPNumber'] <= $endnum; $_POST['SubnetRecNew-IPNumber']++ )
    {
	$querystring = "INSERT INTO SubnetRecord (Subnet, Parent";
	for ( $i = 0; $i < count($subnetrec_cols); $i++ )
        {
	    if (( $subnetrec_cols[$i] == "Subnet" ) || ( $subnetrec_cols[$i] == "Idx" ) || ( $subnetrec_cols[$i] == "Parent"))
		continue;
	    else
	    {
	        $querystring .= ", ";
	        $querystring .= $subnetrec_cols[$i];
	    }
	}
	$querystring .= ") VALUES (" . $_POST['subnet'] . ", '0";
	for ( $i = 0; $i < count($subnetrec_cols); $i++ )
	{
	    $value = mysql_real_escape_string($_POST['SubnetRecNew-' . $subnetrec_cols[$i]]);
	    if ( $subnetrec_cols[$i] == "MAC" )
	        $value = formatMAC($value);
	    if (( $subnetrec_cols[$i] == "Subnet" ) || ( $subnetrec_cols[$i] == "Idx" ) || ( $subnetrec_cols[$i] == "Parent"))
	        continue;
	    else
	    {
	        $querystring .= "', '";
	        $querystring .= $value;
	    }
	}
	$querystring .= "')";
	$result = mysql_query($querystring, $con);
	if ($result)
            echo "New Subnet Record added.<br />";
	$querystring = "UPDATE SubnetRecord SET Parent = Idx WHERE Parent = '0'";
	$result = mysql_query($querystring, $con);
    }
}

/* Add new Subnet */
if (( isset( $_POST['CreateSubnet'] ) ) && ( $authlevel >= 2))
{
    $querystring = "INSERT INTO Subnet (Account) VALUES ({$account})";
    $result = mysql_query($querystring, $con);
    if ($result)
	echo "Subnet created.<br />";
}

/* Delete Subnet */
if (( isset( $_POST['DeleteSubnet'] ) ) && ( strtolower($_POST['DeleteSubnet']) == 'yes' ) && ( $authlevel >= 2))
{
    $querystring = "DELETE FROM Subnet WHERE Idx={$_POST['subnet']}";
    $result = mysql_query($querystring, $con);
    if ($result)
	echo "Subnet deleted.<br />";
    $querystring = "DELETE FROM SubnetRecord WHERE Subnet={$_POST['subnet']}";
    $result = mysql_query($querystring, $con);
    if ($result)
	echo "Subnet records deleted.<br />";
}

/* Update DVR info */
$querystring = "SELECT * FROM DVR WHERE Account={$account}";
$result = mysql_query($querystring, $con);
while ( $row = mysql_fetch_array($result) )
{
	$dvr = $row['Idx'];
	if ( $_POST['dvrchanged'] != $dvr )
	    continue;
	if ( $authlevel < 2 )
	    break;
        for ( $i = 0; $i < count($dvr_cols); $i++ )
        {
	    $value = mysql_real_escape_string($_POST['DVR' . $row['Idx'] . '-' . $dvr_cols[$i]]);
	    if (( $dvr_cols[$i] == 'Idx' ) || ( $dvr_cols[$i] == 'Account' ))
	        continue;
	    else if ( $value != mysql_real_escape_string($row[$dvr_cols[$i]]) )
	    {
	        $querystring2 = "UPDATE DVR SET {$dvr_cols[$i]}='{$value}' WHERE Idx={$row['Idx']}";
                $result = mysql_query($querystring2, $con);
	        if ($result)
	            echo "{$dvr_cols[$i]} updated to " . $_POST['DVR' . $row['Idx'] . '-' . $dvr_cols[$i]] . "<br />";
            }
        }

	/* Update DVR Records */
	$querystring2 = "SELECT * FROM DVRChannel WHERE DVR={$dvr}";
        $result2 = mysql_query($querystring2, $con);
        while ( $row2 = mysql_fetch_array($result2) )
        {
            for ( $i = 0; $i < count($dvrchan_cols); $i++ )
            {
	        $value = mysql_real_escape_string($_POST['DVRChan' . $row2['Idx'] . '-' . $dvrchan_cols[$i]]);
	        if (( $dvrchan_cols[$i] == 'Idx' ) || ($dvrchan_cols[$i] == 'Type') || ($dvrchan_cols[$i] == 'DVR'))
	            continue;
	        else if ( $value != mysql_real_escape_string($row2[$dvrchan_cols[$i]]) )
	        {
	            $querystring3 = "UPDATE DVRChannel SET {$dvrchan_cols[$i]}='{$value}' WHERE Idx={$row2['Idx']}";
                    $result3 = mysql_query($querystring3, $con);
	            if ($result3)
	                echo "{$dvrchan_cols[$i]} updated to " . $_POST['DVRChan' . $row2['Idx'] . '-' . $dvrchan_cols[$i]] . "<br />";
                }
            }
	    if ( isset( $_POST['DVRChan' . $row2['Idx'] . '-Delete'] ))
	    {
		$querystring3 = "DELETE FROM DVRChannel WHERE Idx='{$row2['Idx']}'";
		$result = mysql_query($querystring3, $con);
		if ($result)
		    echo "DVR channel deleted.<br />";
	    }
        }
}

/* Add new DVR Records */
if (( $authlevel >= 2 ) && ( isset( $_POST['DVRChanNew-Channel'] )) && ( $_POST['DVRChanNew-Channel'] != '' ) )
{
    $querystring = "INSERT INTO DVRChannel (DVR";
    for ( $i = 0; $i < count($dvrchan_cols); $i++ )
    {
	if (( $dvrchan_cols[$i] == "DVR" ) || ( $dvrchan_cols[$i] == "Idx" ))
	    continue;
	else
	{
	    $querystring .= ", ";
	    $querystring .= $dvrchan_cols[$i];
	}
    }
    $querystring .= ") VALUES ('" . $_POST['dvr'];
    for ( $i = 0; $i < count($dvrchan_cols); $i++ )
    {
	if (( $dvrchan_cols[$i] == "DVR" ) || ( $dvrchan_cols[$i] == "Idx" ))
	    continue;
	else
	{
	    $querystring .= "', '";
	    $querystring .= mysql_real_escape_string($_POST['DVRChanNew-' . $dvrchan_cols[$i]]);
	}
    }
    $querystring .= "')";
    $result = mysql_query($querystring, $con);
    if ($result)
        echo "New DVR Channel added.<br />";
}

/* Add new DVR */
if (( isset( $_POST['CreateDVR'] ) ) && ($authlevel >= 2 ))
{
    $querystring = "INSERT INTO DVR (Account) VALUES ({$account})";
    $result = mysql_query($querystring, $con);
    if ($result)
	echo "DVR created.<br />";
}

/* Delete DVR */
if (( isset( $_POST['DeleteDVR'] ) ) && ( $_POST['DeleteDVR'] == 'yes' ) && ($authlevel >= 2 ))
{
    $querystring = "DELETE FROM DVR WHERE Idx={$_POST['dvr']}";
    $result = mysql_query($querystring, $con);
    if ($result)
	echo "DVR deleted.<br />";
    $querystring = "DELETE FROM DVRChannel WHERE DVR={$_POST['dvr']}";
    $result = mysql_query($querystring, $con);
    if ($result)
	echo "DVR channels deleted.<br />";
}

/* Update User List Info */
$querystring = "SELECT * FROM UserList WHERE Account={$account}";
$result = mysql_query($querystring, $con);
while ( $row = mysql_fetch_array($result) )
{
	if ( $authlevel < 2 )
	    break;
	$userlist = $row['Idx'];
	if ( $_POST['userlistchanged'] != $userlist )
	    continue;
        for ( $i = 0; $i < count($userlist_cols); $i++ )
        {
	    $value = mysql_real_escape_string($_POST['UserList' . $row['Idx'] . '-' . $userlist_cols[$i]]);
	    if (( $userlist_cols[$i] == 'Idx' ) || ( $userlist_cols[$i] == 'Account' ))
	        continue;
	    else if ( $value != mysql_real_escape_string($row[$userlist_cols[$i]]) )
	    {
	        $querystring2 = "UPDATE UserList SET {$userlist_cols[$i]}='{$value}' WHERE Idx={$row['Idx']}";
                $result = mysql_query($querystring2, $con);
	        if ($result)
	            echo "{$userlist_cols[$i]} updated to " . $_POST['UserList' . $row['Idx'] . '-' . $userlist_cols[$i]] . "<br />";
            }
        }

	/* Update Users */
	$querystring2 = "SELECT * FROM User WHERE UserList={$userlist}";
        $result2 = mysql_query($querystring2, $con);
        while ( $row2 = mysql_fetch_array($result2) )
        {
            for ( $i = 0; $i < count($user_cols); $i++ )
            {
	        $value = mysql_real_escape_string($_POST['User' . $row2['Idx'] . '-' . $user_cols[$i]]);
	        if (( $user_cols[$i] == 'Idx' ) || ($user_cols[$i] == 'UserList'))
	            continue;
	        else if ( $value != mysql_real_escape_string($row2[$user_cols[$i]]) )
	        {
	            $querystring3 = "UPDATE User SET {$user_cols[$i]}='{$value}' WHERE Idx={$row2['Idx']}";
                    $result3 = mysql_query($querystring3, $con);
	            if ($result3)
	                echo "{$user_cols[$i]} updated to " . $_POST['User' . $row2['Idx'] . '-' . $user_cols[$i]] . "<br />";
                }
            }
	    if ( isset( $_POST['User' . $row2['Idx'] . '-Delete'] ))
	    {
		$querystring3 = "DELETE FROM User WHERE Idx='{$row2['Idx']}'";
		$result = mysql_query($querystring3, $con);
		if ($result)
		    echo "User deleted.<br />";
	    }
        }
}

/* Add new Users */
if ( isset( $_POST['UserNew-Username'] ) && ( $_POST['UserNew-Username'] != '' ) && ( $_POST['UserNew-Username'] != 'New User' ) && ( $authlevel >= 2 ))
{
    $querystring = "INSERT INTO User (UserList";
    for ( $i = 0; $i < count($user_cols); $i++ )
    {
	if (( $user_cols[$i] == "UserList" ) || ( $user_cols[$i] == "Idx" ))
	    continue;
	else
	{
	    $querystring .= ", ";
	    $querystring .= $user_cols[$i];
	}
    }
    $querystring .= ") VALUES ('" . $_POST['userlist'];
    for ( $i = 0; $i < count($user_cols); $i++ )
    {
	if (( $user_cols[$i] == "UserList" ) || ( $user_cols[$i] == "Idx" ))
	    continue;
	else
	{
	    $querystring .= "', '";
	    $querystring .= mysql_real_escape_string($_POST['UserNew-' . $user_cols[$i]]);
	}
    }
    $querystring .= "')";
    $result = mysql_query($querystring, $con);
    if ($result)
        echo "New User added.<br />";
}


/* Add new UserList */
if ( isset( $_POST['CreateUserList'] ) && ( $authlevel >= 2 ) )
{
    $querystring = "INSERT INTO UserList (Account) VALUES ({$account})";
    $result = mysql_query($querystring, $con);
    if ($result)
	echo "User List created.<br />";
}

/* Delete UserList */
if ( ( isset( $_POST['DeleteUserList'] ) ) && ( strtolower($_POST['DeleteUserList'] == 'yes' ) ) && ( $authlevel >= 2 ))
{
    $querystring = "DELETE FROM UserList WHERE Idx={$_POST['userlist']}";
    $result = mysql_query($querystring, $con);
    if ($result)
	echo "User list deleted.<br />";
    $querystring = "DELETE FROM User WHERE UserList={$_POST['userlist']}";
    $result = mysql_query($querystring, $con);
    if ($result)
	echo "Users deleted.<br />";
}


/* Update note */
$querystring = "SELECT * FROM Note WHERE Account={$account}";
$result = mysql_query($querystring, $con);
while ( $row = mysql_fetch_array($result) )
{
	if ( $authlevel < 2 )
	    break;
	$note = $row['Idx'];
	if ( $_POST['notechanged'] != $note )
	    continue;

        for ( $i = 0; $i < count($note_cols); $i++ )
        {
	    $value = mysql_real_escape_string($_POST['Note' . $row['Idx'] . '-' . $note_cols[$i]]);
	    if (( $note_cols[$i] == 'Idx' ) || ( $note_cols[$i] == 'Account' ))
	        continue;
	    else if ( $value != mysql_real_escape_string($row[$note_cols[$i]]) )
	    {
	        $querystring2 = "UPDATE Note SET {$note_cols[$i]}='{$value}' WHERE Idx={$row['Idx']}";
                $result = mysql_query($querystring2, $con);
	        if ($result)
	            echo "{$note_cols[$i]} updated to " . $_POST['Note' . $row['Idx'] . '-' . $note_cols[$i]] . "<br />";
            }
        }
}

/* Add new note */
if ( isset( $_POST['CreateNote'] ) && ( $authlevel >= 2 ))
{
    $querystring = "INSERT INTO Note (Account) VALUES ({$account})";
    $result = mysql_query($querystring, $con);
    if ($result)
	echo "Note created.<br />";
}

/* Delete note */
if ( isset( $_POST['DeleteNote'] ) && ( strtolower($_POST['DeleteNote'] == 'yes')) && ( $authlevel >= 2 ) )
{
    $querystring = "DELETE FROM Note WHERE Idx={$_POST['note']}";
    $result = mysql_query($querystring, $con);
    if ($result)
	echo "Note deleted.<br />";
}

/* Update Software Library Info */
$querystring = "SELECT * FROM SoftwareLibrary WHERE Account={$account}";
$result = mysql_query($querystring, $con);
while ( $row = mysql_fetch_array($result) )
{
	if ( $authlevel < 2 )
	    break;
	$softwarelibrary = $row['Idx'];
	if ( $_POST['softwarelibrarychanged'] != $softwarelibrary )
	    continue;
        for ( $i = 0; $i < count($softwarelibrary_cols); $i++ )
        {
	    $value = mysql_real_escape_string($_POST['SoftwareLibrary' . $row['Idx'] . '-' . $softwarelibrary_cols[$i]]);
	    if (( $softwarelibrary_cols[$i] == 'Idx' ) || ( $softwarelibrary_cols[$i] == 'Account' ))
	        continue;
	    else if ( $value != mysql_real_escape_string($row[$softwarelibrary_cols[$i]]) )
	    {
	        $querystring2 = "UPDATE SoftwareLibrary SET {$softwarelibrary_cols[$i]}='{$value}' WHERE Idx={$row['Idx']}";
                $result = mysql_query($querystring2, $con);
	        if ($result)
	            echo "{$softwarelibrary_cols[$i]} updated to " . $_POST['SoftwareLibrary' . $row['Idx'] . '-' . $softwarelibrary_cols[$i]] . "<br />";
            }
        }

	/* Update Software */
	$querystring2 = "SELECT * FROM Software WHERE SoftwareLibrary={$softwarelibrary}";
        $result2 = mysql_query($querystring2, $con);
        while ( $row2 = mysql_fetch_array($result2) )
        {
            for ( $i = 0; $i < count($software_cols); $i++ )
            {
	        $value = mysql_real_escape_string($_POST['Software' . $row2['Idx'] . '-' . $software_cols[$i]]);
	        if (( $software_cols[$i] == 'Idx' ) || ($software_cols[$i] == 'SoftwareLibrary') || ($software_cols[$i] == 'InstalledOn'))
	            continue;
	        else if ( $value != mysql_real_escape_string($row2[$software_cols[$i]]) )
	        {
	            $querystring3 = "UPDATE Software SET {$software_cols[$i]}='{$value}' WHERE Idx={$row2['Idx']}";
                    $result3 = mysql_query($querystring3, $con);
	            if ($result3)
	                echo "{$software_cols[$i]} updated to " . $_POST['Software' . $row2['Idx'] . '-' . $software_cols[$i]] . "<br />";
                }
            }
	    if ( isset( $_POST['Software' . $row2['Idx'] . '-Delete'] ))
	    {
		$querystring3 = "DELETE FROM Software WHERE Idx='{$row2['Idx']}'";
		$result = mysql_query($querystring3, $con);
		if ($result)
		    echo "Software deleted.<br />";
	    }
        }
}

/* Add new Software */
if ( isset( $_POST['SoftwareNew-Description'] ) && ( $_POST['SoftwareNew-Description'] != '' ) && ( $_POST['SoftwareNew-Description'] != 'New Software' ) && ( $authlevel >= 2 ))
{
    $querystring = "INSERT INTO Software (SoftwareLibrary";
    for ( $i = 0; $i < count($software_cols); $i++ )
    {
	if (( $software_cols[$i] == "SoftwareLibrary" ) || ( $software_cols[$i] == "Idx" ))
	    continue;
	else
	{
	    $querystring .= ", ";
	    $querystring .= $software_cols[$i];
	}
    }
    $querystring .= ") VALUES ('" . $_POST['softwarelibrary'];
    for ( $i = 0; $i < count($software_cols); $i++ )
    {
	if (( $software_cols[$i] == "SoftwareLibrary" ) || ( $software_cols[$i] == "Idx" ))
	    continue;
	else
	{
	    $querystring .= "', '";
	    $querystring .= mysql_real_escape_string($_POST['SoftwareNew-' . $software_cols[$i]]);
	}
    }
    $querystring .= "')";
    $result = mysql_query($querystring, $con);
    if ($result)
        echo "New Software added.<br />";
}


/* Add new SoftwareLibrary */
if ( isset( $_POST['CreateSoftwareLibrary'] ) && ( $authlevel >= 2 ) )
{
    $querystring = "INSERT INTO SoftwareLibrary (Account) VALUES ({$account})";
    $result = mysql_query($querystring, $con);
    if ($result)
	echo "Software Library created.<br />";
}

/* Delete SoftwareLibrary */
if ( ( isset( $_POST['DeleteSoftwareLibrary'] ) ) && ( strtolower($_POST['DeleteSoftwareLibrary'] == 'yes' ) ) && ( $authlevel >= 2 ))
{
    $querystring = "DELETE FROM SoftwareLibrary WHERE Idx={$_POST['softwarelibrary']}";
    $result = mysql_query($querystring, $con);
    if ($result)
	echo "Software Library deleted.<br />";
    $querystring = "DELETE FROM Software WHERE SoftwareLibrary={$_POST['softwarelibrary']}";
    $result = mysql_query($querystring, $con);
    if ($result)
	echo "Software deleted.<br />";
}

/* Insert, update & delete attachments */

if ( ($_POST['attachmentchanged'] == 'yes')  && ( $authlevel >= 2 ))
{
    $querystring = "SELECT * FROM Attachment WHERE Account = {$account}";
    $result = mysql_query($querystring, $con);
    while ( $row = mysql_fetch_array($result))
    {
	if ( isset( $_POST["Attachment" . $row['Idx'] . "-Description"] ) && ( $_POST["Attachment" . $row['Idx'] . "-Description"] != $row['Description']))
	{
	    $querystring2 = "UPDATE Attachment SET Description = '" . $_POST['Attachment' . $row['Idx'] . '-Description'] . "' WHERE Idx = {$row['Idx']}";
	    $result2 = mysql_query($querystring2, $con);
	    if ( $result2 )
		echo "Description of attachment {$row['Filename']} changed.<br />";
	}
	if ( isset( $_POST["Attachment" . $row['Idx'] . "-Delete"] ) )
	{
	    $querystring2 = "DELETE FROM Attachment WHERE Idx={$row['Idx']}";
	    $fname = $row['Filename'];
	    $result2 = mysql_query($querystring2, $con);
	    unlink($filesdir . $fname);
	    if ( $result2 )
		echo "Attachment {$fname} deleted.<br />";
	}
    }

    if ($_FILES["Attachment"]["name"] != "")
    {
	//Save File
	$extension = end(explode(".", $_FILES["file"]["name"]));
	if ($_FILES['Attachment']['type'] != "")
	{
  		if ($_FILES["Attachment"]["error"] > 0)
    		{
    		   echo "Files Upload: error code " . $_FILES["Attachment"]["error"] . "<br>";
		   break;
		}
    		else
    		{
    		    $querystring = "INSERT INTO Attachment (Filename, Description, Account) VALUES ('{$_FILES['Attachment']['name']}', '{$_POST['Attachment-Description']}', {$account})";
    		    $result = mysql_query($querystring, $con);
    		    if ( $result )
			echo "Attachment {$_FILES['Attachment']['name']} uploaded.<br />";

    	    		echo "Type: " . $_FILES["Attachment"]["type"] . "<br>";
    	    		echo "Size: " . ($_FILES["Attachment"]["size"] / 1024) . " kB<br>";
    	    		echo "Temp file: " . $_FILES["Attachment"]["tmp_name"] . "<br>";
			echo "Destination: " . $filesdir . $_FILES["Attachment"]["name"] . "<br />";
    	    		if (file_exists($filesdir . $_FILES["Attachment"]["name"]))
      	    		{
      				errorMessage( $_FILES["Attachment"]["name"] . " already exists. ", 3);
				break;
      	   		}
    	    		else
      	    		{
				$success = move_uploaded_file($_FILES["Attachment"]["tmp_name"], $rootdir . $uploaddir . $_FILES["Attachment"]["name"]);
				if ( $success )
      				    echo "Stored in: " . $rootdir . $filesdir . $_FILES["PicAttachmentname"] . "<br />";
				else
				{
    		   		    echo "move_uploaded_file: error code " . $_FILES["Attachment"]["error"] . "<br>";
				    break;
				}
      	    		}
		}
	}
	else
	{
	    if ( $_SESSION['Level'] >= $level_developer )
	    	echo "No File Specified.<br />";
	}
    }
}

/* Update Account Access Permissions */
if ( ($_POST['accesscontrolchanged'] == 'yes') && ( $authlevel >= 2 ) )
{
    $querystring = "UPDATE Accounts SET FullAccess = '{$_POST['fullaccess-string']}' WHERE Idx = {$account}";
    $result = mysql_query($querystring, $con);
    echo "Full access permissions changed to: " . $_POST['fullaccess-string'] . "<br />";
    $querystring = "UPDATE Accounts SET ReadonlyAccess = '{$_POST['readonlyaccess-string']}' WHERE Idx = {$account}";
    $result = mysql_query($querystring, $con);
    echo "Read only access permissions changed to: " . $_POST['readonlyaccess-string'] . "<br />";

}


/**************** DISPLAY TABLES ********************/

echo "<table class='criteria' style='height:100%; width:100%'><tbody><tr><td style='width:10%'>
<form style='text-align:center' name='accounts' method='post' action='subnet.php' >
<table class='criteria' style='border: 0; text-align:top'><tr><td>
<select name='acctlist' size='12' onclick='document.forms[\"accounts\"].elements[\"fn_load\"].checked=true'>";

/* Display List of Accounts */
$querystring = "SELECT Idx, Type, Name, ReadonlyAccess, FullAccess FROM Accounts";
$result = mysql_query($querystring, $con);
while ( $row = mysql_fetch_array($result) )
{
   if (( $_SESSION['Level'] < $level_admin ) &&
       ( stristr($row['FullAccess'], $_SESSION['Username']) == false ) &&
       ( stristr($row['FullAccess'], $_SESSION['MemberOf']) == false ) &&
       ( stristr($row['ReadonlyAccess'], $_SESSION['Username']) == false ) &&
       ( stristr($row['ReadonlyAccess'], $_SESSION['MemberOf']) == false ))
	continue;
   if ( $row['Type'] == 1 )
	continue;
   echo "<option value='{$row['Idx']}' ondblclick='window.location.href=\"subnet.php?account={$row['Idx']}\";'>{$row['Name']}</option>";
}
echo "</select></td>";

if ( isset($account) )
{
    /* Display Section Menu */
    echo "<td>";
    echo "<select name='sectionlist' size='12'>";

    /* Record List of Subnets */
    $querystring = "SELECT Idx, Description FROM Subnet WHERE Account = {$account} ORDER BY Idx";
    $Subnets = array();
    $result = mysql_query($querystring, $con);
    while ( $row = mysql_fetch_array($result) )
    {
	$Subnets[$row['Idx']] = $row['Description'];
	echo "<option value='subnet{$row['Idx']}' ondblclick='window.location.href=\"#subnet{$row['Idx']}\";'>Subnet: {$row['Description']}</option>";
    }

    /* Display rest of Section Menu */
    $querystring = "SELECT Idx, Description FROM DVR WHERE Account = {$account} ORDER BY Idx";
    $result = mysql_query($querystring, $con);
    while ( $row = mysql_fetch_array($result) )
    {
	echo "<option value='dvr{$row['Idx']}' ondblclick='window.location.href=\"#dvr{$row['Idx']}\";'>DVR: {$row['Description']}</option>";
    }
    $querystring = "SELECT Idx, Description FROM UserList WHERE Account = {$account} ORDER BY Idx";
    $result = mysql_query($querystring, $con);
    while ( $row = mysql_fetch_array($result) )
    {
	echo "<option value='userlist{$row['Idx']}' ondblclick='window.location.href=\"#userlist{$row['Idx']}\";'>User List: {$row['Description']}</option>";
    }
    $querystring = "SELECT Idx, Description FROM SoftwareLibrary WHERE Account = {$account} ORDER BY Idx";
    $result = mysql_query($querystring, $con);
    while ( $row = mysql_fetch_array($result) )
    {
	echo "<option value='softwarelibrary{$row['Idx']}' ondblclick='window.location.href=\"#softwarelibrary{$row['Idx']}\";'>Software Library: {$row['Description']}</option>";
    }
    $querystring = "SELECT Idx, Title FROM Note WHERE Account = {$account} ORDER BY Idx";
    $result = mysql_query($querystring, $con);
    while ( $row = mysql_fetch_array($result) )
    {
	echo "<option value='note{$row['Idx']}' ondblclick='window.location.href=\"#note{$row['Idx']}\";'>Note: {$row['Title']}</option>";
    }
    echo "<option value='attachments' ondblclick='window.location.href=\"#attachments\";'>Attachments</option>";
    echo "<option value='accesscontrol' ondblclick='window.location.href=\"#accesscontrol \";'>Acct. Access</option>";
    echo "</select></td>";
}
echo "</tr><tr>";
echo "<td>";
echo "<input name='search' size='16' value='Search Accounts' onclick='this.value=\"\"'></td>";
if ( isset($account) )
{
    echo "<td>";
    echo "<button name='sectionbutton' type='button' onClick=\"window.location.href='subnet.php#' + document.forms['accounts'].elements['sectionlist'].value;\">Go</button>";
    echo "</td>";
}
echo "</tr><tr><td style='text-align:left'>";
echo "<input type='radio' id='fn_load' name='fn' value='load' checked='checked' />Open <br />
<input type='radio' id='fn_new' name='fn' value='new' />New <br />
<input type='radio' id='fn_delete' name='fn' value='delete' onclick=\"this.checked = window.confirm('Are you sure you want to delete the selected account?');\"/>Delete <br />";
echo "</td></tr><tr><td><input type='submit' name='go' value='Go' /></td>";
echo "</tr></table></form>";
echo "</td><td>";

if ( isset($account) )
{
    /* Display Account Info */
    $querystring = "SELECT * From Accounts WHERE Idx = {$account}";
    $result = mysql_query($querystring, $con);
    $row = mysql_fetch_array($result);
    echo "<p style='text-align:center'><h2>{$row['Name']}</h2></p>";
    echo "<p style='text-align:center'>";
    if ($row['Type']==1)
	echo "<i>Archived &nbsp;</i>";
    if ($authlevel==1)
	echo "<i> Read Only</i>";
    echo "</p>";
    echo "<form method='post' name='info' action='subnet.php'>";
    echo "<input type='hidden' name='account' value='{$account}' />";
    echo "<input type='hidden' name='changed' />";
    echo "<script>function UpdateAccountFlag() {
		document.forms['info'].elements['changed'].value = 'Y';
		saved = false;
	  }</script>";
    echo "<div>Acct. Name: <input type='text' name='Name' value=\"{$row['Name']}\" size='25' onchange='UpdateAccountFlag();' /></div>";
    echo "<div>Address: <input type='text' name='Address' value=\"{$row['Address']}\" size='35' onchange='UpdateAccountFlag();' /></div>";
    echo "<div>Contact Name: <input type='text' name='ContactName' value=\"{$row['ContactName']}\" size='25' onchange='UpdateAccountFlag();'/></div>";
    echo "<div>Contact Phone: <input type='text' name='ContactPhone' value=\"{$row['ContactPhone']}\" size='25' onchange='UpdateAccountFlag();'/></div>";
    echo "<div>Contact Email: <input type='text' name='ContactEmail' value=\"{$row['ContactEmail']}\" size='25' onchange='UpdateAccountFlag();'/></div>";
    echo "<div>Office Phone: <input type='text' name='OfficePhone' value=\"{$row['OfficePhone']}\" size='25' onchange='UpdateAccountFlag();'/></div>";
    echo "<div>Office Fax: <input type='text' name='Fax' value=\"{$row['Fax']}\" size='25' onchange='UpdateAccountFlag();'/></div>";
    echo "<div>Domain Name: <input type='text' name='DomainName' value=\"{$row['DomainName']}\" size='25' onchange='UpdateAccountFlag();'/></div>";
    echo "<div>Public IP: <input type='text' name='PublicIP' value=\"{$row['PublicIP']}\" size='25' onchange='UpdateAccountFlag();'/></div>"; 
    echo "<div>ISP Info: <textarea name='ISPInfo' cols='60' rows='3' onchange='UpdateAccountFlag();'/>{$row['ISPInfo']}</textarea></div>";
    if ( $authlevel > 1 )
    	echo "<div><input type='submit' value='Save' onclick='saved=true;'/></div>";
    echo "</form><br />";
    /* Display Create Buttons */
    if ( $authlevel > 1 )
    {
        echo "<table class='criteria'><tr><td><form name='createsubnet' method='post' action='subnet.php' style='text-align:center'>";
        echo "<input type='hidden' name='account' value='{$account}' />";
        echo "<input type='hidden' name='CreateSubnet' value='Y' />";
	echo "<input type='submit' value='Create Subnet' />";
        echo "</form></td><td>";
        echo "<form name='createdvr' method='post' action='subnet.php'>";
        echo "<input type='hidden' name='account' value='{$account}' />";
        echo "<input type='hidden' name='CreateDVR' value='Y' />";
        echo "<input type='submit' value='Create DVR' /></form></td><td>";
        echo "<form name='createuserlist' method='post' action='subnet.php'>";
        echo "<input type='hidden' name='account' value='{$account}' />";
        echo "<input type='hidden' name='CreateUserList' value='Y' />";
        echo "<input type='submit' value='Create User List' /></form></td><td>";
        echo "<form name='createsoftwarelibrary' method='post' action='subnet.php'>";
        echo "<input type='hidden' name='account' value='{$account}' />";
        echo "<input type='hidden' name='CreateSoftwareLibrary' value='Y' />";
        echo "<input type='submit' value='Create Software Library' /></form></td><td>";
        echo "<form name='createnote' method='post' action='subnet.php'>";
        echo "<input type='hidden' name='account' value='{$account}' />";
        echo "<input type='hidden' name='CreateNote' value='Y' />";
        echo "<input type='submit' value='Create Note' /></form></td></tr></table>";
    }
    echo "</td></tr><tr><td colspan='3'><br />";

    /* Display Subnets */
    $querystring = "SELECT * From Subnet WHERE Account = {$account}";
    $result = mysql_query($querystring, $con);
    while ( $row = mysql_fetch_array($result) )
    {
	$subnet = $row['Idx'];
	$networkID = $row['NetworkID'];
	echo "<form method='post' name='subnet{$subnet}' action='subnet.php#subnet{$subnet}' id='subnet{$subnet}' ><table class='criteria ' style='width:100%' padding='2px'>";
	echo "<script>function UpdateSubnetFlag(subnet){ 
	document.forms['subnet' + subnet].elements['subnetchanged'].value = subnet;
	saved = false; }</script>";
	echo "<tr><th colspan='15'><img src={$imagedir}subnet.png style='height:50px; width:50px' alt='subnet' /><b>{$row['Description']}</b></th></tr>";
	echo "<tr><th colspan='15'>";
        echo "<input type='hidden' name='account' value='{$account}' />";
        echo "<input type='hidden' name='subnet' value='{$row['Idx']}' />";
        echo "<input type='hidden' name='subnetchanged' />";
	echo "<input type='hidden' name='DeleteSubnet' />";
	echo "<img class='AddlSubnetInfoButton' id='{$subnet}' src='{$imagedir}plus.png' style='height:15px; width:15px' />&nbsp;&nbsp;";
	echo "Network ID:<input type='text' name='Subnet{$row['Idx']}-NetworkID' size='12' value=\"{$row['NetworkID']}\" onchange=\"UpdateSubnetFlag({$subnet});\" />    ";
	echo "&nbsp;&nbsp;Subnet Mask:<input type='text' name='Subnet{$row['Idx']}-SubnetMask' size='12' value=\"{$row['SubnetMask']}\" onchange=\"UpdateSubnetFlag({$subnet});\" />    ";
        echo "&nbsp;&nbsp;Description:<input type='text' name='Subnet{$row['Idx']}-Description' size='20' value=\"{$row['Description']}\" onchange=\"UpdateSubnetFlag({$subnet});\" />";
        echo "&nbsp;&nbsp;Public IP:<input type='text' name='Subnet{$row['Idx']}-PublicIP' size='20' value=\"{$row['PublicIP']}\" onchange=\"UpdateSubnetFlag({$subnet});\" />";
	echo "</th></tr>";
	echo "<tr><th hidden='hidden' class='AddlSubnetInfo{$subnet}' colspan='15'>";
	echo "Gateway:<input type='text' name='Subnet{$row['Idx']}-Gateway' size='12' value=\"{$row['Gateway']}\" onchange=\"UpdateSubnetFlag({$subnet});\" />    ";
	echo "&nbsp;&nbsp;DNS1:<input type='text' name='Subnet{$row['Idx']}-DNS1' size='12' value=\"{$row['DNS1']}\" onchange=\"UpdateSubnetFlag({$subnet});\" />    ";
        echo "&nbsp;&nbsp;DNS2:<input type='text' name='Subnet{$row['Idx']}-DNS2' size='12' value=\"{$row['DNS2']}\" onchange=\"UpdateSubnetFlag({$subnet});\" />";
        echo "&nbsp;&nbsp;DHCP Range:<input type='text' name='Subnet{$row['Idx']}-DHCPRange' size='30' value=\"{$row['DHCPRange']}\" onchange=\"UpdateSubnetFlag({$subnet});\" />";
	echo "</th></tr></table>";
	echo "<table class='criteria sortable' style='width:100%' padding='2px'><thead>";
	$headers = "<tr><th class='sorttable_nosort'></th><th>IP#</th><th>Type</th><th class='sorttable_alpha'>Description</th><th  class='sorttable_alpha'>Username</th><th class='sorttable_alpha'>Password</th><th class='sorttable_alpha'>Make</th><th  class='sorttable_alpha'>Model</th><th>MAC</th><th class='sorttable_alpha'>Location</th><th>Port</th><th class='sorttable_alpha'>Hostname</th><th class='sorttable_nosort'>Action</th><th class='sorttable_nosort'>Notes</th></tr>";
	echo $headers;

	$SubnetRecords = array();
	$querystring2 = "SELECT Idx, IPNumber, Description From SubnetRecord WHERE Subnet = {$subnet} ORDER BY IPNumber";
        $result2 = mysql_query($querystring2, $con);
	while ( $row2 = mysql_fetch_array($result2) )
	   $SubnetRecords[$row2['Idx']] = $row2['Description'];

        $querystring2 = "SELECT *, Parent AS P From SubnetRecord WHERE Subnet = {$subnet} ORDER BY (CASE WHEN Idx = Parent THEN IPNumber ELSE (SELECT IPNumber From SubnetRecord WHERE Idx = P) + .1 END), IPNumber";
        $result2 = mysql_query($querystring2, $con);
	$childset = false;
	echo "</thead><tbody>";
        while ( $row2 = mysql_fetch_array($result2) )
        {	    
	    $altText = $devicetype_names[$row2['Type']] . ' ' . formatIP($networkID, $row2['IPNumber']);
	    $subnetrec = $row2['Idx'];
/*
	    if (( $childset == false ) && ( $row2['Parent'] != $subnetrec ))    //Begin indented row
	    {
		$childset = true;
		echo "</table><table class='criteria' style='width:96%' padding='2px'>";
	    }
	    else if (( $childset == true ) && ( $row2['Parent'] == $subnetrec ))    //End indented row
	    {
		$childset = false;
		echo "</table><table class='criteria' style='width:100%' padding='2px'>";
	    }
*/
	    echo "<tr><td>";
	    if ( $authlevel > 1 )
	        echo "<a href='editor.php?item={$row2['Idx']}&subnet={$subnet}&account={$account}'><img src='{$imagedir}edit.png' style='height:20px; width:20px' alt='Device Editor' title='Device Editor' /></a>";
	    echo "</td><td sorttable_customkey=\"{$row2['IPNumber']}\">";
	    echo "<input type='text' name='SubnetRec{$subnetrec}-IPNumber' size='{$w1}' value=\"{$row2['IPNumber']}\" onchange=\"UpdateSubnetFlag({$subnet});\" />";
	    echo "</td><td sorttable_customkey='{$altText}'>";
	    echo "<table><tr><td>";
	    echo "<img src='{$imagedir}{$row2['Type']}.png' alt='{$altText}' title='{$altText}' style='height:50px; width:50px' id='SubnetRec{$subnetrec}-TypePic' />";
	    echo "</td><td>";
	    if ( $authlevel > 1 ) {
	        echo "<select name='SubnetRec{$subnetrec}-Type' style='font-size:10; width:22px' onchange=\"UpdateSubnetFlag({$subnet}); 
			document.getElementById('SubnetRec{$subnetrec}-TypePic').src = '{$imagedir}' + this.selectedIndex + '.png'; \" />";
	         for ( $i = 0; $i < count($devicetype_names); $i++ )
		    echo "<option value='{$i}'>{$devicetype_names[$i]}</option>";
	        echo "</select>";
	    }
	    echo "</td></tr></table>";
	    echo "<script>document.forms['subnet{$subnet}'].elements['SubnetRec{$subnetrec}-Type'].selectedIndex = {$row2['Type']}</script></td>";
	    $lDescription = strtolower($row2['Description']);
	    echo "<td sorttable_customkey=\"{$lDescription}\">";
	    echo "<input type='text' name='SubnetRec{$subnetrec}-Description' size='{$w16}' value=\"{$row2['Description']}\" onchange=\"UpdateSubnetFlag({$subnet});\" /></td>";
	    $lUsername = strtolower($row2['Username']);
	    echo "<td sorttable_customkey=\"{$lUsername}\">";
	    echo "<input type='text' name='SubnetRec{$subnetrec}-Username' size='{$w16}' value=\"{$row2['Username']}\" onchange=\"UpdateSubnetFlag({$subnet});\" />";
	    echo "</td><td sorttable_customkey=\"{$row2['Password']}\">";
	    echo "<input type='text' name='SubnetRec{$subnetrec}-Password' size='{$w16}' value=\"{$row2['Password']}\" onchange=\"UpdateSubnetFlag({$subnet});\" />";
	    echo "</td><td sorttable_customkey=\"{$row2['Make']}\">";
	    echo "<input type='text' name='SubnetRec{$subnetrec}-Make' size='{$w16}' value=\"{$row2['Make']}\" onchange=\"UpdateSubnetFlag({$subnet});\" />";
	    echo "</td><td sorttable_customkey=\"{$row2['Model']}\">";
	    echo "<input type='text' name='SubnetRec{$subnetrec}-Model' size='{$w16}' value=\"{$row2['Model']}\" onchange=\"UpdateSubnetFlag({$subnet});\" />";
	    echo "</td><td sorttable_customkey=\"{$row2['MAC']}\">";
	    echo "<input type='text' name='SubnetRec{$subnetrec}-MAC' size='{$w12}' value=\"{$row2['MAC']}\" onchange=\"UpdateSubnetFlag({$subnet});\" />";
	    echo "</td><td sorttable_customkey=\"{$row2['Location']}\">";
	    echo "<input type='text' name='SubnetRec{$subnetrec}-Location' size='{$w16}' value=\"{$row2['Location']}\" onchange=\"UpdateSubnetFlag({$subnet});\" />";
	    echo "</td><td sorttable_customkey=\"'{$row2['Port']}\">";
	    echo "<input type='text' name='SubnetRec{$subnetrec}-Port' size='{$w2}' value=\"{$row2['Port']}\" onchange=\"UpdateSubnetFlag({$subnet});\" />";
	    echo "</td><td sorttable_customkey=\"{$row2['Hostname']}\">";
	    echo "<input type='text' name='SubnetRec{$subnetrec}-Hostname' size='{$w16}' value=\"{$row2['Hostname']}\" onchange=\"UpdateSubnetFlag({$subnet});\" />";
	    echo "</td><td>";
	    if ( $authlevel > 1 ) {

	        echo "<select name='SubnetRec{$subnetrec}-Action' style='font-size:10; width:30px' onchange=\"UpdateSubnetFlag({$subnet});\" />";
	        echo "<option value='None'>...</option>";
	        echo "<option value='Delete'>Delete Device</option>";
	        reset($Subnets);
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
	        }
	        echo "</select>";
	    }
	    echo "</td><td>";
	    echo "<textarea name='SubnetRec{$subnetrec}-Notes' cols='{$w16}' rows='1' onchange=\"UpdateSubnetFlag({$subnet});\" >{$row2['Notes']}</textarea>";
	    echo "</td></tr>";
	}
	echo "</tbody>";
	if ( $authlevel > 1 )
	{
	/* New Subnet Record */
	    if ( $childset == true )    //End indented row
	    {
		$childset = false;
		echo "</table><table class='criteria' style='width:100%' padding='2px'>";
	    }
	    echo "<tfoot>";
	$w = $_SESSION['FieldWidth'];
	    echo "<tr hidden='hidden' class='newSubnetRecord{$subnet}'><td></td><td>";
	    echo "<input type='text' name='SubnetRecNew-IPNumber' size='{$w1}' onchange=\"UpdateSubnetFlag({$subnet});\" />";
	    echo "</td><td>";
	    echo "<select name='SubnetRecNew-Type' style='font-size:10; width:70px' onchange=\"UpdateSubnetFlag({$subnet});\" />";
	    for ( $i = 0; $i < count($devicetype_names); $i++ )
		echo "<option value='{$i}'>{$devicetype_names[$i]}</option>";
	    echo "</select></td><td>";
	    echo "<input type='text' name='SubnetRecNew-Description' size='{$w16}' value='New Device' onchange=\"UpdateSubnetFlag({$subnet});\" onclick=\"if (this.value=='New Device') this.value=''\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='SubnetRecNew-Username' size='{$w16}' onchange=\"UpdateSubnetFlag({$subnet});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='SubnetRecNew-Password' size='{$w16}' onchange=\"UpdateSubnetFlag({$subnet});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='SubnetRecNew-Make' size='{$w16}' onchange=\"UpdateSubnetFlag({$subnet});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='SubnetRecNew-Model' size='{$w16}' onchange=\"UpdateSubnetFlag({$subnet});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='SubnetRecNew-MAC' size='{$w12}' onchange=\"UpdateSubnetFlag({$subnet});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='SubnetRecNew-Location' size='{$w16}' onchange=\"UpdateSubnetFlag({$subnet});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='SubnetRecNew-Port' size='{$w2}' onchange=\"UpdateSubnetFlag({$subnet});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='SubnetRecNew-Hostname' size='{$w16}' onchange=\"UpdateSubnetFlag({$subnet});\" />";
	    echo "</td><td>";
	    echo "</td><td>";
	    echo "<textarea name='SubnetRecNew-Notes' cols='{$w16}' rows='1' onchange=\"UpdateSubnetFlag({$subnet});\"></textarea>";
	    echo "</td></tr>";
	    echo "<tr><td colspan='15'><input type='button' value='Add Device' id='{$subnet}' class='newSubnetRecordButton' />";
	    echo "<input type='button' value='Delete Subnet' onclick=\"document.forms['subnet{$subnet}'].elements['DeleteSubnet'].value = window.prompt('Type yes to confirm deleting this subnet and all records:'); document.forms['subnet{$subnet}'].submit(); \" />";
	    echo "<input type='submit' value='Save Changes' onclick='saved=true;'/></td></tr></tfoot>";
	}
	echo "</table></form><br />";
    }
    /* Display DVR's */
    $querystring = "SELECT * From DVR WHERE Account = {$account}";
    $result = mysql_query($querystring, $con);
    while ( $row = mysql_fetch_array($result) )
    {
	$dvr = $row['Idx'];
	echo "<form method='post' name='dvr{$dvr}' action='subnet.php#dvr{$dvr}' id='dvr{$dvr}'><table class='criteria'>";
	echo "<script>function UpdateDVRFlag(dvr){ document.forms['dvr' + dvr].elements['dvrchanged'].value = dvr;
		      saved = false; }</script>";
	echo "<tr><td colspan='8'><img src={$imagedir}dvr.png style='height:50px; width:50px' alt='dvr' /></td></tr>";
	echo "<tr><td colspan='8'>";
        echo "<input type='hidden' name='account' value='{$account}' />";
        echo "<input type='hidden' name='dvr' value='{$row['Idx']}' />";
        echo "<input type='hidden' name='dvrchanged' />";
	echo "<input type='hidden' name='DeleteDVR' />";
        echo "DVR Description: <input type='text' name='DVR{$row['Idx']}-Description' size='40' value=\"{$row['Description']}\" onchange=\"UpdateDVRFlag({$dvr});\" />";
	echo "</td></tr></table><table class='criteria sortable'>";

	echo "<tr><th>Channel#</th><th>Name</th><th>PTZ Protocol</th><th>PTZ Address</th><th>Make</th><th>Model</th><th>Notes</th><th>Delete</th></tr>";

        $querystring2 = "SELECT * From DVRChannel WHERE DVR = {$dvr} ORDER BY Channel";
        $result2 = mysql_query($querystring2, $con);
        while ( $row2 = mysql_fetch_array($result2) )
        {
	    $dvrchan = $row2['Idx'];
	    echo "<tr><td>";
	    echo "<input type='text' name='DVRChan{$dvrchan}-Channel' size='3' value=\"{$row2['Channel']}\" onchange=\"UpdateDVRFlag({$dvr});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='DVRChan{$dvrchan}-Name' size='16' value=\"{$row2['Name']}\" onchange=\"UpdateDVRFlag({$dvr});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='DVRChan{$dvrchan}-PTZType' size='12' value=\"{$row2['PTZType']}\" onchange=\"UpdateDVRFlag({$dvr});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='DVRChan{$dvrchan}-PTZAddress' size='3' value=\"{$row2['PTZAddress']}\" onchange=\"UpdateDVRFlag({$dvr});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='DVRChan{$dvrchan}-Make' size='12' value=\"{$row2['Make']}\" onchange=\"UpdateDVRFlag({$dvr});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='DVRChan{$dvrchan}-Model' size='12' value=\"{$row2['Model']}\" onchange=\"UpdateDVRFlag({$dvr});\" />";
	    echo "</td><td>";
	    echo "<textarea name='DVRChan{$dvrchan}-Notes' cols='16' rows='1' onchange=\"UpdateDVRFlag({$dvr});\">{$row2['Notes']}</textarea>";
	    echo "</td><td>";
	    echo "<input type='checkbox' name='DVRChan{$dvrchan}-Delete' onchange=\"UpdateDVRFlag({$dvr});\" />";
	    echo "</td></tr>";
	}

	/* New DVR Channel */
	if ($authlevel > 1)
	{
	    echo "<tfoot><tr hidden='hidden' class='newDVRChannel{$dvr}'><td>";
	    echo "<input type='text' name='DVRChanNew-Channel' size='3' onchange=\"UpdateDVRFlag({$dvr});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='DVRChanNew-Name' size='16' value=\"New DVR Channel\" onchange=\"UpdateDVRFlag({$dvr});\" onclick=\"if (this.value=='New DVR Channel') this.value='';\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='DVRChanNew-PTZType' size='12' onchange=\"UpdateDVRFlag({$dvr});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='DVRChanNew-PTZAddress' size='3' onchange=\"UpdateDVRFlag({$dvr});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='DVRChanNew-Make' size='12' onchange=\"UpdateDVRFlag({$dvr});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='DVRChanNew-Model' size='12' onchange=\"UpdateDVRFlag({$dvr});\" />";
	    echo "</td><td>";
	    echo "<textarea name='DVRChanNew-Notes' cols='16' rows='1' onchange=\"UpdateDVRFlag({$dvr});\"></textarea>";
	    echo "</td><td></td></tr>";
	    echo "<tr><td colspan='8'><input type='button' id='{$dvr}' class='newDVRChannelButton' value='Add Channel' />";
	    echo "<input type='button' name='DeleteDVRButton' value='Delete DVR'
		 onclick=\"document.forms['dvr{$dvr}'].elements['DeleteDVR'] = window.prompt('Type yes to delete DVR and all channels:'); document.forms['dvr{$dvr}'].submit(); \" />";
	    echo "<input type='submit' value='Save Changes' onclick='saved=true;'/></td></tr></tfoot>";
	}
	echo "</table></form><br />";
    }

    /* Display Userlists */
    $querystring = "SELECT * From UserList WHERE Account = {$account}";
    $result = mysql_query($querystring, $con);
    while ( $row = mysql_fetch_array($result) )
    {
	$userlist = $row['Idx'];
	echo "<form method='post' name='userlist{$userlist}' id='userlist{$userlist}' action='subnet.php#userlist{$userlist}'><table class='criteria'>";
	echo "<script>function UpdateUserListFlag(userlist){ document.forms['userlist' + userlist].elements['userlistchanged'].value = userlist;
		      saved = false; }</script>";
	echo "<tr><td colspan='9'><img src={$imagedir}userlist.png style='height:50px; width:50px' alt='User List' /></td></tr>";
	echo "<tr><td colspan='9'>";
        echo "<input type='hidden' name='account' value='{$account}' />";
        echo "<input type='hidden' name='userlist' value='{$row['Idx']}' />";
        echo "<input type='hidden' name='userlistchanged' />";
	echo "<input type='hidden' name='DeleteUserList' />";
        echo " User List Description: <input type='text' name='UserList{$row['Idx']}-Description' size='40' value=\"{$row['Description']}\" onchange=\"UpdateUserListFlag({$userlist});\" />";
	echo "</td></tr></table><table class='criteria sortable'>";

	echo "<tr><th>Username</th><th>Password</th><th>Name</th><th>Position</th><th>Email</th><th>Email Password</th><th>Phone</th><th>Notes</th><th>Delete</th></tr>";

        $querystring2 = "SELECT * From User WHERE UserList = {$userlist}";
        $result2 = mysql_query($querystring2, $con);
        while ( $row2 = mysql_fetch_array($result2) )
        {
	    $user = $row2['Idx'];
	    echo "<tr><td>";
	    echo "<input type='text' name='User{$user}-Username' size='12' value=\"{$row2['Username']}\" onchange=\"UpdateUserListFlag({$userlist});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='User{$user}-Password' size='12' value=\"{$row2['Password']}\" onchange=\"UpdateUserListFlag({$userlist});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='User{$user}-Name' size='16' value=\"{$row2['Name']}\" onchange=\"UpdateUserListFlag({$userlist});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='User{$user}-Position' size='12' value=\"{$row2['Position']}\" onchange=\"UpdateUserListFlag({$userlist});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='User{$user}-Email' size='16' value=\"{$row2['Email']}\" onchange=\"UpdateUserListFlag({$userlist});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='User{$user}-EmailPass' size='12' value=\"{$row2['EmailPass']}\" onchange=\"UpdateUserListFlag({$userlist});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='User{$user}-Phone' size='8' value=\"{$row2['Phone']}\" onchange=\"UpdateUserListFlag({$userlist});\" />";
	    echo "</td><td>";
	    echo "<textarea name='User{$user}-Notes' cols='12' rows='1' onchange=\"UpdateUserListFlag({$userlist});\">{$row2['Notes']}</textarea>";
	    echo "</td><td>";
	    echo "<input type='checkbox' name='User{$user}-Delete' onchange=\"UpdateUserListFlag({$userlist});\" />";
	    echo "</td></tr>";
	}

	/* New User Record */
	if ($authlevel > 1)
	{
	    echo "<tfoot><tr hidden='hidden' class='newUser{$userlist}'><td>";
	    echo "<input type='text' name='UserNew-Username' size='12' value='New User' onchange=\"UpdateUserListFlag({$userlist});\" onclick=\"if (this.value=='New User') this.value='';\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='UserNew-Password' size='12' onchange=\"UpdateUserListFlag({$userlist});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='UserNew-Name' size='16' onchange=\"UpdateUserListFlag({$userlist});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='UserNew-Position' size='12' onchange=\"UpdateUserListFlag({$userlist});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='UserNew-Email' size='16' onchange=\"UpdateUserListFlag({$userlist});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='UserNew-EmailPass' size='12' onchange=\"UpdateUserListFlag({$userlist});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='UserNew-Phone' size='8' onchange=\"UpdateUserListFlag({$userlist});\" />";
	    echo "</td><td>";
	    echo "<textarea name='UserNew-Notes' cols='12' rows='1' onchange=\"UpdateUserListFlag({$userlist});\"></textarea>";
	    echo "</td><td></td></tr>";
	    echo "<tr><td colspan='9'><input type='button' class='newUserButton' id='{$userlist}' value='Add User' />";
	    echo "<input type='button' value='Delete Userlist' ";
	    echo "onclick=\"document.forms['userlist{$userlist}'].elements['DeleteUserList'].value = window.prompt('Type yes to delete user list and all users:'); ";
	    echo "document.forms['userlist{$userlist}'].submit(); \" />";
	    echo "<input type='submit' value='Save Changes' onclick='saved=true;'/></td></tr></tfoot>";
	}

	echo "</table></form><br />";
    }

    /* Display Software libraries */
    $querystring = "SELECT * From SoftwareLibrary WHERE Account = {$account}";
    $result = mysql_query($querystring, $con);
    while ( $row = mysql_fetch_array($result) )
    {
	$softwarelibrary = $row['Idx'];
	echo "<form method='post' name='softwarelibrary{$softwarelibrary}' id='softwarelibrary{$softwarelibrary}' action='subnet.php#softwarelibrary{$softwarelibrary}'><table class='criteria'>";
        echo "<script>function UpdateSoftwareLibraryFlag(softwarelibrary){ document.forms['softwarelibrary' + softwarelibrary].elements['softwarelibrarychanged'].value = softwarelibrary;
		      saved = false; }</script>";
	echo "<tr><td colspan='12'><img src={$imagedir}software.png style='height:50px; width:50px' alt='Software Library' /></td></tr>";
	echo "<tr><td colspan='12'>";
        echo "<input type='hidden' name='account' value='{$account}' />";
        echo "<input type='hidden' name='softwarelibrary' value='{$row['Idx']}' />";
        echo "<input type='hidden' name='softwarelibrarychanged' />";
	echo "<input type='hidden' name='DeleteSoftwareLibrary' />";
        echo " Software Library Description: <input type='text' name='SoftwareLibrary{$row['Idx']}-Description' size='40' value=\"{$row['Description']}\" onchange=\"UpdateSoftwareLibraryFlag({$softwarelibrary});\" />";
	echo "</td></tr></table><table class='criteria sortable'>";

	echo "<tr><th></th><th>Description</th><th>Username</th><th>Password</th><th>Serial #</th><th>License Key</th><th>Developer</th><th>Support Phone #</th><th>Website</th><th>Installed On</th><th>Notes</th><th>Delete</th></tr>";

        $querystring2 = "SELECT * From Software WHERE SoftwareLibrary = {$softwarelibrary}";
        $result2 = mysql_query($querystring2, $con);
        while ( $row2 = mysql_fetch_array($result2) )
        {
	    $software = $row2['Idx'];
	    echo "<tr><td>";
	    if ( $authlevel > 1 )
	        echo "<a href='editor-software.php?item={$row2['Idx']}&softwarelibrary={$softwarelibrary}&account={$account}'><img src='{$imagedir}edit.png' style='height:20px; width:20px' alt='Software Editor' title='Software Editor' /></a>";
	    echo "</td><td>";
	    echo "<input type='text' name='Software{$software}-Description' size='{$w16}' value=\"{$row2['Description']}\" onchange=\"UpdateSoftwareLibraryFlag({$softwarelibrary});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='Software{$software}-Username' size='{$w12}' value=\"{$row2['Username']}\" onchange=\"UpdateSoftwareLibraryFlag({$softwarelibrary});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='Software{$software}-Password' size='{$w12}' value=\"{$row2['Password']}\" onchange=\"UpdateSoftwareLibraryFlag({$softwarelibrary});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='Software{$software}-Serial' size='{$w20}' value=\"{$row2['Serial']}\" onchange=\"UpdateSoftwareLibraryFlag({$softwarelibrary});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='Software{$software}-LicenseKey' size='{$w20}' value=\"{$row2['LicenseKey']}\" onchange=\"UpdateSoftwareLibraryFlag({$softwarelibrary});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='Software{$software}-Developer' size='{$w12}' value=\"{$row2['Developer']}\" onchange=\"UpdateSoftwareLibraryFlag({$softwarelibrary});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='Software{$software}-SupportNum' size='{$w12}' value=\"{$row2['SupportNum']}\" onchange=\"UpdateSoftwareLibraryFlag({$softwarelibrary});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='Software{$software}-Website' size='{$w12}' value=\"{$row2['Website']}\" onchange=\"UpdateSoftwareLibraryFlag({$softwarelibrary});\" />";
	    echo "</td><td>";
	    $installedOn = $row2['InstalledOn'];
	    echo "<select name='Software{$software}-InstalledOn' style='font-size:10; width:100px' />";
	    while ( strstr($installedOn, " ") )
	    {
		$idx = strstr($installedOn, " ", true);
		$installedOn = substr(strstr($installedOn, " "), 1);
	    	$querystring3 = "SELECT Description From SubnetRecord WHERE Idx={$idx}";
		$result3 = mysql_query($querystring3, $con);
		$row3 = mysql_fetch_array($result3);
		echo "<option>{$row3['Description']}</option>";
	    }
	    echo "</select></td><td>";
	    echo "<textarea name='Software{$software}-Notes' cols='{$w12}' rows='1' onchange=\"UpdateSoftwareLibraryFlag({$softwarelibrary});\">{$row2['Notes']}</textarea>";
	    echo "</td><td>";
	    echo "<input type='checkbox' name='Software{$software}-Delete' onchange=\"UpdateSoftwareLibraryFlag({$softwarelibrary});\" />";
	    echo "</td></tr>";
	}

	/* New Software Record */
	if ($authlevel > 1)
	{
	    echo "<tfoot><tr hidden='hidden' class='newSoftware{$softwarelibrary}'><td>";
	    echo "</td><td>";
	    echo "<input type='text' name='SoftwareNew-Description' size='{$w16}' value='New Software' onchange=\"UpdateSoftwareLibraryFlag({$softwarelibrary});\" onclick=\"if (this.value=='New Software') this.value='';\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='SoftwareNew-Username' size='{$w12}' onchange=\"UpdateSoftwareLibraryFlag({$softwarelibrary});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='SoftwareNew-Password' size='{$w12}' onchange=\"UpdateSoftwareLibraryFlag({$softwarelibrary});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='SoftwareNew-Serial' size='{$w20}' onchange=\"UpdateSoftwareLibraryFlag({$softwarelibrary});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='SoftwareNew-LicenseKey' size='{$w20}' onchange=\"UpdateSoftwareLibraryFlag({$softwarelibrary});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='SoftwareNew-Developer' size='{$w12}' onchange=\"UpdateSoftwareLibraryFlag({$softwarelibrary});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='SoftwareNew-SupportNum' size='{$w12}' onchange=\"UpdateSoftwareLibraryFlag({$softwarelibrary});\" />";
	    echo "</td><td>";
	    echo "<input type='text' name='SoftwareNew-Website' size='{$w12}' onchange=\"UpdateSoftwareLibraryFlag({$softwarelibrary});\" />";
	    echo "</td><td></td><td>";
	    echo "<textarea name='SoftwareNew-Notes' cols='{$w12}' rows='1' onchange=\"UpdateSoftwareLibraryFlag({$softwarelibrary});\">{$row2['Notes']}</textarea>";
	    echo "</td><td>";
	    echo "</td></tr>";
	    echo "<tr><td colspan='12'><input type='button' class='newSoftwareButton' id='{$softwarelibrary}' value='Add Software' />";
	    echo "<input type='button' value='Delete Library' ";
	    echo "onclick=\"document.forms['softwarelibrary{$softwarelibrary}'].elements['DeleteSoftwareLibrary'].value = window.prompt('Type yes to delete software library and all software:'); ";
	    echo "document.forms['softwarelibrary{$softwarelibrary}'].submit(); \" />";
	    echo "<input type='submit' value='Save Changes' onclick='saved=true;'/></td></tr></tfoot>";
	}
	echo "</table></form><br />";
    }

    /* Display Notes */
    $querystring = "SELECT * From Note WHERE Account = {$account}";
    $result = mysql_query($querystring, $con);
    while ( $row = mysql_fetch_array($result) )
    {
	$note = $row['Idx'];
	echo "<form method='post' name='note{$note}' id='note{$note}' action='subnet.php#note{$note}'>";
        echo "<script>function UpdateNoteFlag(note){ document.forms['note' + note].elements['notechanged'].value = note;
		      saved = false; }</script>";
	echo "<table class='criteria'>";
	echo "<tr><td><img src={$imagedir}note.png style='height:50px; width:50px' alt='note' /></td></tr>";
	echo "<tr><td>";
        echo "<input type='hidden' name='account' value='{$account}' />";
        echo "<input type='hidden' name='note' value='{$row['Idx']}' />";
        echo "<input type='hidden' name='notechanged' />";
	echo "<input type='hidden' name='DeleteNote' />";
        echo "  Title: <input type='text' name='Note{$row['Idx']}-Title' size='60' value=\"{$row['Title']}\" onchange='UpdateNoteFlag({$note});' />";
	echo "</td></tr><tr><td>";
        echo "Notes: <textarea name='Note{$row['Idx']}-Info' cols='60' rows='3' onchange='UpdateNoteFlag({$note});'>";
	echo "{$row['Info']}</textarea></td></tr>";
	if ($authlevel > 1)
	    echo "<tr><td><input type='button' value='Delete Note' onclick=\"document.forms['note{$note}'].elements['DeleteNote'].value = window.prompt('Type yes to delete note {$row['Title']}'); document.forms['note{$note}'].submit(); \" /><input type='submit' value='Save Changes' onclick='saved=true;' /></td></tr>";
	echo "</table></form>";
    }

    /* Display Attachment Table */
    echo "<form method='post' name='attachments' id='attachments' action='subnet.php' enctype='multipart/form-data'>";
    echo "<input type='hidden' name='account' value='{$account}' />";
    echo "<input type='hidden' name='attachmentchanged' />";
    echo "<table class='criteria'>";
    $querystring = "SELECT * From Attachment WHERE Account = {$account}";
    $result = mysql_query($querystring, $con);
    echo "<tr><td colspan='4'><img src='{$imagedir}paperclip.png' style='height:40px; width:40px' alt='attachments' /><b>Attachments</b></td></tr>";
    if ( $authlevel > 1 ) {
        echo "<tr><td colspan='4'>Upload Attachment: <input id='Attachment' type='file' name='Attachment' size='18' ";
        echo "onchange=\"document.forms['attachments'].elements['attachmentchanged'].value = 'yes'\" />";
        echo "Description: <input id='Attachment-Description' name='Attachment-Description' type='text' size='18' ";
        echo "onchange=\"document.forms['attachments'].elements['attachmentchanged'].value = 'yes'\" /></td></tr>";
    }

    $i = 0;
    while ( $row = mysql_fetch_array($result) )
    {
	if ( $i == 0 )
	    echo "<tr><td>Description</td><td>Filename</td><td>Size</td><td>Delete</td></tr>";
	echo "<tr><td><input type='text' name='Attachment{$row['Idx']}-Description' size='20' value='{$row['Description']}' ";
	echo "onchange=\"document.forms['attachments'].elements['attachmentchanged'].value = 'yes'\" />";
	echo "</td><td><a href='{$filesdir}{$row['Filename']}' target='_blank'>{$row['Filename']}</a></td>";
	$size = filesize($filesdir . $row['Filename']);
	echo "<td>{$size}</td>";
	echo "<td><input type='checkbox' name='Attachment{$row['Idx']}-Delete' ";
	echo "onchange=\"document.forms['attachments'].elements['attachmentchanged'].value = 'yes'\" /></td></tr>";
	$i++;
    }
    if ($authlevel > 1)
        echo "<tr><td colspan='3'><input type='submit' value='Save Changes' /></td></tr>";
    echo "</table></form>";

    /* Display Account Access Control */
    $querystring = "SELECT Owner, FullAccess, ReadonlyAccess From Accounts WHERE Idx = {$account}";
    $result = mysql_query($querystring, $con);
    $row = mysql_fetch_array($result);
    $owner = $row['Owner'];
    $fullaccesslist = $row['FullAccess'];
    $readonlyaccesslist = $row['ReadonlyAccess'];
    $fullaccess = array();
    $readonlyaccess = array();
    $noaccess = array();

    $querystring = "SELECT Username, Level, IsGroup FROM Login ORDER BY IsGroup DESC, Username ASC";
    $result = mysql_query($querystring, $con);
    while ( $row = mysql_fetch_array($result) )
    {
	if ( $row['IsGroup'] == 1 )
	    $label = "[{$row['Username']}]";
	else if ( $row['Level'] >= $level_admin )
	    $label = $row['Username'] . " *";
	else
	    $label = $row['Username'];

	if ( $row['Level'] >= $level_admin )
	    array_push($fullaccess, $label);
	else if ( stristr($fullaccesslist, $row['Username']) )
	    array_push($fullaccess, $label);
	else if ( stristr($readonlyaccesslist, $row['Username'] ) )
	    array_push($readonlyaccess, $label );
	else 
	    array_push($noaccess, $label );
    }

    echo "<form method='post' name='accesscontrol' id='accesscontrol' action='subnet.php#accesscontrol'>";
    echo "<input type='hidden' name='account' value='{$account}' />";
    echo "<input type='hidden' name='accesscontrolchanged' />";
    echo "<input type='hidden' name='fullaccess-string' value='{$fullaccesslist}' />";
    echo "<input type='hidden' name='readonlyaccess-string' value='{$readonlyaccesslist}' /><table class='criteria'>";
    echo "<tr><td colspan='3'><img src='{$imagedir}key.png' style='height:40px; width:40px' alt='Access Control' />&nbsp;<strong>Account Access</strong></td></tr>";
    echo "<tr><td colspan='3'>Owner:<input type='text' name='AC-Owner' class='label' readonly='readonly' value='{$owner}' /></td></tr>";
    echo "<tr><td>Full Access&nbsp;</td><td>Read Only</td><td>&nbsp;No Access</td></tr>";
    echo "<tr><td><select name='fullaccess' size='4'>";
    for ($i = 0; $i < count($fullaccess); $i++)
	echo "<option value='{$fullaccess[$i]}'>{$fullaccess[$i]}</option>";
    echo "</select>";
    echo "</td><td><select name='readonlyaccess' size='4'>";
    for ($i = 0; $i < count($readonlyaccess); $i++)
	echo "<option value='{$readonlyaccess[$i]}'>{$readonlyaccess[$i]}</option>";
    echo "</select></td><td><select name='noaccess' size='4'>";
    for ($i = 0; $i < count($noaccess); $i++)
	echo "<option value='{$noaccess[$i]}'>{$noaccess[$i]}</option>";

    echo "</select></td></tr>";
    echo "<tr><td>";
    echo "<script>";
    echo "function updatePermissions()
	  {
		fullaccess=document.forms[\"accesscontrol\"].elements[\"fullaccess\"];
		readonlyaccess=document.forms[\"accesscontrol\"].elements[\"readonlyaccess\"];
		 fullaccess_string = \"\";
		 for (i=0; i<fullaccess.length; i++)
		 {
		     if (fullaccess.options[i].value.substring(fullaccess.options[i].value.length - 2) == \" *\")
		         val = fullaccess.options[i].value.substring(0, fullaccess.options[i].value.length - 2);
		     else
			 val = fullaccess.options[i].value;
		     if ( fullaccess_string != \"\" )
			 fullaccess_string += \" \";
		     fullaccess_string += val;
		 }		 
		 document.forms[\"accesscontrol\"].elements[\"fullaccess-string\"].value = fullaccess_string;
		 readonlyaccess_string = \"\";
		 for (i=0; i<readonlyaccess.length; i++)
		 {
		     val = readonlyaccess.options[i].value;
 		     if ( readonlyaccess_string != \"\" )
			 readonlyaccess_string += \" \";
		     readonlyaccess_string += val;
		 }
		 document.forms[\"accesscontrol\"].elements[\"readonlyaccess-string\"].value = readonlyaccess_string;
		 document.forms[\"accesscontrol\"].elements[\"accesscontrolchanged\"].value = \"yes\";
	  }";
    echo "</script>";
    if ( $authlevel > 1 )
    echo "<button type='button' onclick='fullaccess=document.forms[\"accesscontrol\"].elements[\"fullaccess\"];
					 readonlyaccess=document.forms[\"accesscontrol\"].elements[\"readonlyaccess\"];
					 val=fullaccess.options[fullaccess.selectedIndex].value;
					 if (val.substring(val.length - 2) == \" *\")
					 {
					     window.alert(val.substring(0, val.length - 2) + \" is an administrator and is automatically granted full access.\");
					 }
					 else
					 {
					     fullaccess.remove(fullaccess.selectedIndex);
					     var option = document.createElement(\"option\");
					     option.text = val;
					     readonlyaccess.add(option);
					 }
					 updatePermissions();'> >> </button>";
    echo "</td><td>";
    if ( $authlevel > 1 )
    echo "<button type='button' onclick='fullaccess=document.forms[\"accesscontrol\"].elements[\"fullaccess\"];
					 readonlyaccess=document.forms[\"accesscontrol\"].elements[\"readonlyaccess\"];
					 val=readonlyaccess.options[readonlyaccess.selectedIndex].value;
					 readonlyaccess.remove(readonlyaccess.selectedIndex);
					 var option = document.createElement(\"option\");
					 option.text = val;
					 fullaccess.add(option);
					 updatePermissions();
					 '> << </button>";
    if ( $authlevel > 1 )
    echo "<button type='button' onclick='noaccess=document.forms[\"accesscontrol\"].elements[\"noaccess\"];
					 readonlyaccess=document.forms[\"accesscontrol\"].elements[\"readonlyaccess\"];
					 val=readonlyaccess.options[readonlyaccess.selectedIndex].value;
					 readonlyaccess.remove(readonlyaccess.selectedIndex);
					 var option = document.createElement(\"option\");
					 option.text = val;
					 noaccess.add(option);
					 updatePermissions();'> >> </button>";
    echo "</td><td>";
    if ( $authlevel > 1 )
    echo "<button type='button' onclick='noaccess=document.forms[\"accesscontrol\"].elements[\"noaccess\"];
					 readonlyaccess=document.forms[\"accesscontrol\"].elements[\"readonlyaccess\"];
					 val=noaccess.options[noaccess.selectedIndex].value;
					 noaccess.remove(noaccess.selectedIndex);
					 var option = document.createElement(\"option\");
					 option.text = val;
					 readonlyaccess.add(option);
					 updatePermissions();'> << </button>";
    echo "</td></tr>";
    if ( $authlevel > 1 )
        echo "<tr><td colspan='3'><input type='submit' value='Save' /></td></tr>";
    echo "</table></form>";
}

echo "</td></tr></tbody></table></div>";
echo "<p style='text-align:center'><a href='#top'><i>Return to top</i></a></p>";
mysql_close($con);
?>
<?php
/****************Show Footer*****************/
include 'footer.php'; ?>

</body>
</html>

