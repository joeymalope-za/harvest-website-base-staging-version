<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification test page</title>
</head>
<body>

<?php
define('ABSPATH', __DIR__ . '/../../envs/all/');
include '../../envs/all/wp-content/plugins/includes/patient-reception.php';
activate_chat(uniqid());
?>

<div id="chat-container"></div>

<script src="https://www.gstatic.com/dialogflow-console/fast/messenger-cx/bootstrap.js?v=1"></script>
<script>
    var finalMessage;
    document.addEventListener('DOMContentLoaded', function () {
        createDFMessengerControl();
        const dfMessenger = document.querySelector('df-messenger');
        dfMessenger.addEventListener('df-button-clicked', function (event) {
            // do not react if we don't yet received final message
            if (finalMessage===undefined)
                return;
            window.location.reload();

            // --------------
            // DO NOT USE ON PRODUCTION

            // call WP API to fill in meta fields - on production, Dialogflow does that
            fetch('http://harvest.local/wp-json/harvest-api/virtual-doctor-results', {
                method: 'POST',
                headers: {
                    'Connection': 'close',
                    'Accept-Encoding': 'gzip, deflate, br',
                    'User-Agent': 'Google-Dialogflow',
                    'Accept': '*/*',
                    'authorization': 'Basic 3Q4VXN5RzJVOnh3WXFZRVl6SThD',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(
                    {
                        "detectIntentResponseId": "06b6e1e6-c022-48ac-9481-911bff577d55",
                        "intentInfo": {
                            "lastMatchedIntent": "projects/harvest-385717/locations/australia-southeast1/agents/e37ca8ce-9e44-40dc-8d10-b3abcb449913/intents/2fe38929-95d3-4111-b997-d12d7caf312a",
                            "displayName": "THC conditions",
                            "confidence": 0.69539934
                        },
                        "pageInfo": {
                            "currentPage": "projects/harvest-385717/locations/australia-southeast1/agents/e37ca8ce-9e44-40dc-8d10-b3abcb449913/flows/00000000-0000-0000-0000-000000000000/pages/829caa25-ac08-4906-a691-df3c49409199",
                            "formInfo": {},
                            "displayName": "Recognize condition"
                        },
                        "sessionInfo": {
                            "session": "projects/harvest-385717/locations/australia-southeast1/agents/e37ca8ce-9e44-40dc-8d10-b3abcb449913/environments/df373ba3-1ff6-4c1a-b70b-b9a327f97e70/sessions/"
                                + session_id
                        },
                        "fulfillmentInfo": {
                            "tag": "thc-condition"
                        },
                        "messages": [{"something": "else"}, finalMessage, {"something": "else"}]
                    }
                    )
            })
                .then(response => {
                    console.log("DialogFlow webhook imitated successfully: " + response);
                })
                .catch(error => {
                    console.log("Error imitating DialogFlow webhook: " + error);
                });


            // *****

        });
        dfMessenger.addEventListener('df-response-received', function (event) {
            console.log(event);
            var elem = event.detail.response.queryResult.fulfillmentMessages.find(el => el.hasOwnProperty("payload"));
            if (elem !== undefined) {
                finalMessage = elem;
            }
        });
    });

    function createDFMessengerControl() {
        if (session_id) {
            // Create df-messenger element
            const dfMessenger = document.createElement('df-messenger');

            // Set element attributes
            dfMessenger.setAttribute('df-cx', 'true');
            dfMessenger.setAttribute('location', 'australia-southeast1');
            dfMessenger.setAttribute('chat-title', 'Virtual doctor');
            dfMessenger.setAttribute('agent-id', 'e37ca8ce-9e44-40dc-8d10-b3abcb449913');
            dfMessenger.setAttribute('language-code', 'en');
            dfMessenger.setAttribute('intent', 'start-bot');
            dfMessenger.setAttribute('expand', 'true');
            dfMessenger.setAttribute('session-id', session_id);

            // Append the df-messenger element to the root div
            const root = document.getElementById('chat-container');
            root.appendChild(dfMessenger);
        }
    }

</script>
</body>
</html>