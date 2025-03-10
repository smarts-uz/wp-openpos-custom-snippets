<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<?php
    global $op_warehouse;
    $id = isset($_GET['id']) ? $_GET['id'] : 0;
    $warehouse = $op_warehouse->get($id);
    $warehouse_name = isset($warehouse['name']) ? $warehouse['name'] : '';
    $op_nonce = wp_create_nonce( 'op_nonce' );
?>
<div class="wrap">
    <div id="wrap-loading">
        <div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>
    </div>
    <h1><?php echo implode(__(' of ','openpos'),array( __( 'Inventory', 'openpos' ),$warehouse_name ) ); ?></h1>
    <form id="op-product-list" onsubmit="return false;">
        <input type="hidden" name="action" value="admin_openpos_update_inventory_grid">
        <input type="hidden" name="warehouse_id" value="<?php echo $id; ?>">
        <table id="grid-selection" class="table table-condensed table-hover table-striped op-product-grid">
            <thead>
            <tr>
                <th data-column-id="id" data-identifier="true" data-type="numeric"><?php echo __( 'ID', 'openpos' ); ?></th>
                <th data-column-id="barcode" data-identifier="true" data-type="numeric"><?php echo __( 'Barcode', 'openpos' ); ?></th>
                <th data-column-id="product_thumb" data-sortable="false"><?php echo __( 'Thumbnail', 'openpos' ); ?></th>
                <th data-column-id="post_title" data-sortable="false"><?php echo __( 'Product Name', 'openpos' ); ?></th>
                <th data-column-id="price" data-sortable="false"><?php echo __( 'Price', 'openpos' ); ?></th>
                <th data-column-id="qty" data-type="numeric" data-sortable="false"><?php echo __( 'Qty', 'openpos' ); ?></th>
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
                    action: "op_inventory",
                    warehouse_id : <?php echo $id; ?>,
                    op_nonce : "<?php echo $op_nonce; ?>"
                };
            },
            url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
            selection: true,
            multiSelect: true,
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
                header: "<div id=\"{{ctx.id}}\" class=\"{{css.header}}\"><div class=\"row\"><div class=\"col-sm-6 actionBar\" ><a type=\"button\" data-warehouse_id=\"<?php echo $id;?>\" href=\"<?php echo admin_url('admin-ajax.php?action=op_export_inventory&warehouse_id='.$id);?>\" class=\"btn pull-left export-inventory btn-default\" data-action=\"export\"><?php echo __('Export','openpos');?></a>&nbsp;<a type=\"button\" href=\"<?php echo admin_url('admin.php?page=op-warehouses&op-action=adjust_stock&warehouse_id=' . esc_attr($id));?>\" class=\"btn pull-left btn-default\" data-action=\"export\"><?php echo __('Adjust Stock','openpos');?></a></div><div class=\"col-sm-6 actionBar\"><p class=\"{{css.search}}\"></p><p class=\"{{css.actions}}\"></p><button type=\"button\" class=\"btn vna-action btn-default\" data-action=\"save\"><span class=\" icon glyphicon glyphicon-floppy-save\"></span></button></div></div></div>"
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
            var rowIds = [];
            for (var i = 0; i < rows.length; i++)
            {
                rowIds.push(rows[i].id);

                if($('input[name="qty['+rows[i].id+']"]'))
                {
                    $('input[name="qty['+rows[i].id+']"]').prop('disabled',false);
                }
            }

            // alert("xxSelect: " + rowIds.join(","));
        }).on("deselected.rs.jquery.bootgrid", function(e, rows)
        {
            var rowIds = [];
            for (var i = 0; i < rows.length; i++)
            {
                rowIds.push(rows[i].id);

                if($('input[name="qty['+rows[i].id+']"]'))
                {
                    $('input[name="qty['+rows[i].id+']"]').prop('disabled',true);
                }
            }
            //alert("Deselect: " + rowIds.join(","));
        });
        $('.vna-action').click(function(){
            var selected = $("#grid-selection").find('input[type="checkbox"]:checked');
            var action = $(this).data('action');
            if(selected.length == 0)
            {
                alert('<?php echo __( 'Please choose row to continue.', 'openpos' ); ?>');
            }else{
                $.ajax({
                    url: openpos_admin.ajax_url,
                    type: 'post',
                    dataType: 'json',
                    //data:$('form#op-product-list').serialize(),
                    data: {action: 'admin_openpos_update_inventory_grid',warehouse_id:<?php echo $id; ?>,data:$('form#op-product-list').serialize(),op_nonce : "<?php echo $op_nonce; ?>"},
                    success:function(data){
                        alert('<?php echo __( 'Saved', 'openpos' ); ?>');
                    }
                })

            }

        });
        function export_ajax(warehouse_id,page = 1){
            $.ajax({
                    url: openpos_admin.ajax_url,
                    type: 'post',
                    dataType: 'json',
                    data: {action: 'op_export_inventory',warehouse_id:warehouse_id,page:page,op_nonce : "<?php echo $op_nonce; ?>"},
                    beforeSend:function(){
                        $('body').addClass('op_loading');
                    },
                    success:function(data){
                        if(data['total_page'] > page )
                        {
                            $('body').find('#wrap-loading').html('<span class="text-loading">'+page+' / '+data['total_page']+'</span');
                            export_ajax(warehouse_id, page+ 1)
                        }else{
                            $('body').removeClass('op_loading');
                            document.location = data.export_file;
                            /*
                            var a = document.createElement('a');
                            var url = data.file_url;
                            a.href = url;
                            a.download = data.file_name;
                            document.body.append(a);
                            a.click();
                            a.remove();
                            window.URL.revokeObjectURL(url);
                            */
                        }
                        
                    }
                });
        }
        $(document).on('click','.export-inventory',function(){
            let warehouse_id = $(this).data('warehouse_id');
            export_ajax(warehouse_id);
            return false;
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