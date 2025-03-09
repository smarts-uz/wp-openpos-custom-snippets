'SOManageLineMaint'.maint({panelStyle: "titled",  icon: 'FileInvoiceDollar'})
'Add Sales Order Line'.title({when: 'adding'})
'Edit Sales Order Line'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Add another line'.action({act: 'add'})

'SOLine'.datatype()

'Line Details'.panel()
'salesOrder'.field({readOnly: true, showAsLink: true})
'lineType'.field()
'product'.field({refersTo: 'products'})
'description'.field({allowEmpty: false})
'shippingMethod'.field()
'quantityOrdered'.field({caption: 'Quantity'})
'quantityShipped'.field({readOnly: true})
'quantityRemainingToShip'.field({readOnly: true})
'notes'.field({multiLine: true})

'Price'.panel()
'applyDiscount'.field()
'discount'.field({hideWhenBlank: true, readOnly: true})
'unitPriceBDExTax'.field()
'discountType'.field()
'discountPercentage'.field({numeric: true, decimals: 2, caption: 'Discount %'})
'discountAmountPerUnit'.field({numeric: true, decimals: 2})
'unitPriceExTax'.field({caption: 'Unit Price (Excl Tax)'})
'lineTotalExclTax'.field({readOnly: true, caption: 'Line Total (Excl Tax)'})
'taxPct'.field()
'lineTax'.field({readOnly: true})
'valueIncTax'.field({readOnly: true, caption: 'Line Total (Inc Tax)'})

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

'unitPriceExTax'.readOnlyWhen((maint, line) => {
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

'product'.readOnlyWhen((maint, line) => {
  return ! line.isNew()
})

'Product Image'.visibleWhen((maint, line) => {
  return ((line.lineType === 'Product') && line.product) ? true : false
})

'SOManageLineMaint'.makeDestinationFor('SOLine')

'shippingMethod'.visibleWhen((maint, line) => {
  return line.lineType === 'Shipping'
})

'shippingMethod'.excludeChoiceWhen(async (maint, shippingMethod, line) => {
  let so = await line.toSO(); if ( ! so ) return true
  let countryCode = global.countryToCode(so.billingCountry)
  if ( ! countryCode ) return false
  return ! (await shippingMethod.appliesToCountryCode(countryCode))
})

'product'.visibleWhen((maint, line) => {
  let lineType = line.lineType
  return (! lineType) || (lineType === "Product")
})

'taxPct'.readOnlyWhen((maint, line) => {
  let lineType = line.lineType
  return (! lineType) || (lineType === "Product")
})
