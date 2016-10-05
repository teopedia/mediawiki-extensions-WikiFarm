<?php

class SpecialWikiFarm extends SpecialPage {

function __construct() {
	    parent::__construct( 'WikiFarm' );
	    wfLoadExtensionMessages('WikiFarm');
}

function execute( $par ) {
	global $IP, $wgOut;
    require_once( "$IP/extensions/WikiFarm/WikiFarm_config.php" ); 	//read config
    require_once( "$IP/extensions/WikiFarm/WikiFarm_common.php" ); 	//include common functions

    $wgOut->addWikiText( wfarmHeader() );			//show WikiFarm header

	$tableRows  = moduleInfo('statistics');
	$tableRows .= moduleInfo('links');
	$tableRows .= moduleInfo('changes');
	$tableRows .= moduleInfo('pages');
	$tableRows .= moduleInfo('users');
	$tableRows .= moduleInfo('search');
	$tableRows .= moduleInfo('log');

	$tableHeader = "<div align=center>".
			"\n{| class=wikitable" .
			"\n|+ <span style=\"color: blue;\" class=\"plainlinks\">[http://www.mediawiki.org/wiki/Extension:WikiFarm ". wfMsg( 'wikifarm_name' ) ."]</span> ". WIKIFARM_VERSION ." <small>(". WIKIFARM_BUILD .")</small>";
			"\n|-".
			"\n! ". wfMsg( 'wikifarm_module' ) .
			"\n! ". wfMsg( 'wikifarm_version' ) .
			"\n! ". wfMsg( 'wikifarm_changed' ) .
			"\n! ". wfMsg( 'wikifarm_notes' );

	$tableFooter = "\n|}\n</div>";
	$table = $tableHeader . $tableRows . $tableFooter;
	$wgOut->addWikiText( $table );
    $wgOut->addWikiText( wfarmFooter() );	 // show WikiFarm footer
} // function execute( $par )

} //class SpecialWikiFarm

//-------------------------------------------------
//------- Functions
//-------------------------------------------------

/**
 * Check if module is included. Return it's description if it is or notice that it is not.
 *
 * @param string $module WikiFarm module name to highlight. Optional.
 *		Possible values: statistics, links, changes, pages, users, search, log
 * @return string Table row in wiki format.
 */
function moduleInfo($module = ''){
	$lower = strtolower($module);
	$upper = strtoupper($module);
	$version= 'WIKIFARM_'. $upper .'_VERSION';
	$build	= 'WIKIFARM_'. $upper .'_BUILD';
	$row =	"\n|-";
	if ( !defined('WIKIFARM_'. $upper .'_VERSION') ) {
		$row .=	"\n| <span style=\"color: grey;\">". wfMsg( 'wikifarm_'. $lower ) ."</span>".
				"\n| <center><span style=\"color: grey;\">--</span></center>".
				"\n| <center><span style=\"color: grey;\">--</span></center>".
				"\n| <span style=\"color: grey;\">". wfMsg( 'wikifarm_module_disabled' ) ."</span>";
	}else {
		$row .=	"\n| ". wfMsg( 'wikifarm_'. $lower ) .
				"\n| ". constant($version) .
				"\n| ". constant($build) .
				"\n| ". wfMsg( 'wikifarm'. $lower .'_description' );
	}
	return $row;
}
