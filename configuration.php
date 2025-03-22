<?php
/**
 +-------------------------------------------------------------------------+
 | RubioTV  - A domestic IPTV Web app browser                              |
 | Version 1.5.1                                                           |
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
        public $debug           = false;
        public $password = 'change-me-with-the-md5-hash-of-your-password';  //md5 hash of the plain password        
        public $use_sef         = true;
        public $use_cache       = true;
        public $use_autolog     = true;
        public $key      = 'change-me-with-a-random-key';                   //random key used for the encryption of the sessions (anti-flood)       
        public $menu            = ['dtv', 'categories', 'languages', 'countries', 'custom', 'guides', 'radio'];
	public $list_limit = 36;
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
                'enabled'       => true,
                'debug'         => true,
                'notify'        => false,
                'dir'           => '/path/to/your/site/epg',
                'exec'          => 'npm run grab --prefix=%s -- --channels=%s --output=%s --days=%s',
                'lock'          => 300, /* seconds */
                'limit'         => 5,  /* channels to process in a raw */
                'expiry'        => 15,  /* days */
                'offset'        => 0,  /* hours */
                'secret_key'    => 'change-me-with-a-long-random-key', /* change this */
                ];                
}
