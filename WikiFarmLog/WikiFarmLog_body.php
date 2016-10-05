<?php

class WikiFarmLog extends SpecialPage {

function __construct() {
	    parent::__construct( 'WikiFarmLog' );
	    wfLoadExtensionMessages('WikiFarmLog');
}

function execute( $par ) {
	global $wgOut, $IP, $wgMetaNamespace;

    require_once( "$IP/extensions/WikiFarm/WikiFarm_config.php" ); 	//read config
    require_once( "$IP/extensions/WikiFarm/WikiFarm_common.php" ); 	//include common functions
    require_once( "$IP/extensions/WikiFarm/tools/botclasses.php" );	//include some common variables

	$wgOut->addWikiText( wfarmHeader('log') );	// show WikiFarm links
	//$wgOut->addWikiText( wfarmFormatTitle( wfMsgHtml( 'log' ) ) ); // page title
	$logPage = $wgMetaNamespace .":". wfMsgHtml( 'wikifarm_name' ) .".". wfMsgHtml( 'wikifarm_log' );
	$logCategory = wfMsgHtml( 'nstab-category' ) .":". wfMsgHtml( 'log' );
    $wgOut->addWikiText( "* ". wfMsgHtml( 'wikifarmlog_mediawiki_logs' ) .": [[Special:Log]]" .
    					 "\n* ". wfMsgHtml( 'wikifarmlog_about' ) .
    					 "\n** ". wfMsgHtml( 'wikifarmlog_general_log' ) .": [[$logPage]]" .
    					 "\n** ". wfMsgHtml( 'wikifarmlog_all_logs' ) .": [[:$logCategory]]" );
    // use wfarmLog() from WikiFarm_common.php to log events

    $wgOut->addWikiText( wfarmFooter() );	 // show WikiFarm footer
} // function execute( $par )

} //class WikiFarmLog

