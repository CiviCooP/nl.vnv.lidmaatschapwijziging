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
    
    // get session
    $session = CRM_Core_Session::singleton();
    
    // get values
    $this->_configMembership = CRM_Lidmaatschapwijziging_ConfigMembership::singleton($this->_contactId);
    
    $this->_membershipId = $this->_configMembership->getMembershipCurrentId();
    
    $this->_values = $this->_configMembership->getContact();
    $this->_values = array_merge($this->_values, $this->_configMembership->getMembershipCurrent());
    
    // set display name
    $this->_display_name = $this->_values['display_name'];
    
    // set title
    CRM_Utils_System::setTitle('LidmaatschapWijziging - Membership - ' . $this->_values['display_name']);
    
    // set contact id
    $this->_values['contact_id'] = $this->_contactId;
    
    // This is diffrent from the LidmaatschapWijzigingContact because the custom field values are already send with the membership
    // We have to set the _values of the custom field like huppeldepup and not custom_35, so we
    // loop through the custom field and change the custom_35 values to huppeldepup
    foreach($this->_configMembership->getMembershipCustomFields() as $key => $field){
      $this->_values[$field['name']] = $this->_values['custom_' . $field['id']];
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
    $this->_configMembership = CRM_Lidmaatschapwijziging_ConfigMembership::singleton($this->_contactId);

    // Membership type id
    $options = array();
    foreach ($this->_configMembership->getMembershipTypes() as $id => $type){
      $options[$type['id']] = $type['name'];
    }
    $this->add('select', 'membership_type_id',  ts('Membership type'), $options, true);
    
    $this->add('text', 'source', ts('Source'), '', true );
    $this->addDate('start_date', ts('Start date'), true);
    $this->addDate('end_date', ts('End date'), true);
    $this->add('checkbox', 'is_override', ts('Override status'));
    
    // Membership status
    $options = array();
    foreach ($this->_configMembership->getMembershipStatus() as $id => $status){
      $options[$status['id']] = $status['name'];
    }    
    $this->add('select', 'status_id',  ts('Membership status'), $options, true);
    
    // Lidmaatschap - Maatschappij
    $lidmaatschapMaatschappijFields = $this->_configMembership->getMembershipCustomFieldsByName();
    $lidmaatschapMaatschappijFieldsOptions = $this->_configMembership->getMembershipCustomFieldsOptionValues();
    $$lidmaatschapMaatschappijFieldsNeeded = array('Maatschappij_lid', 'Maatschappij_anders');
    
    foreach($lidmaatschapMaatschappijFields as $name => $field){
      switch($name){
        
        case 'Maatschappij_lid':
          $options = array();
          foreach($lidmaatschapMaatschappijFieldsOptions[$name] as $option_name => $option){
            $options[$option['value']] = ts($option['label']);
          } 
          $this->addRadio($name, ts($field['label']), $options, NULL, '<br/>', true);
          break;
          
        case 'Maatschappij_anders':
          $this->add('text', $name, ts($field['label']), '', true );
          break;
      }
    }
    
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
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
  
  function postProcess() {
    $values = $this->exportValues();
    
    $this->_configMembership = CRM_Lidmaatschapwijziging_ConfigMembership::singleton($this->_contactId);
    
    // api create membership
    try {    
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'contact_id' => $this->_contactId,
        'membership_type_id' => $values['membership_type_id'],
      );
      $result = civicrm_api('Membership', 'create', $params);
      
      if(!empty($this->_membershipId)){
        $params['id'] = $this->_membershipId;
      }
      
      // for the custom values in membership, the need to be custom_35 and not huppeldepup
      foreach($this->_configMembership->getMembershipCustomFields() as $key => $field){
        if(isset($values[$field['name']])){
          $params['custom_' . $field['id']] = $values[$field['name']];
        }
      }
                  
      $result = civicrm_api('Membership', 'create', $params);
      
      // set message
      $session = CRM_Core_Session::singleton();
      $session->setStatus(sprintf(ts('%s membership is saved !'), $this->_display_name), ts('Lidmaatschap Wijziging - Membership - Membership Saved'), 'success');
      
      // redirect user
      $url = CRM_Utils_System::url('civicrm/lidmaatschapwijziging/membership', 'reset=1&cid=' . $this->_contactId);
      CRM_Utils_System::redirect($url);
      
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not create contact, '
        . 'error from Api Contact create: '.$ex->getMessage());
      
      // set message
      /*$session = CRM_Core_Session::singleton();
      $session->setStatus(sprintf(ts('%s is not saved !'), $this->_display_name), ts('Lidmaatschap Wijziging - Contact - Contact Not Saved'), 'error');

      // redirect user
      $url = CRM_Utils_System::url('civicrm/lidmaatschapwijziging/contact', 'reset=1&cid=' . $this->_contactId);
      CRM_Utils_System::redirect($url);*/
    }        
    
    parent::postProcess();
    
    parent::postProcess();
  }
  
  
}
