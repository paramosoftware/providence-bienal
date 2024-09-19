<?php
/* ----------------------------------------------------------------------
 * app/controllers/HierarchyLookupController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Class by Existo Studio
 * Copyright 2017 Existo
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2016 Whirl-i-Gig
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
 *http://arquivo.fbsp.org.br/index.php/lookup/HierarchyLookup/get?id=258:0
 * ----------------------------------------------------------------------
 */
 	require_once(__CA_LIB_DIR__."/Db.php");
	require_once(__CA_LIB_DIR__.'/Controller/ActionController.php');
 	
	class HierarchyLookupController extends ActionController {

 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			
 			parent::__construct($po_request, $po_response, $pa_view_paths);
			
 		}
 		
		# -------------------------------------------------------
 		/**
 		 *
 		 */ 
 		
		public function __call($ps_function, $pa_args) {
			
 		}
		
		public function objects( $pa_args ) {
			
			header("Content-type: application/json");
			
			$db = new Db();
			
			$id = $this->request->getParameter('id', pString);
			
			$ids = explode( ";" , $id );
			
			$response = array();
			
			$limit = $this->request->getParameter('max', pString) ? $this->request->getParameter('max', pString ) : 50000;
			
			foreach( $ids as $id ) {
				
				$param_id = explode(":", $id );				
				$object_id = (int)$param_id[0];
				$offset = (int)$param_id[1];
				$offset = (int)$offset > 0 ? "OFFSET " . $offset : "";
				
				$result = $db->query("
					SELECT ca_objects.object_id AS objectId, ca_objects.idno, ca_objects.parent_id, (SELECT COUNT(ca_objects.idno) FROM ca_objects WHERE ca_objects.parent_id = objectId) AS children_count , ca_list_item_labels.name_singular, ca_object_labels.name
					FROM ca_objects							
					INNER JOIN ca_list_items
					ON ca_objects.type_id=ca_list_items.item_id
					INNER JOIN ca_list_item_labels 
					ON ca_list_item_labels.item_id = ca_list_items.item_id
					INNER JOIN ca_object_labels
					ON ca_object_labels.object_id = ca_objects.object_id							
					WHERE ca_objects.parent_id = $object_id
					AND ca_objects.type_id IN (23,24,25,26,8202)
					AND ca_list_item_labels.locale_id = 13
					AND ca_object_labels.locale_id = 13
					AND ca_objects.deleted = 0
					AND ca_objects.access = 1
					ORDER BY ca_object_labels.name
					LIMIT $limit
					$offset
				");
				
				$c = 0;	
				$sort_order = array();			
				while( $result->nextRow() ) {
					
					$c++;		
					$row_object_idno = strtolower( $result->get('ca_objects.idno') );	
					$row_object_id = $result->get('objectId');	
					$count_children = 0;
					$name = $row_object_idno . "_" . $row_object_id;	
					$data = array(
						"object_id" => $result->get('objectId'),
						"item_id" => $result->get('objectId'),
						"object_type" => $result->get('ca_list_item_labels.name_singular'),
						"idno" => $result->get('ca_objects.idno'),
						"name" => $result->get('ca_object_labels.name'),
						"parent_id" => $result->get('ca_objects.parent_id'),
						"children" => $result->get('children_count'),
					);					
					$sort_order[] = $name;
					$response[ $id ][ $name ] = $data;
					
				}
				
				$response[ $id ]["_sortOrder"] = $sort_order;
				$response[ $id ]["_primaryKey"] = "object_id";
				$response[ $id ]["_itemCount"] = $c;
				
			}
			
			print json_encode( $response );
			
		}
		
		public function occurrences( $pa_args ) {
			
			header("Content-type: application/json");
			
			$db = new Db();
			
			$id = $this->request->getParameter('id', pString);
			
			$ids = explode( ";" , $id );
			
			$response = array();
			
			$limit = $this->request->getParameter('max', pString) ? $this->request->getParameter('max', pString ) : 50000;
			
			foreach( $ids as $id ) {
				
				$param_id = explode(":", $id );				
				$occurrence_id = (int)$param_id[0];
				$offset = (int)$param_id[1];
				$offset = (int)$offset > 0 ? "OFFSET " . $offset : "";
				
				$result = $db->query("
					SELECT ca_occurrences.occurrence_id AS occurrenceId, ca_occurrences.idno, ca_occurrences.parent_id, (SELECT COUNT(ca_occurrences.idno) FROM ca_occurrences WHERE ca_occurrences.parent_id = occurrenceId) AS children_count , ca_list_item_labels.name_singular, ca_occurrence_labels.name
					FROM ca_occurrences							
					INNER JOIN ca_list_items
					ON ca_occurrences.type_id=ca_list_items.item_id
					INNER JOIN ca_list_item_labels 
					ON ca_list_item_labels.item_id = ca_list_items.item_id
					INNER JOIN ca_occurrence_labels
					ON ca_occurrence_labels.occurrence_id = ca_occurrences.occurrence_id							
					WHERE ca_occurrences.parent_id = $occurrence_id AND ca_list_item_labels.locale_id = 13 AND ca_occurrence_labels.locale_id = 13 AND ca_occurrence_labels.is_preferred = 1
					ORDER BY ca_occurrence_labels.name ASC
					LIMIT $limit
					$offset
				");
				
				$c = 0;	
				$sort_order = array();			
				while( $result->nextRow() ) {
					
					$c++;		
					$row_object_idno = strtolower( $result->get('ca_occurrences.idno') );	
					$row_object_id = $result->get('occurrenceId');	
					$count_children = 0;
					$name = $row_object_idno . "_" . $row_object_id;	
					$data = array(
						"occurrence_id" => $result->get('occurrenceId'),
						"item_id" => $result->get('occurrenceId'),
						"occurrence_type" => $result->get('ca_list_item_labels.name_singular'),
						"idno" => $result->get('ca_occurrences.idno'),
						"name" => $result->get('ca_occurrence_labels.name'),
						"parent_id" => $result->get('ca_occurrences.parent_id'),
						"children" => $result->get('children_count'),
					);					
					$sort_order[] = $name;
					$response[ $id ][ $name ] = $data;
					
				}
				
				$response[ $id ]["_sortOrder"] = $sort_order;
				$response[ $id ]["_primaryKey"] = "occurrence_id";
				$response[ $id ]["_itemCount"] = $c;
				
			}
			
			print json_encode( $response );
			
		}

	}