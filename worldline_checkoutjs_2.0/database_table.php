<?php
function database_install1()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "worldlinedetails";
    $query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name));
    if (!$wpdb->get_var($query) == $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name(
            id int NOT NULL AUTO_INCREMENT,  
            orderid int NOT NULL,
            merchantid text NOT NULL,
            PRIMARY KEY (id)
            )$charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
