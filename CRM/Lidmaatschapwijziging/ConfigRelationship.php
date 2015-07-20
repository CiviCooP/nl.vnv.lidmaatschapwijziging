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
  //protected $relationshipTypeEmployerOfName = array('name_a_b' => 'Employee of', 'name_b_a' => 'Employer of', 'contact_type_a' => 'Individual', 'contact_type_b' => 'Organization');
  //protected $relationshipTypeEmployerOf = array();
  //protected $relationshipTypeEmployerOfId = 0;
  
  // Relationship Current Employer
  //protected $relationshipCurrentEmployer = array();
  //protected $relationshipCurrentEmployerId = 0;
  
  // Relationships
  protected $relationship = array();
  protected $relationshipTypes = array();

  // Relationship Last
  protected $relationshipLast = array();
  protected $relationshipLastId = 0;
  
  // Contact a en b
  protected $contact_id_a = 0;
  protected $contact_a = array();
  
  protected $contact_id_b = 0;
  protected $contact_b = array();

  /**
   * Constructor
   */
  function __construct($contact_id) {
    // Contact
    $configContact = CRM_Lidmaatschapwijziging_ConfigContact::singleton($contact_id);   
    $this->contact = $configContact->getContact();
    $this->contact_id = $contact_id;
    
    // Relationship Type
    //$this->setRelationshipTypeEmployerOf();
    
    // Relationship Current Employer
    //$this->setRelationshipCurrentEmployer();
    
    // Relationships
    $this->setRelationships();
    
    $this->setrelatiosnhipTypes();
    
    // Relationship last, Contact a en b
    //$this->setRelationshipLast();
    
    // Contact a en b
    //$this->setContactA();
    //$this->setContactB();
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
  /*protected function setRelationshipTypeEmployerOf(){
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
  }*/
  
  // Relationship Current Employer
  /*protected function setRelationshipCurrentEmployer(){
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
  }*/
  
  // Relationships
  protected function setRelationships(){
    try {
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'contact_id_a' => $this->contact_id,
        'sort' => 'is_active DESC, start_date ASC',
        //'is_active' => 1, // we want, if there is no active relationship also the in active
      );
      $result = civicrm_api('Relationship', 'get', $params);
            
      foreach ($result['values'] as $key => $relationship){
        $this->relationships[$relationship['id']] = $relationship;
        
        $this->relationships[$relationship['id']]['contact_a'] = array();
        
        // contact a
        try {
          $params = array(
            'version' => 3,
            'sequential' => 1,
            'contact_id' => $relationship['contact_id_a'],
          );
          $this->relationships[$relationship['id']]['contact_a'] = civicrm_api('Contact', 'getsingle', $params);

        } catch (CiviCRM_API3_Exception $ex) {
          throw new Exception('Could not find contact a in relationships, '
            . 'error from API Contact getsingle: '.$ex->getMessage());
        }
        
        $this->relationships[$relationship['id']]['contact_b'] = array();
        
        // contact b
        try {
          $params = array(
            'version' => 3,
            'sequential' => 1,
            'contact_id' => $relationship['contact_id_b'],
          );
          $this->relationships[$relationship['id']]['contact_b'] = civicrm_api('Contact', 'getsingle', $params);

        } catch (CiviCRM_API3_Exception $ex) {
          throw new Exception('Could not find contact b in relationships, '
            . 'error from API Contact getsingle: '.$ex->getMessage());
        }
        
        // note
        $this->relationships[$relationship['id']]['notes'] = array();        
        try {         
          $params = array(
            'version' => 3,
            'sequential' => 1,
            'entity_id' => $relationship['id'],
            'entity_table' => 'civicrm_relationship',
            'contact_id' => $this->contact_id,
            'options' => array('limit' => 1),
          );
          $result = civicrm_api('Note', 'getsingle', $params);
          
          if(!isset($result['is_error']) or !$result['is_error']){ // if there is no error   
            $this->relationships[$relationship['id']]['notes'] = $result;
          }          

        } catch (CiviCRM_API3_Exception $ex) {
          throw new Exception('Could not find note in relationships, '
            . 'error from API Note getsingle: '.$ex->getMessage());
        }
      }
                  
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find relationship, '
        . 'error from API Relationship get: '.$ex->getMessage());
    }
  }
  
  public function getRelationships(){
    return $this->relationships;
  }
  
  public function getRelationship($relationship_id){
    return $this->relationships[$relationship_id];
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
  
  public function getRelationshipTypeById($relationship_type_id){
    return $this->relationshipTypes[$relationship_type_id];
  }

  /**
   * This function returns only the relationships that have
   * the same contact_type_b as the contact and contact sub type
   * 
   * @param type $contact_type_b
   * @return type
   */
  public function getRelationshipTypeContactTypeB($contact_type_b, $contact_sub_type_b){
    $array = array();
    foreach($this->relationshipTypes as $id => $relationship_type){
      if($contact_type_b == $relationship_type['contact_type_b']){
        if($contact_sub_type_b == $relationship_type['contact_sub_type_b']){
          $array[$relationship_type['id']] = $relationship_type;
        }
      }
    }
    return $array;
  }


  // Relationship last, Contact id a en b
  /*protected function setRelationshipLast(){
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
    
    // set contact id a
    if(isset($relationshipLast['contact_id_a']) and !empty($relationshipLast['contact_id_a'])){
      $this->contact_id_a = $relationshipLast['contact_id_a'];
    }
    
    // set contact id b
    if(isset($relationshipLast['contact_id_b']) and !empty($relationshipLast['contact_id_b'])){
      $this->contact_id_b = $relationshipLast['contact_id_b'];
    }
    
    $this->relationshipLast = $relationshipLast;
  }
  
  public function getRelationshipLast(){
    return $this->relationshipLast;
  }
  
  public function getRelationshipLastId(){
    return $this->relationshipLastId;
  }*/
  
  // Contact id a en b
  /*public function getContactIdA() {
    return $this->contact_id_a;    
  }
  
  public function getContactIdB() {
    return $this->contact_id_b;    
  }
  
  // Contact a
  protected function setContactA(){
    try {
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'contact_id' => $this->contact_id_a,
      );
      $this->contact_a = civicrm_api('Contact', 'getsingle', $params);
      
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find contact a, '
        . 'error from API Contact getsingle: '.$ex->getMessage());
    }
  }
  
  public function getContactA(){
    return $this->contact_a;
  }
  
  // Contact b
  protected function setContactB(){
    try {
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'contact_id' => $this->contact_id_b,
      );
      $this->contact_b = civicrm_api('Contact', 'getsingle', $params);
      
    } catch (CiviCRM_API3_Exception $ex) {
      throw new Exception('Could not find contact b, '
        . 'error from API Contact getsingle: '.$ex->getMessage());
    }
  }
  
  public function getContactB(){
    return $this->contact_b;
  }*/
}