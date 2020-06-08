<?php 

defined('ABSPATH') || die;

header('Content-Type: text/xml');
do_action( 'gfeed_generate_xml', true);
