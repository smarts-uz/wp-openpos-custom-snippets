'WOLine'.datatype()
'workOrder'.field({refersToParent: 'WO', parentIsHeader: true})
'product'.field({refersTo: 'products', indexed: true})
'description'.field()
'location'.field({refersTo: 'Location', allowEmpty: false, caption: 'From Location'})
'quantity'.field({numeric: true, caption: 'Line Qty'})
'taxPct'.field({numeric: true, decimals: 2, caption: "Tax %"})
'sku'.field({caption: 'SKU'})
'descriptionCalc'.field()
'descriptionAndSKU'.field({caption: 'Description'})
'includeTaxOption'.field({yesOrNo: true})
'image'.field({postImage: true, postImageType: 'full', postIdField: 'product'})
'thumbnailImage'.field({postImage: true, postImageType: 'thumbnail', postIdField: 'product', caption: 'Image'})
'unitCostIncTax'.field({numeric: true, minDecimals: 2, maxDecimals: 6, caption: "Unit Cost (Inc Tax)"})
'unitCostExclTax'.field({numeric: true, minDecimals: 2, maxDecimals: 6, caption: "Unit Cost (Excl Tax)"})
'lineCostIncTax'.field({numeric: true, minDecimals: 2, maxDecimals: 6, caption: "Line Total (Inc Tax)"})
'lineCostExclTax'.field({numeric: true, minDecimals: 2, maxDecimals: 6, caption: "Line Total (Excl Tax)"})
'quantityOnHand'.field({numeric: true})
'quantityPerFGUnit'.field({numeric: true, decimals: 3, caption: 'Qty per FG Unit'})
'costPerFGUnitIncTax'.field({numeric: true, minDecimals: 2, maxDecimals: 6, caption: 'Cost per FG Unit (Inc Tax)'})
'costPerFGUnitExclTax'.field({numeric: true, minDecimals: 2, maxDecimals: 6, caption: 'Cost per FG Unit (Excl Tax)'})
'hasLots'.field({yesOrNo: true, deferCalc: true})
'lotsAreSerials'.field({yesOrNo: true})
'woStatus'.field()
'component'.field({refersTo: 'Component'})

/* eslint-disable no-cond-assign */

'WOLine'.method('lotRefToAllotment', async function(lotRef) {
  if ( ! lotRef ) return null
  let a, as = await this.toAllotments()
  while ( a = as.__() ) {
    if ( a.lot && (a.lot.keyval === lotRef.keyval) )
      return a
  }
  return null
})

'WOLine'.method('allot', async function(opt) {

  let product = await this.toProduct(); if ( ! product ) return
  let rem = this.quantity

  let updateAllotment = async clump => {
    let qty = clump.quantityPackable2
    if ( qty > rem )
      qty = rem
    rem -= qty
    if ( qty <= 0 )
      return
    let a = await this.lotRefToAllotment(clump.lot)
    if ( ! a )
      a = await 'Allotment'.create({parentCast: this}, {allotmentParent: this.reference(), lot: clump.lot})
    a.lot = global.copyObj(clump.lot)
    a.product = product.reference()
    a.quantity = qty
    a.expiryDate = clump.expiryDate
    a.supplierLotNumber = clump.supplierLotNumber
    a.refreshIndexes()
  }

  let getPackableClumpsInExpiringSeq = async () => {
    let cluster = await this.toCluster() // Note: creates if not there
    let clumps = await cluster.getPackableClumps()
    clumps.sort((a, b) => (a.expiryDate > b.expiryDate) ? 1 : -1)
    return clumps
  }

  let clearAllotments = async () => {
    let a, as = await this.toAllotments()
    while ( a = as.__() ) {
      a.quantity = 0
    }
  }

  let doAllot = async () => {
    await clearAllotments()
    let clump, clumps = await getPackableClumpsInExpiringSeq()
    while ( clump = clumps.__() ) {
      if ( rem <= 0 )
        break
      if ( clump.lot && (clump.lot.keyval === 'Unspecified') ) continue
      await updateAllotment(clump)
    }
  }
  
  await doAllot()
})

'WOLine'.method('toWOReceipt', async function() {
  let wo = await this.toWO(); if ( ! wo ) return null
  return await wo.toFirstWOReceipt()
})

'WOLine'.method('toComponent', async function() {
  return await this.referee('component')
})

'WOLine'.method('refreshUnspecifiedLot', async function() {
  if ( this.hasLots !== 'Yes' ) return
  let c = await 'Configuration'.bringFirst(); if ( ! c ) return
  await c.balanceAllotments(this)
})

'quantity'.afterUserChange(async (oldInputValue, newInputValue, woLine, maint) => {
  await woLine.refreshUnspecifiedLot()
})

'product'.afterUserChange(async (oldInputValue, newInputValue, woLine, maint) => {
  let inventory = await woLine.toInventory()
  if ( inventory )
    woLine.location = await inventory.getDefaultLocationRef()
  await woLine.refreshUnspecifiedLot()
})

'WOLine'.method('refreshClumps', async function(allotment) {
  if ( this.hasLots !== 'Yes' ) return
  let allotments = await this.toAllotments()
  for ( var i = 0; i < allotments.length; i++ ) {
    let a = allotments[i]
    let clump = await a.toClump(); if ( ! clump ) continue
    await clump.refreshQuantityOnWOLines()
  }
})

'WOLine'.method('toAllotments', async function() {
  return await 'Allotment'.bringChildrenOf(this)
})

'WOLine'.method('toLocationName', async function() {
  let loc = await this.toLocation(); if ( ! loc ) return 'General'
  let res = loc.locationName
  if ( ! res )
    res = 'General'
  return res
})

'WOLine'.method('refreshQuantityOnWOLines', async function() {
  let inv = await this.toInventory(); if ( ! inv ) return
  await inv.refreshQuantityOnWOLines({refreshClusters: true})
})

'woStatus'.calculate(async woLine => {
  let wo = await woLine.toWO(); if ( ! wo ) return ''
  return wo.status
})

'WOLine'.method('toLocation', async function() {
  return await this.referee('location')
})

'WOLine'.method('toMainQuantity', async function() {
  return this.quantity
})

'hasLots'.calculate(async woLine => {
  let inv = await woLine.toInventory(); if ( ! inv ) return 'No'
  let bres = inv.lotTracking && (inv.lotTracking !== 'None')
  let res = bres ? 'Yes' : 'No'
  return res
})

'lotsAreSerials'.calculate(async woLine => {
  let inv = await woLine.toInventory(); if ( ! inv ) return 'No'
  let bres = inv.lotTracking === 'Serial'
  let res = bres ? 'Yes' : 'No'
  return res
})

'quantityPerFGUnit'.calculate(async woLine => {
  let wo = await woLine.toWO(); if ( ! wo ) return 0
  if ( wo.fgQuantity === 0 ) 
    return 0
  return woLine.quantity / wo.fgQuantity
})

'costPerFGUnitIncTax'.calculate(async woLine => {
  return woLine.unitCostIncTax * woLine.quantityPerFGUnit
})

'costPerFGUnitExclTax'.calculate(async woLine => {
  return woLine.unitCostExclTax * woLine.quantityPerFGUnit
})

'quantityOnHand'.calculate(async woLine => {
  let cluster = await woLine.toCluster(); if ( ! cluster ) return 0
  return cluster.quantityOnHand
})

'WOLine'.method("getLineTaxUnrounded", async function() {
  let rate = this.taxPct / 100
  let factor = rate / (1 + rate)
  let lineCostIncTax = await this.calcLineCostIncTax()
  let res = lineCostIncTax * factor
  return res
})

'WOLine'.method('enteredExclusiveOfTax', async function () {
  return this.includeTaxOption === "No"
})

'WOLine'.method('enteredInclusiveOfTax', async function () {
  return this.includeTaxOption !== "No"
})

'unitCostExclTax'.calculate(async (woLine) => {
  let res = global.roundToXDecimals(woLine.unitCostIncTax / ( 1 + (woLine.taxPct / 100)), 6)
  return res
})

'lineCostIncTax'.calculate(async (woLine) => {
  let res = await woLine.calcLineCostIncTax()
  return res
})

'lineCostExclTax'.calculate(async (woLine) => {
  let res = await woLine.calcLineCostExclTax()
  return res
})

'WOLine'.method('calcLineCostIncTax', async function () {
  let res = global.roundTo2Decimals(this.unitCostIncTax * this.quantity)
  return res
})

'WOLine'.method('calcLineCostExclTax', async function () {
  let res = global.roundTo2Decimals(this.unitCostExclTax * this.quantity)
  return res
})

'WOLine'.method('toStatus', async function() {
  let wo = await this.toWO(); if ( ! wo ) return null
  return wo.status
})

'WOLine'.afterCreating(async function () {
  let c = await 'Configuration'.bringFirst(); if ( ! c ) return
  this.includeTaxOption = c.mfgIncTax
})

'descriptionCalc'.calculate(async (woLine) => {
  return await woLine.toProductUniqueName()
})

'sku'.calculate(async (poLine) => {
  let p = poLine.toProductFast() 
  if ( (! p) || (p === 'na') ) {
    p = await poLine.toProduct(); if ( ! p ) return null
  }
  return p._sku
})

'WOLine'.method('toProductUniqueName', async function() {
  let p = await this.toProduct(); if ( ! p ) return null
  return p.uniqueName
})

'WOLine'.method('toInventoryFast', function() {
  let product = this.toProductFast(); 
  if ( (! product) || (product === 'na') ) return product
  return product.toInventoryFast()
})

'descriptionAndSKU'.calculate(woLine => {
  return woLine.descriptionCalc
})

'WOLine'.method('toInventory', async function() {
  let product = await this.toProduct(); if ( ! product ) return null
  return await product.toInventory()
})

'WOLine'.method('toWO', async function() {
  return await this.referee('workOrder', {includeMarkedForDeletion: true})
})

'WOLine'.method('toProduct', async function() {
  if ( ! this.product ) return null
  return await this.referee('product')
})

'WOLine'.method('toProductFast', function() {
  if ( ! this.product ) return null
  return this.refereeFast('product')
})

'WOLine'.method('toProductName', async function() {
  let p = await this.toProduct(); if ( ! p ) return null
  let res = await p.toName()
  return res
})

'WOLine'.method('ensureProdRefComplete', async function() {
  if ( ! this.product ) return
  if ( this.product.id ) return
  let product = await 'products'.bringFirst({uniqueName: this.product.keyval})
  if ( ! product ) 
    return 
    //throw(new Error('Invalid product ' + this.product.keyval))
  this.product.id = product.id
})

'WOLine'.method('ensureOldProdRefComplete', async function() {
  let old = this.getOld(); if ( ! old ) return
  if ( ! old.product ) return
  if ( old.product.id ) return
  let product = await 'products'.bringFirst({uniqueName: old.product.keyval})
  if ( ! product ) 
    return 
  old.product.id = product.id
})

'WOLine'.beforeSaving(async function() {
  let wo
  await this.ensureProdRefComplete()
  await this.ensureOldProdRefComplete()
  if ( ! this.product.id )
    return
  await this.refreshQuantityOnWOLines()
  wo = await this.toWO(); if ( ! wo ) return
  await wo.refreshStatus()
})

'WOLine'.method("toOrderDate", async function() {
  let wo = this.refereeFast('workOrder', {noCalc: true})
  if ( (! wo) || (wo === 'na') )
    wo = await this.toWO()
  return wo && wo.orderDate
})

'WOLine'.method('toCluster', async function() {
  let inv = await this.toInventory(); if ( ! inv ) return null
  let locRef = await this.location; if ( ! locRef ) return null
  let res = await inv.locationRefToCluster(locRef)
  return res
})
