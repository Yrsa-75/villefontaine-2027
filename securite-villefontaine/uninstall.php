<?php
/**
 * Désinstallation : toutes les données de l'extension sont supprimées
 * (réglages, blocages, journal, appareils connus des comptes).
 *
 * @package SecuriteVillefontaine
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

foreach ( array( 'vf_securite_reglages', 'vf_securite_blocages', 'vf_securite_journal', 'vf_securite_version', 'vf_securite_fichiers_etat', 'vf_securite_fichiers_a_ecrire' ) as $vf_option ) {
	delete_option( $vf_option );
}
delete_metadata( 'user', 0, 'vf_securite_appareils', '', true );
wp_clear_scheduled_hook( 'vf_securite_purge' );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'vf_securite_journal' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
