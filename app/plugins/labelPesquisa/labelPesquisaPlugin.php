<?php
/* ----------------------------------------------------------------------
 * duplicateMenuPlugin.php : implements editing activity menu - a list of recently edited items
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2018 Whirl-i-Gig
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
 
	class labelPesquisaPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->description = _t('Atribui automaticamente um nome à pesquisa igual ao IDNO.');
			parent::__construct();
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the historyMenu plugin always initializes ok
		 */
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => true
			);
		}
		# -------------------------------------------------------
		
		public function hookSaveItem(&$pa_params) 
		{
			global $g_ui_locale_id;
			
			if ($pa_params['table_name'] == 'ca_occurrences')
			{
				$t_instance = $pa_params['instance'];
				
				switch($t_instance->getTypeCode())
				{
					case 'pesquisa':
					
						$vs_label = $t_instance->get('idno');
						
						$t_instance->removeAllLabels();
						
						$t_instance->addLabel(array(
								'name' => $vs_label,
							), $g_ui_locale_id, null, true);
							
						break;
				}
				
				$t_instance->update();
			}
			
			return true;
		}
	}
