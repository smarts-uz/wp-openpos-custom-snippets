'SOsByComponentLot'.list({withHeader: true})
'Sales Orders By Component Lot'.title()
'Back'.action({act: 'cancel'})
'Download to Excel'.action({act: 'excel'})
'componentProduct'.field({refersTo: 'products'})
'componentLot'.field({refersTo: 'Lot'})

'Lines'.manifest()
'Go'.action({place: 'header', spinner: true})
'SOCResult'.datatype({transient: true})
'order'.field({refersTo: 'orders', showAsLink: true})
'lot'.field({refersTo: 'Lot', showAsLink: true})
'product'.field({refersTo: 'products', showAsLink: true})
'customer'.field({refersTo: 'users', showAsLink: true})
'orderDate'.field({date: true})
'shipment'.field({refersTo: 'SOShipment', showAsLink: true})
'shipmentDate'.field({date: true})
'quantity'.field({numeric: true})

/* eslint-disable no-cond-assign */

'componentLot'.excludeChoiceWhen(async (list, lot) => {
  if ( ! lot.product ) return true
  let productRef = list.getFieldValue('componentProduct')
  if ( ! productRef ) return true
  return lot.product.id !== productRef.id
})

'componentProduct'.afterUserChange(async (oldInputValue, newInputValue, headerCast, list) => {
  list.setFieldValue('componentLot', null)
})

'SOsByComponentLot'.beforeLoading(async list => {
  let cast = list.callerCast()
  if ( cast && cast.componentLot ) {
    let lot = await cast.referee('componentLot')
    if ( lot ) {
      list.setFieldValue('componentProduct', lot.product)
      list.setFieldValue('componentLot', lot.reference())
    }
  }
})

'SOsByComponentLot'.afterLoading(async list => {
  await list.doAction('Go')
})

'Go'.act(async (list) => {

  let addResult = async allotment => {
    let soLine = await allotment.toParent(); if ( ! soLine ) return
    let so = await soLine.toSO(); if ( ! so ) return
    let order = await so.toOrder(); if ( ! order ) return
    let shipment = await so.toLastShipment()
    let customer = await so.toCustomer()
    let r = await 'SOCResult'.create()
    r.lot = allotment.lot
    r.product = allotment.product
    r.customer = customer ? customer.reference() : null
    r.order = order.reference()
    r.orderDate = so.orderDate
    r.shipment = shipment ? shipment.reference() : null
    r.shipmentDate = shipment ? shipment.shipmentDate : null
    r.quantity = allotment.quantity
  }

  let lotToSOLineAllotments = async lot => {
    let res = []
    let a; let as = await 'Allotment'.bring({lot: lot})
    while ( a = as.__() ) {
      let parent = await a.toParent(); if ( ! parent ) continue
      if ( parent.datatype() !== 'SOLine' ) continue
      res.push(a)
    }
    return res
  }

  let getSOLineAllotments = async () => {
    let res = []
    //let componentLot = await list.referee('componentLot'); if ( ! componentLot ) return res
    let product = await list.referee('componentProduct'); if ( ! product ) return res
    let selectedLotRef = list.getFieldValue('componentLot'); if ( ! selectedLotRef ) return res
    let selectedLotNumber = selectedLotRef.keyval; if ( ! selectedLotNumber ) return res
    let componentLot = await product.lotNumberToLot(selectedLotNumber); if ( ! componentLot ) return res
    let lot, lots = [componentLot]
    lots.appendArray(await componentLot.toLotsUsedIn({recursive: true}))
    while ( lot = lots.__() ) {
      let as = await lotToSOLineAllotments(lot)
      res.appendArray(as)
    }
    return res
  }

  await 'SOCResult'.clear()

  let a; let as = await getSOLineAllotments()
  while ( a = as.__() ) {
    await addResult(a)
  }

})
