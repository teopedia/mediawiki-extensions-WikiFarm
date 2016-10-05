<?php
//-------------------------------------------------
//--- This file is to configure WikiFarm extention of MediaWiki engine.
//--- For more info please visit the extension web page:
//--- http://www.mediawiki.org/wiki/Extension:WikiFarm
//-------------------------------------------------

// Get global variables
global $wgDBserver, $wgDBname, $wgDBuser, $wgDBpassword, $wgSharedPrefix;

//-------------------------------------------------
//------- Variables
//-------------------------------------------------

// How detailed information should be. 
// 0 - little info, 
// 1 - all usefull info (show $str_info messages)
// 2 - debug info (show $str_debug messages)
$infoLevel = 0;

// Prefix delimiter for MySQL tables
// Used only in wfarmDefineWikis() to set wiki name. 
// It's going to be depricated in WikiFarm 3.0 when MySQL will be used to store some parametes
$wfarmPrefixDelimiter = '__';

// Set all db connection
// $wfarmDBConnection is used for direct MySQL connections and required just in following cases:
// 1) if table prefix = "[all]" Then we need to scan database for all wikis in it
// 2) read interwiki table, which is in admin wiki.	
// 3) Statistics module get its info directly from DB. It is going to use API in future.
// In all other cases you can pass $wfarmDBConnection[0]
$wfarmDBConnection = array();

//The first item is a default connection
$wfarmDBConnection[0] = array(
	    'server'   => $wgDBserver,
	    'name'     => $wgDBname,
	    'user'     => $wgDBuser,
	    'password' => $wgDBpassword,
);


/*
$wfarmDBConnection[] = array(
	    'server'   => '',
	    'name'     => '',
	    'user'     => '',
	    'password' => '',
);
*/

// Set admin wiki. It will be looked up for interwiki links
$wikiAdmin = new wfarmWikiInstance(
    'http://wiki.domain.name/main',   			// URL to wiki page
    'http://wiki.domain.name/w-main/api.php',   // MediaWiki API end point for admin wiki.
    $wfarmDBConnection[0],     					// db connection, see above
	'main__',	               					// prefix for tables (interwiki, users). It may be $wgSharedPrefix
	'',											// Bot name. Leave it empty to annonimous queries
	''											// Bot pass
);

// Set all wikis
$wikis = array();

// uncomment this to add admin wiki to the list
//$wikis[] = $wikiAdmin;

// Add wiki to the list
$wikis[] = new wfarmWikiInstance(
    'http://wiki.domain.name',  // url to wiki page
    'http://wiki.domain.name',  // MediaWiki API end point. If prefix is [all], leave it the same as URL.
    $wfarmDBConnection[0], 		// db connection, see above
	'[all]',	           		// table prefix. Set it to "[all]" to get all wikis in the database. DB will be scanned for wikis in it. Direct MySQL query will be run.
	'',							// Bot name. Leave it empty to annonimous queries
	''							// Bot pass
	''							// Common string in all wiki names. It will be substucted. Optional.
);

/*
// Add second wiki to the list
$wikis[] = new wfarmWikiInstance(
    'http://wiki2.domain.name',  // url to wiki page
    'http://wiki2.domain.name',  // MediaWiki API end point. If prefix is [all], leave it the same as URL.
    $wfarmDBConnection[1], 		// db connection, see above
	'wiki__',	           		// table prefix. Set it to "[all]" to get all wikis in the database. DB will be scanned for wikis in it
	'',							// Bot name. Leave it empty to annonimous queries
	''							// Bot pass
	''							// Common string in all wiki names. It will be substucted. Optional.
);
*/

//-------------------------------------------------
//------- Classes
//-------------------------------------------------

/**
 * Holds wiki parameters
 *
 */
class wfarmWikiInstance	{
	    public $url;				// URL address of wiki
	    public $api;				// URL to API
	    public $db;					// database connection parameters. See $wfarmDBConnection array
	    public $prefix;				// table prefix in DB for the wiki
		public $user;				// bot user name
		public $pass;				// bot user password
		public $interwikiLink;		// interwiki link, if exist in interwiki table
		public $name;				// full wiki name as set in
		public $common_name;		// part of wiki name to substract to display in reports
		public $arr;				// array for additional variables

		function __construct( $url, $api, $db, $table_prefix, $bot_user='', $bot_pass='', $common_name=''){
	        $this->url          = $url;
	        $this->api          = $api;
			$this->db           = $db;
			$this->prefix		= $table_prefix;
			$this->user			= $bot_user;
			$this->pass			= $bot_pass;
			$this->common_name	= $common_name;
			$this->interwikiLink = '';
			$this->name = '';
			$this->arr = array();
		}
} //wfarmWikiInstance

?>
