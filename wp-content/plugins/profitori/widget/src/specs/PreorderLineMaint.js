'PreorderLineMaint'.maint({panelStyle: "titled"})
'Add Preorder Line'.title({when: 'adding'})
'Edit Preorder Line'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Add another line'.action({act: 'add'})
'PreorderLine'.datatype()

'Line Details'.panel()
'preorder'.field({refersToParent: 'Preorder', showAsLink: true, caption: 'Preorder Number'})
'lineType'.field()
'product'.field({refersTo: 'products'})
'description'.field({allowEmpty: false, showAsLink: true})
'shippingMethod'.field()

''.panel()

'Quantity and Price'.panel()
'applyDiscount'.field()
'discount'.field({hideWhenBlank: true, readOnly: true})
'unitPriceBDExTax'.field()
'discountType'.field()
'discountPercentage'.field({numeric: true, decimals: 2, caption: 'Discount %'})
'discountAmountPerUnit'.field({numeric: true, decimals: 2})
'quantity'.field({numeric: true})
'unitValueExclTaxFX'.field({numeric: true})
'unitValueIncTaxFX'.field({numeric: true, caption: "Unit Price (Inc Tax)"})
'taxPct'.field({numeric: true, decimals: 2, caption: "Tax %"})
'lineValueIncTaxFX'.field({numeric: true, decimals: 2, caption: "Line Total (Inc Tax)", readOnly: true})
'lineTaxFX'.field({numeric: true, decimals: 2, readOnly: true})

'Product Image'.panel()
'image'.field({caption: ''})

'discountAmountPerUnit'.readOnlyWhen((maint, line) => {
  return global.preventChangeSOPrice()
})

'discountPercentage'.readOnlyWhen((maint, line) => {
  return global.preventChangeSOPrice()
})

'discountType'.readOnlyWhen((maint, line) => {
  return global.preventChangeSOPrice()
})

'unitPriceBDExTax'.readOnlyWhen((maint, line) => {
  return global.preventChangeSOPrice()
})

'applyDiscount'.readOnlyWhen((maint, line) => {
  return global.preventChangeSOPrice()
})

'unitValueExclTaxFX'.readOnlyWhen((maint, line) => {
  return (line.applyDiscount === 'Yes') || global.preventChangeSOPrice()
})

'unitValueIncTaxFX'.readOnlyWhen((maint, line) => {
  return (line.applyDiscount === 'Yes') || global.preventChangeSOPrice()
})

'unitPriceBDExTax'.visibleWhen((maint, line) => {
  if ( line.applyDiscount !== 'Yes' )
    return false
  return true
})

'discountAmountPerUnit'.visibleWhen((maint, line) => {
  if ( line.applyDiscount !== 'Yes' )
    return false
  if ( line.discountType !== 'Amount' )
    return false
  return true
})

'discountPercentage'.visibleWhen((maint, line) => {
  if ( line.applyDiscount !== 'Yes' )
    return false
  if ( line.discountType !== 'Percentage' )
    return false
  return true
})

'discountType'.visibleWhen((maint, line) => {
  if ( line.applyDiscount !== 'Yes' )
    return false
  return true
})

'discount'.visibleWhen((maint, line) => {
  if ( line.applyDiscount !== 'Yes' )
    return false
  if ( ! line.discount )
    return false
  return true
})

'shippingMethod'.visibleWhen((maint, line) => {
  return line.lineType === 'Shipping'
})

'shippingMethod'.excludeChoiceWhen(async (maint, shippingMethod, line) => {
  let preorder = await line.toPreorder(); if ( ! preorder ) return true
  let countryCode = await preorder.toCountryCode()
  if ( ! countryCode ) return false
  return ! (await shippingMethod.appliesToCountryCode(countryCode))
})

'PreorderLine'.validate(async function() {
  if ( this.lineType === 'Shipping' ) {
    if ( (this.isNew() || this.propChanged('shippingMethod')) && (! this.shippingMethod) )
      throw(new Error('Shipping Method is required'))
  }
})

'Product Image'.visibleWhen((maint, line) => {
  return ((line.lineType === 'Product') && line.product) ? true : false
})

'PreorderLineMaint'.makeDestinationFor('PreorderLine')

'PreorderLine'.datatype()

'unitValueExclTaxFX'.visibleWhen((maint, preorderLine) => {
  return preorderLine.includeTaxOption === "No"
})

'unitValueIncTaxFX'.visibleWhen((maint, preorderLine) => {
  return preorderLine.includeTaxOption !== "No"
})

'quantity'.afterUserChange(async (oldInputValue, newInputValue, preorderLine, maint) => {
  await preorderLine.maybeGenerateImpostLines()
})

'product'.afterUserChange(async (oldInputValue, newInputValue, preorderLine, maint) => {
  await preorderLine.refreshDiscount()
  let name = await preorderLine.toProductName()
  if ( name )
    maint.setFieldValue('description', name)
  let product = await 'products'.bringFirst({uniqueName: newInputValue}) 
  if ( product ) {
    let unitPriceBDExTax = await product.toRetailPriceExclTax()
    preorderLine.unitPriceBDExTax = unitPriceBDExTax
    await preorderLine.refreshDiscount()
    let unitPriceExTax = await preorderLine.subtractDiscount(unitPriceBDExTax)
    maint.setFieldValue('unitPriceBDExTax', unitPriceBDExTax)
    maint.setFieldValue('unitValueExclTaxFX', unitPriceExTax)
    let taxPct = await product.toRetailTaxPct()
    maint.setFieldValue('taxPct', taxPct)
    let unitValueIncTaxFX = global.roundToXDecimals(unitPriceExTax * ( 1 + (taxPct / 100)), 2)
    maint.setFieldValue('unitValueIncTaxFX', unitValueIncTaxFX)
    preorderLine.unitValueIncTaxFX = unitValueIncTaxFX
    preorderLine.unitValueExclTaxFX = unitPriceExTax
    await preorderLine.maybeGenerateImpostLines()
  }
})

'lineType'.afterUserChange(async (oldInputValue, newInputValue, preorderLine, maint) => {
  let lineType = preorderLine.lineType
  if ( lineType === "Other" ) {
    maint.setFieldValue('description', '')
    maint.setFieldValue('quantity', 1)
  } else if ( lineType && (lineType !== "Product") ) {
    maint.setFieldValue('description', lineType)
    maint.setFieldValue('quantity', 1)
  }
})

'product'.visibleWhen((maint, preorderLine) => {
  let lineType = preorderLine.lineType
  return (! lineType) || (lineType === "Product")
})

'taxPct'.inception(async (preorderLine) => {
  let config = await 'Configuration'.bringSingle(); if ( ! config ) return 0
  let res = config.taxPct
  return res
})

'PreorderLine'.datatype()
