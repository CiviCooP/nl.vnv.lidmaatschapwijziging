<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Lidmaatschapwijziging_Form_LidmaatschapWijzigingRegistratieOpleidingBelangstelling extends CRM_Core_Form {
  public $_contactId;
  public $_display_name;
  
  public $_regiOplBelId;
  
  public $_configRegiOplBel = array();
  
  /**
   * This function is called prior to building and submitting the form
   */
  function preProcess() { 
    // check contact_id
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    if(empty($this->_contactId)){
      CRM_Core_Error::statusBounce(ts('Could not get a contact id.'), NULL, ts('Lidmaatschap Wijziging - Contact')); // this also redirects to the default civicrm page
    }
    
    // check for permissions
    $session = CRM_Core_Session::singleton();
    if (!CRM_Contact_BAO_Contact_Permission::allow($this->_contactId, CRM_Core_Permission::EDIT)) {
      CRM_Core_Error::statusBounce(ts('You do not have the necessary permission to edit this contact.'), NULL, ts('Lidmaatschap Wijziging - Contact')); // this also redirects to the default civicrm page
    }
    
    // get session
    $session = CRM_Core_Session::singleton();
    
    // get values
    $this->_configRegiOplBel = CRM_Lidmaatschapwijziging_ConfigRegistratieOpleidingBelangstelling::singleton($this->_contactId);
        
    $this->_values = $this->_configRegiOplBel->getContact();
    
    // set display name
    $this->_display_name = $this->_values['display_name'];
    
    // set title
    CRM_Utils_System::setTitle('LidmaatschapWijziging - Registratie Opleiding Belangstelling - ' . $this->_values['display_name']);
    
    // set contact id
    $this->_values['contact_id'] = $this->_contactId;
    
    // change the default name like huppeldepup_35 to huppeldepup, this
    // ensures the we can use the know names for custom fields in the template like
    // huppeldepup and not the column_names like huppeldepup_35
    $values = $this->_configRegiOplBel->getRegiOplBelCustomValues();
        
    // set vnvn info id, is neede for update or insert in the postProccess
    if(isset($values['id']) and !empty($values['id'])){
      $this->_regiOplBelId = $values['id'];
    }
    
    $this->_values['regioplbel_id'] = $this->_regiOplBelId;
        
    
    foreach($this->_configRegiOplBel->getRegiOplBelCustomFields() as $key => $field){
      $this->_values[$field['name']] = $values[$field['column_name']];
    }
  }
  
  /**
   * This function is called prior to building and submitting the form and after the preProcess
   */
  function setDefaultValues() {
    $defaults = array();
    $defaults = $this->_values;
    
    return $defaults;
  }
  
  function buildQuickForm() {
    $this->_configRegiOplBel = CRM_Lidmaatschapwijziging_ConfigRegistratieOpleidingBelangstelling::singleton($this->_contactId);
    
    // Contact
    $this->add('hidden', 'contact_id', ts('Contact id'), '', true);
    
    $this->add('hidden', 'regioplbel_id', ts('Registartie Opleiding en Belangstelling id'), '', true);
        
    // Registratie Opleiding Belangstelling
    $regiOplBelFields = $this->_configRegiOplBel->getRegiOplBelCustomFieldsByName();
    $regiOplBelFieldsOptions = $this->_configRegiOplBel->getRegiOplBelCustomFieldsOptionValues();
    $regiOplBelFieldsNeeded = array(
      'Luchtvaartopleiding', 
      'Vliegschool',
      'Vliegschool_anders_namelijk_',
      'Vooropleiding',
      'Vooropleiding__Anders',
      'Overige_opleiding',
      'Overige_opleiding__t_w_',
      'Nevenfuncties__Activiteiten__vroeger_',
      'Nevenfuncties__Activiteiten__huidige_',
      'Eventuele_vorige_werkgevers_en_werkervaring',
      'Aanvang_opleiding',
      'Afronding_opleiding',
      'Algemene_onderwerpen',
      'Commissies',
      'Groepscommissie',
    );
    
    foreach($regiOplBelFields as $name => $field){
      
      switch($name){
        
        case 'Luchtvaartopleiding':
          $options = array();
          foreach($regiOplBelFieldsOptions[$name] as $option_name => $option){
            $options[$option['value']] = ts($option['label']);
          }
                    
          $this->addRadio($name, ts($field['label']), $options, NULL, '', $field['is_required']);
          break;
          
        case 'Vliegschool':
        case 'Vooropleiding':
        case 'Overige_opleiding':
          $options = array();
          foreach($regiOplBelFieldsOptions[$name] as $option_name => $option){
            $options[$option['value']] = ts($option['label']);
          }
                    
          $this->addCheckBox($name, ts($field['label']), $options, NULL, NULL, $field['is_required'], NULL, '<br/>', true);
          
          break;
          
        case 'Vliegschool_anders_namelijk_':
        case 'Vooropleiding__Anders':
        case 'Nevenfuncties__Activiteiten__vroeger_':
        case 'Nevenfuncties__Activiteiten__huidige_':
        case 'Eventuele_vorige_werkgevers_en_werkervaring':
        case 'Eventuele_vorige_werkgevers_en_werkervaring':
          $this->add('textarea', $name, ts($field['label']), '', $field['is_required'] );
          break;
        
        case 'Algemene_onderwerpen':
        case 'Commissies':
        case 'Groepscommissie':
          $options = array();
          foreach($regiOplBelFieldsOptions[$name] as $option_name => $option){
            $options[$option['value']] = ts($option['label']);
          }
          
          $this->add('advmultiselect', $name, ts($field['label']), $options, $field['is_required'], array('size' => 5,
            'style' => 'width:150px',
            'class' => 'advmultiselect')
          );
          break;
        
        default:
          $this->add('text', $name, ts($field['label']), '', $field['is_required']);
      }
    }
    
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Opslaan / Klaar'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }
  
  /**
   * If your form requires special validation, add one or more callbacks here
   */
  function addRules() {
    $this->addFormRule(array('CRM_Lidmaatschapwijziging_Form_LidmaatschapWijzigingRegistratieOpleidingBelangstelling', 'myRules'));
  }

  /**
   * Here's our custom validation callback
   */
  static function myRules($values) {
    $errors = array();
         
    // check contact_id
    $contact_id = CRM_Utils_Array::value('contact_id', $values);
    if(!isset($contact_id) or empty($contact_id)){ // contact id exists or empty
      $errors['contact_id'] = ts('Contact id bestaat niet of is leeg !');
    }else if(!is_numeric($contact_id)){ // contact id is not a number
      $errors['contact_id'] = ts('Contact id is geen nummer !');
    }
    
    // check custom fields
    $configRegiOplBel = CRM_Lidmaatschapwijziging_ConfigRegistratieOpleidingBelangstelling::singleton($values['contact_id']);
        
    // we do not need all the fields
    $regiOplBelFields = $configRegiOplBel->getRegiOplBelCustomFieldsByName();
    $regiOplBelFieldsOptions = $configRegiOplBel->getRegiOplBelCustomFieldsOptionValues();
    $regiOplBelFieldsNeeded = array(
      'Luchtvaartopleiding', 
      'Vliegschool',
      'Vliegschool_anders_namelijk_',
      'Vooropleiding',
      'Vooropleiding__Anders',
      'Overige_opleiding',
      'Overige_opleiding__t_w_',
      'Nevenfuncties__Activiteiten__vroeger_',
      'Nevenfuncties__Activiteiten__huidige_',
      'Eventuele_vorige_werkgevers_en_werkervaring',
      'Aanvang_opleiding',
      'Afronding_opleiding',
      'Algemene_onderwerpen',
      'Commissies',
      'Groepscommissie',
    );
    
    echo('<pre>');
    print_r($values);
    print_r($regiOplBelFields);
    echo('</pre>');
    CRM_Utils_System::civiExit();
    
    $customFields = array();
    foreach ($regiOplBelFieldsNeeded as $key => $name){
      
      switch($name){
        case 'Vliegschool':
        case 'Vooropleiding':
        case 'Overige_opleiding':

          // if there is a value submitted, than the checkboxes with name exist and have a 
          // value of 1, the 1 must be the value of the chekcbox (the same as the name of the option)
          if(isset($values[$name]) and !empty($values[$name])){
            foreach($values[$name] as $option_name => $boolean){
              $values[$name][$option_name] = $option_name;
            }
          }
          break;

        case 'Algemene_onderwerpen':
        case 'Commissies':
        case 'Groepscommissie':

          // the advanced select have a array like [0 => label, 1 => label], and it
          // must be [name => name, name => name]
          if(isset($values[$name]) and !empty($values[$name])){
            foreach($values[$name] as $key => $label){
              // get the value from the option label
              foreach($regiOplBelFieldsOptions[$name] as $id => $option){
                if($label == $option['label']){ // if he found the correct option
                  $values[$name][$option['value']] = $option['value'];
                }
              }
            }
          }

          break;
      }
      
      $customFields[$name] = $regiOplBelFields[$name];
    }
    
    // return can be empty if there is no error
    $errors = array_merge($errors, CRM_Lidmaatschapwijziging_ValidateCustomFields::Validate($customFields, $values));  
        
    return empty($errors) ? TRUE : $errors;
  } 
  
  function postProcess() {
    $values = $this->exportValues();
        
    $this->_configRegiOplBel = CRM_Lidmaatschapwijziging_ConfigRegistratieOpleidingBelangstelling::singleton($this->_contactId);
    
    // api create custom value
    try {      
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'entity_id' => $this->_contactId,
      );
      
      // for the custom values, the need to be like custom_35, and
      // other ajustments for checkboxes and advanced select
      $regiOplBelFields = $this->_configRegiOplBel->getRegiOplBelCustomFieldsByName();
      $regiOplBelFieldsOptions = $this->_configRegiOplBel->getRegiOplBelCustomFieldsOptionValues();
            
      foreach($regiOplBelFields as $key => $field){
        switch($field['name']){
          case 'Vliegschool':
          case 'Vooropleiding':
          case 'Overige_opleiding':
          
            // if there is a value submitted, than the checkboxes with name exist and have a 
            // value of 1, the 1 must be the value of the chekcbox (the same as the name of the option)
            if(isset($values[$field['name']]) and !empty($values[$field['name']])){
              foreach($values[$field['name']] as $option_name => $boolean){
                $params['custom_' . $field['id']][$option_name] = $option_name;
              }
            }
            break;

          case 'Algemene_onderwerpen':
          case 'Commissies':
          case 'Groepscommissie':
            
            // the advanced select have a array like [0 => label, 1 => label], and it
            // must be [name => name, name => name]
            if(isset($values[$field['name']]) and !empty($values[$field['name']])){
              foreach($values[$field['name']] as $key => $label){
                // get the value from the option label
                foreach($regiOplBelFieldsOptions[$field['name']] as $id => $option){
                  if($label == $option['label']){ // if he found the correct option
                    $params['custom_' . $field['id']][$option['value']] = $option['value'];
                  }
                }
              }
            }
            
            break;
            
          default:
            $this->regiOplBelCustomValues[$field] = $value;
            if(isset($values[$field['name']])){
              $params['custom_' . $field['id']] = $values[$field['name']];
            }
        }
      }
      
      echo('$params: <pre>');
      print_r($params);
      echo('</pre>');
      
      $result = civicrm_api('CustomValue', 'create', $params);
      
      echo('$result: <pre>');
      print_r($result);
      echo('</pre>');
      
      CRM_Utils_System::civiExit();
      
      // check no error
      if(isset($result['is_error']) and !$result['is_error']){ // if there is no error   
        // set message
        $session = CRM_Core_Session::singleton();
        $session->setStatus(ts('%1 is opgeslagen !', $this->_display_name), ts('Lidmaatschap Wijziging - Registratie Opleiding Belasngstelling'), 'success');

        // redirect user
        $url = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $this->_contactId);
        CRM_Utils_System::redirect($url);
        
      }else { // if there is a error
        // set message
        $session = CRM_Core_Session::singleton();
        $session->setStatus(sprintf(ts('%s is niet opgeslagen !'), $this->_display_name), ts('Lidmaatschap Wijziging - Registratie Opleiding Belasngstelling'), 'error');

        // redirect user
        $url = CRM_Utils_System::url('civicrm/lidmaatschapwijziging/registratieopleidingbelangstelling', 'reset=1&cid=' . $this->_contactId);
        CRM_Utils_System::redirect($url);
      }      
      
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not create registratie opleiding belangstelling, '
        . 'error from Api CustomValue create: '.$ex->getMessage());
    }        
        
    //parent::postProcess();
  }  
}
