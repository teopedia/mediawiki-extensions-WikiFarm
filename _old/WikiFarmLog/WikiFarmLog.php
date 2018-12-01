<?php
# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if (!defined('MEDIAWIKI')) {
        echo <<<EOT
To install this extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/WikiFarm/WikiFarm.php" );
and read WikiFarm.php for more information.
EOT;
        exit( 1 );
}

define( 'WIKIFARM_LOG_VERSION', '2.0' ); 
define( 'WIKIFARM_LOG_BUILD', '2012.02.18' ); 
 
$wgExtensionCredits['specialpage'][] = array(
        'name' => 'WikiFarmLog',
        'author' => 'Pavel Malakhov',
        'url' => 'http://www.mediawiki.org/wiki/Extension:WikiFarm',
        'descriptionmsg' => 'wikifarmlog_description',
        'version' => WIKIFARM_LOG_VERSION,
);
 
$dir = dirname(__FILE__) . '/';
 
 // Include parent extension
require_once( dirname( $dir ) . "/WikiFarm.php" );

// Add Autoload Classes
$wgAutoloadClasses['WikiFarmLog'] = $dir . 'WikiFarmLog_body.php'; 

// Add Internationalized Messages
$wgExtensionMessagesFiles['WikiFarmLog'] = $dir . 'WikiFarmLog.i18n.php'; 

// Register Special page
$wgSpecialPages['WikiFarmLog'] = 'WikiFarmLog'; 
$wgSpecialPageGroups['WikiFarmLog'] = 'wiki';

