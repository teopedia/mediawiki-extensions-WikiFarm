<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'WikiFarm' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['WikiFarm'] = __DIR__ . '/i18n';
	wfWarn( 'Deprecated PHP entry point used for WikiFarm extension. Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.' );
	return;
} else {
	die( 'This version of the WikiFarm extension requires MediaWiki 1.29+' );
}


//-------------------------------------------------
//------- Register WikiFarm extention
//-------------------------------------------------

// Register Special page and place it in section "wiki data and tools"
//$wgSpecialPages['WikiFarm'] = 'SpecialWikiFarm';
//$wgSpecialPageGroups['WikiFarm'] = 'wiki';

//-------------------------------------------------
//------- include WikiFarm tools
//-------------------------------------------------
//global $IP; //get the variable from LocalSettings.php
//require_once( "$IP/extensions/WikiFarm/WikiFarmStatistics/WikiFarmStatistics.php" );
//require_once( "$IP/extensions/WikiFarm/WikiFarmChanges/WikiFarmChanges.php" );
//require_once( "$IP/extensions/WikiFarm/WikiFarmPages/WikiFarmPages.php" );
//require_once( "$IP/extensions/WikiFarm/WikiFarmLog/WikiFarmLog.php" );
//require_once( "$IP/extensions/WikiFarm/WikiFarmUsers/WikiFarmUsers.php" );
//require_once( __DIR__ . "/WikiFarmLinks.php" );
//require_once( "$IP/extensions/WikiFarm/WikiFarmSearch/WikiFarmSearch.php" );

