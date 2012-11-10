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


$GLOBALS['TL_LANG']['tl_website_builder_multilang']['headline']                = 'Mehrsprachige Seitenstrukturen';
$GLOBALS['TL_LANG']['tl_website_builder_multilang']['missing_changelanguage']  = 'Die Erweiterung <a href="contao/main.php?do=repository_catalog&view=changelanguage">changelanguage</a> wird für dieses Feature benötigt!';
$GLOBALS['TL_LANG']['tl_website_builder_multilang']['duplicate_language']      = 'Für die Domain %s existieren in der Sprache %s zwei Seiten, IDs: %s, %s!';
$GLOBALS['TL_LANG']['tl_website_builder_multilang']['missing_root']            = 'Die Fremd-Domain Hauptsprache %s wurde für die Seite ID % nicht gefunden!';
$GLOBALS['TL_LANG']['tl_website_builder_multilang']['missing_fallback']        = 'Für die Domain %s wurde kein Sprachen-Fallback gefunden!';
$GLOBALS['TL_LANG']['tl_website_builder_multilang']['change_dns']              = 'Domain wechseln';
$GLOBALS['TL_LANG']['tl_website_builder_multilang']['duplicate_page_language'] = 'Die Seite %s (%s) ID %s und ihre Fallback Seite %s (%s) ID %s haben die gleiche Sprache %s!';
$GLOBALS['TL_LANG']['tl_website_builder_multilang']['missing_root']            = 'Für die Domain %s wurde in der Sprache %s kein Startpunkt gefunden!';
$GLOBALS['TL_LANG']['tl_website_builder_multilang']['created']                 = 'Die neue Seitenstruktur wurde erstellt.';

$GLOBALS['TL_LANG']['tl_website_builder_multilang']['structure']               = 'Strukturübersicht';


$GLOBALS['TL_LANG']['tl_website_builder_multilang']['source_language']         = array('Ausgangssprache', 'Wählen Sie hier die Sprache aus, dessen Struktur kopiert werden soll.');
$GLOBALS['TL_LANG']['tl_website_builder_multilang']['target_language']         = array('Sprache', 'Wählen Sie hier die Sprache die, die neu erstellt werden soll.');
$GLOBALS['TL_LANG']['tl_website_builder_multilang']['create_language']         = 'Neue Sprachversion erstellen';

?>