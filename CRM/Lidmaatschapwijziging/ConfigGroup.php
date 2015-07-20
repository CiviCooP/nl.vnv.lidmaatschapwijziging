<?php
/**
 * Class following Singleton pattern for specific extension configuration
 *
 * @author Jan-Derek (CiviCooP) <j.vos@bosqom.nl>
 */
class CRM_Lidmaatschapwijziging_ConfigGroup {
  
  /*
   * singleton pattern
   */
  static private $_singleton = NULL;
  
  // Contact
  protected $contact_id = 0;
  protected $contact = array();
  
  /**
   * Constructor
   */
  function __construct($contact_id) {
    // Contact
    $configContact = CRM_Lidmaatschapwijziging_ConfigContact::singleton($contact_id);   
    $this->contact = $configContact->getContact();
    $this->contact_id = $contact_id;
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
      self::$_singleton = new CRM_Lidmaatschapwijziging_ConfigGroup($contact_id);
    }
    return self::$_singleton;
  }
  
  // Contact
  public function getContact() {
    return $this->contact;
  }
}