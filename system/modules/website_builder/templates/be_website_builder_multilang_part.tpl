<?php
$this->import('tl_page');

foreach ($this->children as $strLanguage=>$arrChild):
?>
<tr onmouseover="$(this).addClass('highlight');" onmouseout="$(this).removeClass('highlight');">
	<td><?php $n = 0; for ($i=0; $i<$this->indent; $i++): ?>&nbsp;&nbsp;&nbsp;&nbsp;<?php endfor; ?><?php echo $this->tl_page->addIcon($arrChild['data'], $arrChild['data']['title'], null, 'style="vertical-align:middle;"', true); ?> <?php echo $arrChild['data']['title']; ?></span></td>
	<?php if ($arrChild['data']['type'] == 'root'): ?>
	<td colspan="<?php echo count($arrChild['languages']); ?>">&nbsp;</td>
	<?php else: ?>
	<?php foreach ($arrChild['languages'] as $strLang=>$arrPage): ?>
	<?php if ($arrPage): ?>
	<td><?php echo $this->generateImage('ok.gif', $arrPage['title'], sprintf('title="%s" style="vertical-align: middle;"', specialchars($arrPage['title']))) ?><?php if ($n>0): ?> <?php echo $arrPage['title']; endif; ?></td>
	<?php else: ?>
	<td><?php echo $this->generateImage('error.gif', '', 'style="vertical-align: middle;"') ?></td>
	<?php endif; ?>
	<?php $n++; endforeach; endif; ?>
</tr>
<?php

$objTemplate = new BackendTemplate('be_website_builder_multilang_part');
$objTemplate->setData($arrChild);
$objTemplate->indent = $this->indent+1;
echo $objTemplate->parse();

endforeach;
?>