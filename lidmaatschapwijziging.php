<?php
/**
 * BOS1502450 vnv.nl - wijzigingsformulier
 */

require_once 'lidmaatschapwijziging.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function lidmaatschapwijziging_civicrm_config(&$config) {
  _lidmaatschapwijziging_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function lidmaatschapwijziging_civicrm_xmlMenu(&$files) {
  _lidmaatschapwijziging_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function lidmaatschapwijziging_civicrm_install() {
  return _lidmaatschapwijziging_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function lidmaatschapwijziging_civicrm_uninstall() {
  return _lidmaatschapwijziging_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function lidmaatschapwijziging_civicrm_enable() {
  return _lidmaatschapwijziging_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function lidmaatschapwijziging_civicrm_disable() {
  return _lidmaatschapwijziging_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function lidmaatschapwijziging_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _lidmaatschapwijziging_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function lidmaatschapwijziging_civicrm_managed(&$entities) {
  return _lidmaatschapwijziging_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function lidmaatschapwijziging_civicrm_caseTypes(&$caseTypes) {
  _lidmaatschapwijziging_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function lidmaatschapwijziging_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _lidmaatschapwijziging_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * This add a button to the contact view (summary) template, how it works:
 * this script add to the region page-body ({crmRegion}, look at http://wiki.civicrm.org/confluence/display/CRMDOC/Region+Reference) a
 * template with the button html code and a javascript that moves the buton to a other place (by the #actions) 
 * 
 * @param type $contactID
 * @param type $content
 * @param type $contentPlacement
 */
function lidmaatschapwijziging_civicrm_summary( $contactID, &$content, &$contentPlacement = CRM_Utils_Hook::SUMMARY_ABOVE ){
  CRM_Core_Region::instance('page-body')->add(array('template' => 'CRM/Lidmaatschapwijziging/Page/View/LidmaatschapwijzigingButton.tpl')); 

}

