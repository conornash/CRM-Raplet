<?php

function makeLink($functionstring, $URLtoLink)
{
  if ($URLtoLink)
    {
      $thelink = "<a href=\"" . $URLtoLink . "\">" . $functionstring . "</a>";
      return  $thelink;
    }
  else
    {
      return $functionstring;
    }
}

function htmlRecord($sfRecord, $property)
{
  if (property_exists($sfRecord, $property))
    {
      $value = "<li class=\"sf" . $property . "\">" . $sfRecord->$property . "</li>";
      return $value;
    }
    else
      {
	return "";
      }
}

$callback = $_GET['callback'];
$parameters = array();
$html = "";

if(isset($_GET['show']) && $_GET['show'] === 'metadata')
  {
    //Yes, 'show' is set to 'metadata'
    // 'metadata' section starts here


    $parameters['description'] = "Custom Salesforce Raplet.";
    $parameters['welcome_text'] = "<p>View Salesforce info for a person.</p>";
    $parameters['icon_url'] = "https://s3.amazonaws.com/satisfaction-production/public/uploaded_images/7221881/salesforce_large.jpg";
    $parameters['preview_url'] = "http://kloutlet.com/images/preview.png";
    $parameters['provider_name'] = "Conor Nash";
    $parameters['name'] = "Salesforcelet";
    $parameters['provider_url'] = "http://conornash.com/";
    $parameters['data_provider_name'] = "Salesforce";
    $parameters['data_provider_url'] = "http://www.salesforce.com";
    $parameters['html'] = "";
    $parameters['css'] = "";
    $object = $callback."(".json_encode($parameters).")";
  }
else
  {

    ini_set("soap.wsdl_cache_enabled", "0");
    $callback = $_GET['callback'];
    $email = $_GET['email'];
    $record = "";
    require_once('./constants.php');
    require_once('./zendesk.wrapper.php');
    require_once('./soapclient/SforcePartnerClient.php');
    require_once('./soapclient/SforceHeaderOptions.php');
   // Zendesk querying stuff
    $zd = new Zendesk($ZendeskDomain, $ZendeskUsername, $ZendeskPassword);
    // returns users list in XML format (default)
    $xmlUserResult = $zd->get(ZENDESK_USERS, array('query' => array('query' => $email)));
    $UserResult = new SimpleXMLElement($xmlUserResult);
    $ZendeskInfo = false;
    if(is_object($UserResult->user->id))
      {
	$ZendeskUserID = $UserResult->user->id;
	$ZendeskOrgID = $UserResult->user->{'organization-id'};
	$ZendeskInfo = true;
	$ZendeskHTML = "";
	$ZendeskHTML .= makeLink("<li class=\"ZendeskUser\">Zendesk Tickets for User</li>", "http://" . $ZendeskDomain . ".zendesk.com/users/" . $ZendeskUserID);
	$ZendeskHTML .= makeLink("<li class=\"ZendeskOrg\">Zendesk Tickets for Company</li>", "http://" . $ZendeskDomain . ".zendesk.com/organizations/" . $ZendeskOrgID . "?page=1&select=tickets");
      }

    // Salesforce querying stuff
    $sfObject = new SforcePartnerClient();
    $sfObject->createConnection($SalesforcePartnerWSDL);
    $crmHandle = $sfObject->login($SalesforceUsername, $SalesforcePassword . $SalesforceSecurityToken);
    $query = "SELECT Id, FirstName, LastName, Title, Account.Name, Account.Id FROM Contact WHERE email='" . stripslashes($_GET['email']) . "'";
    $sfResponse = $sfObject->query($query);

    $html .= '<p><ul>';
    if(strlen($ZendeskHTML) > 0)
      {
	$html .= $ZendeskHTML;
      }
    if (count($sfResponse->records) > 0)
      {
	foreach ($sfResponse->records as $record)
	      {
		$html .= makeLink("Salesforce Contact Info", "https://na11.salesforce.com/" . $record->Id);
		$html .= htmlRecord($record->fields, 'Title');
		foreach($record->fields as $field)
		  {
		    if(strlen($field->fields->Name) > 0)
		      {
			$html .= makeLink(htmlRecord($field->fields, 'Name'), "https://na11.salesforce.com/".$field->Id);
		      }
		  }
	      }
	$html .= '<p><ul>';
      }
    else
      {
	$html='<div class="sfNoContactName">No Data Available in Salesforce Instance</div><div class="sfNoContactName">This is a New Contact.</div>';
      }

    $parameters['css'] = "div.sfNoContactName 
{ color: red; } div.sfName { font-size: 1.5em; }";
    $parameters['html'] = $html;
    $parameters['js'] = "";
    $parameters['status'] = 200;
    $object = $callback."(".json_encode($parameters).")";
  }
echo($object); 

?>
