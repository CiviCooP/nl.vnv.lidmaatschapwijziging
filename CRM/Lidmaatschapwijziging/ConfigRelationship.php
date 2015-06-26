<?php
/**
 * Class following Singleton pattern for specific extension configuration
 *
 * @author Jan-Derek (CiviCooP) <j.vos@bosqom.nl>
 */
class CRM_Lidmaatschapwijziging_ConfigRelationship {
  
  /*
   * singleton pattern
   */
  static private $_singleton = NULL;
  
  // Contact
  protected $contact_id = 0;
  protected $contact = array();
  
  // Relationship
  // Relationship Type Employer
  protected $relationshipTypeEmployerOfName = array('name_a_b' => 'Employee of', 'name_b_a' => 'Employer of', 'contact_type_a' => 'Individual', 'contact_type_b' => 'Organization');
  protected $relationshipTypeEmployerOf = array();
  protected $relationshipTypeEmployerOfId = 0;
  
  // Relationship Current Employer
  protected $relationshipCurrentEmployer = array();
  protected $relationshipCurrentEmployerId = 0;
  
  // Relationships
  protected $relationship = array();
  protected $relationshipTypes = array();

  // Relationship Last
  protected $relationshipLast = array();
  protected $relationshipLastId = 0;


  /**
   * Constructor
   */
  function __construct($contact_id) {
    // Contact
    $configContact = CRM_Lidmaatschapwijziging_ConfigContact::singleton($contact_id);   
    $this->contact = $configContact->getContact();
    $this->contact_id = $contact_id;
    
    // Relationship Type
    $this->setRelationshipTypeEmployerOf();
    
    // Relationship Current Employer
    $this->setRelationshipCurrentEmployer();
    
    // Relationships
    $this->setRelationship();
    $this->setrelatiosnhipTypes();
    
    // Relationship last
    $this->setRelationshipLast();
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
      self::$_singleton = new CRM_Lidmaatschapwijziging_ConfigRelationship($contact_id);
    }
    return self::$_singleton;
  }
  
  // Contact
  public function getContact() {
    return $this->contact;
  }
  
  // Relationship Type
  protected function setRelationshipTypeEmployerOf(){
    try {
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'name_a_b' => $this->relationshipTypeEmployerOfName['name_a_b'],
        'name_b_a' => $this->relationshipTypeEmployerOfName['name_b_a'],
        'contact_type_a' => $this->relationshipTypeEmployerOfName['contact_type_a'],
        'contact_type_b' => $this->relationshipTypeEmployerOfName['contact_type_b'],
      );
      $this->relationshipTypeEmployerOf = civicrm_api('RelationshipType', 'getsingle', $params);
      $this->relationshipTypeEmployerOfId = $this->relationshipTypeEmployerOf['id'];
            
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find relationshiptype employer of, '
        . 'error from API RelationshipType getsingle: '.$ex->getMessage());
    }
  }
  
  public function getRelationshipTypeEmployerOf(){
    return $this->relationshipTypeEmployerOf;
  }
  
  public function getRelationshipTypeEmployerOfId(){
    return $this->relationshipTypeEmployerOfId;
  }
  
  // Relationship Current Employer
  protected function setRelationshipCurrentEmployer(){
    try {
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'contact_id_a' => $this->contact_id,
        'relationship_type_id' => $this->relationshipTypeEmployerOfId,
        'is_active' => 1,
      );
      $this->relationshipCurrentEmployer = civicrm_api('Relationship', 'getsingle', $params);
      $this->relationshipCurrentEmployerId = $this->relationshipCurrentEmployer['id'];
                  
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find relationship current employer, '
        . 'error from API Relationship getsingle: '.$ex->getMessage());
    }
  }
  
  public function getRelationshipCurrentEmployer(){
    return $this->relationshipCurrentEmployer;
  }
  
  public function getRelationshipCurrentEmployerId(){
    return $this->relationshipCurrentEmployerId;
  }
  
  // Relationships
  protected function setRelationship(){
    try {
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'contact_id_a' => $this->contact_id,
        'sort' => 'start_date ASC, is_active ASC', // to get the last relationship, i want if there are more than one relationship, in a foreach loop the variable updated with the last
        //'is_active' => 1, // we want, if there is no active relationship also the in active
      );
      $result = civicrm_api('Relationship', 'get', $params);
            
      foreach ($result['values'] as $key => $relationship){
        $this->relationship[] = $relationship;
      }
                  
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find relationship, '
        . 'error from API Relationship get: '.$ex->getMessage());
    }
  }
  
  public function getRelationship(){
    return $this->relationship;
  }
  
  protected function setRelatiosnhipTypes(){
    try {
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'is_active' => 1,
        'contact_type_a' => 'Individual',
        'contact_type_b' => 'Organization',
        //'contact_type_b!' => 'Household', // no households
        'sort' => 'label_a_b ASC',
      );
      $result = civicrm_api('RelationshipType', 'get', $params);
      foreach ($result['values'] as $key => $relationshiptype){
        $this->relationshipTypes[$relationshiptype['id']] = $relationshiptype;
      }
                  
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find relationships, '
        . 'error from API Relationship getsingle: '.$ex->getMessage());
    }
  }
  
  public function getRelatiosnhipTypes(){
    return $this->relationshipTypes;
  }

  
  // Relationship last
  protected function setRelationshipLast(){
    $relationshipLast = array();
    
    // there is just one relationship
    if(count($this->relationship) == 1){
      $relationshipLast = $this->relationship[0];
    }else {
      // get the last active one
      foreach($this->relationship as $key => $relationship){
        if($relationship['is_active'] == '1'){
          $relationshipLast = $relationship;
        }
      }
    }
    
    // if there is still no relationship get the last one
    if(empty($relationshipLast)){
      foreach($this->relationship as $key => $relationship){
        $relationshipLast = $relationship;
      }
    }
    
    // set the relationship last id, if exists
    if(isset($relationshipLast['id']) and !empty($relationshipLast['id'])){
      $this->relationshipLastId = $relationshipLast['id'];
    }
    
    $this->relationshipLast = $relationshipLast;
  }
  
  public function getRelationshipLast(){
    return $this->relationshipLast;
  }
  
  public function getRelationshipLastId(){
    return $this->relationshipLastId;
  }
}