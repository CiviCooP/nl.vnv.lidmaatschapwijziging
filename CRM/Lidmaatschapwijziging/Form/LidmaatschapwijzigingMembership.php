<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Lidmaatschapwijziging_Form_LidmaatschapwijzigingMembership extends CRM_Core_Form {
  public $_contactId;
  public $_display_name;
  
  public $_request = 'choose';
  public $_values = array();
  
  public $_membershipId;
  
  public $_configMembership = array();
  
  /**
   * This function is called prior to building and submitting the form
   */
  function preProcess() {    
    // check contact_id
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    if(empty($this->_contactId)){
      CRM_Core_Error::statusBounce(ts('Could not get a contact id.'), NULL, ts('Lidmaatschap Wijziging - Contact')); // this also redirects to the default civicrm page
    }
    
    // check for edit permission
    if (!CRM_Core_Permission::checkActionPermission('CiviMember', 'update')) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
    }
    
    // get request
    $this->_request = CRM_Utils_Request::retrieve('request', 'String', $this, FALSE, 'choose');
    
    // get session
    $session = CRM_Core_Session::singleton();
    
    // get values
    $this->_configMembership = CRM_Lidmaatschapwijziging_ConfigMembership::singleton($this->_contactId);
    $this->_values = $this->_configMembership->getContact();
    
    // set display name
    $this->_display_name = $this->_values['display_name'];
    
    // set contact id
    $this->_values['contact_id'] = $this->_contactId;
    
    // set request 
    $this->_values['request'] = $this->_request;
    
    // set title
    CRM_Utils_System::setTitle('LidmaatschapWijziging - Lidmaatschap - ' . $this->_values['display_name']);
    
    // request
    if('choose' == $this->_request){
      
    }
    
    if('update' == $this->_request){
      // get membership id
      $this->_membershipId = CRM_Utils_Request::retrieve('membership_id', 'Positive', $this);
      $this->_values['membership_id'] = $this->_membershipId;
      
      // get membership
      $membership = $this->_configMembership->getMembership($this->_membershipId);
      $this->_values = array_merge($this->_values, $membership);
      
      // get source, source is overritten by the contact source and not the membership source, i
      // override it here
      $this->_values['source'] = $membership['source'];

      // This is diffrent from the LidmaatschapWijzigingContact because the custom field values are already send with the membership
      // We have to set the _values of the custom field like huppeldepup and not custom_35, so we
      // loop through the custom field and change the custom_35 values to huppeldepup
      foreach($this->_configMembership->getMembershipCustomFields() as $key => $field){
        $this->_values[$field['name']] = $this->_values['custom_' . $field['id']];
      }
    }
  }
  
  /**
   * This function is called prior to building and submitting the form and after the preProcess
   */
  function setDefaultValues() {
    $defaults = array();
    $defaults = $this->_values;
    
    if('update' == $this->_request){
      // start date
      if (isset($defaults['start_date'])) { 
        list($defaults['start_date']) = CRM_Utils_Date::setDateDefaults($defaults['start_date']); // list is needed or else it does not work
      } 

      // end date
      if (isset($defaults['end_date'])) { 
        list($defaults['end_date']) = CRM_Utils_Date::setDateDefaults($defaults['end_date']); // list is needed or else it does not work
      } 
    }
    
    return $defaults;
  }
  
  function buildQuickForm() {
    $this->_configMembership = CRM_Lidmaatschapwijziging_ConfigMembership::singleton($this->_contactId);

    // Contact
    $this->add('hidden', 'contact_id', ts('Contact id'), '', true);
    
    // Request
    $this->add('hidden', 'request', ts('Request'), '', true);
    
    if('choose' == $this->_request){
      
      // memberships
      $options = array();
      foreach ($this->_configMembership->getMemberships() as $id => $membership){        
        $options[$membership['id']] = $membership['membership_name'] . ', ' . CRM_Utils_Date::customFormat($membership['start_date']);
        if(isset($membership['end_date']) and !empty($membership['end_date'])){ 
          $options[$membership['id']] .= ', ' . CRM_Utils_Date::customFormat($membership['end_date']);
        }
        
        if(isset($membership['status']) and !empty($membership['status'])){ 
          $options[$membership['id']] .= ', ' . ts($membership['status']);
        }
        
        if(isset($membership['source']) and !empty($membership['source'])){ 
          $options[$membership['id']] .= ', ' . ts($membership['source']);
        }
      }
      $this->add('select', 'membership_id',  ts('Membership'), $options, true);
      
      $this->addButtons(array(
        array(
          'type' => 'submit',
          'name' => ts('Kies / Volgende'),
          'isDefault' => TRUE,
        ),
      ));
    }
    
    if('update' == $this->_request){      
      // Membership id
      $this->add('hidden', 'membership_id', ts('Membership id'), '', true);
      
      // Membership type id
      $options = array();
      foreach ($this->_configMembership->getMembershipTypes() as $id => $type){
        $options[$type['id']] = $type['name'];
      }
      $this->add('select', 'membership_type_id',  ts('Membership type'), $options, true);

      $this->add('text', 'source', ts('Source'), '', false );
      $this->addDate('start_date', ts('Start date'), true);
      $this->addDate('end_date', ts('End date'), false);
      $this->add('checkbox', 'is_override', ts('Override status'));

      // Membership status
      $options = array();
      foreach ($this->_configMembership->getMembershipStatus() as $id => $status){
        $options[$status['id']] = $status['name'];
      }    
      $this->add('select', 'status_id',  ts('Membership status'), $options, false, array('disabled' => 'disabled'));

      // Lidmaatschap - Maatschappij
      $lidmaatschapMaatschappijFields = $this->_configMembership->getMembershipCustomFieldsByName();
      $lidmaatschapMaatschappijFieldsOptions = $this->_configMembership->getMembershipCustomFieldsOptionValues();
      $lidmaatschapMaatschappijFieldsNeeded = array('Maatschappij_lid', 'Maatschappij_anders');

      foreach($lidmaatschapMaatschappijFields as $name => $field){
        switch($name){

          case 'Maatschappij_lid':
            $options = array();
            foreach($lidmaatschapMaatschappijFieldsOptions[$name] as $option_name => $option){
              $options[$option['value']] = ts($option['label']);
            } 
            $this->addRadio($name, ts($field['label']), $options, NULL, '<br/>', $field['is_required']);
            break;

          case 'Maatschappij_anders':
            $this->add('text', $name, ts($field['label']), '', $field['is_required'] );
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
    }
    
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
    $this->addFormRule(array('CRM_Lidmaatschapwijziging_Form_LidmaatschapwijzigingMembership', 'myRules'));
  }

  /**
   * Here's our custom validation callback
   */
  static function myRules($values) {
    $errors = array();
    
    // Default checks
    // check contact id
    $contact_id = CRM_Utils_Array::value('contact_id', $values);
    if(!isset($contact_id) or empty($contact_id)){ // contact id exists or empty
      $errors['contact_id'] = ts('Contact id bestaat niet of is leeg !');
    }else if(!is_numeric($contact_id)){ // contact id is not a number
      $errors['contact_id'] = ts('Contact id is geen nummer !');
    }
    
    // check request
    $request = CRM_Utils_Array::value('request', $values);
    if(!isset($request) or empty($request)){ // contact id exists or empty
      $errors['request'] = ts('Aanvraag bestaat niet of is leeg !');
    }
    
    // choose
    if('choose' == $request){
      // check relationship_id
      $membership_id = CRM_Utils_Array::value('membership_id', $values);
      if(!isset($membership_id) or empty($membership_id)){ // exists or empty
        $errors['membership_id'] = ts('Lidmaatschap id a bestaat niet of is leeg !');
      }else if(!is_numeric($membership_id)){ // is not a number
        $errors['membership_id'] = ts('Lidmaatschap id a is geen nummer !');
      }
    }
    
    // update
    if('update' == $request){
      // check end_date
      if(isset($values['end_date']) and !empty($values['end_date'])){
        if($values['start_date'] > $values['end_date']){
          $errors['end_date'] = ts('Einddatum moet gelijk of later dan de begindatum zijn.');
        }
      }

      // check custom fields
      $configMembership = CRM_Lidmaatschapwijziging_ConfigMembership::singleton($values['contact_id']);

      // Vnv Info
      // we do not need all the fields
      $lidmaatschapMaatschappijFields = $configMembership->getMembershipCustomFieldsByName();
      $lidmaatschapMaatschappijFieldsNeeded = array('Maatschappij_lid', 'Maatschappij_anders');

      $customFields = array();
      foreach ($lidmaatschapMaatschappijFieldsNeeded as $key => $name){
        $customFields[$name] = $lidmaatschapMaatschappijFields[$name];
      }

      // return can be empty if there is no error
      $errors = array_merge($errors, CRM_Lidmaatschapwijziging_ValidateCustomFields::Validate($customFields, $values));    
    }
    
    return empty($errors) ? TRUE : $errors;
  } 
  
  function postProcess() {
    $values = $this->exportValues();
        
    $this->_configMembership = CRM_Lidmaatschapwijziging_ConfigMembership::singleton($this->_contactId);
    
    // choose
    if('choose' ==  $this->_request){
      // set message
      $session = CRM_Core_Session::singleton();

      // redirect user
      $url = CRM_Utils_System::url('civicrm/lidmaatschapwijziging/membership', 'reset=1&request=update&cid=' . $this->_contactId . '&membership_id=' . $values['membership_id']);
      CRM_Utils_System::redirect($url);
    }
    
    
    if('update' == $this->_request){
      // api create membership
      try {    
        $params = array(
          'version' => 3,
          'sequential' => 1,
          'contact_id' => $this->_contactId,
          'membership_id' => $this->_membershipId,
          'membership_type_id' => $values['membership_type_id'],
          'source' => $values['source'],
          'start_date' => $values['start_date'],
          'end_date' => $values['end_date'],
          'is_override' => $values['is_override'],
          'status_id' => $values['status_id'],
        );
        $result = civicrm_api('Membership', 'create', $params);
        
        // for the custom values in membership, the need to be custom_35 and not huppeldepup
        foreach($this->_configMembership->getMembershipCustomFields() as $key => $field){
          if(isset($values[$field['name']])){
            $params['custom_' . $field['id']] = $values[$field['name']];
          }
        }

        $result = civicrm_api('Membership', 'create', $params);
        
        // check no error
        if(isset($result['is_error']) and !$result['is_error']){ // if there is no error   
          // set message
          $session = CRM_Core_Session::singleton();
          $session->setStatus(ts('%1 is opgeslagen !', $this->_display_name), ts('Lidmaatschap Wijziging - Membership'), 'success');

          // redirect user
          $url = CRM_Utils_System::url('civicrm/lidmaatschapwijziging/relationship', 'reset=1&request=choose&cid=' . $this->_contactId);
          CRM_Utils_System::redirect($url);

        }else { // if there is a error
          // set message
          $session = CRM_Core_Session::singleton();
          $session->setStatus(ts('Er is een error: %1, %2 is niet opgeslagen !', array($result['error_message'], $this->_display_name)), ts('Lidmaatschap Wijziging - Membership'), 'error');

          // redirect user
          $url = CRM_Utils_System::url('civicrm/lidmaatschapwijziging/membership', 'reset=1&cid=' . $this->_contactId);
          CRM_Utils_System::redirect($url);
        }

      } catch (CiviCRM_API3_Exception $ex) {
        throw new Exception('Could not create contact, '
          . 'error from Api Contact create: '.$ex->getMessage());
      }        
    }
    
    //parent::postProcess();
  }
  
  
}
