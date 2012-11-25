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
 * Website Builder configuration
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{website_builder_legend},website_builder_datasets';

$GLOBALS['TL_DCA']['tl_settings']['fields']['website_builder_datasets'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['website_builder_datasets'],
	'exclude'                 => true,
	'inputType'               => 'listWizard',
	'eval'                    => array('allowHtml'=>true)
);
