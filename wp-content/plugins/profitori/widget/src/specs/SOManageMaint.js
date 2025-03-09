'SOManageMaint'.maint({panelStyle: "titled", icon: 'FileInvoiceDollar'})
'Add Sales Order'.title({when: 'adding'})
'Edit Sales Order'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Add another'.action({act: 'add'})
'Attachments'.action({act: 'attachments'})

'SO'.datatype()

'Order'.panel()
'order'.field({readOnly: true})
'originalSalesOrder'.field({readOnly: true, showAsLink: true, hideWhenBlank: true, caption: 'Original Order'})
'customer'.field()
'orderCurrency'.field()
'orderDate'.field()
'requestedETADate'.field()
'customerReference'.field()
'paymentMethodTitle'.field({readOnly: true})
'wcNiceStatus'.field({caption: 'Status'})

'Fulfillment Details'.panel()
'shipFromLocation'.field()
'shipmentMethod'.field()
'notes'.field()
'singleShipment'.field({readOnly: true, showAsLink: true, hideWhenBlank: true})

'Shipping Address'.panel()
'shippingFirstName'.field({caption: 'First Name'})
'shippingLastName'.field({caption: 'Last Name'})
'shippingCompany'.field({caption: 'Company'})
'shippingAddress1'.field({caption: 'Address'})
'shippingAddress2'.field({caption: 'Address Line 2'})
'shippingCity'.field({caption: 'City'})
'shippingState'.field({caption: 'State'})
'shippingPostcode'.field({caption: 'Postcode'})
'shippingCountry'.field({caption: 'Country'})

'Billing Address'.panel()
'billingFirstName'.field({caption: 'First Name'})
'billingLastName'.field({caption: 'Last Name'})
'billingCompany'.field({caption: 'Company'})
'billingAddress1'.field({caption: 'Address'})
'billingAddress2'.field({caption: 'Address Line 2'})
'billingCity'.field({caption: 'City'})
'billingState'.field({caption: 'State'})
'billingPostcode'.field({caption: 'Postcode'})
'billingCountry'.field({caption: 'Country'})
'billingEmail'.field({caption: 'Email'})
'billingPhone'.field({caption: 'Phone'})

'Amounts'.panel()
'productsTotalExTax'.field({readOnly: true, caption: 'Products'})
'orderShipping'.field({readOnly: true, caption: "Shipping"})
'feesTotal'.field({readOnly: true, caption: "Fees"})
'totalTax'.field({readOnly: true, caption: "Tax"})
'orderTotal'.field({readOnly: true, caption: "Total"})

'Lines'.manifest()
'Add Line'.action({act: 'add'})
'SOLine'.datatype()
'sequence'.field()
'thumbnailImage'.field()
'description'.field({showAsLink: true})
'sku'.field()
'quantityOrdered'.field({readOnly: false, caption: 'Quantity'})
'quantityShipped'.field()
'quantityRemainingToShip'.field()
'unitPriceExTax'.field({caption: 'Unit Price (Excl Tax)', readOnly: false})
'lineTotalExclTax'.field({caption: 'Line Total (Excl Tax)'})
'lineTax'.field({caption: 'Tax'})
'valueIncTax'.field({caption: 'Line Total (Inc Tax)'})
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'SOManageLineMaint.js'.maintSpecname()

'SOManageMaint'.substituteCast(async (cast, maint) => {
  if ( maint.isAdding() ) return cast
  let res = cast
  if ( ! res )
    res = maint.callerCast({immediateOnly: true}) 
  if ( ! res ) return cast
  if ( ! res.datatype ) return cast
  if ( res.datatype() === 'SO') return res
  if ( res.datatype() === 'orders' ) {
    res = await 'SO'.bringSingle({order: res.reference()})
  } else if ( res.datatype() === 'order_items' ) {
    let order = await res.toOrder()
    if ( order )
      res = await 'SO'.bringSingle({order: order.reference()})
  }
  return res
})

'customer'.afterUserChange(async (oldInputValue, newInputValue, so) => {
  let cust = await so.referee('customer'); if ( ! cust ) return
  so.shippingFirstName = cust.shipping_first_name
  so.shippingLastName = cust.shipping_last_name
  so.shippingCompany = cust.shipping_company
  so.shippingAddress1 = cust.shipping_address_1
  so.shippingAddress2 = cust.shipping_address_2
  so.shippingCity = cust.shipping_city
  so.shippingState = cust.shipping_state
  so.shippingPostcode = cust.shipping_postcode
  so.shippingCountry = global.codeToCountry(cust.shipping_country)
  so.billingFirstName = cust.billing_first_name
  so.billingLastName = cust.billing_last_name
  so.billingCompany = cust.billing_company
  if ( ! so.billingCompany )
    so.billingCompany = cust.display_name
  so.billingAddress1 = cust.billing_address_1
  so.billingAddress2 = cust.billing_address_2
  so.billingCity = cust.billing_city
  so.billingState = cust.billing_state
  so.billingPostcode = cust.billing_postcode
  so.billingCountry = global.codeToCountry(cust.billing_country)
  so.billingEmail = cust.billing_email
  so.billingPhone = cust.billing_phone
})

'order'.visibleWhen((maint, so) => {
  return so.order && so.order.id && (so.order.id > 0)
})

'billingCountry'.dynamicOptions(async () => {
  return global.getCountryNames()
})

'shippingCountry'.dynamicOptions(async () => {
  return global.getCountryNames()
})

'SOManageMaint'.afterCreatingCast(async so => {
  so.manageInProfitori = 'Yes'
  //let order = await 'orders'.create()
  //so.order = order.reference()
})

'SOManageMaint'.afterInitialising(async so => {
  so.manageInProfitori = 'Yes'
})

'wcNiceStatus'.dynamicOptions(async maint => {
  let res = ['Pending', 'On-hold', 'Processing', 'Completed']
  let orders = await 'orders.RecentOrActive'.bring()
  for ( var i = 0; i < orders.length; i++ ) {
    let order = orders[i]
    let status = order.niceStatus
    if ( res.indexOf(status) >= 0 ) continue
    res.push(status)
  }
  return res
})

'Lines'.defaultSort({field: "sequence"})

'Lines'.filter(async (soLine, list) => {
  if ( ! soLine.parentSOLine ) return true
  let parentLine = await soLine.toTopParentSOLine(); if ( ! parentLine ) return false
  return parentLine.quantityToMake !== 0
})

'unitPriceExTax'.visibleWhen((maint, line) => {
  if ( ! line ) return true
  return line.impost ? false : true
})

'quantityOrdered'.visibleWhen((maint, line) => {
  if ( ! line ) return true
  return line.impost ? false : true
})

'quantityShipped'.visibleWhen((maint, line) => {
  if ( ! line ) return true
  return line.product ? true : false
})

'requestedETADate'.inception(global.emptyYMD())

'SOManageMaint'.makeDestinationFor('SO')

'description'.destination(async line => {
  if ( line.lineType === 'Product' )
    return await line.toProduct()
  return line
})

