<?php
/**
 * Class following Singleton pattern for specific extension configuration
 *
 * @author Jan-Derek (CiviCooP) <j.vos@bosqom.nl>
 */
class CRM_Lidmaatschapwijziging_ValidateCustomFields {
    
  public static function Validate($customFields, $values){
    $errors = array();
    
    echo('Validate:<pre>');
    print_r($customFields);
    print_r($values);
    echo('</pre>');
    
    // loop through the custom fields and check if the data_type matches the value
    foreach($customFields as $name => $field){
      
      // look first for the html_type
      switch($field['html_type']){
        // do nothing
        case 'Radio':
        case 'CheckBox':
        case 'TextArea':
        case 'AdvMulti-Select':
          
          break;
        
        case 'Text':
          switch ($field['data_type']){
            case 'String':
              // check lenght
              if(strlen($values[$name]) > $field['text_length']){
                $errors[$name] = ts('Te veel karakters (max %1)', array(1 => $field['text_length']));
              }
              break;
            case 'Int':
              // check lenght
              if(strlen($values[$name]) > $field['text_length']){
                $errors[$name] = ts('Te veel karakters (max %1)', array(1 => $field['text_length']));
              }
              
              // check if is int
              if(!is_numeric($values[$name])){
                $errors[$name] = ts('Het is geen nummer !');
              }
              break;
          }
      }
    }
    return $errors;
  }
}