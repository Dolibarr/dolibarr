<!-- file list.tpl.php -->
<?php
/* Copyright (C) 2025		Open-Dsi							<support@open-dsi.fr>
 */
// Protection to avoid direct call of template
if (empty($context) || !is_object($context)) {
	print "Error, template page can't be called as URL";
	exit(1);
}
'@phan-var-force Context $context';
'@phan-var-force AbstractCardController $this';

/**
 * @var Conf					$conf
 * @var HookManager				$hookmanager
 * @var Translate				$langs
 * @var Context					$context
 * @var AbstractCardController 	$this
 * @var FormCardWebPortal 		$formCard
 */
$formCard = $this->formCard;

$fieldShowList = $formCard->fieldsmanager->getAllFieldsInfos($formCard->object, $formCard->extrafields, 'view', 2, array(1 => $formCard->key_for_break));

?>

<div id="properties">
	<div class="grid">
		<div class="card-left">
			<?php foreach ($fieldShowList['columns'] as $idxColumn => $fields) {
				// Switch to the second column
				if ($idxColumn == 2) { ?>
		</div>
		<div class="card-right">
				<?php }

				// Display fields for this column
				foreach ($fields as $fieldKey => $fieldInfos) {
					// TODO make support of separator in web portal
					if ($fieldInfos->fieldType == 'separate') {
						//$formCard->fieldsmanager->printSeparator($fieldKey, $formCard->object);
						continue;
					}

					if ($fieldInfos->fieldType == FieldInfos::FIELD_TYPE_OBJECT) {
						$value = $formCard->object->{$fieldInfos->key} ?? '';
					} else {
						$value = $formCard->object->array_options['options_' . $fieldInfos->key] ?? '';
					}

					// Load language file
					if (!empty($fieldInfos->langFile)) {
						$langs->load($fieldInfos->langFile);
					}

					if ($fieldInfos->fieldType == FieldInfos::FIELD_TYPE_EXTRA_FIELD && $fieldInfos->key == 'lang') {
						$langs->load('languages');
						$labellang = ($value ? $langs->trans('Language_' . $value) : '');
						//$labellang .= picto_from_langcode($value, 'class="paddingrightonly saturatemedium opacitylow"');
						$input = $labellang;
					} else {
						$input = $formCard->fieldsmanager->printOutputField($fieldInfos, $fieldKey, $value);
					}

					$label_class = 'labelfield fieldname_' . $fieldKey;
					if (!empty($fieldInfos->tdCss)) $label_class .= $fieldInfos->tdCss;
					$value_class = 'valuefield fieldname_' . $fieldKey;
					if (!empty($fieldInfos->viewCss)) $value_class .= $fieldInfos->viewCss;

					$label = is_string($fieldInfos->label) ? $langs->trans($fieldInfos->label) : $fieldInfos->label;
					print $formCard->form->printFieldCell($fieldKey, '<strong>' . $label . '</strong>', $input, [
						'label_class' => $label_class,
						'value_class' => $value_class,
					]);
				}
			} ?>
		</div>
	</div>
</div>
