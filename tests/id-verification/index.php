<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification test page</title>
</head>
<body>

<?php
function plugin_dir_path($dir) {
    return '../../public/wp-content/plugins/harvest/';
}
define('ID_API_DOMAIN',"http://id-api.local:3000/");
include '../../public/wp-content/plugins/harvest/harvest.php';
$verification_id = $_GET["verificationId"];
if (empty($verification_id)) {
    $verification_id = verify(12311, "CAMPBELL JOHN SMITH");
    echo "<script>window.history.pushState({page: 'verification'}, 'Verification in progress', 'index.php?verificationId=$verification_id');</script>";
} else {
    echo validate_verification($verification_id);
}
?>

<script type="text/javascript">
    class IdentityVerification {
        eventListener = (event) => {
            if(event.source !== this.iframe.contentWindow) {
                return
            }

            const { type } = event.data

            if(type === 'user-exit') {
                this.onExit && this.onExit()
                this.close()
            }

            if(type === 'verification-completed') {
                this.onComplete && this.onComplete(event.data.verification)
                this.close()
            }
        }

        encryptString(string, secret) {
            const key = CryptoJS.enc.Utf8.parse(secret);
            const iv = CryptoJS.lib.WordArray.random(16);

            const encrypted = CryptoJS.AES.encrypt(string, key, {
                iv: iv,
                mode: CryptoJS.mode.CBC,
                padding: CryptoJS.pad.Pkcs7,
            });

            const ciphertext = encrypted.ciphertext;
            const bytes = iv.concat(ciphertext);

            return bytes.toString(CryptoJS.enc.Base64);
        }

        start = async ({ token }) => { try {
            const url = `${this.verificationUrl}?token=${token}`
            const iframe = document.createElement('iframe');
            iframe.src = url
            iframe.allowTransparency = "true"
            iframe.allow = "camera"

            iframe.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            background-color: transparent;
        `;

            document.body.appendChild(iframe);
            this.iframe = iframe

            window.addEventListener('message', this.eventListener);
        } catch(error) {
            this.onError && this.onError(error)
        }}

        close() {
            if(this.iframe) {
                this.iframe.parentNode.removeChild(this.iframe);
                this.iframe = null;

                window.removeEventListener('message', this.eventListener);
            }
        }

        constructor({
                        onComplete,
                        onExit,
                        onError,
                    }) {
            this.verificationUrl = verification_url;
            this.onComplete = onComplete
            this.onExit = onExit
            this.onError = onError
        }
    }

    const identityVerification = new IdentityVerification({
        onComplete: (data) => window.location.reload(),
        onClose: () => window.location.reload(),
        onError: (error) => window.location.reload()
    });

    identityVerification.start({ token: verification_token })
</script>


</body>
</html>