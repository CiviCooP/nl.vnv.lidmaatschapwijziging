<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Lidmaatschapwijziging_Form_LidmaatschapWijziging extends CRM_Core_Form {
     
  public $_contactId;
  
  /**
   * This function is called prior to building and submitting the form
   */
  function preProcess() {   
    /*$this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    if(empty($this->_contactId)){
      CRM_Core_Error::statusBounce(ts('Could not get a contact id.'), NULL, ts('Lidmaatschap Wijziging - Contact')); // this also redirects to the default civicrm page
    }
    
    $url = CRM_Utils_System::url('civicrm/lidmaatschapwijziging/contact', 'reset=1&cid=' . $this->_contactId);
    CRM_Utils_System::redirect($url);*/
  }
}
