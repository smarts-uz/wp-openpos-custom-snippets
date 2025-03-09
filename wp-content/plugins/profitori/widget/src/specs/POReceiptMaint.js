'POReceiptMaint'.maint({panelStyle: "titled", icon: "Boxes"})
'POReceipt'.datatype()
'Add Purchase Order Receipt'.title({when: 'adding'})
'Edit Purchase Order Receipt'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Zero All'.action()
'Fill All'.action()
'Scan Products In'.action()
'Labels'.action({spec: "Labels.js"})
'Delivery Note'.action()
'Attachments'.action({act: 'attachments'})

'Receipt Details'.panel()
'scanStatus'.field({ephemeral: true, readOnly: true})
'inScanMode'.field({yesOrNo: true, hidden: true})
'receiptNumber'.field({key: true})
'purchaseOrder'.field({refersToParent: 'PO', showAsLink: true})
'receivedDate'.field({date: true})
'supplier'.field({refersTo: 'Supplier', readOnly: true, showAsLink: true})
'currency'.field({refersTo: 'Currency', readOnly: true, showAsLink: true})
'exchangeRate'.field({numeric: true, minDecimals: 4})
'location'.field({refersTo: 'Location', readOnly: true, showAsLink: true})
'costIncTaxFX'.field({numeric: true, decimals: 2, hidden: true})
'costExclTaxFX'.field({numeric: true, decimals: 2, hidden: true})
'costIncTaxFXWithCurrency'.field({caption: "Order Amount (Inc Tax)", hidden: true})
'includeTaxOption'.field({yesOrNo: true, hidden: true})
'journaledShippingAmount'.field({numeric: true, decimals: 2, hidden: true, preserveOnTrash: true})
'journaledShippingLocation'.field({refersTo: 'Location', hidden: true})
'journaledFeesAmount'.field({numeric: true, decimals: 2, hidden: true, preserveOnTrash: true})
'journaledFeesLocation'.field({refersTo: 'Location', hidden: true})
'journaledTaxAmount'.field({numeric: true, decimals: 2, hidden: true, preserveOnTrash: true})
'journaledTaxLocation'.field({refersTo: 'Location', hidden: true})
'journaledDate'.field({date: true, allowEmpty: true, hidden: true})
'tax'.field({numeric: true, decimals: 2, hidden: true})
'containsBundles'.field({yesOrNo: true, hidden: true})

'Carrier'.panel()
'carrier'.field()
'trackingNumber'.field({caption: "Tracking#"})

'Lines'.manifest()
'POReceiptLine'.datatype()
'thumbnailImage'.field()
'descriptionAndSKU'.field({showAsLink: true})
'sku'.field()
'lineExpectedDeliveryDate'.field()
'supplierSku'.field()
'uomDisplay'.field({readOnly: true, caption: 'UOM'})
'orderedQuantityPU'.field({numeric: true, caption: 'Ordered'})
'previouslyReceivedPU'.field({numeric: true, caption: 'Previously Received'})
'receivedQuantityPU'.field({numeric: true, readOnly: false, caption: 'Received'})
'cancelledQuantityPU'.field({numeric: true, readOnly: false, caption: 'Cancelled'})
'receivedQuantity'.field({numeric: true, caption: 'Received Stock Units'})
'unitCostFX'.field({readOnly: false})
'unitCostExclTaxFX'.field({readOnly: false})
'wooCommerceRegularPrice'.field({caption: "WC Reg Price", readOnly: false})
'newQuantityOnHand'.field()
'unbundle'.field({readOnly: false})
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'POReceiptLineMaint.js'.maintSpecname()

/* eslint-disable no-cond-assign */

'POReceipt'.method('toTotalCostInShippingLines', async function() {
  let line; let lines = await 'POReceiptLine'.bringChildrenOf(this)
  let res = 0
  while ( line = lines.__() ) {
    if ( line.lineType !== 'Shipping' ) continue
    res += line.unitCost * line.receivedQuantity
  }
  return res
})

'POReceipt'.method('toTotalProductReceivedQuantity', async function() {
  let line; let lines = await 'POReceiptLine'.bringChildrenOf(this)
  let res = 0
  while ( line = lines.__() ) {
    if ( line.lineType !== 'Product' ) continue
    res += line.receivedQuantity
  }
  return res
})

'POReceipt'.method('refreshAllShippingUnitCosts', async function() {
  if ( global.confVal('asc') !== 'Yes' ) return
  let line; let lines = await 'POReceiptLine'.bringChildrenOf(this)
  let shippingCost = await this.toTotalCostInShippingLines()
  let totalQuantity = await this.toTotalProductReceivedQuantity(); if ( ! totalQuantity ) return
  while ( line = lines.__() ) {
    if ( line.lineType !== 'Product' ) continue
    let shippingUnitCost = shippingCost / totalQuantity
    line.shippingUnitCost = global.roundToXDecimals(shippingUnitCost, 6)
  }
})

'containsBundles'.calculate(async rec => { 
  let line; let lines = await 'POReceiptLine'.bringChildrenOf(rec)
  while ( line = lines.__() ) {
    if ( line.isBundle === 'Yes' )
      return 'Yes'
  }
  return 'No'
})

'unbundle'.columnVisibleWhen((grid, poReceipt) => {
  if ( global.confVal('eub') !== 'Yes' ) return false
  return (poReceipt.containsBundles === 'Yes')
})

'POReceipt'.method('toLocation', async function() {
  let res = await this.referee('location')
  return res
})

'tax'.calculate(async (rec) => {
  let lines = await 'POReceiptLine'.bringChildrenOf(rec)
  let res = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    let lineTax = await line.getLineTaxUnrounded()
    res += lineTax
  }
  return res
})

'POReceipt'.method('toNonProductTax', async function() {
  let rec = this
  let lines = await 'POReceiptLine'.bringChildrenOf(rec)
  let res = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    if ( (! line.lineType) || (line.lineType === 'Product') ) continue
    let lineTax = await line.getLineTaxUnrounded()
    res += lineTax
  }
  return res
})

'lineExpectedDeliveryDate'.columnVisibleWhen((grid, poReceipt) => {
  let po = poReceipt.refereeFast('purchaseOrder', {noCalc: true})
  if ( (! po) || (po === 'na') )
    return false
  return po.hasSeparateDeliveryLines === 'Yes'
})

'exchangeRate'.afterUserChange(async (oldInputValue, newInputValue, receipt) => {
  let po = await receipt.toPO()
  po.exchangeRate = receipt.exchangeRate
})

'Fill All'.availableWhen(rec => {
  return global.confVal('startPOReceiptsAsZero') === 'Yes'
})

'Fill All'.act(async (maint, rec) => {
  let lines = await 'POReceiptLine'.bringChildrenOf(rec)
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    let poLine = await line.toPOLine(); if ( ! poLine ) continue
    let outstanding = poLine.quantity - line.previouslyReceived - poLine.cancelledQuantity
    line.receivedQuantityPU = outstanding
    await line.refreshReceivedQuantity()
  }
})

'Zero All'.availableWhen(rec => {
  return global.confVal('startPOReceiptsAsZero') !== 'Yes'
})

'Zero All'.act(async (maint, rec) => {
  let lines = await 'POReceiptLine'.bringChildrenOf(rec)
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    line.receivedQuantityPU = 0
    await line.refreshReceivedQuantity()
  }
})

'receivedQuantity'.columnVisibleWhen((maint, rec) => {
  let c = 'Configuration'.bringSingleFast(); if ( ! c ) return false
  let res = c.showUOMOnPOLines === 'Yes'
  return res
})

'Delivery Note'.act(async (maint, rec) => {
  maint.downloadPDF({spec: "DeliveryNote.js", docName: rec.receiptNumber + ".PDF"})
})

'includeTaxOption'.calculate(async rec => {
  let c = await 'Configuration'.bringFirst(); if ( ! c ) return
  return c.enterPurchasePricesInclusiveOfTax
})

'unitCostExclTaxFX'.columnVisibleWhen((maint, rec) => {
  return rec.includeTaxOption === "No"
})

'unitCostFX'.columnVisibleWhen((maint, rec) => {
  return rec.includeTaxOption !== "No"
})

'costIncTaxFX'.calculate(async (rec) => {
  let lines = await 'POReceiptLine'.bringChildrenOf(rec)
  let res = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    let lineCostIncTax = await line.calcLineCostIncTaxFX()
    res += lineCostIncTax
  }
  return res
})

'costExclTaxFX'.calculate(async (rec) => {
  let lines = await 'POReceiptLine'.bringChildrenOf(rec)
  let res = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    let lineCostExclTax = await line.calcLineCostExclTaxFX()
    res += lineCostExclTax
  }
  return res
})

'costIncTaxFXWithCurrency'.calculate(async rec => {
  let currency = rec.currency ? (rec.currency.keyval + ' ') : ''
  let res = currency + global.numToStringWithXDecimals(rec.costIncTaxFX, 2)
  return res
})

'POReceipt'.method('toSupplier', async function() {
  let po = await this.toPO(); if ( ! po ) return null
  return await po.toSupplier()
})

'POReceipt'.method('barcodeToLine', async function(barcode) {
  let lines = await 'POReceiptLine'.bringChildrenOf(this)
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    let inv = await line.toInventory(); if ( ! inv ) continue
    if ( await inv.matchesBarcode(barcode) ) {
      return line
    }
  }
})

'POReceiptMaint'.onScannerRead(async (maint, poReceipt, barcode) => {
  let line = await poReceipt.barcodeToLine(barcode)
  if ( ! line ) {
    poReceipt.scanStatus = 'Scanned code unrecognized: ' + barcode
    return
  }
  line.receivedQuantityPU++
  await line.refreshReceivedQuantity()
  await line.refreshCalculations({force: true, includeDefers: true})
  let product = await line.toProduct()
  poReceipt.scanStatus = 'Received ' + line.receivedQuantityPU + ' of ' + product.uniqueName
  line.scanStatus = poReceipt.scanStatus
  await maint.segue("edit", 'POReceiptLineMaint.js', line)
})

'POReceiptMaint'.afterInitialising(async poReceipt => {
  global.prfiInScanMode = (poReceipt.inScanMode === "Yes")
})

'scanStatus'.visibleWhen((maint, poReceipt) => {
  return global.prfiInScanMode
})

'Scan Products In'.act(async (maint, poReceipt) => {
  
  let zeroAllReceiptLines = async () => {
    let lines = await 'POReceiptLine'.bringChildrenOf(poReceipt)
    for ( var i = 0; i < lines.length; i++ ) {
      let line = lines[i]
      line.receivedQuantity = 0
      line.receivedQuantityPU = 0
    }
  }

  if ( poReceipt.inScanMode === "Yes" ) {
    poReceipt.inScanMode = 'No'
    global.prfiInScanMode = false
    return
  }
  poReceipt.inScanMode = 'Yes'
  global.prfiInScanMode = true
  poReceipt.scanStatus = 'Ready for you to start scanning'
  await zeroAllReceiptLines()
})

'descriptionAndSKU'.destination(async line => {
  let poLine = await line.toPOLine(); if ( ! poLine ) return null
  if ( poLine.lineType === 'Product' )
    return await poLine.toProduct()
  return line
})

'POReceiptMaint'.makeDestinationFor('POReceipt')

'supplier'.default((maint) => {
  return maint.parentCast().supplier
})

'currency'.calculate(async receipt => {
  let po = await receipt.referee('purchaseOrder', {noCalc: true}); if ( ! po ) return null
  return po.currency
})

'currency'.visibleWhen((maint, receipt) => {
  return receipt.currency ? true : false
})

'exchangeRate'.visibleWhen((maint, receipt) => {
  return receipt.currency ? true : false
})

'location'.calculate(async receipt => {
  let po = await receipt.referee('purchaseOrder', {noCalc: true}); if ( ! po ) return null
  return po.location
})

'POReceipt'.method('toPO', async function(options) {
  return await this.referee('purchaseOrder', options)
})

'POReceipt'.afterRetrieving(async function() {
  let po = await this.toPO({noCalc: true}); if ( ! po ) return
  this.exchangeRate = po.exchangeRate
})

'POReceipt'.afterRetrievingFast(async function() {
  let po = this.refereeFast('purchaseOrder', {noCalc: true})
  if ( (! po) || (po === 'na') ) return false
  this.exchangeRate = po.exchangeRate
  return true
})

'POReceipt'.method('toShippingValueIncTax', async function() {
  let lines = await 'POReceiptLine'.bringChildrenOf(this)
  let res = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    if ( line.lineType === 'Shipping' )
      res += await line.calcLineCostIncTax()
  }
  return res
})

'POReceipt'.method('toFeesAndOtherValueIncTax', async function() {
  let lines = await 'POReceiptLine'.bringChildrenOf(this)
  let res = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    if ( (line.lineType === 'Fee') || (line.lineType === 'Other') )
      res += await line.calcLineCostIncTax()
  }
  return res
})

'POReceipt'.beforeSaving(async function() {
  
  let supplier = await this.toSupplier()

  let getOrCreateEntry = async () => {
    let sourceAbbrev = 'PR'
    let entryNumber = sourceAbbrev + '-' + this.journaledDate
    let receivedDate = this.receivedDate
    if ( ! receivedDate )
      receivedDate = global.todayYMD()
    if ( receivedDate !== this.journaledDate )
      entryNumber += '--' + receivedDate
    let res = await 'Entry'.bringOrCreate({entryNumber: entryNumber})
    res.effectiveDate = this.journaledDate
    res.sourceEffectiveDate = receivedDate
    res.notes = 'Purchase ' + receivedDate.toLocalMMMDY()
    res.enteredDate = global.todayYMD()
    return res
  }

  let updateFeesJournalEntry = async (options) => {
    let entry = await getOrCreateEntry()
    await entry.updatePurchase(options.location, options.amount, 'Fees', supplier)
  }

  let maybeReverseOldFeesEntry = async () => {
    if ( ! this.journaledFeesAmount ) return
    let loc = await this.referee('journaledFeesLocation')
    await updateFeesJournalEntry({location: loc, amount: - this.journaledFeesAmount})
  }

  let updateFeesJournalEntries = async () => {
    await maybeReverseOldFeesEntry()
    let amount = await this.toFeesAndOtherValueIncTax()
    let loc = await this.toLocation()
    await updateFeesJournalEntry({location: loc, amount: amount})
    this.journaledFeesAmount = amount
    this.journaledFeesLocation = loc.reference()
  }

  let updateShippingJournalEntry = async (options) => {
    let entry = await getOrCreateEntry()
    await entry.updatePurchase(options.location, options.amount, 'Shipping', supplier)
  }

  let maybeReverseOldShippingEntry = async () => {
    if ( ! this.journaledShippingAmount ) return
    let loc = await this.referee('journaledShippingLocation')
    await updateShippingJournalEntry({location: loc, amount: - this.journaledShippingAmount})
  }

  let updateShippingJournalEntries = async () => {
    await maybeReverseOldShippingEntry()
    let amount = await this.toShippingValueIncTax()
    let loc = await this.toLocation()
    await updateShippingJournalEntry({location: loc, amount: amount})
    this.journaledShippingAmount = amount
    this.journaledShippingLocation = loc.reference()
  }

  let maybeReverseOldTaxEntry = async () => {
    if ( ! this.journaledTaxAmount ) return
    let loc = await this.referee('journaledTaxLocation')
    await updateTaxJournalEntry({location: loc, amount: - this.journaledTaxAmount})
  }

  let updateTaxJournalEntry = async (options) => {
    let entry = await getOrCreateEntry()
    await entry.updatePurchase(options.location, options.amount, 'Tax', supplier)
  }

  let updateTaxJournalEntries = async () => {
    await maybeReverseOldTaxEntry()
    let amount = await this.toNonProductTax()
    let loc = await this.toLocation()
    await updateTaxJournalEntry({location: loc, amount: amount})
    this.journaledTaxAmount = amount
    this.journaledTaxLocation = loc.reference()
  }

  let updateJournalEntries = async () => {
    let config = await 'Configuration'.bringOrCreate()
    if ( config.glEnabled !== 'Yes' ) return
    this.journaledDate = await config.dateToPostableDate(this.receivedDate)
    // ** NOTE: the product lines are handled through the inventory transaction journal code
    // ** That's why there's no mention of them here.
    await updateTaxJournalEntries()
    await updateShippingJournalEntries()
    await updateFeesJournalEntries()
  }

  let po = await this.toPO(); if ( ! po ) return
  po.exchangeRate = this.exchangeRate
  await updateJournalEntries()
})

'POReceiptMaint'.whenAdding(async function(poReceipt, maint) {

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
  
    let poNo = this.fieldNameToKeyValue('purchaseOrder')
    let po = this.getFieldValue('purchaseOrder')
    let recs = await 'POReceipt'.bring({purchaseOrder: po})
    let rec = recsToOneWithMaxReceiptNumber(recs)
    let suffix = "01"
    if ( rec ) {
      suffix = recToSuffix(rec)
      suffix = incSuffix(suffix)
    }
    let recNo = poNo + "-" + suffix
    this.setFieldValue('receiptNumber', recNo)
  }

  let createLines = async () => {
    let po = this.getFieldValue('purchaseOrder')
    let poLines = await 'POLine'.bring({purchaseOrder: po})
    let leaveAsZero = (global.confVal('startPOReceiptsAsZero') === 'Yes')
    await poLines.forAllAsync(async poLine => {
      let recLine = await this.addChildCast()
      recLine.product = poLine.product
      recLine.poLine = poLine.reference()
      recLine.unitCostFX = poLine.unitCostIncTaxFX
      recLine.unitCostExclTaxFX = poLine.unitCostExclTaxFX
      recLine.uom = global.copyObj(poLine.uom)
      await recLine.refreshDependentFields()
      await recLine.refreshCalculations({force: true, includeDefers: true})
      let outstanding = poLine.quantity - recLine.previouslyReceived - poLine.cancelledQuantity
      if ( ! leaveAsZero )
        recLine.receivedQuantity = outstanding
      if ( outstanding === 0 ) {
        await recLine.trash()
        return 'continue'
      }
      await recLine.refreshReceivedQuantityPU()
      await recLine.defaultUnbundle()
      if ( recLine.hasLots === 'Yes' )
        await recLine.createAllotments()
    })
  }

  let defaultFromPO = async () => {
    let po = await poReceipt.referee('purchaseOrder')
    poReceipt.exchangeRate = po ? po.exchangeRate : 1
    if ( ! poReceipt.exchangeRate )
      poReceipt.exchangeRate = 1
    if ( po ) {
      poReceipt.carrier = po.carrier
      poReceipt.trackingNumber = po.trackingNumber
    }
  }

  await defaultReceiptNumber()
  await defaultFromPO()
  await createLines()
  await poReceipt.refreshExpiryDates()
})

'POReceipt'.method('refreshExpiryDates', async function() {
  let line; let lines = await 'POReceiptLine'.bringChildrenOf(this)
  while ( line = lines.__( ) ) {
    await line.refreshExpiryDate()
  }
})

'receivedDate'.afterUserChange(async (oldInputValue, newInputValue, poReceipt, maint) => {
  await poReceipt.refreshExpiryDates()
})
