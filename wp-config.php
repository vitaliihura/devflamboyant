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
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('WP_CACHE', true);
define( 'WPCACHEHOME', '/var/www/vhosts/flamboyant-carver.65-108-229-32.plesk.page/httpdocs/wp-content/plugins/wp-super-cache/' );
define( 'DB_NAME', 'devflamboyant' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '0000' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

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
define('AUTH_KEY', 'x09+6x8Rp0+&%5)HTZ0y75eT*6J10jA%7-DK54PFCpv87z59&R4X0Z316wS0%tMG');
define('SECURE_AUTH_KEY', 'P|0/KOdf2*T7:[[r&PIz]e@k5GX3!us5&9!UkJ0h03O52@1)~187s~@(1bjMSCe4');
define('LOGGED_IN_KEY', '5&/Spa;@UJ-q3)ds;usG(zr2UVl689o7;1[/7FnNgc*K[5fWdKk5[e#RigdGr3N_');
define('NONCE_KEY', 'X3_JzU4(p)3q|cn*@CVB123H!//I41%6#4PWU8Ii2K%R8(j17dlj76s4(cKbp%Hj');
define('AUTH_SALT', 'a]FscCl5xLi)x4n/xb0u!Y6ce53C0@Lp((8E*D86#&]35~8byqfda~c7m+_Mh7if');
define('SECURE_AUTH_SALT', 'oj%2!w!_-58D2%R%af7/mu*j:e1ve3NbDiA[~(C@G47(@%0c1_5cH*CWS@Rs*W7!');
define('LOGGED_IN_SALT', '&;)[J2rVNFWaM%3&C/D01Z430;;fPC]EQ-tdRyA1pVQ_x46_;g)*j(%[n!En8I[:');
define('NONCE_SALT', '8E|1u4R1Er%HzxJ5gA@L*O)n78+3Vm_#!mR)V2:z3v[i#@l4#1-@!P@v|4N-/20[');


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'nt_';



/* Add any custom values between this line and the "stop editing" line. */

define('WP_ALLOW_MULTISITE', true);
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
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_MEMORY_LIMIT', '256M' );

define( 'IMPORT_DEBUG', true );

define( 'DUPLICATOR_AUTH_KEY', '#*>js+XLoJCr/+Pe`%-,02rZdcCv/DdK{j(QF]>O-#NF!aTE?tzhpWg4CD`L(Zgj' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
