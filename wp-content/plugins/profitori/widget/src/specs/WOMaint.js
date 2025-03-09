'WOMaint'.maint({panelStyle: "titled", icon: 'Tools'})
'Add Work Order'.title({when: 'adding'})
'Edit Work Order'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'FG Lots'.action({spec: 'WOLotsMaint.js'})
'View Receipts'.action({spec: 'ViewWOReceipts.js'})
'Labels'.action({spec: "Labels.js"})
'Download PDF'.action()
'Attachments'.action({act: 'attachments'})
'Add another'.action({act: 'add'})
'Bundles'.action({spec: 'BundleList.js'})
'WO'.datatype({caption: 'Work Order'})

'Order Summary'.panel()
'workOrderNumber'.field({key: true})
'product'.field({refersTo: 'products', indexed: true})
'fgQuantity'.field({numeric: true, caption: 'Finished Goods Quantity'})
'receivedQuantity'.field({numeric: true, readOnly: true})
'cancelledQuantity'.field({numeric: true, readOnly: true})
'orderDate'.field({date: true})
'scheduledDate'.field({date: true})
'priority'.field()
'expectedCompletionDate'.field({date: true})
'overdueDays'.field({numeric: true, hidden: true})
'includeTaxOption'.field({yesOrNo: true, hidden: true})
'pdfTitle'.field({hidden: true})
'linesGenerated'.field({yesOrNo: true, hidden: true})

'Manufacturing Details'.panel()
'fgLocation'.field({refersTo: 'Location', allowEmpty: false, caption: 'Finished Goods Location'})
'expiryDate'.field({date: true, allowEmpty: true})
'notes'.field({multiLine: true})
'status'.field({refersTo: 'WOStatus', readOnly: true, translateOnDisplay: true})
'stage'.field()
'receivedDate'.field({date: true, readOnly: true, caption: 'Completed Date'})
'tax'.field({numeric: true, decimals: 2, hidden: true})
'taxPct'.field({numeric: true, decimals: 2, caption: "Tax %", hidden: true})
'overheadCostIncTax'.field({numeric: true, hidden: true, decimals: 2, readOnly: true, caption: 'Overhead (Inc Tax)'})
'overheadCostExclTax'.field({numeric: true, hidden: true, decimals: 2, readOnly: true, caption: 'Overhead (Excl Tax)'})
'compCostPerFGUnitIncTax'.field({numeric: true, decimals: 2, readOnly: true, caption: 'Cost of Components per FG Unit (Inc Tax)'})
'compCostPerFGUnitExclTax'.field({numeric: true, decimals: 2, readOnly: true, caption: 'Cost of Components per FG Unit (Excl Tax)'})
'overheadPerFGUnitIncTax'.field({numeric: true, decimals: 2, readOnly: true, caption: 'Overhead per FG Unit (Inc Tax)'})
'overheadPerFGUnitExclTax'.field({numeric: true, decimals: 2, readOnly: true, caption: 'Overhead per FG Unit (Excl Tax)'})
'costIncTax'.field({hidden: true, numeric: true, decimals: 2, caption: "Order Total (Inc Tax)"})
'costExclTax'.field({hidden: true, numeric: true, decimals: 2, caption: "Order Total (Excl Tax)"})
'fgUnitCostIncTax'.field({numeric: true, decimals: 2, readOnly: true, caption: 'Total Cost Per FG Unit (Inc Tax)'})
'fgUnitCostExclTax'.field({numeric: true, decimals: 2, readOnly: true, caption: 'Total Cost Per FG Unit (Excl Tax)'})
'hasLots'.field({yesOrNo: true, hidden: true})
'lotsAreSerials'.field({yesOrNo: true, hidden: true})
'fgLotsAndQuantities'.field({hidden: true})
'manuallyAllotted'.field({yesOrNo: true, hidden: true})

'Lines'.manifest()
'Generate Lines'.action()
'WOLine'.datatype()
'thumbnailImage'.field()
'descriptionAndSKU'.field({showAsLink: true, readOnly: true})
'sku'.field({readOnly: true})
'location'.field({readOnly: false})
'quantityPerFGUnit'.field({readOnly: true})
'quantity'.field({readOnly: true})
'quantityOnHand'.field({readOnly: true, caption: 'On Hand'})
'unitCostIncTax'.field({readOnly: true})
'unitCostExclTax'.field({readOnly: true})
'costPerFGUnitIncTax'.field({readOnly: true, caption: 'Cost per FG Unit (Inc Tax)'})
'costPerFGUnitExclTax'.field({readOnly: true, caption: 'Cost per FG Unit (Excl Tax)'})
'lineCostExclTax'.field({readOnly: true, hidden: true})
'lineCostIncTax'.field({readOnly: true, hidden: true})
'Edit'.action({place: 'row', act: 'edit'})
'WOLineMaint.js'.maintSpecname()

/* eslint-disable no-cond-assign */

'priority'.options(['', '1', '2', '3', '4', '5', '6', '7', '8', '9'])

'WO'.method('updateLotExpiryDates', async function() {
  let a; let as = await this.toAllotments()
  while ( a = as.__() ) {
    a.expiryDate = this.expiryDate
  }
})

'WO'.method('toFirstWOReceipt', async function() {
  return await 'WOReceipt'.bringFirst({workOrder: this})
})

'expiryDate'.afterUserChange(async (oldInputValue, newInputValue, wo, maint) => {
  await wo.updateLotExpiryDates()
  await wo.refreshUnspecifiedLot()
})

'expiryDate'.visibleWhen((maint, wo) => {
  return wo.hasLots === 'Yes'
})

'expiryDate'.default(global.emptyYMD())

'fgLotsAndQuantities'.calculate(async wo => {
  if ( wo.hasLots !== 'Yes' ) return ''
  let res = ''
  let as = await wo.toAllotments()
  for ( var i = 0; i < as.length; i++ ) {
    let a = as[i]
    let lot = await a.toLot(); if ( ! lot ) continue
    res += lot.lotNumber + ' (' + a.quantity + ') '
  }
  return res
})

'WO'.method('thereAreLinesWithLots', async function() {
  let lines = await this.toWOLines()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    if ( line.hasLots === 'Yes' ) 
      return true
  }
  return false
})

'WO'.method('checkSerialNotDuplicated', async function(allotment) {
  if ( this.lotsAreSerials !== 'Yes' ) return
  let lot = await allotment.toLot(); if ( ! lot ) return
  if ( lot.lotNumber === 'Unspecified' )
    return
  let otherAllotments = await 'Allotment'.bring({lot: lot})
  for ( var j = 0; j < otherAllotments.length; j++ ) {
    let oa = otherAllotments[j]
    if ( oa.id === allotment.id ) continue
    if ( oa.quantity <= 0 ) continue
    let parent = await oa.parent(); if ( ! parent ) continue
    if ( oa.quantity <= 0 ) continue
    if ( parent.datatype() !== 'WO' ) continue
    throw(new Error('This serial number is already on another work order'))
  }
})

'WO'.method('toAllotments', async function() {
  return await 'Allotment'.bringChildrenOf(this)
})

'WO'.method('toReceivedAllotments', async function() {
  let res = []
  let rs = await this.toWOReceipts()
  for ( var i = 0; i < rs.length; i++ ) {
    let r = rs[i]
    let as = await r.toAllotments()
    res.appendArray(as)
  }
  return res
})

'WO'.method('allotmentToOutstandingQuantity', async function(allotment) {
  let qty = allotment.quantity
  let lotNumber = await allotment.toLotNumber()
  let receivedAllotments = await this.toReceivedAllotments()
  for ( let i = 0; i < receivedAllotments.length; i++ ) {
    let ra = receivedAllotments[i]
    let rln = await ra.toLotNumber()
    if ( rln !== lotNumber ) continue
    qty -= ra.quantity
  }
  return qty
})

'WO'.method('toCluster', async function() {
  let inv = await this.toInventory(); if ( ! inv ) return null
  let res = await inv.locationRefToCluster(this.fgLocation)
  return res
})

'WO'.method('toMainQuantity', async function() {
  return this.fgQuantity
})

'WO'.method('refreshUnspecifiedLot', async function() {
  if ( this.hasLots !== 'Yes' ) return
  let c = await 'Configuration'.bringFirst(); if ( ! c ) return
  await c.balanceAllotments(this)
})

'FG Lots'.availableWhen(wo => {
  if ( ! wo ) return false
  return wo.hasLots === 'Yes'
})

'hasLots'.calculate(async wo => {
  let inv = await wo.toInventory(); if ( ! inv ) return 'No'
  let bres = inv.lotTracking && (inv.lotTracking !== 'None')
  let res = bres ? 'Yes' : 'No'
  return res
})

'lotsAreSerials'.calculate(async wo => {
  let inv = await wo.toInventory(); if ( ! inv ) return 'No'
  let bres = inv.lotTracking === 'Serial'
  let res = bres ? 'Yes' : 'No'
  return res
})

'overheadPerFGUnitIncTax'.calculate(async wo => {
  if ( ! wo.fgQuantity ) return 0
  return wo.overheadCostIncTax / wo.fgQuantity
})

'overheadPerFGUnitExclTax'.calculate(async wo => {
  if ( ! wo.fgQuantity ) return 0
  return wo.overheadCostExclTax / wo.fgQuantity
})

'overheadPerFGUnitIncTax'.visibleWhen((maint, wo) => {
  return wo.includeTaxOption !== "No"
})

'overheadPerFGUnitExclTax'.visibleWhen((maint, wo) => {
  return wo.includeTaxOption === "No"
})

'costPerFGUnitIncTax'.columnVisibleWhen((maint, wo) => {
  return wo.includeTaxOption !== "No"
})

'costPerFGUnitExclTax'.columnVisibleWhen((maint, wo) => {
  return wo.includeTaxOption === "No"
})

'location'.readOnlyWhen((grid, woLine) => {
  let wo = woLine.refereeFast('workOrder', {noCalc: true}); 
  if ( (! wo) || (wo === 'na') ) return true
  return wo.status !== 'Awaiting Manufacture'
})

'product'.readOnlyWhen((maint, wo) => wo.linesGenerated === 'Yes')

'taxPct'.calculate(async wo => {
  let product = await wo.referee('product'); if ( ! product ) return 0
  return await product.toTaxPct()
})

'descriptionAndSKU'.destination(async woLine => {
  return await woLine.toProduct()
})

'compCostPerFGUnitIncTax'.visibleWhen((maint, wo) => {
  if ( wo.status === 'New' ) return false
  return wo.includeTaxOption !== "No"
})

'compCostPerFGUnitExclTax'.visibleWhen((maint, wo) => {
  if ( wo.status === 'New' ) return false
  return wo.includeTaxOption === "No"
})

'overheadCostExclTax'.calculate(async (wo) => {
  let res = global.roundToXDecimals(wo.overheadCostIncTax / ( 1 + (wo.taxPct / 100)), 6)
  return res
})

/*
'overheadCostIncTax'.calculate(async wo => {
  let bundle = await wo.toBundle(); if ( ! bundle ) return 0
  return wo.fgQuantity * bundle.overheadCost
})
*/

'WO'.method('toBundle', async function() {
  let product = await this.referee('product'); if ( ! product ) return null
  return await product.toBundle()
})

'fgUnitCostIncTax'.visibleWhen((maint, wo) => {
  if ( wo.status === 'New' ) return false
  return wo.includeTaxOption !== "No"
})

'fgUnitCostExclTax'.visibleWhen((maint, wo) => {
  if ( wo.status === 'New' ) return false
  return wo.includeTaxOption === "No"
})

'fgUnitCostIncTax'.calculate(async wo => {
  if ( ! wo.fgQuantity ) return 0
  return wo.costIncTax / wo.fgQuantity
})

'fgUnitCostExclTax'.calculate(async (wo) => {
  let res = global.roundToXDecimals(wo.fgUnitCostIncTax / ( 1 + (wo.taxPct / 100)), 6)
  return res
})

'fgLocation'.afterUserChange(async (oldInputValue, newInputValue, wo) => {
  let hasLines = await wo.hasLines()
  if ( hasLines )
    return "Location cannot be altered once work order lines have been generated"
})

'WO'.method('toWOReceipts', async function() {
  let res = await 'WOReceipt'.bring({workOrder: this})
  return res
})

'WO'.method('refreshReceived', async function() {
  let recs = await this.toWOReceipts()
  this.receivedQuantity = 0
  this.cancelledQuantity = 0
  for ( var i = 0; i < recs.length; i++ ) {
    let rec = recs[i]
    this.receivedQuantity += rec.receivedQuantity
    this.cancelledQuantity += rec.cancelledQuantity
  }
})

'WO'.method('refreshOverhead', async function() {

  let calcOverheadCostIncTax = async () => {
    let bundle = await wo.toBundle(); if ( ! bundle ) return 0
    return wo.fgQuantity * bundle.overheadCost
  }

  let wo = this
  wo.overheadCostIncTax = await calcOverheadCostIncTax()
})

'product'.afterUserChange(async (oldInputValue, newInputValue, wo, maint) => {
  let inventory = await wo.toInventory()
  if ( inventory )
    wo.fgLocation = await inventory.getDefaultLocationRef()
  await wo.refreshOverhead()
  await wo.refreshExpectedDeliveryDate()
  await wo.refreshExpiryDate()
  await wo.refreshUnspecifiedLot()
})

'WO'.method('toInventory', async function() {
  let product = await this.referee('product'); if ( ! product ) return null
  return await product.toInventory({allowCreate: true})
})

'pdfTitle'.calculate(po => {
  return 'WORK ORDER'.translate()
})

'WO'.method('refreshClumps', async function() {
  let lines = await this.toWOLines()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    await line.refreshClumps()
  }
})

'WO'.method('toWOLines', async function() {
  return await 'WOLine'.bringChildrenOf(this)
})

'receivedDate'.calculate(async wo => {
  let rec = await wo.toLastWOReceipt(); if ( ! rec ) return global.emptyYMD()
  return rec.receivedDate
})

'receivedDate'.visibleWhen((maint, wo) => {
  return wo.receivedDate && (wo.receivedDate !== global.emptyYMD())
})

'WO'.method('toLastWOReceipt', async function () {
  let recs = await 'WOReceipt'.bring({workOrder: this})
  if ( recs.length === 0 ) return null
  recs.sort((r1, r2) => r1.receivedDate > r2.receivedDate ? 1 : -1)
  return recs[recs.length - 1]
})

'WO'.afterCreating(async function () {
  let c = await 'Configuration'.bringFirst(); if ( ! c ) return
  this.includeTaxOption = c.mfgIncTax
})

'View Receipts'.availableWhen(wo => wo && wo.status !== "Awaiting Manufacture")

'stage'.options(['Draft', 'Entered', 'Sent to Manufacturing', 'Complete'])

'WO'.method("hasLines", async function () {
  let lines = await 'WOLine'.bringChildrenOf(this)
  return lines.length > 0
})

'orderDate'.afterUserChange(async (oldInputValue, newInputValue, wo) => {
  await wo.refreshExpectedDeliveryDate()
  await wo.refreshExpiryDate()
})

'WO'.method('getExpectedDaysToManufacture', async function() {
  let bundle = await this.toBundle(); if ( ! bundle ) return 0
  return bundle.expectedDaysToManufacture
})

'WO'.method('refreshExpectedDeliveryDate', async function() {
  if ( ! global.ymdIsSet(this.orderDate) ) return
  let days = await this.getExpectedDaysToManufacture()
  this.expectedCompletionDate = this.orderDate.incDays(days)
})

'WO'.method('refreshExpiryDate', async function() {
  if ( ! global.ymdIsSet(this.expectedCompletionDate) ) return
  let inv = await this.toInventory(); if ( ! inv ) return
  let days = inv.defaultExpiryDays; if ( ! days ) return
  let newDate = this.expectedCompletionDate.incDays(days)
  if ( this.expiryDate !== newDate ) {
    this.expiryDate = newDate
    await this.updateLotExpiryDates()
  }
})

'Generate Lines'.act(async (maint, wo) => {

  let getDefaultTaxPct = async () => {
    let config = await 'Configuration'.bringSingle(); if ( ! config ) return 0
    return config.taxPct
  }

  let createLines = async () => {
    let components = await bundle.toComponents()
    for ( var i = 0; i < components.length; i++ ) {
      let component = components[i]
      let inv = await component.toInventory(); if ( ! inv ) continue
      let woLine = await maint.addChildCast()
      woLine.product = inv.product
      woLine.description = inv.productName
      woLine.taxPct = await getDefaultTaxPct()
      let cost = inv.avgUnitCost
      if ( cost === global.unknownNumber() )
        cost = 0
      woLine.unitCostIncTax = cost
      woLine.quantity = component.quantity * wo.fgQuantity
      woLine.location = await inv.getDefaultLocationRef()
      woLine.component = component.reference()
      woLine.refreshIndexes()
      await woLine.refreshCalculations({force: true, includeDefers: true})
      await woLine.allot()
      await woLine.refreshUnspecifiedLot()
    }
  }

  let enoughComponentStock = async resObj => {
    let components = await bundle.toComponents()
    for ( var i = 0; i < components.length; i++ ) {
      let component = components[i]
      let product = await component.toProduct(); if ( ! product ) continue
      let inv = await component.toInventory(); if ( ! inv ) continue
      let reqQty = component.quantity * wo.fgQuantity
      if ( (inv.quantityOnHand + inv.quantityMakeable) < reqQty ) {
        resObj.product = product
        return false
      }
    }
    return true
  }

  if ( wo.linesGenerated === 'Yes' ) {
    maint.showMessage("Lines have already been generated")
    return
  }
  if ( ! wo.fgQuantity ) {
    maint.showMessage("Please enter a quantity before generating lines")
    return
  }
  let product = await wo.referee('product')
  if ( ! product ) {
    maint.showMessage("Please choose a product before generating lines")
    return
  }
  let bundle = await product.toBundle()
  if ( ! bundle ) {
    maint.showMessage("This product does not have a Bundle")
    return
  }
  if ( bundle.manageManufacturingWithWorkOrders !== 'Yes' ) {
    maint.showMessage("The product bundle doesn't have 'Manage Manufacturing With Work Orders' set")
    return
  }
  if ( global.confVal('pns') === 'Yes') {
    let resObj = {}
    if ( ! await enoughComponentStock(resObj) ) {
      maint.showMessage('Not enough quantity on hand of'.translate() + ' ' + resObj.product.uniqueName)
      return
    }
  }

  await createLines()
  wo.linesGenerated = 'Yes'
  await wo.refreshStatus()

})

'overdueDays'.calculate(async (wo) => {
  return global.todayYMD().dateSubtract(wo.expectedCompletionDate)
})

'compCostPerFGUnitIncTax'.calculate(async (wo) => {
  let lines = await 'WOLine'.bringChildrenOf(wo)
  let res = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    res += line.costPerFGUnitIncTax
  }
  return res
})

'compCostPerFGUnitExclTax'.calculate(async (wo) => {
  let res = global.roundToXDecimals(wo.compCostPerFGUnitIncTax / ( 1 + (wo.taxPct / 100)), 6)
  return res
})

'costIncTax'.calculate(async (wo) => {
  let lines = await 'WOLine'.bringChildrenOf(wo)
  let res = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    res += line.lineCostIncTax
  }
  return wo.overheadCostIncTax + res
})

'costExclTax'.calculate(async (wo) => {
  let res = global.roundToXDecimals(wo.costIncTax / ( 1 + (wo.taxPct / 100)), 6)
  return res
})

'tax'.calculate(async (wo) => {
  let lines = await 'WOLine'.bringChildrenOf(wo)
  let res = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    let lineTax = await line.getLineTaxUnrounded()
    res += lineTax
  }
  return res
})

'WOMaint'.whenAdding(async function() {

  let defaultNumber = async () => {
    let res
    while ( true ) {
      let nextNo = await 'NextNumber'.bringOrCreate({forDatatype: 'WO'})
      nextNo.number = nextNo.number + 1
      let noStr = nextNo.number + ""
      res = "WO" + noStr.padStart(5, '0')
      let wo = await 'WO'.bringFirst({workOrderNumber: res})
      if ( ! wo )
        break
    }
    this.setFieldValue('workOrderNumber', res)
  }

  await defaultNumber()

})

'status'.inception('New')

'fgLocation'.inception(async po => {
  let loc = await 'Location'.bringSingle({locationName: 'General'})
  return loc.reference()
})

'WO'.method('refreshStatus', async function() {
  let lines = await 'WOLine'.bringChildrenOf(this)
  if ( lines.length === 0 ) {
    this.status = 'New'
  } else if ( (Math.abs(this.fgQuantity) > 0) && Math.abs(this.receivedQuantity + this.cancelledQuantity) >= Math.abs(this.fgQuantity) ) {
    this.status = 'Completed'
  } else if ( this.receivedQuantity === 0 ) {
    this.status = 'Awaiting Manufacture'
  } else if ( Math.abs(this.receivedQuantity + this.cancelledQuantity) < Math.abs(this.fgQuantity) ) {
    this.status = 'Partially Completed'
  } else {
    this.status = 'Completed'
  }
  await this.refreshClumps()
})

'WO'.beforeSaving(async function() {
  let lines = await 'WOLine'.bringChildrenOf(this)
  if ( (lines.length > 0) && this.fgLocation && this.old && this.old.fgLocation && this.propChanged('fgLocation') ) {
    throw(new Error("You cannot change the location on a work order that has had lines generated".translate()))
  }
  await this.refreshQuantityBeingManufactured()
})

'WO'.method('refreshQuantityBeingManufactured', async function() {
  let inv = await this.toInventory(); if ( ! inv ) return
  let cluster = await this.toCluster(); if ( ! cluster ) return
  await cluster.refreshQuantityBeingManufactured()
  await inv.refreshQuantityBeingManufactured({refreshClusters: true})
})

'Download PDF'.act(async (maint, po) => {
  maint.downloadPDF({spec: "WOPdf.js", docName: po.workOrderNumber + ".PDF"})
})

'WO'.method('toLocation', async function() {
  let res = await this.referee('fgLocation')
  return res
})

'WO'.method('toLocationName', async function() {
  let loc = await this.toLocation(); if ( ! loc ) return 'General'
  let res = loc.locationName
  if ( ! res )
    res = 'General'
  return res
})

'WOMaint'.makeDestinationFor('WO')

'lineCostExclTax'.columnVisibleWhen((maint, wo) => {
  return wo.includeTaxOption === "No"
})

'lineCostIncTax'.columnVisibleWhen((maint, wo) => {
  return wo.includeTaxOption !== "No"
})

'unitCostExclTax'.columnVisibleWhen((maint, wo) => {
  return wo.includeTaxOption === "No"
})

'unitCostIncTax'.columnVisibleWhen((maint, wo) => {
  return wo.includeTaxOption !== "No"
})

'fgQuantity'.afterUserChange(async (oldInputValue, newInputValue, wo, maint) => {
  await wo.refreshOverhead()
  await wo.refreshUnspecifiedLot()
})

'fgQuantity'.readOnlyWhen((maint, wo) => wo.linesGenerated === 'Yes')


