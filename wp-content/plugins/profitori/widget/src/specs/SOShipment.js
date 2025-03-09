'SOShipment'.datatype()
'shipmentNumber'.field({key: true})
'order'.field({refersTo: "orders.RecentOrActive", indexed: true})
'shipmentDate'.field({date: true})
'shippingNameAndCompany'.field({caption: 'Ship To'})
'shippingAddress'.field()
'billingNameAndCompany'.field()
'billingEmailAndPhone'.field()
'localPickup'.field({yesOrNo: true})
'pickedUp'.field({yesOrNo: true})
'invoiced'.field({yesOrNo: true})
'businessName'.field()
'billingCompany'.field()
'pdfTitle'.field()
'salesOrder'.field({refersTo: 'SO'})
'originalSalesOrder'.field({refersTo: 'SO', indexed: true})
'return'.field({yesOrNo: true, caption: 'Return?'})
'returnCompleted'.field({yesOrNo: true, caption: 'Return Completed'})
'customerReference'.field()
'agentNames'.field()
'billingPhone'.field()
'customer'.field({refersTo: 'users', indexed: true})

/* eslint-disable no-cond-assign */

'SOShipment'.method('toPallets', async function() {
  return await 'Pallet'.bringChildrenOf(this)
})

'agentNames'.calculate( async s => {
  let order = await s.toOrder(); if ( ! order ) return ''
  let pieces = await 'Piece'.bring({order: order})
  let res = ''
  for ( var i = 0; i < pieces.length; i++ ) {
    let piece = pieces[i]
    let agent = await piece.referee('agent'); if ( ! agent ) continue
    global.appendWithSep(res, agent.agentName, ',')
  }
  return res
})

'customerReference'.calculate(async s => { 
  let so = await s.toSO(); if ( ! so ) return ''
  return so.customerReference
})

'salesOrder'.calculateWhen(async shipment => {
  let res = shipment.salesOrder ? false : true
  return res
})

'salesOrder'.calculate(async shipment => {
  let order = await shipment.toOrder(); if ( ! order ) return null
  let so = await order.toSO(); if ( ! so ) return null
  return so.reference()
})

'order'.calculateWhen(async shipment => {
  let res = shipment.salesOrder ? true : false
  return res
})

'order'.calculate(async shipment => {
  let so = await shipment.toSO(); if ( ! so ) return null
  let order = await so.toOrder(); if ( ! order ) return null
  return order.reference()
})

'SOShipment'.method('toOriginalSO', async function() {
  return await this.referee('originalSalesOrder')
})

'SOShipment'.method('maybeEmailInvoice', async function(options) {
  let credit = await this.toCredit(); 
  let exceptionOnError = options && options.exceptionOnError
  let ignoreError = options && options.ignoreError
  if ( ! credit ) return
  try {
    await credit.maybeEmail()
  } catch(e) {
    if ( ignoreError ) return
    if ( exceptionOnError )
      throw(e)
    global.gApp.showMessage("There was a problem sending the invoice via email:".translate() + " " + e.message)
  }
})

/*
'SOShipment'.beforeSaving(async function() {

  let generateSequentialInvoiceNumber = async () => {
    let res
    while ( true ) {
      let nextNo = await 'NextNumber'.bringOrCreate({forDatatype: 'Invoice'})  // Note: pseudo-datatype
      nextNo.number = nextNo.number + 1
      let noStr = nextNo.number + ""
      res = noStr.padStart(6, '0')
      let cr = await 'Credit'.bringFirst({creditNumber: res})
      if ( ! cr )
        break
    }
    return res
  }

  let createCredit = async () => {
    let order = await this.toOrder(); if ( ! order ) return null
    let customer = await order.toCustomer(); if ( ! customer ) return null
    let debtor = await customer.getOrCreateDebtor(); if ( ! debtor ) return null
    let creditNumber = this.shipmentNumber
    if ( global.confVal('sin') )
      creditNumber = await generateSequentialInvoiceNumber()
    let res = await 'Credit'.bringOrCreate({creditNumber: creditNumber})
    res.debtor = debtor.reference()
    res.creditType = 'Invoice'
    res.date = this.shipmentDate
    res.dueDate = await debtor.dateToDueDate(res.date)
    res.shipment = this.reference()
    res.order = order.reference()
    res.billingNameAndCompany = order.billingNameAndCompany
    res.billingAddress = order.billingAddress
    res.billingState = order._billing_state
    res.billingCountry = order._billing_country
    res.billingEmailAndPhone = order.billingEmailAndPhone
    res.ourBusinessName = order.ourBusinessName
    res.ourFromAddress = order.ourFromAddress
    res.ourContactDetails = order.ourContactDetails
    res.ourTaxId = order.ourTaxId
    res.ourCompanyRegistrationNumber = order.ourCompanyRegistrationNumber
    res.ourIBAN = order.ourIBAN
    res.shippingExclTaxFX = order._order_shipping
    res.feesExclTaxFX = order.fees_total
    res.shippingIncTaxFX = order.shippingIncTax
    res.feesIncTaxFX = order.feesIncTax
    res.taxFX = order._order_tax
    res.terms = order.terms
    res.paymentInstructions = order.paymentInstructions
    res.customerReference = order.customerReference
    await res.refreshEmailAddress()
    res.refreshFieldIndexes()
    return res
  }

  let createCreditLines = async credit => {
    let shipmentLines = await this.toLines()
    for ( var i = 0; i < shipmentLines.length; i++ ) {
      let shipmentLine = shipmentLines[i]
      let orderItem = await shipmentLine.toOrderItem(); if ( ! orderItem ) continue
      let creditLine = await 'CreditLine'.create({parentCast: credit}, {credit: credit.reference()})
      let product = await orderItem.toProduct(); if ( ! product ) continue
      creditLine.shipmentLine = shipmentLine.reference()
      creditLine.product = product.reference()
      creditLine.productUniqueName = product.uniqueName
      creditLine.productSku = product._sku
      creditLine.quantity = shipmentLine.quantityShipped
      creditLine.unitPriceExclTaxFX = await orderItem.toUnitPriceExclTax()
      creditLine.unitPriceFX = await orderItem.toUnitPrice()
      creditLine.lineValueExclTaxFX = await shipmentLine.toValueExclTax()
      creditLine.lineValueFX = await shipmentLine.toValue()
      creditLine.refreshFieldIndexes()
    }
  }

  let refreshCredit = async () => {
    let credit = await this.toCredit()
    if ( ! credit ) {
      if ( this.invoiced !== 'Yes' ) return
      credit = await createCredit(); if ( ! credit ) return
    }
    await credit.trashChildren()
    if ( this.invoiced === 'Yes' ) {
      let value = 0
      await createCreditLines(credit)
      if ( global.confVal('excludeTaxFromShipmentInvoices') === 'Yes' )
        value = await this.toLinesValueExclTax() + credit.shippingExclTaxFX + credit.feesExclTaxFX
      else
        value = await this.toLinesValue() + credit.shippingIncTaxFX + credit.feesIncTaxFX
      credit.amount = - value
    } else
      credit.amount = 0
  }

  let refreshSOFulfillStage = async () => {
    if ( this.propChanged('pickedUp') && (this.pickedUp === 'Yes') ) {
      let so = await this.toSO()
      if ( so.fulfillStage !== 'Waiting for Pickup' )
        return
      if ( await so.doQuantitiesShippedMatchOrdered() ) {
        so.fulfillStage = 'Shipped'
        await so.maybeAutoSetWCStatus()
      } else
        so.fulfillStage = 'Waiting – Partially Shipped'
    }
  }

  let maybeTrashSO = async () => {
    if ( ! this._markedForDeletion ) return
    if ( global.confVal('swo') !== 'Yes' ) return
    let so = await this.toSO(); if ( ! so ) return
    if ( so.isSecondary !== 'Yes' ) return
    await so.trash()
  }

  await refreshCredit()
  await refreshSOFulfillStage()
  await maybeTrashSO()
})
*/

'SOShipment'.method('isFullOrderShipment', async function() {
  let shLine; let shLines = await this.toLines()
  while ( shLine = shLines.__() ) {
    let oi = await shLine.toOrderItem(); if ( ! oi ) return false
    if ( oi._qty !== shLine.quantityShipped )
      return false
  }
  return true
})

'SOShipment'.beforeSaving(async function() {

  let generateSequentialInvoiceNumber = async () => {
    let res
    while ( true ) {
      let nextNo = await 'NextNumber'.bringOrCreate({forDatatype: 'Invoice'})  // Note: pseudo-datatype
      nextNo.number = nextNo.number + 1
      let noStr = nextNo.number + ""
      res = noStr.padStart(6, '0')
      let cr = await 'Credit'.bringFirst({creditNumber: res})
      if ( ! cr )
        break
    }
    return res
  }

  let createCredit = async () => {
    let order = await this.toOrder(); if ( ! order ) return null
    let customer = await order.toCustomer(); if ( ! customer ) return null
    let debtor = await customer.getOrCreateDebtor(); if ( ! debtor ) return null
    let creditNumber = this.shipmentNumber
    if ( global.confVal('sin') )
      creditNumber = await generateSequentialInvoiceNumber()
    let res = await 'Credit'.bringOrCreate({creditNumber: creditNumber})
    res.debtor = debtor.reference()
    res.creditType = 'Invoice'
    res.date = this.shipmentDate
    res.dueDate = await debtor.dateToDueDate(res.date)
    res.shipment = this.reference()
    res.order = order.reference()
    res.billingNameAndCompany = order.billingNameAndCompany
    res.billingAddress = order.billingAddress
    res.billingState = order._billing_state
    res.billingCountry = global.codeToCountry(order._billing_country)
    res.billingEmailAndPhone = order.billingEmailAndPhone
    res.ourBusinessName = order.ourBusinessName
    res.ourFromAddress = order.ourFromAddress
    res.ourContactDetails = order.ourContactDetails
    res.ourTaxId = order.ourTaxId
    res.ourCompanyRegistrationNumber = order.ourCompanyRegistrationNumber
    res.ourIBAN = order.ourIBAN
    res.shippingExclTaxFX = order._order_shipping
    res.feesExclTaxFX = order.fees_total
    res.shippingIncTaxFX = order.shippingIncTax
    res.feesIncTaxFX = order.feesIncTax
    res.taxFX = order._order_tax
    res.terms = order.terms
    res.paymentInstructions = order.paymentInstructions
    res.customerReference = order.customerReference
    await res.refreshEmailAddress()
    res.refreshFieldIndexes()
    return res
  }

  let createCreditLines = async credit => {
    let shipmentLines = await this.toLines()
    for ( var i = 0; i < shipmentLines.length; i++ ) {
      let shipmentLine = shipmentLines[i]
      let orderItem = await shipmentLine.toOrderItem(); if ( ! orderItem ) continue
      let creditLine = await 'CreditLine'.create({parentCast: credit}, {credit: credit.reference()})
      let product = await orderItem.toProduct(); if ( ! product ) continue
      creditLine.shipmentLine = shipmentLine.reference()
      creditLine.product = product.reference()
      creditLine.productUniqueName = product.uniqueName
      creditLine.productSku = product._sku
      creditLine.quantity = shipmentLine.quantityShipped
      creditLine.unitPriceExclTaxFX = await orderItem.toUnitPriceExclTax()
      creditLine.unitPriceFX = await orderItem.toUnitPrice()
      creditLine.lineValueExclTaxFX = await shipmentLine.toValueExclTax()
      creditLine.lineValueFX = await shipmentLine.toValue()
      creditLine.refreshFieldIndexes()
    }
  }

  let refreshCredit = async () => {
    let credit = await this.toCredit()
    if ( ! credit ) {
      if ( this.invoiced !== 'Yes' ) return
      credit = await createCredit(); if ( ! credit ) return
    }
    await credit.trashChildren()
    if ( this.invoiced === 'Yes' ) {
      let value = 0
      let tax = 0
      await createCreditLines(credit)
      let valueExclTax = await this.toLinesValueExclTax() + credit.shippingExclTaxFX + credit.feesExclTaxFX
      if ( global.confVal('excludeTaxFromShipmentInvoices') === 'Yes' ) {
        value = valueExclTax
      } else {
        let order = await this.toOrder()
        let isFullOrder = await this.isFullOrderShipment()
        if ( isFullOrder && order )  {
          value = order._order_total
          tax = order._order_tax
        } else {
          value = await this.toLinesValue() + credit.shippingIncTaxFX + credit.feesIncTaxFX
          tax = value - valueExclTax 
        }
      }
      credit.amount = - value
      credit.taxFX = tax
    } else {
      credit.amount = 0
      credit.taxFX = 0
    }
  }

  let refreshSOFulfillStage = async () => {
    if ( this.propChanged('pickedUp') && (this.pickedUp === 'Yes') ) {
      let so = await this.toSO()
      if ( so.fulfillStage !== 'Waiting for Pickup' )
        return
      if ( await so.doQuantitiesShippedMatchOrdered() ) {
        so.fulfillStage = 'Shipped'
        await so.maybeAutoSetWCStatus()
      } else
        so.fulfillStage = 'Waiting – Partially Shipped'
    }
  }

  let maybeTrashSO = async () => {
    if ( ! this._markedForDeletion ) return
    if ( global.confVal('swo') !== 'Yes' ) return
    let so = await this.toSO(); if ( ! so ) return
    if ( so.isSecondary !== 'Yes' ) return
    await so.trash()
  }

  await refreshCredit()
  await refreshSOFulfillStage()
  await maybeTrashSO()
})

'SOShipment'.method('toOrderRef', async function() {
  let order = await this.toOrder(); if ( ! order ) return null
  return order.reference()
})

'SOShipment'.method('toLinesValueExclTax', async function() {
  let lines = await this.toLines()
  let res = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    res += await line.toValueExclTax()
  }
  return res
})

'SOShipment'.method('toLinesValue', async function() {
  let lines = await this.toLines()
  let res = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    res += await line.toValue()
  }
  return res
})

'SOShipment'.method('toDebtor', async function() {
  let order = await this.toOrder(); if ( ! order ) return null
  return await order.toDebtor()
})

'SOShipment'.method('toCredit', async function() {
  return await 'Credit'.bringSingle({shipment: this.reference()})
})

'SOShipment'.method('toLines', async function() {
  let lines = await 'SOShipmentLine'.bringChildrenOf(this)
  return lines
})

'SOShipment'.method('toSO', async function() {
  if ( this.salesOrder )
    return await this.referee('salesOrder')
  let order = await this.toOrder(); if ( ! order ) return null
  return await order.toSO()
})

'SOShipment'.method('toOrder', async function() {
  if ( ! this.order ) {
    let so = await this.toSO()
    if ( so )
      return await so.toOrder()
    return null
  }
  let res = 'orders.RecentOrActive'.bringSingleFast({id: this.order.id})
  if ( global.fastFail(res) )
    res = await 'orders.RecentOrActive'.bringSingle({id: this.order.id})
  return res
})

'SOShipment'.method('toOrderFast', function() {
  if ( ! this.order )
    return 'na'
  let res = 'orders.RecentOrActive'.bringSingleFast({id: this.order.id})
  if ( global.fastFail(res) )
    return 'na'
  return res
})

'SOShipment'.method('isLastInOrderFast', function() {
  let shipments = 'SOShipment'.bringFast({order: this.order}, {force: true})
  if ( (! shipments) || (shipments === 'na') )
    return false
  shipments.sort((s1, s2) => (s1.shipmentDate + s1.shipmentNumber) > (s2.shipmentDate + s2.shipmentNumber) ? 1 : -1)
  return (this === shipments.last())
})

'SOShipment'.method('orderShipmentCountFast', function() {
  let shipments = 'SOShipment'.bringFast({order: this.order}, {force: true})
  return shipments.length
})

'SOShipment'.method('toCustomer', async function() {
  let order = await 'orders.RecentOrActive'.bringSingle({id: this.order.id}); if ( ! order ) return null
  return await order.toCustomer()
}) 

'pdfTitle'.calculate(po => {
  return 'SHIPPING NOTE'.translate()
})

'order'.destination(async shipment => {
  let order = await 'orders.RecentOrActive'.bringSingle({id: shipment.order.id})
  return order
})

'shippingNameAndCompany'.calculate(async shipment => {
  let order = shipment.toOrderFast()
  if ( global.fastFail(order) )
    order = await shipment.toOrder()
  if ( ! order ) return null
  return order.shippingNameAndCompany
})

'shippingAddress'.calculate(async shipment => {
  let order = shipment.toOrderFast()
  if ( global.fastFail(order) )
    order = await shipment.toOrder()
  if ( ! order ) return null
  return order.shippingAddress
})

'billingNameAndCompany'.calculate(async shipment => {
  let order = shipment.toOrderFast()
  if ( global.fastFail(order) )
    order = await shipment.toOrder()
  if ( ! order ) return null
  return order.billingNameAndCompany
})

'billingCompany'.calculate(async shipment => {
  let order = shipment.toOrderFast()
  if ( global.fastFail(order) )
    order = await shipment.toOrder()
  if ( ! order ) return null
  return order._billing_company
})

'billingPhone'.calculate(async shipment => {
  let order = shipment.toOrderFast()
  if ( global.fastFail(order) )
    order = await shipment.toOrder()
  if ( ! order ) return null
  return order._billing_phone
})

'billingEmailAndPhone'.calculate(async shipment => {
  let order = shipment.toOrderFast()
  if ( global.fastFail(order) )
    order = await shipment.toOrder()
  if ( ! order ) return null
  return order.billingEmailAndPhone
})

'businessName'.calculate(async po => {
  let c = 'Configuration'.bringSingleFast(); if ( ! c ) return null
  return c.businessName
})

