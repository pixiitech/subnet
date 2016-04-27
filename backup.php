<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php require 'config.php'; 
session_start();?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="author" content="Gregory Hedrick" />
<?php include 'style.php'; ?>
<script src="jquery-2.1.1.min.js"></script>
<title>
Mass Data
</title>

</head>
<body>

<?php
function setField($ID, $val)
{
    echo "<script>document.forms['recordinput'].elements['{$ID}'].value = '{$val}';</script>";
}
include 'menu.php'; 
if ( $_SESSION['Level'] < $level_admin )
{
    die ("You do not have authorization to view this page.<br />");
}
echo "<h2 style='text-align:center'>Mass Data</h2>";

/* Connect to SQL Server */
$con = mysql_connect($sql_server, $sql_user, $sql_pass);
if (!$con)
	die ("Could not connect to SQL Server: " . mysql_error() . "<br />");
$db_selected = mysql_select_db($sql_db, $con);
if (!$db_selected)
	die ("Could not find database. <br />");

$querystring = "SHOW TABLES";
$result = mysql_query($querystring, $con);
$tables = array();
$n = 0;
while ( $tablename = mysql_result($result, $n) )
{
    $tables[$n] = $tablename;
    $n++;
}
$outputbuffer = "";


if (isset($_POST['bak']) && ($_POST['bak'] == 'backup'))
{
    //Generic Backup
    for ( $n = 0; $tables[$n] != null; $n++ )
    {
	if ( isset( $_POST['db_' . $tables[$n]] ))
	{
	    /* Backup Table Format */
	    $querystring = "SHOW COLUMNS FROM ". $tables[$n];
	    //if ( $_SESSION['Level'] >= $level_developer )
		//echo $querystring . "<br />";
	    $result = mysql_query($querystring, $con);
  	    $file = fopen($backupdir . $tables[$n] . "-" . date("m-d-y") . ".frm","w+");
	    while ( $row = mysql_fetch_array( $result ) )
	    {
		fwrite($file, $row['Field'] . "	");
		fwrite($file, $row['Type'] . "	");
		fwrite($file, $row['Null'] . "	");
		fwrite($file, $row['Key'] . "	");
		fwrite($file, $row['Default'] . "	");
		fwrite($file, $row['Extra'] . "	");
		fwrite($file, "\n");
	    }	
	    fclose($file);
	    $outputbuffer .= $tables[$n] . " format structure saved as " . $tables[$n] . "-" . date("m-d-y") . ".frm\n";	

	    /* Backup Data */
	    $querystring = "SELECT * FROM " . $tables[$n];
	    //if ( $_SESSION['Level'] >= $level_developer )
		//echo $querystring . "<br />";
	    $result = mysql_query($querystring, $con);
  	    $file = fopen($backupdir . $tables[$n] . "-" . date("m-d-y") . ".bak","w+");
	    $rows = 0;
	    while ( $row = mysql_fetch_array($result) )
	    {
		$rows++;
		for ( $i=0; $i<count($row); $i++ )
		    fwrite($file, mysql_real_escape_string($row[$i]) . "	");
		fwrite($file, "\n");
	    }
	    fclose($file);
	    $outputbuffer .= $tables[$n] . " backup saved as " . $tables[$n] . "-" . date("m-d-y") . ".bak\n{$rows} records processed.\n";
	}
    }
}
else if (isset($_POST['res']) && ($_POST['res'] == 'restore')) 
{
    $file=fopen($backupdir . $_POST['restore_db'] . ".frm", "r");
    if ( !$file )
	echo "Unable to open file " . $_POST['restore_db'] . ".frm<br />";
    else
    {
	$buffer = "";

	for ( $i=0; !feof($file); $i++ )
	{
	    $text = fgets($file);
	    $buffer .= $text;
	}
	fclose($file);
	$_POST['format'] = $buffer;
    }
    $file=fopen($backupdir . $_POST['restore_db'] . ".bak", "r");
    if ( !$file )
	$outputbuffer .= "Unable to open file " . $_POST['restore_db'] . ".bak";
    else
    {
	$buffer = "";

	for ( $i=0; !feof($file); $i++ )
	{
	    $text = fgets($file);
	    $buffer .= $text;
	}
	fclose($file);
	$_POST['data'] = $buffer;
    }
    $_POST['newline'] = 'Linux';
    $_POST['delimiter'] = "\t";
    $_POST['upload_db'] = substr($_POST['restore_db'], 0, strpos( $_POST['restore_db'], "-" ));
}

if (isset($_POST['data']))
{
	$dataparse = $_POST['data'];
	$format = $_POST['format'];
	$fieldlist = array();
	if ($_POST['newline'] == 'Windows')
	    $newline = "\r\n";
	if ($_POST['newline'] == 'Linux')
	    $newline = "\n";
	if ($_POST['newline'] == 'Mac')
	    $newline = "\r";
	if ($_POST['newline'] == 'Windows')
	    $nllength = 2;
	else
	    $nllength = 1;
	$dellength = strlen($_POST['delimiter']);

	//Parse format string
	for ($fieldcount = 0; $format != ""; $fieldcount++)
	{
	    if ( $_POST['fsdelimiter'] == 'specify' )
	    {
		$delpos = strpos($format, $_POST['fsdelimiterspecify']);
		if ( $delpos != false )
	            $Name = substr($format, 0, $delpos);
		else
		    $Name = $format;
		$Name = trim($Name);
	        if ($Name == "")
		   break;
	        $format = substr($format, $delpos + $dellength);
	        $fieldlist[$fieldcount] = $Name;
		if  ($delpos == false)
		   break;
	    }
	    else
	    {
		$delpos = strpos($format, "\t");
		$nlpos = strpos($format, $newline);
	        $Name = substr($format, 0, $delpos);
	        if ((trim($Name) == "") || ($nlpos === false))
		   break;
	        $format = substr($format, $nlpos + $nllength);
	        $fieldlist[$fieldcount] = $Name;
	    }
	}

	//Parse data
	$rowcount = 0;
	$failedrowcount = 0;
	while ($dataparse != "")
	{
	    $rowdata = array();
	    $break = false;
	    for ( $n = 0; (($break == false) && ($dataparse != "")); $n++ )
	    {
		$nlpos = strpos($dataparse, $newline);
		$delpos = strpos($dataparse, $_POST['delimiter']);
		if ( $delpos === false ) //Last field before EOF
		{
		    $rowdata[$n] = trim($dataparse);
		    $dataparse = "";
		    $break = true;
		}
		else if ( $nlpos === false ) //Last line, more fields
		{
		    $rowdata[$n] = trim(substr($dataparse, 0, $delpos));
		    $dataparse = substr($dataparse, $delpos + $dellength);
		}
		else if ( $nlpos < $delpos ) //Last field of not last line
		{
		    $rowdata[$n] = trim(substr($dataparse, 0, $nlpos));
		    $dataparse = substr($dataparse, $nlpos + $nllength);
		    $break = true;
		}
		else	//More fields, more lines
		{
		    $rowdata[$n] = trim(substr($dataparse, 0, $delpos));
		    $dataparse = substr($dataparse, $delpos + $dellength);
		}
	    }

	    if ( $rowdata[0] == '' )
		continue;
	    $querystring = "INSERT INTO " . $_POST['upload_db'] . "(";
	    for ($n = 0; $n < count($fieldlist); $n++)
	    {
		if ( $n != 0 )
		    $querystring .= ", ";
		$querystring .=  $fieldlist[$n];
	    }
	    $querystring .= ") VALUES (";
	    for ($n = 0; $n < count($fieldlist); $n++)
	    {
		if ( $n != 0 )
		    $querystring .= ", ";
		$querystring .=  "'" . $rowdata[$n] . "'";
	    }
	    $querystring .= ")";
	    $result = mysql_query($querystring, $con);
	    $outputbuffer .= $querystring . "\n\n";
	    if ( $result ) {
		$outputbuffer .= "Record inputted successfully.\n\n";
	        $rowcount++;
	    }
	    else {
		$outputbuffer .= "Record failed to insert.\n\n";
		$failedrowcount++;
	    } 

	}  
	$outputbuffer .= "Restore " . $_POST['upload_db'] . ": " . $fieldcount . " fields on " . $rowcount . " record(s) processed successfully. {$failedrowcount} record(s) failed.";

}

echo "<br /><div class='recordinput'>
<h3 style='text-align:center'>Database Backup</h3>
<form name='backupform' method='post' action='backup.php'>
<input id='bak' name='bak' type='hidden' />
<input type='checkbox' name='db_all' onclick=\"";
for ( $n = 0; $tables[$n] != null; $n++ )
    echo "document.forms['backupform'].elements['db_{$tables[$n]}'].checked='true'; ";
echo "\" />All  ";

for ( $n = 0; $tables[$n] != null; $n++ )
    echo "<input type='checkbox' name='db_{$tables[$n]}' />{$tables[$n]}  ";

echo "<br />
<input type='submit' value='Backup' onclick='document.forms[\"backupform\"].elements[\"bak\"].value=\"backup\"' />
</form></div>
<div class='recordinput'>
<h3 style='text-align:center'>Database Restore</h3>
<form name='restoreform' method='post' style='text-align:center' action='backup.php'>
<input id='res' name='res' type='hidden' />
<select name='restore_db'>";
/* Load list of forms */
$files = glob($backupdir . "*.bak");

for ( $i = 0; $i < count($files); $i++ )
{
    $extpos = strpos($files[$i], ".bak");
    $noext = substr($files[$i], strlen($backupdir), $extpos - strlen($backupdir)); 
    echo "<option value='{$noext}'>{$noext}</option>";
}
echo "</select><br />
<input type='submit' value='Restore' onclick='document.forms[\"restoreform\"].elements[\"res\"].value=\"restore\"' /><br />
</form></div>
<div class='recordinput'>
<h3 style='text-align:center'>Mass Data SQL Upload</h3>
<form name='sqlupload' method='post' action='backup.php'>
<table class='criteria'><tbody><tr><td colspan='2'>
Insert data into:<br />";
for ( $n = 0; $tables[$n] != null; $n++ )
{
    if ( $_POST['upload_db'] == $tables[$n] )
        echo "<input type='radio' name='upload_db' id='{$tables[$n]}Option' value='{$tables[$n]}' checked=true required='required' />{$tables[$n]}  ";
    else
        echo "<input type='radio' name='upload_db' id='{$tables[$n]}Option' value='{$tables[$n]}' required='required' />{$tables[$n]}  ";
}
echo "</td></tr>
<tr><td colspan='2'><b>Format Field Delimiter: </b>
<input type='radio' name='fsdelimiter' value='restore' checked='true' /> Restore Mode    
<input type='radio' name='fsdelimiter' id='specify' value='specify' /> Specify (default is tab): 
<input type='text' name='fsdelimiterspecify' size='2' value=\"\t\" onchange=\"document.forms['sqlupload'].elements['specify'].checked=true;\" /></td></tr>
<tr><td colspan='2'><b>   Data Field Delimiter: </b>(default is tab) 
<input type='text' name='delimiter' size='2' value='	' /></td></tr>
<tr><td colspan='2'><b>   Newline </b><input type='radio' name='newline' value='Linux' checked='true' /> Linux \\n (default)  
		  <input type='radio' name='newline' value='Windows' /> Windows \\r\\n  
		  <input type='radio' name='newline' value='Mac' /> Mac \\r  <br /><br /></td></tr>";
echo "<tr><td colspan='2'>Acceptable fields for SubnetRecord: ";
$querystring = "SHOW COLUMNS From SubnetRecord";
$result = mysql_query($querystring, $con);
$account_cols = array();
while ( $row = mysql_fetch_array($result) )
    echo $row['Field'] . " ";
echo "</td></tr>";
echo "<tr><td><b>Format String </b><br />
<input type='file' id='loadFormatString' name='loadFormatString' size='1' /></td>
<td><textarea name='format' rows='4' cols='100'>{$_POST['format']}</textarea></td></tr>
<tr><td><b>CSV Data</b><br /><input type='file' id='loadData' name='loadData' size='1' /></td>
<td><textarea name='data' rows='8' cols='100'>{$_POST['data']}</textarea></td></tr>
<tr><td><b>Output Window</b></td>
<td><textarea name='output' rows='8' cols='100'>{$outputbuffer}</textarea></td></tr>
<td colspan='2'><input type='submit' value='Upload' /><input type='reset' value='Clear' onclick='document.forms[\"sqlupload\"].elements[\"format\"].value = \"\"; document.forms[\"sqlupload\"].elements[\"data\"].value = \"\"; document.forms[\"sqlupload\"].elements[\"output\"].value = \"\";' />
</td></tr></tbody></table>
</form></div>";
//Script to enable text file upload
echo "<script type='text/javascript'>
  function readFormatFile(evt) {
    var f = evt.target.files[0]; 
    if (f) {
      var r = new FileReader();
      r.onload = function(e) { 
	      var contents = e.target.result;
	      document.forms['sqlupload'].elements['format'].value = contents;
      }
      r.readAsText(f);
    } else { 
      alert('Failed to load file');
    }
  }
  function readDataFile(evt) {
    var f = evt.target.files[0]; 
    if (f) {
      var r = new FileReader();
      r.onload = function(e) { 
	      var contents = e.target.result;
	      document.forms['sqlupload'].elements['data'].value = contents;
      }
      r.readAsText(f);
    } else { 
      alert('Failed to load file');
    }
  }
  document.getElementById('loadFormatString').addEventListener('change', readFormatFile, false);
  document.getElementById('loadData').addEventListener('change', readDataFile, false);
</script>";
mysql_close($con);
?>
<?php include 'footer.php';?>

</body>
</html>

