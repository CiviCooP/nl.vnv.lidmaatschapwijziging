<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Lidmaatschapwijziging_Form_LidmaatschapWijzigingContact extends CRM_Core_Form {
  public $_contactId;
  public $_display_name;
  
  public $_vnvinfoId;
  public $_werkgeverId;
  
  public $_values = array();
  
  public $_configContact = array();


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
    
    // redirect user after postProcess
    //$urlParams = 'reset=1&cid=' . $this->_contactId;
    //$session->pushUserContext(CRM_Utils_System::url('civicrm/lidmaatschapwijziging/contact', $urlParams));
    
    // get values
    $this->_configContact = CRM_Lidmaatschapwijziging_ConfigContact::singleton($this->_contactId);    
    $this->_values = $this->_configContact->getContact();
    
    // set display name
    $this->_display_name = $this->_values['display_name'];
    
    // set title
    CRM_Utils_System::setTitle('LidmaatschapWijziging - Contact - ' . $this->_values['display_name']);
    
    // set contact id
    $this->_values['contact_id'] = $this->_contactId;
            
    // change the default name like huppeldepup_35 to huppeldepup, this
    // ensures the we can use the know names for custom fields in the template like
    // huppeldepup and not the column_names like huppeldepup_35
    $values = $this->_configContact->getVnvInfoCustomValues();
    
    // set vnvn info id, is neede for update or insert in the postProccess
    if(isset($values['id']) and !empty($values['id'])){
      $this->_vnvinfoId = $values['id'];
    }
    
    foreach($this->_configContact->getVnvInfoCustomFields() as $key => $field){
      $this->_values[$field['name']] = $values[$field['column_name']];
    }
    
    $values = $this->_configContact->getWerkgeverCustomValues();
    
    // set werkgever id, is neede for update or insert in the postProccess
    if(isset($values['id']) and !empty($values['id'])){
      $this->_werkgeverId = $values['id'];
    }
    
    foreach($this->_configContact->getWerkgeverCustomFields() as $key => $field){
      $this->_values[$field['name']] = $values[$field['column_name']];
    }
    
    $currentEmployer = CRM_Contact_BAO_Relationship::getCurrentEmployer(array($this->_contactId));
    $defaults['current_employer_id'] = CRM_Utils_Array::value('org_id', $currentEmployer[$this->_contactId]);
    
    // assign values needed for the template
    $this->assign('contactId', $this->_contactId);
    $this->assign('employerDataURL', '/civicrm/ajax/rest?className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&context=contact&org=1&employee_id=' . $this->_contactId);
    $this->assign('currentEmployer', $this->_values['employer_id']);
    
    
  }
  
  /**
   * This function is called prior to building and submitting the form and after the preProcess
   */
  function setDefaultValues() {
    $defaults = array();
    $defaults = $this->_values;
    
    // current employer id
    $defaults['current_employer_id'] = trim($defaults['employer_id']);
    
    // datum in dienst
    if (isset($defaults['Datum_in_dienst'])) { 
      list($defaults['Datum_in_dienst']) = CRM_Utils_Date::setDateDefaults($defaults['Datum_in_dienst']); // list is needed or else it does not work
    } 
    
    return $defaults;
  }
  
  function buildQuickForm() {
    $this->_configContact = CRM_Lidmaatschapwijziging_ConfigContact::singleton($this->_contactId);
    
    // Contactdetails
    $this->add('hidden', 'contact_id', ts('Contact id'), '', true);
    $this->add('hidden', 'contact_type', ts('Contact type'), '', true);
    $this->add('hidden', 'current_employer_id', ts('Current empoyer id'), array('id' => 'current_employer_id'), false); // id is neede for the update trough the javascript
    $this->add('text', 'current_employer',  ts('Current empoyer'), '', false );
    $this->add('text', 'job_title', ts('Job title'), '', false );
    $this->add('text', 'source', ts('Source'), '', false );
    
    // VNV info 
    $vnvInfoFields = $this->_configContact->getVnvInfoCustomFieldsByName();
    $vnvInfoFieldsOptions = $this->_configContact->getVnvInfoCustomFieldsOptionValues();
    $vnvInfoFieldsNeeded = array('Lid_kandidaat_voldoet_aan_een_van_de_volgende_criteria', 'Lid_beroepsvereniging_voldoet_aan_een_van_de_volgende_criteria');
         
    foreach($vnvInfoFields as $name => $field){
      
      switch($name){
        
        case 'Lid_kandidaat_voldoet_aan_een_van_de_volgende_criteria':
          $options = array();
          foreach($vnvInfoFieldsOptions[$name] as $option_name => $option){
            $options[$option['value']] = ts($option['label']);
          }
                    
          $this->addRadio($name, ts($field['label']), $options, NULL, '<br/>', $field['is_required']);
          break;
          
        case 'Lid_beroepsvereniging_voldoet_aan_een_van_de_volgende_criteria':
          $options = array();
          foreach($vnvInfoFieldsOptions[$name] as $option_name => $option){
            $options[$option['value']] = ts($option['label']);
          }
                    
          $this->addRadio($name, ts($field['label']), $options, NULL, '<br/>', $field['is_required']);
          break;
      }
    }
    
    // Werkgever
    $werkgeverFields = $this->_configContact->getWerkgeverCustomFieldsByName();
    $werkgeverFieldsNeeded = array('Vl_type', 'Datum_in_dienst', 'Pers_code_let_', 'Postvak', 'persnr', 'Standplaats_Werkgever');
    
    foreach($werkgeverFields as $name => $field){
      switch($name){
        
        case 'Vl_type':
          $this->add('text', $name, ts($field['label']), '', $field['is_required']);
          break;
        
        case 'Datum_in_dienst':
          $this->addDate($name, ts($field['label']), $field['is_required']);
          break;
        
        case 'Pers_code_let_':
          $this->add('text', $name, ts($field['label']), '', $field['is_required']);
          break;
        
        case 'Postvak':
          $this->add('text', $name, ts($field['label']), '', $field['is_required']);
          break;
        
        case 'persnr':
          $this->add('text', $name, ts($field['label']), '', $field['is_required']);
          break;
        
        case 'Standplaats_Werkgever':
          $this->add('text', $name, ts($field['label']), '', $field['is_required']);
          break;
      }
    }
        
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Opslaan / Volgende'),
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
    $this->addFormRule(array('CRM_Lidmaatschapwijziging_Form_LidmaatschapWijzigingContact', 'myRules'));
  }

  /**
   * Here's our custom validation callback
   */
  static function myRules($values) {
    $errors = array();
        
    // check contact id
    $contact_id = CRM_Utils_Array::value('contact_id', $values);
    if(!isset($contact_id) or empty($contact_id)){ // contact id exists or empty
      $errors['contact_id'] = ts('Contact id bestaat niet of is leeg !');
    }else if(!is_numeric($contact_id)){ // contact id is not a number
      $errors['contact_id'] = ts('Contact id is geen nummer !');
    }
    
    // check current employer id
    $current_employer_id = trim(CRM_Utils_Array::value('current_employer_id', $values)); // needs to be trimed, there is by default a whitespace after the number
    /*if(!isset($current_employer_id) or empty($current_employer_id)){ // current employer id exists or empty
      $errors['current_employer'] = ts('Huidige werkgever bestaat niet of is leeg !');
    }else if(!is_numeric($current_employer_id)){ // current employer id is not a number
      $errors['current_employer'] = ts('Current employer id is not a n number !');
    }*/
    // current employer is not required
    if(!empty($current_employer_id) and !is_numeric($current_employer_id)){
      $errors['current_employer'] = ts('Huidige werkgever id is geen nummer !');
    }
    
    
    // check custom fields
    $configContact = CRM_Lidmaatschapwijziging_ConfigContact::singleton($contact_id);
    
    // Vnv Info
    // we do not need all the fields
    $vnvInfoFields = $configContact->getVnvInfoCustomFieldsByName();
    $vnvInfoFieldsNeeded = array('Lid_kandidaat_voldoet_aan_een_van_de_volgende_criteria', 'Lid_beroepsvereniging_voldoet_aan_een_van_de_volgende_criteria');
    
    $customFields = array();
    foreach ($vnvInfoFieldsNeeded as $key => $name){
      $customFields[$name] = $vnvInfoFields[$name];
    }
    
    // return can be empty if there is no error
    $errors = array_merge($errors, CRM_Lidmaatschapwijziging_ValidateCustomFields::Validate($customFields, $values));
    
    // Werkgever
    // we do not need all the fields
    $werkgeverFields = $configContact->getWerkgeverCustomFieldsByName();
    $werkgeverFieldsNeeded = array('Vl_type', 'Datum_in_dienst', 'Pers_code_let_', 'Postvak', 'persnr', 'Standplaats_Werkgever');
    
    $customFields = array();
    foreach ($werkgeverFieldsNeeded as $key => $name){
      $customFields[$name] = $werkgeverFields[$name];
    }
    // return can be empty if there is no error
    $errors = array_merge($errors, CRM_Lidmaatschapwijziging_ValidateCustomFields::Validate($customFields, $values));   
    
    return empty($errors) ? TRUE : $errors;
  } 
  
  function postProcess() {
    $values = $this->exportValues();
    
    $this->_configContact = CRM_Lidmaatschapwijziging_ConfigContact::singleton($this->_contactId);
        
    // set current_employer
    // current employer
    if (isset($values['current_employer_id']) and is_numeric($values['current_employer_id'])) {
      $values['current_employer'] = $values['current_employer_id'];
    }
    
    // api create contact
    try {    
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'id' => $this->_contactId,
        'contact_type' => $values['contact_type'],
        'current_employer' => $values['current_employer'],
        'job_title' => $values['job_title'],
        'source' => $values['source'],
      );

      // for the custom values in contact, the need to be custom_35
      foreach($this->_configContact->getVnvInfoCustomFields() as $key => $field){
        if(isset($values[$field['name']])){
          $params['custom_' . $field['id']] = $values[$field['name']];
        }
      }

      foreach($this->_configContact->getWerkgeverCustomFields() as $key => $field){
        if(isset($values[$field['name']])){
          $params['custom_' . $field['id']] = $values[$field['name']];
        }
      }

      $result = civicrm_api('Contact', 'create', $params);
      
      // check no error
      if(isset($result['is_error']) and !$result['is_error']){ // if there is no error   
        // set message
        $session = CRM_Core_Session::singleton();
        $session->setStatus(ts('%1 is opgeslagen !', $this->_display_name), ts('Lidmaatschap Wijziging - Contact'), 'success');

        // redirect user
        $url = CRM_Utils_System::url('civicrm/lidmaatschapwijziging/membership', 'reset=1&action=choose&cid=' . $this->_contactId);
        CRM_Utils_System::redirect($url);
        
      }else { // if there is a error
        // set message
        $session = CRM_Core_Session::singleton();
        $session->setStatus(ts('Er is een error: %1, %2 is niet opgeslagen !', array($result['error_message'], $this->_display_name)), ts('Lidmaatschap Wijziging - Contact'), 'error');

        // redirect user
        $url = CRM_Utils_System::url('civicrm/lidmaatschapwijziging/contact', 'reset=1&cid=' . $this->_contactId);
        CRM_Utils_System::redirect($url);
      }
      
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not create contact, '
        . 'error from Api Contact create: '.$ex->getMessage());
    }
  }
}
