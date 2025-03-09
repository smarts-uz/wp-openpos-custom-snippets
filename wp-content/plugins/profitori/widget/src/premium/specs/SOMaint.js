'SOMaint'.maint({panelStyle: "titled", icon: 'Truck'})
'Sales Order Fulfillment'.title()
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Drop Ship'.action()
'Full Edit'.action({spec: 'SOManageMaint.js'})
'Transport'.action()

'SO'.datatype()

'Order Summary'.panel()
'order'.field({readOnly: true})
'orderDate'.field({readOnly: true})
'customerReference'.field({readOnly: true})
'wcNiceStatus'.field({caption: 'WC Order Status'})
'shippingNameAndCompany'.field({caption: "Ship To", readOnly: true})
'shippingAddress'.field({caption: "Address", readOnly: true})
'shippingEmailAndPhone'.field({caption: "Contact", readOnly: true})

'Fulfillment Details'.panel()
'latestShipmentNumber'.field({caption: 'Shipment Number', readOnly: true})
'trackingNumbers'.field({readOnly: true})
'shipFromLocation'.field()
'packable'.field({readOnly: true, caption: 'Packable From This Location'})
'shipmentMethod'.field()
'fulfillStage'.field()
'priority'.field()
'notes'.field()

'Lines'.manifest()
'SOLine'.datatype()
'sequence'.field()
'descriptionAndSKU'.field({caption: 'Product', showAsLink: true})
'shipFromLocation'.field({showAsLink: true})
'packable'.field()
'fulfillStage'.field()
'priority'.field()
'quantityOrdered'.field()
'quantityRemainingToShip'.field()
'quantityPickable'.field()
'quantityToPack'.field()
'quantityShipped'.field({readOnly: false})
'quantityShippedIncremental'.field({readOnly: false})
'Edit'.action({place: 'row', act: 'edit'})
'SOLineMaint.js'.maintSpecname()

'Transport'.act(async (maint, so) => {
  let shipment = await so.toLastShipment()
  if ( ! shipment ) {
    maint.showMessage('There are no quantities to ship')
    return
  }
  await maint.segue('add', 'TransportMaint.js', shipment)
})

'trackingNumbers'.visibleWhen((maint, so) => {
  return so.trackingNumbers ? true : false
})

'Drop Ship'.act(async maint => {
  await maint.segue('add', 'POMaint.js')
})

'quantityShippedIncremental'.columnVisibleWhen((list, line) => {
  return global.confVal('enterIncrementalShipmentQuantity') === 'Yes'
})

'quantityShipped'.readOnlyWhen((maint, line) => {
  return global.confVal('enterIncrementalShipmentQuantity') === 'Yes'
})

'SOMaint'.afterInitialising(async so => {

  await so.refreshLatestShipmentNumber()
})

'wcNiceStatus'.dynamicOptions(async maint => {
  let res = ['Pending', 'On-hold', 'Processing', 'Completed']
  let orders = await 'orders.RecentOrActive'.bring()
  for ( var i = 0; i < orders.length; i++ ) {
    let order = orders[i]
    let status = order.niceStatus
    if ( res.indexOf(status) >= 0 ) continue
    res.push(status)
  }
  return res
})

'Lines'.defaultSort({field: "sequence"})

'Lines'.filter(async (soLine, list) => {
  if ( ! soLine.parentSOLine ) {
    let lineType = soLine.lineType
    if ( lineType && (lineType !== 'Product') )
      return false
    return true
  }
  let parentLine = await soLine.toTopParentSOLine(); if ( ! parentLine ) return false
  return parentLine.quantityToMake !== 0
})
