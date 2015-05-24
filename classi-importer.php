<?php

/*
  Plugin Name: ClassiPress Ads Importer plugin
  Plugin URI: http://appthemesimporter.info/
  Description: Import Ads+Users from CSV file in ClassiPress Theme
  Author: @appthemes-importer
  Version: 2.1.0
  Author URI: http://appthemesimporter.info/

  Copyright 2012  @appthemes-importer  (email : admin@appthemesimporter.info)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

setlocale(LC_ALL, 'en_US.UTF-8');

include(dirname(__FILE__) . '/classi-importer.class.php');
$my_post = array(
);

$importer = new Classi_Importer(
                'ad_listing',
                $my_post,
                array(
                    'custom_fields' => array(),
                    'taxonomies' => array(),
                    'tax_meta' => array(),
                )
);
$importer->init_plugin();
?>
