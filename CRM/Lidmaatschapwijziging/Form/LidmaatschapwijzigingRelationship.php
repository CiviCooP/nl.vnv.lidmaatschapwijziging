<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Lidmaatschapwijziging_Form_LidmaatschapwijzigingRelationship extends CRM_Core_Form {
  
  public $_contactId;
  public $_display_name;
  
  public $_values = array();
  
  public $_relationshipId;
  
  public $_configRelationship = array();

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
    $this->_configRelationship = CRM_Lidmaatschapwijziging_ConfigRelationship::singleton($this->_contactId);
    
    $this->_relationshipId = $this->_configRelationship->getRelationshipLastId();
    
    $this->_values = $this->_configRelationship->getContact();
    $this->_values = array_merge($this->_values, $this->_configRelationship->getRelationshipLast());
    
    // set display name
    $this->_display_name = $this->_values['display_name'];
    
    // set title
    CRM_Utils_System::setTitle('LidmaatschapWijziging - Relationship - ' . $this->_values['display_name']);
    
    // set contact id
    $this->_values['contact_id'] = $this->_contactId;
    
    echo('<pre>');
    print_r($this->_values);
    echo('</pre>');
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
    $this->_configRelationship = CRM_Lidmaatschapwijziging_ConfigRelationship::singleton($this->_contactId);
    
    // Relationship type id
    $options = array();
    foreach ($this->_configRelationship->getRelatiosnhipTypes() as $id => $type){
      $options[$type['id']] = $type['label_a_b'];
    }
    $this->add('select', 'relationship_type_id',  ts('Relationship type'), $options, true);
    
    $this->addDate('start_date', ts('Start date'), true);
    $this->addDate('end_date', ts('End date'), true);
    
    $this->add('text', 'description', ts('Description'), '', true );
    $this->add('textarea', 'note', ts('Note'), '', true );
    
    $this->addElement('checkbox', 'is_permission_a_b', ts('Permission for contact a to view and update information for contact b'), NULL);
    $this->addElement('checkbox', 'is_permission_b_a', ts('permission for contact b to view and update information for contact a'), NULL);
    $this->add('checkbox', 'is_active', ts('Is active'));
        
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
    $options = $this->getColorOptions();
    CRM_Core_Session::setStatus(ts('You picked color "%1"', array(
      1 => $options[$values['favorite_color']]
    )));
    parent::postProcess();
  }
}