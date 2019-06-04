<?php
use MediaWiki\MediaWikiServices;    //  for config

//-------------------------------------------------
//------- Functions
//-------------------------------------------------

/**
 * Creates a header for the WikiFarm modules.
 * Header consists of links to active modules.
 *
 * @param string $module WikiFarm module name to highlight. Optional.
 *		Possible values: statistics, links, changes, pages, users, search, log
 */
function wfarmHeader($module = '') {
    $config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'wikifarm' );
    
    $delimiter = " '''<nowiki>|</nowiki>''' ";      // delimiter for menu items
    $fields = array();                              // columns in header
    $fields['title']= "\n! style=\"background: #e6e6ff; width: 20px\" | [[Special:WikiFarm|" . 
        wfMessage( 'wikifarm_name' )->escaped() . ']]' .
                '<div style="font-size: 80%; text-align: left;">' . 
                '<span title="'. $config->get( "WikifarmBuild" ) .'">v. '. $config->get( "WikifarmVersion" ) .'</span>, ' .
                '<span title="Info level">level '. $config->get( "WikifarmInfoLevel" ) .'</span>' .
                '</div>';
    $fields['time'] = "\n| style=\"background: #e6e6ff; width: 100px\" | <center><small>". date('Y.m.d') ."</small> '''". date('H:i:s') ."'''</center>";
    $fields['links']= "\n|<center>";

    // links in navigation bar for modules
    $links['main']      = "[[Special:WikiFarm|" . wfMessage( 'wikifarm_main' )->escaped() . "]]";
    $links['statistics']= "[[Special:WikiFarm/Statistics|" . wfMessage( 'wikifarm_statistics' )->escaped() . "]]";
    $links['links']	= "[[Special:WikiFarm/Links|" . wfMessage( 'wikifarm_links' )->escaped() . "]]";
    $links['changes']	= "[[Special:WikiFarm/Changes|" . wfMessage( 'wikifarm_changes' )->escaped() . "]]";
    $links['pages']	= "[[Special:WikiFarm/Pages|" . wfMessage( 'wikifarm_pages' )->escaped() . "]]";
    $links['users']	= "[[Special:WikiFarm/Users|" . wfMessage( 'wikifarm_users' )->escaped() . "]]";
    $links['search']	= "[[Special:WikiFarm/Search|" . wfMessage( 'wikifarm_search' )->escaped() . "]]";
    $links['log']	= "[[Special:WikiFarm/Log|" . wfMessage( 'wikifarm_log' )->escaped() . "]]";
    if ( !empty($module) ) {
        $links[$module]	= "<big>'''". $links[$module] ."'''</big>";
    }
    
    // navigation bar; check if the module is switched on
    $fields['links'] .= $links['main'];
    if ( $config->get( "WikifarmModuleStatistics" ) )   {$fields['links'] .= $delimiter . $links['statistics'];}
    if ( $config->get( "WikifarmModuleLinks" ) )        {$fields['links'] .= $delimiter . $links['links'];}
    if ( $config->get( "WikifarmModuleChanges" ) )      {$fields['links'] .= $delimiter . $links['changes'];}
    if ( $config->get( "WikifarmModulePages" ) )        {$fields['links'] .= $delimiter . $links['pages'];}
    if ( $config->get( "WikifarmModuleUsers" ) )        {$fields['links'] .= $delimiter . $links['users'];}
    if ( $config->get( "WikifarmModuleSearch" ) )       {$fields['links'] .= $delimiter . $links['search'];}
    if ( $config->get( "WikifarmModuleLog" ) )          {$fields['links'] .= $delimiter . $links['log'];}
    $fields['links'] .= $delimiter ."</center>";

    $tableHeader = "{| style=\"border: 1px dotted #000000;\" width=100%";
    $tableFooter = "\n|}";
    $tableRow = $fields['title'] . $fields['links'] . $fields['time'];
    $returnString = $tableHeader . $tableRow . $tableFooter;
    return $returnString;
}//wfarmHeader()

/**
 * Creates a footer for the WikiFarm modules.
 * Footer consists of links for Special page in admin wiki.
 */
function wfarmFooter() {
    $delimiter = " '''<nowiki>|</nowiki>''' ";
    $fields = array();
    $fields['title']= "\n! style=\"background: #e6e6ff; \" | [[Special:SpecialPages|" . wfMessage( 'wikifarm_admin_special_pages' )->escaped() . "]]";
    $fields['time'] = "\n| style=\"background: #e6e6ff; width: 100px\" | <center><small>". date('Y.m.d') ."</small> '''". date('H:i:s') ."'''</center>";

    $links['version']   = $delimiter . "[[Special:Version|" . wfMessage( 'wikifarm_version' )->escaped() . "]]";
    $links['pages']     = $delimiter . "[[Special:AllPages|" . wfMessage( 'wikifarm_pages' )->escaped() . "]]";
    $links['files']     = $delimiter . "[[Special:ListFiles|" . wfMessage( 'wikifarm_files' )->escaped() . "]]";
    $links['users']     = $delimiter . "[[Special:ListUsers|" . wfMessage( 'wikifarm_users' )->escaped() . "]]";
    $links['rights']    = $delimiter . "[[Special:UserRights|" . wfMessage( 'wikifarm_user_rights' )->escaped() . "]]";
    $links['interwiki'] = $delimiter . "[[Special:Interwiki|" . wfMessage( 'wikifarm_interwiki' )->escaped() . "]]";

    $fields['links'] = "\n|<center>";
    $fields['links'] .= $links['version'] . $links['pages'] . $links['files'] .	$links['users'] .
                    $links['rights'] . $links['interwiki'];
    $fields['links'] .= $delimiter ."</center>";

    $tableHeader = "{| style=\"border: 1px dotted #000000;\" width=100%";
    $tableFooter = "\n|}";
    $tableRow = $fields['title'] . $fields['links'] . $fields['time'];

    $returnString = $tableHeader . $tableRow . $tableFooter;
    return $returnString;
}//wfarmHeader()


/**
 * Return formated string for titles
 * @param $title Title string
 */
function wfarmFormatTitle($title) {
	$str = "<div align=center><big><big>'''" . $title . "'''</big></big></div>";
	return $str;
}//wfarmFormatTitle( $title )

/**
 * Returns formated date and time string
 * @param $datetime DateTime class string
 * @return string
 */
// more info http://www.php.net/manual/en/datetime.createfromformat.php
function wfarmFormatDate($datetime='') {
	if (empty($datetime))
		$datetime = now();	//TO-DO: now() does not work
	$date = date('Y.m.d', strtotime($datetime));
	$time = date('H:i', strtotime($datetime));
	$str = $date ."&nbsp;&nbsp;&nbsp;<small>". $time . "</small>";
	return $str;
}

/**
 * Make connection to DB and return a database object. Write debug info.
 * @param $wiki A wfarmWikiInstance object.
 * @param $debugMessage Some leading words for debug message to help to find it in log file.
 */
function wfarmNewDB($wiki, $debugMessage = "") {
	//global $IP;
	//require_once( "$IP/extensions/WikiFarm/tools/botclasses.php" );
	//connect to DB
	$host = $wiki->db['server'];
	$dbname = $wiki->db['name'];
	$user = $wiki->db['user'];
	$pass = $wiki->db['password'];
	wfDebug($debugMessage . ": Connecting to DB: $dbname LOGIN: $user:$pass@$host ...\n");
	$db = new DatabaseMysql();
	if ($db->Open($wiki->db['server'], $wiki->db['user'], $wiki->db['password'], $wiki->db['name'])) {
		wfDebug($debugMessage . ": Connected to: $dbname.\n");
		return $db;
	} else {
		wfDebug($debugMessage . ": Connection failed to: $dbname.\n");
		return null;
	}
	return $db;
}

/**
 * Make a hint string, which will pop up on mouse cursor over the text
 * @param $str_show String to show.
 * @param $hit Pop up text.
 * @return string
 */
function wfarmHint($str_show, $hint) {
	return "<span style=\"border-bottom:1px dotted gray; cursor:help\" title=\"$hint\">$str_show</span>";
}

/**
 * Make a hint string, which will pop up on mouse cursor over the text
 * @param $str_show String to show.
 * @param $hit Pop up text.
 * @return string
 */
function wfarmHintUnhilited($str_show, $hint) {
	return "<span title=\"$hint\">$str_show</span>";
}

/**
 * NOT READY YET!!!
 * Writes log to wikipage. If page does not exist, it creates it and puts in Category:Log
 * @param $str String to write
 * @param $summary Comment for post
 * @param $logWiki Wiki to write log to
 * @param $logPage Name of log page
 * @return true if log successfull or false if not. TODO: return string with error if could not write a log.
 */
function wfarmLog($str, $summary, $logWiki, $logPage) {
	//TODO: Make $logPage a global variable, logWiki = wikiAdmin;
	/*
	  $bot = new extended( $logWiki->api );
	  if ( !empty($logWiki->user) ) {
	  $bot->login( $logWiki->user, $logWiki->pass );
	  }
	  bot->addtext($logPage, $str, $summary);
	 */
	return $str;
}

/**
 * Returns link as interwiki or external URL, depending on if the wiki is in the interwiki table.
 * @param $wiki A wfarmWikiInstance object
 * @param $link Page name
 * @param $reference Link text
 * @param $alt_ref Alernative reference. Use this string if $reference is empty. Optional. Default=''.
 * @return String
 */
function wfarmWikiLink($wiki, $link, $reference, $alt_ref = '') {
	if (empty($reference))
		$reference = $alt_ref;
	if (empty($wiki->interwikiLink)) {
		$str = "[$wiki->url/". wiki_url_encode($link) ." $reference]";
	} else {
		$str = "[[:$wiki->interwikiLink:$link|$reference]]";
	}
	return $str;
}

/**
 * Returns encoded string (for example URL) with spaces replaced with underscore "_"
 * @param $str string to encode
 * @return string
 */
function wiki_url_encode($str) {
	$ret = str_replace(" ", "_", $str);
	return urlencode($ret);
}

/**
 * Creates summary table for wikis with counters and individual links for each wiki Special page.
 *
 * @param $wikis instance of class wfarmWikiInstance
 * @param $counterIndex Which index in variable array (arr[]) use as a counter
 * @param $link Special page link. Optional
 * @return table as string in wiki format
 *
 * @link http://www.php.net/manual/en/datetime.createfromformat.php description
 */
function wfarmSummary($wikis, $counterIndex, $link='') {
	$str_summary = '';
	$summary = 0;
	foreach ($wikis as $wiki) {
		$counter = $wiki->arr[$counterIndex];
		$summary += $counter;
		if ($counter > 0) {
			$str_summary .= "| '''[" . $wiki->url . $link ." ". $wiki->name . "''' (" . $counter . ")] ";
		} else {
			$str_summary .= "| [" . $wiki->url . $link ." <span style=\"color: grey;\">" . $wiki->name . "</span>] <span style=\"color: grey;\">(" . $counter . ")</span> ";
		}
	}
	$str_summary = "{| class=wikitable align=center" .
			"\n|-" .
			"\n! " . wfMsg('wikifarm_summary') . ": " . $summary .
			"\n| " . $str_summary .
			"\n|}\n";
	return $str_summary;
}
?>
