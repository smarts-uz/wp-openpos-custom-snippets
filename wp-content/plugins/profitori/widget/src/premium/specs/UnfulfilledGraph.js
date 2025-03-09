'UnfulfilledGraph'.graph({style: "bar"})
'Unfulfilled Sales Orders'.title()
'Back'.action({act: 'cancel'})
'Refresh'.action({act: "refresh"})

'order_items'.datatype()
'UnfulfilledGraph'.limitToSubset('RecentOrActive')

'niceOrderStatus'.xAxis()
'valueIncTax'.yAxis()
'UnfulfilledSalesOrders.js'.drilldownSpecname()

'UnfulfilledGraph'.filter(async orderItem => {
  
  let isActive = await orderItem.isActive()
  return isActive
})

'UnfulfilledGraph'.barColor(val => {
  if ( val === 'Pending' )
    return '#86AC41'
  if ( val === 'On-hold' )
    return '#34675C'
  if ( val === 'Processing' )
    return '#7DA3A1'
})
