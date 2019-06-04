<?php
//-------------------------------------------------
//--- This file is to configure WikiFarm extention of MediaWiki engine.
//--- For more info please visit the extension web page:
//--- http://www.mediawiki.org/wiki/Extension:WikiFarm
//-------------------------------------------------

require_once( __DIR__ ."/WikiFarm_common.php" ); 	//include common functions and classes

// How detailed information should be. 
// 0 - little info, 
// 1 - all usefull info (show $str_info messages)
// 2 - debug info (show $str_debug messages)
//$infoLevel = 0;
//$wgWikifarmInfoLevel = 2;


// Prefix delimiter for MySQL tables
// Used only in wfarmDefineWikis() to set wiki name. 
//$wfarmPrefixDelimiter = '__';

// Set all database connection.
// $wfarmDBConnection is used for direct MySQL connections. 
// It is optional and required just in the following cases:
// 1) if table prefix = "[all]" Then we need to scan database for all wikis in it
// 2) read interwiki table, which is in admin wiki.	
// 3) Statistics module get its info directly from DB. It is going to use API in future.
// In all other cases you can skip it.
$wfarmDBConnection = array();

// The zero item (fist in array) is set to null. Used in cases
// 1) Skip DB requests for this wiki
// 2) Manual DB connection is set in wikis.json
/*
$wfarmDBConnection[2] = array(
    'server'   => '',
    'name'     => '',
    'user'     => '',
    'password' => '',
);
*/
// Temporary variable to hold one wiki parameters
$wiki_array = array();

// Set up admin wiki. It will be used to look up for interwiki links
$wiki_array = array(
    'name'   => "Admin wiki",
    'notes'  => "Wiki to look up users and interwiki, store DB tables",
    'url'    => 'https://ru.teopedia.org/main',
    'api'    => 'https://ru.teopedia.org/w-main/api.php',
    'db_id'  => '1',
    'prefix' => 'main__',
);
$wikiAdmin = new wfarmWikiInstance( $wiki_array );
        
// Create wiki list
$wikis = array();

// uncomment this to add admin wiki to the list
$wikis[] = $wikiAdmin;

// Configure a new wiki
/*
$wiki_array = array(
    'url'    => 'https://sysadminwiki.ru',
    'api'    => 'https://sysadminwiki.ru',
    'db_id'  => '2',
    'prefix' => '[all]',
);
*/
// and add it to the list
//$wikis[] = new wfarmWikiInstance( $wiki_array );

?>
