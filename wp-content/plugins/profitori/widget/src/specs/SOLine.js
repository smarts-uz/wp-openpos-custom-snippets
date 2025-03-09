'SOLine'.datatype({caption: 'Sales Order Line', alwaysDoAfterRetrieve: true})
'salesOrder'.field({refersToParent: 'SO', parentIsHeader: true})
'order_item_id'.field({numeric: true, indexed: true})
'descriptionAndSKU'.field({caption: 'Description'})
'shipToStateAndCountry'.field()
'shipFromLocation'.field({refersTo: 'Location'})
'packable'.field()
'fulfillStage'.field()
'priority'.field()
'notes'.field({multiLine: true, caption: 'Our Notes'})
'quantityOrdered'.field({numeric: true})
'quantityRemainingToShip'.field({numeric: true})
'quantityPickable'.field({numeric: true})
'quantityPackable'.field({numeric: true})
'quantityToPick'.field({numeric: true})
'quantityToPack'.field({numeric: true})
'quantityShipped'.field({numeric: true})
'quantityToPickEntered'.field({yesOrNo: true})
'quantityAllocated'.field({numeric: true, caption: 'Quantity Allocated for Picking'})
'product'.field({refersTo: 'products', indexed: true})
'sequence'.field({caption: 'Line No'})
'parentSOLine'.field({refersTo: 'SOLine', indexed: true})
'quantityMakeable'.field({numeric: true})
'quantityToMake'.field({numeric: true})
'quantityToMakeEntered'.field({yesOrNo: true})
'quantityAllocatedForMaking'.field({numeric: true})
'productIsBundle'.field({yesOrNo: true})
'hasLots'.field({yesOrNo: true})
'lotsAreSerials'.field({yesOrNo: true})
'journaledAmount'.field({numeric: true, preserveOnTrash: true})
'journaledDate'.field({date: true, allowEmpty: true})
'journaledLocation'.field({refersTo: 'Location'})
'unitPriceIncTax'.field({numeric: true, decimals: 2})
'divvy'.field({numeric: true})
'quantityShippedIncremental'.field({numeric: true, caption: 'This Shipment Qty'})
'lineType'.field()
'description'.field()
'unitPriceExTax'.field({numeric: true, decimals: 2})
'lineTotalExclTax'.field({numeric: true, caption: "Line Total Ex Tax", decimals: 2})
'lineTax'.field({numeric: true, caption: "Tax", decimals: 2})
'valueIncTax'.field({numeric: true, decimals: 2})
'image'.field({postImage: true, postImageType: 'full', postIdField: 'product'})
'taxPct'.field({numeric: true, decimals: 2, caption: "Tax %"})
'customerId'.field({numeric: true})
'impost'.field({refersTo: 'Impost'})
'originatingSOLine'.field({refersTo: 'SOLine'})
'sku'.field({caption: 'SKU'})
'thumbnailImage'.field({postImage: true, postImageType: 'thumbnail', postIdField: 'product', caption: 'Image'})
'shippingMethod'.field({refersTo: 'shipping_methods', allowEmpty: true})
'manuallyAllotted'.field({yesOrNo: true})
'splitFromSOLine'.field({refersTo: 'SOLine'})
'allowNegativeUnspecified'.field({yesOrNo: true})
'applyDiscount'.field({yesOrNo: true})
'discount'.field({refersTo: 'Discount'})
'unitPriceBDExTax'.field({numeric: true, decimals: 2, caption: 'Unit Price Before Discount (Excl Tax)'})
'lineTotalBDExclTax'.field({numeric: true, caption: "Line Total Before Discount Ex Tax", decimals: 2})
'discountType'.field()
'discountPercentage'.field({numeric: true, decimals: 2, caption: 'Discount %'})
'discountAmountPerUnit'.field({numeric: true, decimals: 2})

/* eslint-disable eqeqeq */
/* eslint-disable no-eval */
/* eslint-disable no-cond-assign */

'applyDiscount'.inception('No')

'discountType'.options(['Percentage', 'Amount'])

'discountType'.inception('Percentage')

'unitPriceBDExTax'.calculateWhen(line => {
  return line.applyDiscount !== 'Yes'
})

'unitPriceBDExTax'.calculate(line => {
  return line.unitPriceExTax
})

'unitPriceBDExTax'.afterUserChange(async (oldInputValue, newInputValue, line) => {
  await line.refreshDiscountedUnitPrice()
})

'applyDiscount'.afterUserChange(async (oldInputValue, newInputValue, line) => {
  await line.refreshDiscountedUnitPrice()
})

'discountType'.afterUserChange(async (oldInputValue, newInputValue, line) => {
  await line.refreshDiscountedUnitPrice()
})

'discountPercentage'.afterUserChange(async (oldInputValue, newInputValue, line) => {
  await line.refreshDiscountedUnitPrice()
})

'discountAmountPerUnit'.afterUserChange(async (oldInputValue, newInputValue, line) => {
  await line.refreshDiscountedUnitPrice()
})

'SOLine'.method('refreshDiscountedUnitPrice', async function() {
  this.unitPriceExTax = await this.subtractDiscount(this.unitPriceBDExTax)
})

'SOLine'.method('toCustomer', async function() {
  let so = await this.toSO(); if ( ! so ) return null
  return await so.toCustomer()
})

'SOLine'.method('determineDiscount', async function() {
  let discounts = await 'Discount'.bring({active: 'Yes'})
  if ( discounts.length === 0 ) return null
  let customer = await this.toCustomer(); if ( ! customer ) return null
  let product = await this.toProduct(); if ( ! product ) return null
  let discount = await customer.productToDiscount(product)
  return discount
})

'SOLine'.method('refreshDiscount', async function() {
  let discount = await this.determineDiscount()
  if ( ! discount ) {
    this.discount = null
    this.applyDiscount = 'No'
    return
  }
  this.discount = discount.reference()
  this.applyDiscount = 'Yes'
  this.discountType = discount.discountType
  if ( this.discountType === 'Amount') {
    this.discountAmountPerUnit = this.unitPriceBDExTax - discount.amount
  } else
    this.discountPercentage = discount.percentage
})

'SOLine'.method('lotRefToAllotment', async function(lotRef) {
  if ( ! lotRef ) return null
  let a, as = await this.toAllotments()
  while ( a = as.__() ) {
    if ( a.lot && (a.lot.keyval === lotRef.keyval) )
      return a
  }
  return null
})

'SOLine'.method('allot', async function(opt) {

  let product = await this.toProduct(); if ( ! product ) return
  let rem = this.quantityToPick

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
    await this.refreshClusterQuantityAllocated()
    await this.refreshClusterQuantityReservedForCustomerOrders()
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
    await this.refreshClusterQuantityAllocated()
    await this.refreshClusterQuantityReservedForCustomerOrders()
    //await trashZeroAllotments()
  }
  
  if ( (this.manuallyAllotted !== 'Yes') && (! this.quantityShipped) )
    await doAllot()
  let forReallocation = opt && opt.forReallocation
  let keepIfZero = forReallocation
  await this.refreshUnspecifiedLot({keepIfZero: keepIfZero}) // Keep when zero is so that allotments aren't trashed and recreated all the time
})

'SOLine'.method('refreshClusterQuantityReservedForCustomerOrders', async function() {
  let cluster = await this.toCluster() // Note: creates if not there
  await cluster.refreshQuantityReservedForCustomerOrders()
})

'SOLine'.method('refreshClusterQuantityAllocated', async function() {
  let cluster = await this.toCluster() // Note: creates if not there
  await cluster.refreshQuantityAllocated()
})

/*
'SOLine'.method('refreshClumpsQuantityReservedForCustomerOrders', async function() {
  let cluster = await this.toCluster() // Note: creates if not there
  await cluster.refreshClumpsQuantityReservedForCustomerOrders()
})

'SOLine'.method('refreshClumpsQuantityAllocated', async function() {
  let cluster = await this.toCluster() // Note: creates if not there
  await cluster.refreshClumpsQuantityAllocated()
})
*/

'SOLine'.method('toLatestShipmentLine', async function() {
  let so = await this.toSO(); if ( ! so ) return
  let sl, sls = await this.toShipmentLines()
  while ( sl = sls.__() ) {
    let s = await sl.toShipment(); if ( ! s ) continue
    if ( s.shipmentNumber !== so.latestShipmentNumber ) continue
    return sl
  }
})

'SOLine'.method('refreshShipmentLineLots', async function() {
  if ( this.hasLots !== 'Yes' ) return
  let sl = await this.toLatestShipmentLine(); if ( ! sl ) return
  await sl.copyAllotments(this)
})

'SOLine'.method('refreshOnLotChange', async function(opt) {
  let so = await this.toSO(); if ( ! so ) return
  //await so.refreshShipment() don't do this here as it will split the order if that option is turned on
  await this.refreshShipmentLineLots()
  await this.refreshClusterQuantityReservedForCustomerOrders()
  await this.refreshClusterQuantityAllocated()
  await this.refreshUnspecifiedLot(opt)
})

'SOLine'.method('toAllotments', async function() {
  return await 'Allotment'.bringChildrenOf(this)
})

'SOLine'.method('toInstanceId', async function() {
  return await this.grab('shippingMethod.instance_id')
})

'SOLine'.method('toMethodId', async function() {
  return await this.grab('shippingMethod.shipping_id') // not a typo!
})

'shippingMethod'.afterUserChange(async (oldInputValue, newInputValue, line) => {
  let meth = await line.referee('shippingMethod'); if ( ! meth ) return
  line.unitPriceExTax = meth.cost
  line.unitPriceBDExTax = meth.cost
})

'SOLine'.method('affectsStock', async function() {
  let so = await this.toSO(); if ( ! so ) return true
  return await so.affectsStock()
})

'SOLine'.method('validateEffectOnStock', async function() {
  if ( await this.manageInProfitori() !== 'Yes' ) return
  if ( global.harmonizing ) return
  if ( global.confVal('pns') !== 'Yes' ) return
  let product = await this.toProduct(); if ( ! product ) return
  let so = await this.toSO(); if ( ! so ) return
  if ( ! await this.affectsStock() ) return
  let chg = 0
  if ( await so.newlyAffectsStock() ) {
    chg = - this.quantityOrdered
  } else {
    chg = - (this.quantityOrdered - this.getOld().quantityOrdered)
  }
  if ( ! chg ) return
  if ( chg > 0 ) return
  let inv = await this.toInventory()
  let quantityOnHand = inv ? inv.quantityOnHand : 0
  let quantityMakeable = inv ? inv.quantityMakeable : 0
  let newQuantityOnHand = quantityOnHand + quantityMakeable + chg
  if ( newQuantityOnHand < 0 )
    throw(new Error('Not enough quantity on hand of'.translate() + ' ' + product.uniqueName))
})

'SOLine'.method('validateMargin', async function() {

  if ( await this.manageInProfitori() !== 'Yes' ) return
  if ( global.harmonizing ) return
  if ( global.confVal('pnm') !== 'Yes' ) return
  let sess = await 'session'.bringSingle()
  if ( sess && sess.userHasAdminRights && (! global.autoTesting) ) return
  if ( ! (this.propChanged('quantityOrdered') || this.propChanged('unitPriceExTax')) ) return
  let product = await this.toProduct(); if ( ! product ) return
  let so = await this.toSO(); if ( ! so ) return
  let order = await so.toOrder(); if ( ! order ) return
  let inv = await this.toInventory()
  let price = this.unitPriceExTax
  price = global.roundToXDecimals(price, 2)
  let cost = inv ? inv.avgUnitCostExclTax : 0
  if ( cost === global.unknownNumber() )
    cost = 0
  cost = global.roundToXDecimals(cost, 6)
  let margin = price - cost
  if ( margin < 0 ) 
    throw(new Error('Price is below cost'.translate() + ' ' + 'Order'.translate() + ': ' + order.order_id +
      ' ' + 'Product'.translate() + ': ' + product.uniqueName + ' ' + 'Price'.translate() + ': ' + price +
      ' ' + 'Cost'.translate() + ': ' + cost))
})

'SOLine'.validate(async function() {
  if ( global.harmonizing ) return
  await this.validateEffectOnStock()
  await this.validateMargin()
  if ( this.lineType === 'Shipping' ) {
    if ( await this.manageInProfitori() === 'Yes' ) {
      if ( (this.isNew() || this.propChanged('shippingMethod')) && (! this.shippingMethod) ) {
        throw(new Error('Shipping Method is required'))
      }
    }
  }
})

'SOLine'.method('toCountryCode', async function () {
  let so = await this.toSO(); if ( ! so ) return null
  return global.countryToCode(so.billingCountry)
})

'quantityOrdered'.afterUserChange(async (oldInputValue, newInputValue, line, maint) => {
  await line.maybeGenerateImpostLines()
})

'SOLine'.method('subtractDiscount', async function (amt) {
  let res = amt
  if ( this.applyDiscount !== 'Yes' ) return res
  if ( this.discountType === 'Percentage' ) 
    res = amt * (1 - (this.discountPercentage / 100))
  else 
    res = amt - this.discountAmountPerUnit
  if ( res < 0 ) 
    res = 0
  return res
})

'product'.afterUserChange(async (oldInputValue, newInputValue, line, maint) => {
  await line.refreshDiscount()
  let name = await line.toProductName()
  if ( name )
    maint.setFieldValue('description', name)
  let product = await 'products'.bringFirst({uniqueName: newInputValue})
  if ( product ) {
    let unitPriceBDExTax = await product.toRetailPriceExclTax()
    line.unitPriceBDExTax = unitPriceBDExTax
    await line.refreshDiscount()
    let unitPriceExTax = await line.subtractDiscount(unitPriceBDExTax)
    maint.setFieldValue('unitPriceBDExTax', unitPriceBDExTax)
    maint.setFieldValue('unitPriceExTax', unitPriceExTax)
    let countryCode = await line.toCountryCode()
    let taxPct = await product.toRetailTaxPct({countryCode: countryCode})
    maint.setFieldValue('taxPct', taxPct)
    await line.maybeGenerateImpostLines()
  }
})

'lineType'.afterUserChange(async (oldInputValue, newInputValue, line, maint) => {
  let lineType = line.lineType
  if ( lineType && (lineType !== "Product") ) {
    maint.setFieldValue('description', lineType)
    maint.setFieldValue('quantityOrdered', 1)
  }
})

'SOLine'.afterAnyUserChange((line, maint) => {
  let so = line.toSOFast() 
  if ( (! so) || (so === 'na') ) return
  so.linesVersion++
  so.refreshTotalsFast()
})

'sku'.calculate(line => {
  
  let slow = async () => {
    let p = await line.toProduct(); if ( ! p ) return null
    return p._sku
  }

  let p = line.toProductFast()
  if ( global.fastFail(p) )
    return slow()
  return p._sku
})

'valueIncTax'.calculate(line => {
  return line.lineTotalExclTax + line.lineTax
})

'sequence'.inception(async soLine => {

  let soToLastNormalLine = async so => {
    let lines = await so.getAllLines()
    for ( var i = lines.length - 1; i >= 0; i-- ) {
      let line = lines[i]
      if ( line.sequence && (line.sequence.length === 3) )
        return line
    }
  }

  let so = await soLine.referee('salesOrder'); if ( ! so ) return '001'
  let lastLine = await soToLastNormalLine(so); if ( ! lastLine ) return '001'
  let no = parseFloat(lastLine.sequence, 10)
  return global.padWithZeroes(no + 1, 3)
})


'SOLine'.method('maybeGenerateImpostLines', async function() {

  let so = await this.referee('salesOrder'); if ( ! so ) return

  let removeImpostLines = async () => {
    let lines = await so.getAllLines()
    for ( var i = 0; i < lines.length; i++ ) {
      let line = lines[i]
      if ( line.id === this.id ) continue
      if ( ! line.originatingSOLine ) continue
      if ( line.originatingSOLine.id !== this.id ) continue
      await line.trash()
    }
  }

  let impostAppliesToSOCustomer = async impost => {
    let customer = await so.toCustomer()
    if ( ! customer )
      return true
    let applies = customer[impost.customerMetaFieldName]
    return applies !== 'No'
  }

  let impostToFunction = async (impost, product) => {
    let res 
    try {
      eval('res = ' + impost.script)
    } catch(e) {
      console.log('Error parsing Javascript for impost ' + impost.description + ': ' + e.message)
      return null
    }
    return res.bind(this, product, this.quantityOrdered)
  }

  let createImpostLine = async (impost, value, product, no) => {
    let line = await 'SOLine'.create({parentCast: so}, {salesOrder: so.reference()})
    line.lineType = 'Fee'
    line.description = product.uniqueName + ' - ' + impost.description
    line.quantityOrdered = 1
    line.taxPct = 0
    line.unitPriceExTax = value
    line.unitPriceBDExTax = value
    line.impost = impost.reference()
    line.originatingSOLine = this.reference()
    line.sequence = this.sequence + '.' + global.padWithZeroes(no, 3)
    line.refreshIndexes()
  }

  if ( ! this.product ) return
  if ( (! this.propChanged('quantityOrdered')) && (! this.propChanged('product')) ) return
  await removeImpostLines()
  if ( this._markedForDeletion ) 
    return
  let imposts = await 'Impost'.bring()
  let no = 0
  let product = await this.referee('product'); if ( ! product ) return
  await product.defaultEmptyClassifications()
  for ( var i = 0; i < imposts.length; i++ ) {
    let impost = imposts[i]
    if ( ! await impostAppliesToSOCustomer(impost) ) continue
    let fn = await impostToFunction(impost, product); if ( ! fn ) continue
    let value = fn()
    if ( ! value ) continue
    no++
    await createImpostLine(impost, value, product, no)
  }
})

'customerId'.calculate(async line => {
  let so = line.refereeFast('salesOrder')
  if ( global.fastFail(so) )
    so = await line.referee('salesOrder');
  if ( ! so ) return null
  let customer = so.toCustomerFast()
  if ( global.fastFail(customer) )
    customer = await so.toCustomer();
  if ( ! customer ) return null
  return customer.id
})

'quantityOrdered'.inception('1')

'SOLine'.method('toInventoryFast', function() {
  let product = this.toProductFast(); 
  if ( (! product) || (product === 'na') ) return product
  return product.toInventoryFast()
})

'SOLine'.method('toTaxClass', async function () {
  let p = await this.toProduct(); if ( ! p ) return null
  return p._tax_class
})

'SOLine'.method('manageInProfitori', async function() {
  let so = await this.toSO(); if ( ! so ) return;
  return so.manageInProfitori
})

'lineTax'.calculateWhen(async line => {
  return (await line.manageInProfitori()) === 'Yes'
})

'lineTax'.calculate(line => {
  let rate = line.taxPct / 100
  let res = line.lineTotalExclTax * rate
  res = global.roundTo2Decimals(res)
  return res
})

'taxPct'.inception(async line => {
  let config = await 'Configuration'.bringSingle(); if ( ! config ) return 0
  let res = config.taxPct
  return res
})

'lineTotalExclTax'.calculate(line => {
  let res = global.roundTo2Decimals(line.unitPriceExTax * line.quantityOrdered)
  return res
})

'lineTotalBDExclTax'.calculate(line => {
  let res = global.roundTo2Decimals(line.unitPriceBDExTax * line.quantityOrdered)
  return res
})

'lineType'.options(['Product', 'Shipping', 'Fee'])

'lineType'.inception('Product')

'description'.fallback(async line => {
  let name = await line.toProductName()
  if ( name )
    return name
  return line.lineType
})

'SOLine'.method('toProductName', async function() {
  let p = await this.toProduct(); if ( ! p ) return null
  let res = await p.toName()
  return res
})

'SOLine'.method('refreshDrop', async function() {
  let poLines = await 'POLine'.bring({soLine: this.reference()})
  this.quantityShipped = 0
  for ( var i = 0; i < poLines.length; i++ ) {
    let poLine = poLines[i]
    await poLine.refreshReceived()
    this.quantityShipped += poLine.receivedQuantity
  }
  await this.refreshCalculations({force: true})
})

'SOLine'.method('isActive', async function() {
  let so = await this.toSO(); if ( ! so ) return false
  return await so.isActive()
})

'SOLine'.method('isActiveFast', async function() {
  let so = this.toSOFast()
  if ( (! so) || (so === 'na') )
    return 'na'
  return so.isActiveFast()
})

'SOLine'.method('toUnitPriceExclTax', async function() {
  let order_item = await this.toOrderItem(); if ( ! order_item ) return 0
  if ( ! order_item._qty ) return 0
  return order_item._line_total / order_item._qty
})

'quantityShippedIncremental'.afterUserChange(async (oldInputValue, newInputValue, line) => {
  line.quantityShipped = (await line.toQtyShippedOnOtherShipments()) + line.quantityShippedIncremental
  await line.processQuantityShippedChange()
})

'SOLine'.afterRetrieving(async function() {
  if ( this.propChanged("quantityShippedIncremental") )
    return
  if ( global.tc ) global.tc('SOLine.afterRetrieving 1')
  let so = this.toSOFast()
  if ( global.fastFail(so) )
    so = await this.toSO()
  if ( so && (so.retired === 'Yes') ) {
    if ( global.tc ) global.tc('SOLine.afterRetrieving 1')
    return
  }
  let qtyOnOther = this.toQtyShippedOnOtherShipmentsFast()
  if ( global.fastFail(qtyOnOther) )
    qtyOnOther = await this.toQtyShippedOnOtherShipments()
  this.quantityShippedIncremental = this.quantityShipped - qtyOnOther
  if ( global.tc ) global.tc('SOLine.afterRetrieving 2')
})

'SOLine'.afterRetrievingFast(function() {
  if ( this.propChanged("quantityShippedIncremental") )
    return
  if ( global.tc ) global.tc('SOLine.afterRetrievingFast 1')
  let so = this.toSOFast()
  if ( (! global.fastFail(so)) && (so.retired === 'Yes') ) {
    if ( global.tc ) global.tc('SOLine.afterRetrievingFast 2')
    return true
  }
  let qtyOnOther = this.toQtyShippedOnOtherShipmentsFast()
  if ( global.fastFail(qtyOnOther) ) {
    if ( global.tc ) global.tc('SOLine.afterRetrievingFast 3')
    return false
  }
  this.quantityShippedIncremental = this.quantityShipped - qtyOnOther
  if ( global.tc ) global.tc('SOLine.afterRetrievingFast 4')
  return true
})

'SOLine'.method('toQtyShippedOnOtherShipmentsFast', function() {
  let soLine = this
  let res = 0
  let shipmentLines = 'SOShipmentLine'.bringFast({soLine: soLine}, {force: true})
  if ( global.fastFail(shipmentLines) ) {
    return 'na'
  }
  for ( var i = 0; i < shipmentLines.length; i++ ) {
    let shipmentLine = shipmentLines[i]
    let shipment = shipmentLine.refereeFast('shipmentNumber', {noCalc: true})
    if ( global.fastFail(shipment) ) {
      return 'na'
    }
    if ( (shipment.shipmentDate === global.todayYMD()) && (shipment.invoiced !== 'Yes') ) continue
    res += shipmentLine.quantityShipped
  }
  return res
})

'SOLine'.method('toQtyShippedOnOtherShipments',  async function() {
  let soLine = this
  let res = 0
  let shipmentLines = await 'SOShipmentLine'.bring({soLine: soLine})
  for ( var i = 0; i < shipmentLines.length; i++ ) {
    let shipmentLine = shipmentLines[i]
    let shipment = await shipmentLine.referee('shipmentNumber'); if ( ! shipment ) continue
    if ( (shipment.shipmentDate === global.todayYMD()) && (shipment.invoiced !== 'Yes') ) continue
    res += shipmentLine.quantityShipped
  }
  return res
})

'SOLine'.method('toShipmentLines', async function() {
  return await 'SOShipmentLine'.bring({soLine: this.reference()})
})

'SOLine'.method('toShipmentLinesFast', function() {
  return 'SOShipmentLine'.retrieveFast({soLine: this.reference()})
})

'unitPriceIncTax'.calculate(soLine => {

  let slow = async () => {
    let order_item = await soLine.toOrderItem(); 
    if ( ! order_item ) return 0
    if ( ! order_item._qty ) return 0
    return order_item.valueIncTax / order_item._qty
  }

  let order_item = soLine.toOrderItemFast()
  if ( global.fastFail(order_item) )
    return slow()
  if ( ! order_item._qty ) return 0
  return order_item.valueIncTax / order_item._qty

})

'SOLine'.method('adjustLotQuantities', async function() {

  let createTran = async (qty, lot, allotment) => {
    if ( ! qty ) return
    let product = await this.toProduct(); if ( ! product ) return
    let so = await this.toSO()
    let loc = await this.toShipFromLocation()
    let tran = await 'Transaction'.create()
    tran.product = product.reference()
    tran.date = so.orderDate
    tran.quantity = qty
    tran.source = 'Serial/Lot Sale'
    tran.reference = so.order.id
    tran.notes = 'Lot adjustment'
    tran.location = loc.reference()
    if ( allotment )
      tran.allotment = allotment.reference()
    if ( lot )
      tran.lot = lot.reference()
    tran.refreshIndexes()
  }

  if ( this.hasLots !== 'Yes' ) return
  let oi = await this.toOrderItem(); if ( ! oi ) return
  if ( oi.isNew() ) return
  if ( oi.order_status === 'wc-pending' ) return // these haven't been deducted from WC stock level
  let allotments = await 'Allotment'.bringChildrenOf(this, {includeMarkedForDeletion: true})
  for ( var i = 0; i < allotments.length; i++ ) {
    let a = allotments[i]
    let qty = a.quantity - a.quantityUpdatedToTransaction
    if ( qty === 0 ) continue
    let lot = await a.toLot(); if ( ! lot ) continue
    await createTran(- qty, lot, a)
    await createTran(qty, null)
    a.quantityUpdatedToTransaction = a.quantity
  }
})

'SOLine'.method('toLocation', async function() {
  return await this.toShipFromLocation()
})

'SOLine'.method('toLocationFast', function() {
  return this.toShipFromLocationFast()
})

'SOLine'.method('toMainQuantity', async function() {
  if ( this.quantityShipped )
    return this.quantityShipped
  return this.quantityToPick
})

'SOLine'.method('refreshUnspecifiedLot', async function(opt) {
  if ( this.hasLots !== 'Yes' ) return
  let c = await 'Configuration'.bringFirst(); if ( ! c ) return
  await c.balanceAllotments(this, opt)
  await this.refreshClusterQuantityReservedForCustomerOrders()
  await this.refreshClusterQuantityAllocated()
})

'SOLine'.method('checkSerialNotDuplicated', async function(allotment) {
  if ( this.lotsAreSerials !== 'Yes' ) return
  let lot = await allotment.toLot(); if ( ! lot ) return
  if ( lot.lotNumber === 'Unspecified' )
    return
  let otherAllotments = await 'Allotment'.bringChildrenOf(this)
  for ( var i = 0; i < otherAllotments.length; i++ ) {
    let oa = otherAllotments[i]
    if ( oa.id === allotment.id ) continue
    if ( oa.quantity <= 0 ) continue
    let oln = await oa.toLotNumber()
    if ( oln !== lot.lotNumber ) continue
    throw(new Error('You cannot specify the same serial number twice on the same Sales Order Line'))
  }
})

'hasLots'.calculate(soLine => {

  let slow = async () => {
    let inv = await soLine.toInventory(); 
    if ( ! inv ) return 'No'
    let bres = inv.lotTracking && (inv.lotTracking !== 'None')
    let res = bres ? 'Yes' : 'No'
    return res
  }

  let inv = soLine.toInventoryFast()
  if ( global.fastFail(inv) ) 
    return slow()
  let bres = inv.lotTracking && (inv.lotTracking !== 'None')
  let res = bres ? 'Yes' : 'No'
  return res
})

'lotsAreSerials'.calculate(soLine => {

  let slow = async () => {
    let inv = await soLine.toInventory(); 
    if ( ! inv ) return 'No'
    let bres = inv.lotTracking === 'Serial'
    let res = bres ? 'Yes' : 'No'
    return res
  }

  let inv = soLine.toInventoryFast()
  if ( global.fastFail(inv) )
    return slow()
  let bres = inv.lotTracking === 'Serial'
  let res = bres ? 'Yes' : 'No'
  return res
})

'SOLine'.method('processQuantityShippedChange', async function() {
  await this.refreshUnspecifiedLot()
  this.allowNegativeUnspecified = 'No'
  let so = await this.toSO(); if ( ! so ) return
  so.manageInProfitori = 'Yes'
  await so.maybeAutoSetFulfillStage()
  await so.maybeAutoSetWCStatus()
})

'quantityShipped'.afterUserChange(async (oldInputValue, newInputValue, line) => {
  await line.processQuantityShippedChange()
})

'shipFromLocation'.afterUserChange(async (oldInputValue, newInputValue, line) => {
  await line.refreshUnspecifiedLot()
  let c = await 'Configuration'.bringFirst()
  await c.refreshPackables()
})

'quantityPackable'.calculate(line => {
  return line.quantityPickable + line.quantityMakeable
})

'quantityToPack'.calculate(line => {
  let res = line.quantityToPick + line.quantityToMake
  return res
})

'quantityToPick'.afterUserChange(async (oldInputValue, newInputValue, line) => {
  if ( (! oldInputValue) && (! newInputValue) ) return
  if ( oldInputValue == newInputValue ) return
  line.quantityToPickEntered = (newInputValue !== '') ? 'Yes' : 'No'
  if ( line.quantityAllocated > line.quantityToPick ) {
    line.quantityAllocated = line.quantityToPick
    let c = await line.toCluster()
    await c.refreshQuantityAllocated()
    await c.refreshQuantityReservedForCustomerOrders()
  }
  await line.allot()
})

'quantityToMake'.afterUserChange(async (oldInputValue, newInputValue, line) => {
  if ( (! oldInputValue) && (! newInputValue) ) return
  if ( oldInputValue == newInputValue ) return
  line.quantityToMakeEntered = (newInputValue !== '') ? 'Yes' : 'No'
  if ( line.quantityAllocatedForMaking > line.quantityToMake ) {
    line.quantityAllocatedForMaking = line.quantityToMake
    let c = await line.toCluster()
    await c.refreshQuantityAllocated()
    await c.refreshQuantityReservedForCustomerOrders()
  }
  await line.refreshPackable()
})

'SOLine'.method('toTopParentSOLine', async function() {
  let parent = await this.referee('parentSOLine'); 
  if ( ! parent )
    return this
  return await parent.toTopParentSOLine()
})

'SOLine'.method('toProductUniqueName', async function() {
  if ( ! this.product ) return ''
  let p = await this.toProduct(); if ( ! p ) return null
  return p.uniqueName
})

'SOLine'.method('toProductUniqueNameFast', function() {
  if ( ! this.product ) return ''
  let p = this.toProductFast()
  if ( global.fastFail(p) )
    return 'na'
  return p.uniqueName
})

'SOLine'.method('toShipFromLocation', async function() {
  return await this.referee('shipFromLocation')
})

'SOLine'.method('toShipFromLocationFast', function() {
  return this.refereeFast('shipFromLocation')
})

'descriptionAndSKU'.calculate(soLine => {

  let slow = async () => {
    let res = await soLine.toProductUniqueName()
    if ( global.tc ) global.tc("descriptionAndSKUSlow")
    return res
  }

  if ( ! soLine.product ) return ''
  let res = soLine.toProductUniqueNameFast()
  if ( global.fastFail(res) ) 
    return slow()
  return res
})

'SOLine'.method('toProduct', async function() {
  if ( ! this.product ) return null
  let res = this.toProductFast()
  if ( global.fastFail(res) )
    res = await this.referee('product')
  return res
})

'SOLine'.method('toProductFast', function() {
  if ( ! this.product ) return null
  return this.refereeFast('product')
})

'SOLine'.method('toOrderItemFast', function () {
  let mold = global.foreman.doNameToMold('order_items')
  if ( ! this.order_item_id )
    return null
  let res = mold.fastRetrieveSingle({id: this.order_item_id});
  if ( res === 'na' ) return null
  return res
})

'SOLine'.method('toOrderItem', async function () {
  if ( ! this.order_item_id ) return null
  return await 'order_items.RecentOrActive'.bringSingle({id: this.order_item_id})
})

'quantityRemainingToShip'.calculate(line => {
  let res = line.quantityOrdered - line.quantityShipped
  if ( res < 0 )
    res = 0
  return res
})

'shipToStateAndCountry'.calculate(line => {

  let slow = async () => {
    let so = await line.toSO(); if ( ! so ) return ''
    return so.shipToStateAndCountry
  }

  let so = line.toSOFast()
  if ( global.fastFail(so) )
    return slow()
  return so.shipToStateAndCountry
})

'SOLine'.method('toSO', async function() {
  return await this.referee('salesOrder')
})

'SOLine'.method('toSOFast', function() {
  return this.refereeFast('salesOrder')
})

'packable'.options(['Yes', 'Partially', 'No'])

'fulfillStage'.options(['Waiting', 'Waiting – Partially Shipped', 'Picking', 'Packing', 'Packed', 'Shipped'])

'fulfillStage'.inception('Waiting')

'priority'.options(['', '1', '2', '3', '4', '5', '6', '7', '8', '9'])

'SOLine'.method('toPreallocationPct', async function() {
  let allocationLine = await 'AllocationLine'.bringFirst({soLine: this.reference()}); if ( ! allocationLine ) return 0
  return allocationLine.allocatePercent
})

'SOLine'.method('refreshPackable', async function(opt) {
  let lineType = this.lineType
  if ( lineType && (lineType !== 'Product' ) ) {
    this.packable = 'Yes'
    return
  }
  let unallocate = opt && opt.unallocate
  let forReallocation = opt && opt.forReallocation
  let preallocated = opt && opt.preallocated
  if ( this.parentSOLine ) return
  if ( preallocated && (! this.divvy) ) return
  if ( (preallocated === false) && this.divvy ) return
  let oldQuantityToPick = this.quantityToPick
  //await this.removeSublines()
  await this.zeroSublines()
  await this.refreshCalculations({force: true})
  this.packable = 'No'
  this.quantityAllocated = 0
  this.quantityAllocatedForMaking = 0
  this.quantityPickable = 0
  this.quantityMakeable = 0
  let cluster = await this.toCluster(); if ( ! cluster ) return
  await cluster.refreshQuantityAllocated()
  if ( ! unallocate ) {
    if ( this.divvy ) {
      this.quantityPickable = this.divvy
    } else {
      this.quantityPickable = cluster.quantityPickable - cluster.quantityAllocatedForPicking - cluster.quantityReservedAndShipped
    }
    this.quantityMakeable = cluster.quantityMakeable - cluster.quantityAllocatedForMaking
  }
  await this.refreshCalculations({force: true})
  if ( this.quantityRemainingToShip <= 0 )
    this.packable = 'No'
  else if ( this.quantityPackable >= this.quantityRemainingToShip )
    this.packable = 'Yes'
  else if ( this.quantityPackable > 0 )
    this.packable = 'Partially'
  else
    this.packable = 'No'
  if ( (this.quantityToPickEntered !== 'Yes') || (this.quantityRemainingToShip < this.quantityToPick) || (this.quantityToPick > this.quantityPickable) ) {
    this.quantityToPick = Math.min(this.quantityPickable, this.quantityRemainingToShip)
    if ( this.quantityPickable < 0 ) 
      this.quantityToPick = 0
  }
  this.quantityAllocated = this.quantityToPick
  let rem = this.quantityRemainingToShip - this.quantityToPick
  if ( (this.quantityToMakeEntered !== 'Yes') || (rem < this.quantityToMake) || (this.quantityToMake > this.quantityMakeable) ) {
    this.quantityToMake = Math.min(this.quantityMakeable, rem)
    if ( this.quantityMakeable < 0 ) 
      this.quantityToMake = 0
  }
  this.quantityAllocatedForMaking = this.quantityToMake
  //await this.addSublines()
  await this.updateSublines()
  await this.refreshCalculations({force: true})
  if ( (this.hasLots === 'Yes') && (oldQuantityToPick !== this.quantityToPick) && (this.manuallyAllotted !== 'Yes') ) {
    await this.allot({forReallocation: forReallocation})
  }
  await cluster.refreshQuantityAllocated()
  if ( this.hasLots === 'Yes' )
    await cluster.refreshQuantityReservedForCustomerOrders()
  let inv = await this.toInventory()
  await inv.refreshQuantityAllocated() // just sums the clusters
})

'SOLine'.method('zeroSublines', async function() {
  let so = await this.toSO(); if ( ! so ) return
  let lines = await 'SOLine'.bring({parentId: so.id})
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    if ( ! line.parentSOLine ) continue
    let parent = await line.referee('parentSOLine'); if ( ! parent ) continue
    if ( (parent === this) || (parent.id === this.id) ) {
      let subline = line
      subline.packable = 'No'
      subline.quantityOrdered = 0
      let cluster = await subline.toCluster(); if ( ! cluster ) continue
      subline.quantityPickable = 0
      subline.quantityToPick = 0
      subline.quantityAllocated = 0
      subline.quantityMakeable = 0
      subline.quantityToMake = 0
      subline.quantityAllocatedForMaking = 0
      await subline.zeroSublines()
    }
  }
})

'SOLine'.method('updateSublines', async function(opt) {
  let line = this
  line.productIsBundle = 'No'
  let bundle = await this.toBundle(); if ( ! bundle ) return
  line.productIsBundle = 'Yes'
  let location = await this.toShipFromLocation()
  let components = await bundle.toComponents()
  let so = await this.toSO();
  for ( var i = 0; i < components.length; i++ ) {
    let component = components[i]
    let product = await component.toProduct(); if ( ! product ) continue
    let subline
    subline = 'SOLine'.bringSingleFast({salesOrder: so, parentSOLine: line, product: product}, {useIndexedField: 'parentSOLine'})
    if ( global.fastFail(subline) )
      subline = await 'SOLine'.bringFirst({salesOrder: so, parentSOLine: line, product: product}, {useIndexedField: 'parentSOLine'})
    if ( (! subline) && (! line.quantityToMake) )
      continue
    if ( ! subline )
      subline = await 'SOLine'.create({parentCast: so}, {salesOrder: so, parentSOLine: line, product: product})
    subline.shipFromLocation = location.reference()
    subline.sequence = line.sequence + '.' + global.padWithZeroes(i + 1, 3)
    subline.order_item_id = line.order_item_id
    subline.packable = 'Yes'
    subline.fulfillStage = line.fulfillStage
    subline.priority = line.priority
    let quantityNeeded = line.quantityToMake * component.quantity
    subline.quantityOrdered = quantityNeeded
    let cluster
    cluster = await subline.toCluster(); if ( ! cluster ) continue
    subline.quantityPickable = cluster.quantityPickable - cluster.quantityAllocatedForPicking
    subline.quantityToPick = Math.min(quantityNeeded, subline.quantityPickable)
    subline.quantityAllocated = subline.quantityToPick
    subline.quantityMakeable = cluster.quantityMakeable - cluster.quantityAllocatedForMaking
    subline.quantityToMake = quantityNeeded - subline.quantityToPick
    subline.quantityAllocatedForMaking = subline.quantityToMake
    await subline.updateSublines(opt)
  }
/*
  let keepZeroes = opt && opt.keepZeroes
  if ( ! keepZeroes )
    await this.removeZeroSublines()
*/
})

'SOLine'.method('removeZeroSublines', async function() {
  let so = await this.toSO(); if ( ! so ) return
  let lines = await 'SOLine'.bring({parentId: so.id})
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    if ( ! line.parentSOLine ) continue
    let parent = await line.referee('parentSOLine'); if ( ! parent ) continue
    if ( (parent === this) || (parent.id === this.id) ) {
      if ( line.quantityOrdered || line.quantityToMake || line.quantityToPick ) continue
      await line.removeSublines()
      await line.trash()
    }
  }
})

'SOLine'.method('removeSublines', async function() {
  let so = await this.toSO(); if ( ! so ) return
  let lines = await 'SOLine'.bring({parentId: so.id})
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    if ( ! line.parentSOLine ) continue
    let parent = await line.referee('parentSOLine'); if ( ! parent ) continue
    if ( (parent === this) || (parent.id === this.id) ) {
      await line.removeSublines()
      await line.trash()
    }
  }
})

/*
'SOLine'.method('addSublines', async function() {
  let line = this
  line.productIsBundle = 'No'
  let bundle = await this.toBundle(); if ( ! bundle ) return
  line.productIsBundle = 'Yes'
  let location = await this.toShipFromLocation()
  let components = await bundle.toComponents()
  let so = await this.toSO();
  for ( var i = 0; i < components.length; i++ ) {
    let component = components[i]
    let product = await component.toProduct(); if ( ! product ) continue
    let subline = await 'SOLine'.create(null, {salesOrder: so})
    subline.parentSOLine = line.reference()
    subline.shipFromLocation = location.reference()
    subline.sequence = line.sequence + '.' + global.padWithZeroes(i + 1, 3)
    subline.order_item_id = line.order_item_id
    subline.product = product.reference()
    subline.packable = 'Yes'
    subline.fulfillStage = line.fulfillStage
    subline.priority = line.priority
    let quantityNeeded = line.quantityToMake * component.quantity
    subline.quantityOrdered = quantityNeeded
    let cluster = await subline.toCluster(); if ( ! cluster ) continue
    subline.quantityPickable = cluster.quantityPickable - cluster.quantityAllocatedForPicking
    subline.quantityToPick = Math.min(quantityNeeded, subline.quantityPickable)
    subline.quantityAllocated = subline.quantityToPick
    subline.quantityMakeable = cluster.quantityMakeable - cluster.quantityAllocatedForMaking
    subline.quantityToMake = quantityNeeded - subline.quantityToPick
    subline.quantityAllocatedForMaking = subline.quantityToMake
    await subline.addSublines()
  }
})
*/

'SOLine'.method('toBundle', async function() {
  let product = await this.toProduct(); if ( ! product ) return null
  let res = await product.toBundle()
  return res
})

'shipFromLocation'.inception(async soLine => {
  let location = await 'Location'.bringOrCreate({locationName: 'General'})
  return location.reference()
})

'SOLine'.method('toInventory', async function() {
  let p = await this.toProduct(); if ( ! p ) return null
  let res = await p.toInventory()
  return res
})

'SOLine'.method('toCluster', async function() {
  let inv = await this.toInventory(); if ( ! inv ) return null
  let res = await inv.locationRefToCluster(this.shipFromLocation)
  return res
})

'descriptionAndSKU'.destination(async (soLine, productRef) => {
  return await soLine.toProduct()
})

'SOLine'.method('toOrder', async function() {
  let oi = await this.toOrderItem(); if ( ! oi ) return null
  return await oi.toOrder()
})

'SOLine'.method('toOrderId', async function() {
  let order = await this.toOrder(); if ( ! order ) return null
  return order.id
})

'SOLine'.method('toOrderDate', async function() {
  if ( (await this.manageInProfitori()) === 'Yes' )
    return this.orderDate
  let order = await this.toOrder(); if ( ! order ) return global.emptyYMD()
  return order.order_date
})

'SOLine'.method('toSaleValue', async function() {
  if ( (await this.manageInProfitori()) === 'Yes' )
    return this.valueIncTax
  let oi = await this.toOrderItem(); if ( ! oi ) return 0
  return oi.valueIncTax
})

'SOLine'.beforeFirstSave(async function() {
  global._deferRefreshAllocated = true
  global._inventoriesToRefresh = {}
})

'SOLine'.afterLastSave(async function() {
  global._deferRefreshAllocated = false
  let keys = Object.keys(global._inventoriesToRefresh)
  for ( var i = 0; i < keys.length; i++ ) {
    let key = keys[i]
    let inv = global._inventoriesToRefresh[key]
    await inv.refreshQuantityAllocated({refreshClusters: true})
  }
  global._inventoriesToRefresh = {}
})

'SOLine'.beforeSaving(async function() {

  let getOrCreateEntry = async () => {
    let sourceAbbrev = 'SA'
    let entryNumber = sourceAbbrev + '-' + this.journaledDate
    let orderDate = await this.toOrderDate()
    if ( ! orderDate )
      orderDate = global.todayYMD()
    if ( orderDate !== this.journaledDate )
      entryNumber += '--' + orderDate
    let res = await 'Entry'.bringOrCreate({entryNumber: entryNumber})
    res.effectiveDate = this.journaledDate
    res.sourceEffectiveDate = orderDate
    res.notes = 'Sale ' + orderDate.toLocalMMMDY()
    res.enteredDate = global.todayYMD()
    return res
  }

  let updateJournalEntry = async (options) => {
    let entry = await getOrCreateEntry()
    await entry.updateFromSOLine(options.location, options.amount)
  }
  
  let maybeReverseOldEntry = async () => {
    if ( ! this.journaledAmount ) return
    let loc = await this.referee('journaledLocation')
    await updateJournalEntry({location: loc, amount: - this.journaledAmount})
  }

  let updateJournalEntries = async () => {
    let config = await 'Configuration'.bringOrCreate()
    if ( config.glEnabled !== 'Yes' ) return
    let date = await this.toOrderDate()
    this.journaledDate = await config.dateToPostableDate(date)
    if ( this.parentSOLine ) return
    await maybeReverseOldEntry()
    let amount = await this.toSaleValue()
    let loc = await this.toLocation()
    await updateJournalEntry({location: loc, amount: amount, date: date})
    this.journaledAmount = amount
    this.journaledLocation = loc.reference()
  }

  let getOldShipFromLocationId = async () => {
    let ref = this.getOld().shipFromLocation; if ( ! ref ) return null
    return ref.id
  }

  let maybeMoveInventory = async () => {
    if ( this.parentSOLine ) return
    let loc = await this.toShipFromLocation(); if ( ! loc ) return
    let oldLocId = await getOldShipFromLocationId(); if ( ! oldLocId ) return
    let oldLoc = await 'Location'.bringSingle({id: oldLocId}); if ( ! oldLoc ) return
    if ( loc === oldLoc )
      return
    let product = await this.toProduct(); if ( ! product ) return
    let order = await this.toOrder(); if ( ! order ) return
    let nextNo = await 'NextNumber'.bringOrCreate({forDatatype: 'Transfer'})
    let tfrNo
    let tfr
    while ( true ) {
      nextNo.number = nextNo.number + 1
      let noStr = nextNo.number + ""
      tfrNo = "TF" + noStr.padStart(5, '0')
      tfr = await 'Transfer'.bringFirst({transferNumber: tfrNo})
      if ( ! tfr )
        break
    }
    tfr = await 'Transfer'.create(null, {transferNumber: tfrNo})
    tfr.date = global.todayYMD()
    tfr.fromLocation = loc.reference()
    tfr.toLocation = oldLoc.reference() // old location quantity increases, as the order is no longer coming from there
    tfr.product = product.reference()
    tfr.quantity = this.quantityOrdered
    tfr.notes = 'SO ' + order.id + ' Ship From Location change'
    if ( this.hasLots === 'Yes' ) {
      let config = await 'Configuration'.bringSingle(); if ( ! config ) return
      await config.balanceAllotments(tfr)
    }
  }

  let refreshSO = async () => {
    let so = await this.toSO()
    await so.refreshTotals()
    if ( this.propChanged('quantityShipped') ) {
      await so.refreshShipment()
    }
    await so.maybeAnnotateWCOrder()
  }

  let deleteOrderItem = async () => {
    if ( this.parentSOLine ) return // these don't have order_items
    let oi = await this.toOrderItem(); if ( ! oi ) return
    await oi.trash()
  }

  let inv = await this.toInventory()
  if ( global.confVal('preventOverpick') === 'Yes' ) {
    if ( (! global.harmonizing) && (! global.refreshingPackables) ) {
      if ( this.propChanged('quantityShipped') ) {
        let qtyAlreadyShipped = await this.toQtyShippedOnOtherShipments()
        let shippedChg = this.quantityShipped - qtyAlreadyShipped
        if ( shippedChg > this.quantityPickable ) {
          throw(new Error("Quantity Shipped cannot be more than Quantity Pickable"))
        }
        if ( this.quantityShipped > this.quantityOrdered )
          throw(new Error("Quantity Shipped cannot be more than Quantity Remaining to Ship"))
      }
    }
  }
  if ( inv ) {
    await inv.refreshQuantityReservedForCustomerOrders({refreshClusters: true})
    await inv.refreshQuantityAllocated({refreshClusters: true})
  }
  //if ( this.quantityToPick > this.quantityRemainingToShip )
    //throw(new Error("Quantity to Pick cannot be more than Quantity Remaining To Ship"))
  if ( ! this._markedForDeletion ) {
    await maybeMoveInventory()
    await refreshSO()
    await this.adjustLotQuantities() // Added Aug 4 2022
  } else {
    if ( (await this.manageInProfitori()) === 'Yes' )
      await deleteOrderItem()
  }
  await updateJournalEntries()
})
