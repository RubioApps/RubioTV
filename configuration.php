<?php
namespace RubioTV\Framework;

defined('_TVEXEC') or die;

class TVConfig {
        public $sitename = 'RubioTV';
        public $live_site = 'https://famillerubio.com/tv';
	public $log_path = '/var/www/rubiotv/log';
	public $tmp_path = '/var/www/rubiotv/tmp';        
        public $menu = ['dtv','categories','languages','countries','custom','guides'];
	public $list_limit = 30;
        public $theme = 'default';
        public $dtv = [
                'type'          => 'tvheadend',                
                'host'          => 'https://famillerubio.com/coreelec',
                'channels'      => '/playlist/channels',
                'stream'        => '/stream/channel',
                'xmltv'         => '/xmltv/channels',
                'cache'         => '/imagecache',
                'filename'      => 'coreelec'
                ];
        public $use_cache = true;
        public $notify_cache = false;
        public $links = [
                'RubioVPN'      => 'https://famillerubio.com/vpn/',
                'RubioGuard'    => 'https://famillerubio.com/squid/',
                'Alfogon'       => 'https://famillerubio.com/alfogon/',
                'Jardiname'     => 'https://famillerubio.com/jardiname/',

                ];
	public $epg = [                
		'enabled'	=> true,
                'notify'	=> false,                                
                'dir'           => '/var/www/rubiotv/epg',
		'exec'		=> 'npm run grab --prefix=%s -- --channels=%s --output=%s --days=%s > /dev/null 2>&1',
                'lock'          => 60 ,   /* seconds */
                'expiry'        => 7 ,    /* days */		
                'secret_key'    => 'Bw:?%3)NJFTs`^:gg:Q!6()vS!\a#Sc<[t5Z`9s9G*.3X<Y7^tRP}+!:2b88X}_', /* change this */
	        ];
}
