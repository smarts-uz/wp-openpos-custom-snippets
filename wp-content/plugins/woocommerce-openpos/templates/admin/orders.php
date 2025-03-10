<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<?php
    $register_id = isset($_GET['register']) ? (int)$_GET['register'] : 0;
    $warehouse_id = isset($_GET['warehouse']) ? (int)$_GET['warehouse'] : 0;
    $op_nonce = wp_create_nonce( 'op_nonce' );
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo __( 'POS Orders', 'openpos' ); ?></h1>
    <form id="op-order-list"  onsubmit="return false;">
        <table id="grid-selection" class="table table-condensed table-hover table-striped op-product-grid">
            <thead>
            <tr>
                <th data-column-id="id" data-identifier="true" data-type="numeric"><?php echo __( 'ID', 'openpos' ); ?></th>
                <th data-column-id="order_number" data-sortable="false"><?php echo __( 'Order', 'openpos' ); ?></th>
                <th data-column-id="created_at" data-identifier="true" data-type="numeric"><?php echo __( 'Date', 'openpos' ); ?></th>
                <th data-column-id="source" data-sortable="false"><?php echo __( 'Source', 'openpos' ); ?></th>
                <th data-column-id="created_by" data-sortable="false"><?php echo __( 'By', 'openpos' ); ?></th>
                <th data-column-id="total" data-sortable="false"><?php echo __( 'Total', 'openpos' ); ?></th>
                <th data-column-id="status" data-sortable="false"><?php echo __( 'Status', 'openpos' ); ?></th>
            </tr>
            </thead>
        </table>
    </form>
    <br class="clear">
</div>


<script type="text/javascript">
    (function($) {
        "use strict";
       var grid = $("#grid-selection").bootgrid({
            ajax: true,
            post: function ()
            {
                /* To accumulate custom parameter with the request object */
                return {
                    action: "op_orders",
                    register: <?php echo $register_id; ?>,
                    warehouse: <?php echo $warehouse_id ; ?>,
                    op_nonce : "<?php echo $op_nonce; ?>"
                };
            },
            url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
            selection: false,
            multiSelect: true,
            identifier: true,
            formatters: {
                "link": function(column, row)
                {
                    return "<a href=\"#\">" + column.id + ": " + row.id + "</a>";
                },
                "price": function(column,row){

                    return row.formatted_price;
                }
            },
           templates: {
               header: "<div id=\"{{ctx.id}}\" class=\"{{css.header}}\"><div class=\"row\"><div class=\"col-sm-12 actionBar\"><p class=\"{{css.search}}\"></p><p class=\"{{css.actions}}\"></p></div></div></div>"
           },
           labels: {
                all: "<?php echo __( 'All', 'openpos' ); ?>",
                infos: "<?php echo __( 'Showing {{ctx.start}} to {{ctx.end}} of {{ctx.total}} entries', 'openpos' ); ?>",
                loading: "<?php echo __( 'Loading...', 'openpos' ); ?>",
                noResults: "<?php echo __( 'No results found!', 'openpos' ); ?>",
                refresh: "<?php echo __( 'Refresh', 'openpos' ); ?>",
                search: "<?php echo __( 'Search', 'openpos' ); ?>"
            }
        }).on("initialized.rs.jquery.bootgrid",function(){

        }).on("selected.rs.jquery.bootgrid", function(e, rows)
        {


           // alert("xxSelect: " + rowIds.join(","));
        }).on("deselected.rs.jquery.bootgrid", function(e, rows)
        {

        });

        $('.vna-action').click(function(){
            var selected = $("#grid-selection").bootgrid("getSelectedRows");
            var action = $(this).data('action');
            if(selected.length == 0)
            {
                alert('<?php echo __( 'Please choose row to continue.', 'openpos' ); ?>');
            }else{

                if(confirm('<?php echo __( 'Are you sure ? ', 'openpos' ); ?>'))
                {
                    $.ajax({
                        url: openpos_admin.ajax_url,
                        type: 'post',
                        dataType: 'json',
                        //data:$('form#op-product-list').serialize(),
                        data: {action: 'admin_openpos_update_transaction_grid',data:selected,op_nonce : "<?php echo $op_nonce; ?>"},
                        success:function(data){
                            alert('<?php echo __( 'Saved', 'openpos' ); ?>');
                            $("#grid-selection").bootgrid("reload");
                        }
                    });
                }


            }

        });

    })( jQuery );
</script>

<style>
    .action-row a{
        display: block;
        padding: 3px 4px;
        text-decoration: none;
        border: solid 1px #ccc;
        text-align: center;
        margin: 5px;
    }
    .op-product-grid td{
        vertical-align: middle!important;
    }
</style>