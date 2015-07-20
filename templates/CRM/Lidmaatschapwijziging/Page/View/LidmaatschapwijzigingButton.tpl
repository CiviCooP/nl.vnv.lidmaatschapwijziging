<li id="lidmaatschapwijziging_button">
  <a title="Lidmaatschap Wijziging" class="edit button" href="/civicrm/lidmaatschapwijziging/contact?reset=1&cid={$contactId}">
    <span><div class="icon edit-icon"></div>Lidmaatschap Wijziging</span>
  </a>
</li>

{literal}
  <script type="text/javascript">
    // This script moves the button after the first li in #actions
    cj(document).ready(function($) {
      $("#lidmaatschapwijziging_button").insertAfter("#actions > li:eq(1)");
    });
  </script>
{/literal}
