-- Harvest development environment DB data
USE `harvestj`;

INSERT INTO wp_users (ID, user_login, user_pass, user_nicename, user_email, user_url, user_registered, user_activation_key, user_status, display_name) VALUES
(1, 'superadmin', MD5('superadmin'), 'superadmin', 'admin@harvest.au', 'http://harvest.au', '2022-09-29 06:12:26', '', 0, 'superadmin');

INSERT INTO wp_usermeta (umeta_id, user_id, meta_key, meta_value) VALUES
(12, 1, 'wp_capabilities', 'a:1:{s:13:"administrator";b:1;}'),
(13, 1, 'wp_user_level', '10');

INSERT INTO `wp_posts` VALUES
(999999,1,'2023-06-01 06:20:13','2023-05-31 20:20:13','Test product created manually<img src=\"https://harvest-dev.xyz/wp-content/uploads/2023/06/reg3-231x300.jpg\" alt=\"\" width=\"231\" height=\"300\" class=\"alignnone size-medium wp-image-11157\" />','Test THC product','','publish','closed','closed','','test-thc-product','','','2023-06-02 00:24:53','2023-06-01 14:24:53','',0,'https://harvest-dev.xyz/?post_type=product&#038;p=11156',0,'product','',0);

INSERT INTO `wp_postmeta` (post_id, meta_key, meta_value) VALUES
(999999,'_regular_price','1000'),
(999999,'_sale_price','999.99'),
(999999,'_price','999.99');

GRANT ALL PRIVILEGES ON *.* TO 'harvestj'@'%';
FLUSH PRIVILEGES;
