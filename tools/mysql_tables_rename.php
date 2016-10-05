<?php 
/*
    This script will rename MySQL tables. It is under Public Domain license.
    
    Pavel Malakhov 2011.04.14, http://sysadminwiki.ru/wiki/MySQL_tables_rename
                   2011.06.27 Added test option, comments and form on result page
                              Changed 'replace' and 'remove' functions to see if table name needs to be changed
*/
?>
 
<html>
<head>
<title>MySQL Table Prefix Changer</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
 
<body>
<?php
// Check for POST data
$action = isset($_REQUEST['action'])?$_REQUEST['action']:false;
$mysql_server = isset($_REQUEST['server_addr'])?$_REQUEST['server_addr']:"localhost";
$mysql_db   = $_REQUEST['db_name'];
$mysql_user = $_REQUEST['db_user'];
$mysql_pass = $_REQUEST['db_pass'];
$prefix_old = $_REQUEST['prefix_old'];
$prefix_new = $_REQUEST['prefix_new'];
$test_mode  = $_REQUEST['test'];       // We are in the test mode


//-------------------------------------------------
// First run. Get variables
//-------------------------------------------------
?>
<h1 align='center'>Rename MySQL tables </h1>
<small>
<p>* Leave "OLD prefix" blank to add a new prefix to all the tables in the database.</p>
<p>* Leave "NEW prefix" blank to remove a prefix from all the tables.</p>
<p>* In both prefixes include all symbols like "_" or "-" etc. since that will be a substring to search and replace.</p>
<p>* Be carefull about spaces!</p>
<p>* Test before do!!!</p>

</small>

<form name="form1" method="post" action="mysql_tables_rename.php">
    <table width="70%" border="0" cellspacing="2" cellpadding="2">
	<tr>
	    <td width=50% align='right'>Server address:</td>
	    <td width=50%><input name="server_addr" type="text" id="server_addr" size="33" value="<?php echo $mysql_server ?>"></td>
	</tr>
	<tr>
	    <td align='right'>Database name:</td>
	    <td><input name="db_name" type="text" id="db_name" size="33" value="<?php echo $mysql_db ?>"></td>
	</tr>
	<tr>
	    <td align='right'>Database user:</td>
	    <td><input name="db_user" type="text" id="db_user" size="33" value="<?php echo $mysql_user ?>"></td>
	</tr>
	<tr>
	    <td align='right'>User password:</td>
	    <td><input name="db_pass" type="password" id="db_pass" size="33" value="<?php echo $mysql_pass ?>"></td>
	</tr>
	<tr>
	    <td align='right'>OLD prefix:</td>
	    <td><input name="prefix_old" type="text" id="prefix_old" size="33" value="<?php echo $prefix_old ?>"></td>
	</tr>
	<tr>
	    <td align='right'>NEW prefix:</td>
	    <td><input name="prefix_new" type="text" id="prefix_new" size="33" value="<?php echo $prefix_new ?>"></td>
	</tr>
	<tr>
	    <td></td>
	    <td><INPUT TYPE="checkbox" NAME="test" checked>Test before execute<br></td>
	</tr>
	<tr>
	    <td>&nbsp;</td>
	    <td><input type="submit" name="Submit" value="Rename tables">
		<input name="action" type="hidden" id="action" value="data"></td>
	</tr>
    </table>
</form>

<?php
//-------------------------------------------------
// Second run. Need to do something: Test or Rename tables
//-------------------------------------------------

if ($action) {

echo "<hr>";

if ( $test_mode ){   // We are in the test mode.
    echo "<center><strong>TEST  MODE</strong> <br />no changes to database will be made</center>";
}else{
    echo "<center><strong>PRODUCTION  MODE</strong> <br /> all changes are to be applied to the real database</center>";
}
 
// Check if any prefix specified
if ((!$prefix_old) & (!$prefix_new)){
	die('No prefix specified! Please set OLD or/and NEW prefix');
} 
 
// Connect to MySQL 
$link = mysql_connect($mysql_server, $mysql_user, $mysql_pass);
if (!$link) {
    die('Could not connect: ' . mysql_error());
}
echo "<p>Successfully connected to: <strong>$mysql_db</strong> at <strong>$mysql_server</strong> </p>";
 
 
// Select database and grab table list
mysql_select_db($mysql_db, $link) or die ("Database $mysql_db not found.");
$tables = mysql_list_tables($mysql_db);
 
 
// Pull table names into an array and replace prefixes
$i = 0;
while ($i < mysql_num_rows($tables)) {
    $table_name = mysql_tablename($tables, $i);
    $table_array[$i] = $table_name;
    $i++;
}
 
// Pull table names into another array after replacing prefixes
// See what to do: Add, Replace or Remove prefix?

// ADD prefix, if the field with old prefix is empty
if (($prefix_old=='') & ($prefix_new<>'')){
    echo "<strong> Action:</strong> ADD prefix <strong>'$prefix_new'strong> to all table names \n";
	foreach ($table_array as $key => $value) {
    	$table_names[$key] = prefix_add($value, $prefix_new);
    }
}else   // REMOVE prefix, if the field with new prefix is empty	
	if (($prefix_old<>'') & ($prefix_new=='')){ 
        echo "<strong> Action:</strong> REMOVE prefix <strong>'$prefix_old'</strong> from all table names \n";
		foreach ($table_array as $key => $value) {
    		$table_names[$key] = prefix_remove($value, $prefix_old);
    	}
}else{  // REPLACE prefix, if both fields are not empty
    echo "<strong> Action:</strong> REPLACE prefix <strong>'$prefix_old'</strong> with <strong>'$prefix_new'</strong> in every matched table name \n";
	foreach ($table_array as $key => $value) {
    	$table_names[$key] = prefix_replace($value, $prefix_old, $prefix_new);
	}
}

// Write new table names back
echo "
<DIV ALIGN=CENTER>
<TABLE CELLPADDING=0 CELLSPACING=0 RULES=COLS >
	<TR>
	    <TD><P ALIGN=center><strong>OLD</strong> table name</P></TD>
		<TD><P ALIGN=center><strong>New</strong> table name</P></TD>
	</TR>
";

$counter_tables     = 0;
$counter_no_changed = 0;
$counter_changed    = 0;
$counter_failed     = 0;

foreach ($table_array as $key => $value) {
    $old_name = $table_array[$key];
    $new_name = $table_names[$key];
    $counter_tables++;
    if ( $test_mode ){   // We are in the test mode. Just show new table names
        if ($old_name == $new_name){// don't need to rename this table
	        $message = "<tr><td>-=(no change)=-</td><td>$old_name</td></tr>\n";
	        $counter_no_changed++;
        }else{    // do rename tables
            $message = "<tr><td>". $old_name ."</td><td>". $new_name ."</td></tr>\n";
            $counter_changed++;
        }
    }else{   
        if ($old_name == $new_name){// don't need to rename this table
	        $message = "<tr><td>-=(no change)=-</td><td>$old_name</td></tr>\n";             
        }else{    // do rename tables
            $query = sprintf('RENAME TABLE %s TO %s', $old_name, $new_name);
            $result = mysql_query($query, $link);
            if (!$result) {
	            $error = mysql_error();
	            $message = "<tr><td><strong>Failed to:</strong> $query </td><td>$error</td></tr>\n";
	            $counter_failed++;
            } else {
                $message = "<tr><td>". $old_name ."</td><td>". $new_name ."</td></tr>\n";
                $counter_changed++;
            }
        }
    }
    echo "$message";    
}

echo "</TABLE>
</DIV>
<hr/>
<p></p>
<center><strong>DONE!</strong></center>
<p></p>
<p> Table renaming statistics:<br />
-=- tables        : <strong>$counter_tables</strong><br />
-=- changed       : <strong>$counter_changed</strong><br />
-=- did not change: <strong>$counter_no_changed</strong><br />
-=- failed        : <strong>$counter_failed</strong>
</p>
</BODY>
</HTML>";
 
// Free the resources
mysql_close($link);
}

//-----------------------------------
// Functions section
//-----------------------------------

function prefix_add($s, $p_new) {
    $s = $p_new.$s;
    return $s;
}

function prefix_remove($s, $p_old) {
	$pos = strpos($s, $p_old);
    if ($pos !== false) {
        $s = substr($s, $pos +  mb_strlen($p_old));
        $name_changed = true;    
    }
    return $s;
}
 
function prefix_replace($s, $p_old, $p_new) {
	$pos = strpos($s, $p_old);
    if ($pos !== false) {
        $s = substr($s, $pos + mb_strlen($p_old));
        $s = $p_new.$s;
        $name_changed = true;    
    }
    return $s;
}

?>

