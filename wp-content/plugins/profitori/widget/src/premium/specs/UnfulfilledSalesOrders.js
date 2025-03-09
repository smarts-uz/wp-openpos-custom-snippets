'UnfulfilledSalesOrders'.list()
'Unfulfilled Sales Orders'.title()
'Back'.action({act: 'cancel'})
'Refresh'.action({act: "refresh"})
'Download to Excel'.action({act: 'excel'})

'order_items'.datatype()
'order_date'.field()
'theOrder'.field({showAsLink: true})
'billingNameAndCompany'.field({caption: "Customer"})
'productUniqueName'.field({caption: 'Product', showAsLink: true})
'_qty'.field()
'valueIncTax'.field()
'niceOrderStatus'.field({caption: "Status"})

'UnfulfilledSalesOrders'.filter(async (orderItem, list) => {
  let selStatus = list.parm()
  let status = orderItem.niceOrderStatus
  if ( ! selStatus ) 
    return (['Pending', 'Processing', 'On-hold'].indexOf(status) >= 0)
  return status === selStatus 
})

'UnfulfilledSalesOrders'.dynamicTitle(function() {
  let status = this.parm()
  if ( ! status ) 
    return 'Unfulfilled Sales Orders'.translate()
  return status + ' ' + 'Sales Orders'.translate()
})

'UnfulfilledSalesOrders'.limitToSubset('RecentOrActive')
