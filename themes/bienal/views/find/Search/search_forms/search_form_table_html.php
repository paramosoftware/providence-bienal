<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Search/search_forms/search_form_table_html.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
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
 
	$va_form_element_list = $this->getVar('form_elements');


	// #1 Recuperando $va_hierarchy_elements (elementos selecionados dos campos de busca por nível hierárquico)
	// de BaseAdvancedSearchController.php para preencher os campos de busca
	/// FRED 5/2/2021
	
	$va_hierarchy_elements = $this->getVar('hierarchy_elements');
	
	// FIM #1

	
	$va_settings = $this->getVar('settings');
	if (!($vn_num_columns = $va_settings['form_width'])) { $vn_num_columns = 2; }
	
	print "<div class='searchFormLineModeContainer'>
<table>";
	
	$vn_c = 0;
	foreach($va_form_element_list as $vn_index => $va_element) {
		if ($vn_c == 0) {
			print "<tr valign='top'>\n";
		}
		
		print "<td class='searchFormGroupElementModeElement'><div class='searchFormLineModeElementLabel'>".$va_element['label']."</div>\n".$va_element['element']."</td>\n";
	
		if ($vn_c == ($vn_num_columns - 1)) {
			$vn_c = 0;
			print "</tr>\n";
			continue;
		}
	
		$vn_c++;
	}
	if ($vn_c != ($vn_num_columns - 1)) {
		print "</tr>\n";
	}
?>

<?php
	if ( $this->getVar('tablename') == "ca_objects" ) 
	{
?>
	<tr>
		<td class="campo" style="margin-top:5px; margin-left:2px">
			
			<div class="searchFormLineModeElementLabel">
				Em um nível hierárquico
			</div>
			<div>
				
				<!-- #3 Cada um desses campos <select> recebeu um valor de name="parent_id_x" para ser recuperado depois do POST do formulário.
				FRED 5/2/2021
				-->
				
				<select id="hierarchyselecter1" name="parent_id_1" style="width:250px;display:block !important;margin-bottom:10px !important" onchange="hierarchyfinder_find(this)" data-level="1">
					<option>carregando...</option>
				</select>
				<select id="hierarchyselecter2" name="parent_id_2" disabled="disabled" style="opacity:.2;width:250px;display:block !important;margin-bottom:10px !important" onchange="hierarchyfinder_find(this)" data-level="2"></select>
				<select id="hierarchyselecter3" name="parent_id_3" disabled="disabled" style="opacity:.2;width:250px;display:block !important;margin-bottom:10px !important" onchange="hierarchyfinder_find(this)" data-level="3"></select>
				<select id="hierarchyselecter4" name="parent_id_4" disabled="disabled" style="opacity:.2;width:250px;display:block !important;margin-bottom:10px !important" onchange="hierarchyfinder_find(this)" data-level="4"></select>
				
				<!-- FIM #3 -->
				
				<!--
				#5 Ao atributo name de cada um desses campos foi adicionado o nome da tabela "ca_objects"
				FRED 5/2/2021
				-->
				
				<input type="hidden" name="ca_objects.parent_id" id="parent_id" class="hierarchyselectervalue" />       
				<input type="hidden" name="ca_objects.parent_id_label" value="nível hierárquico" />
				
				<!-- FIM #5 -->
			</div>
			
			<script>
			
				function hierarchyfinder_find( $obj ) {
					var selecter = $( $obj );
					var value = selecter.val();
					var level = Number(selecter.data("level"));
					var populate = $("#hierarchyselecter" + (level+1) );
					hierarchyfinder_clear(level+1);
					populate.prop('disabled', 'disabled');
					populate.css("opacity",".2");					
					populate.find('option').remove().end().append('<option value="">carregando...</option>').val('');				
					$(".hierarchyselectervalue").val( value );					
					
					//#6
					hierarchyfinder_get( value , populate, null );
				}
				function hierarchyfinder_disable( $obj ) {
					$obj.prop('disabled', 'disabled');
					$obj.css("opacity",".2");					
					$obj.find('option').remove().end().append('<option value="">-</option>').val('');
				}
				function hierarchyfinder_clear( $level ) {
					for ( var i = $level+1 ; i <= 5 ; i++ ) {
						hierarchyfinder_disable( $("#hierarchyselecter" + i ) );
					}

				}							
				
				//#7
				function hierarchyfinder_get( $parent_id , $selecter, $selected_item ) 
				{
					//#8
					u = "/ca2/service.php/HierarchyLookup/objects?id=" + $parent_id;
					
					$.ajax({
						url: u ,
						dataType: 'json',
						method:'GET',
						success: function( $data ) {
							
							//#9
							hierarchyfinder_populate( $data , $parent_id , $selecter, $selected_item );
						}
					});
					
				}
				
				//#10
				function hierarchyfinder_populate( $data , $parent_id , $selecter, $selected_item ) {

					$data = $data[ $parent_id ];								
					
					//#11
					if ( $data["_itemCount"] > 0 ) {
						$selecter.prop('disabled', false);
						$selecter.css("opacity","1");		
						$selecter.find("option:first-child").html("-").val("");		
						$.each( $data , function() {
							var o = this;							
							if ( o["name"] ) $selecter.append($("<option />").val( o["object_id"] ).text( o["name"] ));
						});	
						// #12 Adicionado para selecionar o item do campo buscado na última
						// busca e carregar o nível inferior
						// FRED 5/2/2021
						
						if ( ($selected_item != null) && ($selected_item != '') )
						{
							$selecter.val($selected_item);
							
							var level = Number($selecter.data("level"));
							var populate = $("#hierarchyselecter" + (level+1) );
							
							populate.append('<option value="">-</option>').val('');
							
							if (arrHierarchyElements.length > (level))						
								hierarchyfinder_get( $selected_item , populate, arrHierarchyElements[level] );
							else
								hierarchyfinder_get( $selected_item , populate, null );
						}
						
						/// FIM #12
					} else {
						$selecter.find("option:first-child").html("não há mais registros");
					}
				}
				
				//#13 Criação de uma variável para armazenar os elementos selecionados de todos os campos
				var arrHierarchyElements = new Array();
			</script>
			
			<?php
			// #14 Adição de código
			// Adição em arrHierarchyElements de todos os elementos selecionados ($va_hierarchy_elements) nos campos 
			// de nível hierárquico 
			
			foreach ($va_hierarchy_elements as $v_hierarchy_element)
			{
			?>

				<script>
					arrHierarchyElements.push('<?php print $v_hierarchy_element; ?>');
				</script>
				
			<?php
			}
			
			// FIM #14
			?>
			
			<!-- #15 Alteração de código
			Se existem itens selecionados nos campos hierárquicos (arrHierarchyElements.length > 0),
			passa o primeiro valor para hierarchyfinder_get (arrHierarchyElements[0]), senão passa null
			--> 
			
			<script>
				if (arrHierarchyElements.length > 0)
					hierarchyfinder_get( 1 , $("#hierarchyselecter1"), arrHierarchyElements[0] );
				else
					hierarchyfinder_get( 1 , $("#hierarchyselecter1"), null );
			</script>
			
			<!-- FIM #15 -->
		</td>
	</tr>

<?php
}

print "</table></div>\n";

?>

<script>

	$("#ca_occurrence_labels_name").autocomplete({
		source: function( $request, $response ) {
			$.ajax( {
				url: "/ca2/service.php/Find/ca_occurrences?q=ca_occurrences.preferred_labels.name:" + $request.term + "&limit=10",
				success: function( $data ) {
					
					var r = [];
					for ( var i in $data["results"] ) {
						r.push({
							id:"id_" + $data["results"][i]["id"],
							label:$data["results"][i]["display_label"],
							value:$data["results"][i]["display_label"]
						});
					}
					$response( r );
				}
			});
		},
		open: function( $event , $ui ) {
			//
		},
		minLength: 3,
		select: function( $event, $ui ) {					
			$event.preventDefault();
			$("#ca_occurrence_labels_name").val( $ui.item.value );
		}
	});
		
	$("#ca_entity_labels_displayname").autocomplete({
		source: function( $request, $response ) {
			$.ajax( {
				url: "/ca2/service.php/Find/ca_entities?q=ca_entities.preferred_labels.displayname:" + $request.term + "&limit=10",
				success: function( $data ) {
					
					var r = [];
					for ( var i in $data["results"] ) {
						r.push({
							id:"id_" + $data["results"][i]["id"],
							label:$data["results"][i]["display_label"],
							value:$data["results"][i]["display_label"]
						});
					}
					$response( r );
				}
			});
		},
		open: function( $event , $ui ) {
			//
		},
		minLength: 3,
		select: function( $event, $ui ) {					
			$event.preventDefault();
			$("#ca_entity_labels_displayname").val( $ui.item.value );
		}
	});
	
	$("#ca_place_labels_name").autocomplete({
		source: function( $request, $response ) {
			$.ajax( {
				url: "/ca2/service.php/Find/ca_places?q=ca_places.preferred_labels.name:" + $request.term + "&limit=10",
				success: function( $data ) {
					
					var r = [];
					for ( var i in $data["results"] ) {
						r.push({
							id:"id_" + $data["results"][i]["id"],
							label:$data["results"][i]["display_label"],
							value:$data["results"][i]["display_label"]
						});
					}
					$response( r );
				}
			});
		},
		open: function( $event , $ui ) {
			//
		},
		minLength: 3,
		select: function( $event, $ui ) {					
			$event.preventDefault();
			$("#ca_place_labels_name").val( $ui.item.value );
		}
	});

</script>