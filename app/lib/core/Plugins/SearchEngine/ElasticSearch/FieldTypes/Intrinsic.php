<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Intrinsic.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
 * @package CollectiveAccess
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

namespace ElasticSearch\FieldTypes;

require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/FieldType.php');

class Intrinsic extends FieldType {

	/**
	 * Table name
	 * @var string
	 */
	protected $ops_table_name;
	/**
	 * Field name
	 * @var string
	 */
	protected $ops_field_name;

	/**
	 * Intrinsic constructor.
	 * @param string $ops_table_name
	 * @param string $ops_field_name
	 */
	public function __construct($ops_table_name, $ops_field_name) {
		$this->ops_table_name = $ops_table_name;
		$this->ops_field_name = $ops_field_name;
	}

	/**
	 * @return string
	 */
	public function getTableName() {
		return $this->ops_table_name;
	}

	/**
	 * @param string $ops_table_name
	 */
	public function setTableName($ops_table_name) {
		$this->ops_table_name = $ops_table_name;
	}

	/**
	 * @return string
	 */
	public function getFieldName() {
		return $this->ops_field_name;
	}

	/**
	 * @param string $ops_field_name
	 */
	public function setFieldName($ops_field_name) {
		$this->ops_field_name = $ops_field_name;
	}

	/**
	 * @param mixed $pm_content
	 * @param array $pa_options
	 * @return array
	 */
	public function getIndexingFragment($pm_content, $pa_options) {
		if(is_array($pm_content)) { $pm_content = serialize($pm_content); }
		if($pm_content == '') { $pm_content = null; }

		$va_field_info = \Datamodel::load()->getFieldInfo($this->getTableName(), $this->getFieldName());

		switch($va_field_info['FIELD_TYPE']) {
			case (FT_BIT):
				$pm_content = (bool) $pm_content;
				break;
			case (FT_NUMBER):
			case (FT_TIME):
			case (FT_TIMERANGE):
			case (FT_TIMECODE):
				if (!isset($va_field_info['LIST_CODE'])) {
					$pm_content = (float) $pm_content;
				}
				break;
			default:
				// noop (pm_content is just pm_content)
				break;
		}

		$va_return = array(
			$this->getTableName() . '.' . $this->getFieldName() => $pm_content
		);

		if($vn_rel_type_id = caGetOption('relationship_type_id', $pa_options)) {
			// elasticsearch doesn't allow slashes in field names, so we use a pipe instead
			$va_return[
				$this->getTableName() . '.' . $this->getFieldName() . '|' . caGetRelationshipTypeCode($vn_rel_type_id)
			] = $pm_content;
		}

		return $va_return;
	}

	/**
	 * @param \Zend_Search_Lucene_Index_Term $po_term
	 * @return \Zend_Search_Lucene_Index_Term
	 */
	public function getRewrittenTerm($po_term) {
		$t_instance = \Datamodel::load()->getInstance($this->getTableName());

		$va_field_components = explode('.', $po_term->field);

		if((strtolower($po_term->text) === '[blank]')) {
			if($t_instance instanceof \BaseLabel) { // labels usually have actual [BLANK] values
				return new \Zend_Search_Lucene_Index_Term(
					'"'.$po_term->text.'"', $po_term->field
				);
			} else {
				return new \Zend_Search_Lucene_Index_Term(
					$po_term->field, '_missing_'
				);
			}
		} elseif(stripos($po_term->field, '/') !== false) {
			// elasticsearch doesn't allow slashes in field names, so we use a pipe instead.
			// rewrite ca_entity_labels.displayname/creator to ca_entity_labels.displayname|creator here
			// note that there are (hopefully) no other cases where we need slashes
			return new \Zend_Search_Lucene_Index_Term(
				$po_term->text, str_replace('/', '|', $po_term->field)
			);
		} elseif(
			isset($va_field_components[1]) &&
			($t_instance->getProperty('ID_NUMBERING_ID_FIELD') == $va_field_components[1])
		) {
			return new \Zend_Search_Lucene_Index_Term(
				'"'.$po_term->text.'"', $po_term->field
			);
		}

		return $po_term;
	}
}
