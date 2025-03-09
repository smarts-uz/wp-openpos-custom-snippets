'WOReceiptMaint'.maint({panelStyle: "titled", icon: "Boxes"})
'WOReceipt'.datatype()
'Add Work Order Receipt'.title({when: 'adding'})
'Edit Work Order Receipt'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'FG Lots'.action({spec: 'WOReceiptLotsMaint.js', noSave: true})
'Co-products'.action({spec: 'WOReceiptCoproductsMaint.js', noSave: true})
'Edit Order'.action()
'Labels'.action({spec: "Labels.js"})
'Attachments'.action({act: 'attachments'})

'Receipt Details'.panel()
'receiptNumber'.field({key: true})
'workOrder'.field({refersToParent: 'WO', showAsLink: true, indexed: true})
'receivedDate'.field({date: true, caption: 'Completion Date'})
'expiryDate'.field({date: true, allowEmpty: true})
'fgLocation'.field({refersTo: 'Location', readOnly: true, showAsLink: true, caption: 'Location'})

'product'.field({refersTo: 'products', showAsLink: true, readOnly: true})
'orderedQuantity'.field({numeric: true, readOnly: true})
'receivedQuantity'.field({numeric: true})
'previouslyReceived'.field({numeric: true, readOnly: true})
'cancelledQuantity'.field({numeric: true})
'outstandingQuantity'.field({numeric: true, readOnly: true})
'hasLots'.field({yesOrNo: true, hidden: true})
'lotsAreSerials'.field({yesOrNo: true, hidden: true})
'manuallyAllotted'.field({yesOrNo: true, hidden: true})

'Product Image'.panel()
'image'.field({postImage: true, postImageType: 'full', postIdField: 'product'})
'thumbnailImage'.field({postImage: true, postImageType: 'thumbnail', postIdField: 'product', caption: 'Image', hidden: true})

/* eslint-disable no-cond-assign */

'Co-products'.availableWhen(woReceipt => {
  return global.confVal('acp') === 'Yes'
})

'WOReceipt'.method('toBundle', async function() {
  let product = await this.toProduct(); if ( ! product ) return null
  return await product.toBundle()
})

'WOReceipt'.method('updateLotExpiryDates', async function() {
  let a; let as = await this.toAllotments()
  while ( a = as.__() ) {
    a.expiryDate = this.expiryDate
  }
})

'expiryDate'.visibleWhen((maint, woReceipt) => {
  return woReceipt.hasLots === 'Yes'
})

'receivedDate'.afterUserChange(async (oldInputValue, newInputValue, woReceipt, maint) => {
  await woReceipt.refreshExpiryDate()
  await woReceipt.updateLotExpiryDates()
  await woReceipt.refreshUnspecifiedLot()
})

'expiryDate'.afterUserChange(async (oldInputValue, newInputValue, woReceipt, maint) => {
  await woReceipt.updateLotExpiryDates()
  await woReceipt.refreshUnspecifiedLot()
})

'expiryDate'.inception(async woReceipt => {
  if ( ! woReceipt ) return
  let wo = await woReceipt.toWO(); if ( ! wo ) return global.emptyYMD()
  return wo.expiryDate
})


'WOReceipt'.method('refreshExpiryDate', async function() {
  if ( ! global.ymdIsSet(this.receivedDate) ) return
  let inv = await this.toInventory(); if ( ! inv ) return
  let days = inv.defaultExpiryDays; if ( ! days ) return
  let newDate = this.receivedDate.incDays(days)
  if ( this.expiryDate !== newDate ) {
    this.expiryDate = newDate
    await this.updateLotExpiryDates()
  }
})

'WOReceipt'.afterCreating(async function() {
  // Stub for sites to call enhance on
})

'WOReceipt'.method('involvesProduct', async function(product) {
  if ( this.product && (this.product.id === product.id) ) 
    return true
  let line; let lines = await this.toWOLines()
  while ( line = lines.__() ) {
    if ( line.product && (line.product.id === product.id) ) 
      return true
  }
  return false
})

'WOReceipt'.method('toProduct', async function() {
  return await this.referee('product')
})

'WOReceipt'.method('toWOLines', async function() {
  let wo = await this.toWO(); if ( ! wo ) return []
  return await wo.toWOLines()
})

'Edit Order'.act(async (maint, woReceipt) => {
  let wo = await woReceipt.toWO(); if ( ! wo ) return
  await maint.segue('edit', 'WOMaint.js', wo)
})

'WOReceipt'.method('thereAreLinesWithLots', async function() {
  let wo = await this.toWO(); if ( ! wo ) return false
  return await wo.thereAreLinesWithLots()
})

'WOReceipt'.method('checkLotQuantities', async function() {
  let qty = 0
  let allotments = await 'Allotment'.bringChildrenOf(this)
  for ( var i = 0; i < allotments.length; i++ ) {
    let a = allotments[i]
    qty += a.quantity
  }
  if ( qty !== this.receivedQuantity ) {
    let msg
    if ( this.lotsAreSerials === 'Yes' )
      msg = 'Quantity of Serial Numbers'.translate() + ' (' + qty + ') ' + 
        'does not match the quantity received'.translate() + ' (' + this.receivedQuantity + ')'
    else
      msg = 'Lot quantity total'.translate() + ' (' + qty + ') ' + 
        'does not match the quantity received'.translate() + ' (' + this.receivedQuantity + ')'
    throw(new Error(msg))
  }
})

'receivedQuantity'.afterUserChange(async (oldInputValue, newInputValue, woReceipt, maint) => {
  if ( newInputValue === (oldInputValue + '') ) return
  let wo = await woReceipt.toWO(); if ( ! wo ) return
  if ( await woReceipt.thereAreLinesWithLots() ) {
    woReceipt.cancelledQuantity = wo.fgQuantity - woReceipt.receivedQuantity
    //return "Qty cannot be changed as lines have lots. " + 
      //"Edit original WO instead, and create a new WO for the difference."
  }
  await woReceipt.refreshUnspecifiedLot()
  await wo.refreshReceived()
  await wo.refreshStatus()
})

'cancelledQuantity'.afterUserChange(async (oldInputValue, newInputValue, woReceipt, maint) => {
  if ( newInputValue === (oldInputValue + '') ) return
  let wo = await woReceipt.toWO(); if ( ! wo ) return
  if ( await woReceipt.thereAreLinesWithLots() ) {
    woReceipt.receivedQuantity = wo.fgQuantity - woReceipt.cancelledQuantity
    //return "Qty cannot be changed as lines have lots. " + 
      //"Edit original WO instead."
  }
  await woReceipt.refreshUnspecifiedLot()
  await wo.refreshReceived()
  await wo.refreshStatus()
})

'WOReceipt'.method('toLocation', async function() {
  return await this.referee('fgLocation')
})

'WOReceipt'.method('toCluster', async function() {
  let inv = await this.toInventory(); if ( ! inv ) return null
  let res = await inv.locationRefToCluster(this.fgLocation)
  return res
})

'WOReceipt'.method('toMainQuantity', async function() {
  return this.receivedQuantity
})

'WOReceipt'.method('refreshUnspecifiedLot', async function() {
  if ( this.hasLots !== 'Yes' ) return
  let c = await 'Configuration'.bringFirst(); if ( ! c ) return
  await c.balanceAllotments(this)
})

'FG Lots'.availableWhen(woReceipt => {
  if ( ! woReceipt ) return false
  return woReceipt.hasLots === 'Yes'
})

'hasLots'.calculate(async woReceipt => {
  let inv = await woReceipt.toInventory(); if ( ! inv ) return 'No'
  let bres = inv.lotTracking && (inv.lotTracking !== 'None')
  let res = bres ? 'Yes' : 'No'
  return res
})

'lotsAreSerials'.calculate(async woReceipt => {
  let inv = await woReceipt.toInventory(); if ( ! inv ) return 'No'
  let bres = inv.lotTracking === 'Serial'
  let res = bres ? 'Yes' : 'No'
  return res
})

'WOReceipt'.method('toAllotments', async function() {
  return await 'Allotment'.bringChildrenOf(this)
})

'WOReceipt'.method('createAllotments', async function() {
  let wo = await this.toWO(); if ( ! wo ) return
  if ( wo.hasLots !== 'Yes' ) return
  this.manuallyAllotted = wo.manuallyAllotted
  let allotments = await 'Allotment'.bringChildrenOf(wo)
  for ( var i = 0; i < allotments.length; i++ ) {
    let woAllotment = allotments[i]
    let quantity = await wo.allotmentToOutstandingQuantity(woAllotment)
    if ( quantity <= 0 )
      continue
    let thisRef = this.reference()
    let a = await 'Allotment'.create({parentCast: this}, {allotmentParent: thisRef})
    let lot = await woAllotment.toLot(); if ( ! lot ) continue
    a.lot = lot.reference()
    a.quantity = quantity
    a.expiryDate = woAllotment.expiryDate
    woAllotment.refreshIndexes()
  }
  await this.refreshUnspecifiedLot()
})

'outstandingQuantity'.calculate(async (woReceipt) => {
  let res = woReceipt.orderedQuantity - woReceipt.previouslyReceived - woReceipt.receivedQuantity - woReceipt.cancelledQuantity
  if ( res < 0 )
    res = 0
  return res
})

'previouslyReceived'.calculate(async (woReceipt) => {
  let res = 0
  let otherRecs = await 'WOReceipt'.bring({workOrder: woReceipt.workOrder}, {useIndexedField: 'workOrder', forceFast: true})
  if ( otherRecs === 'na' )
    otherRecs = await 'WOReceipt'.bring({workOrder: woReceipt.workOrder}, {useIndexedField: 'workOrder'})
  for ( var i = 0; i < otherRecs.length; i++ ) {
    let rl = otherRecs[i]
    if ( rl.id === woReceipt.id ) continue
    res = res + rl.receivedQuantity
  }
  return res
})

'product'.calculate(async woReceipt => {
  let wo = await woReceipt.referee('workOrder'); if ( ! wo ) return
  return wo.product
})

'fgLocation'.calculate(async woReceipt => {
  let wo = await woReceipt.referee('workOrder'); if ( ! wo ) return
  return wo.fgLocation
})

'orderedQuantity'.calculate(async woReceipt => {
  let wo = await woReceipt.referee('workOrder'); if ( ! wo ) return
  return wo.fgQuantity
})

'WOReceipt'.method('createTransactionForAllotment', async function(a) {
  let wo = await this.toWO(); if ( ! wo ) return
  let chg = a.quantity - a.getOld().quantity
  if ( chg === 0 )
    return
  let tran = await 'Transaction'.create()
  let newExtCost = a.quantity * wo.fgUnitCostIncTax
  let oldExtCost = a.getOld().quantity * wo.fgUnitCostIncTax
  let extCostChg = newExtCost - oldExtCost
  let receipt = this
  tran.product = a.product ? a.product : this.product
  if ( a._markedForDeletion )
    tran.product = a.getOld().product
  tran.date = receipt.receivedDate
  tran.quantity = chg
  tran.unitCost = (chg === 0) ? 0 : extCostChg / chg
  tran.source = 'WO Receipt'
  tran.reference = wo.workOrderNumber
  tran.location = global.copyObj(wo.fgLocation)
  tran.lot = global.copyObj(a.lot)
  tran.taxPct = this.taxPct
  a.quantityUpdatedToOnHand += chg
})

'WOReceipt'.method('toCoproducts', async function() {
  return await 'Coproduct'.bringChildrenOf(this)
})

'WOReceipt'.method('createTransactions', async function(chg) {

  let createCoproductTransaction = async coproduct => {
    let cochg = coproduct.quantity - coproduct.getOld().quantity
    if ( ! cochg )
      return
    let product = await coproduct.toProduct(); if ( ! product ) return
    let tran = await 'Transaction'.create()
    tran.product = global.copyObj(coproduct.product)
    tran.date = this.receivedDate
    tran.quantity = cochg
    tran.unitCost = await product.toAvgUnitCost()
    tran.source = 'WO Receipt'
    tran.reference = wo.workOrderNumber
    tran.location = global.copyObj(wo.fgLocation)
    tran.taxPct = wo.taxPct
  }

  let createCoproductTransactions = async () => {
    let coproduct; let coproducts = await this.toCoproducts()
    while ( coproduct = coproducts.__() ) {
      await createCoproductTransaction(coproduct)
    }
  }

  let createFGTransaction = async () => {
    let tran = await 'Transaction'.create()
    tran.product = global.copyObj(this.product)
    tran.date = this.receivedDate
    tran.quantity = chg
    tran.unitCost = wo.fgUnitCostIncTax
    tran.source = 'WO Receipt'
    tran.reference = wo.workOrderNumber
    tran.location = global.copyObj(wo.fgLocation)
    tran.taxPct = wo.taxPct
  }

  let createRMTransaction = async woLine => {
    let tran = await 'Transaction'.create()
    let consumedQuantity = woLine.quantity * (chg / wo.fgQuantity)
    tran.product = global.copyObj(woLine.product)
    tran.date = this.receivedDate
    tran.quantity = - consumedQuantity
    tran.unitCost = woLine.unitCostIncTax
    tran.source = 'WO Consumed'
    tran.reference = wo.workOrderNumber
    tran.location = global.copyObj(woLine.location)
    tran.taxPct = wo.taxPct
  }

  let createRMTransactionForAllotment = async (allotment, woLine) => {
    let tran = await 'Transaction'.create()
    let consumedQuantity = allotment.quantity
    if ( chg < 0 )
      consumedQuantity = - allotment.quantity
    tran.product = global.copyObj(woLine.product)
    tran.date = this.receivedDate
    tran.quantity = - consumedQuantity
    tran.unitCost = woLine.unitCostIncTax
    tran.source = 'WO Consumed'
    tran.reference = wo.workOrderNumber
    tran.location = global.copyObj(woLine.location)
    tran.lot = global.copyObj(allotment.lot)
    tran.taxPct = wo.taxPct
  }

  let createRMTransactionsForAllotments = async woLine => {
    let allotments = await woLine.toAllotments()
    for ( var i = 0; i < allotments.length; i++ ) {
      let allotment = allotments[i]
      await createRMTransactionForAllotment(allotment, woLine)
    }
  }

  let wo = await this.referee('workOrder'); if ( ! wo ) return
  if ( this.hasLots !== 'Yes' ) { // otherwise created in beforeSaving of Allotment
    await createFGTransaction()
    await createCoproductTransactions()
  }
  let woLines = await wo.toWOLines()
  for ( var i = 0; i < woLines.length; i++ ) {
    let woLine = woLines[i]
    if ( woLine.hasLots !== 'Yes' )
      await createRMTransaction(woLine)
    else
      await createRMTransactionsForAllotments(woLine)
  }
})

'WOReceipt'.beforeSaving(async function() {

  let checkAllotmentsBalanceOnLine = async line => { 
    let aSum = 0
    let a; let as = await line.toAllotments()
    while ( a = as.__() ) {
      aSum += a.quantity
    }
    if ( aSum !== line.quantity )
      throw(new Error('There are one or more Work Order lines without an assigned lot'.translate()))
  }

  let checkAllotmentsBalanceOnLines = async () => {
    let line; let lines = await this.toWOLines()
    while ( line = lines.__() ) {
      if ( line.hasLots !== 'Yes' ) continue
      await checkAllotmentsBalanceOnLine(line)
    }
  }

  await checkAllotmentsBalanceOnLines()
  if ( (this.hasLots === 'Yes') && this.propChanged('receivedQuantity') ) 
    await this.checkLotQuantities()
  let unreceived = this.orderedQuantity - this.previouslyReceived - this.receivedQuantity
  if ( this.propChanged('cancelledQuantity') ) {
    if ( this.orderedQuantity >= 0 ) {
      if ( unreceived < 0 ) unreceived = 0
      if ( this.cancelledQuantity > unreceived ) throw(new Error('You cannot cancel more than the quantity left on order'))
    } else {
      if ( unreceived > 0 ) unreceived = 0
      if ( this.cancelledQuantity < unreceived ) throw(new Error('You cannot cancel more than the quantity left on order'))
    }
  }
  let wo = await this.referee('workOrder')
  if ( wo ) {
    await wo.refreshReceived()
    await wo.refreshStatus()
  }
  let chg = this.receivedQuantity - this.getOld().receivedQuantity
  if ( chg !== 0 )
    await this.createTransactions(chg)
  let inventory = await this.toInventory()
  await this.refreshQuantitiesOnWOLines()
  if ( inventory ) {
    await inventory.refreshQuantityBeingManufactured({refreshClusters: true})
    await inventory.refreshLastWOReceipt()
  }
})

'WOReceipt'.method('refreshQuantitiesOnWOLines', async function() {
  let wo = await this.toWO(); if ( ! wo ) return
  let lines = await wo.toWOLines()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    let inventory = await line.toInventory(); if ( ! inventory ) continue
    await inventory.refreshQuantityOnWOLines({refreshClusters: true, refreshClumps: true})
  }
})

'WOReceipt'.method('toInventory', async function() {
  let product = await this.referee('product'); if ( ! product ) return null
  return await product.toInventory()
})

'WOReceiptMaint'.makeDestinationFor('WOReceipt')

'WOReceipt'.method('toWO', async function(options) {
  return await this.referee('workOrder', options)
})

'WOReceiptMaint'.whenAdding(async function(woReceipt, maint) {

  let defaultReceiptNumber = async () => {
    let recToSuffix = (aRec) => {
      let no = aRec.receiptNumber
      let parts = no.split('-')
      if ( parts.length < 2 ) return "00"
      return parts[parts.length - 1]
    }
  
    let incSuffix = (aSuffix) => {
      let no = Number(aSuffix) + 1 + ""
      return no.padStart(2, '0')
    }

    let recsToOneWithMaxReceiptNumber = (aRecs) => {
      let res
      let max = ''
      aRecs.forAll(rec => {
        let no = rec.receiptNumber; if ( ! no ) return 'continue'
        if ( no <= max ) return 'continue'
        max = no
        res = rec
      })
      return res
    }
  
    let woNo = this.fieldNameToKeyValue('workOrder')
    let woRef = this.getFieldValue('workOrder')
    let recs = await 'WOReceipt'.bring({workOrder: woRef})
    let rec = recsToOneWithMaxReceiptNumber(recs)
    let suffix = "01"
    if ( rec ) {
      suffix = recToSuffix(rec)
      suffix = incSuffix(suffix)
    }
    let recNo = woNo + "-" + suffix
    this.setFieldValue('receiptNumber', recNo)
  }

  await defaultReceiptNumber()
  woReceipt.receivedQuantity = woReceipt.outstandingQuantity
  await woReceipt.createAllotments()
})

