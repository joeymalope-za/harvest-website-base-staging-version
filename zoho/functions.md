# About
This file contains custom functions used in various Zoho services. Please make sure to update the function here, after you edit it in Zoho.

# CRM Contact
## Prescribe button
```Javascript
contactId = input.contact_id;
is_approved = input.prescription_approved;
contact = zoho.crm.getRecordById("Contacts",contactId.toLong());
harvest_uuid = contact.get("harvest_uuid");
update = zoho.crm.updateRecord("Contacts",contactId.toLong(),{"Prescription_Approved":is_approved});
info update;
headers = Map();
headers.put("Content-Type","application/json");
params = Map();
params.put("data",{"harvest_uuid":harvest_uuid,"zoho_id":contactId,"is_approved":is_approved});
response = postUrl("https://harvest-api-x83ku.kinsta.app/api/forward-b52d95b4/update_membership?auth_token=REDACTED",params,headers);
if(response.get("status_code") != 200)
{
	info "API call failed.";
}
return "Prescription issued";
```