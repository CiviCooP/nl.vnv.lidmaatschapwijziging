<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Lidmaatschapwijziging_Form_LidmaatschapWijzigingGroup extends CRM_Core_Form {
  
  public $_contactId;
  public $_display_name;
  
  public $_values = array();
  
  public $_configGroup = array();
  
  public $_context = '';
  
  /**
   * The groupContact id, used when editing the groupContact
   *
   * @var int
   */
  protected $_groupContactId;

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
    $this->_configGroup = CRM_Lidmaatschapwijziging_ConfigGroup::singleton($this->_contactId);
    $this->_values = $this->_configGroup->getContact();
    
    // set display name
    $this->_display_name = $this->_values['display_name'];
    
    // set title
    CRM_Utils_System::setTitle('LidmaatschapWijziging - Group - ' . $this->_values['display_name']);
    
    // set contact id
    $this->_values['contact_id'] = $this->_contactId;
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
    // Contact
    $this->add('hidden', 'contact_id', ts('Contact id'), '', true);
    
    // get the list of all the groups
    $allGroups = CRM_Core_PseudoConstant::group();

    // Arrange groups into hierarchical listing (child groups follow their parents and have indentation spacing in title)
    $groupHierarchy = CRM_Contact_BAO_Group::getGroupsHierarchy($allGroups, NULL, '&nbsp;&nbsp;', TRUE);

    // get the list of groups contact is currently in ("Added") or unsubscribed ("Removed").
    $currentGroups = CRM_Contact_BAO_GroupContact::getGroupList($this->_contactId);

    // Remove current groups from drowdown options ($groupSelect)
    if (is_array($currentGroups)) {
      // Compare array keys, since the array values (group title) in $groupList may have extra spaces for indenting child groups
      $groupSelect = array_diff_key($groupHierarchy, $currentGroups);
    }
    else {
      $groupSelect = $groupHierarchy;
    }

    $groupSelect = array( '' => ts('- select group -')) + $groupSelect;

    if (count($groupSelect) > 1) {
      $session = CRM_Core_Session::singleton();
      $msg = ts('Add to a group');
      $this->add('select', 'group_id', $msg, $groupSelect, false);
      
      $this->addButtons(array(
        array(
          'type' => 'submit',
          'name' => ts('Opslaan / Volgende'),
          'isDefault' => TRUE,
        ),
      ));

      // export form elements
      $this->assign('elementNames', $this->getRenderableElementNames());
    }
    
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
    $this->addFormRule(array('CRM_Lidmaatschapwijziging_Form_LidmaatschapWijzigingGroup', 'myRules'));
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
    $group_id = trim(CRM_Utils_Array::value('group_id', $values)); // needs to be trimed, there is by default a whitespace after the number
    // current employer is not required
    if(!empty($group_id) and !is_numeric($group_id)){
      $errors['group_id'] = ts('Groep id is geen nummer !');
    }
        
    return empty($errors) ? TRUE : $errors;
  } 
  
  function postProcess() {
    $values = $this->exportValues();
        
    // if group_id does not exist or is empty skip it
    if(isset($values['group_id']) and !empty($values['group_id'])){
      // get method
      $groupId = $values['group_id'];
      $method    = 'Admin';    
      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');

      if ($userID == $this->_contactId) {
        $method = 'Web';
      }
      
      $groupContact = CRM_Contact_BAO_GroupContact::addContactsToGroup(array($this->_contactId), $groupId, $method);

      // check no error
      if ($groupContact) {
        $groups = CRM_Core_PseudoConstant::group();
        //CRM_Core_Session::setStatus(ts("Contact has been added to '%1'.", array(1 => $groups[$groupId])), ts('Added to Group'), 'success');
        
        // set message
        $session = CRM_Core_Session::singleton();
        $session->setStatus(ts('%1 is toegevoegd aan %2 !', array($this->_display_name, $groups[$groupId])), ts('Lidmaatschap Wijziging - Groep'), 'success');
        
        // redirect user
        $url = CRM_Utils_System::url('civicrm/lidmaatschapwijziging/registratieopleidingbelangstelling', 'reset=1&cid=' . $this->_contactId);
        CRM_Utils_System::redirect($url);
        
      }else { // if there is a error
        // set message
        $session = CRM_Core_Session::singleton();
        $session->setStatus(ts('Er is een error: %1, %2 is niet opgeslagen !', array($result['error_message'], $this->_display_name)), ts('Lidmaatschap Wijziging - Groep'), 'error');

        // redirect user
        $url = CRM_Utils_System::url('civicrm/lidmaatschapwijziging/groep', 'reset=1&cid=' . $this->_contactId);
        CRM_Utils_System::redirect($url);
      } 
        
    }else {
      $session = CRM_Core_Session::singleton();
      $session->setStatus(ts("Geen groep gekozen ! %1 is niet aan een groep toegevoegd.", array(1 => $this->_display_name)), ts('Lidmaatschap Wijziging - Groep'), 'warning');

      // redirect user
      $url = CRM_Utils_System::url('civicrm/lidmaatschapwijziging/registratieopleidingbelangstelling', 'reset=1&cid=' . $this->_contactId);
      CRM_Utils_System::redirect($url);
    }
    
    parent::postProcess();
  }
}
