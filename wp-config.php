<?php
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
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wp_cursoemvideo' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

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
define( 'AUTH_KEY',         '1y9%f~wItgYQU.zRuG0^50<=s?C%chm:bIVDkI7),VhL6b{Bh8*@3MwV965&(SDW' );
define( 'SECURE_AUTH_KEY',  ';M%ik.YfCmnNxx4|^:AQ.I0K*}V8O1/:@,c7Peh0~wX=5Nv^W8GcIS7|<YVIu-DP' );
define( 'LOGGED_IN_KEY',    'sA/)58ZHYZhud7va9df04GfzhA1:dfn6X{NhX8khE|9T<>,)BEl.sCX,)laN/d0:' );
define( 'NONCE_KEY',        '`L2U^S(P|bPu`n/>S8W[r5B^J:$ ju;iC|K29O]JP<x?kt>9wA^|e_sJ`?%QtIBh' );
define( 'AUTH_SALT',        'z{+5)F&J9RTCEPUb}F{]{ty2XItW$:cEJ8;(C;Tvsq*544d@Vz0W!<QcF?}X>=xw' );
define( 'SECURE_AUTH_SALT', 'qDQrv93jH6rGB382AO&GWTn&6:/vw]><BK.CDTS5;</,jrAgH.;ib#r9.K5/vd*L' );
define( 'LOGGED_IN_SALT',   ')`_7`~U7#D$z53)+G,-AQ((r}#1N{n6yxzE)Ejr5)n|*Xxj-coujUtf[BMRUj@RU' );
define( 'NONCE_SALT',       'JOskkujft0Q~?.rBr*KcvGp~x@ncJdjEidmMeQ;;]=6XSCA0w0bsLbJ:Uv 7Nl:g' );

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
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
