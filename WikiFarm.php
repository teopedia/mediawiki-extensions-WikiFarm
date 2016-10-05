<?php
/* All scripts are under Public Domain license except the ones from MediaWiki 1.17. Those are under GNU/GPL v.2 (see the header of each script)

Additional info is on extention page http://www.mediawiki.org/wiki/Extension:WikiFarm
or distibution home page http://sysadminwiki.ru/wiki/File:WikiFarm.tar.gz
*/

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if (!defined('MEDIAWIKI')) {
    echo <<<EOT
To install this extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/WikiFarm/WikiFarm.php" );

and read WikiFarm.php for more information.
EOT;
    exit( 1 );
}

define( 'WIKIFARM_VERSION', '2.3' );
define( 'WIKIFARM_BUILD', '2012.10.24' );

//-------------------------------------------------
//------- Register WikiFarm extention
//-------------------------------------------------

$wgExtensionCredits['specialpage'][] = array(
        'name' => 'WikiFarm',
        'author' => 'Pavel Malakhov',
        'url' => 'http://www.mediawiki.org/wiki/Extension:WikiFarm',
        'descriptionmsg' => 'wikifarm_description',
        'version' => WIKIFARM_VERSION,
);

$dir = dirname(__FILE__) . '/';

// Add Autoload Classes
$wgAutoloadClasses['SpecialWikiFarm'] = $dir . 'WikiFarm_body.php';

// Add Internationalized Messages
$wgExtensionMessagesFiles['WikiFarm'] = $dir . 'WikiFarm.i18n.php';

// Register Special page and place it in section "wiki data and tools"
$wgSpecialPages['WikiFarm'] = 'SpecialWikiFarm';
$wgSpecialPageGroups['WikiFarm'] = 'wiki';

//-------------------------------------------------
//------- include WikiFarm tools
//-------------------------------------------------
global $IP; //get the variable from LocalSettings.php
require_once( "$IP/extensions/WikiFarm/WikiFarmStatistics/WikiFarmStatistics.php" );
require_once( "$IP/extensions/WikiFarm/WikiFarmChanges/WikiFarmChanges.php" );
require_once( "$IP/extensions/WikiFarm/WikiFarmPages/WikiFarmPages.php" );
//require_once( "$IP/extensions/WikiFarm/WikiFarmLog/WikiFarmLog.php" );
require_once( "$IP/extensions/WikiFarm/WikiFarmUsers/WikiFarmUsers.php" );
require_once( "$IP/extensions/WikiFarm/WikiFarmLinks/WikiFarmLinks.php" );
require_once( "$IP/extensions/WikiFarm/WikiFarmSearch/WikiFarmSearch.php" );

