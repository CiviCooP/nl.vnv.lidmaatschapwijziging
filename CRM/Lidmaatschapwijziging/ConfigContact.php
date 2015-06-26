<?php
/**
 * Class following Singleton pattern for specific extension configuration
 *
 * @author Jan-Derek (CiviCooP) <j.vos@bosqom.nl>
 */
class CRM_Lidmaatschapwijziging_ConfigContact {
  
  /*
   * singleton pattern
   */
  static private $_singleton = NULL;
  
  // Contact
  protected $contact_id = 0;
  protected $contact = array();

  // Vnv Info
  protected $vnvInfoCustomGroupName = 'VNV_info';
  protected $vnvInfoCustomGroup = array();
  protected $vnvInfoCustomFields = array();
  protected $vnvInfoCustomFieldsOptionValues = array();
  protected $vnvInfoCustomValues = array();
  
  // Werkgever
  protected $werkgeverCustomGroupName = 'Werkgever';
  protected $werkgeverCustomGroup = array();
  protected $werkgeverCustomFields = array();
  protected $werkgeverCustomFieldsOptionValues = array();
  protected $werkgeverCustomValues = array();

  /**
   * Constructor
   */
  function __construct($contact_id) {
    // Contact
    $this->setContact($contact_id);
    
    // Vnv Info
    $this->setVnvInfoCustomGroup();
    $this->setVnvInfoCustomFields();
    $this->setvnvInfoCustomFieldsOptionValues();
    $this->setVnvInfoCustomValues();
    
    // Werkgever
    $this->setWerkgeverCustomGroup();
    $this->setWerkgeverCustomFields();
    $this->setWerkgeverCustomFieldsOptionValues();
    $this->setWerkgeverCustomValues();
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
      self::$_singleton = new CRM_Lidmaatschapwijziging_ConfigContact($contact_id);
    }
    return self::$_singleton;
  }
  
  // Contact
  protected function setContact($contact_id){
    if(empty($contact_id)){
      echo('No contact id, '
        . 'error from function setContact');
        CRM_Utils_System::civiExit();
    }
    
    $this->contact_id = $contact_id;
    
    /*try {
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'contact_id' => $this->contact_id,
      );
      $this->contact = civicrm_api('Contact', 'getsingle', $params);
      
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find contact, '
        . 'error from API Contact getsingle: '.$ex->getMessage());
    }*/
    
    // the source or contact source does not exist if youn use the api
    try {
      $query = 'SELECT * FROM civicrm_contact WHERE id= %1';
      $params = array(1 => array($this->contact_id, 'Positive'));
      $dao = CRM_Core_DAO::executeQuery($query, $params);
            
      if ($dao->fetch()) {
        foreach($dao as $field => $value){
          $this->contact[$field] = $value;
        }
      }else {
        echo('No contact values, '
        . 'error from function setContact');
        CRM_Utils_System::civiExit();
      }  
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find contact, '
        . 'error from CRM_Core_DAO: '.$ex->getMessage());
    }
  }
  
  public function getContact() {
    return $this->contact;
  }


  // Vnv Info 
  // Vnv Info - Custom Group
  protected function setVnvInfoCustomGroup(){
    try {
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'name' => $this->vnvInfoCustomGroupName,
        'sort' => 'weight', // sort by weight or you know
      );
      $this->vnvInfoCustomGroup = civicrm_api('CustomGroup', 'getsingle', $params);
      
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find vnv info custom group id, '
        . 'error from API CustomGroup getsingle: '.$ex->getMessage());
    }
  }
  
  public function getVnvInfoCustomGroup(){
    return $this->vnvInfoCustomGroup;
  }
  
  public function getVnvInfoCustomGroupField($field){
    return $this->vnvInfoCustomGroup[$field];
  }
  
  // Vnv Info - Custom Fields
  protected function setVnvInfoCustomFields(){
    try {
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'custom_group_id' => $this->getVnvInfoCustomGroupField('id'),
        'sort' => 'weight', // sort by weight or you know
      );
      $result = civicrm_api('CustomField', 'get', $params);
      
      foreach ($result['values'] as $key => $value){
        $this->vnvInfoCustomFields[$value['id']] = $value;
      }
      
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find vnv info custom fields, '
        . 'error from API CustomField get: '.$ex->getMessage());
    }
  }
  
  public function getVnvInfoCustomFields(){
    return $this->vnvInfoCustomFields;
  }
  
  public function getVnvInfoCustomFieldsByName(){
    $return = array();
    foreach ($this->vnvInfoCustomFields as $id => $field){
      $return[$field['name']] = $field;
    }
    
    return $return;
  }
  
  // Vnv Info - Custom Fields Options Values
  protected function setVnvinfoCustomFieldsOptionValues(){
    foreach($this->vnvInfoCustomFields as $id => $field){
      if(isset($field['option_group_id']) and !empty($field['option_group_id'])){
        $this->vnvInfoCustomFieldsOptionValues[$field['name']] = array();
        
        try {
          $params = array(
            'version' => 3,
            'sequential' => 1,
            'option_group_id' => $field['option_group_id'],
            'sort' => 'weight', // sort by weight or you know
          );
          $result = civicrm_api('OptionValue', 'get', $params);        

        } catch (CiviCRM_API3_Exception $ex) {
          throw new Exception('Could not find vnv info custom fields option values, '
            . 'error from API CustomField get: '.$ex->getMessage());
        }
        
        foreach ($result['values'] as $key => $value){
          $this->vnvInfoCustomFieldsOptionValues[$field['name']][$value['id']] = $value; // you have to use the id, because al long name is sorted to around the 30 characters, so it can be that the name is double
        }
      }
    }
  }
    
  public function getVnvInfoCustomFieldsOptionValues(){
    return $this->vnvInfoCustomFieldsOptionValues;
  }
    
  // Vnv Info - Custom Values
  protected function setVnvInfoCustomValues(){
    try {
      $query = 'SELECT * FROM '.$this->getVnvInfoCustomGroupField('table_name').' WHERE entity_id= %1';
      $params = array(1 => array($this->contact_id, 'Positive'));
      $dao = CRM_Core_DAO::executeQuery($query, $params);
            
      if ($dao->fetch()) {
        foreach($dao as $field => $value){
          $this->vnvInfoCustomValues[$field] = $value;
        }
      }else {
        echo('No custom values, '
        . 'error from function setVnvInfoCustomValues');
        CRM_Utils_System::civiExit();
      }  
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find vnv info custom values, '
        . 'error from CRM_Core_DAO: '.$ex->getMessage());
    }
  }
  
  public function getVnvInfoCustomValues(){
    return $this->vnvInfoCustomValues;
  }
  
  public function getVnvInfoCustomValuesField($field){
    return $this->vnvInfoCustomValues[$field];
  }
  
  // Werkgever 
  // Werkgever - Custom Group
  protected function setWerkgeverCustomGroup(){
    try {
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'name' => $this->werkgeverCustomGroupName,
        'sort' => 'weight', // sort by weight or you know
      );
      $this->werkgeverCustomGroup = civicrm_api('CustomGroup', 'getsingle', $params);
      
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find werkgever custom group id, '
        . 'error from API CustomGroup getsingle: '.$ex->getMessage());
    }
  }
  
  public function getWerkgeverCustomGroup(){
    return $this->werkgeverCustomGroup;
  }
  
  public function getWerkgeverCustomGroupField($field){
    return $this->werkgeverCustomGroup[$field];
  }
  
  // Werkgever - Custom Fields
  protected function setWerkgeverCustomFields(){    
    try {
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'custom_group_id' => $this->getWerkgeverCustomGroupField('id'),
        'sort' => 'weight', // sort by weight or you know
      );
      $result = civicrm_api('CustomField', 'get', $params);
            
      foreach ($result['values'] as $key => $value){
        $this->werkgeverCustomFields[$key] = $value;
      }
      
      
      
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find werkgever custom fields, '
        . 'error from API CustomField get: '.$ex->getMessage());
    }
  }
  
  public function getWerkgeverCustomFields(){
    return $this->werkgeverCustomFields;
  }
  
  public function getWerkgeverCustomFieldsByName(){    
    $return = array();
    foreach ($this->werkgeverCustomFields as $key => $value){
      $return[$value['name']] = $value;
    }
    
    return $return;
  }
  
  // Werkgever - Custom Fields Options Values
  protected function setWerkgeverCustomFieldsOptionValues(){
    foreach($this->werkgeverCustomFields as $id => $field){
      if(isset($field['option_group_id']) and !empty($field['option_group_id'])){
        $this->werkgeverCustomFieldsOptionValues[$field['name']] = array();
        
        try {
          $params = array(
            'version' => 3,
            'sequential' => 1,
            'option_group_id' => $field['option_group_id'],
            'sort' => 'weight',  // sort by weight or you know
          );
          $result = civicrm_api('OptionValue', 'get', $params);
          
          // 
          foreach ($result['values'] as $key => $value){
            $this->werkgeverCustomFieldsOptionValues[$field['name']][$value['id']] = $value; // you have to use the id, because al long name is sorted to around the 30 characters, so it can be that the name is double
          }

        } catch (CiviCRM_API3_Exception $ex) {
          throw new Exception('Could not find werkgever custom fields option values, '
            . 'error from API CustomField get: '.$ex->getMessage());
        }
      }
    }
  }
    
  public function getWerkgeverCustomFieldsOptionValues(){
    return $this->werkgeverCustomFieldsOptionValues;
  }
  
  // Werkgever - Custom Values
  protected function setWerkgeverCustomValues(){
    try {
      $query = 'SELECT * FROM '.$this->getWerkgeverCustomGroupField('table_name').' WHERE entity_id= %1';
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
  
  public function getWerkgeverCustomValues(){
    return $this->werkgeverCustomValues;
  }
  
  public function getWerkgeverCustomValuesField($field){
    return $this->werkgeverCustomValues[$field];
  }
}