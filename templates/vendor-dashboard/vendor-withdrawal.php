<?php
/**
 * The template for displaying vendor orders
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/vendor-dashboard/vendor-withdrawal.php
 *
 * @author 		WC Marketplace
 * @package 	WCMp/Templates
 * @version   2.2.0
 */
if (!defined('ABSPATH')) {
// Exit if accessed directly
exit;
}
global $woocommerce, $WCMp;
$get_vendor_thresold = 0;
if (isset($WCMp->vendor_caps->payment_cap['commission_threshold']) && $WCMp->vendor_caps->payment_cap['commission_threshold']) {
$get_vendor_thresold = $WCMp->vendor_caps->payment_cap['commission_threshold'];
}
?>
<?php if($get_vendor_thresold) : ?>
<div class="col-md-12">
    <blockquote>
        <span><?php _e('Your Threshold value for withdrawals is : ', 'dc-woocommerce-multi-vendor'); ?><?php echo wc_price($get_vendor_thresold); ?></span>
    </blockquote>
</div>
<?php endif; ?>
<div class="col-md-12">
    <div class="panel panel-default">
        <h3 class="panel-heading"><?php _e('Completed Orders', 'dc-woocommerce-multi-vendor'); ?></h3>
        <div class="panel-body">
            <form method="post" name="get_paid_form">
                <table id="vendor_withdrawal" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center"><input class="select_all_withdrawal" type="checkbox" onchange="toggleAllCheckBox(this, 'vendor_withdrawal');" /></th>
                            <th><?php _e('Order ID', 'dc-woocommerce-multi-vendor') ?></th>
                            <th><?php _e('Commission Amount', 'dc-woocommerce-multi-vendor') ?></th>
                            <th><?php _e('Shipping Amount', 'dc-woocommerce-multi-vendor') ?></th>
                            <th><?php _e('Tax Amount', 'dc-woocommerce-multi-vendor') ?></th>
                            <th><?php _e('Total', 'dc-woocommerce-multi-vendor') ?></th>
                        </tr>
                    </thead>
                    <tbody>  
                    </tbody>
                </table>
                <div class="wcmp_table_loader">
                    <input type="hidden" id="total_orders_count" value = "<?php echo count($vendor_unpaid_orders); ?>" />
                    <?php if (count($vendor_unpaid_orders) > 0) { 
                        if (isset($WCMp->vendor_caps->payment_cap['wcmp_disbursal_mode_vendor']) && $WCMp->vendor_caps->payment_cap['wcmp_disbursal_mode_vendor'] == 'Enable') {
                            $total_vendor_due = $vendor->wcmp_vendor_get_total_amount_due();
                            if ($total_vendor_due > $get_vendor_thresold) { ?>
                            <div class="wcmp-action-container">
                                <button name="vendor_get_paid" type="submit" class="btn btn-default"><?php _e('Request Withdrawals', 'dc-woocommerce-multi-vendor'); ?></button>
                            </div>
                    <?php
                            }
                        }
                    }
                    ?>
                    <div class="clear"></div>
                </div>
            </form>
            <?php $vendor_payment_mode = get_user_meta($vendor->id, '_vendor_payment_mode', true);
            if ($vendor_payment_mode == 'paypal_masspay' && wp_next_scheduled('masspay_cron_start')) { ?>
            <div class="wcmp_admin_massege">
                <div class="wcmp_mixed_msg"><?php _e('Your next scheduled payment date is on:', 'dc-woocommerce-multi-vendor'); ?>	<span><?php echo date('d/m/Y g:i:s A', wp_next_scheduled('masspay_cron_start')); ?></span> </div>
            </div>
        <?php } ?> 
        </div>
    </div>
</div>
<script>
jQuery(document).ready(function($) {
    var vendor_withdrawal;
    vendor_withdrawal = $('#vendor_withdrawal').DataTable({
        ordering  : <?php echo isset($table_init['ordering']) ? $table_init['ordering'] : 'false'; ?>,
        searching  : <?php echo isset($table_init['searching']) ? $table_init['searching'] : 'false'; ?>,
        processing: true,
        serverSide: true,
        language: {
            "emptyTable": "<?php echo isset($table_init['emptyTable']) ? $table_init['emptyTable'] : __('No orders found!','dc-woocommerce-multi-vendor'); ?>",
            "processing": "<?php echo isset($table_init['processing']) ? $table_init['processing'] : __('Processing...', 'dc-woocommerce-multi-vendor'); ?>",
            "info": "<?php echo isset($table_init['info']) ? $table_init['info'] : __('Showing _START_ to _END_ of _TOTAL_ orders','dc-woocommerce-multi-vendor'); ?>",
            "infoEmpty": "<?php echo isset($table_init['infoEmpty']) ? $table_init['infoEmpty'] : __('Showing 0 to 0 of 0 orders','dc-woocommerce-multi-vendor'); ?>",
            "lengthMenu": "<?php echo isset($table_init['lengthMenu']) ? $table_init['lengthMenu'] : __('Show _MENU_ orders','dc-woocommerce-multi-vendor'); ?>",
            "zeroRecords": "<?php echo isset($table_init['zeroRecords']) ? $table_init['zeroRecords'] : __('No matching orders found','dc-woocommerce-multi-vendor'); ?>",
            "search": "<?php echo isset($table_init['search']) ? $table_init['search'] : __('Search:','dc-woocommerce-multi-vendor'); ?>",
            "paginate": {
                "next":  "<?php echo isset($table_init['next']) ? $table_init['next'] : __('Next','dc-woocommerce-multi-vendor'); ?>",
                "previous":  "<?php echo isset($table_init['previous']) ? $table_init['previous'] : __('Previous','dc-woocommerce-multi-vendor'); ?>"
            }
        },
        drawCallback: function () {
            $('table.dataTable tr [type="checkbox"]').each(function(){
                if($(this).parent().is('span.checkbox-holder')) return;
                $(this).wrap('<span class="checkbox-holder"></span>').after('<i class="wcmp-font ico-uncheckbox-icon"></i>');
            })
        },
        ajax:{
            url : woocommerce_params.ajax_url+'?action=wcmp_vendor_unpaid_order_vendor_withdrawal_list', 
            type: "post",
            error: function(xhr, status, error) {
                $("#vendor_withdrawal tbody").append('<tr class="odd"><td valign="top" colspan="6" class="dataTables_empty" style="text-align:center;">'+error+' - <a href="javascript:window.location.reload();"><?php _e('Reload', 'dc-woocommerce-multi-vendor'); ?></a></td></tr>');
                $("#vendor_withdrawal_processing").css("display","none");
            }
        },
        columns: [
            { data: "select_withdrawal", className: "text-center", orderable: false },
            { data: "order_id", orderable: false },
            { data: "commission_amount", orderable: false },
            { data: "shipping_amount", orderable: false },
            { data: "tax_amount", orderable: false },
            { data: "total", orderable: false }
        ]
    });
});
</script>