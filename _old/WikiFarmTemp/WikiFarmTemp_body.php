<?php

class WikiFarmTemp extends SpecialPage {

function __construct() {
	    parent::__construct( 'WikiFarmTemp' );
	    wfLoadExtensionMessages('WikiFarmTemp');
}

function execute( $par ) {
	global $wgOut, $IP;

    require_once( "$IP/extensions/WikiFarm/WikiFarm_config.php" ); 	//read config
    require_once( "$IP/extensions/WikiFarm/WikiFarm_common.php" );	//include some common variables
    $wgOut->addWikiText( wfarmHeader() );					    // show WikiFarm links
	//$wgOut->addWikiText( wfarmFormatTitle( wfMsg( 'wikifarm_module' ) ) );	// page title

	$wikis = wfarmDefineWikis( $wikis, $wikiAdmin, $infoLevel, $wfarmPrefixDelimiter );	//define wikis to work with
	$wgOut->addWikiText( "Template works" );

    $wgOut->addWikiText( wfarmFooter() );	 // show WikiFarm footer
} // function execute( $par )

} //class WikiFarmTemp

