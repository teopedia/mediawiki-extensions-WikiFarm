<?php

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
	$delimiter = " '''<nowiki>|</nowiki>''' ";
	$fields = array();
	$fields['title'] = "\n! style=\"background: #e6e6ff; width: 20px\" | [[Special:WikiFarm|" . wfMsgHtml('wikifarm_name') . "]]";
	$fields['time']	 = "\n| style=\"background: #e6e6ff; width: 100px\" | <center><small>". date('Y.m.d') ."</small> '''". date('H:i:s') ."'''</center>";
	$fields['links']  = "\n|<center>";

	$links['statistics']= "[[Special:WikiFarmStatistics|" . wfMsgHtml('wikifarm_statistics') . "]]";
	$links['links']		= "[[Special:WikiFarmLinks|" . wfMsgHtml('wikifarm_links') . "]]";
	$links['changes']	= "[[Special:WikiFarmChanges|" . wfMsgHtml('wikifarm_changes') . "]]";
	$links['pages']		= "[[Special:WikiFarmPages|" . wfMsgHtml('wikifarm_pages') . "]]";
	$links['users']		= "[[Special:WikiFarmUsers|" . wfMsgHtml('wikifarm_users') . "]]";
	$links['search']	= "[[Special:WikiFarmSearch|" . wfMsgHtml('wikifarm_search') . "]]";
	$links['log']		= "[[Special:WikiFarmLog|" . wfMsgHtml('wikifarm_log') . "]]";
	if ( !empty($module) ) {
		$links[$module]	= "<big>'''". $links[$module] ."'''</big>";
	}
	if ( defined('WIKIFARM_STATISTICS_VERSION') ) $fields['links'] .= $delimiter . $links['statistics'];
	if ( defined('WIKIFARM_LINKS_VERSION') ) 	$fields['links'] .= $delimiter . $links['links'];
	if ( defined('WIKIFARM_CHANGES_VERSION') ) 	$fields['links'] .= $delimiter . $links['changes'];
	if ( defined('WIKIFARM_PAGES_VERSION') ) 	$fields['links'] .= $delimiter . $links['pages'];
	if ( defined('WIKIFARM_USERS_VERSION') ) 	$fields['links'] .= $delimiter . $links['users'];
	if ( defined('WIKIFARM_SEARCH_VERSION') ) 	$fields['links'] .= $delimiter . $links['search'];
	if ( defined('WIKIFARM_LOG_VERSION') )		$fields['links'] .= $delimiter . $links['log'];
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
	$fields['title'] = "\n! style=\"background: #e6e6ff; \" | [[Special:SpecialPages|" . wfMsgHtml('wikifarm_admin_special_pages') . "]]";
	$fields['time']	 = "\n| style=\"background: #e6e6ff; width: 100px\" | <center><small>". date('Y.m.d') ."</small> '''". date('H:i:s') ."'''</center>";

	$links['version'] = $delimiter . "[[Special:Version|" . wfMsgHtml('wikifarm_version') . "]]";
	$links['pages']	  = $delimiter . "[[Special:AllPages|" . wfMsgHtml('wikifarm_pages') . "]]";
	$links['files']   = $delimiter . "[[Special:ListFiles|" . wfMsgHtml('wikifarm_files') . "]]";
	$links['users']   = $delimiter . "[[Special:ListUsers|" . wfMsgHtml('wikifarm_users') . "]]";
	$links['rights']  = $delimiter . "[[Special:UserRights|" . wfMsgHtml('wikifarm_user_rights') . "]]";
	$links['interwiki'] = $delimiter . "[[Special:Interwiki|" . wfMsgHtml('wikifarm_interwiki') . "]]";

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
 * Process all wikis in configuration. If there is a wiki with '[all]' parameter, then query database for all wikis in it and add to $wikis array
 *
 * @param array  $wikis 			wiki array
 * @param string $level_info 		how verbous info should be (0,1,2)
 * @param string $prefixDelimiter	what delimiters prefix from table name
 * @return array Array of wfarmWikiInstance objects
 *
 * @todo Make all variables global and do not pass to the function.
 */
//TODO: make MySQL table for wikis and update it on config file change or demand.
// 		For the last option: put links in wiki table on Statistics page.
//		Links point to php script which after update redirects back to Statistics.
//		URL: wikifarm_update.php?action=update_wikilist
//		other actions: update_wikicounter_pages, update_wikicounter_categories, update_wikicounter_interwiki, update_wikicounter_all
function wfarmDefineWikis($wikis, $wikiAdmin, $infoLevel = 0, $prefixDelimiter = "__") {
	global $IP, $wgOut;
//	require_once( "$IP/extensions/WikiFarm/WikiFarm_config.php" ); //TODO: Does not get variables from config
//	$wgOut->addWikiText("* wikiAdmin: ". $wikiAdmin->url);
//	$prefixDelimiter = $wfarmPrefixDelimiter;
//	$wikis = $wfarmWikis;
	// show all passed wiki array
	if ($infoLevel > 1) {
		$str_debug = "<big>'''Wiki list in configuration file:'''</big>\n{| class=\"wikitable sortable\"\n! URL || DB name || Table prefix\n ";
		foreach ($wikis as $wiki) {
			$str_debug = $str_debug . "|-\n| " . $wiki->url . " || " . $wiki->db['name'] . " || " . $wiki->prefix . "\n";
		}
		$wgOut->addWikiText($str_debug . "|}\n\n\n<big>'''Check if there are wikis with '[all]' parameter:'''</big>");
	}

	// check if there are wikis with '[all]' parameter. Those records we need to unfold to get all wikis
	$str_debug = "{| class=\"wikitable sortable\"\n! URL || Table prefix\n";
	foreach ($wikis as $wiki_index => $wiki) {
		if ($wiki->prefix == '[all]') {	  //scan db for all wiki prefixes
			$db_connection = mysql_connect($wiki->db['server'], $wiki->db['user'], $wiki->db['password'])
					or die("Could not connect to server: $wiki->db['server']");
			try {
				mysql_select_db($wiki->db['name'])
						or die("Could not select database: $wiki->db['name']");
				$tables = mysql_list_tables($wiki->db['name']);
				if ($infoLevel > 0) { //show basic info about wikis found
					$wgOut->addWikiText("Scan '''" . $wiki->db['name'] . "''' database for wikis\n* number of tables: '''" . mysql_num_rows($tables) . "'''");
				}
				//find all 'interwiki' tables (can be any table name) and get prefix from table name
				$wiki_counter = 0;
				$i = 0;
				while ($i < mysql_num_rows($tables)) {
					$table_name = mysql_tablename($tables, $i);
					$pos = strpos($table_name, 'interwiki');
					if ($pos > 0) {
						$t_prefix = substr($table_name, 0, $pos);
						//Set wiki name = table_prefix - prefix_delimiter
						$wiki->name = substr($t_prefix, 0, mb_strlen($t_prefix) - mb_strlen($prefixDelimiter));
						$wiki_url = $wiki->url . "/" . $wiki->name;
						$wiki_api = $wiki->api . "/w-" . $wiki->name . "/api.php";
						$wikis[] = new wfarmWikiInstance(
										$wiki_url,
										$wiki_api,
										$wiki->db,
										$t_prefix,
										$wiki->user,
										$wiki->pass,
										$wiki->common_name
						);
						$wiki_counter++;
						$str_debug = $str_debug . "|-\n| " . $wiki_url . " || " . $t_prefix . "\n";
					}
					$i++;
				}
				if ($infoLevel > 0) {
					$wgOut->addWikiText("\n* wikis found: '''$wiki_counter'''");
				}
				if ($infoLevel > 1) {   //show verbous wikis info
					$wgOut->addWikiText($str_debug . "|}");
				}
			} catch (Exception $e) {
				print "<BR/>Exception occured: $e->getMessage()<BR/>";
				mysql_close($db_connection);
			}
		}
	}   // [all] parameter handled
	// Clean wikis array: remove processed wikis with [all] prefix
	// Set additional info for each wiki
	foreach ($wikis as $i => $wiki) {
		if ($wiki->prefix == '[all]') {
			unset($wikis[$i]);
		} else {
			// TODO: read wiki name from additional table. For now make it from table prefix.
			if (empty($wiki->name))
				$wiki->name = substr($wiki->prefix, 0, mb_strlen($wiki->prefix) - mb_strlen($prefixDelimiter));
			// TODO: move here interwiki lookup from WikiFarmStatistics_body.php
		}
		$wiki_index++;
	}
	if ($infoLevel > 1) {
		$wgOut->addWikiText("\n\n\n<big>'''Resulting wiki list:'''</big>\n* Total wikis in the project: '''" . count($wikis) . "'''");
		$str_debug = "{| class=\"wikitable sortable\"\n! URL || wiki API || Table prefix\n";
		foreach ($wikis as $wiki) {
			$str_debug = $str_debug . "|-\n| " . $wiki->url . " || " . $wiki->api . " || " . $wiki->prefix . "\n";
		}
		$wgOut->addWikiText($str_debug . "|}");
	}

	//-------------------------------------------------
	//------- Get wiki name and interwiki link for every wiki via bot -------
	//-------------------------------------------------
	require_once( "$IP/extensions/WikiFarm/tools/botclasses.php" ); //include bot functions
	//$wgOut->addWikiText("* wikiAdmin2: ". $wikiAdmin->url);
	// get interwiki list
	$bot = new extended($wikiAdmin->api);
	if (!empty($wikiAdmin->user)) {
		$bot->login($wikiAdmin->user, $wikiAdmin->pass);
	}
	$interwikiList = $bot->interwikilist();
	$interwikiCounter = count($interwikiList);

	//print_r($interwikiList);

	foreach ($wikis as $wiki) {
		$bot = new extended($wiki->api);
		if (!empty($wiki->user)) {
			$bot->login($wiki->user, $wiki->pass);
		}

		// get sitename
		$wikiInfo = $bot->siteinfo();
		if (!empty($wikiInfo['sitename'])) {
			//substract common part (f.e. for farm) from wiki name
			$pos = strpos($wikiInfo['sitename'], $wiki->common_name);
			if ($pos !== false) {
				$str = substr_replace($wikiInfo['sitename'], '', $pos, strlen($wiki->common_name));
			} else {
				$str = $wikiInfo['sitename'];
			}
			$wiki->name = $str;
		}

		// get interwiki link if exist
		foreach ($interwikiList as $iwiki) {
			$pos = strpos($iwiki['url'], $wiki->url);
			if ($pos !== false) {
				$tmp = $iwiki['prefix'];
				$wiki->interwikiLink = $iwiki['prefix'];
				break;
			}
		}
		//$wgOut->addWikiText("* wiki name: ". $wiki->name ."; interwiki: " . $wiki->interwikiLink);
	}
	return $wikis;
}//wfarmDefineWikis( $wikis, $infoLevel )

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
		$datetime = now();	//TODO: now() does not work
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
