<?php
/**
 * Class following Singleton pattern for specific extension configuration
 *
 * @author Jan-Derek (CiviCooP) <j.vos@bosqom.nl>
 */
class CRM_Lidmaatschapwijziging_ConfigRegistratieOpleidingBelangstelling {
  
  /*
   * singleton pattern
   */
  static private $_singleton = NULL;
  
  // Contact
  protected $contact_id = 0;
  protected $contact = array();
  
  // Registratie Opleiding Belangstelling
  protected $regiOplBelCustomGroupName = 'Registratie_opleiding_en_belangstelling';
  protected $regiOplBelCustomGroup = array();
  protected $regiOplBelCustomFields = array();
  protected $regiOplBelCustomFieldsOptionValues = array();
  protected $regiOplBelCustomValues = array();
  
  /**
   * Constructor
   */
  function __construct($contact_id) {
    // Contact
    $configContact = CRM_Lidmaatschapwijziging_ConfigContact::singleton($contact_id);   
    $this->contact = $configContact->getContact();
    $this->contact_id = $contact_id;
    
    // Registratie Opleiding Belangstelling
    $this->setRegiOplBelCustomGroup();
    $this->setRegiOplBelCustomFields();
    $this->setRegiOplBelCustomFieldsOptionValues();
    $this->setRegiOplBelCustomValues();
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
      self::$_singleton = new CRM_Lidmaatschapwijziging_ConfigRegistratieOpleidingBelangstelling($contact_id);
    }
    return self::$_singleton;
  }
  
  // Contact
  public function getContact() {
    return $this->contact;
  }
  
  // Registratie Opleiding Belangstelling
  // Registratie Opleiding Belangstelling - Custom Group
  protected function setRegiOplBelCustomGroup(){
    try {
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'name' => $this->regiOplBelCustomGroupName,
        'sort' => 'weight', // sort by weight or you know
      );
      $this->regiOplBelCustomGroup = civicrm_api('CustomGroup', 'getsingle', $params);
      
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find registratie opleiding belangstelling custom group id, '
        . 'error from API CustomGroup getsingle: '.$ex->getMessage());
    }
  }
  
  public function getRegiOplBelCustomGroup(){
    return $this->regiOplBelCustomGroup;
  }
  
  public function getRegiOplBelCustomGroupField($field){
    return $this->regiOplBelCustomGroup[$field];
  }
  
  // Registratie Opleiding Belangstelling - Custom Fields
  protected function setRegiOplBelCustomFields(){
    try {
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'custom_group_id' => $this->getRegiOplBelCustomGroupField('id'),
        'sort' => 'weight', // sort by weight or you know
      );
      $result = civicrm_api('CustomField', 'get', $params);
      
      foreach ($result['values'] as $key => $value){
        $this->regiOplBelCustomFields[$value['id']] = $value;
      }
      
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find registratie opleiding belangstelling custom fields, '
        . 'error from API CustomField get: '.$ex->getMessage());
    }
  }
  
  public function getRegiOplBelCustomFields(){
    return $this->regiOplBelCustomFields;
  }
  
  public function getRegiOplBelCustomFieldsByName(){
    $return = array();
    foreach ($this->regiOplBelCustomFields as $id => $field){
      $return[$field['name']] = $field;
    }
    
    return $return;
  }
  
  // Registratie Opleiding Belangstelling - Custom Fields Options Values
  protected function setRegiOplBelCustomFieldsOptionValues(){
    foreach($this->regiOplBelCustomFields as $id => $field){
      if(isset($field['option_group_id']) and !empty($field['option_group_id'])){
        $this->regiOplBelCustomFieldsOptionValues[$field['name']] = array();
        
        try {
          $params = array(
            'version' => 3,
            'sequential' => 1,
            'option_group_id' => $field['option_group_id'],
            'sort' => 'weight', // sort by weight or you know
          );
          $result = civicrm_api('OptionValue', 'get', $params);        

        } catch (CiviCRM_API3_Exception $ex) {
          throw new Exception('Could not find registratie opleiding belangstelling custom fields option values, '
            . 'error from API CustomField get: '.$ex->getMessage());
        }
        
        foreach ($result['values'] as $key => $value){
          $this->regiOplBelCustomFieldsOptionValues[$field['name']][$value['id']] = $value; // you have to use the id, because al long name is sorted to around the 30 characters, so it can be that the name is double
        }
      }
    }
  }
    
  public function getRegiOplBelCustomFieldsOptionValues(){
    return $this->regiOplBelCustomFieldsOptionValues;
  }
    
  // Registratie Opleiding Belangstelling - Custom Values
  protected function setRegiOplBelCustomValues(){
    try {
      $query = 'SELECT * FROM '.$this->getRegiOplBelCustomGroupField('table_name').' WHERE entity_id= %1';
      $params = array(1 => array($this->contact_id, 'Positive'));
      $dao = CRM_Core_DAO::executeQuery($query, $params);
      
      $RegiOplBelCustomFieldsByName = $this->getRegiOplBelCustomFieldsByName();
      
      if ($dao->fetch()) {       
        foreach($dao as $field => $value){
          switch($field){
            case $RegiOplBelCustomFieldsByName['Vliegschool']['column_name']:
            case $RegiOplBelCustomFieldsByName['Vooropleiding']['column_name']:
            case $RegiOplBelCustomFieldsByName['Overige_opleiding']['column_name']:
            case $RegiOplBelCustomFieldsByName['Algemene_onderwerpen']['column_name']:
            case $RegiOplBelCustomFieldsByName['Commissies']['column_name']:
            case $RegiOplBelCustomFieldsByName['Groepscommissie']['column_name']:
            case $RegiOplBelCustomFieldsByName['Vliegschool']['column_name']:
              echo('$field: ' . $field) . '<br/>' . PHP_EOL;
              $values = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
              foreach($values as $key => $value){
                if(!empty($value)){
                  $this->regiOplBelCustomValues[$field][$value] = $value;
                }
              }
              break;
            
            default:
              $this->regiOplBelCustomValues[$field] = $value;
          }
        }
      }else {
        // there could be no current values
        /*echo('No custom values, '
        . 'error from function setRegiOplBelCustomValues');
        CRM_Utils_System::civiExit();*/
      }  
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find registratie opleiding belangstelling custom values, '
        . 'error from CRM_Core_DAO: '.$ex->getMessage());
    }
  }
  
  public function getRegiOplBelCustomValues(){
    return $this->regiOplBelCustomValues;
  }
  
  public function getRegiOplBelCustomValuesField($field){
    return $this->regiOplBelCustomValues[$field];
  }
}