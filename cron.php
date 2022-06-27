<?php
$pagePath = explode('/wp-content/',dirname(__FILE__));

include_once($pagePath[0].'/wp-load.php');
require_once(dirname(__FILE__).'/obs-boutlas-xml.php');

$obs_plugin_obj->init();
$obs_plugin_obj->mode = 'bulk';
$obs_plugin_obj->obs_woo_export_schedule_cron();