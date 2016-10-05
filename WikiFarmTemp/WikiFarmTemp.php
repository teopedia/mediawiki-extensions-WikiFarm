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

define( 'WIKIFARM_TEMPLATE_VERSION', '1.0' ); 
define( 'WIKIFARM_TEMPLATE_BUILD', '2012.01.18' ); 
 
$wgExtensionCredits['specialpage'][] = array(
        'name' => 'WikiFarmTemp',
        'author' => 'Pavel Malakhov',
        'url' => 'http://www.mediawiki.org/wiki/Extension:WikiFarm',
        'descriptionmsg' => 'wikifarmtemp_description',
        'version' => WIKIFARM_TEMPLATE_VERSION,
);
 
$dir = dirname(__FILE__) . '/';
 
 // Include parent extension
require_once( dirname( $dir ) . "/WikiFarm.php" );

// Add Autoload Classes
$wgAutoloadClasses['WikiFarmTemp'] = $dir . 'WikiFarmTemp_body.php'; 

// Add Internationalized Messages
$wgExtensionMessagesFiles['WikiFarmTemp'] = $dir . 'WikiFarmTemp.i18n.php'; 

// Register Special page
$wgSpecialPages['WikiFarmTemp'] = 'WikiFarmTemp'; 
$wgSpecialPageGroups['WikiFarmTemp'] = 'wiki';

