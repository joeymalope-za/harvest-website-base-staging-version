<?php
include '../public/wp-load.php';
echo "WP loaded\n";

function create_test_prescription() {
    update_user_meta(1, "active_prescription", json_decode('{"dosage": "28", "dosage_unit": "grams", "frequency": "monthly", "thc_content": "22", "created_at": "1707142387", "expiration_date": "1722694387", "duration": "6"}'));
}
create_test_prescription();