'ViewSOShipments'.list({icon: 'Pallet'})
'View Shipments'.title()
'SOShipment'.datatype()
'Back'.action({act: 'cancel'})
'Shipment Notes'.action()
'Select All'.action()
'Select None'.action()
'Download Shipment Notes PDF'.action()
'Refresh'.action({act: "refresh"})
'Recalls'.action({spec: 'Recalls.js'})
'Transport Settings'.action({spec: 'TransportConfigMaint.js'})
'include'.field({ephemeral: true, tickbox: true, allowInput: true})
'shipmentNumber'.field()
'shipmentDate'.field()
'shippingNameAndCompany'.field()
'billingNameAndCompany'.field()
'billingEmailAndPhone'.field()
'localPickup'.field()
'pickedUp'.field()
'invoiced'.field()
'order'.field({showAsLink: true})
'attachmentIcon'.field({icon: true, caption: ''})
'Edit'.action({place: 'row', act: 'edit'});
'Merge With Previous'.action({place: 'row'});
'SOShipmentMaint.js'.maintSpecname()

'include'.visibleWhen((list) => {
  return list.situation()._doingShipmentNotes
})

'Shipment Notes'.availableWhen((cast, list) => {
  return ! list.situation()._doingShipmentNotes
})

'Shipment Notes'.act(async (list, cast) => {
  list.situation()._selectedAll = true
  let shipments = list.casts()
  for ( var i = 0; i < shipments.length; i++ ) {
    let shipment = shipments[i]
    shipment.include = 'Yes'
  }
  let mold = global.foreman.doNameToMold('SOShipment')
  mold.version++
  list.situation()._doingShipmentNotes = true
})

'Back'.act(async (list, cast) => {
  if ( list.situation()._doingShipmentNotes ) {
    list.situation()._doingShipmentNotes = false
    return
  }
  list.back()
})

'Select All'.act(async (list, cast) => {
  list.situation()._selectedAll = true
  let shipments = list.casts()
  for ( var i = 0; i < shipments.length; i++ ) {
    let shipment = shipments[i]
    shipment.include = 'Yes'
  }
  let mold = global.foreman.doNameToMold('SOShipment')
  mold.version++
})

'Select All'.availableWhen((cast, list) => {
  return list.situation()._doingShipmentNotes && (! list.situation()._selectedAll)
})

'Select None'.act(async (list, cast) => {
  list.situation()._selectedAll = false
  let shipments = list.casts()
  for ( var i = 0; i < shipments.length; i++ ) {
    let shipment = shipments[i]
    shipment.include = 'No'
  }
  let mold = global.foreman.doNameToMold('SOShipment')
  mold.version++
})

'Select None'.availableWhen((cast, list) => {
  return list.situation()._doingShipmentNotes && list.situation()._selectedAll
})

'Download Shipment Notes PDF'.availableWhen((cast, list) => {
  return list.situation()._doingShipmentNotes
})

'Download Shipment Notes PDF'.act(async list => {
  let shipments = await 'SOShipment'.bring({include: 'Yes'})
  if ( shipments.length === 0 ) {
    global.gApp.showMessage('Please select at least one shipment to include')
    return
  }
  let spec = "ShipmentNote.js"
  await list.downloadPDF({spec: spec, docName: "Shipment Notes.PDF"})
  let mold = global.foreman.doNameToMold('SOShipment')
  mold.version++
})

'attachmentIcon'.calculate(async cast => {
  let attachment = await 'Attachment'.bringFirst({theParentId: cast.id})
  if ( attachment )
    return 'Paperclip'
})

'attachmentIcon'.destination(async cast => {
  return 'AttachmentsByParent.js'
})


'Merge With Previous'.availableWhen(shipment => {
  return shipment.isLastInOrderFast() && (shipment.orderShipmentCountFast() > 1)
})

'Merge With Previous'.act(async (list, shipment) => {
  let so = await shipment.toSO(); if ( ! so ) return
  let prev = await so.toSecondLastShipment(); if ( ! prev ) return
  if ( prev.invoiced === 'Yes' ) {
    global.gApp.showMessage('Cannot merge with previous shipment because the previous shipment has been invoiced')
    return
  }
  await so.refreshShipment({merge: true})
  await global.foreman.doSave({msgOnException: true})
  list.refresh()
})

'ViewSOShipments'.filter(async shipment => {
  if ( ! shipment.order ) return false
  let order = await 'orders.RecentOrActive'.bringSingle({id: shipment.order.id})
  return order ? true : false
})
