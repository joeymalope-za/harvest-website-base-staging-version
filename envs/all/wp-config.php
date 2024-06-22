<?php
define('WP_CACHE', true); // Added by WP Rocket

if (!defined('KINSTA_DEV_ENV')) {
    define('KINSTA_DEV_ENV', ${KINSTA_DEV_ENV:-false});};
if (!defined('JETPACK_STAGING_MODE')) {
    define('JETPACK_STAGING_MODE', ${JETPACK_STAGING_MODE:-false});};

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

define('AUTOMATIC_UPDATER_DISABLED', true);

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', '${DATABASE:-harvestj}');

/** Database username */
define('DB_USER', '${DB_USER:-harvestj}');

/** Database password */
define('DB_PASSWORD', '${DB_PASSWORD:-harvestj}');

/** Database hostname */
define('DB_HOST', '${DB_HOST:-localhost}');

/** Database charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The database collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', 'yHLrABp2Qy{MtP/$-`y|9V~a}N#qz[z}PWHFHU8)=Ig5PuZ}T@qgUcq lT7s,@MK');
define('SECURE_AUTH_KEY', '<J{mZ9S2Kc9Fhz^eG_Y7%<B >D9WhFUUSX4lqU{yB7hO78/<7Ay;kD_G+ZZp9cI$');
define('LOGGED_IN_KEY', 'n[|$k$jp+Q0[JYxd8#WL7%Li<G-:h;foY8+)w>+9Q4vuCsR,PA!$VORkq?Fd4IO6');
define('NONCE_KEY', '*q%$[@-kpEcI;SU%:tFh.VLKrL(..3Qd4IqX<!d|[9}HfIZki}S4uWxdky^1sG[{');
define('AUTH_SALT', '+.{%QSL=|wev+NS{wrx{/{#;[V2`A;SLI +n7%7v#w!ufStoqar,r{Fxn> ! pD6');
define('SECURE_AUTH_SALT', 'N9;Ll+MQ?|ONKozkJRk? mC-d]b$f3)mP iG]<Tk(^st_hoiSOgm$;= T4r8y;cK');
define('LOGGED_IN_SALT', 'bD@JOIh~DWx!oNh]pmu|LBE70J-u+C!ewh %TjKjp-[(g*4{mm6.gI Rb->?1BT%');
define('NONCE_SALT', 'BMDe,pb`_[oxg#[O``qN]9p*]Y0O<B?7~T-Aqq;Gw)p4o~*`(Pj1 a@FKC{Dc3;W');
define('WP_CACHE_KEY_SALT', 'Yxrh;*U3zQHHwv$+>P@,37NfzSm_J&;%@c<{:6XofvaajFR =OzwLWVZQ|8$Dj5?');


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define('WP_DEBUG', ${WP_DEBUG:-false});
define('WP_DEBUG_LOG', ${WP_DEBUG_LOG:-false});
define('WP_DEBUG_DISPLAY', ${WP_DEBUG_DISPLAY:-false});


/* Add any custom values between this line and the "stop editing" line. */

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

// config for SSL/TLS behind reverse-proxy
if(!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
}

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strpos($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') !== false) {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = 443;
    define('FORCE_SSL_ADMIN', true);
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
