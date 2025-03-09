'Coproduct'.datatype()
'woReceipt'.field({key: true, nonUnique: true, refersToParent: 'WOReceipt', parentIsHeader: true})
'product'.field({refersTo: 'products', allowEmpty: false})
'quantity'.field({numeric: true, maxDecimals: 6, caption: 'Quantity'})
'notes'.field({multiLine: true})
'hasLots'.field({yesOrNo: true})
'lotsAreSerials'.field({yesOrNo: true})
'manuallyAllotted'.field({yesOrNo: true})

/* eslint-disable no-cond-assign */

'Coproduct'.method('toWOReceipt', async function() {
  return await this.toParent()
})

'Coproduct'.method('toWO', async function() {
  let wr = await this.toWOReceipt(); if ( ! wr ) return null
  return await wr.toWO()
})

'Coproduct'.method('toParent', async function() {
  return await this.parent()
})

'Coproduct'.cruxFields(['woReceipt', 'product'])

'Coproduct'.method('updateLotExpiryDates', async function() {
  let a; let as = await this.toAllotments()
  while ( a = as.__() ) {
    a.expiryDate = this.expiryDate
  }
})

'Coproduct'.method('toProduct', async function() {
  return await this.referee('product')
})

'Coproduct'.method('toCluster', async function() {
  let inv = await this.toInventory(); if ( ! inv ) return null
  let wor = await this.toWOReceipt(); if ( ! wor ) return null
  let res = await inv.locationRefToCluster(wor.fgLocation)
  return res
})

'Coproduct'.method('toMainQuantity', async function() {
  return this.quantity
})

'Coproduct'.method('refreshUnspecifiedLot', async function() {
  if ( this.hasLots !== 'Yes' ) return
  let c = await 'Configuration'.bringFirst(); if ( ! c ) return
  await c.balanceAllotments(this)
})

'hasLots'.calculate(async coproduct => {
  let inv = await coproduct.toInventory(); if ( ! inv ) return 'No'
  let bres = inv.lotTracking && (inv.lotTracking !== 'None')
  let res = bres ? 'Yes' : 'No'
  return res
})

'lotsAreSerials'.calculate(async coproduct => {
  let inv = await coproduct.toInventory(); if ( ! inv ) return 'No'
  let bres = inv.lotTracking === 'Serial'
  let res = bres ? 'Yes' : 'No'
  return res
})

'Coproduct'.method('toAllotments', async function() {
  return await 'Allotment'.bringChildrenOf(this)
})

'Coproduct'.method('createTransactionForAllotment', async function(a) {
  let receipt = await this.toWOReceipt(); if ( ! receipt ) return
  let wo = await this.toWO(); if ( ! wo ) return
  let chg = a.quantity - a.getOld().quantity
  if ( chg === 0 )
    return
  let product = await a.toProduct(); if ( ! product ) return
  let tran = await 'Transaction'.create()
  tran.product = a.product ? a.product : this.product
  if ( a._markedForDeletion )
    tran.product = a.getOld().product
  tran.date = receipt.receivedDate
  tran.quantity = chg
  tran.unitCost = await product.toAvgUnitCost()
  tran.source = 'WO Receipt'
  tran.reference = wo.workOrderNumber
  tran.location = global.copyObj(wo.fgLocation)
  tran.lot = global.copyObj(a.lot)
  tran.taxPct = wo.taxPct
  a.quantityUpdatedToOnHand += chg
})

'Coproduct'.method('checkLotQuantities', async function() {
  let qty = 0
  let allotments = await 'Allotment'.bringChildrenOf(this)
  for ( var i = 0; i < allotments.length; i++ ) {
    let a = allotments[i]
    qty += a.quantity
  }
  if ( qty !== this.quantity ) {
    let msg
    if ( this.lotsAreSerials === 'Yes' )
      msg = 'Quantity of Serial Numbers'.translate() + ' (' + qty + ') ' +
        'does not match the quantity received'.translate() + ' (' + this.quantity + ')'
    else
      msg = 'Lot quantity total'.translate() + ' (' + qty + ') ' +
        'does not match the quantity received'.translate() + ' (' + this.quantity + ')'
    throw(new Error(msg))
  }
})

'Coproduct'.beforeSaving(async function() {
  if ( (this.hasLots === 'Yes') && this.propChanged('quantity') ) 
    await this.checkLotQuantities()
})

'Coproduct'.method('toInventory', async function() {
  let product = await this.referee('product'); if ( ! product ) return null
  return await product.toInventory()
})

'Coproduct'.method('toLocation', async function() {
  let receipt = await this.toWOReceipt(); if ( ! receipt ) return
  return await receipt.referee('fgLocation')
})

