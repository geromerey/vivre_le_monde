<?php
/**
 * La configuration de base de votre installation WordPress.
 *
 * Ce fichier contient les réglages de configuration suivants : réglages MySQL,
 * préfixe de table, clés secrètes, langue utilisée, et ABSPATH.
 * Vous pouvez en savoir plus à leur sujet en allant sur
 * {@link http://codex.wordpress.org/fr:Modifier_wp-config.php Modifier
 * wp-config.php}. C’est votre hébergeur qui doit vous donner vos
 * codes MySQL.
 *
 * Ce fichier est utilisé par le script de création de wp-config.php pendant
 * le processus d’installation. Vous n’avez pas à utiliser le site web, vous
 * pouvez simplement renommer ce fichier en "wp-config.php" et remplir les
 * valeurs.
 *
 * @package WordPress
 */

// ** Réglages MySQL - Votre hébergeur doit vous fournir ces informations. ** //
/** Nom de la base de données de WordPress. */
define('DB_NAME', 'vivre_le_monde');

/** Utilisateur de la base de données MySQL. */
define('DB_USER', 'root');

/** Mot de passe de la base de données MySQL. */
define('DB_PASSWORD', 'root');

/** Adresse de l’hébergement MySQL. */
define('DB_HOST', 'localhost');

/** Jeu de caractères à utiliser par la base de données lors de la création des tables. */
define('DB_CHARSET', 'utf8mb4');

/** Type de collation de la base de données.
  * N’y touchez que si vous savez ce que vous faites.
  */
define('DB_COLLATE', '');

/**#@+
 * Clés uniques d’authentification et salage.
 *
 * Remplacez les valeurs par défaut par des phrases uniques !
 * Vous pouvez générer des phrases aléatoires en utilisant
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ le service de clefs secrètes de WordPress.org}.
 * Vous pouvez modifier ces phrases à n’importe quel moment, afin d’invalider tous les cookies existants.
 * Cela forcera également tous les utilisateurs à se reconnecter.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '*5bR%!;*wEZRLxS;)-{kSAY2>8:mOU}]R~(M<=ZGAXN@usJ7x5Kd oWFQ6`*c 6T');
define('SECURE_AUTH_KEY',  'va,la]8^=&<{!>? !^/RnG#vpkz?o-i!v8=k%dF}{EkfX]1Z>WU-#3ckWP4ePyv=');
define('LOGGED_IN_KEY',    'dW]O:o2l>.,.LV=BckR-BFKq0Y} -&ML6}7Ws7~o#T q2AT>zDFU.Oe TAKp%69z');
define('NONCE_KEY',        '#%S2kvYC;K^?M?J(RX4S-<a8}6by<Bk1JcGfsb87K|I0*jC%doIF&m(zxG4ee0|y');
define('AUTH_SALT',        'C^j@3v+Vu?u9]PhW/>>$E`uk/_du{*u=d]|3i1=l1)j)}j?r}.97:MEmNfP^CS.r');
define('SECURE_AUTH_SALT', '092;9$er8L0/^%q^)6d^XMEn/My_&C,:xFq6wk3Y.f,xU:.te+z-VuY@]L-#{Izr');
define('LOGGED_IN_SALT',   ' }Xg>+_34QL(O~0[?x)$=;VxUc/_Zo/_qQLm!bx)bwT8+(_KZeH8&i1IHtQ:ytM3');
define('NONCE_SALT',       '{-ZoE2`ROHb6d*32PMi~crj~=NDo,t.i%skszk3d`0*HWV<ja*7(m+Vuk=`Lh#[1');
/**#@-*/

/**
 * Préfixe de base de données pour les tables de WordPress.
 *
 * Vous pouvez installer plusieurs WordPress sur une seule base de données
 * si vous leur donnez chacune un préfixe unique.
 * N’utilisez que des chiffres, des lettres non-accentuées, et des caractères soulignés !
 */
$table_prefix  = 'wp_';

/**
 * Pour les développeurs : le mode déboguage de WordPress.
 *
 * En passant la valeur suivante à "true", vous activez l’affichage des
 * notifications d’erreurs pendant vos essais.
 * Il est fortemment recommandé que les développeurs d’extensions et
 * de thèmes se servent de WP_DEBUG dans leur environnement de
 * développement.
 *
 * Pour plus d’information sur les autres constantes qui peuvent être utilisées
 * pour le déboguage, rendez-vous sur le Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* C’est tout, ne touchez pas à ce qui suit ! */

/** Chemin absolu vers le dossier de WordPress. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Réglage des variables de WordPress et de ses fichiers inclus. */
require_once(ABSPATH . 'wp-settings.php');