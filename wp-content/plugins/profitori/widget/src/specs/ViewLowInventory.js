'ViewLowInventory'.list({expose: true, suppressOnMoreMenu: true, icon: 'Eye'})
'View Short Stock'.title()
'Back'.action({act: 'cancel'})
'Refresh'.action({act: "refresh"})
'Download to Excel'.action({act: 'excel'})

'LowInventory'.datatype({transient: true})
'includeInAutoPOs'.field({hidden: true, ephemeral: true, tickbox: true})
'product'.field({refersTo: 'products', key: true, showAsLink: true})
'surplus'.field({numeric: true})
'priorWeeksSales'.field({numeric: true, caption: "Last X Weeks Sales"})
'surplusWeeks'.field({numeric: true, decimals: 2, sortUnknownsLast: true})
'onHand'.field({numeric: true})
'onPurchaseOrders'.field({numeric: true})
'onSalesOrders'.field({numeric: true})
'avgBOUnitPriceExclTax'.field({numeric: true, decimals: 2, caption: "Backorder / Indicative Price Ex Tax"})
'threshold'.field({numeric: true, caption: "Low Stock Threshold"})
'supplier'.field({refersTo: 'Supplier', caption: 'Main Supplier', showAsLink: true})
'inventory'.field({refersTo: 'Inventory', hidden: true})

'LowInventory'.method('toBackOrderedSOLines', async function() {
  let soLines = await 'SOLine'.bring({product: this.product})
  let res = []
  for ( var i = 0; i < soLines.length; i++ ) {
    let soLine = soLines[i]
    let so = await soLine.toSO(); if ( ! so ) continue
    if ( so.retired === 'Yes' ) continue
    if ( soLine.quantityRemainingToShip <= 0 ) continue
    res.push(soLine)
  }
  return res
})

'LowInventory'.method('toRecentSOLines', async function(options) {
  let soLines = await 'SOLine'.bring({product: this.product})
  let candidates = []
  for ( let i = 0; i < soLines.length; i++ ) {
    let soLine = soLines[i]
    if ( soLine.quantityOrdered <= 0 ) continue
    let so = await soLine.toSO(); if ( ! so ) continue
    soLine.__orderDate = so.orderDate
    candidates.push(soLine)
  }
  candidates.sort((s1, s2) => s1.__orderDate > s2.__orderDate ? 1 : -1)
  let res = []
  let done = 0
  for ( let i = candidates.length - 1; i >= 0; i-- ) {
    let candidate = candidates[i]
    res.push(candidate)
    done++
    if ( done >= options.count )
      break
  }
  return res
})

'avgBOUnitPriceExclTax'.calculate(async lowInventory => {
  let soLines = await lowInventory.toBackOrderedSOLines()
  let doingRecent
  if ( soLines.length === 0 ) {
    soLines = await lowInventory.toRecentSOLines({count: 5})
    doingRecent = true
  }
  let quantity = 0
  let value = 0
  for ( var i = 0; i < soLines.length; i++ ) {
    let soLine = soLines[i]
    let lineQuantity = soLine.quantityRemainingToShip
    if ( doingRecent )
      lineQuantity = soLine.quantityOrdered
    quantity += lineQuantity
    let unitPriceExclTax = await soLine.toUnitPriceExclTax()
    value += lineQuantity * unitPriceExclTax
  }
  let res = 0
  if ( quantity > 0 )
    res = value / quantity
  return res
})

'priorWeeksSales'.caption(async (list) => {
  let config = await 'Configuration'.bringFirst()
  let priorWeekCount = await config.getSalesProjectionPriorWeeks()
  return "Last".translate() + " " + priorWeekCount + " " + "Weeks Sales".translate()
})

'ViewLowInventory'.defaultSort({field: "surplusWeeks"})

'ViewLowInventory'.beforeLoading(async list => {
  
  let config = await 'Configuration'.bringFirst()
  let priorWeekCount = await config.getSalesProjectionPriorWeeks()

  let createData = async () => {

    let updateSales = async () => {
      let orderItems = await 'order_items.RecentOrActive'.bring()
      let salesFromDate = global.todayYMD().incDays((- priorWeekCount) * 7)
      for ( var i = 0; i < orderItems.length; i++ ) {
        let orderItem = orderItems[i]
        if ( orderItem.order_date < salesFromDate ) continue
        let varId = orderItem._variation_id
        let productId = varId ? varId : orderItem._product_id
        let product = await 'products'.bringSingle({id: productId}); if ( ! product ) continue
        let lowInv = await 'LowInventory'.bringSingle({product: product}); if ( ! lowInv ) continue
        let status = orderItem.order_status
        if ( (status === "wc-pending") || (status === "wc-failed") || (status === "wc-partially-paid") )
          lowInv.onSalesOrders += orderItem._qty
        if ( (status !== "wc-cancelled") && (status !== "wc-refunded") )
          lowInv.priorWeeksSales += orderItem._qty
      }
    }
  
    let createLowInventory = async product => {
      let lowInv = await 'LowInventory'.bringOrCreate({product: product})
      lowInv.threshold = product._low_stock_amount
      let inventory = await 'Inventory'.bringSingle({product: product}); if ( ! inventory ) return
      lowInv.threshold = inventory.minQuantity
      lowInv.onPurchaseOrders = inventory.quantityOnPurchaseOrders
      lowInv.onHand = inventory.quantityOnHand
      lowInv.supplier = await inventory.toMainSupplierRef()
      lowInv.inventory = inventory.reference()
      if ( lowInv.refreshExclusiveFields )
        await lowInv.refreshExclusiveFields()
    }
  
    let updateSurpluses = async () => {
      let lowInvs = await 'LowInventory'.bring()
      lowInvs.forAll(lowInv => {
        lowInv.surplus = lowInv.onHand - lowInv.onSalesOrders + lowInv.onPurchaseOrders - lowInv.threshold
        let salesPerWeek = lowInv.priorWeeksSales / priorWeekCount
        lowInv.surplusWeeks = global.unknownNumber()
        if ( salesPerWeek > 0.001 ) {
          lowInv.surplusWeeks = lowInv.surplus / salesPerWeek
        }
      })
    }
  
    let products = await 'products'.bring()
    'LowInventory'.clear()
    await products.forAllAsync(async product => {
      await createLowInventory(product)
    })
    await updateSales()
    await updateSurpluses()
  
  }

  await list.harmonize()
  await createData()

})

'product'.destination(async lowInventory => {
  let pref = lowInventory.product; if ( ! pref ) return null
  if ( ! pref.id ) return null
  let p = await 'products'.bringFirst({id: pref.id})
  return p
})

