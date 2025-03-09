'PreorderMaint'.maint({panelStyle: "titled"})
'Add Preorder'.title({when: 'adding'})
'Edit Preorder'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Confirm Split'.action()
'Convert to WC Order'.action()
'Split'.action()
'Attachments'.action({act: 'attachments'})
'Add another'.action({act: 'add'})
'Preorder'.datatype()

'Order Summary'.panel()
'preorderNumber'.field({key: true})
'orderDate'.field({date: true})
'customer'.field({refersTo: 'users'/*, depictionField: 'billingNameAndCompany'*/})
'customerReference'.field({caption: 'Customer Reference'})
'requestedETADate'.field({date: true, allowEmpty: true, caption: 'Requested ETA Date'})
'notes'.field({multiLine: true})
'includeTaxOption'.field({yesOrNo: true, hidden: true})
'location'.field({refersTo: 'Location', allowEmpty: false, hidden: true})
'disposition'.field()
'userLogin'.field({caption: 'Created By', readOnly: true})

'Amounts'.panel()
'valueIncTax'.field({numeric: true, decimals: 2, hidden: true, caption: "Order Total (Inc Tax)"})
'valueExclTax'.field({numeric: true, decimals: 2, hidden: true, caption: "Order Total (Excl Tax)"})
'valueIncTaxFX'.field({numeric: true, decimals: 2, readOnly: true, caption: "Order Total (Inc Tax)"})
'valueExclTaxFX'.field({numeric: true, decimals: 2, hidden: true, caption: "Order Total (Excl Tax)"})
'tax'.field({numeric: true, decimals: 2, hidden: true})
'taxFX'.field({numeric: true, decimals: 2, readOnly: true, caption: "Tax"})
'exchangeRate'.field({numeric: true, decimals: 2, hidden: true})

'Lines'.manifest()
'Add Line'.action({act: 'add'})
'PreorderLine'.datatype()
'sequence'.field({readOnly: true})
'includeInSplit'.field({caption: 'Split', ephemeral: true, tickbox: true, allowInput: true, readOnly: false})
'thumbnailImage'.field()
'descriptionAndSKU'.field({showAsLink: true, readOnly: true})
'sku'.field({readOnly: true})
'quantity'.field({readOnly: false})
'unitValueIncTaxFX'.field({readOnly: false})
'unitValueExclTaxFX'.field({readOnly: false})
'lineValueExclTaxFX'.field({readOnly: true})
'lineValueIncTaxFX'.field({readOnly: true})
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'PreorderLineMaint.js'.maintSpecname()

'Lines'.defaultSort({field: "sequence"})

'customer'.excludeChoiceWhen( async (maint, user) => {
  return ! user.isCustomer()
})

'Preorder'.method('toCountryCode', async function() {
  let customer = await this.toCustomer(); if ( ! customer ) return null
  return customer.billing_country
})

'unitValueExclTaxFX'.visibleWhen((maint, line) => {
  if ( ! line ) return true
  return line.impost ? false : true
})

'unitValueIncTaxFX'.visibleWhen((maint, line) => {
  if ( ! line ) return true
  return line.impost ? false : true
})

'quantity'.visibleWhen((maint, preorderLine) => {
  if ( ! preorderLine ) return true
  return preorderLine.impost ? false : true
})

'includeInSplit'.visibleWhen(maint => {
  return maint.situation()._doingSplit
})

'Split'.availableWhen((cast, maint) => {
  return (! maint.situation()._doingSplit)
})

'Split'.act(async (maint, cast) => {
  maint.situation()._doingSplit = true
  maint.showMessage("Tick the lines to be split into a separate Preorder, then click 'Confirm Split'".translate())
})

'Confirm Split'.availableWhen((cast, maint) => {
  return maint.situation()._doingSplit
})

'Confirm Split'.act(async (maint, preorder) => {

  let line
  let newLine

  let copyProps = () => {
    for ( var prop in preorder ) {
      if ( prop === 'preorderNumber' ) continue
      if ( prop === 'id' ) continue
      if ( prop === 'userLogin' ) continue
      if ( preorder.propIsSystemProp(prop) ) continue
      if ( ! Object.prototype.hasOwnProperty.call(preorder, prop) ) continue
      preorder.copyProp(prop, preorder, newPreorder)
    }
  }

  let copyLineProps = () => {
    for ( var prop in line ) {
      if ( prop === 'preorder' ) continue
      if ( prop === 'id' ) continue
      if ( line.propIsSystemProp(prop) ) continue
      if ( ! Object.prototype.hasOwnProperty.call(line, prop) ) continue
      line.copyProp(prop, line, newLine)
    }
  }

  let getTickedLineCount = () => {
    let res = 0
    for ( var i = 0; i < lines.length; i++ ) {
      let line = lines[i]
      if ( line.includeInSplit !== 'Yes' ) continue
      res++
    }
    return res
  }

  let generateNewPreorderNumber = async () => {
    let res = preorder.preorderNumber
    let parts = res.split('-')
    let prefix = parts[0]
    let suffixNo = 2
    while ( true ) {
      let suffix = global.padWithZeroes(suffixNo, 2)
      res = prefix + '-' + suffix
      let otherPreorder = await 'Preorder'.bringSingle({preorderNumber: res})
      if ( ! otherPreorder ) break
      suffixNo++
    }
    return res
  }

  let lines = await 'PreorderLine'.bringChildrenOf(preorder)
  let tickedLineCount = getTickedLineCount()
  if ( tickedLineCount === 0 ) {
    maint.showMessage("Please tick at least one line before confirming the split".translate())
    return
  }
  if ( tickedLineCount === lines.length ) {
    maint.showMessage("Please untick at least one line before confirming the split".translate())
    return
  }
  let newPreorderNumber = await generateNewPreorderNumber()
  let newPreorder = await 'Preorder'.bringOrCreate({preorderNumber: newPreorderNumber})
  copyProps()
  for ( var i = 0; i < lines.length; i++ ) {
    line = lines[i]
    if ( line.includeInSplit !== 'Yes' ) continue
    newLine = await 'PreorderLine'.create({parentCast: newPreorder}, {preorder: newPreorder.reference()})
    copyLineProps()
    await line.trash()
  }
  maint.showMessage("Created new Preorder".translate() + ' ' + newPreorderNumber)
  maint.situation()._doingSplit = false
})

'Convert to WC Order'.act(async (maint, preorder) => {

  let preorderLineTypeToOrderItemType = preorderLineType => {
    if ( (! preorderLineType) || (preorderLineType === 'Product') )
      return 'line_item'
    return preorderLineType.toLowerCase()
  }

  let addOrderItems = async (order) => {
    let preorderLines = await preorder.toPreorderLines()
/*
    let fees = 0
    let fees_tax = 0
    let shipping = 0
    let shipping_tax = 0
*/
    for ( var i = 0; i < preorderLines.length; i++ ) {
      let preorderLine = preorderLines[i]
/*
      if ( preorderLine.lineType === 'Fee' ) {
        fees += preorderLine.lineValueExclTax
        fees_tax += preorderLine.lineTax
        continue
      } else if ( preorderLine.lineType === 'Shipping' ) {
        shipping += preorderLine.lineValueExclTax
        shipping_tax += preorderLine.lineTax
        continue
      }
*/
      let orderItem = await 'order_items'.create({parentCast: order})
      if ( preorderLine.product ) {
        let product = await preorderLine.referee('product')
        if ( product && product.isVariation() )
          orderItem._variation_id = product.id
        else
          orderItem._product_id = product.id
      }
      orderItem._qty = preorderLine.quantity
      orderItem._line_total = preorderLine.lineValueExclTax
      //orderItem._line_subtotal = orderItem._line_total
      orderItem._line_subtotal = preorderLine.unitPriceBDExTax * preorderLine.quantity
      orderItem._line_tax = preorderLine.lineTax
      orderItem._name = preorderLine.description
      orderItem.method_id = await preorderLine.toMethodId()
      orderItem.instance_id = await preorderLine.toInstanceId()
      orderItem.order_item_type = preorderLineTypeToOrderItemType(preorderLine.lineType)
      orderItem.order_status = order.status
      orderItem.refreshFieldIndexes()
    }
/*
    order._order_shipping = shipping
    order._order_shipping_tax = shipping_tax
    order.fees_total = fees
    order.fees_tax = fees_tax
*/
  }

  let doIt = async () => {
    let order = await 'orders'.create()
    order.order_date = global.todayYMD()
    order.status = "wc-processing"
    order.customerReference = preorder.customerReference
    let customer = await preorder.referee('customer')
    if ( customer ) {
      order._customer_user = customer.user_id
      order._shipping_first_name = customer.shipping_first_name
      order._shipping_last_name = customer.shipping_last_name
      order._shipping_company = customer.shipping_company
      order._shipping_address_1 = customer.shipping_address_1
      order._shipping_address_2 = customer.shipping_address_2
      order._shipping_city = customer.shipping_city
      order._shipping_state = customer.shipping_state
      order._shipping_postcode = customer.shipping_postcode
      order._shipping_country = customer.shipping_country
      order._billing_first_name = customer.billing_first_name
      order._billing_last_name = customer.billing_last_name
      order._billing_company = customer.billing_company
      order._billing_address_1 = customer.billing_address_1
      order._billing_address_2 = customer.billing_address_2
      order._billing_city = customer.billing_city
      order._billing_state = customer.billing_state
      order._billing_postcode = customer.billing_postcode
      order._billing_country = customer.billing_country
      order._billing_email = customer.billing_email
      order._billing_phone = customer.billing_phone
    }
    order.refreshFieldIndexes()
    await addOrderItems(order)
    await preorder.trash()
    let err = await global.foreman.doSave()
    if ( err ) {
      maint.showMessage("There was a problem saving your data: " + err)
      return
    }
    maint.showMessage("New WC Order was created")
    maint.back()
  }

  await global.foreman.doSave()
  maint.showMessage('Are you sure you want to convert this Preorder to a WC order?',
    {yesNo: true, onYes: doIt}
  )
})

'userLogin'.inception(async () => {
  let sess = await 'session'.bringSingle(); if ( ! sess ) return '';
  return sess.user
})

'disposition'.options(['Firm', 'Forecast Only'])

'disposition'.inception('Firm')

'requestedETADate'.inception(global.emptyYMD())

'Preorder'.method('toPreorderLines', async function() {
  return await 'PreorderLine'.bringChildrenOf(this)
})

'Preorder'.afterCreating(async function () {
  let c = await 'Configuration'.bringFirst(); if ( ! c ) return
  this.includeTaxOption = c.preIncTax
})

'lineValueExclTaxFX'.columnVisibleWhen((maint, preorder) => {
  return preorder.includeTaxOption === "No"
})

'lineValueIncTaxFX'.columnVisibleWhen((maint, preorder) => {
  return preorder.includeTaxOption !== "No"
})

'unitValueExclTaxFX'.columnVisibleWhen((maint, preorder) => {
  return preorder.includeTaxOption === "No"
})

'unitValueIncTaxFX'.columnVisibleWhen((maint, preorder) => {
  return preorder.includeTaxOption !== "No"
})

'exchangeRate'.inception(1)

'Preorder'.method("hasLines", async function () {
  let lines = await 'PreorderLine'.bringChildrenOf(this)
  return lines.length > 0
})

'Preorder'.method('toCustomer', async function() {
  return await this.referee('customer')
})

'valueIncTax'.calculate(async (preorder) => {
  let lines = await 'PreorderLine'.bringChildrenOf(preorder)
  let res = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    let lineValueIncTax = await line.calcLineValueIncTax()
    res += lineValueIncTax
  }
  return res
})

'valueExclTax'.calculate(async (preorder) => {
  let lines = await 'PreorderLine'.bringChildrenOf(preorder)
  let res = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    let lineValueExclTax = await line.calcLineValueExclTax()
    res += lineValueExclTax
  }
  return res
})

'valueExclTaxFX'.calculate(async (preorder) => {
  let lines = await 'PreorderLine'.bringChildrenOf(preorder)
  let res = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    let lineValueExclTax = await line.calcLineValueExclTaxFX()
    res += lineValueExclTax
  }
  return res
})

'valueIncTaxFX'.calculate(async (preorder) => {
  let lines = await 'PreorderLine'.bringChildrenOf(preorder)
  let res = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    let lineValueIncTax = await line.calcLineValueIncTaxFX()
    res += lineValueIncTax
  }
  return res
})

'tax'.calculate(async (preorder) => {
  let lines = await 'PreorderLine'.bringChildrenOf(preorder)
  let res = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    let lineTax = await line.getLineTaxUnrounded()
    res += lineTax
  }
  return res
})

'taxFX'.calculate(async (preorder) => {
  let lines = await 'PreorderLine'.bringChildrenOf(preorder)
  let res = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    let lineTax = await line.getLineTaxFXUnrounded()
    res += lineTax
  }
  return res
})

'PreorderMaint'.whenAdding(async function() {

  let defaultNumber = async () => {
    let nextNo = await 'NextNumber'.bringOrCreate({forDatatype: 'Preorder'})
    nextNo.number = nextNo.number + 1
    let noStr = nextNo.number + ""
    let adjNo = "PR" + noStr.padStart(5, '0')
    this.setFieldValue('preorderNumber', adjNo)
  }

  await defaultNumber()

})

'location'.inception(async po => {
  let loc = await 'Location'.bringSingle({locationName: 'General'})
  return loc.reference()
})

'Preorder'.beforeSaving(async function() {
  await this.refreshQuantitiesOnPreorders()
})

'Preorder'.method('refreshQuantitiesOnPreorders', async function() {
  let lines = await this.toPreorderLines()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    let inv = await line.toInventory(); if ( ! inv ) continue
    await inv.refreshQuantityOnPreorders()
  }
})

'PreorderMaint'.makeDestinationFor('Preorder')

'descriptionAndSKU'.destination(async preorderLine => {
  if ( preorderLine.lineType === 'Product' )
    return await preorderLine.toProduct()
  return preorderLine
})

'Preorder'.method('toLocation', async function() {
  let res = await this.referee('location')
  return res
})

'Preorder'.method('toLocationName', async function() {
  let loc = await this.toLocation(); if ( ! loc ) return 'General'
  let res = loc.locationName
  if ( ! res )
    res = 'General'
  return res
})

