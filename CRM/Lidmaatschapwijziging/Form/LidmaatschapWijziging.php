<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Lidmaatschapwijziging_Form_LidmaatschapWijziging extends CRM_Core_Form {
     
  /**
   * The contact type of the form
   *
   * @var string
   */
  public $_contactType;

  /**
   * The contact type of the form
   *
   * @var string
   */
  public $_contactSubType;
  
  /**
   * The contact id, used when editing the form
   *
   * @var int
   */
  public $_contactId;
  public $_vnvinfoId;
  public $_werkgeverId;
  
  public $_values = array();
  
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
    
    $displayName = CRM_Contact_BAO_Contact::displayName($this->_contactId);
    $displayName = ts('Edit %1', array(1 => $displayName));

    // Check if this is default domain contact CRM-10482
    if (CRM_Contact_BAO_Contact::checkDomainContact($this->_contactId)) {
      $displayName .= ' (' . ts('default organization') . ')';
    }

    // omitting contactImage from title for now since the summary overlay css doesn't work outside of our crm-container
    CRM_Utils_System::setTitle($displayName);
    
    // get values
    $config = CRM_Lidmaatschapwijziging_Config::singleton($this->_contactId);
    $this->_values = $config->getContact();
    
    // set contact id
    $this->_values['contact_id'] = $this->_contactId;
        
    // change the default name like huppeldepup_35 to huppeldepup, this
    // ensures the we can use the know names for custom fields in the template like
    // huppeldepup and not the column_names like huppeldepup_35
    $values = $config->getVnvInfoCustomValues();
    
    // set vnv info id, is neede for update or insert in the postProccess
    if(isset($values['id']) and !empty($values['id'])){
      $this->_vnvinfoId = $values['id'];
    }
    
    foreach($config->getVnvInfoCustomFields() as $key => $field){
      $this->_values[$field['name']] = $values[$field['column_name']];
    }
    
    $values = $config->getWerkgeverCustomValues();
    
    // set werkgever id, is neede for update or insert in the postProccess
    if(isset($values['id']) and !empty($values['id'])){
      $this->_werkgeverId = $values['id'];
    }
    
    foreach($config->getWerkgeverCustomFields() as $key => $field){
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
  
  /**
   * Will be called prior to outputting html (and prior to buildForm hook)
   */
  function buildQuickForm() {    
    $config = CRM_Lidmaatschapwijziging_Config::singleton($this->_contactId);
    
    // Contactdetails
    $this->add('hidden', 'contact_id', ts('Contact id'), '', true);
    $this->add('hidden', 'contact_type', ts('Contact id'), '', true);
    $this->add('hidden', 'current_employer_id', ts('Current empoyer id'), '', true);
    $this->add('text', 'current_employer',  ts('Current empoyer'), '', true );
    $this->add('text', 'job_title', ts('Job title'), '', true );
    $this->add('text', 'source', ts('Source'), '', true );
    
    // VNV info 
    $vnvInfoFields = $config->getVnvInfoCustomFieldsByName();
    $vnvInfoFieldsOptions = $config->getVnvInfoCustomFieldsOptionValues();
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
    $werkgeverFields = $config->getWerkgeverCustomFieldsByName();
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
  
  /**
   * If your form requires special validation, add one or more callbacks here
   */
  function addRules() {
    $this->addFormRule(array('CRM_Lidmaatschapwijziging_Form_LidmaatschapWijziging', 'formRule'));
  }
  
  /**
   * Here's our custom validation callback
   */
  function formRule($values) {
    $errors = array();
    
    // contact id
    if (!$this->_contactId){
      $errors['foo'] = ts('No contact id!');
      
    }else if(!is_numeric($this->_contactId)){
      $errors['foo'] = ts('Contact id is not a number!');
    }
    
    // current employer
    if (!CRM_Utils_Array::value('current_employer_id', $values)){
      $errors['foo'] = ts('No current employer id!');
      
    }else if(!is_numeric(CRM_Utils_Array::value('current_employer_id', $values))){
      $errors['foo'] = ts('Current employer id is not a number!');
    }
    
    return empty($errors) ? TRUE : $errors;
  }
  
  /**
   * Called after form is successfully submitted
   */
  function postProcess() {
    $values = $this->exportValues();
    
    echo('$this->_contactId: ' . $this->_contactId) . '<br/>' . PHP_EOL;
    echo('$this->_vnvinfoId: ' . $this->_vnvinfoId) . '<br/>' . PHP_EOL;
    echo('$this->_vnvinfoId: ' . $this->_werkgeverId) . '<br/>' . PHP_EOL;
    
    echo('<pre>');
    print_r($values);
    echo('</pre>');
    
    
    
    // current employer
    if (is_numeric(CRM_Utils_Array::value('current_employer_id', $values)) && CRM_Utils_Array::value('current_employer', $values)) {
      $values['current_employer'] = $params['current_employer_id'];
    }
        
    // call hook
    CRM_Utils_Hook::pre('edit', $values['contact_type'], $values['contact_id'], $values);
    
    // create contact
    try {
      $params = array(
        'contact_id' => $this->_contactId,
        'contact_type' => $values['contact_type'],
        'current_employer' => CRM_Utils_Array::value('current_employer', $values),
        'job_title' => CRM_Utils_Array::value('job_title', $values),
        'source' => CRM_Utils_Array::value('source', $values),
      );
    
    // Allow un-setting of location info, CRM-5969
    $params['updateBlankLocInfo'] = TRUE;

    $contact = CRM_Contact_BAO_Contact::create($params, TRUE, FALSE, TRUE);
    
    echo('<pre>');
    print_r($contact);
    echo('</pre>');
      
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not update contact, '
        . 'error from CRM_Contact_BAO_Contact::create: '.$ex->getMessage());
    }
    
    $customFields = CRM_Core_BAO_CustomField::getFields($params['contact_type'], FALSE, TRUE);

    //CRM-5143
    //if subtype is set, send subtype as extend to validate subtype customfield
    $customFieldExtends = (CRM_Utils_Array::value('contact_sub_type', $params)) ? $params['contact_sub_type'] : $params['contact_type'];

    foreach ($values as $field => $value){
      $params[$field] = $value;
    }
    
    echo('CRM_Core_BAO_CustomField: <pre>');
    print_r($params);
    print_r($customFieldExtends);
    echo('</pre>');
    
    $params['custom_34_56678'] = 'Hallo';
    $params['custom_34'] = 'Hallo';
    
    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
      $customFields,
      $this->_contactId,
      $customFieldExtends,
      TRUE
    );
    
    CRM_Utils_System::civiExit();
    
    $config = CRM_Lidmaatschapwijziging_Config::singleton($this->_contactId);
    
    // create vnvInfo
    if(isset($this->_vnvinfoId) and !empty($this->_vnvinfoId)){
      try {
        $query = 'INSERT * FROM '.$this->getWerkgeverCustomGroupField('table_name').' WHERE entity_id= %1';
        $params = array(1 => array($this->contact_id, 'Positive'));
        $dao = CRM_Core_DAO::executeQuery($query, $params);

        if ($dao->fetch()) {
          foreach($dao as $field => $value){
            $this->werkgeverCustomValues[$field] = $value;
          }
        }else {
          echo('No custom values, '
          . 'error from function setWerkgeverCustomValues');
          CRM_Utils_System::civiExit();
        }  
      } catch (CiviCRM_API3_Exception $ex) {
        throw new Exception('Could not find werkgever custom values, '
          . 'error from CRM_Core_DAO: '.$ex->getMessage());
      }
    }
    
    CRM_Utils_System::civiExit();
    
    
    /*$this->add('hidden', 'contact_id', ts('Contact id'), '', true);
    $this->add('hidden', 'contact_type', ts('Contact id'), '', true);
    $this->add('hidden', 'current_employer_id', ts('Current empoyer id'), '', true);
    $this->add('text', 'current_employer',  ts('Current empoyer'), '', true );
    $this->add('text', 'job_title', ts('Job title'), '', true );
    $this->add('text', 'source', ts('Source'), '', true );
    
    try {
          $params = array(
            'version' => 3,
            'sequential' => 1,
            'option_group_id' => $field['option_group_id'],
          );
          $result = civicrm_api('OptionValue', 'get', $params);

          foreach ($result['values'] as $key => $value){
            $this->werkgeverCustomFieldsOptionValues[$field['name']][$value['name']] = $value;
          }

        } catch (CiviCRM_API3_Exception $ex) {
          throw new Exception('Could not find werkgever custom fields option values, '
            . 'error from API CustomField get: '.$ex->getMessage());
        }
    
    $params = array(
  'version' => 3,
  'sequential' => 1,
  'id' => 27003,
);
$result = civicrm_api('Contact', 'create', $params);*/
    
    CRM_Utils_System::civiExit();
    
        
    
    
    // contact id
    if ($this->_contactId) {
      $params['contact_id'] = $this->_contactId;
    }
       
    // call hook
    CRM_Utils_Hook::pre('edit', $params['contact_type'], $params['contact_id'], $params);
    
    // in the preProcces we changed the names from huppeldepup_35 to huppeldepup, and
    // now we must change them back from huppeldepup to huppeldepup_35, so we can use these ad
    // create with the api
    $values = $config->getVnvInfoCustomValues();
    foreach($config->getVnvInfoCustomFields() as $key => $field){
      $this->_values[$field['name']] = $values[$field['column_name']];
    }
    
    $values = $config->getWerkgeverCustomValues();
    foreach($config->getWerkgeverCustomFields() as $key => $field){
      $this->_values[$field['name']] = $values[$field['column_name']];
    }
    
    parent::postProcess();
  }
}
