<?php

/* ----------------------------------------------------------------------
 * objectHierarchyChangerPlugin.php
 * ----------------------------------------------------------------------
 * 
 * Software by Existo (http://www.exis.to)
 * Copyright 2019 Existo
 *
 * ----------------------------------------------------------------------
 */

class objectHierarchyChangerPlugin extends BaseApplicationPlugin
{

    var $opo_plugin_config = null;
    var $db = null;



    public function __construct($ps_plugin_path)
    {
        $this->description = _t('Este plugin permite a atualização do IDNO de objetos hierárquicos, respeitando as regras definidas pelo MultipartIDNumber ID numbering plug-in.');
        parent::__construct();

        $this->opo_plugin_config = Configuration::load($ps_plugin_path . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'objectHierarchyChanger.conf');
    }

    
    /**
     * Override checkStatus()
     * to return 'available' => true/false as defined in conf file
     * @return Array
     */
    public function checkStatus()
    {
        return array(
            'description' => $this->getDescription(),
            'errors'      => array(),
            'warnings'    => array(),
            'available'   => (bool) $this->opo_plugin_config->get('enabled')
        );
    }

    
    /**
     * Override getRoleActionList()
     * @return Array
     */
    public static function getRoleActionList()
    {
        return array();
    }

    
    /**
     * Hook to be executed before the Update
     * @param Array $pa_params
     */
    public function hookBeforeBundleUpdate(&$pa_params)
    {
		$item_id    = $pa_params['id'];
        $table_num  = $pa_params['table_num'];
        $table_name = $pa_params['table_name'];
        $instance   = $pa_params['instance'];
        
        $this->changeIDNO($instance);
    }
	
	// #2 Função adicionada por FRED em 11/2/2021
	// Ao carregar o formulário de edição de objeto para uma inserção ($vb_is_insert = !$t_instance->getPrimaryKey()), 
	// verifica se o sistema sabe criar um IDNO (no caso de uma inserção feita por um registro mãe). Se não sabe, 
	// cria um IDNO temporário formado pela palavra TEMP e por um uid (número único gerado automaticamente).
	
	public function hookEditItem(&$pa_params)
	{
		if ($pa_params['table_name'] == 'ca_objects')
		{
			$t_instance = $pa_params['instance'];
			
			if ($vb_is_insert = !$t_instance->getPrimaryKey())
			{
				$o_idno = $t_instance->getIDNoPlugInInstance();
				
				if (!$o_idno->htmlFormValue('idno', null, true))
				{
					$newIDNO = "TEMP_" . uniqid();
					$t_instance->set('idno', $newIDNO);
				}
			}
		}
		
		return true;
	}
	
	// FIM
    
    private function changeIDNO(&$instance)
    {
		// if there is no primary key, the instance is not loaded
        // (or is a new one being created?)
        if(!$instance->getPrimaryKey()) 
            return false;
        
		// the parent_id or the type_id has changed?
        // We need to rewrite the idno in two situations:
        // 1) when we change the hierarchy; and/or
        // 2) when we change the object type 
        if (!$instance->changed('parent_id') && !$instance->changed('type_id'))
            return false;
        
        
        // is a hierarchical record?
        if (!$instance->isHierarchical())
            return false;
        
        
        // load rules:
        $change_rules = $this->opo_plugin_config->get('change_idno_rules');
		if(!$change_rules || (!is_array($change_rules)) || (sizeof($change_rules)<1))
            return false;
        
        
        // set DB:
        $this->db = $instance->getDb();
        
        
        // process rules:
        foreach($change_rules as $rule_key => $rule)
        {
            // respect table options:
			if($instance->tableName() != $rule['table']) 
                continue;
            
            
            // respect restrictToTypes options:
			if($rule['restrictToTypes'] && is_array($rule['restrictToTypes']) && (sizeof($rule['restrictToTypes']) > 0)) 
            {
				if(!in_array($instance->getTypeCode(), $rule['restrictToTypes'])) 
                {
					Debug::msg("[changeIDNO()] skipping rule $rule_key because current record type ".$instance->getTypeCode()." is not in restrictToTypes");
					continue;
				}
			}
            
            
            
            // let's rock:
            // 1) cache some info from current and new data:
            
            $idnoField = $instance->getProperty('ID_NUMBERING_ID_FIELD'); // usually is 'idno'
            $currentID = $instance->getOriginalValue($instance->primaryKey());
            $currentIDNO = $instance->getOriginalValue($idnoField);
			
			//var_dump($currentID, $currentIDNO); exit();
            
            $currentParentID = $instance->getOriginalValue('parent_id');
            $currentParentData = BaseModel::getFieldValueArraysForIDs(array($currentParentID), $instance->tableName());
            $currentParentIDNO = $currentParentData[$currentParentID][$idnoField];
            
            $newParentID = $instance->get('parent_id');
            $newParentData = BaseModel::getFieldValueArraysForIDs(array($newParentID), $instance->tableName());
            $newParentIDNO = $newParentData[$newParentID][$idnoField];
            
            
            
            // 2) find what should be the new IDNO:
            $newIDNO = $this->_generateIDNO($instance, $newParentIDNO);
			
			$o_log = new KLogger('/var/www/html/app/plugins/objectHierarchyChanger', 6);
			$o_log->logInfo(_t("(%1): %2 ==> %3", $currentID, $currentIDNO, $newIDNO));
			
            // IMPORTANT!!!
            $instance->set($idnoField, $newIDNO);
            
            
            
            // 3) find the direct children:
            $children = $this->_updateChildrenIDNO($instance, $newIDNO);
            
            /*
            if (!$instance->rebuildAllHierarchicalIndexes()) {
                $instance->rebuildHierarchicalIndex();
            }
            //*/
            
            
            /*
            echo "<pre>";
            print_r("\n id: ".$currentID);
            print_r("\n ID_NUMBERING_ID_FIELD: ".$idnoField);
            
            //print_r("\n is_a: ");print_r( is_a($instance,'BaseModel') );
            //print_r("\n is_subclass_of: ");print_r( is_subclass_of($instance,'BaseModel') );
            print_r("\n authority / table: ".$instance->tableName());
            print_r("\n type: ".$instance->getTypeCode());
            echo "\n ";
            
            echo "\n CURRENT DATA:";
            print_r("\n idno: ".$currentIDNO);
            print_r("\n parent id: ".$currentParentID);
            print_r("\n parent idno: ".$currentParentIDNO);
            echo "\n ";
            //print_r("\n isHierarchical: ".$instance->isHierarchical());
            //print_r("\n getHierarchyType: ".$instance->getHierarchyType());
            
            //print_r("\n newIDNOData: ");print_r($newIDNOData);
            //print_r("\n currentChildIDs: ");print_r($currentChildIDs);
            //print_r("\n currentChildIDNOs: ");print_r($currentChildIDNOs);
            echo "\n ";
            
            
            //print_r("\n newParent['idno']: ");print_r($newParent);
            //echo "\n ";
            
            //echo "\n PREVIOUS DATA:";
            //print_r("\n idno: ".$instance->getOriginalValue('idno'));
            //print_r("\n object_id: ".$instance->getOriginalValue($instance->primaryKey()));
            //print_r("\n parent_id: ".$instance->getOriginalValue('parent_id'));
            // print_r("\n database: ");print_r($instance->getDb());
            // print_r("\n values: ");print_r($instance->getFieldValuesArray());
            //echo "\n ";
            
            echo "\n NEW DATA:";
            print_r("\n idno: ".$newIDNO);
            print_r("\n parent id: ".$newParentID);
            print_r("\n parent idno: ".$newParentIDNO);
            //print_r("\n object_id: ".$instance->get($instance->primaryKey()));
            //print_r("\n parent_id: ".$instance->get('parent_id'));
            
            echo "\n UPDATED DATA:";
            print_r("\n idno: ".$instance->get($idnoField));
            
            //print_r($instance);

            echo "</pre>";
            
            //*/
        }
        
        //exit();
    }
    
    
    private function _generateIDNO(&$instance, $parentIDNO)
    {
        // TODO: save also vs_idno_sort_field
        
        if (($idnoField = $instance->getProperty('ID_NUMBERING_ID_FIELD')) && ($vs_idno_sort_field = $instance->getProperty('ID_NUMBERING_SORT_FIELD')))
        {
            if ( $o_idno = $instance->getIDNoPlugInInstance() )
            {
                
                // define o registro como sendo "filho" do $parentIDNO recebido:
                $o_idno->isChild(true, $parentIDNO);
                
                
                // recupera o nome do último elemento do formato do IDNO;
                // precisamos do nome de qual será o elemento SERIAL para 
                // calcular corretamente sua contagem:
				
				// #1 Correção feita por FRED 8/1/2021
				// Acrescentado o parâmetro true para $pb_dont_mark_serial_value_as_used,
				// isto é, o próximo número da sequência não é "reservado" agora, mas só em
				// $o_idno->getNextValue
				//  $elements = $o_idno->htmlFormValuesAsArray( $idnoField );
				
                $elements = $o_idno->htmlFormValuesAsArray($idnoField, null, true);
				
				// FIM
				
				end($elements);
                $lastElementName = key($elements);
                
                $nextValue = $o_idno->getNextValue($lastElementName);
                				
                // o novo IDNO será o IDNO do parent, 
                // concatenado com a contagem encontrada:
                $newIDNO = $parentIDNO . $o_idno->getSeparator() . $nextValue;
                
                /*
                print_r( "<pre>=====\n" );
                print_r( "\n class: " );print_r( get_class($o_idno) );
                //print_r( "\n getFormat: " );print_r( $o_idno->getFormat() );
                //print_r( "\n getType: " );print_r( $o_idno->getType() );
                //print_r( "\n getFormats: " );print_r( $o_idno->getFormats() );
                
                print_r( "\n htmlFormValue: " );print_r( $o_idno->htmlFormValue( 'idno' ) );
                print_r( "\n htmlFormValuesAsArray: " );print_r( $o_idno->htmlFormValuesAsArray( 'idno' ) );
                print_r( "\n getSeparator: " );print_r( $o_idno->getSeparator() );
                //print_r( "\n getSortableValue: " );print_r( $o_idno->getSortableValue($instance->get($vs_idno_field)) );
                print_r( "\n" );
                
                print_r( "\n lastElementName: " );print_r( $lastElementName );
                print_r( "\n getNextValue: " );print_r( $nextValue );
                print_r( "\n" );
                
                print_r( "\n newIDNO: " );print_r( $newIDNO );
                
                print_r( "\n" );
                print_r( "\n\n=====</pre>" );
                //*/
                
                return $newIDNO;
            }

            
            /*
            if (($o_idno = $instance->getIDNoPlugInInstance()) && (method_exists($o_idno, 'getSortableValue')))
            { 
                // try to use plug-in's sort key generator if defined
                // $instance->set($vs_idno_sort_field, $o_idno->getSortableValue($instance->get($vs_idno_field)));
                $vs_idno_sort_field = $o_idno->getSortableValue($instance->get($vs_idno_field));
                return $vs_idno_sort_field;
            }
            //*/
            
            
            /*
            // TODO:
            // Create reasonable facsimile of sortable value since 
            // idno plugin won't do it for us
            $va_tmp = preg_split('![^A-Za-z0-9]+!', $instance->get($vs_idno_field));

            $va_output = array();
            $va_zeroless_output = array();
            foreach ($va_tmp as $vs_piece)
            {
                if (preg_match('!^([\d]+)!', $vs_piece, $va_matches))
                {
                    $vs_piece = $va_matches[1];
                }
                $vn_pad_len = 12 - mb_strlen($vs_piece);

                if ($vn_pad_len >= 0)
                {
                    if (is_numeric($vs_piece))
                    {
                        $va_output[] = str_repeat(' ', $vn_pad_len) . $va_matches[1];
                    }
                    else
                    {
                        $va_output[] = $vs_piece . str_repeat(' ', $vn_pad_len);
                    }
                }
                else
                {
                    $va_output[] = $vs_piece;
                }
                if ($vs_tmp = preg_replace('!^[0]+!', '', $vs_piece))
                {
                    $va_zeroless_output[] = $vs_tmp;
                }
                else
                {
                    $va_zeroless_output[] = $vs_piece;
                }
            }

            $instance->set($vs_idno_sort_field, join('', $va_output) . ' ' . join('.', $va_zeroless_output));
            //*/
        }
        
        return "ERR";
    }

    
    
    
    
    private function _updateChildrenIDNO(&$instance, $instanceIDNO)
    {
        $idnoField = $instance->getProperty('ID_NUMBERING_ID_FIELD'); // usually is 'idno'
        $instanceID = $instance->getOriginalValue($instance->primaryKey());
        // $instanceIDNO = $instance->get($idnoField);
        
        
        // gets only the direct children
        // the grandchildren will be processed recursively later...
        
        $childIDs = $instance->getHierarchyChildren($instanceID, array('idsOnly'=>true));
        //$childIDs = $instance->getHierarchyAsList($instanceID, array('idsOnly'=>true));
        
        
        // each child will be updated with the new IDNO, 
        // generated using the $instanceIDNO (the parent):
        if (count($childIDs)>0)
        {
            //$parentData = BaseModel::getFieldValueArraysForIDs(array($instanceID), $instance->tableName());
            //$parentIDNO = $parentData[$instanceID][$idnoField];
            //$parentIDNO = $idno;
            
            foreach ($childIDs as $cID)
            {
                $child = new ca_objects($cID);
                $newChildIDNO = $this->_generateIDNO($child, $instanceIDNO);
                
                // IMPORTANT!!!
                // $child->set($idnoField, $newChildIDNO);
                // $child->update();
                
                
                // optionally, we will use the DB directly...
                
                $queryResult = $this->db->query("
                    UPDATE ".$child->tableName()." 
                    SET {$idnoField} = '{$newChildIDNO}' 
                    WHERE (".$child->primaryKey()." = ?) 
                    ", intval($cID));
                
                // print_r( "<pre> UPDATE ".$child->tableName()." SET {$idnoField} = '{$newChildIDNO}' WHERE (".$child->primaryKey()." = ".intval($cID).") </pre>" );
                
                // we need to call it recursively, the hooks will NOT take care of it...
                // bacause we changed the IDNO diretly in the Database!
                $this->_updateChildrenIDNO($child, $newChildIDNO);
            }
            
        }
        
    }
    
    
    
    /*
    private function _getChildrenIDNOs(Array $childIDs, $primaryKeyName, $tableName)
    {
        if (count($childIDs)<1)
            return array();

        $childIDNOs = array();
        
        $queryResult = $this->db->query("
            SELECT {$primaryKeyName}, idno, parent_id 
            FROM {$tableName} 
            WHERE {$primaryKeyName} IN (".implode(',',$childIDs).") 
            ", array());

        while ($queryResult->nextRow()) 
            $childIDNOs[$queryResult->get($primaryKeyName)] = array(
                'idno' => $queryResult->get('idno'),
                'parent_id' => $queryResult->get('parent_id')
            );
        
        return $childIDNOs;
    }
    //*/
    
    /*
    private function _getDirectChildrenIDNOs(&$instance)
    {
        $childIDs = $instance->getHierarchyChildren($instance->getOriginalValue($instance->primaryKey()), array('idsOnly'=>true));
        $childIDNOs = array();

        $queryResult = $this->db->query("
            SELECT ".$instance->primaryKey().", idno, parent_id 
            FROM ".$instance->tableName()."
            WHERE ".$instance->primaryKey()." "
                . "IN (".implode(',',$childIDs).") 
        ", array());

        while ($queryResult->nextRow()) 
            $childIDNOs[$queryResult->get($instance->primaryKey())] = array(
                'idno' => $queryResult->get('idno'),
                'parent_id' => $queryResult->get('parent_id')
            );
        
        return $childIDNOs;
    }
    //*/

}

