{* HEADER *}

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="top"}
</div>

{* FIELD EXAMPLE: OPTION 1 (AUTOMATIC LAYOUT) *}

<table class="form-layout-compressed">
  {foreach from=$elementNames item=elementName}
    
    {if $form.$elementName.name eq 'start_date'}
      <tr class="crm-membership-{$form.$elementName.name}">
        <td class="label">{$form.$elementName.label}</td>
        <td>
           {include file="CRM/common/jcalendar.tpl" elementName=$form.$elementName.name}<br/>
          <br/>
          <span class="description">{ts}First day of current continuous membership period. Start Date will be automatically set based on Membership Type if you don't select a date.{/ts}</span>
        </td>
      </tr>

    {elseif $form.$elementName.name eq 'end_date'}
      <tr class="crm-membership-{$form.$elementName.name}">
        <td class="label">{$form.$elementName.label}</td>
        <td>
          {include file="CRM/common/jcalendar.tpl" elementName=$form.$elementName.name}<br/>
         <span class="description">{ts}Latest membership period expiration date. End Date will be automatically set based on Membership Type if you don't select a date.{/ts}</span>
        </td>
      </tr>
      
    {else}
      <tr class="crm-membership-{$form.$elementName.name}">
        <td class="label">{$form.$elementName.label}</td>
        <td>{$form.$elementName.html}</td>
      </tr>
    {/if}

  {/foreach}

</table>

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

{literal}
<script type="text/javascript">
cj( document ).ready(function($) {
  function enableDisableMembershipStatus(){
    if(cj('#is_override').is(':checked')){
      cj('#status_id').removeAttr('disabled');
    }else {
      cj('#status_id').attr('disabled', 'disabled');
    }
  }

  enableDisableMembershipStatus();

  cj('#is_override').change(function () {
    enableDisableMembershipStatus();
  });

});
</script>
{/literal}