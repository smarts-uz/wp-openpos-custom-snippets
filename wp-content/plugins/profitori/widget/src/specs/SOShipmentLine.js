'SOShipmentLine'.datatype()
'shipmentNumber'.field({refersToParent: 'SOShipment', parentIsHeader: true})
'product'.field({refersTo: 'products'})
'quantityShipped'.field({numeric: true})
'soLine'.field({refersTo: 'SOLine', caption: "SO Line", indexed: true})
'order_item_id'.field({numeric: true, indexed: true, storedCalc: true})
'descriptionAndSKU'.field()
'customerId'.field({numeric: true})
'orderId'.field({numeric: true, storedCalc: true})
'shipmentDate'.field({date: true})
'order'.field({refersTo: 'orders'})
'billingCompany'.field()
'invoiced'.field({yesOrNo: true})
'shipmentDate'.field({date: true})
'sku'.field()
'quantityOrdered'.field({numeric: true})
'quantityRemaining'.field({numeric: true})
'originalSOLine'.field({refersTo: 'SOLine', indexed: true})
'originalOrderItemId'.field()
'hasLots'.field({yesOrNo: true, deferCalc: true})
'lotsAreSerials'.field({yesOrNo: true})
'allowNegativeUnspecified'.field({yesOrNo: true})

'SOShipmentLine'.method('toSO', async function() {
  let soLine = await this.toSOLine(); if ( ! soLine ) return null
  return await soLine.toSO()
})

'SOShipmentLine'.method('copyAllotments', async function(soLine) {
  let shipmentLine = this

  let shipmentLineAndLotToQuantity = async (shipmentLine, lot) => {
    let allotments = await shipmentLine.toAllotments()
    for ( var i = 0; i < allotments.length; i++ ) {
      let allotment = allotments[i]
      if ( allotment.lot.id === lot.id )
        return allotment.quantity
    }
    return 0
  }

  let allotmentToQtyShippedOnOtherShipments = async (allotment, soLine) => {
    let res = 0
    let lot = await allotment.toLot(); if ( ! lot ) return res
    let shipmentLines = await 'SOShipmentLine'.bring({soLine: soLine})
    for ( var i = 0; i < shipmentLines.length; i++ ) {
      let shipmentLine = shipmentLines[i]
      let shipmentNumber = await shipmentLine.toShipmentNumber()
      if ( shipmentNumber === this.latestShipmentNumber ) continue
      let quantityShipped = await shipmentLineAndLotToQuantity(shipmentLine, lot)
      res += quantityShipped
    }
    return res
  }

  await shipmentLine.trashChildren()
  if ( soLine.hasLots !== 'Yes' ) return
  let allotments = await soLine.toAllotments()
  for ( var i = 0; i < allotments.length; i++ ) {
    let allotment = allotments[i]
    if ( ! allotment.lot ) continue
    if ( allotment.lot.keyval === 'Unspecified' ) continue
    let qtyAlreadyShipped = await allotmentToQtyShippedOnOtherShipments(allotment, soLine)
    let diff = allotment.quantity - qtyAlreadyShipped
    if ( ! diff ) 
      continue
    let newAllotment = await 'Allotment'.create({parentCast: shipmentLine}, {allotmentParent: shipmentLine.reference()})
    newAllotment.product = global.copyObj(allotment.product)
    newAllotment.lot = global.copyObj(allotment.lot)
    newAllotment.quantity = diff
    newAllotment.expiryDate = allotment.expiryDate
    newAllotment.supplierLotNumber = allotment.supplierLotNumber
    newAllotment.refreshIndexes()
  }
  let c = await 'Configuration'.bringFirst()
  await c.balanceAllotments(shipmentLine)
})

'SOShipmentLine'.method('toSOLine', async function() {
  return await this.referee('soLine')
})

'SOShipmentLine'.method('toLocation', async function() {
  let soLine = await this.toSOLine(); if ( ! soLine ) return null
  return await soLine.toShipFromLocation()
})

'SOShipmentLine'.method('toMainQuantity', async function() {
  return this.quantityShipped
})

'hasLots'.calculate(async line => {
  let inv = await line.toInventory(); if ( ! inv ) return 'No'
  let bres = inv.lotTracking && (inv.lotTracking !== 'None')
  let res = bres ? 'Yes' : 'No'
  return res
})

'lotsAreSerials'.calculate(async line => {
  let inv = await line.toInventory(); if ( ! inv ) return 'No'
  let bres = inv.lotTracking === 'Serial'
  let res = bres ? 'Yes' : 'No'
  return res
})

'SOShipmentLine'.method('toAllotments', async function() {
  return await 'Allotment'.bringChildrenOf(this)
})

'SOShipmentLine'.method('toProductFast', function() {
  if ( ! this.product ) return null
  return this.refereeFast('product')
})

'SOShipmentLine'.method('toInventory', async function() {
  let product = this.toProductFast()
  if ( (! product) || (product === 'na') ) {
    product = await this.toProduct(); if ( ! product ) return null
  }
  return await product.toInventory()
})

'SOShipmentLine'.method('toInventoryFast', function() {
  let product = this.toProductFast()
  if ( (! product) || (product === 'na') ) return product
  return product.toInventoryFast()
})

'orderId'.calculateWhen(line => {
  if ( ! line.soLine ) return false
  if ( ! line.soLine.order ) return false
  if ( ! line.soLine.order.id ) return false
  if ( line.soLine.order.id < 0 ) return false
  return true
})

'orderId'.calculate(async line => {
  let soLine = await line.referee('soLine'); if ( ! soLine ) return 0
  return soLine.order.id
})

'order_item_id'.calculateWhen(line => line.soLine ? true : false)

'order_item_id'.calculate(async line => {
  let soLine = await line.referee('soLine'); if ( ! soLine ) return 0
  return soLine.order_item_id
})

'SOShipmentLine'.method('toOriginalSOLine', async function() {
  return await this.referee('originalSOLine')
})

'quantityRemaining'.calculate(async soShipmentLine => {
  let soLine = await soShipmentLine.referee('soLine'); if ( ! soLine ) return 0
  return soLine.quantityRemainingToShip
})

'quantityOrdered'.calculate(async soShipmentLine => {
  let oi = await soShipmentLine.toOrderItem(); if ( ! oi ) return 0
  return oi._qty
})

'SOShipmentLine'.method('toProductUniqueName', async function() {
  let product = await this.toProduct(); if ( ! product ) return null
  return product.uniqueName
})

'SOShipmentLine'.method('toProduct', async function() {
  return await this.referee('product')
})

'sku'.calculate(async shipmentLine => {
  let product = await shipmentLine.referee('product'); if ( ! product ) return null
  return product._sku
})

'shipmentDate'.calculate(async shipmentLine => {
  let shipment = await shipmentLine.toShipment(); if ( ! shipment ) return null
  return shipment.shipmentDate
})

'SOShipmentLine'.method('toValue', async function() {
  let oi = await this.toOrderItem(); if ( ! oi ) return 0
  let unitPrice = await oi.toUnitPrice()
  return this.quantityShipped * unitPrice
})

'SOShipmentLine'.method('toValueExclTax', async function() {
  let oi = await this.toOrderItem(); if ( ! oi ) return 0
  return this.quantityShipped * (await oi.toUnitPriceExclTax())
})

'order'.calculate(async shipmentLine => {
  let oi = await shipmentLine.toOrderItem(); if ( ! oi ) return null
  let order = await oi.toOrder(); if ( ! order ) return null
  return order.reference()
})

'billingCompany'.calculate(async shipmentLine => {
  if ( ! shipmentLine.order ) return ''
  let order = await 'orders.RecentOrActive'.bringSingle({id: shipmentLine.order.id}); if ( ! order ) return null
  return order._billing_company
})

'invoiced'.calculate(async shipmentLine => {
  let shipment = await shipmentLine.toShipment(); if ( ! shipment ) return null
  return shipment.invoiced
})

'SOShipmentLine'.method('toOrderItem', async function() {
  return await 'order_items.RecentOrActive'.bringSingle({id: this.order_item_id})
})

'SOShipmentLine'.method('toShipmentNumber', async function() {
  let shipment = await this.toShipment(); if ( ! shipment ) return null
  return shipment.shipmentNumber
})

'SOShipmentLine'.method('toShipment', async function() {
  return await this.referee('shipmentNumber')
})

/*
'shipmentDate'.calculate(async shipmentLine => {
  let shipment = await shipmentLine.referee('shipmentNumber'); if ( ! shipment ) return global.emptyYMD()
  return shipment.shipmentDate
})
*/

'descriptionAndSKU'.calculate(async shipmentLine => {
  let product = shipmentLine.refereeFast('product')
  if ( (! product) || (product === 'na') )
    product = await shipmentLine.referee('product')
  if ( ! product )
    return null
  return product.uniqueName
})

'descriptionAndSKU'.destination(async shipmentLine => {
  return await shipmentLine.referee('product')
})
