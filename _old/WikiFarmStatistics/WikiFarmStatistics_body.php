<?php

class WikiFarmStatistics extends SpecialPage {

function __construct() {
	    parent::__construct( 'WikiFarmStatistics' );
	    wfLoadExtensionMessages('WikiFarmStatistics');
}

function execute( $par ) {
	global $wgOut, $IP;
	require_once( "$IP/extensions/WikiFarm/WikiFarm_config.php" ); 	//read config
	require_once( "$IP/extensions/WikiFarm/WikiFarm_common.php" ); 	//include common functions
   	require_once( "$IP/extensions/WikiFarm/tools/botclasses.php" ); //include bot functions

	$wgOut->addWikiText( wfarmHeader('statistics') );			    //show WikiFarm links
	//$wgOut->addWikiText( wfarmFormatTitle( wfMsg( 'wikifarm_statistics' ) ) );	//page title
	$wikis = wfarmDefineWikis( $wikis, $wikiAdmin, $infoLevel, $wfarmPrefixDelimiter );	//define wikis to work with
	$wikiCounter = count($wikis);

    //-------------------------------------------------
    //------- Get info about every wiki -------
    //-------------------------------------------------
	foreach( $wikis as $wiki ){
		$bot = new extended( $wiki->api );
		if ( !empty($wiki->user) ) {
			$bot->login( $wiki->user, $wiki->pass );
		}

		$wikiInfo = $bot->sitestatistics();
		$wiki->arr['pages'] 	= $wikiInfo['pages'];
		$wiki->arr['articles']	= $wikiInfo['articles'];
		$wiki->arr['views'] 	= $wikiInfo['views'];
		$wiki->arr['edits'] 	= $wikiInfo['edits'];
		$wiki->arr['images'] 	= $wikiInfo['images'];
		$wiki->arr['users'] 	= $wikiInfo['users'];

		$wikiInfo = $bot->allcategories();
		$wiki->arr['categories']	= count($wikiInfo);

		$wikiInfo = $bot->alltemplates();
		$wiki->arr['templates']	= count($wikiInfo);
    }

    //-------------------------------------------------
    //------- All data is collected. Show it -------
    //-------------------------------------------------

    $tableHeader = "\n<div align=center>".
		"\n{| class=\"wikitable sortable\" " .
		"\n|+ <big>". wfMsg( 'wikifarm_wiki_list' ) ."</big> (". $wikiCounter .")".
		"\n|-".
		"\n! ". wfMsg( 'wikifarm_wiki_name' ) .
		"\n! ". wfMsg( 'wikifarm_pages' ) .
		"\n! ". wfMsg( 'wikifarm_articles' ) .
		"\n! ". wfMsg( 'wikifarm_views' ) .
		"\n! ". wfMsg( 'wikifarm_edits' ) .
		"\n! ". wfMsg( 'statistics-files' ) .
		"\n! ". wfMsg( 'group-user' ) .
		"\n! ". wfMsg( 'wikifarm_categories' ) .
		"\n! ". wfMsg( 'wikifarm_templates' );
	if ($infoLevel > 0){	$tableHeader .=
		"\n! ". wfMsg( 'wikifarm_table_prefix' ) .
		"\n! ". wfMsg( 'wikifarm_database' ) .
		"\n! ". wfMsg( 'wikifarm_server' );
	}
	$newRecord    = "\n|- valign=top align=right";
	$tableRecords = '';
	$tableFooter  = "\n|}\n</div>";
	$fields = array();	//table fields
    foreach( $wikis as $wiki ){
		$fields['wikiName']		= "\n| align=left| ". wfarmWikiLink( $wiki, ":Special:Statistics", $wiki->name, "-" );
		$fields['pages']		= "\n| ". wfarmWikiLink( $wiki, ":Special:AllPages", $wiki->arr['pages'], "-" );
		$fields['articles']		= "\n| ". $wiki->arr['articles'];
		$fields['views']		= "\n| ". $wiki->arr['views'];
		$fields['edits']		= "\n| ". $wiki->arr['edits'];
		$fields['images']		= "\n| ". wfarmWikiLink( $wiki, ":Special:FileList", $wiki->arr['images'], "-" );
		$fields['users']		= "\n| ". wfarmWikiLink( $wiki, ":Special:ListUsers", $wiki->arr['users'], "-" );
		$fields['categories']	= "\n| ". wfarmWikiLink( $wiki, ":Special:Categories", $wiki->arr['categories'], "-" );
		$fields['templates']	= "\n| ". $wiki->arr['templates'];
		$fields['wikiPrefix']	= "\n| ". $wiki->prefix;
		$fields['dbName']		= "\n| ". $wiki->db['name'];
		$fields['dbServer']		= "\n| ". $wiki->db['server'];

		$tableRecords .= $newRecord . $fields['wikiName'] . $fields['pages'] . $fields['articles'] .
				$fields['views'] . $fields['edits'] . $fields['images'] . $fields['users'] .
				$fields['categories'] . $fields['templates'];
		if ($infoLevel > 0){
			$tableRecords .= $fields['wikiPrefix'] . $fields['dbName'] . $fields['dbServer'];
		}
	}
    $wgOut->addWikiText( $tableHeader . $tableRecords . $tableFooter );
    $wgOut->addWikiText( wfarmFooter() );	 // show WikiFarm footer
} // function execute( $par )

} //class WikiFarmStatistics
