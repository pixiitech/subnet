<?php
global $cols, $con, $sql_server, $sql_user, $sql_pass;

$sql_user = "root";
$sql_pass = "battleship787";
$sql_server = "localhost";
$sql_db = "Subnet";
$logoPic = "gazebo-wb2.png";
$commName = "Subnet Organizer";
$rootdir = "/var/www/";
$filesdir = "files/";
$uploaddir = "files/";
$backupdir = "backups/";
$imagedir = "images/";

$encryption_salt = "pixii";
$max_upload_size = 8000000;		//8 MB

$session_expiration = 60*60*24;
$cookie_expiration = 60*60*24;		//24 hours

//Define how privilege levels are saved in the database
$level_logout = -1;
$level_disabled = 0;
$level_user = 1;
$level_admin = 2;
$level_developer = 3;

//Privilege Level names
$levels = array("Disabled", "User", "Admin", "Developer");

//Device type list
$devicetype_names = array("Server", "Workstation", "Printer", "Router",
			  "IP Camera", "DVR", "Wireless AP", "Wireless Bridge", 
			  "Switch", "NAS", "Access Control", "Alt. NIC",
			  "Virtual PC", "TV", "Modem", "Laptop",
			  "UPS", "Encoder", "Other", "IP Audio", 
			  "Mobile Device", "VoIP Phone", "Projector");

//Color Scheme names
$colorschemes = array("Aqua", "White on Blue", "Pink Panther",
		"Rainy Day", "Mainframe", "Honeycomb",
		"Ultraviolet", "No Style");

//Size Variables
if ( isset( $_SESSION['Level'] ))
{
    $w1 = 1 + $_SESSION['FieldWidth'];
    $w2 = 2 + $_SESSION['FieldWidth'];
    $w8 = 8 + $_SESSION['FieldWidth'];
    $w12 = 12 + $_SESSION['FieldWidth'];
    $w16 = 16 + $_SESSION['FieldWidth'];
    $w20 = 20 + $_SESSION['FieldWidth'];
    $w24 = 24 + $_SESSION['FieldWidth'];
    $w36 = 36 + $_SESSION['Fieldwidth']; 
}

?>
