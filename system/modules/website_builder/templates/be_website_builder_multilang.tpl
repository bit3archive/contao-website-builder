<?php if (is_array($this->pages)): ?>
<div class="tl_version_panel">
	<form action="contao/main.php?do=multilang" method="post">
		<div class="tl_formbody">
			<input type="hidden" name="FORM_SUBMIT" value="tl_multilang" />
			<select name="dns">
				<?php foreach ($this->pages as $strDomain=>$arrDomain): ?>
				<option value="<?php echo specialchars($strDomain); ?>"><?php echo $strDomain; ?></option>
				<?php endforeach; ?>
			</select>
			<input type="submit" value="<?php echo specialchars($GLOBALS['TL_LANG']['tl_website_builder_multilang']['change_dns']); ?>" />
		</div>
	</form>
</div>

<div id="tl_website_builder_multilang">

<h2 class="sub_headline"><?php echo $GLOBALS['TL_LANG']['tl_website_builder_multilang']['headline']; ?></h2>

<?php echo $this->getMessages(); ?>

<?php
if ($this->structure):
$arrDomain = $this->pages[$this->dns];
$arrLanguages = array_keys($arrDomain['pages']);
$arrLanguageNames = $this->getLanguages();
?>
<form action="contao/main.php?do=multilang" method="post">
<input type="hidden" name="FORM_SUBMIT" value="tl_multilang_create" />
<input type="hidden" name="dns" value="<?php echo $this->dns; ?>" />
<div class="tl_formbody_edit">
<fieldset class="tl_tbox block">
	<legend><?php echo specialchars($GLOBALS['TL_LANG']['tl_website_builder_multilang']['structure']); ?></legend>
	<table>
		<thead>
			<tr>
				<th><?php echo $this->dns; ?></th>
				<?php foreach ($arrLanguages as $strLang): ?>
				<th><img src="system/modules/changelanguage/media/images/<?php echo $strLang; ?>.gif" alt="<?php echo specialchars($arrLanguageNames[$strLang]); ?>" title="<?php echo specialchars($arrLanguageNames[$strLang]); ?>" /></th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php
			$objTemplate = new BackendTemplate('be_website_builder_multilang_part');
			$objTemplate->setData($this->structure);
			$objTemplate->indent = 0;
			echo $objTemplate->parse();
			?>
		</tbody>
	</table>
<?php
endif;
?>
</fieldset>

<fieldset class="tl_box block">
	<legend><?php echo specialchars($GLOBALS['TL_LANG']['tl_website_builder_multilang']['create_language']); ?></legend>
	<div class="w50">
		<h3><label for="ctrl_source_language"><?php echo $GLOBALS['TL_LANG']['tl_website_builder_multilang']['source_language'][0]; ?></label></h3>
		<select id="ctrl_source_language" name="source_language">
			<?php foreach ($arrLanguages as $strLang): ?>
			<option value="<?php echo $strLang; ?>"><?php echo $arrLanguageNames[$strLang]; ?></option>
			<?php endforeach; ?>
		</select>
		<p class="tl_help tl_tip"><?php echo $GLOBALS['TL_LANG']['tl_website_builder_multilang']['source_language'][1]; ?></p>
	</div>
	<div class="w50">
		<h3><label for="ctrl_target_language"><?php echo $GLOBALS['TL_LANG']['tl_website_builder_multilang']['target_language'][0]; ?></label></h3>
		<select id="ctrl_target_language" name="target_language">
			<?php foreach ($arrLanguageNames as $strLang=>$strName): if (!in_array($strLang, $arrLanguages)): ?>
			<option value="<?php echo $strLang; ?>"><?php echo $strName; ?></option>
			<?php endif; endforeach; ?>
		</select>
		<p class="tl_help tl_tip"><?php echo $GLOBALS['TL_LANG']['tl_website_builder_multilang']['target_language'][1]; ?></p>
	</div>
</fieldset>
</div>

<div class="tl_formbody_submit">
	<div class="tl_submit_container">
		<input type="submit" value="<?php echo specialchars($GLOBALS['TL_LANG']['tl_website_builder_multilang']['create_language']); ?>" accesskey="s" class="tl_submit" />
	</div>
</div>
</form>

</div>
<?php else: ?>

<?php echo $this->getMessages(); ?>

<br/>

<?php endif; ?>
