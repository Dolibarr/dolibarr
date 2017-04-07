<?php

/*
 * Copyright (C) 2015	   Patrick DELCROIX     <pmpdelcroix@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
//global $db;     
global $langs;


/*
 * function to genegate a select list from a table, the showed text will be a concatenation of some 
 * column defined in column bit, the Least sinificative bit will represent the first colum 
 * 
 *  @param    object             	$db                 db Object to do the querry
 *  @param    string              	$table              table which the enum refers to (without prefix)
 *  @param    string              	$fieldValue         field of the table which the enum refers to
 *  @param    string              	$htmlName           name to the form select
 *  @param    string              	$selected           which value must be selected
 *  @param    string              	$selectparam          to add parameters to the select
 *  @param    array(string)             $addtionnalChoices    array of additionnal fields Array['VALUE']=string to show
 *  @return string                                                   html code
 */
 
function select_enum($table, $fieldValue,$htmlName,$selected='',$selectparam='',$addtionnalChoices=null){
global $langs;
global $db;
    if($table=='' || $fieldValue=='' || $htmlName=='' )
    {
        return 'error, one of the mandatory field of the function  select_enum is missing';
    }    
    $sql='SHOW COLUMNS FROM ';//llx_hr_event_time LIKE 'audience'";
    $sql.=MAIN_DB_PREFIX.$table.' WHERE Field="';
    $sql.=$fieldValue.'"';
    //$sql.= " ORDER BY t.".$field;
       
    dol_syslog('form::select_enum sql='.$sql, LOG_DEBUG);
    
    $resql=$db->query($sql);
    
    if ($resql)
    {
        $i=0;
         //return $table."this->db".$field;
        $num = $db->num_rows($resql);
        if($num)
        {
           
            $obj = $db->fetch_object($resql);
            if ($obj && strpos($obj->Type,'enum(')===0)
            {
                if(empty($selected) && !empty($obj->Default))$selected="'{$obj->Default}'";
                    $select.='<select class="flat minwidth200" id="'.$htmlName.'Select" name="'.$htmlName.'"'.$nodatarole.' '.$selectparam.'>';
                    $select.= '<option value="-1" '.(empty($selected)?'selected="selected"':'').">&nbsp;</option>\n";

                $enums= explode(',',substr($obj->Type, 5,-1));
                foreach ($enums as $enum){
                    $select.= '<option value="'.(substr($enum,1,-1)).'" ';
                    $select.=((substr($enum,1,-1)===$selected)?'selected="selected" >':'>');                    
                    $select.=$langs->trans(substr($enum,1,-1));          
                    $select.="</option>\n";
                }
                if($addtionnalChoices)foreach($addtionnalChoices as $value => $choice){
                     $select.='<option value="'.$value.'" '.(($selected==$value)?'selected':'').">{$choice}</option>\n";
                }
                $select.= '<option value="NULL" '.(($selected=='NULL')?'selected':'').">NULL</option>\n";
                $select.="</select>\n";
            }else{
                $select="<input selected=\"{$selected}\" id=\"{$htmlName} \" name=\"{$htmlName}\">";
            }
 
        }else{
                $select="<input selected=\"{$selected}\" id=\"{$htmlName} \" name=\"{$htmlName}\">";
        }
    }
    else
    {
        $error++;
        dol_print_error($db);
       $select="<input selected=\"{$selected}\" id=\"{$htmlName} \" name=\"{$htmlName}\">";
    }
      
      return $select;
    
 }
/*
 * function to genegate a select list from a table, the showed text will be a concatenation of some 
 * column defined in column bit, the Least sinificative bit will represent the first colum 
 * 
 *  @param    object             	$db                 db Object to do the querry
 *  @param    string              	$table                 table which the fk refers to (without prefix)
 *  @param    string              	$fieldValue         field of the table which the fk refers to, the one to put in the Valuepart
 *  @param    string              	$htmlName        name to the form select
 *  @param    string              	$fieldToShow1    first part of the concatenation
 *  @param    string              	$fieldToShow1    second part of the concatenation
 *  @param    string              	$selected            which value must be selected
 *  @param    string              	$separator          separator between the tow contactened fileds
*  @param    string              	$sqlTail              to limit per entity, to filter ...
*  @param    string              	$selectparam          to add parameters to the select
 *  @param    array(string)             $addtionnalChoices    array of additionnal fields Array['VALUE']=string to show

 *  @return string                                                   html code
 */
function select_generic($table, $fieldValue,$htmlName,$fieldToShow1,$fieldToShow2='',$selected='',$separator=' - ',$sqlTail='', $selectparam='', $addtionnalChoices=array('NULL'=>'NULL')){
     //
    //return 'tada';
    global $conf,$langs,$db;
    if($table=='' || $fieldValue=='' || $fieldToShow1=='' || $htmlName=='' )
    {
        return 'error, one of the mandatory field of the function  select_generic is missing';
    }
    $select="\n";
    if ($conf->use_javascript_ajax)
    {
        include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
        $comboenhancement = ajax_combobox($htmlName);
        $select.=$comboenhancement;
        $nodatarole=($comboenhancement?' data-role="none"':'');
    }
    $select.='<select class="flat minwidth200" id="'.$htmlName.'" name="'.$htmlName.'"'.$nodatarole.' '.$selectparam.'>';
    $sql='SELECT DISTINCT';
    $sql.=' t.'.$fieldValue;
    $sql.=' ,'.$fieldToShow1;
    if(!empty($fieldToShow2))
        $sql.=' ,'.$fieldToShow2;
    $sql.= ' FROM '.MAIN_DB_PREFIX.$table.' as t';
    if(!empty($sqlTail))
            $sql.=' '.$sqlTail;
    //$sql.= " ORDER BY t.".$field;
       
    dol_syslog('form::select_generic sql='.$sql, LOG_DEBUG);
    
    $resql=$db->query($sql);
   
    if ($resql)
    {
          // support AS in the fields ex $field1='CONTACT(u.firstname,' ',u.lastname) AS fullname'
        // with sqltail= 'JOIN llx_user as u ON t.fk_user=u.rowid'
        $starfields1=strpos($fieldToShow1,' AS ');
        if($starfields1>0)
            $fieldToShow1=  substr($fieldToShow1, $starfields1+4);
        $starfields2=strpos($fieldToShow2,' AS ');
        if($starfields2>0)
            $fieldToShow2=  substr($fieldToShow2, $starfields2+4);

        $select.= "<option value=\"-1\" ".(empty($selected)?"selected":"").">&nbsp;</option>\n";
        $i=0;
         //return $table."this->db".$field;
        $num = $db->num_rows($resql);
        while ($i < $num)
        {
            
            $obj = $db->fetch_object($resql);
            
            if ($obj)
            {
                    $select.= "<option value=\"".$obj->{$fieldValue}."\" ";
                    $select.=(($obj->{$fieldValue}==$selected)?"selected=\"selected\" >":">");                    
                    $select.=$obj->{$fieldToShow1};
                    if(!empty($fieldToShow2))
                         $select.=$separator.$obj->{$fieldToShow2};            
                    $select.="</option>\n";
            } 
            $i++;
        }
        if($addtionnalChoices)foreach($addtionnalChoices as $value => $choice){
            $select.= '<option value="'.$value.'" '.(($selected==$value)?'selected':'').">{$choice}</option>\n";
        }

        
    }
    else
    {
        $error++;
        dol_print_error($db);
       $select.= "<option value=\"-1\" selected=\"selected\">ERROR</option>\n";
    }
      $select.="</select>\n";
      return $select;
    
 }
 
 
/*
 * function to genegate a select list from a table, the showed text will be a concatenation of some 
 * column defined in column bit, the Least sinificative bit will represent the first colum 
 * 
 *  @param    object             	$db                 db Object to do the querry
 *  @param    string              	$table                 table which the fk refers to (without prefix)
 *  @param    string              	$fieldValue         field of the table which the fk refers to, the one to put in the Valuepart
 *  @param    string              	$selected           value selected of the field value column
 *  @param    string              	$fieldToShow1    first part of the concatenation
 *  @param    string              	$fieldToShow1    second part of the concatenation
 *  @param    string              	$separator          separator between the tow contactened fileds
 *  @param    string              	$sqlTail              to limit per entity, to filter ...

 *  @return string                                                   html code
 */
function print_generic($table, $fieldValue,$selected,$fieldToShow1,$fieldToShow2="",$separator=' - ',$sqltail="",$sqljoin=""){
   //return $table.$db.$field;
    global $db;
    if($table=="" || $fieldValue=="" || $fieldToShow1=='')
    {
        return "error, one of the mandatory field of the function  print_generic is missing";
    }else if (empty($selected)){
        return "NuLL";
    }
    
    $sql="SELECT";
    $sql.=" t.".$fieldValue;
    $sql.=" ,".$fieldToShow1;
    if(!empty($fieldToShow2))
        $sql.=" ,".$fieldToShow2;
    $sql.= " FROM ".MAIN_DB_PREFIX.$table." as t";
    if(!empty($sqljoin))
        $sql.=' '.$sqljoin;
    $sql.= " WHERE t.".$fieldValue."=".$selected;
    if(!empty($sqlTail))
            $sql.=' '.$sqlTail;
       
    dol_syslog("form::print_generic sql=".$sql, LOG_DEBUG);
    
    $resql=$db->query($sql);
    
    if ($resql)
    {
    // support AS in the fields ex $field1='CONTACT(u.firstname,' ',u.lastname) AS fullname'
     // with sqltail= 'JOIN llx_user as u ON t.fk_user=u.rowid'
     $starfields1=strpos($fieldToShow1,' AS ');
     if($starfields1>0){
         $fieldToShow1=  substr($fieldToShow1, $starfields1+4);
     }
     $starfields2=strpos($fieldToShow2,' AS ');
     if($starfields2>0){
         $fieldToShow2=substr($fieldToShow2, $starfields2+4);
      }

        $num = $db->num_rows($resql);
        if ( $num)
        {
            $obj = $db->fetch_object($resql);
            
            if ($obj)
            {
                            $select=$obj->{$fieldToShow1};
                            if(!empty($fieldToShow2))
                                 $select.=$separator.$obj->{$fieldToShow2};        
            }else{
                $select= "NULL";
            }
        }else{
            $select= "NULL";
        }
    }
    else
    {
        $error++;
        dol_print_error($db);
       $select.= "ERROR";
    }
      //$select.="\n";
      return $select;
 }

 /*
 * function to genegate a select list from a table, the showed text will be a concatenation of some 
 * column defined in column bit, the Least sinificative bit will represent the first colum 
 * 
 *  @param    object             	$db                 db Object to do the querry
 *  @param    int/array                       $userid             ID of the user you want to get the subordinate liste *  @param    int                       $userid             ID of the user you want to get the subordinate liste
 *  @param    int                       $entity             entity 
 *  @return   array                                         List of the subordinate ids  and level [[id][lvl]]                                          
 */