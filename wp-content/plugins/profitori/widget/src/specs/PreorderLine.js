'PreorderLine'.datatype()
'preorder'.field({refersToParent: 'Preorder', parentIsHeader: true})
'lineType'.field()
'product'.field({refersTo: 'products', indexed: true})
'description'.field()
'quantity'.field({numeric: true})
'taxPct'.field({numeric: true, decimals: 2, caption: "Tax %"})
'unitValueIncTaxFX'.field({numeric: true, decimals: 2, caption: "Unit Price (Inc Tax)"})
'unitValueIncTax'.field({numeric: true, decimals: 2, caption: "Unit Price (Inc Tax)", storedCalc: true})
'unitValueExclTaxFX'.field({numeric: true, decimals: 2, caption: "Unit Price (Excl Tax)"})
'unitValueExclTax'.field({numeric: true, decimals: 2})
'lineValueIncTax'.field({numeric: true, decimals: 2, caption: "Line Total (Inc Tax)"})
'lineValueIncTaxFX'.field({numeric: true, decimals: 2, caption: "Line Total (Inc Tax)", readOnly: true})
'lineValueExclTaxFX'.field({numeric: true, decimals: 2, caption: "Line Total (Excl Tax)", readOnly: true})
'lineTax'.field({numeric: true, decimals: 2})
'lineTaxFX'.field({numeric: true, decimals: 2, caption: "Line Tax", readOnly: true})
'sku'.field({caption: 'SKU'})
'supplierProductNameAndSKU'.field({caption: 'Product'})
'descriptionCalc'.field()
'descriptionAndSKU'.field({caption: 'Description'})
'includeTaxOption'.field({yesOrNo: true})
'unitValueIncTaxFXRounded'.field({numeric: true, decimals: 2, caption: "Unit Price (Inc Tax)"})
'image'.field({postImage: true, postImageType: 'full', postIdField: 'product'})
'thumbnailImage'.field({postImage: true, postImageType: 'thumbnail', postIdField: 'product', caption: 'Image'})
'lineValueExclTax'.field({numeric: true, minDecimals: 2, maxDecimals: 6, caption: "Line Total (Excl Tax)"})
'preorderNumber'.field()
'requestedETADate'.field({date: true, allowEmpty: true, caption: 'Requested ETA Date'})
'customerId'.field({numeric: true})
'impost'.field({refersTo: 'Impost'})
'originatingPreorderLine'.field({refersTo: 'PreorderLine'})
'sequence'.field({caption: 'Line No'})
'shippingMethod'.field({refersTo: 'shipping_methods'})
'applyDiscount'.field({yesOrNo: true})
'discount'.field({refersTo: 'Discount'})
'unitPriceBDExTax'.field({numeric: true, decimals: 2, caption: 'Unit Price Before Discount (Excl Tax)'})
'lineTotalBDExclTax'.field({numeric: true, caption: "Line Total Before Discount Ex Tax", decimals: 2})
'discountType'.field()
'discountPercentage'.field({numeric: true, decimals: 2, caption: 'Discount %'})
'discountAmountPerUnit'.field({numeric: true, decimals: 2})

/* eslint-disable no-eval */

'applyDiscount'.inception('No')

'discountType'.options(['Percentage', 'Amount'])

'discountType'.inception('Percentage')

'unitPriceBDExTax'.calculateWhen(line => {
  return line.applyDiscount !== 'Yes'
})

'unitPriceBDExTax'.calculate(line => {
  let res = line.unitValueExclTaxFX
  return res
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

'PreorderLine'.method('refreshDiscountedUnitPrice', async function() {
  this.unitValueExclTaxFX = await this.subtractDiscount(this.unitPriceBDExTax)
  this.unitValueIncTaxFX = global.roundToXDecimals(this.unitValueExclTaxFX * ( 1 + (this.taxPct / 100)), 2)
})

'PreorderLine'.method('toCustomer', async function() {
  let p = await this.toPreorder(); if ( ! p ) return null
  return await p.toCustomer()
})

'PreorderLine'.method('determineDiscount', async function() {
  let discounts = await 'Discount'.bring({active: 'Yes'})
  if ( discounts.length === 0 ) return null
  let customer = await this.toCustomer(); if ( ! customer ) return null
  let product = await this.toProduct(); if ( ! product ) return null
  let discount = await customer.productToDiscount(product)
  return discount
})

'PreorderLine'.method('refreshDiscount', async function() {
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

'PreorderLine'.method('subtractDiscount', async function (amt) {
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

'PreorderLine'.method('toInstanceId', async function() {
  let meth = await this.referee('shippingMethod'); if ( ! meth ) return null
  return meth.instance_id
})

'PreorderLine'.method('toMethodId', async function() {
  let meth = await this.referee('shippingMethod'); if ( ! meth ) return null
  return meth.shipping_id // not a typo!
})

'shippingMethod'.afterUserChange(async (oldInputValue, newInputValue, line) => {
  let meth = await line.referee('shippingMethod'); if ( ! meth ) return
  if ( await line.enteredInclusiveOfTax() )
    line.unitValueIncTaxFX = meth.cost
  else
    line.unitValueExclTaxFX = meth.cost
})

'sequence'.inception(async preorderLine => {

  let preorderToLastNormalLine = async preorder => {
    let lines = await preorder.toPreorderLines()
    for ( var i = lines.length - 1; i >= 0; i-- ) {
      let line = lines[i]
      if ( line.sequence && (line.sequence.length === 3) )
        return line
    }
  }

  let preorder = await preorderLine.referee('preorder'); if ( ! preorder ) return '001'
  let lastLine = await preorderToLastNormalLine(preorder); if ( ! lastLine ) return '001'
  let no = parseFloat(lastLine.sequence, 10)
  return global.padWithZeroes(no + 1, 3)
})

'PreorderLine'.method('maybeGenerateImpostLines', async function() {

  let preorder = await this.referee('preorder'); if ( ! preorder ) return

  let removeImpostLines = async () => {
    let lines = await preorder.toPreorderLines()
    for ( var i = 0; i < lines.length; i++ ) {
      let line = lines[i]
      if ( line.id === this.id ) continue
      if ( ! line.originatingPreorderLine ) continue
      if ( line.originatingPreorderLine.id !== this.id ) continue
      await line.trash()
    }
  }

  let impostAppliesToPreorderCustomer = async impost => {
    let customer = await preorder.toCustomer()
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
    return res.bind(this, product, this.quantity)
  }

  let createImpostLine = async (impost, value, product, no) => {
    let line = await 'PreorderLine'.create({parentCast: preorder}, {preorder: preorder.reference()})
    line.lineType = 'Fee'
    line.description = product.uniqueName + ' - ' + impost.description
    line.quantity = 1
    line.taxPct = 0
    line.unitValueIncTaxFX = value
    line.includeTaxOption = 'Yes'
    line.impost = impost.reference()
    line.originatingPreorderLine = this.reference()
    line.sequence = this.sequence + '.' + global.padWithZeroes(no, 3)
    line.refreshIndexes()
  }

  if ( ! this.product ) return
  //if ( (! this.propChanged('quantity')) && (! this.propChanged('product')) ) return
  await removeImpostLines()
  if ( this._markedForDeletion ) 
    return
  let imposts = await 'Impost'.bring()
  let no = 0
  let product = await this.referee('product'); if ( ! product ) return
  await product.defaultEmptyClassifications()
  for ( var i = 0; i < imposts.length; i++ ) {
    let impost = imposts[i]
    if ( ! await impostAppliesToPreorderCustomer(impost) ) continue
    let fn = await impostToFunction(impost, product); if ( ! fn ) continue
    let value = fn()
    if ( ! value ) continue
    no++
    await createImpostLine(impost, value, product, no)
  }
})

'customerId'.calculate(async preorderLine => {
  let preorder = await preorderLine.referee('preorder'); if ( ! preorder ) return null
  let customer = await preorder.referee('customer'); if ( ! customer ) return null
  return customer.id
})

'preorderNumber'.calculate(async preorderLine => {
  let preorder = await preorderLine.referee('preorder'); if ( ! preorder ) return null
  return preorder.preorderNumber
})

'requestedETADate'.calculate(async preorderLine => {
  let preorder = await preorderLine.referee('preorder'); if ( ! preorder ) return null
  return preorder.requestedETADate
})

'quantity'.inception('1')

'PreorderLine'.method('toMainQuantity', async function() {
  return this.quantity
})

'PreorderLine'.afterCreating(async function () {
  let c = await 'Configuration'.bringFirst(); if ( ! c ) return
  this.includeTaxOption = c.preIncTax
})

'unitValueExclTaxFX'.calculateWhen(async (preorderLine) => {
  return await preorderLine.enteredInclusiveOfTax()
})

'unitValueExclTaxFX'.calculate(async (preorderLine) => {
  let res = global.roundToXDecimals(preorderLine.unitValueIncTaxFX / ( 1 + (preorderLine.taxPct / 100)), 2)
  return res
})

'unitValueIncTaxFX'.calculateWhen(async (preorderLine) => {
  let res = (! preorderLine.impost) && await preorderLine.enteredExclusiveOfTax()
  return res
})

'unitValueIncTaxFX'.calculate(async (preorderLine) => {
  let res = global.roundToXDecimals(preorderLine.unitValueExclTaxFX * ( 1 + (preorderLine.taxPct / 100)), 2)
  return res
})

'unitValueIncTaxFXRounded'.calculate(async (preorderLine) => {
  return preorderLine.unitValueIncTaxFX
})

'unitValueIncTax'.calculate(async preorderLine => {
  let exchangeRate = await preorderLine.toExchangeRate()
  if ( ! exchangeRate )
    exchangeRate = 1
  let res = preorderLine.unitValueIncTaxFX / exchangeRate
  return res
})

'unitValueExclTax'.calculate(async (preorderLine) => {
  let exchangeRate = await preorderLine.toExchangeRate()
  if ( ! exchangeRate )
    exchangeRate = 1
  let res = preorderLine.unitValueExclTaxFX / exchangeRate
  return res
})

'descriptionCalc'.calculate(async (preorderLine) => {
  let res = preorderLine.description
  if ( ! res )
    res = await preorderLine.toProductUniqueName()
  return res
})

'sku'.calculate(async (preorderLine) => {
  let p = preorderLine.toProductFast() 
  if ( (! p) || (p === 'na') ) {
    p = await preorderLine.toProduct(); if ( ! p ) return null
  }
  return p._sku
})

'lineType'.options(['Product', 'Shipping', 'Fee'])

'lineType'.inception('Product')

'PreorderLine'.method('toExchangeRate', async function() {
  let res = 1
  let preorder = this.refereeFast('preorder', {noCalc: true})
  if ( (! preorder) || (preorder === 'na') )
    preorder = await this.toPreorder()
  if ( ! preorder ) return res
  res = preorder.exchangeRate
  if ( ! res )
    res = 1
  return res
})

'PreorderLine'.method('toProductUniqueName', async function() {
  let p = await this.toProduct(); if ( ! p ) return null
  return p.uniqueName
})

'PreorderLine'.method('toInventoryFast', function() {
  let product = this.toProductFast(); 
  if ( (! product) || (product === 'na') ) return product
  return product.toInventoryFast()
})

'descriptionAndSKU'.calculate(async (preorderLine) => {
  let res = preorderLine.descriptionCalc
  let sku = preorderLine.sku
  if ( sku )
    res += " (" + sku + ")"
  return res
})

'PreorderLine'.method('toInventory', async function() {
  let product = await this.toProduct(); if ( ! product ) return null
  return await product.toInventory()
})

'PreorderLine'.method('toPreorder', async function() {
  return await this.referee('preorder', {includeMarkedForDeletion: true})
})

'PreorderLine'.method('toProduct', async function() {
  if ( ! this.product ) return null
  return await this.referee('product')
})

'PreorderLine'.method('toProductFast', function() {
  if ( ! this.product ) return null
  return this.refereeFast('product')
})

'PreorderLine'.method('toProductName', async function() {
  let p = await this.toProduct(); if ( ! p ) return null
  let res = await p.toName()
  return res
})

'description'.fallback(async preorderLine => {
  let name = await preorderLine.toProductName()
  if ( name )
    return name
  return preorderLine.lineType
})

'PreorderLine'.method('ensureProdRefComplete', async function() {
  if ( ! this.product ) return
  if ( this.product.id ) return
  let product = await 'products'.bringFirst({uniqueName: this.product.keyval})
  if ( ! product ) 
    return 
  this.product.id = product.id
})

'PreorderLine'.method('ensureOldProdRefComplete', async function() {
  let old = this.getOld(); if ( ! old ) return
  if ( ! old.product ) return
  if ( old.product.id ) return
  let product = await 'products'.bringFirst({uniqueName: old.product.keyval})
  if ( ! product ) 
    return 
  old.product.id = product.id
})

'PreorderLine'.beforeSaving(async function() {
  let preorder
  let inv
  if ( (! this.isNew()) && (this.lineType !== this.getOld().lineType) ) {
    throw(new Error("You cannot change the line type of an existing Preorder Line"))
  }
  if ( this.lineType && (this.lineType !== "Product") ) {
    return
  }
  if ( ! this.product )
    throw(new Error("Please choose a product"))
  await this.ensureProdRefComplete()
  await this.ensureOldProdRefComplete()
  if ( ! this.product.id )
    return

  preorder = await this.toPreorder(); if ( ! preorder ) return
  //await this.maybeGenerateImpostLines()
  let oldInv
  let cluster
  let oldCluster
  let outstanding = this.quantity
  if ( this.quantity >= 0 ) {
    if ( outstanding < 0 ) outstanding = 0
  } else {
    if ( outstanding > 0 ) outstanding = 0
  }
  let oldOutstanding = this.getOld().quantity
  if ( this.getOld().quantity >= 0 ) {
    if ( oldOutstanding < 0 ) oldOutstanding = 0
  } else {
    if ( oldOutstanding > 0 ) oldOutstanding = 0
  }
  if ( this.product ) {
    inv = await 'Inventory'.bringOrCreate({product: this.product})
    cluster = await inv.locationRefToCluster(preorder.location)
  }
  if ( this.getOld().product ) {
    oldInv = await 'Inventory'.bringFirst({product: this.getOld().product})
    if ( oldInv ) {
      oldCluster = await oldInv.locationRefToCluster(preorder.getOld().location)
    }
  }
  if ( oldInv ) {
    await oldInv.refreshQuantityOnPreorders()
  }
  if ( oldCluster ) {
    await oldCluster.refreshQuantityOnPreorders()
  }
  if ( inv ) {
    await inv.refreshQuantityOnPreorders()
  }
  if ( cluster ) {
    await cluster.refreshQuantityOnPreorders()
  }

})

'PreorderLine'.method("toOrderDate", async function() {
  let preorder = this.refereeFast('preorder', {noCalc: true})
  if ( (! preorder) || (preorder === 'na') )
    preorder = await this.toPreorder()
  return preorder && preorder.orderDate
})

'PreorderLine'.method("getLineTaxUnrounded", async function() {
  let rate = this.taxPct / 100
  let factor = rate / (1 + rate)
  let lineValueIncTax = await this.calcLineValueIncTax()
  let res = lineValueIncTax * factor
  return res
})

'PreorderLine'.method("getLineTaxFXUnrounded", async function() {
  let rate = this.taxPct / 100
  let factor = rate / (1 + rate)
  let lineValueIncTax = await this.calcLineValueIncTaxFX()
  let res = lineValueIncTax * factor
  return res
})

'PreorderLine'.method('calcLineValueIncTaxFX', async function () {
  let res = global.roundTo2Decimals(this.unitValueIncTaxFX * this.quantity)
  return res
})

'PreorderLine'.method('calcLineValueIncTax', async function () {
  let res = global.roundTo2Decimals(this.unitValueIncTax * this.quantity)
  return res
})

'PreorderLine'.method('calcLineValueExclTax', async function () {
  let res = global.roundTo2Decimals(this.unitValueExclTax * this.quantity)
  return res
})

'PreorderLine'.method('calcLineValueExclTaxFX', async function () {
  let res = global.roundTo2Decimals(this.unitValueExclTaxFX * this.quantity)
  return res
})

'PreorderLine'.method('toTaxClass', async function () {
  let p = await this.toProduct(); if ( ! p ) return null
  return p._tax_class
})

'PreorderLine'.method('toUnitTax', async function () {
  return this.unitValueIncTax - this.unitValueExclTax
})

'lineValueIncTax'.calculate(async (preorderLine) => {
  let res = await preorderLine.calcLineValueIncTax()
  return res
})

'lineValueIncTaxFX'.calculate(async (preorderLine) => {
  let res = await preorderLine.calcLineValueIncTaxFX()
  return res
})

'lineValueExclTaxFX'.calculate(async (preorderLine) => {
  let res = await preorderLine.calcLineValueExclTaxFX()
  return res
})

'lineValueExclTax'.calculate(async (preorderLine) => {
  let res = await preorderLine.calcLineValueExclTax()
  return res
})

'lineTaxFX'.calculate(async (preorderLine) => {
  let res = await preorderLine.getLineTaxFXUnrounded()
  res = global.roundTo2Decimals(res)
  return res
})

'lineTax'.calculate(async (preorderLine) => {
  let res = await preorderLine.getLineTaxUnrounded()
  res = global.roundTo2Decimals(res)
  return res
})

'PreorderLine'.method('enteredExclusiveOfTax', async function () {
  return this.includeTaxOption === "No"
})

'PreorderLine'.method('enteredInclusiveOfTax', async function () {
  return this.includeTaxOption !== "No"
})

'PreorderLine'.method('toLocationName', async function() {
  let preorder = await this.toPreorder(); if ( ! preorder ) return 'General'
  let res = await preorder.toLocationName()
  return res
})

'PreorderLine'.method('toCluster', async function() {
  let inv = await this.toInventory(); if ( ! inv ) return null
  let locRef = await this.toLocationRef(); if ( ! locRef ) return null
  let res = await inv.locationRefToCluster(locRef)
  return res
})

'PreorderLine'.method('toLocationRef', async function() {
  let preorder = this.refereeFast('preorder', {noCalc: true})
  if ( (! preorder) || (preorder === 'na') )
    preorder = await this.toPreorder()
  let res = preorder && preorder.location
  return res
})

'PreorderLine'.method('toLocation', async function() {
  let preorder = await this.toPreorder(); if ( ! preorder ) return null
  let res = await preorder.toLocation()
  return res
})

