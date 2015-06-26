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
    $defaults['current_employer_id'] = $defaults['employer_id'];
    
    return $defaults;
  }
  
  function buildQuickForm() {
    $this->_configContact = CRM_Lidmaatschapwijziging_ConfigContact::singleton($this->_contactId);
    
    // Contactdetails
    $this->add('hidden', 'contact_id', ts('Contact id'), '', true);
    $this->add('hidden', 'contact_type', ts('Contact id'), '', true);
    $this->add('hidden', 'current_employer_id', ts('Current empoyer id'), '', true);
    $this->add('text', 'current_employer',  ts('Current empoyer'), '', true );
    $this->add('text', 'job_title', ts('Job title'), '', true );
    $this->add('text', 'source', ts('Source'), '', true );
    
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
                    
          $this->addRadio($name, ts($field['label']), $options, NULL, '<br/>', true);
          break;
          
        case 'Lid_beroepsvereniging_voldoet_aan_een_van_de_volgende_criteria':
          $options = array();
          foreach($vnvInfoFieldsOptions[$name] as $option_name => $option){
            $options[$option['value']] = ts($option['label']);
          }
                    
          $this->addRadio($name, ts($field['label']), $options, NULL, '<br/>', true);
          break;
      }
    }
    
    // Werkgever
    $werkgeverFields = $this->_configContact->getWerkgeverCustomFieldsByName();
    $werkgeverFieldsNeeded = array('Vl_type', 'Datum_in_dienst', 'Pers_code_let_', 'Postvak', 'persnr', 'Standplaats_Werkgever');
    
    foreach($werkgeverFields as $name => $field){
      switch($name){
        
        case 'Vl_type':
          $this->add('text', $name, ts($field['label']), '', true);
          break;
        
        case 'Datum_in_dienst':
          $this->addDate($name, ts($field['label']), true);
          break;
        
        case 'Pers_code_let_':
          $this->add('text', $name, ts($field['label']), '', true);
          break;
        
        case 'Postvak':
          $this->add('text', $name, ts($field['label']), '', true);
          break;
        
        case 'persnr':
          $this->add('text', $name, ts($field['label']), '', true);
          break;
        
        case 'Standplaats_Werkgever':
          $this->add('text', $name, ts($field['label']), '', true);
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
        
    $this->_configContact = CRM_Lidmaatschapwijziging_ConfigContact::singleton($this->_contactId);
        
    // set current_employer
    // current employer
    if (is_numeric($values['current_employer_id']) && $values['current_employer']) {
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
      
      // set message
      $session = CRM_Core_Session::singleton();
      $session->setStatus(sprintf(ts('%s is saved !'), $this->_display_name), ts('Lidmaatschap Wijziging - Contact - Contact Saved'), 'success');
      
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
  }
}
