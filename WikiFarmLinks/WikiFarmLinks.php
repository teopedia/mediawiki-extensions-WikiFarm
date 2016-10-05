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

define( 'WIKIFARM_LINKS_VERSION', '2.3' );
define( 'WIKIFARM_LINKS_BUILD', '2012.10.24' );

$wgExtensionCredits['specialpage'][] = array(
        'name' => 'WikiFarmLinks',
        'author' => 'Pavel Malakhov',
        'url' => 'http://www.mediawiki.org/wiki/Extension:WikiFarm',
        'descriptionmsg' => 'wikifarmlinks_description',
        'version' => WIKIFARM_LINKS_VERSION,
);

$dir = dirname(__FILE__) . '/';

 // Include parent extension
require_once( dirname( $dir ) . "/WikiFarm.php" );

// Add Autoload Classes
$wgAutoloadClasses['WikiFarmLinks'] = $dir . 'WikiFarmLinks_body.php';

// Add Internationalized Messages
$wgExtensionMessagesFiles['WikiFarmLinks'] = $dir . 'WikiFarmLinks.i18n.php';

// Register Special page
$wgSpecialPages['WikiFarmLinks'] = 'WikiFarmLinks';
$wgSpecialPageGroups['WikiFarmLinks'] = 'wiki';

