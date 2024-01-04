<?php
defined('_TVEXEC') or die;

header('Location: ' . $factory->getTaskURL('channels', 'dtv', $config->dtv['filename']));   


