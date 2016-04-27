<?php
$font = "courier new";
switch ( $_SESSION['ColorScheme'] )
{
    default:

    case 0:
	$colors = array("link-color" => "#0033FF",
	      "link-hover-color" => "#00FFFF",
	      "general-background-color" => "#00CCCC",
	      "input-background-color" => "#00FFFF",
	      "input-text-color" => "#003366",
	      "table-background-color" => "#99FFFF",
	      "table-border-color" => "#FFFFFF",
	      "text-color" => "#003366");
	break;
    case 1:
	$colors = array("link-color" => "#FFFFFF",
	      "link-hover-color" => "#FFFFFF",
	      "general-background-color" => "#0000FF",
	      "input-background-color" => "#0000BB",
	      "input-text-color" => "#FFFFFF",
	      "table-background-color" => "#0000FF",
	      "table-border-color" => "#FFFFFF",
	      "text-color" => "#FFFFFF");
	break;
    case 2:
	$colors = array("link-color" => "#DDDDDD",
	      "link-hover-color" => "#999999",
	      "general-background-color" => "#222222",
	      "input-background-color" => "#220000",
	      "input-text-color" => "#CF006F",
	      "table-background-color" => "#222222",
	      "table-border-color" => "#555555",
	      "text-color" => "#CF006F");
	break;
    case 3:
	$colors = array("link-color" => "#000000",
	      "link-hover-color" => "#666666",
	      "general-background-color" => "#CCCCCC",
	      "input-background-color" => "#AAAAAA",
	      "input-text-color" => "#000000",
	      "table-background-color" => "#CCCCCC",
	      "table-border-color" => "#000000",
	      "text-color" => "#000000");
	break;
    case 4:
	$colors = array("link-color" => "#99FF99",
	      "link-hover-color" => "#FFFFFF",
	      "general-background-color" => "#000000",
	      "input-background-color" => "#002200",
	      "input-text-color" => "#99FF99",
	      "table-background-color" => "#000000",
	      "table-border-color" => "#99FF99",
	      "text-color" => "#99FF99");
	break;
    case 5:
	$colors = array("link-color" => "#FF9900",
	      "link-hover-color" => "#FFBB33",
	      "general-background-color" => "#FFFFBB",
	      "input-background-color" => "#FF9900",
	      "input-text-color" => "#000000",
	      "table-background-color" => "#FFFF99",
	      "table-border-color" => "#CC6600",
	      "text-color" => "#000000");
	break;
    case 6:
	$colors = array("link-color" => "#33FFFF",
	      "link-hover-color" => "#33CCFF",
	      "general-background-color" => "#3300CC",
	      "input-background-color" => "#110066",
	      "input-text-color" => "#FFFFFF",
	      "table-background-color" => "#3333CC",
	      "table-border-color" => "#110066",
	      "text-color" => "#FFFFFF");
	break;
    default:
	break;

}
echo "<style media='screen' type='text/css'>

a:link {color: {$colors["link-color"]}}
a:visited {color: {$colors["link-color"]}}
a:hover {color: {$colors["link-hover-color"]}} 

table.lbox
{
    margin-left:auto; 
    margin-right:auto;
    border: 0;
}

table.main
{
    text-align:center;
    margin-left:auto; 
    margin-right:auto;
    border: 3;
    background-color:{$colors["table-background-color"]};
    border-style: solid;
    border-color: {$colors["table-border-color"]};
}

h1 
{
    color: {$colors["text-color"]};
    border: white solid thin;
}

p 
{
    color: {$colors["text-color"]};
    font-family: {$font};
}

p.center
{
    color: {$colors["text-color"]};
    font-family: {$font};
    text-align: center;
}

div.recordinput
{
    text-align: center;
    color: {$colors["text-color"]};
    font-family: {$font};
    border-style: solid;
    border-color: {$colors["table-border-color"]};
}

input,select,button
{
    color: {$colors["input-text-color"]};
    background-color: {$colors["input-background-color"]};
    font-size: 70%;
    font-style: bold;
}
textarea
{
    color: {$colors["input-text-color"]};
    background-color: {$colors["input-background-color"]};
}

input.label
{
    color: {$colors["text-color"]};
    background-color: {$colors["table-background-color"]};
    border: none;
}

table.criteria, table.result
{
    margin-left:auto; 
    margin-right:auto;
    border: 2;
    border-style: solid;
    background-color: {$colors["table-background-color"]};
    border-color: {$colors["table-border-color"]};
    text-align:center;
    font-style: bold;
}

body
{
    background-color: {$colors["general-background-color"]};
    color: {$colors["text-color"]};
    font-family: {$font};
}

</style>";
?>
