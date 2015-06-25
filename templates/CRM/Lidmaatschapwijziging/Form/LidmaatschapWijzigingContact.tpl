{* HEADER *}

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="top"}
</div>

{* FIELD EXAMPLE: OPTION 1 (AUTOMATIC LAYOUT) *}

{foreach from=$elementNames item=elementName}
  
  {if $form.$elementName.name eq 'current_employer'}
    <div class="crm-section">
      <div class="label">{$form.$elementName.label}&nbsp;{help id="id-current-employer" file="CRM/Contact/Form/Contact.hlp"}</div>
      <div class="content">{$form.$elementName.html|crmAddClass:twenty}</div>
      <div id="employer_address" style="display:none;"></div>
    </div>
  
    {include file="CRM/Contact/Form/CurrentEmployer.tpl"}
  
  {elseif $form.$elementName.name eq 'Datum_in_dienst'}
    <div class="crm-section">
      <div class="label">{$form.$elementName.label}</div>
      <div class="content">{include file="CRM/common/jcalendar.tpl" elementName=$form.$elementName.name}</div>
      <div class="clear"></div>
    </div>
  
  {else}
    <div class="crm-section">
      <div class="label">{$form.$elementName.label}</div>
      <div class="content">{$form.$elementName.html}</div>
      <div class="clear"></div>
    </div>
  {/if}
  
{/foreach}

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>