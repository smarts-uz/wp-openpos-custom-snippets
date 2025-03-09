'SO'.datatype({caption: 'Sales Order'})
'order'.field({refersToParent: "orders.RecentOrActive", key: true, populatedLate: true})
'shipToStateAndCountry'.field()
'shipToPostalCode'.field()
'shipFromLocation'.field({refersTo: 'Location'})
'shippingNameAndCompany'.field()
'shippingAddress'.field()
'shippingEmailAndPhone'.field()
'packable'.field()
'fulfillStage'.field()
'priority'.field()
'notes'.field({multiLine: true, caption: 'Our Notes'})
'orderDate'.field({date: true})
'wcNiceStatus'.field({translateOnDisplay: true, caption: 'WC Status'})
'retired'.field({yesOrNo: true})
'journaledShippingAmount'.field({numeric: true, decimals: 2, preserveOnTrash: true})
'journaledShippingLocation'.field({refersTo: 'Location'})
'journaledFeesAmount'.field({numeric: true, decimals: 2, preserveOnTrash: true})
'journaledFeesLocation'.field({refersTo: 'Location'})
'journaledTaxAmount'.field({numeric: true, decimals: 2, preserveOnTrash: true})
'journaledTaxLocation'.field({refersTo: 'Location'})
'journaledDate'.field({date: true, allowEmpty: true})
'latestShipmentNumber'.field()
'financeStatus'.field()
'valueRemainingToBeInvoiced'.field({numeric: true, decimals: 2})
'shipmentMethod'.field()
'manageInProfitori'.field({yesOrNo: true})
'customerReference'.field()
'requestedETADate'.field({date: true, allowEmpty: true, caption: 'Requested ETA Date'})
'shippingFirstName'.field()
'shippingLastName'.field()
'shippingCompany'.field()
'shippingAddress1'.field({caption: 'Shipping Address'})
'shippingAddress2'.field({caption: 'Shipping Address Line 2'})
'shippingCity'.field()
'shippingState'.field()
'shippingPostcode'.field()
'shippingCountry'.field()
'billingFirstName'.field()
'billingLastName'.field()
'billingCompany'.field()
'billingAddress1'.field({caption: 'Billing Address'})
'billingAddress2'.field({caption: 'Billing Address Line 2'})
'billingCity'.field()
'billingState'.field()
'billingPostcode'.field()
'billingCountry'.field({allowEmpty: true})
'billingEmail'.field()
'billingPhone'.field()
'orderCurrency'.field({refersTo: 'Currency', allowEmpty: true})
'cartDiscount'.field({numeric: true, caption: "Discount (Excl Tax)", decimals: 2})
'cartDiscountTax'.field({numeric: true, caption: "Discount Tax", decimals: 2})
'orderShipping'.field({numeric: true, caption: "Shipping (Excl Tax)", decimals: 2})
'orderShippingTax'.field({numeric: true, caption: "Shipping Tax", decimals: 2})
'feesTotal'.field({numeric: true, caption: "Fees (Excl Tax)", decimals: 2})
'feesTax'.field({numeric: true, caption: "Fees Tax", decimals: 2})
'productsTotalExTax'.field({numeric: true, decimals: 2, caption: 'Products Total (Excl Tax)'})
'totalTax'.field({numeric: true, decimals: 2})
'orderTotal'.field({numeric: true, caption: "Total Amount", decimals: 2})
'paymentMethodTitle'.field({caption: 'Payment Method'})
'customer'.field({refersTo: 'users'})
'linesVersion'.field({numeric: true})
'trackingNumbers'.field()
'isSecondary'.field({yesOrNo: true})
'originalSalesOrder'.field({refersTo: 'SO', indexed: true})
'singleShipment'.field({refersTo: 'SOShipment', caption: 'Shipment'})
'suppressEmail'.field({yesOrNo: true})

/* eslint-disable no-cond-assign */

'SO'.validate(async function() {
  
  let validateEffectOnStock = async () => {
    if ( global.confVal('pns') !== 'Yes' ) return
    let soLine; let soLines = await this.toSOLines()
    while ( soLine = soLines.__() ) {
      await soLine.validateEffectOnStock()
    }
  }

  if ( this.manageInProfitori !== 'Yes' ) return
  if ( global.harmonizing ) return
  await validateEffectOnStock()
})

'wcNiceStatus'.inception('Pending')

'customer'.excludeChoiceWhen( async (maint, user) => {
  return ! user.isCustomer()
})

'SO'.method('toOriginalOrder', async function() {
  let originalSO = await this.referee('originalSalesOrder'); if ( ! originalSO ) return null
  let res = await originalSO.toOrder()
  return res
})

'SO'.method('toFirstShipmentNumber', async function() {
  let shipment = await this.toFirstShipment(); if ( ! shipment ) return '';
  return shipment.shipmentNumber;
})

'SO'.method('toFirstShipmentId', async function() {
  let shipment = await this.toFirstShipment(); if ( ! shipment ) return null
  return shipment.id;
})

'SO'.method('toFirstShipment', async function() {
  return await 'SOShipment'.bringFirst({salesOrder: this})
})

'SO'.method('refreshSecondarySOs', async function() {

  let refreshSOQuantities = async () => {
    this.manageInProfitori = 'Yes'
    let soLines = await this.getLines()
    for ( var i = 0; i < soLines.length; i++ ) {
      let soLine = soLines[i]
      let origQuantityOrdered = soLine.quantityOrdered
      soLine.quantityOrdered = soLine.quantityRemainingToShip
      soLine.quantityShipped = 0
      soLine.quantityToPick = 0
      soLine.lineTotalExclTax = soLine.quantityOrdered * soLine.unitPriceExTax
      soLine.lineTotalBDExclTax = soLine.quantityOrdered * soLine.unitPriceBDExTax
      soLine.lineTax = soLine.lineTax * (soLine.quantityOrdered / origQuantityOrdered)
      await soLine.refreshPackable()
    }
    await this.copyToWCOrder()
  }

  let moveLineAllotments = async (newSOLine, origSOLine) => {
    if ( newSOLine.hasLots !== 'Yes' ) return
    let allotments = await origSOLine.toAllotments()
    for ( var i = 0; i < allotments.length; i++ ) {
      let allotment = allotments[i]
      if ( ! allotment.lot ) continue
      if ( allotment.lot.keyval === 'Unspecified' ) continue
      let newAllotment = await 'Allotment'.create({parentCast: newSOLine}, {allotmentParent: newSOLine.reference()})
      newAllotment.product = global.copyObj(allotment.product)
      newAllotment.lot = global.copyObj(allotment.lot)
      newAllotment.quantity = allotment.quantity
      newAllotment.quantityUpdatedToOnHand = allotment.quantityUpdatedToOnHand
      newAllotment.quantityUpdatedToTransaction = allotment.quantityUpdatedToTransaction
      newAllotment.expiryDate = allotment.expiryDate
      newAllotment.supplierLotNumber = allotment.supplierLotNumber
      newAllotment.refreshIndexes()
      await allotment.maybeUpdateTransactionAllotment(newAllotment)
      allotment.quantity = 0
      allotment.quantityUpdatedToOnHand = 0
      allotment.quantityUpdatedToTransaction = 0
      await allotment.trash()
    }
    newSOLine.manuallyAllotted = origSOLine.manuallyAllotted
    origSOLine.manuallyAllotted = 'No'
    await newSOLine.refreshUnspecifiedLot()
    await origSOLine.refreshUnspecifiedLot()
  }

  let moveAllotments = async newSO => {
    let newLines = await newSO.getLines()
    for ( var i = 0; i < newLines.length; i++ ) {
      let newLine = newLines[i]
      let origLine = await newLine.referee('splitFromSOLine'); if ( ! origLine ) continue
      await moveLineAllotments(newLine, origLine)
      origLine.manuallyAllotted = 'No'
    }
  }

  let thereAreQuantitiesRemainingToShip = async() => {
    let soLines = await this.getLines()
    for ( var i = 0; i < soLines.length; i++ ) {
      let soLine = soLines[i]
      await soLine.refreshCalculations({force: true})
      if ( soLine.quantityRemainingToShip > 0 ) 
        return true
    }
    return false
  }
  
  let setSOToShipped = async so => {
    if ( so.shipmentMethod === 'Local Pickup' )
      so.fulfillStage = 'Waiting for Pickup'
    else
      so.fulfillStage = 'Shipped'
    let status = global.confVal('scs')
    if ( status ) {
      so.wcNiceStatus = status
      await so.refreshWCOrderStatus()
    } else {
      if ( global.confVal('autoCompleteWCOrders') === 'Yes' ) {
        so.wcNiceStatus = 'Completed'
        await so.refreshWCOrderStatus()
      }
    }
  }

  let syncSecondarySOsToShipments = async () => {
    let shipments = await 'SOShipment'.bring({originalSalesOrder: this})
    for ( var i = 0; i < shipments.length; i++ ) {
      let shipment = shipments[i]
      let secondarySO = await shipment.toSO(); if ( ! secondarySO ) continue
      if ( secondarySO.id === this.id )
        continue
      await secondarySO.syncToShipment()
    }
  }

  if ( global.confVal('swo') !== 'Yes' ) return
  this.manageInProfitori = 'Yes'
  if ( ! await thereAreQuantitiesRemainingToShip() ) {
    await setSOToShipped(this)
    await syncSecondarySOsToShipments()
    return
  }

  await refreshSOQuantities()
  let order = await this.toOrder(); if ( ! order ) return ''
  let shipments = await 'SOShipment'.bring({originalSalesOrder: this})
  for ( var i = 0; i < shipments.length; i++ ) {
    let shipment = shipments[i]
    let secondarySO = await shipment.toSO()
    if ( secondarySO && (secondarySO.id === this.id) )
      secondarySO = null
    let isNew = false
    if ( ! secondarySO ) {
      isNew = true
      secondarySO = await 'SO'.create()
      secondarySO.isSecondary = 'Yes'
      secondarySO.originalSalesOrder = this.reference()
      secondarySO.manageInProfitori = 'Yes'
      secondarySO.wcNiceStatus = this.wcNiceStatus
      shipment.salesOrder = secondarySO.reference()
      shipment.refreshIndexes()
      shipment.refreshCalculations({force: true})
      await this.refreshShipmentOrderIds(shipment, order)
    }
    await secondarySO.syncToShipment()
    if ( isNew ) {
      await moveAllotments(secondarySO)
      await setSOToShipped(secondarySO)
      //this.latestShipmentNumber = null
      this.singleShipment = null
      await this.copyToWCOrder()
    }
  }
  
})

'SO'.method('refreshShipmentOrderIds', async function(shipment, order) {
  if ( ! shipment )
    shipment = await this.toFirstShipment() 
  if ( ! shipment ) return
  shipment.order = order.reference()
  let shipmentLines = await shipment.toLines()
  for ( var i = 0; i < shipmentLines.length; i++ ) {
    let shipmentLine = shipmentLines[i]
    shipmentLine.orderId = order.order_id
    shipmentLine.order = order.reference()
  }
})

'trackingNumbers'.calculate(so => {

  let slow = async () => {
    let order = await so.toOrder(); if ( ! order ) return ''
    return order.trackingNumbers
  }
 
  let order = so.toOrderFast()
  if ( global.fastFail(order) )
    return slow()
  return order.trackingNumbers
})

'SO'.method('maybeDefaultShipmentMethod', async function() {
  
  let getFirstShippingLine = async () => {
    let lines = await this.getAllLines()
    let res = lines.filterSingle(line => line.lineType === 'Shipping')
    return res
  }

  if ( this.shipmentMethod ) return
  let line = await getFirstShippingLine(); if ( ! line ) return
  let method = await line.referee('shippingMethod'); if ( ! method ) return
  let title = (method.title + '').toLowerCase()
  let methodTitle = (method.methodTitle + '').toLowerCase()
  if ( (title === 'local pickup') || (methodTitle === 'local pickup') )
    this.shipmentMethod = 'Local Pickup'
})

'SO'.afterRetrieving(async function() {
  if ( this.retired === 'Yes' ) return
  if ( ! this.productsTotalExTax ) { // i.e. legacy SO
    await this.refreshTotals()
  }
})

'SO'.afterRetrievingFast(function() {
  if ( this.retired === 'Yes' ) return true
  if ( ! this.productsTotalExTax ) { // i.e. legacy SO
    this.refreshTotalsFast()
  }
  return true
})

'SO'.method('refreshTotals', async function() {
  if ( this.__refreshingTotals ) return
  this.__refreshingTotals = true
  let lines = await this.getAllLines()
  this.refreshTotalsFromLines(lines)
  this.__refreshingTotals = false
})

'SO'.method('refreshTotalsFast', function() {
  if ( this.__refreshingTotals ) return
  this.__refreshingTotals = true
  let lines = this.getAllLinesFast()
  if ( global.fastFail(lines) ) {
    this.__refreshingTotals = false
    return 'na'
  }
  this.refreshTotalsFromLines(lines)
  this.__refreshingTotals = false
})

'SO'.method('refreshTotalsFromLines', function(lines) {
  let so = this
  so.orderShipping = 0
  so.orderShippingTax = 0
  so.feesTotal = 0
  so.feesTax = 0
  so.productsTotalExTax = 0
  so.totalTax = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    let lineType = line.lineType || 'Product'
    so.orderShipping += lineType === 'Shipping' ? line.lineTotalExclTax : 0
    so.orderShippingTax += lineType === 'Shipping' ? line.lineTax : 0
    so.feesTotal += lineType === 'Fee' ? line.lineTotalExclTax : 0
    so.feesTax += lineType === 'Fee' ? line.lineTax : 0
    so.productsTotalExTax += lineType === 'Product' ? line.lineTotalExclTax : 0
    so.totalTax += line.lineTax
  }
})

'orderTotal'.calculate(so => {
  let res = so.productsTotalExTax + so.orderShipping + so.feesTotal + so.totalTax - so.cartDiscount
  return res
})

/*
'totalTax'.calculate(async so => {
  let lines = so.getAllLinesFast()
  if ( global.fastFail(lines) ) {
    lines = await so.getAllLines()
    if ( global.tc ) global.tc('totalTaxSlow')
  }
  return lines.sum(line => line.lineTax)
})

'feesTotal'.calculate(async so => {
  let lines = so.getAllLinesFast()
  if ( global.fastFail(lines) )
    lines = await so.getAllLines()
  return lines.sum(line => line.lineType === 'Fee' ? line.lineTotalExclTax : 0)
})

'feesTax'.calculate(async so => {
  let lines = so.getAllLinesFast()
  if ( global.fastFail(lines) )
    lines = await so.getAllLines()
  return lines.sum(line => line.lineType === 'Fee' ? line.lineTax : 0)
})

'orderShipping'.calculate(async so => {
  let lines = so.getAllLinesFast()
  if ( global.fastFail(lines) )
    lines = await so.getAllLines()
  return lines.sum(line => line.lineType === 'Shipping' ? line.lineTotalExclTax : 0)
})

'orderShippingTax'.calculate(async so => {
  let lines = so.getAllLinesFast()
  if ( global.fastFail(lines) )
    lines = await so.getAllLines()
  return lines.sum(line => line.lineType === 'Shipping' ? line.lineTax : 0)
})
*/

'orderDate'.inception(
  global.todayYMD()
)

'SO'.method('copyToWCOrder', async function() {

  let copyHeader = async order => {
    let originalOrder = await this.toOriginalOrder()
    order.manageInProfitori = this.manageInProfitori
    order.suppressEmail = this.suppressEmail
    order.order_date = this.orderDate
    order._billing_first_name = this.billingFirstName
    order._billing_last_name = this.billingLastName
    order._billing_company = this.billingCompany
    order._billing_address_1 = this.billingAddress1
    order._billing_address_2 = this.billingAddress2
    order._billing_city = this.billingCity
    order._billing_state = this.billingState
    order._billing_postcode = this.billingPostcode
    order._billing_country = global.countryToCode(this.billingCountry)
    order._billing_email = this.billingEmail
    order._billing_phone = this.billingPhone
    order._shipping_first_name = this.shippingFirstName
    order._shipping_last_name = this.shippingLastName
    order._shipping_company = this.shippingCompany
    order._shipping_address_1 = this.shippingAddress1
    order._shipping_address_2 = this.shippingAddress2
    order._shipping_city = this.shippingCity
    order._shipping_state = this.shippingState
    order._shipping_postcode = this.shippingPostcode
    order._shipping_country = global.countryToCode(this.shippingCountry)
    order._order_currency = this.orderCurrency
    order._cart_discount = this.cartDiscount
    order._cart_discount_tax = this.cartDiscountTax
    order._order_shipping = this.orderShipping
    order._order_shipping_tax = this.orderShippingTax
    order._order_tax = this.totalTax
    order._order_total = this.orderTotal
    order.customerReference = this.customerReference
    let customer = await this.toCustomer()
    order._customer_user = customer ? customer.id : 0
    order.shipmentMethod = this.shipmentMethod
    order.originalOrderId = originalOrder ? originalOrder.id : null
    //order.status = originalOrder ? originalOrder.status : null
    order.shipmentNumber = await this.toFirstShipmentNumber()
    order.shipmentId = await this.toFirstShipmentId()
    order.refreshIndexes()
  }

  let soLineTypeToOrderItemType = soLineType => {
    if ( (! soLineType) || (soLineType === 'Product') )
      return 'line_item'
    return soLineType.toLowerCase()
  }

  let copyLine = async (soLine, orderItem) => {
    let product = await soLine.toProduct()
    if ( product ) {
      if ( product.isVariation() ) {
        orderItem._variation_id = product.id
      } else {
        orderItem._product_id = product.id
      }
    }
    orderItem._qty = soLine.quantityOrdered
    orderItem._line_total = soLine.lineTotalExclTax
    orderItem._line_subtotal = soLine.lineTotalBDExclTax
    orderItem._line_tax = soLine.lineTax
    orderItem._name = soLine.description
    orderItem.order_item_type = soLineTypeToOrderItemType(soLine.lineType)
    orderItem.method_id = await soLine.toMethodId()
    orderItem.instance_id = await soLine.toInstanceId()
    //orderItem.manageInProfitori = 'Yes'
    orderItem.refreshIndexes()
  }

  let markOrderItemsAsUnseen = async items => {
    for ( var i = 0; i < items.length; i++ ) {
      let item = items[i]
      item.__seen = false
    }
  }

  let trashUnseenOrderItems = async items => {
    for ( var i = 0; i < items.length; i++ ) {
      let item = items[i]
      if ( ! item.__seen ) {
        await item.trash()
      }
    }
  }

  let copyLines = async order => {
    let items = await 'order_items.RecentOrActive'.bring({parentId: order.id})
    await markOrderItemsAsUnseen(items)
    let soLines = await this.getAllLines()
    for ( var i = 0; i < soLines.length; i++ ) {
      let soLine = soLines[i]
      if ( soLine.parentSOLine ) continue
      let orderItem
      if ( soLine.order_item_id ) {
        orderItem = items.filterSingle(item => item.id === soLine.order_item_id)
        if ( ! orderItem ) {
          console.log('Missing order_item ' + soLine.order_item_id)
          continue
        }
      } else {
        orderItem = await 'order_items'.create({parentId: order.id}, {theOrder: order.reference()})
      }
      orderItem.__seen = true
      soLine.order_item_id = orderItem.id
      await copyLine(soLine, orderItem)
    }
    await trashUnseenOrderItems(items)
  }

  let deleteOrder = async () => {
    let order = await this.toOrder(); if ( ! order ) return
    await order.trash()
  }

  if ( this._markedForDeletion ) {
    await deleteOrder()
    return
  }
  let order = await this.toOrder();
  if ( ! order ) {
    order = await 'orders'.create()
    this.order = order.reference()
  }
  await copyHeader(order)
  await copyLines(order)
  await this.refreshShipmentOrderIds(null, order)
  await this.refreshWCOrderStatus()
})

'orderDate'.calculateWhen(async so => {
  return so.manageInProfitori !== 'Yes'
})

'SO'.method('refreshDrop', async function() {
  let so = this
  so.shipmentMethod = 'Drop Shipment'
  let lines = await this.getLines()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    await line.refreshDrop()
  }
  if ( await this.isFullyShipped() ) {
    so.fulfillStage = 'Shipped'
    await so.refreshShipment()
    await so.refreshPackable()
    this.wcNiceStatus = 'Completed'
    await this.refreshWCOrderStatus()
  }
})

'SO'.method('maybeAutoSetFulfillStage', async function() {
  if ( this.shipmentMethod !== 'Local Pickup' ) return
  if ( ! this.anyQuantityShippedChanged() ) return
  this.fulfillStage = 'Waiting for Pickup'
  let mold = global.foreman.doNameToMold('SO')
  mold.version++
})

'SO'.method('anyQuantityShippedChanged', async function() {
  let lines = await this.getLines()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    if ( line.propChanged('quantityShipped') )
      return true
  }
  return false
})

'shipmentMethod'.options(['Local Pickup', 'Shipping Carrier', 'Drop Shipment'])

'SO'.method('refreshValueRemainingToBeInvoiced', async function() {
  let order = await this.toOrder(); if ( ! order ) return
  this.valueRemainingToBeInvoiced = order._order_total
  let credits = await 'Credit'.bring({order: order})
  for ( var i = 0; i < credits.length; i++ ) {
    let credit = credits[i]
    this.valueRemainingToBeInvoiced += credit.amount
  }
})

'financeStatus'.calculate(async so => {
  let order = await so.toOrder(); if ( ! order ) return 'N/A'
  let debtor = await order.toDebtor()
  if ( ! debtor )
    return 'N/A'
  let avail = debtor.balance + debtor.creditLimit
  if ( avail <= 0 )
    return 'Unfinanced'
  let value = await so.valueRemainingToBeInvoiced
  if ( avail >= value )
    return 'Financed'
  return 'Partially Financed'
})

'SO'.method('toValue', async function() {
  let order = await this.toOrder(); if ( ! order ) return
  return order._order_total
})

'SO'.method('toDebtor', async function() {
  let order = await this.toOrder(); if ( ! order ) return
  return await order.toDebtor()
})

'SO'.method('trashLastShipment', async function() {
  let shipment = await this.toLastShipment(); if ( ! shipment ) return null
  await shipment.trash()
})

'SO'.method('toSecondLastShipmentNumber', async function() {
  let shipment = await this.toSecondLastShipment(); if ( ! shipment ) return null
  return shipment.shipmentNumber
})

'SO'.method('toSecondLastShipment', async function() {
  let shipments = await 'SOShipment'.bring({originalSalesOrder: this.originalSalesOrder})
  shipments.sort((s1, s2) => (s1.shipmentDate + s1.shipmentNumber) > (s2.shipmentDate + s2.shipmentNumber) ? 1 : -1)
  if ( shipments.length < 2 )
    return null
  return shipments[shipments.length - 2]
})

'SO'.method('toLastShipment', async function() {
  let shipments = await 'SOShipment'.bring({order: this.order})
  shipments.sort((s1, s2) => (s1.shipmentDate + s1.shipmentNumber) > (s2.shipmentDate + s2.shipmentNumber) ? 1 : -1)
  return shipments.last()
})

'SO'.method('toCustomer', async function() {
  if ( this.manageInProfitori === 'Yes' ) {
    return await this.referee('customer')
  }
  let order = await this.toOrder(); if ( ! order ) return null
  return await order.toCustomer()
}) 

'SO'.method('toCustomerFast', function() {
  if ( this.manageInProfitori === 'Yes' ) {
    return this.refereeFast('customer')
  }
  let order = this.toOrderFast(); 
  if ( global.fastFail(order) ) return 'na'
  return order.toCustomerFast()
}) 

'SO'.method('refreshLatestShipmentNumber', async function() {
  let so = this

  let shipmentToSuffix = (shipment) => {
    let no = shipment.shipmentNumber
    let parts = no.split('-')
    if ( parts.length < 2 ) return "00"
    return parts[parts.length - 1]
  }

  let incSuffix = (aSuffix) => {
    let no = Number(aSuffix) + 1 + ""
    return no.padStart(2, '0')
  }

  let shipmentsToOneWithMaxShipmentNumber = shipments => {
    let res
    let max = ''
    shipments.forAll(shipment => {
      let no = shipment.shipmentNumber; if ( ! no ) return 'continue'
      if ( no <= max ) return 'continue'
      max = no
      res = shipment
    })
    return res
  }

  let shipment
  if ( so.latestShipmentNumber )
    shipment = await 'SOShipment'.bringSingle({shipmentNumber: so.latestShipmentNumber})
  let needNew = (! shipment) || (shipment.shipmentDate !== global.todayYMD()) || shipment.invoiced === 'Yes'
  if ( ! needNew )
    return
  let shipments = await 'SOShipment'.bring({originalSalesOrder: so})
  shipment = shipmentsToOneWithMaxShipmentNumber(shipments)
  let suffix = "01"
  if ( shipment ) {
    suffix = shipmentToSuffix(shipment)
    suffix = incSuffix(suffix)
  }
  so.latestShipmentNumber = so.order.id + "-" + suffix
})

'SO'.method('refreshShipment', async function(options) {

  let soLineToQtyShippedOnOtherShipments = async soLine => {
    let res = 0
    let shipmentLines = await 'SOShipmentLine'.bring({soLine: soLine})
    for ( var i = 0; i < shipmentLines.length; i++ ) {
      let shipmentLine = shipmentLines[i]
      let shipmentNumber = await shipmentLine.toShipmentNumber()
      if ( shipmentNumber === this.latestShipmentNumber ) continue
      res += shipmentLine.quantityShipped
    }
    return res
  }

  let linesHaveQuantityShipped = soLines => {
    let line
    while ( line = soLines.__() ) {
      if ( line.quantityShipped )
        return true
    }
    return false
  }

  let mergeAllotment = async (toLine, fromAllotment) => {
    let toAllotment = await toLine.lotRefToAllotment(fromAllotment.lot)
    if ( ! toAllotment ) {
      toAllotment = await 'Allotment'.create({parentCast: toLine}, {allotmentParent: toLine.reference()})
      toAllotment.product = global.copyObj(fromAllotment.product)
      toAllotment.lot = global.copyObj(fromAllotment.lot)
      toAllotment.expiryDate = fromAllotment.expiryDate
      toAllotment.supplierLotNumber = fromAllotment.supplierLotNumber
      toAllotment.refreshIndexes()
    }
    toAllotment.quantity += fromAllotment.quantity
    toAllotment.quantityUpdatedToOnHand += fromAllotment.quantityUpdatedToOnHand
    toAllotment.quantityUpdatedToTransaction += fromAllotment.quantityUpdatedToTransaction
    await fromAllotment.maybeUpdateTransactionAllotment(toAllotment)
    fromAllotment.quantity = 0
    fromAllotment.quantityUpdatedToOnHand = 0
    fromAllotment.quantityUpdatedToTransaction = 0
    await fromAllotment.trash()
  }

/*
  let zeroAllotmentQuantities = async line => {
    let a, as = await line.toAllotments()
    while ( a = as.__() ) {
      a.quantity = 0
    }
  }
*/

  let mergeAllotments = async (toLine, fromLine) => {
    if ( toLine.hasLots !== 'Yes' ) return
/*
    if ( ! toLine.quantityShipped )
      await zeroAllotmentQuantities(toLine) // otherwise it'll be over-allotted as we're mixing a shipped with non-shipped line
*/
    if ( ! toLine.quantityShipped ) {
      toLine.allowNegativeUnspecified = 'Yes'
    }
    let a, as = await fromLine.toAllotments()
    while ( a = as.__() ) {
      await mergeAllotment(toLine, a)
    }
  }

  let mergeAndTrashDefunctSplitOrder = async shipment => {
    let splitSO = await shipment.referee('salesOrder'); if ( ! splitSO ) return
    let splitLine, splitLines = await splitSO.toSOLines()
    while ( splitLine = splitLines.__() ) {
      let soLine = await splitLine.referee('splitFromSOLine'); if ( ! soLine ) continue
      await mergeAllotments(soLine, splitLine)
      soLine.quantityOrdered += splitLine.quantityOrdered
      soLine.quantityShipped += splitLine.quantityShipped
      soLine.lineTotalExclTax = soLine.quantityOrdered * soLine.unitPriceExTax
      soLine.lineTotalBDExclTax = soLine.quantityOrdered * soLine.unitPriceBDExTax
      splitLine.quantityOrdered = 0
      splitLine.quantityShipped = 0
      await splitLine.trash()
      await soLine.refreshCalculations({force: true})
    }
    await splitSO.trash()
  }

  if ( this.isSecondary === 'Yes' ) return
  let merge = options && options.merge
  if ( merge ) {
    this.latestShipmentNumber = await this.toSecondLastShipmentNumber()
    await this.trashLastShipment()
  } else
    await this.refreshLatestShipmentNumber()
  let shipmentNumber = this.latestShipmentNumber
  if ( ! shipmentNumber )
    return
  let order = await this.toOrder(); if ( ! order ) return
  //let shipment = await 'SOShipment'.bringSingle({shipmentNumber: shipmentNumber, order: this.order})
  let shipment = await 'SOShipment'.bringSingle({shipmentNumber: shipmentNumber})
  let isNew
  let soLines = await this.getLines()
  let customer = await this.toCustomer()
  if ( ! shipment ) {
    if ( ! linesHaveQuantityShipped(soLines) )
      return
    shipment = await 'SOShipment'.bringOrCreate({shipmentNumber: shipmentNumber, order: this.order})
    isNew = true
    if ( global.confVal('swo') === 'Yes' )
      this.singleShipment = shipment.reference()
    shipment.salesOrder = this.reference()
    shipment.customer = customer ? customer.reference() : null
  }
  this.manageInProfitori = 'Yes'
  shipment.originalSalesOrder = this.reference()
  shipment.shipmentDate = global.todayYMD()
  shipment.localPickup = (this.shipmentMethod === 'Local Pickup') ? 'Yes' : 'No'
  if ( (this.fulfillStage === 'Shipped') && (shipment.localPickup === 'Yes') )
    shipment.pickedUp = 'Yes'
  if ( shipment.salesOrder.id !== this.id ) {
    await mergeAndTrashDefunctSplitOrder(shipment)
    shipment.salesOrder = this.reference()
    this.copyToWCOrder()
  }
  await shipment.trashChildren()
  let done = 0
  for ( var i = 0; i < soLines.length; i++ ) {
    let soLine = soLines[i]
    if ( soLine.parentSOLine ) continue
    let qtyAlreadyShipped = await soLineToQtyShippedOnOtherShipments(soLine)
    let quantityShipped = soLine.quantityShipped
    //quantityShipped += await soLineToSplitOrderQuantityShipped(soLine)
    let diff = quantityShipped - qtyAlreadyShipped
    if ( ! diff ) 
      continue
    let shipmentLine = await 'SOShipmentLine'.create({parentCast: shipment}, {shipment: shipment.reference()})
    shipmentLine.originalSOLine = soLine.reference()
    shipmentLine.product = soLine.product
    shipmentLine.soLine = soLine.reference()
    shipmentLine.order_item_id = soLine.order_item_id
    shipmentLine.originalOrderItemId = soLine.order_item_id
    shipmentLine.quantityShipped = diff
    shipmentLine.customerId = order._customer_user
    shipmentLine.orderId = order.id
    shipmentLine.shipmentDate = shipment.shipmentDate
    shipmentLine.allowNegativeUnspecified = 'Yes'
    //await copyAllotments(shipmentLine, soLine)
    await shipmentLine.copyAllotments(soLine)
    await shipmentLine.refreshCalculations({force: true})
    done++
  }
  if ( (! done) && isNew ) {
    //if ( this.singleShipment && (this.singleShipment.id === shipment.id) ) {
      //this.singleShipment = null
    //}
    await shipment.trash()
  } else {
/*
    if ( global.confVal('swo') === 'Yes' ) {
      await this.syncToShipment({force: true})
    }
*/
  }
  shipment.refreshIndexes()
  await this.refreshSecondarySOs()
})

'SO'.method('hasBundles', async function() {
  let lines = await this.getLines()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    if ( line.parentSOLine )
      return true
  }
  return false
})

'SO'.method('zeroQuantitiesAllocated', async function() {
  let lines = await this.getLines()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    line.quantityAllocated = 0
    line.quantityAllocatedForMaking = 0
  }
})

'SO'.method('toOrderId', async function() {
  let order = await this.toOrder(); if ( ! order ) return null
  return order.id
})

'SO'.method('toSOLines', async function() {
  return await this.getLines()
})

'SO'.method('getLines', async function() {
  let res = await 'SOLine'.bringChildrenOf(this)
  res = res.filter(line => (! line.lineType) || (line.lineType === 'Product'))
  return res
})

'SO'.method('getAllLines', async function() {
  let res = await 'SOLine'.bringChildrenOf(this)
  return res
})

'SO'.method('getAllLinesFast', function() {
  let res = 'SOLine'.bringChildrenOfFast(this, {kosher: true})
  return res
})

'shippingNameAndCompany'.calculate(async so => {
  let order = so.toOrderFast()
  if ( ! order )
    order = await so.toOrder() 
  if ( ! order ) return ''
  if ( order.shippingNameAndCompany )
    return order.shippingNameAndCompany
  let customer = await so.toCustomer(); if ( ! customer ) return null
  return customer.shippingNameAndCompany
})

'shippingAddress'.calculate(async so => {
  let order = so.toOrderFast()
  if ( ! order )
    order = await so.toOrder() 
  if ( ! order ) return ''
  if ( order.shippingAddress )
    return order.shippingAddress
  let customer = await so.toCustomer(); if ( ! customer ) return null
  return customer.shippingAddress
})

'shippingEmailAndPhone'.calculate(async so => {
  let order = so.toOrderFast()
  if ( ! order )
    order = await so.toOrder() 
  if ( ! order ) return ''
  if ( order.shippingEmailAndPhone )
    return order.shippingEmailAndPhone
  let customer = await so.toCustomer(); if ( ! customer ) return null
  return customer.billingEmailAndPhone
})

'fulfillStage'.afterUserChange(async (oldInputValue, newInputValue, so) => {

  let shiftPackingQtysToShipped = async () => {
    let lines = await so.getLines()
    for ( var i = 0; i < lines.length; i++ ) {
      let line = lines[i]
      if ( line.quantityToPack <= 0 ) continue
      if ( line.quantityShipped > 0 ) continue
      line.quantityShipped = line.quantityToPack
      line.quantityToPack = 0
    }
  }

  let lines = await so.getLines()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    line.fulfillStage = so.fulfillStage
  }
  if ( newInputValue === 'Shipped' ) {
    await shiftPackingQtysToShipped()
    await so.refreshShipment()
  }
  await so.refreshPackable()
  await so.maybeAutoSetWCStatus()
})

'priority'.afterUserChange(async (oldInputValue, newInputValue, so) => {
  let lines = await so.getLines()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    line.priority = so.priority
  }
})

'shipFromLocation'.afterUserChange(async (oldInputValue, newInputValue, so) => {

  let updateSublines = async () => {
    let lines = await so.getAllLines()
    for ( var i = 0; i < lines.length; i++ ) {
      let line = lines[i]
      if ( line.parentSOLine ) continue // updateSublines is recursive, so we only need to call it on top level lines
      await line.updateSublines({keepZeroes: true})
    }
  }

  let alterLineLocations = async () => {
    let lines = await so.getAllLines()
    for ( var i = 0; i < lines.length; i++ ) {
      let line = lines[i]
      line.shipFromLocation = so.shipFromLocation
    }
    await updateSublines()
  }

  let refreshAffected = async () => {
    let lines = await so.getLines()
    for ( var i = 0; i < lines.length; i++ ) {
      let line = lines[i]
      let inv = await line.toInventory(); if ( ! inv ) continue
      await inv.refreshQuantityReservedForCustomerOrders({refreshClusters: true})
      await inv.refreshQuantityAllocated({refreshClusters: true, doComponents: true}) // also does pickable and makeable
    }
  }

  await refreshAffected()
  await alterLineLocations()
  await refreshAffected()
  await global.foreman.doSave({msgOnException: true}) // important as this adjusts quantities on hand in the clusters
  //await updateSublines()
  await refreshAffected()
  let c = await 'Configuration'.bringFirst()
  await c.refreshPackables()
})

'SO'.method('isActive', async function() {
  let order = await this.toOrder(); if ( ! order ) return false
  return await order.isActive()
})

'SO'.method('isActiveFast', async function() {
  let order = await this.toOrderFast() 
  if ( (! order) || (order === 'na') )  
    return 'na'
  return order.isActiveFast()
})

'SO'.method('syncToShipment', async function(opt) {

  let existingLines

  let shipmentLineToSOLine = async (shipmentLine, soLines) => {
    for ( var i = 0; i < soLines.length; i++ ) {
      let line = soLines[i]
      if ( line.parentSOLine ) continue
      if ( shipmentLine.soLine && (line.id === shipmentLine.soLine.id) )
        return line
    }
    return null
  }

  let markExistingLinesAsUntouched = async () => {
    for ( var i = 0; i < existingLines.length; i++ ) {
      let el = existingLines[i]
      el._touched = false
    }
  }

  let removeUntouchedLines = async () => {
    for ( var i = 0; i < existingLines.length; i++ ) {
      let el = existingLines[i]
      if ( el._touched ) continue
      await el.trash()
    }
  }

  let numberSimpleLines = async () => {
    let lines = await 'SOLine'.bring({parentId: this.id})
    let no = 0
    for ( var i = 0; i < lines.length; i++ ) {
      let line = lines[i]
      let lineType = line.lineType
      if ( lineType && (lineType !== 'Product') )
        continue
      line.sequence = global.padWithZeroes(no + 1, 3)
      no++
    }
    // Put shipments and fees last
    for ( i = 0; i < lines.length; i++ ) {
      let line = lines[i]
      let lineType = line.lineType
      if ( (! lineType) || (lineType === 'Product') )
        continue
      line.sequence = global.padWithZeroes(no + 1, 3)
      no++
    }
  }

  let copyFieldsFromSO = so => {
    this.shippingFirstName = so.shippingFirstName
    this.shippingLastName = so.shippingLastName
    this.shippingCompany = so.shippingCompany
    this.shippingAddress1 = so.shippingAddress1
    this.shippingAddress2 = so.shippingAddress2
    this.shippingCity = so.shippingCity
    this.shippingState = so.shippingState
    this.shippingPostcode = so.shippingPostcode
    this.shippingCountry = so.shippingCountry
    this.billingFirstName = so.billingFirstName
    this.billingLastName = so.billingLastName
    this.billingCompany = so.billingCompany
    this.billingAddress1 = so.billingAddress1
    this.billingAddress2 = so.billingAddress2
    this.billingCity = so.billingCity
    this.billingState = so.billingState
    this.billingPostcode = so.billingPostcode
    this.billingCountry = so.billingCountry
    this.billingEmail = so.billingEmail
    this.billingPhone = so.billingPhone
    this.orderCurrency = so.orderCurrency
    this.cartDiscount = so.cartDiscount
    this.cartDiscountTax = so.cartDiscountTax
    this.paymentMethodTitle = so.paymentMethodTitle
    this.customerReference = global.copyObj(so.customerReference)
  }

  let force = opt && opt.force
  if ( (! force) && (this.isSecondary !== 'Yes') ) return
  let shipment = await this.toFirstShipment(); if ( ! shipment ) return
  let originalSO = await shipment.toOriginalSO(); if ( ! originalSO ) return
  this.singleShipment = shipment.reference()
  this.customer = global.copyObj(originalSO.customer)
  this.orderDate = originalSO.orderDate
  if ( ! this.wcNiceStatus )
    this.wcNiceStatus = originalSO.wcNiceStatus
  this.shipmentMethod = originalSO.shipmentMethod
  copyFieldsFromSO(originalSO)
  existingLines = await 'SOLine'.bring({parentId: this.id})
  await markExistingLinesAsUntouched()
  let shipmentLines = await shipment.toLines()
  for ( var i = 0; i < shipmentLines.length; i++ ) {
    let shipmentLine = shipmentLines[i]
    let originalSOLine = await shipmentLine.toOriginalSOLine(); if ( ! originalSOLine ) continue
    if ( shipmentLine.quantityShipped === 0 ) continue
    let product = await shipmentLine.toProduct(); if ( ! product ) continue
    let soLine = await shipmentLineToSOLine(shipmentLine, existingLines)
    if ( soLine )
      soLine._touched = true
    if ( ! soLine ) {
      soLine = await 'SOLine'.create(null, {salesOrder: this})
    }
    shipmentLine.soLine = soLine.reference()
    if ( soLine.order_item_id !== 0 )
      shipmentLine.order_item_id = soLine.order_item_id
    if ( soLine.order && (soLine.order.id !== 0) )
      shipmentLine.orderId = soLine.order.id
    soLine.splitFromSOLine = originalSOLine.reference()
    soLine.product = product.reference()
    soLine.quantityOrdered = shipmentLine.quantityShipped
    soLine.quantityShipped = shipmentLine.quantityShipped
    soLine.unitPriceExTax = originalSOLine.unitPriceExTax
    soLine.unitPriceBDExTax = originalSOLine.unitPriceBDExTax
    soLine.lineTotalExclTax = shipmentLine.quantityShipped * soLine.unitPriceExTax
    soLine.lineTotalBDExclTax = shipmentLine.quantityShipped * soLine.unitPriceBDExTax
    soLine.taxPct = originalSOLine.taxPct
    soLine.lineTax = originalSOLine.lineTax * (soLine.quantityOrdered / originalSOLine.quantityOrdered)
    soLine.lineType = 'Product'
    soLine.description = originalSOLine.description
    await soLine.refreshCalculations({force: true})
    await shipmentLine.refreshCalculations({force: true})
    this.retired = 'No'
  }
  await removeUntouchedLines()
  await numberSimpleLines()
  await this.refreshValueRemainingToBeInvoiced()
  await this.refreshTotals()
  await this.maybeDefaultShipmentMethod()
  await this.refreshPackable()
  await this.copyToWCOrder()
})

'SO'.method('syncToOrder', async function(opt) {

  let existingLines
  let skipFulfillmentRefreshes = opt && opt.skipFulfillmentRefreshes

  let orderItemToSOLine = async (oi, soLines) => {
    for ( var i = 0; i < soLines.length; i++ ) {
      let line = soLines[i]
      if ( line.parentSOLine ) continue
      if ( line.order_item_id === oi.id )
        return line
    }
    return null
  }

  let markExistingLinesAsUntouched = async () => {
    for ( var i = 0; i < existingLines.length; i++ ) {
      let el = existingLines[i]
      el._touched = false
    }
  }

  let removeUntouchedLines = async () => {
    for ( var i = 0; i < existingLines.length; i++ ) {
      let el = existingLines[i]
      if ( el._touched ) continue
      await el.trash()
    }
  }

  let numberSimpleLines = async () => {
    let lines = await 'SOLine'.bring({parentId: this.id})
    let no = 0
    for ( var i = 0; i < lines.length; i++ ) {
      let line = lines[i]
      let lineType = line.lineType
      if ( lineType && (lineType !== 'Product') )
        continue
      line.sequence = global.padWithZeroes(no + 1, 3)
      no++
    }
    // Put shipments and fees last
    for ( i = 0; i < lines.length; i++ ) {
      let line = lines[i]
      let lineType = line.lineType
      if ( (! lineType) || (lineType === 'Product') )
        continue
      line.sequence = global.padWithZeroes(no + 1, 3)
      no++
    }
  }

  let copyFieldsFromOrder = async order => {
    this.shippingFirstName = order._shipping_first_name
    this.shippingLastName = order._shipping_last_name
    this.shippingCompany = order._shipping_company
    this.shippingAddress1 = order._shipping_address_1
    this.shippingAddress2 = order._shipping_address_2
    this.shippingCity = order._shipping_city
    this.shippingState = order._shipping_state
    this.shippingPostcode = order._shipping_postcode
    this.shippingCountry = global.codeToCountry(order._shipping_country)
    this.billingFirstName = order._billing_first_name
    this.billingLastName = order._billing_last_name
    this.billingCompany = order._billing_company
    this.billingAddress1 = order._billing_address_1
    this.billingAddress2 = order._billing_address_2
    this.billingCity = order._billing_city
    this.billingState = order._billing_state
    this.billingPostcode = order._billing_postcode
    this.billingCountry = global.codeToCountry(order._billing_country)
    this.billingEmail = order._billing_email
    this.billingPhone = order._billing_phone
    this.orderCurrency = order._order_currency
    this.cartDiscount = order._cart_discount
    this.cartDiscountTax = order._cart_discount_tax
    this.paymentMethodTitle = order._payment_method_title
    this.customerReference = order.customerReference
    if ( global.confVal('eaa') === 'Yes' ) // eaa = agent names can be entered in WC order entry
      await order.refreshPieceFromAgentName()
  }

  let itemTypeToLineType = itemType => {
    if ( itemType === 'fee' )
      return 'Fee'
    if ( itemType === 'shipping' )
      return 'Shipping'
    if ( (! itemType) || (itemType === 'line_item') )
      return 'Product'
    return null
  }

  let order = await this.toOrder(); if ( ! order ) return
  if ( ! order.order_date ) return
  if ( order.order_date.isEmptyYMD() ) return
  await order.refreshCalculations({force: true})
  this.manageInProfitori = order.manageInProfitori
  let manageInWC = (this.manageInProfitori !== 'Yes')
  this.customer = await order.toCustomerRef()
  this.orderDate = order.order_date
  this.wcNiceStatus = order.niceStatus
  this.shipmentMethod = order.shipmentMethod
  await copyFieldsFromOrder(order)
  existingLines = await 'SOLine'.bring({parentId: this.id})
  await markExistingLinesAsUntouched()
  let ois = await 'order_items.RecentOrActive'.bring({theOrder: this.order})
  for ( var i = 0; i < ois.length; i++ ) {
    let oi = ois[i]
    await oi.refreshCalculations({force: true})
    let isActive = await oi.isActive()
    let lineType = itemTypeToLineType(oi.order_item_type)
    if ( ! ['Product', 'Fee', 'Shipping'].contains(lineType) ) continue
    let line = await orderItemToSOLine(oi, existingLines)
    if ( line )
      line._touched = true
    if ( ! isActive ) continue
    if ( ! line ) {
      line = await 'SOLine'.create(null, {salesOrder: this})
    }
    let product = await oi.toProduct()
    line.order_item_id = oi.id
    if ( product )
      line.product = product.reference()
    line.quantityOrdered = await oi.toQtyNetOfRefunds()
    if ( (line.quantityOrdered === 0) && (lineType !== 'Product') )
      line.quantityOrdered = 1
    line.lineTotalExclTax = oi._line_total
    line.unitPriceExTax = oi.unitPriceExTax
    line.unitPriceBDExTax = oi.unitPriceBDExTax
    line.lineTax = oi._line_tax
    line.taxPct = oi.toTaxPct()
    line.lineType = lineType
    if ( oi._prfi_notes && ((! line.notes) || manageInWC) )
      line.notes = oi._prfi_notes
    if ( oi._name && ((! line.description) || manageInWC) )
      line.description = oi._name
    if ( lineType === 'Shipping' ) {
      line.shippingMethod = await oi.toShippingMethodRef()
    }
    if ( line.unitPriceBDExTax !== line.unitPriceExTax ) {
      await line.refreshDiscount()
      line.lineTotalExclTax = oi._line_total // make sure none of the above disturbs the actual real price
    }
    await line.refreshCalculations()
    this.retired = 'No'
  }
  await removeUntouchedLines()
  await numberSimpleLines()
  await this.refreshValueRemainingToBeInvoiced()
  await this.refreshTotals()
  await this.maybeDefaultShipmentMethod()
  await order.maybeCreateDebtor()
  if ( ! skipFulfillmentRefreshes ) {
    await this.refreshPackable()
  }
})

'SO'.method('retire', async function() {
  let linesToInvs = async lines => {
    let res = {}
    for ( var i = 0; i < lines.length; i++ ) {
      let line = lines[i]
      let inv = await line.toInventory(); if ( ! inv ) continue
      res[inv.id] = inv
    }
    return res
  }

  let lines = await 'SOLine'.bring({parentId: this.id})
  let invs = await linesToInvs(lines)
  for ( let invId in invs ) {
    let inv = invs[invId]; if ( ! inv ) continue
    await inv.refreshQuantityReservedForCustomerOrders({refreshClusters: true})
  }
  await this.refreshPackable({unallocate: true})
  for ( let invId in invs ) {
    let inv = invs[invId]; if ( ! inv ) continue
    await inv.refreshQuantityReservedForCustomerOrders({refreshClusters: true})
  }
  this.retired = 'Yes'
})

'SO'.method('refreshPackable', async function(opt) {
  let lines = await 'SOLine'.bring({parentId: this.id})
  let fullyCount = 0
  let partialCount = 0
  let doneCount = 0
  let linesCount = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    let lineType = line.lineType
    if ( lineType && (lineType !== 'Product') )
      continue
    linesCount++
    await line.refreshPackable(opt)
    if ( line.packable === 'Yes' )
      fullyCount++
    else if ( line.packable === 'Partially' )
      partialCount++
    else if ( line.quantityRemainingToShip <= 0 )
      doneCount++
  }
  if ( (fullyCount > 0) && ((fullyCount + doneCount) === linesCount) )
    this.packable = 'Yes'
  else if ( (partialCount > 0) || (fullyCount > 0) )
    this.packable = 'Partially'
  else
    this.packable = 'No'
})

'shipFromLocation'.inception(async soLine => {
  let location = await 'Location'.bringOrCreate({locationName: 'General'})
  return location.reference()
})

'shipToStateAndCountry'.calculate(async so => {
  let order = so.toOrderFast()
  if ( ! order )
    order = await so.toOrder() 
  if ( ! order ) return ''
  let state = order._shipping_state
  let country = global.codeToCountry(order._shipping_country)
  let res = country
  if ( res && state )
    res += ' ' + state
  return res
})

'shipToPostalCode'.calculate(async so => {
  let order = so.toOrderFast()
  if ( ! order )
    order = await so.toOrder() 
  if ( ! order ) return ''
  return order._shipping_postcode
})

'SO'.method('toOrder', async function() {
  if ( ! this.order ) 
    return null
  let res = await 'orders.RecentOrActive'.bringSingle({id: this.order.id})
  return res
})

'SO'.method('toOrderFast', function () {
  if ( ! this.order )
    return null
  let mold = global.foreman.doNameToMold('orders')
  //let res = mold.fastRetrieveSingle({id: this.order.keyval});
  let res = mold.fastRetrieveSingle({id: this.order.id});
  if ( res === 'na' ) return null
  return res
})

'packable'.options(['Yes', 'Partially', 'No'])

'fulfillStage'.options(['Waiting', 'Waiting – Partially Shipped', 'Picking', 'Packing', 'Packed', 'Waiting for Pickup', 'Shipped'])

'fulfillStage'.inception('Waiting')

'priority'.options(['', '1', '2', '3', '4', '5', '6', '7', '8', '9'])

'SO'.method('refreshWCOrderStatus', async function() {
  let order = await this.toOrder() ; if ( ! order ) return
  order.status = 'wc-' + this.wcNiceStatus.toLowerCase()
  let items = await 'order_items.RecentOrActive'.bring({parentId: order.id})
  for ( var i = 0; i < items.length; i++ ) {
    let item = items[i]
    item.order_status = order.status
  }
})

'SO'.method('affectsStock', async function() {
  let res = ['Pending', 'Failed', 'Cancelled', 'Refunded'].indexOf(this.wcNiceStatus) < 0
  return res
})

'SO'.method('newlyAffectsStock', async function() {
  if ( ! await this.affectsStock() ) 
    return false
  let old = this.getOld(); if ( ! old ) return true
  if ( ! old.wcNiceStatus ) 
    return true
  if ( ['Pending', 'Failed', 'Cancelled', 'Refunded'].indexOf(old.wcNiceStatus) >= 0 )
    return true
  return false
})

'wcNiceStatus'.afterUserChange(async (oldInputValue, newInputValue, so) => {
  await so.refreshWCOrderStatus()
  /*
  let order = await so.toOrder() 
  order.status = 'wc-' + newInputValue.toLowerCase()
  let items = await 'order_items.RecentOrActive'.bring({parentId: order.id})
  for ( var i = 0; i < items.length; i++ ) {
    let item = items[i]
    item.order_status = order.status
  }
  */
})

'SO'.beforeSaving(async function() {

  let getOrCreateEntry = async () => {
    let sourceAbbrev = 'SA'
    let entryNumber = sourceAbbrev + '-' + this.journaledDate
    let orderDate = this.orderDate
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

  let updateFeesJournalEntry = async (options) => {
    let entry = await getOrCreateEntry()
    await entry.updateSale(options.location, options.amount, 'Fees')
  }

  let maybeReverseOldFeesEntry = async () => {
    if ( ! this.journaledFeesAmount ) return
    let loc = await this.referee('journaledFeesLocation')
    await updateFeesJournalEntry({location: loc, amount: - this.journaledFeesAmount})
  }

  let updateFeesJournalEntries = async () => {
    await maybeReverseOldFeesEntry()
    let amount = await this.toFeesValueIncTax()
    let loc = await this.toShipFromLocation()
    await updateFeesJournalEntry({location: loc, amount: amount})
    this.journaledFeesAmount = amount
    this.journaledFeesLocation = loc.reference()
  }

  let updateShippingJournalEntry = async (options) => {
    let entry = await getOrCreateEntry()
    await entry.updateSale(options.location, options.amount, 'Shipping')
  }
  
  let maybeReverseOldShippingEntry = async () => {
    if ( ! this.journaledShippingAmount ) return
    let loc = await this.referee('journaledShippingLocation')
    await updateShippingJournalEntry({location: loc, amount: - this.journaledShippingAmount})
  }

  let updateShippingJournalEntries = async () => {
    await maybeReverseOldShippingEntry()
    let amount = await this.toShippingValueIncTax()
    let loc = await this.toShipFromLocation()
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
    await entry.updateSale(options.location, options.amount, 'Tax')
  }
  
  let updateTaxJournalEntries = async () => {
    await maybeReverseOldTaxEntry()
    let amount = await this.toTaxValue()
    let loc = await this.toShipFromLocation()
    await updateTaxJournalEntry({location: loc, amount: amount})
    this.journaledTaxAmount = amount
    this.journaledTaxLocation = loc.reference()
  }

  let updateJournalEntries = async () => {
    let config = await 'Configuration'.bringOrCreate()
    if ( config.glEnabled !== 'Yes' ) return
    this.journaledDate = await config.dateToPostableDate(this.orderDate)
    await updateTaxJournalEntries()
    await updateShippingJournalEntries()
    await updateFeesJournalEntries()
  }

  await this.refreshTotals()
  if ( this.manageInProfitori === 'Yes' ) {
    await this.copyToWCOrder()
  }
  await this.adjustLotQuantities()
  if ( this._markedForDeletion ) {
    await this.refreshQuantitiesReservedForCustomerOrders()
    await updateJournalEntries()
    await this.refreshShipment()
    return
  }
  if ( this.getOld().wcNiceStatus && (this.wcNiceStatus !== this.getOld().wcNiceStatus) ) {
    if ( this.wcNiceStatus === 'Completed' ) {
      //let ok = await this.doQuantitiesShippedMatchPacked()
      //if ( ! ok )
        //throw(new Error('Quantity Shipped must equal Quantity to Pack before you can set the order to Completed'.translate()))
      if ( (! global.harmonizing) && (! global.refreshingPackables) ) {
        let ok = await this.doQuantitiesShippedMatchOrdered()
        if ( ! ok )
          throw(new Error('Quantity Shipped must equal Quantity Ordered before you can set the order to Completed'.translate()))
      }
      await this.adjustForDifferencesToPreempts()
    }
  }
  await this.refreshQuantitiesReservedForCustomerOrders()
  await this.maybeAnnotateWCOrder()
  await updateJournalEntries()
})

'SO'.method('maybeAnnotateWCOrder', async function() {
  if ( global.confVal('annotatePartiallyDeliveredWCOrders') !== 'Yes' ) return
  let order = await this.toOrder(); if ( ! order ) return
  if ( await this.isFullyShipped() )
    order.partiallyDelivered = ''
  else if ( await this.hasPartiallyOrFullyShippedLines() ) {
    order.partiallyDelivered = 'Yes'
  } else
    order.partiallyDelivered = ''
})

'SO'.method('toShipFromLocation', async function() {
  return await this.referee('shipFromLocation')
})

'SO'.method('adjustLotQuantities', async function() {
  let lines = await this.getLines()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    await line.adjustLotQuantities()
  }
})

'SO'.method('refreshQuantitiesReservedForCustomerOrders', async function() {
  let lines = await this.getLines()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    let inv = await line.toInventory(); if ( ! inv ) continue
    await inv.refreshQuantityReservedForCustomerOrders({refreshClusters: true})
  }
})

'SO'.method('adjustForDifferencesToPreempts', async function() {

  let adjustStock = async (qty, line, pickOrMake) => {
    let loc = await line.toShipFromLocation()
    let product = await line.toProduct()
    let tran = await 'Transaction'.create()
    tran.product = product.reference()
    tran.date = global.todayYMD()
    tran.location = loc.reference()
    tran.quantity = qty
    tran.source = 'SO'
    tran.notes = pickOrMake.translate() + ' ' + 'adjustment on order completion'.translate()
    tran.reference = await this.toOrderId()
  }

  let lineToPreempted = async (line) => {
    let resObj = {made: 0, consumed: 0}
    if ( ! line.product ) return
    let preempts = await 'Preempt'.bring({order_item_id: line.order_item_id})
    for ( var i = 0; i < preempts.length; i++ ) {
      let preempt = preempts[i]
      if ( preempt.product_id !== line.product.id ) continue
      if ( preempt.finalised === 'Yes' ) continue
      if ( preempt.madeOrConsumed === 'consumed' )
        resObj.consumed -= preempt.quantity
      else
        resObj.made += preempt.quantity
      preempt.finalised = 'Yes'
    }
    return resObj
  }

  let lines = await this.getLines()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    let preempted = await lineToPreempted(line); if ( ! preempted ) continue
    if ( preempted.made !== line.quantityToMake )
      await adjustStock(line.quantityToMake - preempted.made, line, 'Make')
    if ( line.parentSOLine && (preempted.consumed !== line.quantityToPick) )
      await adjustStock(preempted.consumed - line.quantityToPick, line, 'Pick')
  }
})

'SO'.method('doQuantitiesShippedMatchPacked', async function() {
  let lines = await this.getLines()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    if ( line.quantityShipped !== line.quantityToPack ) {
      return false
    }
  }
  return true
})

'SO'.method('doQuantitiesShippedMatchOrdered', async function() {
  let lines = await this.getLines()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    if ( line.quantityShipped !== line.quantityOrdered ) {
      return false
    }
  }
  return true
})

'SO'.method('hasPartiallyOrFullyShippedLines', async function() {
  let lines = await this.getLines()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    if ( line.quantityShipped ) {
      return true
    }
  }
  return false
})

'SO'.method('isFullyShipped', async function() {
  let lines = await this.getLines()
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    if ( line.quantityShipped < line.quantityOrdered ) {
      return false
    }
  }
  return true
})

'SO'.method('maybeAutoSetWCStatus', async function() {
  if ( ! await this.doQuantitiesShippedMatchOrdered() ) return
  if ( global.confVal('autoCompleteWCOrders') !== 'Yes' ) return
  if ( this.fulfillStage === 'Waiting for Pickup' ) return
  this.wcNiceStatus = 'Completed'
  await this.refreshWCOrderStatus()
  let mold = global.foreman.doNameToMold('SO')
  mold.version++
})

'orderDate'.calculate(async so => {
  let order = so.toOrderFast()
  if ( ! order )
    order = await so.toOrder() 
  if ( ! order ) return null
  return order.order_date
})

'SO'.method('toFeesValueIncTax', async function() {
  let order = await this.toOrder(); if ( ! order ) return 0
  return order.feesIncTax
})

'SO'.method('toShippingValueIncTax', async function() {
  let order = await this.toOrder(); if ( ! order ) return 0
  return order.shippingIncTax
})

'SO'.method('toTaxValue', async function() {
  let order = await this.toOrder(); if ( ! order ) return 0
  return order._order_tax + order._order_shipping_tax
})

