'SalesShipmentList'.list()
'Sales by Shipment'.title()
'SOShipmentLine'.datatype()
'Back'.action({act: 'cancel'})
'Select All'.action()
'Select None'.action()
'Download to Excel'.action({act: 'excel'})
'Refresh'.action({act: "refresh"})

'include'.field({ephemeral: true, tickbox: true, allowInput: true})
'shipmentNumber'.field({showAsLink: true})
'billingCompany'.field()
'shipmentDate'.field()
'order'.field({showAsLink: true})
'invoiced'.field()
'product'.field({showAsLink: true})
'sku'.field({caption: 'SKU'})
'quantityShipped'.field()

'SalesShipmentList'.shouldIncludeCastInDownload(shipmentLine => {
  return shipmentLine.include === 'Yes'
})

'SalesShipmentList'.filter(async shipmentLine => {
  if ( ! shipmentLine.order ) return false
  let order = await 'orders.RecentOrActive'.bringSingle({id: shipmentLine.order.id})
  return order ? true : false
})

'Select All'.act(async (list, cast) => {
  list.situation()._selectedAll = true
  let lines = list.casts()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    line.include = 'Yes'
  }
  let mold = global.foreman.doNameToMold('SOShipmentLine')
  mold.version++
})

'Select All'.availableWhen((cast, list) => {
  return (! list.situation()._selectedAll)
})

'Select None'.act(async (list, cast) => {
  list.situation()._selectedAll = false
  let lines = list.casts()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    line.include = 'No'
  }
  let mold = global.foreman.doNameToMold('SOShipmentLine')
  mold.version++
})

'Select None'.availableWhen((cast, list) => {
  return list.situation()._selectedAll
})


