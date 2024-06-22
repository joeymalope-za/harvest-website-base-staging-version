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
include '../../envs/all/wp-content/plugins/includes/shop.php';
echo "<script>var meeting_url='https://the-harvest.daily.co/harvest_test_room';</script>";
echo "<script>var user_id='harvest_test10@test.com';</script>";
echo "<script>var user_email='harvest_test10@test.com';</script>";
echo "<script>var user_name='harvest_test10@test.com';</script>";
echo "<script>var isScheduled='false';</script>";
?>

<!-------------------------->
<script crossorigin src="https://unpkg.com/@daily-co/daily-js"></script>
<div id="noPermissions" style="display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%;">
        <p>No camera or mic permissions. Please grant the permissions and try again.</p>
        <button id="tryAgain" style="background-color: #FF9100; border: none; color: white; padding: 15px 32px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 10px 2px; cursor: pointer;">Try again</button>
    </div>
</div>


<div id="chat-container">
    <script type="text/javascript" id="zsiqchat">var $zoho = $zoho || {};
        $zoho.salesiq = $zoho.salesiq || {
            widgetcode: "b7fd66b676f247e36c2a3ee26c51c772d64fad6d61c5efd3afe53f8d44876388",
            values: {},
            ready: function () {
            }
        };
        var d = document;
        s = d.createElement("script");
        s.type = "text/javascript";
        s.id = "zsiqscript";
        s.defer = true;
        s.src = "https://salesiq.zoho.com.au/widget";
        t = d.getElementsByTagName("script")[0];
        t.parentNode.insertBefore(s, t);
        window.zoho = $zoho;
    </script>
</div>
<script>
    var meeting_url_template = '%meeting_url%';
    var meeting_start_time = null;
    var handle_events = true;

    function meetingStart() {
        try {
            callFrame = window.DailyIframe.createFrame({
                showLeaveButton: true,
                iframeStyle: {
                    position: 'absolute',
                    width: '100%',
                    height: '100%',
                    left: 0,
                    top: 0,
                    'z-index': 1000
                }
            });
        } catch (error) {
            console.log(error);
        }
        $zoho.salesiq.floatwindow.visible('hide');
        callFrame.on('left-meeting', (event) => {
            if (!handle_events)
                return;
            console.log('patient left meeting');
            window.location.reload();
        });
        callFrame.on('participant-left', (event) => {
            if (!handle_events)
                return;
            callFrame.destroy();
            if (meeting_start_time) {
                let currentTime = new Date();
                let timeElapsed = Math.abs(currentTime - meeting_start_time) / 1000;
                console.log(`doctor left the meeting after ${timeElapsed} sec`);
                let url = "/wp-json/harvest-api/call_finished_convenience";
                let params = {email: user_email};
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(params)
                })
                    .then(response => {
                        if (!response.ok) {
                            console.log("non-fatal error calling convenience callback");
                        }
                        window.location.reload();
                    });
            } else {
                console.log(`doctor left the meeting, unable to determine meeting length`);
                window.location.reload();
            }
        });
        callFrame.on('participant-joined', (event) => {
            if (!handle_events)
                return;
            console.log('doctor joined');
            meeting_start_time = new Date();
        });
        callFrame.on('camera-error', (event) => {
            handle_events = false;
            callFrame.destroy();
            noCameraPermissions();
        });
        callFrame.join({url: meeting_url, userName: user_name});
    }

    function injectMeetingLink() {
        document.querySelectorAll('iframe').forEach(item => item.contentWindow.document.body.querySelectorAll(".siq-user-message").forEach(
            msg => msg.innerHTML = msg.innerHTML.replace(meeting_url_template, `<p><button onclick="parent.meetingStart()">Start meeting</button></p>`)
            )
        );
    }

    function noCameraPermissions() {
        var modal = document.getElementById("noPermissions");
        var button = document.getElementById("tryAgain");
        modal.style.display = "block";
        button.onclick = function () {
            modal.style.display = "none";
            meetingStart();
        }
    }

    $zoho.salesiq.ready = function () {
        // 10 mins wait
        $zoho.salesiq.floatbutton.position("topright");
        $zoho.salesiq.chat.waittime(600);
        console.log("zoho chat ready");
        $zoho.salesiq.visitor.id(user_id);
        $zoho.salesiq.visitor.email(user_email);
        $zoho.salesiq.visitor.info({"scheduled": isScheduled});
        $zoho.salesiq.chat.accepttransfer(function (visitid, data) {
            console.log("accepttransfer " + JSON.stringify(data));
        });
        $zoho.salesiq.chat.attend(function (visitid, data) {
            console.log("attend " + JSON.stringify(data));
        });
        $zoho.salesiq.chat.agentMessage(function (visitid, data) {
            console.log(data);
            if (data.message.includes(meeting_url_template)) {
                injectMeetingLink();
            }
        });
        $zoho.salesiq.chat.continue(function (visitid, data) {
            injectMeetingLink();
        });
        $zoho.salesiq.floatbutton.click(function () {
            injectMeetingLink();
        });
    }
</script>

<!-------------------------->
</body>
</html>