<?php
/** ---------------------------------------------------------------------
 * app/lib/Import/MetsALTOImporter.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2024 Whirl-i-Gig
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
 * @subpackage Import
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */ 
require_once(__CA_LIB_DIR__.'/MetsALTO/MetsALTOSearch.php');

class MetsALTOImporter {
	# -------------------------------------------------------
	/**
	 *
	 */
	protected $search;
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		$this->search = new MetsALTOSearch();
	}
	# -------------------------------------------------------
	/*/
	 *
	 */
	public static function tokenize(string $content) : array {
		$content = array_filter(array_map(function($v) {
			return preg_replace("/[^[:alnum:][:space:]]/u", '', $v);
		}, caTokenizeString($content)), function($x) { return strlen($x); });
		
		return $content;
	}
	# -------------------------------------------------------
	/*/
	 *
	 */
	public function importFile(BaseModel $object, BaseModel $rep, string $file) : ?bool {
		if(!($xml = simplexml_load_file($file))) { return null; }
		
		$output_dir = __CA_BASE_DIR__.'/newspaper_data';
		
		$object_id = $object->getPrimaryKey();
		$object_identifier = $object->get('ca_objects.idno');
		$rep_identifier = $rep->getPrimaryKey();
		
		foreach($xml->Layout->Page as $page) {
			$i = 1;
			$pnum = (int)$page['PHYSICAL_IMG_NR'];
			$pwidth = (float)$page['WIDTH'];
			$pheight = (float)$page['HEIGHT'];
			
			print "PAGE {$pnum} ({$pwidth}/{$pheight})\n";
			$acc = [];
			$page_content = [];
			foreach($page->PrintSpace->TextBlock as $block) {
				foreach($block->TextLine as $line) {
					foreach($line->String as $str) {
						$content = self::tokenize((string)mb_strtolower($str['CONTENT']));
						
						if(sizeof($content)) {
							foreach($content as $w) {
								$acc[$w][] = [
									'w' => sprintf("%0.3f", (float)$str['WIDTH']/$pwidth), 
									'h' => sprintf("%0.3f", (float)$str['HEIGHT']/$pheight), 
									'x' => sprintf("%0.3f", (float)$str['HPOS']/$pwidth), 
									'y' => sprintf("%0.3f", (float)$str['VPOS']/$pheight),
									'i' => $i
								];
								$i++;
							}
							$page_content[] = $w;
						}
					}
				}
			}
			
			$this->search->addPage($object, $rep, $pnum, join(' ', $page_content)) ;
			
			$page_data = [
				'object_id' => $object_id,
				'representation_id' => $rep_identifier,
				'idno' => $object_identifier,
				'locations' => $acc
			];
			
			if(!file_exists("{$output_dir}/{$rep_identifier}")) { mkdir("{$output_dir}/{$rep_identifier}"); }
			file_put_contents("{$output_dir}/{$rep_identifier}/".sprintf("%06d", $pnum).".json", json_encode($page_data));	
		}
		
		return true;
	}
	# -------------------------------------------------------
}