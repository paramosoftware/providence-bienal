<?php
/* ----------------------------------------------------------------------
 * bundles/ca_list_item_labels_nonpreferred.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2023 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *
 * This source code is free and modifiable under the terms of 
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */
$id_prefix 				= $this->getVar('placement_code').$this->getVar('id_prefix');
$labels 				= $this->getVar('labels');
$t_subject 				= $this->getVar('t_subject');
$t_label 				= $this->getVar('t_label');
$initial_values 		= $this->getVar('label_initial_values');
if (!$force_new_labels 	= $this->getVar('new_labels')) { $force_new_labels = array(); }	// list of new labels not saved due to error which we need to for onto the label list as new

$settings 				= $this->getVar('settings');
$add_label 				= $this->getVar('add_label');

$read_only				= ((isset($settings['readonly']) && $settings['readonly'])  || ($this->request->user->getBundleAccessLevel('ca_list_items', 'nonpreferred_labels') == __CA_BUNDLE_ACCESS_READONLY__));

$show_effective_date 	= $this->getVar('show_effective_date');
$show_access 			= $this->getVar('show_access');
$label_list 			= $this->getVar('label_type_list');
$locale_list			= $this->getVar('locale_list');
$show_source 			= $t_subject->getTypeSetting('show_source_for_nonpreferred_labels');

print caEditorBundleShowHideControl($this->request, $id_prefix.'NPLabels', $settings, caInitialValuesArrayHasValue($id_prefix.'NPLabels', $initial_values));	
print caEditorBundleMetadataDictionary($this->request, $id_prefix.'NPLabels', $settings);
?>
<div id="<?= $id_prefix; ?>NPLabels">
<?php
	//
	// The bundle template - used to generate each bundle in the form
	//
?>
	<textarea class='caLabelTemplate' style='display: none;'>
		<div id="{fieldNamePrefix}Label_{n}" class="labelInfo">
			<div style="float: right;">
				<a href="#" class="caDeleteLabelButton"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a>
			</div>
			<table>
				<tr valign="middle">
					<td>
						<table>
							<tr>
								<td>
									<?= $t_label->htmlFormElement('name_singular', null, array('name' => "{fieldNamePrefix}name_singular_{n}", 'id' => "{fieldNamePrefix}name_singular_{n}", "value" => "{{name_singular}}", 'no_tooltips' => false, 'textAreaTagName' => 'textentry', 'readonly' => $read_only, 'tooltip_namespace' => 'bundle_ca_list_item_labels_nonpreferred')); ?>
								</td>
							</tr>
							<tr>
								<td>
									<?= $t_label->htmlFormElement('name_plural', null, array('name' => "{fieldNamePrefix}name_plural_{n}", 'id' => "{fieldNamePrefix}name_plural_{n}", "value" => "{{name_plural}}", 'no_tooltips' => false, 'textAreaTagName' => 'textentry', 'readonly' => $read_only, 'tooltip_namespace' => 'bundle_ca_list_item_labels_nonpreferred')); ?>
								</td>
							</tr>
							<tr>
								<td>
									<?= $t_label->htmlFormElement('description', null, array('name' => "{fieldNamePrefix}description_{n}", 'id' => "{fieldNamePrefix}description_{n}", "value" => "{{description}}", 'no_tooltips' => false, 'textAreaTagName' => 'textentry', 'readonly' => $read_only, 'tooltip_namespace' => 'bundle_ca_list_item_labels_nonpreferred')); ?>
								</td>
							</tr>
							<tr>
								<td>
									<div class="formLabel">
										<?= $locale_list; ?>	
										<?= $label_list ? $t_label->htmlFormElement('type_id', "^LABEL ^ELEMENT", array('classname' => 'labelType', 'id' => "{fieldNamePrefix}type_id_{n}", 'name' => "{fieldNamePrefix}type_id_{n}", "value" => "{type_id}", 'no_tooltips' => true, 'list_code' => $label_list, 'dont_show_null_value' => true, 'hide_select_if_no_options' => true)) : ''; ?>
										<?= $show_effective_date ? $t_label->htmlFormElement('effective_date', "^LABEL ^ELEMENT", array('classname' => 'labelLocale', 'id' => "{fieldNamePrefix}effective_date_{n}", 'name' => "{fieldNamePrefix}effective_date_{n}", "value" => "{effective_date}", 'no_tooltips' => true)) : ''; ?>	
										<?= $show_access ? $t_label->htmlFormElement('access', "^LABEL ^ELEMENT", array('classname' => 'labelLocale', 'id' => "{fieldNamePrefix}access_{n}", 'name' => "{fieldNamePrefix}access_{n}", "value" => "{access}", 'no_tooltips' => true)) : ''; ?>	
									</div>	
								</td>
							</tr>
<?php
	if($show_source) {
?>					
							<tr>
								<td>
									<div class="formLabel">
										<?= $t_label->htmlFormElement('source_info', "^LABEL<br/>^ELEMENT", array('classname' => 'labelSourceInfo', 'id' => "{fieldNamePrefix}source_info_{n}", 'name' => "{fieldNamePrefix}source_info_{n}", "value" => "{source_info}", 'no_tooltips' => true, 'textAreaTagName' => 'textentry')); ?>	
									</div>
								</td>
							</tr>
<?php
	}	
?>
						</table>
					</td>
				</tr>
			</table>
		</div>
<?php
	print TooltipManager::getLoadHTML('bundle_ca_list_item_labels_preferred');
?>
	</textarea>
	
	<div class="bundleContainer">
		<div class="caLabelList">
		
		</div>
		<div class="button labelInfo caAddLabelButton"><a href='#'><?= caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?= $add_label ? $add_label : _t("Add label"); ?></a></div>
	</div>
			
	
</div>
<script type="text/javascript">
	caUI.initLabelBundle('#<?= $id_prefix; ?>NPLabels', {
		mode: 'nonpreferred',
		fieldNamePrefix: '<?= $id_prefix; ?>',
		templateValues: ['name_singular', 'name_plural', 'description', 'locale_id', 'type_id', 'effective_date', 'access', 'source_info'],
		initialValues: <?= json_encode($initial_values); ?>,
		forceNewValues: <?= json_encode($force_new_labels); ?>,
		labelID: 'Label_',
		localeClassName: 'labelLocale',
		templateClassName: 'caLabelTemplate',
		labelListClassName: 'caLabelList',
		addButtonClassName: 'caAddLabelButton',
		deleteButtonClassName: 'caDeleteLabelButton',
		bundlePreview: <?= caEscapeForBundlePreview($this->getVar('bundle_preview')); ?>,
		readonly: <?= $read_only ? "1" : "0"; ?>,
		defaultLocaleID: <?= ca_locales::getDefaultCataloguingLocaleID(); ?>,
		defaultAccess: <?= json_encode(caGetDefaultItemValue('access_statuses')); ?>
	});
</script>
