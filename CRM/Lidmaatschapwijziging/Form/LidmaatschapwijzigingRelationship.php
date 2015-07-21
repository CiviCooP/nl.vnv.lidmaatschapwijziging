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
  
  public $_request = 'choose';
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
    
    // get request
    $this->_request = CRM_Utils_Request::retrieve('request', 'String', $this, FALSE, 'choose');
    
    // get session
    $session = CRM_Core_Session::singleton();
    
    // get values
    $this->_configRelationship = CRM_Lidmaatschapwijziging_ConfigRelationship::singleton($this->_contactId);
    $this->_values = $this->_configRelationship->getContact();
    
    // set contact id
    $this->_values['contact_id'] = $this->_contactId;
    
    // set display name
    $this->_display_name = $this->_values['display_name'];
    
    // set request 
    $this->_values['request'] = $this->_request;
    
    // set title
    CRM_Utils_System::setTitle('LidmaatschapWijziging - Relatie - ' . $this->_values['display_name']);
    
    // request
    if('empty' == $this->_request){
      
    }
    
    if('choose' == $this->_request){
      // if there is no relatiosnhips then the options are empty, we
      // show a message that there are no memebrships and a submit butten to
      // go to the relationship, first we redirect them to request empty
      $relationships = $this->_configRelationship->getRelationships();
      if(empty($relationships)){
        // redirect user
        $url = CRM_Utils_System::url('civicrm/lidmaatschapwijziging/relationship', 'reset=1&request=empty&cid=' . $this->_contactId);
        CRM_Utils_System::redirect($url);
      }      
    }
    
    if('update' == $this->_request){
      // get relationship id
      $this->_relationshipId = CRM_Utils_Request::retrieve('relationship_id', 'Positive', $this);
      $this->_values['relationship_id'] = $this->_relationshipId;
      
      // get relationship
      $this->_values = array_merge($this->_values, $this->_configRelationship->getRelationship($this->_relationshipId));
      
      if(!empty($this->_values['contact_a']['display_name'])){
        $this->assign('sort_name_a', $this->_values['contact_a']['display_name']);
      }

      if(!empty($this->_values['contact_b']['display_name'])){
        $this->assign('sort_name_b', $this->_values['contact_b']['display_name']);
      }
      
      // note
      $this->_values['note_id'] = $this->_values['notes']['id'];
      $this->_values['note'] = $this->_values['notes']['note'];
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
      
      // note
      if(isset($defaults['notes']['id'])){
        $defaults['note_id'] = $defaults['notes']['id'];
      }else {
        $defaults['note_id'] = 0;
      }
      
      if(isset($defaults['notes']['note'])){
        $defaults['note'] = $defaults['notes']['note'];
      }
    }
    
    return $defaults;
  }
  
  function buildQuickForm() {
    $this->_configRelationship = CRM_Lidmaatschapwijziging_ConfigRelationship::singleton($this->_contactId);
        
    // Contact
    $this->add('hidden', 'contact_id', ts('Contact id'), '', true);
    
    // Request
    $this->add('hidden', 'request', ts('Request'), '', true);
        
    if('empty' == $this->_request){      
      $this->assign('request', 'empty');
      
      $this->addButtons(array(
        array(
          'type' => 'submit',
          'name' => ts('Volgende'),
          'isDefault' => TRUE,
        ),
      ));
    }
    
    if('choose' == $this->_request){
      $this->assign('request', 'choose');
      
      $relatiosnhipTypes = $this->_configRelationship->getRelatiosnhipTypes();
      
      // memberships
      $options = array();
      foreach ($this->_configRelationship->getRelationships() as $id => $relationship){
        $options[$relationship['id']] = ts($relatiosnhipTypes[$relationship['relationship_type_id']]['name_a_b']);
        $options[$relationship['id']] .= ', ' . $relationship['contact_b']['display_name'];        
        
        // is_active is only that it it is active set by the user not if it is really active.
        // it is active if is_active is true and if it start and end date is between now
        if('1' == $relationship['is_active']){ 
          
          // it is active if the start date is smaller than now and the end date is not set or 
          // bigger than now
          if(date('Y-m-d') > $relationship['start_date'] and (date('Y-m-d') < $relationship['end_date']) or ('1970-01-01' == $relationship['end_date'] or empty($relationship['end_date']))){
            $options[$relationship['id']] .= ', Actief';
          }else {
            $options[$relationship['id']] .= ', Niet actief';
          }
        }else {
          $options[$relationship['id']] .= ', Niet actief';
        }
        
        
        $options[$relationship['id']] .= ', ' . CRM_Utils_Date::customFormat($relationship['start_date']);

        if(isset($relationship['end_date']) and !empty($relationship['end_date'])){ 
          $options[$relationship['id']] .= ', ' . CRM_Utils_Date::customFormat($relationship['end_date']);
        }
      }
      $this->add('select', 'relationship_id',  ts('Relatiosnhip'), $options, true);
      
      $this->addButtons(array(
        array(
          'type' => 'submit',
          'name' => ts('Kies / Volgende'),
          'isDefault' => TRUE,
        ),
      ));
    }   
    
    if('update' == $this->_request){
      $this->assign('request', 'update');
      
      // Relationship id
      $this->add('hidden', 'relationship_id', ts('Relationship id'), '', true);
      
      // contact a b
      $this->add('hidden', 'contact_id_a', ts('Contact id a'), '', true);
      $this->add('hidden', 'contact_id_b', ts('Contact id b'), '', true);
      
      // note
      $this->add('hidden', 'note_id', ts('Note id'), '', true);

      // Relationship type id
      $relationship = $this->_configRelationship->getRelationship($this->_values['relationship_id']);
            
      $options = array();
      foreach ($this->_configRelationship->getRelationshipTypeContactTypeB($relationship['contact_b']['contact_type'], $relationship['contact_b']['contact_sub_type']) as $id => $type){
        $options[$type['id']] = $type['label_a_b'];
      }
      $this->add('select', 'relationship_type_id',  ts('Relationship type'), $options, true);

      $this->addDate('start_date', ts('Start date'), false);
      $this->addDate('end_date', ts('End date'), false);

      $this->add('text', 'description', ts('Description'), '', false );
      $this->add('textarea', 'note', ts('Note'), '', false );

      $this->addElement('checkbox', 'is_permission_a_b', ts('Permission for contact a to view and update information for contact b'), NULL);
      $this->addElement('checkbox', 'is_permission_b_a', ts('permission for contact b to view and update information for contact a'), NULL);
      $this->add('checkbox', 'is_active', ts('Is active'));

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
    $this->addFormRule(array('CRM_Lidmaatschapwijziging_Form_LidmaatschapwijzigingRelationship', 'myRules'));
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
    
    // check request
    $request = CRM_Utils_Array::value('request', $values);
    if(!isset($request) or empty($request)){ // contact id exists or empty
      $errors['request'] = ts('Aanvraag bestaat niet of is leeg !');
    }
    
    
    
    $configRelationship = CRM_Lidmaatschapwijziging_ConfigRelationship::singleton($contact_id);
    
    // empty
    if('empty' == $request){
      
    }
    
    // choose
    if('choose' == $request){
      // check relationship_id
      $relationship_id = CRM_Utils_Array::value('relationship_id', $values);
      if(!isset($relationship_id) or empty($relationship_id)){ // exists or empty
        $errors['relationship_id'] = ts('Relatie id a bestaat niet of is leeg !');
      }else if(!is_numeric($relationship_id)){ // is not a number
        $errors['relationship_id'] = ts('Relatie id a is geen nummer !');
      }
    }
    
    // update
    if('update' == $request){
      // check relationship_id
      $relationship_id = CRM_Utils_Array::value('relationship_id', $values);
      if(!isset($relationship_id) or empty($relationship_id)){ // exists or empty
        $errors['relationship_id'] = ts('Relatie id a bestaat niet of is leeg !');
      }else if(!is_numeric($relationship_id)){ // is not a number
        $errors['relationship_id'] = ts('Relatie id a is geen nummer !');
      }
    
      // check contact_id_a
      $contact_id_a = CRM_Utils_Array::value('contact_id_a', $values);
      if(!isset($contact_id_a) or empty($contact_id_a)){ // contact id exists or empty
        $errors['contact_id_a'] = ts('Contact id a bestaat niet of is leeg !');
      }else if(!is_numeric($contact_id_a)){ // contact id is not a number
        $errors['contact_id_a'] = ts('Contact id a is geen nummer !');
      }

      // check contact_id_b
      $contact_id_b = CRM_Utils_Array::value('contact_id_b', $values);
      if(!isset($contact_id_b) or empty($contact_id_b)){ // contact id exists or empty
        $errors['contact_id_b'] = ts('Contact id b bestaat niet of is leeg !');
      }else if(!is_numeric($contact_id_b)){ // contact id is not a number
        $errors['contact_id_b'] = ts('Contact id b is geen nummer !');
      }

      // check relationship_type_id
      $relationship_type_id = CRM_Utils_Array::value('relationship_type_id', $values);
      if(!isset($relationship_type_id) or empty($relationship_type_id)){ // relationship_type_id exists or empty
        $errors['relationship_type_id'] = ts('Relatie type id bestaat niet of is leeg !');
      }else if(!is_numeric($relationship_type_id)){ // relationship_type_id is not a number
        $errors['relationship_type_id'] = ts('Relatie type id is geen nummer !');
      }

      // check end_date
      if(isset($values['end_date']) and !empty($values['end_date'])){
        if($values['start_date'] > $values['end_date']){
          $errors['end_date'] = ts('Einddatum moet gelijk of later dan de begindatum zijn.');
        }
      }
      
      // check if contact_b is the correct contact type for that 
      // specific relationship type, see /civicrm/admin/reltype?reset=1
      // check if there is no error for relationship_id, contact_id_b and relationship_type_id
      /**
       * This is already done in the buildQuickForm, the relationship type id are filter so they match the contact type and sub type of
       * the realtionship types !!!!
       */
      /*if(!isset($errors['relationship_id']) and !isset($errors['contact_id_b']) and !isset($errors['relationship_type_id'])){ 
        $relationship = $configRelationship->getRelationship($relationship_id);
        $relationshipType = $configRelationship->getRelationshipTypeById($relationship_type_id);
                
        // rease a error if the contact type of the contact_b is not the same
        // as the relationship contact type b
        if($relationship['contact_b'] != $relationshipType['contact_type_b']){
          //$errors['relationship_type_id'] = ts('De relatie type die gekozen is, is voor');
        }
        
      }*/
    }
    
    return empty($errors) ? TRUE : $errors;
  } 
  
  function postProcess() {
    $values = $this->exportValues();    
    $this->_configRelationship = CRM_Lidmaatschapwijziging_ConfigRelationship::singleton($this->_contactId);
    
    // empty
    if('empty' ==  $this->_request){
      // set message
      $session = CRM_Core_Session::singleton();
      $session->setStatus(ts('Voor %1 bestaat geen lidmaatschap !', $this->_display_name), ts('Lidmaatschap Wijziging - Relatie'), 'warning');

      // redirect user
      $url = CRM_Utils_System::url('civicrm/lidmaatschapwijziging/group', 'reset=1&cid=' . $this->_contactId);
      CRM_Utils_System::redirect($url);
    }
    
    // choose
    if('choose' ==  $this->_request){
      // set message
      $session = CRM_Core_Session::singleton();
      
      // redirect user
      $url = CRM_Utils_System::url('civicrm/lidmaatschapwijziging/relationship', 'reset=1&request=update&cid=' . $this->_contactId . '&relationship_id=' . $values['relationship_id']);
      CRM_Utils_System::redirect($url);
    }
    
    // update
    if('update' == $this->_request){ 
      /**
       * Note works diffrent from the other, if the
       * is empty delete it, if the note not empty create 
       * it or update
       */
      if(!isset($values['note']) or empty($values['note'])){ // delete if empty
        
        // only delete if there is a id
        if(isset($values['note_id']) and !empty($values['note_id'])){ // just delete all the notes, there is only one note with the relationship
          try {
            $params = array(
              'version' => 3,
              'sequential' => 1,
              'entity_id' => $this->_relationshipId,
              'contact_id' => $this->_contactId,
              'entity_table' => 'civicrm_relationship',
              'note' => NULL,
              'id' => $values['note_id'],
            );
              
            $result = civicrm_api('Note', 'delete', $params);

            // check no error
            if(isset($result['is_error']) and !$result['is_error']){ // if there is no error   
              // set message
              //$session = CRM_Core_Session::singleton();
              //$session->setStatus(ts('%1 is opgeslagen !', $this->_display_name), ts('Lidmaatschap Wijziging - Relatie'), 'success');

            }else { // if there is a error
              // set message
              $session = CRM_Core_Session::singleton();
              $session->setStatus(ts('Er is een error: %1, De notitie van %2 is niet verwijdert !', array($result['error_message'], $this->_display_name)), ts('Lidmaatschap Wijziging - Relatie'), 'error');
            }

          } catch (CiviCRM_API3_Exception $ex) {
            throw new Exception('Could not delete note, '
              . 'error from Api Note create: '.$ex->getMessage());
          }
        }
        
      }else { // create if not empty
        try {    
          $params = array(
            'version' => 3,
            'sequential' => 1,
            'entity_id' => $this->_relationshipId,
            'contact_id' => $this->_contactId,
            'entity_table' => 'civicrm_relationship',
            'note' => $values['note'],
          );

          if(isset($values['note_id']) and !empty($values['note_id'])){
            $params['id'] = $values['note_id'];
          }
        
          $result = civicrm_api('Note', 'create', $params);

          // check no error
          if(isset($result['is_error']) and !$result['is_error']){ // if there is no error   
            // set message
            //$session = CRM_Core_Session::singleton();
            //$session->setStatus(ts('%1 is opgeslagen !', $this->_display_name), ts('Lidmaatschap Wijziging - Relatie'), 'success');

          }else { // if there is a error
            // set message
            $session = CRM_Core_Session::singleton();
            $session->setStatus(ts('Er is een error: %1, De notitie van %2 is niet opgeslagen !', array($result['error_message'], $this->_display_name)), ts('Lidmaatschap Wijziging - Relatie'), 'error');
          }

        } catch (CiviCRM_API3_Exception $ex) {
          throw new Exception('Could not create note, '
            . 'error from Api Note create: '.$ex->getMessage());
        }      
      }
      
      // set checkboxes
      // set if not exist
      if(!isset($values['is_permission_a_b'])){
        $values['is_permission_a_b'] = '0';
      }
      
      // set if not exist
      if(!isset($values['is_permission_b_a'])){
        $values['is_permission_b_a'] = '0';
      }
      
      // set if not exist
      if(!isset($values['is_active'])){
        $values['is_active'] = '0';
      }
      
      // api create relatiosnhip
      try {    
        $params = array(
          'version' => 3,
          'sequential' => 1,
          //'relationship_id' => $this->_relationshipId,
          'id' => $this->_relationshipId,
          'contact_id_a' => $values['contact_id_a'],
          'contact_id_b' => $values['contact_id_b'],
          'relationship_type_id' => $values['relationship_type_id'],
          'start_date' => $values['start_date'],
          'end_date' => $values['end_date'],
          'description' => $values['description'],
          'is_permission_a_b' => $values['is_permission_a_b'],
          'is_permission_b_a' => $values['is_permission_b_a'],
          'is_active' => $values['is_active'],
        );
        
        $result = civicrm_api('Relationship', 'create', $params);
                
        // check no error
        if(isset($result['is_error']) and !$result['is_error']){ // if there is no error   
          // set message
          $session = CRM_Core_Session::singleton();
          $session->setStatus(ts('%1 is opgeslagen !', $this->_display_name), ts('Lidmaatschap Wijziging - Relatie'), 'success');

          // redirect user
          $url = CRM_Utils_System::url('civicrm/lidmaatschapwijziging/group', 'reset=1&cid=' . $this->_contactId);
          CRM_Utils_System::redirect($url);

        }else { // if there is a error
          // set message
          $session = CRM_Core_Session::singleton();
          $session->setStatus(ts('Er is een error: %1, %2 is niet opgeslagen !', array($result['error_message'], $this->_display_name)), ts('Lidmaatschap Wijziging - Relatie'), 'error');

          // redirect user
          $url = CRM_Utils_System::url('civicrm/lidmaatschapwijziging/relationship', 'reset=1&cid=' . $this->_contactId);
          CRM_Utils_System::redirect($url);
        }

      } catch (CiviCRM_API3_Exception $ex) {
        throw new Exception('Could not create relationship, '
          . 'error from Api Relationship create: '.$ex->getMessage());
      }
    }
    
    //parent::postProcess();
  }
}