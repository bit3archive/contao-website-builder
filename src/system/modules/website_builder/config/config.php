<?php

/**
 * WebsiteBuilder
 * Extension for Contao Open Source CMS
 *
 * @author  Tristan Lins <tristan.lins@bit3.de>
 * @package WebsiteBuilder
 * @link    http://bit3.de
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Back end modules
 */
$i = array_search('system', array_keys($GLOBALS['BE_MOD'])) + 1;
$GLOBALS['BE_MOD'] = array_merge(
	array_slice($GLOBALS['BE_MOD'], 0, $i),
	array
	(
		'website_builder' => array
		(
			'dataset_import' => array
			(
				'callback'   => 'WebsiteBuilderDatasetImport',
				'icon'       => 'system/modules/website_builder/assets/images/dataset_import.png',
				'stylesheet' => 'system/modules/website_builder/assets/css/backend.css'
			),
			'multilang' => array
			(
				'callback'   => 'WebsiteBuilderMultilang',
				'icon'       => 'system/modules/website_builder/assets/images/multilang.png',
				'stylesheet' => 'system/modules/website_builder/assets/css/backend.css'
			)
		)
	),
	array_slice($GLOBALS['BE_MOD'], $i)
);


/**
 * HOOKs
 */
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('WebsiteBuilderDatasetImport', 'hookLoadDataContainer');
