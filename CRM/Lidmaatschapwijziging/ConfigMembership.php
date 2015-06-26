<?php
/**
 * Class following Singleton pattern for specific extension configuration
 *
 * @author Jan-Derek (CiviCooP) <j.vos@bosqom.nl>
 */
class CRM_Lidmaatschapwijziging_ConfigMembership {
  
  /*
   * singleton pattern
   */
  static private $_singleton = NULL;
  
  // Contact
  protected $contact_id = 0;
  protected $contact = array();
  
  // Membership
  protected $membership = array();
  protected $membershipCurrent = array();
  protected $membershipCurrentId = 0;
  protected $membershipFields = array();
  protected $membershipTypes = array();
  protected $membershipStatus = array();
  protected $membershipCustomGroupName = 'Lidmaatschap__Maatschappij';
  protected $membershipCustomGroup = array();
  protected $membershipCustomFields = array();
  protected $membershipCustomFieldsOptionValues = array();


  /**
   * Constructor
   */
  function __construct($contact_id) {
    // Contact
    $configContact = CRM_Lidmaatschapwijziging_ConfigContact::singleton($contact_id);   
    $this->contact = $configContact->getContact();
    $this->contact_id = $contact_id;
    
    // membership
    $this->setMembership();
    $this->setMembershipCurrent();
    $this->setMembershipFields();
    $this->setMembershipTypes();
    $this->setMembershipStatus();
    $this->setMembershipCustomGroup();
    $this->setMembershipCustomFields();
    $this->setMembershipCustomFieldsOptionValues();
  }
  
  /**
   * Function to return singleton object
   * 
   * @return object $_singleton
   * @access public
   * @static
   */
  public static function &singleton($contact_id) {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Lidmaatschapwijziging_ConfigMembership($contact_id);
    }
    return self::$_singleton;
  }
  
  // Contact
  public function getContact() {
    return $this->contact;
  }
  
  // Membership
  protected function setMembership(){
    try {
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'contact_id' => $this->contact_id,
        'sort' => 'start_date', // so the last membership will be the last, in getMembershipCurrent() will get the last one, because it is the last one in the array
      );
      $result = civicrm_api('Membership', 'get', $params);
      foreach($result['values'] as $key => $membership){
        $this->membership[] = $membership;
      }
            
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find membership, '
        . 'error from API Membership get: '.$ex->getMessage());
    }
  }
  
  public function getMembership(){
    return $this->membership;
  }
  
  protected function setMembershipCurrent(){
    $membershipCurrent = array();
    // if there is just one membership
    if(count($this->membership) == 1){ 
      $membershipCurrent = $this->membership[0];
      
    }else {
      // check if there is one, that is between current date (start_date, end_date)
      foreach ($this->membership as $key => $membership){
        if($membership['start_date'] >= date('Y-m-d') and ($membership['end_date'] <= date('Y') or $membership['end_date'] == '1970-01-01' or $membership['end_date'] == NULL)){
          $membershipCurrent = $membership;
        }
      }
    }    
    
    // if there is sill no memebership get the last one
    if(empty($membershipCurrent)){
      foreach ($this->membership as $key => $membership){
        $membershipCurrent = $membership;
      }
    }
    
    // set the membership current id, if exists
    if(isset($membershipCurrent['id']) and !empty($membershipCurrent['id'])){
      $this->membershipCurrentId = $membershipCurrent['id'];
    }
    
    $this->membershipCurrent = $membershipCurrent;
  }
  
  public function getMembershipCurrent(){
    return $this->membershipCurrent;
  }


  public function getMembershipCurrentId(){
    $this->membershipCurrentId;
  }
  
  protected function setMembershipFields() {
    try {
      $params = array(
        'version' => 3,
        'sequential' => 1,
      );
      $result = civicrm_api('Membership', 'getfields', $params);
      
      foreach($result['values'] as $key => $field){
        $this->membershipFields[$field['name']] = $field;
      }
            
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find membership fields, '
        . 'error from API Membership getfields: '.$ex->getMessage());
    } 
  }
  
  public function getMembershipFields(){
    return $this->membershipFields;
  }
  
  // Membership - Type
  protected function setMembershipTypes(){
    try {
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'sort' => 'weight', // sort by weight or you know
      );
      $result = civicrm_api('MembershipType', 'get', $params);
      
      foreach($result['values'] as $key => $field){
        $this->membershipTypes[$field['id']] = $field;
      }
            
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find membership types, '
        . 'error from API MembershipType get: '.$ex->getMessage());
    } 
  }

  public function getMembershipTypes(){
    return $this->membershipTypes;
  }
  
  // Membership - Status
  protected function setMembershipStatus(){
    try {
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'is_active' => 1,
        'sort' => 'weight', // sort by weight or you know
      );
      $result = civicrm_api('MembershipStatus', 'get', $params);
      
      foreach($result['values'] as $key => $field){
        $this->membershipStatus[$field['id']] = $field;
      }
            
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find membership status, '
        . 'error from API MembershipStatus get: '.$ex->getMessage());
    } 
  }

  public function getMembershipStatus(){
    return $this->membershipStatus;
  }

  // Membership - Custom Group
  protected function setMembershipCustomGroup(){
    try {
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'name' => $this->membershipCustomGroupName,
        'sort' => 'weight', // sort by weight or you know
      );
      $this->membershipCustomGroup = civicrm_api('CustomGroup', 'getsingle', $params);
      
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find membership custom group id, '
        . 'error from API CustomGroup getsingle: '.$ex->getMessage());
    }
  }
  
  public function getMembershipCustomGroup(){
    return $this->vnvInfoCustomGroup;
  }
  
  public function getMembershipCustomGroupField($field){
    return $this->vnvInfoCustomGroup[$field];
  }
  
  // Membership - Custom Fields
  protected function setMembershipCustomFields(){
    try {
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'custom_group_id' => $this->getMembershipCustomGroupField('id'),
        'sort' => 'weight', // sort by weight or you know
      );
      $result = civicrm_api('CustomField', 'get', $params);
      
      foreach ($result['values'] as $key => $value){
        $this->membershipCustomFields[$value['id']] = $value;
      }
      
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find membership custom fields, '
        . 'error from API CustomField get: '.$ex->getMessage());
    }
  }
  
  public function getMembershipCustomFields(){
    return $this->membershipCustomFields;
  }
  
  public function getMembershipCustomFieldsByName(){
    $return = array();
    foreach ($this->membershipCustomFields as $id => $field){
      $return[$field['name']] = $field;
    }
    
    return $return;
  }
  
  // Membership - Custom Fields Options Values
  protected function setMembershipCustomFieldsOptionValues(){
    foreach($this->membershipCustomFields as $id => $field){
      if(isset($field['option_group_id']) and !empty($field['option_group_id'])){
        $this->membershipCustomFieldsOptionValues[$field['name']] = array();
        
        try {
          $params = array(
            'version' => 3,
            'sequential' => 1,
            'option_group_id' => $field['option_group_id'],
            'sort' => 'weight', // sort by weight or you know
          );
          $result = civicrm_api('OptionValue', 'get', $params);        

        } catch (CiviCRM_API3_Exception $ex) {
          throw new Exception('Could not find membership custom fields option values, '
            . 'error from API CustomField get: '.$ex->getMessage());
        }
        
        foreach ($result['values'] as $key => $value){
          $this->membershipCustomFieldsOptionValues[$field['name']][$value['id']] = $value; // you have to use the id, because al long name is sorted to around the 30 characters, so it can be that the name is double
        }
      }
    }
  }
    
  public function getMembershipCustomFieldsOptionValues(){
    return $this->membershipCustomFieldsOptionValues;
  }
}