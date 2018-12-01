<?php

class WikiFarmLinks extends SpecialPage {

function __construct() {
	    parent::__construct( 'WikiFarmLinks' );
	    wfLoadExtensionMessages('WikiFarmLinks');
}

function execute( $par ) {
	global $wgOut, $IP;

    require_once( "$IP/extensions/WikiFarm/WikiFarm_config.php" ); 	//read config
    require_once( "$IP/extensions/WikiFarm/WikiFarm_common.php" );	//include some common variables
   	require_once( "$IP/extensions/WikiFarm/tools/botclasses.php" ); //include bot functions

	$wgOut->addWikiText( wfarmHeader('links') );	// show WikiFarm links
    //$wgOut->addWikiText( wfarmFormatTitle( wfMsg( 'wikifarm_links' ) ) );	// page title
	$wikis = wfarmDefineWikis( $wikis, $wikiAdmin, $infoLevel, $wfarmPrefixDelimiter );	//define wikis to work with
	$wikiCounter = count($wikis);

    $tableHeader = "\n{| class=\"wikitable sortable\" width=100%" .
			"\n|+ <big>". wfMsg( 'wikifarm_wiki_list' ) ."</big> (". $wikiCounter .")".
			"\n|- ".
			"\n! ". wfMsg( 'wikifarm_wiki_name' ) .
			"\n! ". wfMsg( 'wikifarm_links' ).
			"\n";
	$newRecord    = "\n|- valign=top";
	$tableRecords = '';
	$tableFooter  = "\n|}\n";
	$fields = array();	//table fields
	foreach( $wikis as $wiki ){
		//$str_Interwiki = wfarmWikiLink($wiki, '', wfMsg( 'wikifarm_interwiki_link' )); //works but does not fit, because of format
		$str_Interwiki= "[[:$wiki->interwikiLink:|". wfMsg( 'wikifarm_interwiki_link' ) ."]] <nowiki>|</nowiki> ";
		if ( empty($wiki->interwikiLink) )
			$str_Interwiki = "<span style=\"color: grey;\">". wfarmHintUnhilited( wfMsg( 'wikifarm_interwiki_link' ), wfMsg( 'wikifarm_interwiki_link_absent' ) ) ."</span> <nowiki>|</nowiki> ";
		$fields['wikiName'] = "\n| $wiki->name";
		$fields['links']	= "\n| <small> ".
			"\n* '''". wfMsg('wikifarm_wiki') .":''' ".
				$str_Interwiki .
				wfarmWikiLink( $wiki, ":Special:Statistics", wfMsg('wikifarm_statistics') ) ." <nowiki>|</nowiki> ".
				$wiki->url .
			"\n* '''". wfMsg('wikifarm_engine') .":''' ".
				wfarmHintUnhilited(wfarmWikiLink( $wiki, ":Special:Version", wfMsg('version-software-version')), wfMsg('version') ) ." <nowiki>|</nowiki> ".
				wfarmWikiLink( $wiki, ":Special:Log", wfMsg('log') ) ." <nowiki>|</nowiki> ".
				wfarmWikiLink( $wiki, ":Special:SpecialPages", wfMsg('specialpages') ) ." <nowiki>|</nowiki> ".
				wfarmWikiLink( $wiki, ":Special:AllMessages", wfMsg('allmessages') ) ." <nowiki>|</nowiki> ".
				wfarmWikiLink( $wiki, ":Special:MediaWiki:Common.css", "CSS" ) ." <nowiki>|</nowiki> ".
				wfarmWikiLink( $wiki, ":Special:MediaWiki:Mainpage", wfMsg('mainpage') ) ." <nowiki>|</nowiki> ".
			"\n* '''". wfMsg('revdelete-content') .":''' ".
				wfarmWikiLink( $wiki, ":Special:Allpages", wfMsg('wikifarm_pages') ) ." <nowiki>|</nowiki> ".
				wfarmWikiLink( $wiki, ":Special:Categories", wfMsg('categories') ) ." <nowiki>|</nowiki> ".
				wfarmWikiLink( $wiki, ":Special:RecentChanges", wfMsg('wikifarm_files') ) ." <nowiki>|</nowiki> ".
				wfarmWikiLink( $wiki, ":Special:Sidebar", wfMsg('wikifarm_sidebar') ) ." <nowiki>|</nowiki> ".
				wfarmWikiLink( $wiki, ":Special:Search", wfMsg('search') ) ." <nowiki>|</nowiki> ".
			"\n* '''". wfMsg('group-user') .":''' ".
				wfarmWikiLink( $wiki, ":Special:ListUsers", wfMsg('group-user') ) ." <nowiki>|</nowiki> ".
				wfarmWikiLink( $wiki, ":Special:UserLogin", wfMsg('nologinlink') ) ." <nowiki>|</nowiki> ".
				wfarmWikiLink( $wiki, ":Special:UserRights", wfMsg('listgrouprights-rights') ) ." <nowiki>|</nowiki> ".
			"</small>\n";

        $tableRecords .= $newRecord . $fields['wikiName'] . $fields['links'];
    }
    $wgOut->addWikiText( $tableHeader . $tableRecords . $tableFooter );
	$wgOut->addWikiText( wfarmFooter() );	// show WikiFarm footer
} // function execute( $par )

} //class WikiFarmLinks

