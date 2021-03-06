<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  InfinitySoft 2010
 * @author     Tristan Lins <tristan.lins@infinitysoft.de>
 * @package    Website Builder
 * @license    http://opensource.org/licenses/lgpl-3.0.html
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
				'tables'     => array('tl_dataset_import'),
				'callback'   => 'WebsiteBuilderDatasetImport',
				'icon'       => 'system/modules/website_builder/html/dataset_import.png',
				'stylesheet' => 'system/modules/website_builder/html/stylesheet.css'
			),
			'multilang' => array
			(
				'callback'   => 'WebsiteBuilderMultilang',
				'icon'       => 'system/modules/website_builder/html/multilang.png',
				'stylesheet' => 'system/modules/website_builder/html/stylesheet.css'
			)
		)
	),
	array_slice($GLOBALS['BE_MOD'], $i)
);


/**
 * HOOKs
 */
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('WebsiteBuilderDatasetImport', 'hookLoadDataContainer');

?>