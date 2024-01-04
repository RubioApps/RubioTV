<?php
namespace RubioTV\Framework;

defined('_TVEXEC') or die;

class TVConfig {
        public $sitename = 'RubioTV';
        public $live_site = 'https:/yoursite.com/tv';
	public $log_path = '/path/to/your/site/log';
	public $tmp_path = '/path/to/your/site/tmp';        
        public $menu = ['dtv','categories','languages','countries','custom','guides'];
	public $list_limit = 30;
        public $theme = 'default';
        public $dtv = [
                'type'          => 'tvheadend',                
                'host'          => 'https://yoursite.com/coreelec',
                'channels'      => '/playlist/channels',
                'stream'        => '/stream/channel',
                'xmltv'         => '/xmltv/channels',
                'cache'         => '/imagecache',
                'filename'      => 'coreelec'
                ];
        public $use_cache = true;
        public $notify_cache = false;
        public $links = [
                'MySite1'      => 'https://yoursite1.com/',
		'MySite2'      => 'https://yoursite2.com/',
                ];
	public $epg = [                
		'enabled'	=> true,
                'notify'	=> false,                                
                'dir'           => '/path/to/your/site/epg',
		'exec'		=> 'npm run grab --prefix=%s -- --channels=%s --output=%s --days=%s > /dev/null 2>&1',
                'lock'          => 60 ,   /* seconds */
                'expiry'        => 7 ,    /* days */		
                'secret_key'    => 'change-me-with-a-long-random-key', /* change this */
	        ];
}
