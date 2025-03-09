'Recalls'.list({withHeader: true, icon: 'Undo'})
'Recalls Shipment Search'.title()
'Back'.action({act: 'cancel'})
'Download to Excel'.action({act: 'excel'})
'fromDate'.field({date: true})
'toDate'.field({date: true})
'customer'.field({refersTo: 'users'})

'Lines'.manifest()
'Go'.action({place: 'header', spinner: true})
'RecallsResult'.datatype({transient: true})
'lot'.field({refersTo: 'Lot', showAsLink: true})
'product'.field({refersTo: 'products', showAsLink: true})
'customer'.field({refersTo: 'users', showAsLink: true})
'order'.field({refersTo: 'orders', showAsLink: true, caption: 'Order'})
'orderDate'.field({date: true})
'shipment'.field({refersTo: 'SOShipment', showAsLink: true})
'shipmentDate'.field({date: true})
'pallet'.field({refersTo: 'Pallet'})
'quantity'.field({numeric: true})

/* eslint-disable no-cond-assign */

'lot'.destination(async rr => {
  return 'ComponentLotsByFGLot.js'
})

'Recalls'.beforeLoading(async list => {
  await list.harmonize()
})

'fromDate'.default(async (list) => {
  let c = await 'Configuration'.bringSingle(); if ( ! c ) return
  return c.viewFromDate
})

'toDate'.default(async (list) => {
  let c = await 'Configuration'.bringSingle(); if ( ! c ) return
  return c.viewToDate
})

'Go'.act(async (list) => {

  let saveSetting = async (fieldName, settingFieldName) => {
    let c = await 'Configuration'.bringOrCreate()
    let v = list.getFieldValue(fieldName)
    c[settingFieldName] = v
  }

  await saveSetting('fromDate', 'viewFromDate')
  await saveSetting('toDate', 'viewToDate')
  await global.app.save()
  let fromDate = list.getFieldValue('fromDate')
  if ( (! fromDate) || fromDate.isEmptyYMD() ) fromDate = "0000-00-00"
  let toDate = list.getFieldValue('toDate')
  if ( (! toDate) || toDate.isEmptyYMD() ) toDate = "9999-99-99"

  let getCustomerShipmentLines = async customer => {
    let sh, shs = await 'SOShipment'.bring({customer: customer})
    let res = []
    while ( sh = shs.__() ) {
      res.appendArray(await sh.toLines())
    }
    return res
  }

  let getShipmentLines = async () => {
    let customer = await list.referee('customer')
    if ( customer )
      return await getCustomerShipmentLines(customer)
    return await 'SOShipmentLine'.bring()
  }

  let allotmentToPallet = async a => {
    let palletLine = await 'PalletLine'.bringFirst({allotment: a}); if ( ! palletLine ) return null
    return await palletLine.referee('pallet')
  }

  let addResult = async (parms) => {
    let shipment = parms.shipment
    let shipmentLine = parms.shipmentLine
    let so = parms.so
    let order = await so.toOrder(); if ( ! order ) return
    let allotment = parms.allotment
    let soLine = await shipmentLine.toSOLine(); if ( ! soLine ) return
    let pallet = await allotmentToPallet(allotment)
    let customer = await so.toCustomer()
    let r = await 'RecallsResult'.create()
    r.lot = allotment.lot
    r.product = allotment.product
    r.customer = customer ? customer.reference() : null
    r.order = order.reference()
    r.orderDate = so.orderDate
    r.shipment = shipment.reference()
    r.shipmentDate = shipment.shipmentDate
    r.pallet = pallet ? pallet.reference() : null
    r.quantity = allotment.quantity
  }

  let addResultsForShipmentLine = async shipmentLine => {
    let so = await shipmentLine.toSO(); if ( ! so ) return
    let shipment = await shipmentLine.toShipment(); if ( ! shipment ) return
    let orderDateOutsideRange = (so.orderDate < fromDate) || (so.orderDate > toDate)
    let shipmentDateOutsideRange = (shipment.shipmentDate < fromDate) || (shipment.shipmentDate > toDate)
    if ( orderDateOutsideRange && shipmentDateOutsideRange ) 
      return
    let a, as = await shipmentLine.toAllotments()
    while ( a = as.__() ) {
      await addResult({so: so, shipment: shipment, shipmentLine: shipmentLine, allotment: a})
    }
  }

  await list.app().harmonize()
  await 'RecallsResult'.clear()

  let shipmentLine, shipmentLines = await getShipmentLines()
  while ( shipmentLine = shipmentLines.__() ) {
    await addResultsForShipmentLine(shipmentLine)
  }

})
