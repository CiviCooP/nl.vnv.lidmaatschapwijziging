{* HEADER *}

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="top"}
</div>

{* FIELD EXAMPLE: OPTION 1 (AUTOMATIC LAYOUT) *}

{foreach from=$elementNames item=elementName}
  
  {if ($form.$elementName.name eq 'Algemene_onderwerpen') or ($form.$elementName.name eq 'Commissies') or ($form.$elementName.name eq 'Groepscommissie')}
    <div class="crm-section">
      <div class="label">{$form.$elementName.label}</div>
      <div class="content">{$form.$elementName.html}</div>
      <div class="clear"></div>
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