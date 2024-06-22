USE `harvestj`;

INSERT INTO wp_users (ID, user_login, user_pass, user_nicename, user_email, user_url, user_registered, user_activation_key, user_status, display_name)
VALUES (1, 'superadmin', MD5('WP_PASSWORD'), 'superadmin', 'admin@harvest.au', 'http://harvest.au', '2022-09-29 06:12:26', '', 0, 'superadmin')
ON DUPLICATE KEY UPDATE
                     user_pass = MD5('WP_PASSWORD'),
                     user_nicename = VALUES(user_nicename),
                     user_email = VALUES(user_email),
                     user_url = VALUES(user_url),
                     user_registered = VALUES(user_registered),
                     user_activation_key = VALUES(user_activation_key),
                     user_status = VALUES(user_status),
                     display_name = VALUES(display_name);

INSERT IGNORE INTO wp_usermeta (umeta_id, user_id, meta_key, meta_value) VALUES
(12, 1, 'wp_capabilities', 'a:1:{s:13:"administrator";b:1;}'),
(13, 1, 'wp_user_level', '10');

-- Always update wp_usermeta data assuming the umeta_id is the unique key
UPDATE wp_usermeta SET meta_value = 'a:1:{s:13:"administrator";b:1;}' WHERE umeta_id = 12 AND user_id = 1;
UPDATE wp_usermeta SET meta_value = '10' WHERE umeta_id = 13 AND user_id = 1;

GRANT ALL PRIVILEGES ON *.* TO 'DB_USER'@'%';
FLUSH PRIVILEGES;
