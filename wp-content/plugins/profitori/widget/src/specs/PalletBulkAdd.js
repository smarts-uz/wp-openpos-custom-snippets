'PalletBulkAdd'.list()
'Add Products to Shipment Pallet'.title()
'Cancel'.action({act: 'cancel', icon: 'AngleLeft'})
'OK'.action({icon: 'CheckDouble'})

'BulkPalletLine'.datatype({transient: true})
'include'.field({tickbox: true, allowInput: true})
'product'.field({refersTo: 'products', readOnly: true, showAsLink: true})
'lot'.field({refersTo: 'Lot', readOnly: true, showAsLink: true, caption: 'Serial/Lot'})
'quantity'.field({numeric: true, readOnly: true})
'shipmentLine'.field({refersTo: 'SOShipmentLine', key: true, nonUnique: true, hidden: true})
'allotment'.field({refersTo: 'Allotment', hidden: true})

'product'.calculate(async line => {
  let sl = await line.referee('shipmentLine'); if ( ! sl ) return null
  return global.copyObj(sl.product)
})

'lot'.calculate(async line => {
  let a = await line.referee('allotment'); if ( ! a ) return null
  return global.copyObj(a.lot)
})

'OK'.act(async list => {

  let pallet = list.callerCast(); if ( ! pallet ) return
  let bulkLines = await 'BulkPalletLine'.bring()
  for ( var i = 0; i < bulkLines.length; i++ ) {
    let bulkLine = bulkLines[i]
    if ( bulkLine.include !== 'Yes' ) continue
    let inv = await bulkLine.toInventory(); if ( ! inv ) continue
    let palletLine = await 'PalletLine'.bringOrCreate(
      {pallet: pallet, shipmentLine: bulkLine.shipmentLine, allotment: bulkLine.allotment})
    palletLine.quantity = bulkLine.quantity
    await palletLine.refreshCalculations({force: true, includeDefers: true})
  }

  list.ok()
})

'PalletBulkAdd'.beforeLoading(async list => {

  let allotmentIsOnAPallet = async allotment => {
    let palletLine = await 'PalletLine'.bringFirst({allotment: allotment})
    return palletLine ? true : false
  }

  let shipmentLineIsOnAPallet = async shipmentLine => {
    let palletLine = await 'PalletLine'.bringFirst({shipmentLine: shipmentLine})
    return palletLine ? true : false
  }

  let addAllotmentLines = async shipmentLine => {
    let as = await shipmentLine.toAllotments()
    for ( var i = 0; i < as.length; i++ ) {
      let a = as[i]
      if ( a.quantity === 0 ) continue
      if ( await allotmentIsOnAPallet(a) ) continue
      let line = await 'BulkPalletLine'.create(null, {shipmentLine: shipmentLine, allotment: a}) 
      line.quantity = a.quantity
    }
  }

  await 'BulkPalletLine'.clear()
  let pallet = list.callerCast(); if ( ! pallet ) return
  let shipment = await pallet.toShipment(); if ( ! shipment ) return
  let shipmentLines = await shipment.toLines()
  for ( var i = 0; i < shipmentLines.length; i++ ) {
    let shipmentLine = shipmentLines[i]
    let line
    if ( shipmentLine.hasLots !== 'Yes' ) {
      if ( await shipmentLineIsOnAPallet(shipmentLine) ) continue
      line = await 'BulkPalletLine'.create(null, {shipmentLine: shipmentLine}) 
      line.quantity = shipmentLine.quantityShipped
    } else {
      await addAllotmentLines(shipmentLine)
    }
  }
  list.startAlter({skipFieldCheck: true})
})

'BulkPalletLine'.method('toInventory', async function() {
  let product = await this.referee('product'); if ( ! product ) return null
  return await product.toInventory()
})

