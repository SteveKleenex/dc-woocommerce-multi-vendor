<?php
/**
 * The template for displaying vendor orders
 *
 * Override this template by copying it to yourtheme/dc-product-vendor/vendor-dashboard/vendor-orders.php
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
?>
<div class="col-md-12">
    <div class="panel panel-default">
        <div class="panel-body">
            <form name="wcmp_vendor_dashboard_orders" method="POST" class="form-inline">
                <div class="form-group">
                    <span class="date-inp-wrap">
                        <input type="text" name="wcmp_start_date_order" class="pickdate gap1 wcmp_start_date_order form-control" placeholder="<?php _e('from', 'dc-woocommerce-multi-vendor'); ?>" value="<?php echo isset($_POST['wcmp_start_date_order']) ? $_POST['wcmp_start_date_order'] : date('01-m-Y'); ?>" />
                    </span> 
                    <!-- <span class="between">&dash;</span> -->
                </div>
                <div class="form-group">
                    <span class="date-inp-wrap">
                    <input type="text" name="wcmp_end_date_order" class="pickdate wcmp_end_date_order form-control" placeholder="<?php _e('to', 'dc-woocommerce-multi-vendor'); ?>" value="<?php echo isset($_POST['wcmp_end_date_order']) ? $_POST['wcmp_end_date_order'] : date('t-m-Y'); ?>" />
                    </span>
                </div>
                <button class="wcmp_black_btn btn btn-default" type="submit" name="wcmp_order_submit"><?php _e('Show', 'dc-woocommerce-multi-vendor'); ?></button>
            </form>
            <form method="post" name="wcmp_vendor_dashboard_completed_stat_export">
                <table class="table table-striped table-bordered" id="wcmp-vendor-orders">
                    <thead>
                        <tr>
                            <th class="text-center"><input type="checkbox" class="select_all_all" onchange="toggleAllCheckBox(this, 'wcmp-vendor-orders');" /></th>
                            <th><?php _e('Order ID', 'dc-woocommerce-multi-vendor'); ?></th>
                            <th><?php _e('Date', 'dc-woocommerce-multi-vendor'); ?></th>
                            <th><?php _e('Earnings', 'dc-woocommerce-multi-vendor'); ?></th>
                            <th><?php _e('Status', 'dc-woocommerce-multi-vendor'); ?></th>
                            <th><?php _e('Action', 'dc-woocommerce-multi-vendor'); ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            <?php if(apply_filters('can_wcmp_vendor_export_orders_csv', true, get_current_vendor_id())) : ?>
            <div class="wcmp-action-container">
                <input class="btn btn-default" type="submit" name="wcmp_download_vendor_order_csv" value="<?php _e('Download CSV', 'dc-woocommerce-multi-vendor') ?>" />
            </div>
            <?php endif; ?>
            <?php if (isset($_POST['wcmp_start_date_order'])) : ?>
                <input type="hidden" name="wcmp_start_date_order" value="<?php echo $_POST['wcmp_start_date_order']; ?>" />
            <?php endif; ?>
            <?php if (isset($_POST['wcmp_end_date_order'])) : ?>
                <input type="hidden" name="wcmp_end_date_order" value="<?php echo $_POST['wcmp_end_date_order']; ?>" />
            <?php endif; ?>    
            </form>
        </div>
    </div>

    <!-- Modal -->
    <div id="marke-as-ship-modal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <form method="post">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title"><?php _e('Shipment Tracking Details', 'dc-woocommerce-multi-vendor'); ?></h4>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label for="tracking_url"><?php _e('Enter Tracking Url', 'dc-woocommerce-multi-vendor'); ?> *</label>
                            <input type="url" class="form-control" id="email" name="tracking_url" required="">
                        </div>
                        <div class="form-group">
                            <label for="tracking_id"><?php _e('Enter Tracking ID', 'dc-woocommerce-multi-vendor'); ?> *</label>
                            <input type="text" class="form-control" id="pwd" name="tracking_id" required="">
                        </div>
                    </div>
                    <input type="hidden" name="order_id" id="wcmp-marke-ship-order-id" />
                    <?php if (isset($_POST['wcmp_start_date_order'])) : ?>
                        <input type="hidden" name="wcmp_start_date_order" value="<?php echo $_POST['wcmp_start_date_order']; ?>" />
                    <?php endif; ?>
                    <?php if (isset($_POST['wcmp_end_date_order'])) : ?>
                        <input type="hidden" name="wcmp_end_date_order" value="<?php echo $_POST['wcmp_end_date_order']; ?>" />
                    <?php endif; ?>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" name="wcmp-submit-mark-as-ship"><?php _e('Submit', 'dc-woocommerce-multi-vendor'); ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


<script type="text/javascript">
    jQuery(document).ready(function ($) {
        var orders_table;
        var statuses = [];
        <?php 
        $filter_by_status = apply_filters('wcmp_vendor_dashboard_order_filter_status_arr',array(
            'all' => __('All', 'dc-woocommerce-multi-vendor'),
            'processing' => __('Processing', 'dc-woocommerce-multi-vendor'),
            'completed' => __('Completed', 'dc-woocommerce-multi-vendor')
        ));
        foreach ($filter_by_status as $key => $label) { ?>
            obj = {};
            obj['key'] = "<?php echo $key; ?>";
            obj['label'] = "<?php echo $label; ?>";
            statuses.push(obj);
        <?php } ?>
        orders_table = $('#wcmp-vendor-orders').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            ordering: false,
            drawCallback: function (settings) {
                $( "#filter_by_order_status" ).detach();
                var order_status_sel = $('<select id="filter_by_order_status" class="wcmp-filter-dtdd wcmp_filter_order_status form-control">').appendTo("#wcmp-vendor-orders_length");
                $(statuses).each(function () {
                    order_status_sel.append($("<option>").attr('value', this.key).text(this.label));
                });
                if(settings.oAjaxData.order_status){
                    order_status_sel.val(settings.oAjaxData.order_status);
                }
                $('table.dataTable tr [type="checkbox"]').each(function(){
                    if($(this).parent().is('span.checkbox-holder')) return;
                    $(this).wrap('<span class="checkbox-holder"></span>').after('<i class="wcmp-font ico-uncheckbox-icon"></i>');
                })
            },
            language: {
                emptyTable: "<?php echo __('No orders found!', 'dc-woocommerce-multi-vendor'); ?>",
                processing: "<?php echo __('Processing...', 'dc-woocommerce-multi-vendor'); ?>",
                info: "<?php echo __('Showing _START_ to _END_ of _TOTAL_ orders', 'dc-woocommerce-multi-vendor'); ?>",
                infoEmpty: "<?php echo __('Showing 0 to 0 of 0 orders', 'dc-woocommerce-multi-vendor'); ?>",
                lengthMenu: "<?php echo __('Show orders _MENU_', 'dc-woocommerce-multi-vendor'); ?>",
                zeroRecords: "<?php echo __('No matching orders found', 'dc-woocommerce-multi-vendor'); ?>",
                paginate: {
                    next: "<?php echo __('Next', 'dc-woocommerce-multi-vendor'); ?>",
                    previous: "<?php echo __('Previous', 'dc-woocommerce-multi-vendor'); ?>"
                }
            },
            ajax: {
                url: woocommerce_params.ajax_url + '?action=wcmp_datatable_get_vendor_orders',
                type: "post",
                data: function (data) {
                    data.start_date = vendor_orders_args.start_date;
                    data.end_date = vendor_orders_args.end_date;
                    data.order_status = $('#filter_by_order_status').val();
                },
                error: function(xhr, status, error) {
                    $("#wcmp-vendor-orders tbody").append('<tr class="odd"><td valign="top" colspan="6" class="dataTables_empty" style="text-align:center;">'+error+' - <a href="javascript:window.location.reload();"><?php _e('Reload', 'dc-woocommerce-multi-vendor'); ?></a></td></tr>');
                    $("#wcmp-vendor-orders_processing").css("display","none");
                }
            },
            columns: [
                {data: 'select_order', className: 'text-center'},
                {data: 'order_id'},
                {data: 'order_date'},
                {data: 'vendor_earning'},
                {data: 'order_status'},
                {data: 'action'}
            ]
        });
        $(document).on('change', '#filter_by_order_status', function () {
            orders_table.ajax.reload();
        });
    });

    function wcmpMarkeAsShip(self, order_id) {
        jQuery('#wcmp-marke-ship-order-id').val(order_id);
        jQuery('#marke-as-ship-modal').modal('show');
    }
</script>