'ViewSalesOrder'.maint({readOnly: true, panelStyle: "titled"})
'View Sales Order'.title()
'Back'.action({act: 'cancel'})
'Edit Order'.action({spec: 'SOManageMaint.js'})
'Commission'.action({spec: 'CommissionMaint.js'})
'Download Invoice PDF'.action()
'Download Invoice CSV'.action()
'Attachments'.action({act: 'attachments'})
'orders'.datatype()

'Sales Order'.panel()
'order_id'.field({caption: "Order Number"})
"order_date".field()
"billingName".field({caption: "Customer", showAsLink: true})
"_date_completed".field()
'niceStatus'.field()

'Billing'.panel()
'_payment_method_title'.field()
'customerReference'.field()
'billingNameAndCompany'.field({caption: "Bill To"})
'billingAddress'.field({caption: "Address"})
'billingEmailAndPhone'.field({caption: "Contact"})

'Amounts'.panel()
'productsTotalExTax'.field({caption: "Products"})
'_order_shipping'.field({caption: "Shipping"})
'fees_total'.field({caption: "Fees"})
'totalTax'.field({caption: "Tax"})
'orderTotalWithCurrency'.field({caption: "Total"})

'Shipping'.panel()
'shippingNameAndCompany'.field({caption: "Ship To"})
'shippingAddress'.field({caption: "Address"})
'shippingEmailAndPhone'.field({caption: "Contact"})

'Lines'.manifest({useContainerSubset: true})
'order_items'.datatype()
//'productUniqueName'.field({caption: "Product", showAsLink: true})
'_name'.field({caption: 'Description', showAsLink: true})
'_qty'.field()
'unitPriceExTax'.field({caption: "Unit Price"})
'_line_total'.field({caption: "Amount"})
'_line_tax'.field({caption: "Tax"})
'valueIncTax'.field({caption: "Amount Inc Tax"})

'unitPriceExTax'.visibleWhen((maint, oi) => {
  if ( ! oi ) return true
  return oi.order_item_type === 'line_item'
})

'_qty'.visibleWhen((maint, oi) => {
  if ( ! oi ) return true
  return oi.order_item_type === 'line_item'
})

'Download Invoice CSV'.act(async (maint, order) => {
  maint.downloadCSV({spec: "SalesInvoiceCsv.js", docName: "Invoice " + order.id + ".CSV"})
})

'_name'.destination(async order_item => {
  return await order_item.toProduct() 
})

'billingName'.destination(async order => {
  return await order.toCustomer()
})

'Download Invoice PDF'.act(async (maint, order) => {
  maint.downloadPDF({spec: "SalesInvoicePdf.js", docName: "Invoice " + order.id + ".PDF"})
})

'ViewSalesOrder'.makeDestinationFor('orders')
