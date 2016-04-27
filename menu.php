<?php
echo "<strong font-family='courier'>Subnet Organizer  - </strong>";
require 'authcheck.php';
if ( $_SESSION['Level'] >= $level_admin )
{
    echo "  -  <a href='subnet.php'><i>Main</i></a>  -  <a href='search.php'><i>Search</i></a>  -  <a href='acctmgr.php'><i>Account Manager</i></a> - <a href='loginmgr.php'><i>Login Manager</i></a>  -  <a href='backup.php'><i>Mass Data</i></a><br />";
}
else
{
    echo "  -  <a href='subnet.php'><i>Main</i></a>  -  <a href='search.php'><i>Search</i></a>  -  <a href='acctmgr.php'><i>Account List</i></a> - <a href='loginmgr.php'><i>Profile</i></a><br />";
}

?>
