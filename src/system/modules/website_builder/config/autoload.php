<?php

/**
 * Contao Open Source CMS
 * 
 * Copyright (C) 2005-2012 Leo Feyer
 * 
 * @package Website_builder
 * @link    http://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	'WebsiteBuilderDatasetImport' => 'system/modules/website_builder/WebsiteBuilderDatasetImport.php',
	'WebsiteBuilderMultilang'     => 'system/modules/website_builder/WebsiteBuilderMultilang.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'be_website_builder_dataset_import' => 'system/modules/website_builder/templates',
	'be_website_builder_multilang'      => 'system/modules/website_builder/templates',
	'be_website_builder_multilang_part' => 'system/modules/website_builder/templates',
));
