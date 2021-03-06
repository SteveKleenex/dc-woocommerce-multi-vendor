<?php

/**
 * WCMp Cron Job Class
 *
 * @version		2.2.0
 * @package		WCMp
 * @author 		WC Marketplace
 */
class WCMp_Cron_Job {

    public function __construct() {
        add_action('masspay_cron_start', array(&$this, 'do_mass_payment'));
        // vendor weekly order stats reports
        add_action('vendor_weekly_order_stats', array(&$this, 'vendor_weekly_order_stats_report'));
        // vendor monthly order stats reports
        add_action('vendor_monthly_order_stats', array(&$this, 'vendor_monthly_order_stats_report'));
    }

    /**
     * Calculate the amount and selete payment method.
     *
     *
     */
    function do_mass_payment() {
        global $WCMp;
        $payment_admin_settings = get_option('wcmp_payment_settings_name');
        if (!isset($payment_admin_settings['wcmp_disbursal_mode_admin'])) {
            return;
        }
        $commission_to_pay = array();
        $commissions = $this->get_query_commission();
        if ($commissions && is_array($commissions)) {
            foreach ($commissions as $commission) {
                $commission_id = $commission->ID;
                $vendor_term_id = get_post_meta($commission_id, '_commission_vendor', true);
                $commission_to_pay[$vendor_term_id][] = $commission_id;
            }
        }
        foreach ($commission_to_pay as $vendor_term_id => $commissions) {
            $vendor = get_wcmp_vendor_by_term($vendor_term_id);
            if ($vendor) {
                $payment_method = get_user_meta($vendor->id, '_vendor_payment_mode', true);
                if ($payment_method && $payment_method != 'direct_bank') {
                    if (array_key_exists($payment_method, $WCMp->payment_gateway->payment_gateways)) {
                        $WCMp->payment_gateway->payment_gateways[$payment_method]->process_payment($vendor, $commissions);
                    }
                }
            }
        }
    }

    /**
     * Get Commissions
     *
     * @return object $commissions
     */
    public function get_query_commission() {
        $args = array(
            'post_type' => 'dc_commission',
            'post_status' => array('publish', 'private'),
            'meta_key' => '_paid_status',
            'meta_value' => 'unpaid',
            'posts_per_page' => 5
        );
        $commissions = get_posts($args);
        return $commissions;
    }
    
    /**
     * Weekly order stats report
     *
     * 
     */
    public function vendor_weekly_order_stats_report() {
        global $WCMp;
        $vendors = get_wcmp_vendors();
        if($vendors){
            foreach ($vendors as $key => $vendor_obj) {
                if($vendor_obj->user_data->user_email){
                    $order_data = array();
                    $vendor = get_wcmp_vendor($vendor_obj->id);
                    $email = WC()->mailer()->emails['WC_Email_Vendor_Orders_Stats_Report'];
                    $vendor_weekly_stats = $vendor->get_vendor_orders_reports_of('vendor_stats', array('vendor_id' => $vendor->id));
                    $transaction_details = $WCMp->transaction->get_transactions($vendor->term_id, date('Y-m-d', strtotime('-7 days')), date('Y-m-d'));
                    if(is_array($vendor_weekly_stats)){
                        $vendor_weekly_stats['total_transaction'] = array_sum(wp_list_pluck($transaction_details, 'total_amount'));
                    }
                    $report_data = array(
                        'period' => 'weekly',
                        'start_date' => date('Y-m-d', strtotime('-7 days')),
                        'end_date' => @date('Y-m-d'),
                        'stats' => $vendor_weekly_stats,
                    );
                    $attachments = array();
                    $vendor_weekly_orders = $vendor->get_vendor_orders_reports_of('', array('vendor_id' => $vendor->id));
                    if($vendor_weekly_orders && count($vendor_weekly_orders) > 0){
                        foreach ($vendor_weekly_orders as $key => $data) {
                            if($data->commission_id != 0 && $data->commission_id != ''){ 
                                $order_data[$data->commission_id] = $data->order_id;
                            }
                        }
                        if (count($order_data) > 0) {
                            $report_data['order_data'] = $order_data;
                            $args = array(
                                'filename' => 'OrderReports-'.$report_data['start_date'].'-To-'.$report_data['end_date']. '.csv',
                                'action' => 'temp',
                            );
                            $report_csv = $WCMp->vendor_dashboard->generate_csv($order_data, $vendor, $args);
                            if($report_csv) $attachments[] = $report_csv;
                            if($email->trigger($vendor, $report_data, $attachments)){
                                $email->find[ ]      = $vendor->page_title;
                                $email->replace[ ]   = '{STORE_NAME}';
                                if (file_exists($report_csv)) {
                                    @unlink($report_csv);
                                }
                            }else{
                                if (file_exists($report_csv)) {
                                    @unlink($report_csv);
                                }
                            }
                        }else{
                            $report_data['order_data'] = $order_data;
                            if($email->trigger($vendor, $report_data, $attachments)){
                                $email->find[ ]      = $vendor->page_title;
                                $email->replace[ ]   = '{STORE_NAME}';
                            }
                        }
                    }else{
                        $report_data['order_data'] = $order_data;
                        if($email->trigger($vendor, $report_data, $attachments)){
                            $email->find[ ]      = $vendor->page_title;
                            $email->replace[ ]   = '{STORE_NAME}';
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Monthly order stats report
     *
     * 
     */
    public function vendor_monthly_order_stats_report() {
        global $WCMp;
        $vendors = get_wcmp_vendors();
        if($vendors){
            foreach ($vendors as $key => $vendor_obj) {
                if($vendor_obj->user_data->user_email){
                    $order_data = array();
                    $vendor = get_wcmp_vendor($vendor_obj->id);
                    $email = WC()->mailer()->emails['WC_Email_Vendor_Orders_Stats_Report'];
                    $vendor_monthly_stats = $vendor->get_vendor_orders_reports_of('vendor_stats', array('vendor_id' => $vendor->id, 'start_date' => date('Y-m-d H:i:s', strtotime('-30 days'))));
                    $transaction_details = $WCMp->transaction->get_transactions($vendor->term_id, date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
                    if(is_array($vendor_monthly_stats)){
                        $vendor_monthly_stats['total_transaction'] = array_sum(wp_list_pluck($transaction_details, 'total_amount'));
                    }
                    $report_data = array(
                        'period' => 'monthly',
                        'start_date' => date('Y-m-d', strtotime('-30 days')),
                        'end_date' => @date('Y-m-d'),
                        'stats' => $vendor_monthly_stats,
                    );
                    $attachments = array();
                    $vendor_monthly_orders = $vendor->get_vendor_orders_reports_of('', array('vendor_id' => $vendor->id, 'start_date' => date('Y-m-d H:i:s', strtotime('-30 days'))));
                    if($vendor_monthly_orders && count($vendor_monthly_orders) > 0){
                        foreach ($vendor_monthly_orders as $key => $data) {
                            if($data->commission_id != 0 && $data->commission_id != ''){ 
                                $order_data[$data->commission_id] = $data->order_id;
                            }
                        }
                        if (count($order_data) > 0) {
                            $report_data['order_data'] = $order_data;
                            $args = array(
                                'filename' => 'OrderReports-'.$report_data['start_date'].'-To-'.$report_data['end_date']. '.csv',
                                'action' => 'temp',
                            );
                            $report_csv = $WCMp->vendor_dashboard->generate_csv($order_data, $vendor, $args);
                            if($report_csv) $attachments[] = $report_csv;
                            if($email->trigger($vendor, $report_data, $attachments)){
                                $email->find[ ]      = $vendor->page_title;
                                $email->replace[ ]   = '{STORE_NAME}';
                                if (file_exists($report_csv)) {
                                    @unlink($report_csv);
                                }
                            }else{
                                if (file_exists($report_csv)) {
                                    @unlink($report_csv);
                                }
                            }
                        }else{
                            $report_data['order_data'] = $order_data;
                            if($email->trigger($vendor, $report_data, $attachments)){
                                $email->find[ ]      = $vendor->page_title;
                                $email->replace[ ]   = '{STORE_NAME}';
                            }
                        }
                    }else{
                        $report_data['order_data'] = $order_data;
                        if($email->trigger($vendor, $report_data, $attachments)){
                            $email->find[ ]      = $vendor->page_title;
                            $email->replace[ ]   = '{STORE_NAME}';
                        }
                    }
                }
            }
        }
    }

}
