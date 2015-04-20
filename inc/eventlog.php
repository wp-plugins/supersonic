<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function wpss_log($event, $info = '', $ip = null, $ip_country = null) {
	/* event codes
	1 - login country blocked
	2 - comment country blocked
	3 - xmlrpc country blocked
	4 - ip ban from comment
	5 - bad login
	6 - bruteforce ban
	7 - ip wl from comment
	8 - ip nul from comment
	9 - Purge CloudFlare cache
	10 - Report comment spam
	14 - ip ban from log
	17 - ip wl from log
	18 - ip nul from log
	20 - cron job
	*/
	$settings = get_option( "wpss_settings", array('event_log' => 0) );
	if ($settings['event_log'] == '1') {
		if ($ip == null) {
			$ip = $_SERVER["REMOTE_ADDR"];
		}
		if ($ip_country == null) {
			$ip_country = '-';
			if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
				$ip_country = $_SERVER['HTTP_CF_IPCOUNTRY'];
			}
		}
		global $wpdb;
		$wpdb->insert($wpdb->prefix."wpss_log",array('event' => $event, 'time' => current_time('timestamp'), 'ip' => $ip, 'ip_country' => $ip_country, 'info' => $info));
	}	
}

function wpss_log_clear() {
	$settings = get_option( "wpss_settings", array('event_log' => 0) );
	if (isset($settings['event_log_clear']) && intval($settings['event_log_clear']) > 0) {		
		global $wpdb;
		$wpdb->query("delete from ".$wpdb->prefix."wpss_log where time < ".(intval(current_time('timestamp'))-intval($settings['event_log_clear'])*60*60*24));
	}
}
add_action('wpss_log_clear','wpss_log_clear');

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WPSS_Log_List_Table extends WP_List_Table {
	
	function __construct() {
		global $status, $page;
		parent::__construct(array(
            'singular' => 'log',
            'plural' => 'logs',
		));
	}

	function column_default($item, $column_name) {
		return $item[$column_name];
	}

	function column_time($item) {
		$time = date_i18n( get_option( 'date_format' ).' '.get_option( 'time_format' ), intval($item['time']));
		$actions = array(
          'delete' => sprintf('<a href="?page=%s&action=delete&tab=log&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Delete', 'wpss')),
		);
		return sprintf('%s %s',
            $time,
            $this->row_actions($actions)
		);
	}

	function column_event($item) {
		return $item['event'];
	}

	function column_event_description($item) {
		$ret = "<a href='?page=wpss&tab=log&where=event&where_value=".$item['event']."'>".$item['event_description']."</a>";
		return $ret;
	}

	function column_ip($item) {
		$ret = "<a href='?page=wpss&tab=log&where=ip&where_value=".$item['ip']."'>".$item['ip']."</a>";
		return $ret;
	}

	function column_ip_country($item) {    	
		global $wpss_countries;
		$ip_country = $item['ip_country'];
		$ip = $item['ip'];
		$id = $item['id'];
		$ret = '';
		if (isset($wpss_countries[$ip_country])) {
			$c = $wpss_countries[$ip_country].' ('.$ip_country.')';
			$ret = "<a href='?page=wpss&tab=log&where=ip_country&where_value=".$ip_country."'>".$c."</a>";
			$ret .= '<br/><img style="margin-top:5px;" src="'.plugins_url().'/supersonic/flags/'.strtolower($ip_country).'.png"/>';
			$ret .= '<div class="row-actions"><strong>IP</strong>: <span class="delete">';
			$ret .= '<a title="Ban IP in CloudFlare" href="javascript:void(0);" onclick="wpss_ip_action(\''.$ip.'\',\''.$ip_country.'\',\'ban\',\''. wp_create_nonce( 'wpss_ip_nonce' ).'\','.$id.',\'log\');" class="delete">BAN</a></span>';
			$ret .= '<span> | <a title="White list IP in CloudFlare" href="javascript:void(0);" onclick="wpss_ip_action(\''.$ip.'\',\''.$ip_country.'\',\'wl\',\''. wp_create_nonce( 'wpss_ip_nonce' ).'\','.$id.',\'log\');" class="delete">WL</a></span>';
			$ret .= '<span> | <a title="Remove IP from CloudFlare lists" href="javascript:void(0);" onclick="wpss_ip_action(\''.$ip.'\',\''.$ip_country.'\',\'nul\',\''. wp_create_nonce( 'wpss_ip_nonce' ).'\','.$id.',\'log\');" class="delete">NUL</a></span>';
			$ret .= '<br/>';
			$ret .= '</div>';
			$ret .= '<div id="message-'.$id.'"></div>';
		}
		return $ret;
	}


	function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="id[]" value="%s" />',
			$item['id']
		);
	}

	function get_columns() {
		$columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'time' => __('Time', 'custom_table_example'),
            'event' => __('Event', 'wpss'),
            'event_description' => __('Event', 'wpss'),
            'info' => __('Information', 'custom_table_example'),
            'ip' => __('IP', 'custom_table_example'),
            'ip_country' => __('Country', 'custom_table_example'),
		);
		return $columns;
	}

    function get_sortable_columns() {
        $sortable_columns = array(
            'event_description' => array('event_description', false),
            'time' => array('time', true),
            'ip' => array('ip', false),
            'ip_country' => array('ip_country', false),
        );
        return $sortable_columns;
    }

    function get_bulk_actions() {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }

    function process_bulk_action() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpss_log'; // do not forget about tables prefix

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) $ids = implode(',', $ids);

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
    }

    function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpss_log'; // do not forget about tables prefix

        $per_page = 20; 

        $columns = $this->get_columns();
        $hidden = array('event');
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->process_bulk_action();
        //
        $where = '';
        if (isset($_REQUEST['where']) && isset($_REQUEST['where_value'])) {
        	$where = " where ".$_REQUEST['where']." = '".$_REQUEST['where_value']."'";
        }

        // will be used in pagination settings
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name $where");

        // prepare query params, as usual current page, order by and order direction
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'time';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';
        //
        
        $this->items = $wpdb->get_results($wpdb->prepare(
        "SELECT id, time, event, ip, ip_country, info, 
        case 
          when event = 1 then 'WP-Login: country blocked'
          when event = 2 then 'Comment: country blocked'
          when event = 3 then 'XmlRpc: country blocked'
          when event = 4 then 'IP ban from comment'
          when event = 5 then 'Bad login'
          when event = 6 then 'Bruteforce ban send'
          when event = 7 then 'IP white listed from comment'
          when event = 8 then 'IP nul from comment'
          when event = 9 then 'Purge CloudFlare cache'
          when event = 10 then 'Report comment spam'
          when event = 14 then 'IP ban from log view'
          when event = 17 then 'IP white listed from log view'
          when event = 18 then 'IP nul from log view'
          when event = 20 then 'Cron job'
          else 'Undefined'
        end as event_description
        FROM $table_name 
        $where
        ORDER BY $orderby $order 
        LIMIT %d OFFSET %d", $per_page, $paged*$per_page), ARRAY_A);
        
        $this->set_pagination_args(array(
            'total_items' => $total_items, // total items defined above
            'per_page' => $per_page, // per page constant defined at top of method
            'total_pages' => ceil($total_items / $per_page) // calculate pages count
        ));
    }
} //WPSS_Log_List_Table
