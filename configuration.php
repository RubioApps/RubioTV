<?php
/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.3.0                                                           |
 |                                                                         |
 | This program is free software: you can redistribute it and/or modify    |
 | it under the terms of the GNU General Public License as published by    |
 | the Free Software Foundation.                                           |
 |                                                                         |
 | This file forms part of the RubioTV software.                           |
 |                                                                         |
 | If you wish to use this file in another project or create a modified    |
 | version that will not be part of the RubioTV Software, you              |
 | may remove the exception above and use this source code under the       |
 | original version of the license.                                        |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the            |
 | GNU General Public License for more details.                            |
 |                                                                         |
 | You should have received a copy of the GNU General Public License       |
 | along with this program.  If not, see http://www.gnu.org/licenses/.     |
 |                                                                         |
 +-------------------------------------------------------------------------+
 | Author: Jaime Rubio <jaime@rubiogafsi.com>                              |
 +-------------------------------------------------------------------------+
*/
namespace RubioTV\Framework;

defined('_TVEXEC') or die;

class TVConfig {        
        public $sitename = 'RubioTV';
        public $live_site = 'https:/yoursite.com/tv';
	public $log_path = '/path/to/your/site/log';
	public $tmp_path = '/path/to/your/site/tmp';   
        public $password = 'change-me-with-the-md5-hash-of-your-password';  //md5 hash of the plain password
        public $key      = 'change-me-with-a-random-key';                   //random key used for the encryption of the sessions (anti-flood)
        public $use_sef = true;
        public $use_autolog = true;
        public $use_cache = true;
        public $notify_cache = false;        
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
        public $links = [
                'MySite1'      => 'https://yoursite1.com/',
		'MySite2'      => 'https://yoursite2.com/',

                ];
	public $epg = [
		'enabled'	=> true,
                'notify'	=> false,                                
                'dir'           => '/path/to/your/site/epg',
		'exec'		=> 'npm run grab --prefix=%s -- --channels=%s --output=%s --days=%s',
                'lock'          => 60 , /* seconds */
                'expiry'        => 7 ,  /* days */		
                'fix_time'      => 6 ,  /* hours */
                'secret_key'    => 'change-me-with-a-long-random-key', /* change this */
	        ];
}
