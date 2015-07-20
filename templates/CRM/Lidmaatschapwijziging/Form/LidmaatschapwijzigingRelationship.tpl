{* HEADER *}

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="top"}
</div>

{* FIELD EXAMPLE: OPTION 1 (AUTOMATIC LAYOUT) *}

<table class="form-layout-compressed">
  {foreach from=$elementNames item=elementName}

    {if $form.$elementName.name eq 'relationship_type_id'}
      <tr class="crm-membership-{$form.$elementName.name}">
        <td class="label">{$form.$elementName.label}</td>
        <td>{$form.$elementName.html}</td>
        <td>{$sort_name_b}</td>
      </tr>
      
    {elseif $form.$elementName.name eq 'start_date'}
      <tr class="crm-membership-{$form.$elementName.name}">
        <td class="label">{$form.$elementName.label}</td>
        <td colspan="2">
           {include file="CRM/common/jcalendar.tpl" elementName=$form.$elementName.name}<br/>
        </td>
      </tr>

    {elseif $form.$elementName.name eq 'end_date'}
      <tr class="crm-membership-{$form.$elementName.name}">
        <td class="label">{$form.$elementName.label}</td>
        <td colspan="2">
          {include file="CRM/common/jcalendar.tpl" elementName=$form.$elementName.name}<br/>
         <span class="description">{ts}If this relationship has start and/or end dates, specify them here.{/ts}</span>
        </td>
      </tr>

    {elseif $form.$elementName.name eq 'is_permission_a_b'}
      <tr class="crm-membership-{$form.$elementName.name}">
        <td class="label"></td>
        <td colspan="2">
          {$form.$elementName.html}
          <span id="permision_a_b-a_b">
            <strong>'{$sort_name_a}'</strong> {ts}can view and update information for {/ts} <strong>'{$sort_name_b}'</strong>
          </span>
        </td>
      </tr>
      
    {elseif $form.$elementName.name eq 'is_permission_b_a'}
      <tr class="crm-membership-{$form.$elementName.name}">
        <td class="label"></td>
        <td colspan="2">
          {$form.$elementName.html}
          <span id="permision_a_b-b_a">
            <strong>'{$sort_name_b}'</strong> {ts}can view and update information for {/ts} <strong>'{$sort_name_a}'</strong>
          </span>
        </td>
      </tr>
      
    {else}
      <tr class="crm-membership-{$form.$elementName.name}">
        <td class="label">{$form.$elementName.label}</td>
        <td colspan="2">{$form.$elementName.html}</td>
      </tr>
    {/if}

  {/foreach}
</table>

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>