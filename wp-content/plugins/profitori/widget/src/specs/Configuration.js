'Configuration'.datatype({singleton: true, preventNative: true, plex: false})
'i5'.field({yesOrNo: true})
'ifx'.field({yesOrNo: true})
'viewFromDate'.field({date: true, allowEmpty: true})
'viewToDate'.field({date: true, allowEmpty: true})
'usersWithAccess'.field({adminOnly: true})
'businessName'.field()
'phoneNumber'.field()
'email'.field()
'deliveryAddress'.field()
'deliveryCity'.field()
'deliveryState'.field()
'deliveryPostcode'.field()
'deliveryCountry'.field()
'shortDateFormat'.field()
'salesProjectionPriorWeeks'.field({numeric: true, caption: 'Weeks of Prior Sales to use for Sales Projections'})
'salesAnalysisPriorWeeks'.field({numeric: true, caption: 'Weeks of Prior Sales to use for Sales Analysis'})
'salesRecentWeeks'.field({numeric: true})
'lastPriorWeeksUsed'.field({numeric: true})
'enterPurchasePricesInclusiveOfTax'.field({yesOrNo: true})
'srDate'.field({date: true, allowEmpty: true})
'displayDatesUsingShortDateFormat'.field({yesOrNo: true})
'drSecs'.field({numeric: true, decimals: 0, caption: 'Dashboard Refresh Frequency (seconds)'})
'noAutoSunder'.field({yesOrNo: true, caption: 'Keep Dashboard In Main Window'})
'databaseOptimized'.field({yesOrNo: true})
'licenseKey'.field()
'poPdfLogoUrl'.field()
'defaultStockingUOM'.field({refersTo: 'UOM', caption: 'Default Stocking Unit of Measure'})
'showUOMOnPOLines'.field({yesOrNo: true, caption: 'Show Unit of Measure on PO Lines'})
'ipu'.field({yesOrNo: true})
'sortPOPdfBySupplierSku'.field({yesOrNo: true, caption: 'Sort PO PDF by Supplier SKU'})
'mainCalendar'.field({refersTo: 'Calendar'})
'standardAccountsCreated'.field({yesOrNo: true})
'calendarCreated'.field({yesOrNo: true})
'standardStatementsCreated'.field({yesOrNo: true})
'calendarStartingMonth'.field()
'glEnabled'.field({caption: 'Enable General Ledger', yesOrNo: true})
'preventPostingBefore'.field({date: true, allowEmpty: true})
'controlAccessAtPageLevel'.field({yesOrNo: true})
'ipm'.field({yesOrNo: true})
'storeAttachmentsInSecureLocation'.field({yesOrNo: true})
'attachmentsPathOnServer'.field({caption: 'Secure Path (local to the server) for Attachments'})
'useAttachmentSubfolders'.field({yesOrNo: true, caption: 'Store In Subfolders By Supplier/Customer By Document Number'})
'suppressPOQuantityOnHandUpdates'.field({yesOrNo: true})
'ied'.field({yesOrNo: true})
'prioritizeOrderCompletion'.field({yesOrNo: true, caption: "Fulfill Fully Satisfiable Orders First"})
'viewSalesHistoryInPurchasing'.field({yesOrNo: true, caption: 'View Sales History When Entering PO Lines'})
'excludeTaxFromPOPdf'.field({yesOrNo: true, caption: 'Exclude Tax from PO PDF'})
'autoCompleteWCOrders'.field({yesOrNo: true, caption: 'Auto-complete WC Orders When Fulfilled'})
'preventOverpick'.field({yesOrNo: true, caption: 'Prevent Shipped Qty > Pickable Qty'})
'enterIncrementalShipmentQuantity'.field({yesOrNo: true})
'annotatePartiallyDeliveredWCOrders'.field({yesOrNo: true, caption: 'Set WC Order Custom Field "partiallyDelivered"'})
'alwaysCreateInventoryRecord'.field({yesOrNo: true})
'startPOReceiptsAsZero'.field({yesOrNo: true, caption: 'Start PO Receipts As Zero'})
'lmlDate'.field({date: true, allowEmpty: true})
'transactionRecentWeeks'.field({numeric: true, caption: 'Weeks of Inventory Transaction History To Show'})
'ims2'.field({yesOrNo: true})
'stImp'.field({yesOrNo: true, caption: 'Automatically Add Imposts In Store'})
'cdr'.field({yesOrNo: true, caption: 'Classic Data Retrieval'})
'swo'.field({yesOrNo: true, caption: 'Split WC Orders to Match Shipments'})
'scs'.field({caption: 'Status to set WC Order to when Shipment Created'})
'ais'.field({caption: 'Auto-generate Invoice when WC Order Status Becomes'})
'isw'.field({yesOrNo: true})
'hsnc'.field({yesOrNo: true, caption: 'Hide Shipments if Customer has no Credit Facility'})
'shc'.field({caption: 'Shipments Tab Caption'})
'hfc'.field({caption: 'Hide Financial Status Of Individual Orders', yesOrNo: true})
'crn'.field({caption: 'Company Registration Number'})
'ibn'.field({caption: 'IBAN'})
'bur'.field({caption: "Invoice PDF Background Image URL"})
'nfa'.field({caption: "Disable Fulfillment Page Auto-Refresh", yesOrNo: true})
'sin'.field({caption: "Use Separate Sequential Number Range for Generated Invoices", yesOrNo: true})
'asd'.field({caption: "Automatically Show Discounts In Store", yesOrNo: true})
'ump'.field({caption: "Use Markup Pricing", yesOrNo: true})
'ptr'.field({caption: "Track PO Stage and Status Transitions", yesOrNo: true})
'pca'.field()
'cph'.field({caption: 'Customer Payment Handling'})
'emb'.field({caption: 'Support Unlisted Product Ordering', yesOrNo: true})
'trb'.field({caption: 'Turbo Mode'})
'tbs'.field({caption: 'Turbo Mode Status'})
'tbi'.field({caption: 'Turbo Mode Initiated', yesOrNo: true})
'lpr'.field({caption: 'Prompt to Produce Labels when Stock Received', yesOrNo: true})
'eaa'.field({caption: 'Allow Edit Agent in WC', yesOrNo: true})
'ccf'.field({caption: 'Calculate Commission From'})
'cty'.field({caption: 'Base Commission Value On'})
'spt'.field({caption: 'Show Payment Terms', yesOrNo: true})
'pte'.field({caption: 'Payment Terms'})
'eub'.field({yesOrNo: true, caption: 'Enable Unbundling When Receiving POs'})
'acp'.field({yesOrNo: true, caption: 'Allow WO Receipt Co-products'})
'pns'.field({yesOrNo: true, caption: 'Prevent Negative Stock When Entering Orders In Profitori'})
'pnm'.field({yesOrNo: true, caption: 'Prevent Negative Margin When Entering Orders In Profitori'})
'hnm'.field({yesOrNo: true, caption: 'Hide Products With Negative Margin From Store'})
'sti'.field({yesOrNo: true, caption: 'Save Total Order Inventory Value as a WC Order Attribute'})
'mih'.field({numeric: true, decimals: 0, caption: 'Maximum Image Height (Pixels)', blankWhenZero: true})
'pam'.field({yesOrNo: true, caption: 'Prevent Admin Users'})
'pll'.field({yesOrNo: true, caption: 'Allow Different Locations on PO Lines'})
'pld'.field({refersTo: 'Designator', caption: 'Default PO Lot Designator'})
'wld'.field({refersTo: 'Designator', caption: 'Default WO Lot Designator'})
's24'.field({yesOrNo: true, caption: 'Show 24 Hours Per Day In Master Schedule'})

'cty'.options(['Gross Profit', 'Gross Sales'])

'cty'.inception('Gross Profit')

'ccf'.options(['Shipments', 'Orders'])

'ccf'.inception('Shipments')

'tbs'.inception('Active')

'trb'.inception('Native Data Only')

'trb'.options(['Off', 'Native Data Only'])

'shc'.inception('Shipments')

'hsnc'.inception('No')

'nfa'.inception('No')

'sin'.inception('No')

'Configuration'.method('useClassicDataRetrieval', function() {
  if ( global.foreman.constituent )
    return true
  return this.cdr === 'Yes'
})

'salesRecentWeeks'.calculate(async configuration => {
  let res = configuration.salesProjectionPriorWeeks
  if ( configuration.salesAnalysisPriorWeeks && (configuration.salesAnalysisPriorWeeks > res) )
    res = configuration.salesAnalysisPriorWeeks
  if ( ! res )
    res = 4
  return res
})

'Configuration'.method('dateToPostableDate', async function(date) {
  let res = date
  if ( ! this.preventPostingBefore ) return res
  if ( this.preventPostingBefore.isEmptyYMD() ) return res
  if ( res <= this.preventPostingBefore )
    res = this.preventPostingBefore.incDays(1)
  return res
})

'Configuration'.method('getCalendarStartingMonthNo', async function() {
  let months = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
  ]
  let res = months.indexOf(this.calendarStartingMonth) + 1
  if ( res < 1 ) 
    res = 1
  return res
})

'Configuration'.beforeSaving(async function() {

  let thereAreEntries = async () => {
    let entries = await 'Entry'.bring()
    return (entries.length > 0)
  }

  if ( this.getOld().calendarStartingMonth && (this.calendarStartingMonth !== this.getOld().calendarStartingMonth) ) {
    if ( await thereAreEntries() )
      throw(new Error("The starting month cannot be changed as there have been journal entries made"))
  }

  if ( this.storeAttachmentsInSecureLocation === 'Yes' ) {
    if ( ! this.attachmentsPathOnServer ) 
      throw(new Error('Please specify a path to store attachments at on the server'))
    if ( ! this.attachmentsPathOnServer.startsWith('/') ) 
      throw(new Error('The attachments path must be an absolute path (starting with /)'))
  }
})

'Configuration'.afterSaving(async function() {
  global.refreshShortDateFormat()
  global.refreshLanguage()
  await global.gApp.checkLicense()
})

'glEnabled'.inception('No')

'calendarStartingMonth'.inception('July')

'calendarStartingMonth'.options([
  'January',
  'February',
  'March',
  'April',
  'May',
  'June',
  'July',
  'August',
  'September',
  'October',
  'November',
  'December',
])

'Configuration'.method('getMainCalendar', async function(options) {
  if ( ! this.mainCalendar ) return null
  let res = await this.referee('mainCalendar')
  return res
})

'Configuration'.method('getCurrentPeriod', async function(options) {
  let year = await this.getCurrentYear(options); if ( ! year ) return null
  return await year.toCurrentPeriod(options)
})

'Configuration'.method('getCurrentYear', async function(options) {
  if ( ! this.mainCalendar ) return null
  let calendar = await this.referee('mainCalendar')
  return await calendar.getCurrentYear(options)
})

'Configuration'.method('getFinancialYearStartDate', async function() {
  let year = await this.getCurrentYear()
  let period = await year.getFirstPeriod()
  return period.startDate
})

'Configuration'.method('toDefaultStockingUOM', async function() {
  return await this.referee('defaultStockingUOM')
})

'databaseOptimized'.calculate(async config => {
  let sess = await 'session'.bringSingle()
  return sess.databaseIsOptimized ? 'Yes' : 'No'
})

'Configuration'.method('getFullDeliveryAddress',
  async function() {
    let res = this.businessName
    res = global.appendStr(res, this.deliveryAddress, ", ")
    res = global.appendStr(res, this.deliveryCity, ", ")
    res = global.appendStr(res, this.deliveryState, ", ")
    res = global.appendStr(res, this.deliveryPostcode, " ")
    res = global.appendStr(res, this.deliveryCountry, ", ")
    return res
  }
)

'Configuration'.afterRetrievingFast(function() {
  if ( this.viewFromDate === global.invalidYMD() )
    this.viewFromDate = global.emptyYMD()
  if ( this.viewToDate === global.invalidYMD() )
    this.viewToDate = global.emptyYMD()
  if ( ! this.drSecs )
    this.drSecs = 60
  return true
})

'Configuration'.afterRetrieving(async function() {
  if ( ! this.zoomPct )
    this.zoomPct = '100'
})

/*
'Configuration'.afterSaving(async function() {
  global.refreshShortDateFormat()
  global.refreshLanguage()
  //global.refreshTheming()
  await global.gApp.checkLicense()
})
*/

'Configuration'.afterCreating(async function() {
  this.shortDateFormat = 'Browser default'
  this.salesProjectionPriorWeeks = 12
  this.salesAnalysisPriorWeeks = 12
  this.transactionRecentWeeks = 12
  this.enterPurchasePricesInclusiveOfTax = "Yes"
  this.preIncTax = "Yes"
  this.drSecs = 60
  this.treatOldIncompleteOrdersAsInactive = 'Yes'
  this.prDateType = 'Order Payment Date'
})

'shortDateFormat'.options(["Browser default", "dd/mm/yyyy", "mm/dd/yyyy", "yyyy/mm/dd"])

'Configuration'.method('getSalesProjectionPriorWeeks', async function() {
  let res = this.salesProjectionPriorWeeks
  if ( ! res )
    res = 4
  return res
})

'Configuration'.method('getSalesAnalysisPriorWeeks', async function() {
  let res = this.salesAnalysisPriorWeeks
  if ( ! res )
    res = 4
  return res
})

'Configuration'.method('refreshPackables', async function(options) {

  let orders

  let zeroQuantitiesAllocated = async opt => {
/*
let knt = 0
let oldOrders = orders
orders = []
for  ( let i = 0; i < oldOrders.length; i++ ) {
  let order = oldOrders[i]
  let isActive = order.isActiveFast(); if ( ! isActive ) continue
  let so = order.toSOFast();
  if ( ! so )
  so = await order.toSO(); 
  if ( ! so ) continue
  knt++
  if ( knt > 2 ) break
  orders.push(order)
}
*/
    for  ( let i = 0; i < orders.length; i++ ) {
      let order = orders[i]
      let isActive = order.isActiveFast(); if ( ! isActive ) continue
      let so = order.toSOFast();
      if ( ! so )
        so = await order.toSO(); 
      if ( ! so ) continue
      await so.refreshPackable({unallocate: true, forReallocation: true})
    }
  }

  let doIt = async opt => {
    if ( ! opt )
      opt = {}
    let pass = opt.pass
    if ( pass === -1 )
      opt.preallocated = true
    else
      opt.preallocated = false
    await global.updateProgress(opt.startProgress)
    for  ( let i = 0; i < orders.length; i++ ) {
      await global.updateProgress(opt.startProgress + ((i / orders.length) * (opt.finishProgress - opt.startProgress)))
      let order = orders[i]
      let isActive = order.isActiveFast(); if ( ! isActive ) continue
      let so = order.toSOFast();
      if ( ! so )
        so = await order.toSO(); 
      if ( ! so ) continue
      if ( pass === 2 ) {
        if ( so.packable === 'Yes' ) continue
      }
      await so.refreshPackable(opt)
      if ( (pass === 1) && (so.packable === 'Partially') ) {
        // Prioritize orders that can be fully packed
        await so.refreshPackable({unallocate: true, forReallocation: true})
      }
    }
    await global.updateProgress(opt.finishProgress)
  }

  let soIsDuplicate = async so => {
    let sos = await 'SO'.bring({order: so.order})
    if ( sos.length <= 1 ) return false
    let firstSO = sos[0]
    if ( so.id === firstSO.id ) return false
    return true
  }

  let deleteDuplicateSOs = async () => {
    let sos = await 'SO'.bring()
    for ( var i = 0; i < sos.length; i++ ) {
      let so = sos[i]
      if ( ! await so.isActive() ) continue
      if ( ! await soIsDuplicate(so) ) continue
      if ( so.retired !== 'Yes' )
        await so.retire()
      await so.trash()
    }
  }

  let getClustersThatHaveActiveSalesOrders = async () => {
    let res = []
    //let clusters = await 'Cluster'.bring({quantityReservedForCustomerOrders: 0, compare: '!='})
    let clusters = await 'Cluster'.bring()
    for ( let i = 0; i < clusters.length; i++ ) {
      let cluster = clusters[i]
      if ( cluster.quantityReservedForCustomerOrders === 0 ) continue
      res.push(cluster)
    }
    return res
  }

  let shipmentLinesToQuantityShippedInCurrentShipment = (shipmentLines, so, soLine) => {
    let res = soLine.quantityShipped
    for ( var i = 0; i < shipmentLines.length; i++ ) {
      let shipmentLine = shipmentLines[i]
      let shipmentNumber = shipmentLine.shipmentNumber.keyval
      if ( shipmentNumber !== so.latestShipmentNumber )
        res -= shipmentLine.quantityShipped
    }
    return res
  }

  let soLineToQuantityShippedInCurrentShipment = async soLine => {
    let so = await soLine.toSO(); if ( ! so ) return 0
    let shipmentLines = await soLine.toShipmentLines()
    return shipmentLinesToQuantityShippedInCurrentShipment(shipmentLines, so, soLine)
  }

  let soLineToQuantityShippedInCurrentShipmentFast = soLine => {
    let so = soLine.toSOFast() 
    if ( (! so) || (so === 'na') ) 
      return 'na'
    let shipmentLines = soLine.toShipmentLinesFast()
    if ( (! shipmentLines) || (shipmentLines === 'na') ) 
      return 'na'
    return shipmentLinesToQuantityShippedInCurrentShipment(shipmentLines, so, soLine)
  }

  let soLinesToQuantityShippedInOtherShipments = async (soLines, cluster) => {
    let res = 0
    for ( let i = 0; i < soLines.length; i++ ) {
      let soLine = soLines[i]
      let qtyShippedInCurrentShipment = await soLineToQuantityShippedInCurrentShipment(soLine)
      let qty = soLine.quantityShipped - qtyShippedInCurrentShipment
      res += qty
    }
    return res
  }

  let soLinesToQuantityShippedInOtherShipmentsFast = soLines => {
    let res = 0
    for ( let i = 0; i < soLines.length; i++ ) {
      let soLine = soLines[i]
      let qty = soLineToQuantityShippedInCurrentShipmentFast(soLine)
      if ( qty === 'na' )
        return 'na'
      qty = soLine.quantityShipped - qty
      res += qty
    }
    return res
  }

  let soLinesToUnsatisfiedSOLines = async soLines => {
    let res = []
    for ( let i = 0; i < soLines.length; i++ ) {
      let soLine = soLines[i]
      let quantityShippedInCurrentShipment = await soLineToQuantityShippedInCurrentShipment(soLine)
      let required = soLine.quantityRemainingToShip + quantityShippedInCurrentShipment - soLine.divvy
      if ( required <= 0 ) 
        continue
      res.push(soLine)
    }
    return res
  }

  let soLinesToTotalAllocationPct = async soLines => {
    let res = 0
    for ( let i = 0; i < soLines.length; i++ ) {
      let soLine = soLines[i]
      let pct = await soLine.toPreallocationPct()
      res += pct
    }
    return res
  }

  let refreshDivvyPcts = async (aSoLines, options) => {
    let firstPass = options && options.firstPass
    let soLines = aSoLines
    if ( ! firstPass )
      soLines = await soLinesToUnsatisfiedSOLines(aSoLines)
    let totalPct = await soLinesToTotalAllocationPct(soLines)
    for ( let i = 0; i < soLines.length; i++ ) {
      let soLine = soLines[i]
      soLine.divvyPct = 0
      if ( totalPct === 0 )
        continue
      let allocationPct = await soLine.toPreallocationPct()
      soLine.divvyPct = allocationPct * (100 / totalPct)
    }
    return totalPct
  }

  let clusterToSOLines = async cluster => {
    let product = await cluster.toProduct(); if ( ! product ) return []
    let soLines = await 'SOLine'.bring({product: product.reference()})
    let res = []
    for ( let i = 0; i < soLines.length; i++ ) {
      let soLine = soLines[i]
      let active = await soLine.isActive()
      if ( ! active ) continue
      let location = await soLine.toShipFromLocation()
      if ( cluster.location.id !== location.id ) continue
      res.push(soLine)
    }
    return res
  }

  let clusterToSOLinesFast = cluster => {
    let product = cluster.toProductFast() 
    if ( (! product) || (product === 'na') ) 
      return 'na'
    let soLines = 'SOLine'.retrieveFast({product: product.reference()})
    if ( soLines === 'na' )
      return 'na'
    let res = []
    for ( let i = 0; i < soLines.length; i++ ) {
      let soLine = soLines[i]
      let active = soLine.isActiveFast()
      if ( active === 'na' )
        return 'na'
      if ( ! active ) continue
      let location = soLine.toShipFromLocationFast()
      if ( (! location) || (location === 'na') )
        return 'na'
      if ( cluster.location.id !== location.id ) continue
      res.push(soLine)
    }
    return res
  }

  let divvyCluster = async cluster => {
    let soLines = clusterToSOLinesFast(cluster)
    if ( soLines === 'na' )
      soLines = await clusterToSOLines(cluster)
    for ( let i = 0; i < soLines.length; i++ ) {
      let soLine = soLines[i]
      let quantityShippedInCurrentShipment = soLineToQuantityShippedInCurrentShipmentFast(soLine)
      if ( quantityShippedInCurrentShipment === 'na' )
        quantityShippedInCurrentShipment = await soLineToQuantityShippedInCurrentShipment(soLine)
      soLine.quantityShippedInCurrentShipment = quantityShippedInCurrentShipment
      soLine.divvy = 0
    }
    let totalQuantityShippedInOtherShipments = soLinesToQuantityShippedInOtherShipmentsFast(soLines)
    if ( totalQuantityShippedInOtherShipments === 'na' ) {
      totalQuantityShippedInOtherShipments = await soLinesToQuantityShippedInOtherShipments(soLines, cluster)
    }
    let pot = cluster.quantityPickable - totalQuantityShippedInOtherShipments
    let potRemaining = pot
    let totalPct = await refreshDivvyPcts(soLines, {firstPass: true})
    if ( totalPct === 0 )
      return
    for ( let i = 0; i < soLines.length; i++ ) {
      let soLine = soLines[i]
      let required = soLine.quantityRemainingToShip + soLine.quantityShippedInCurrentShipment
      let allowed = Math.round(pot * (soLine.divvyPct / 100)) - soLine.quantityShippedInCurrentShipment
      if ( allowed < 0 )
        allowed = 0
      soLine.divvy = Math.min(required, allowed) 
      potRemaining -= (soLine.divvy + soLine.quantityShippedInCurrentShipment)
      if ( potRemaining < 0 ) {
        soLine.divvy += potRemaining
        if ( soLine.divvy < 0 )
          soLine.divvy = 0
        potRemaining = 0
      }
    }
    while ( potRemaining > 0 ) {
      let dregs = potRemaining
      let dregsRemaining = dregs
      await refreshDivvyPcts(soLines)
      for ( let i = 0; i < soLines.length; i++ ) {
        let soLine = soLines[i]
        let required = soLine.quantityRemainingToShip + soLine.quantityShippedInCurrentShipment
        let allowed = Math.round(dregs * (soLine.divvyPct / 100))
        let totalAllowed = allowed + soLine.divvy
        let newDivvy = Math.min(required, totalAllowed) 
        let divvyChg = newDivvy - soLine.divvy
        if ( divvyChg > dregsRemaining ) 
          divvyChg = dregsRemaining
        soLine.divvy += divvyChg
        if ( soLine.divvy < 0 )
          soLine.divvy = 0
        dregsRemaining -= divvyChg
      }
      if ( dregsRemaining === dregs )
        break
      potRemaining = dregsRemaining
    }
  }

  let divvy = async () => {
    let clusters = await getClustersThatHaveActiveSalesOrders() 
    for ( var i = 0; i < clusters.length; i++ ) {
      let cluster = clusters[i]
      await divvyCluster(cluster)
    }
  }

  let getSensitiveDatatypes = () => {
    return [
      'Configuration', 
      'orders', 
      'order_items', 
      'Inventory', 
      'Cluster', 
      'SO', 
      'SOLine', 
      'Component', 
      'Preempt', 
      'Lot', 
      'Clump', 
      'SOShipment', 
      'SOShipmentLine', 
      'Allocation', 
      'AllocationLine', 
      'users', 
      'shipping_methods' 
    ]
  }

  let needRefresh = async () => {
    if ( options && options.force )
      return true
    if ( ! global.foreman._lastRefreshPackables ) 
      return true
    let dts = getSensitiveDatatypes()
    return global.foreman.anyHaveBeenSavedOrExternallyAlteredSince(dts, global.foreman._lastRefreshPackables)
  }

  global.refreshingPackables = true
  global.startProgress({message: 'Allocating inventory to orders'})
  try {
    await global.updateProgress(0.01)
    await global.foreman.doSave({msgOnException: true}) // So that stock that needs to be moved based on SOLine location changes is moved
    await global.updateProgress(0.1)
    if ( ! await needRefresh() )
      return
    if ( global.logRefreshPackables )
      "****REFRESHING PACKABLES".m()
    orders = await 'orders.RecentOrActive'.bring()
    orders.sort((a, b) => {
      if ( a.order_date === b.order_date )
        return a.id > b.id ? 1 : -1
      return a.order_date > b.order_date ? 1 : -1
    })
    await deleteDuplicateSOs()
    await global.updateProgress(0.2)
    await zeroQuantitiesAllocated()
    await global.updateProgress(0.3)
    let thereAreAllocations = ((await 'Allocation'.bring()).length > 0)
    if ( thereAreAllocations ) {
      await divvy()
      await doIt({pass: -1, startProgress: 0.4, finishProgress: 0.5})
    }
    if ( this.prioritizeOrderCompletion === 'Yes' ) {
      await doIt({pass: 1, startProgress: 0.5, finishProgress: 0.75})
      await doIt({pass: 2, startProgress: 0.75, finishProgress: 0.9})
    } else {
      await doIt({startProgress: 0.5, finishProgress: 0.9})
    }
    await global.foreman.doSave({msgOnException: true})
    await global.updateProgress(0.95)
    global.foreman._lastRefreshPackables = global.nowMs()
  } finally {
    global.refreshingPackables = false
    global.stopProgress()
  }
})

'Configuration'.method("getPackingListCasts", async output => {

  let soAndLocToPackingList = async (so, loc) => {
    let orderId = await so.toOrderId()
    let packingLists = await 'PackingList'.bring({orderId: orderId})
    for ( var i = 0; i < packingLists.length; i++ ) {
      let packingList = packingLists[i]
      if ( packingList.shipFromLocationName === loc.locationName )
        return packingList
    }
    return null
  }

  let createPackingList = async (so, loc) => {
    let res = await 'PackingList'.create()
    res.orderId = await so.toOrderId()
    res.shipFromLocationName = loc.locationName
    res.orderDate = so.orderDate
    res.shippingNameAndCompany = so.shippingNameAndCompany
    res.shippingAddress = so.shippingAddress
    res.shippingEmailAndPhone = so.shippingEmailAndPhone
    res.notes = so.notes
    res.customerReference = so.customerReference
    return res
  }

  let addAllotmentsToPackingList = async (packingList, soLine) => {
    let as = await soLine.toAllotments()
    let prefix = '---Lot '
    if ( soLine.lotsAreSerials === 'Yes' )
      prefix = '---Serial# '
    for ( var i = 0; i < as.length; i++ ) {
      let a = as[i]
      let packingListLine = await 'PackingListLine'.create({parentCast: packingList}, {packingList: packingList.reference()})
      packingListLine.soLine = soLine.reference()
      packingListLine.descriptionAndSKU = prefix + await a.toLotNumber()
      packingListLine.location = a.location
      packingListLine.quantityToPick = a.quantity
    }
  }

  let addSOLineToPackingList = async (packingList, soLine) => {
    let packingListLine = await 'PackingListLine'.create({parentCast: packingList}, {packingList: packingList.reference()})
    packingListLine.soLine = soLine.reference()
    packingListLine.sequence = soLine.sequence
    packingListLine.descriptionAndSKU = soLine.descriptionAndSKU
    packingListLine.quantityOrdered = soLine.quantityOrdered
    packingListLine.quantityRemainingToShip = soLine.quantityRemainingToShip
    packingListLine.quantityToPick = soLine.quantityToPick
    packingListLine.quantityToMake = soLine.quantityToMake
    if ( soLine.hasLots === 'Yes' )
      await addAllotmentsToPackingList(packingList, soLine)
  }

  let addSOLineToPackingLists = async soLine => {
    let so = await soLine.toSO()
    if ( so.include !== 'Yes' ) return
    let loc = await soLine.toShipFromLocation()
    let packingList = await soAndLocToPackingList(so, loc)
    if ( ! packingList )
      packingList = await createPackingList(so, loc)
    await addSOLineToPackingList(packingList, soLine)
  }

  let addSOToPackingLists = async so => {
    let lines = await so.getLines()
    for ( var i = 0; i < lines.length; i++ ) {
      let line = lines[i]
      await addSOLineToPackingLists(line)
    }
  }

  let createPackingLists = async () => {
    let orders = await 'orders.RecentOrActive'.bring()
    for ( var i = 0; i < orders.length; i++ ) {
      updateProgress((i / orders.length) * 0.5)
      let order = orders[i]
      if ( ! await order.isActive() ) continue
      let so = await order.toSO(); if ( ! so ) continue
      await addSOToPackingLists(so)
    }
  }

  let setFulfillStagesToPacking = async () => {
    let packingListLines = await 'PackingListLine'.bring()
    for ( var i = 0; i < packingListLines.length; i++ ) {
      updateProgress(0.5 + ((i / packingListLines.length) * 0.4))
      let packingListLine = packingListLines[i]
      let soLine = await packingListLine.referee('soLine')
      soLine.fulfillStage = 'Packing'
      let so = await soLine.toSO()
      so.fulfillStage = 'Packing'
    }
    await global.foreman.doSave({msgOnException: true})
  }

  let updateProgress = async (complete) => {
    await global.updateProgress(complete)
    await global.wait(1)
  }

  global.startProgress({message: 'Generating Packing Lists'})
  try {
    'PackingList'.clear()
    await createPackingLists()
    updateProgress(0.5)
    await setFulfillStagesToPacking()
    updateProgress(0.95)
    return await 'PackingList'.bring()
  } catch(e) {
    global.gApp.showMessage(e.message)
  } finally {
    global.stopProgress()
  }
})

'Configuration'.method('prodRefToUnspecifiedLot', async function(product) {

  let getUnspecifiedLot = async () => {
    let lots = await 'Lot'.bring({product: product})
    for ( var i = 0; i < lots.length; i++ ) {
      let lot = lots[i]
      if ( lot.lotNumber === 'Unspecified' )
        return lot
    }
    return null
  }

  let res = await getUnspecifiedLot()
  if ( res )
    return res
  res = await 'Lot'.create(null, {product: product, lotNumber: 'Unspecified'})
  return res
})

'Configuration'.method('balanceAllotments', async function(parent, opt) {

  let allotments

  let getAllotments = async () => {
    let res = await 'Allotment'.bringChildrenOf(parent, {includeMarkedForDeletion: true})
    return res
  }

  let removeAllotments = async () => {
    for ( var i = 0; i < allotments.length; i++ ) {
      let a = allotments[i]
      if ( a._markedForDeletion ) continue
      await a.trash()
    }
  }

  let getSpecifiedQuantity = async () => {
    let res = 0
    for ( var i = 0; i < allotments.length; i++ ) {
      let a = allotments[i]
      if ( a._markedForDeletion ) continue
      let lot = await a.referee('lot')
      if ( lot && (lot.lotNumber === 'Unspecified') ) continue
      res += a.quantity
    }
    return res
  }

  let getUnspecifiedAllotment = async () => {
    for ( var i = 0; i < allotments.length; i++ ) {
      let a = allotments[i]
      let lot = await a.referee('lot'); if ( ! lot ) continue
      if ( lot.lotNumber === 'Unspecified' ) 
        return a
    }
    return null
  }

  let getOrCreateUnspecifiedAllotment = async () => {
    let res = await getUnspecifiedAllotment()
    if ( res ) {
      if ( res._markedForDeletion )
        res._markedForDeletion = false
      return res
    }
    res = await 'Allotment'.create({parentCast: parent}, {allotmentParent: parent.reference()})
    res.allotmentParent = parent.reference()
    let lot = await this.prodRefToUnspecifiedLot(parent.product)
    res.lot = lot.reference()
    return res
  }

  let trashZeroAllotments = async () => {
    for ( var i = 0; i < allotments.length; i++ ) {
      let a = allotments[i]
      if ( a._markedForDeletion ) continue
      if ( opt && (opt.calledByAllotment === a) ) continue
      if ( a.quantity !== 0 ) continue
      if ( a.quantityUpdatedToOnHand !== 0 ) continue
      if ( a.quantityUpdatedToTransaction !== 0 ) continue
      await a.trash()
    }
  }

  if ( parent.beforeBalanceAllotments )
    await parent.beforeBalanceAllotments()
  allotments = await getAllotments()
  await parent.refreshCalculations({force: true, includeDefers: true})
  if ( parent.hasLots !== 'Yes' ) {
    await removeAllotments()
    return
  }
  let specified = await getSpecifiedQuantity()
  let quantity = await parent.toMainQuantity()
  let unspecQuantity = quantity - specified
  let unspecifiedAllotment
  let keepIfZero = opt && opt.keepIfZero
  if ( unspecQuantity === 0 ) {
    if ( ! keepIfZero ) {
      unspecifiedAllotment = await getUnspecifiedAllotment()
      if ( unspecifiedAllotment ) {
        await unspecifiedAllotment.trash()
      }
    }
  } else {
    unspecifiedAllotment = await getOrCreateUnspecifiedAllotment()
    unspecifiedAllotment.quantity = unspecQuantity
  }
  if ( ! keepIfZero )
    await trashZeroAllotments()
})
  
'Configuration'.method('getWOPdfCasts', async output => {

  let addAllotmentsToMemo = async (woMemo, woLine) => {
    let as = await woLine.toAllotments()
    let prefix = '---Lot '
    if ( woLine.lotsAreSerials === 'Yes' )
      prefix = '---Serial# '
    for ( var i = 0; i < as.length; i++ ) {
      let a = as[i]
      let memoLine = await 'WOMemoLine'.create({parentCast: woMemo}, {woMemo: woMemo.reference()})
      memoLine.woLine = woLine.reference()
      memoLine.descriptionAndSKU = prefix + await a.toLotNumber()
      memoLine.location = a.location
      memoLine.quantity = a.quantity
    }
  }

  let addWOLineToMemo = async (woMemo, woLine) => {
    let memoLine = await 'WOMemoLine'.create({parentCast: woMemo}, {woMemo: woMemo.reference()})
    memoLine.woLine = woLine.reference()
    memoLine.descriptionAndSKU = woLine.descriptionAndSKU
    memoLine.location = woLine.location
    memoLine.quantityPerFGUnit = woLine.quantityPerFGUnit
    memoLine.quantity = woLine.quantity
    if ( woLine.hasLots === 'Yes' )
      await addAllotmentsToMemo(woMemo, woLine)
  }

  let createWOMemo = async wo => {
    await wo.refreshCalculations({force: true})
    let res = await 'WOMemo'.create()
    res.businessName = wo.businessName
    res.pdfTitle = wo.pdfTitle
    res.workOrderNumber = wo.workOrderNumber
    res.product = wo.product
    res.fgQuantity = wo.fgQuantity
    res.fgLocation = wo.fgLocation
    res.fgLotsAndQuantities = wo.fgLotsAndQuantities
    res.orderDate = wo.orderDate
    res.expectedCompletionDate = wo.expectedCompletionDate
    res.notes = wo.notes
    let woLines = await wo.toWOLines()
    for ( var i = 0; i < woLines.length; i++ ) {
      let woLine = woLines[i]
      await addWOLineToMemo(res, woLine)
    }
    return res
  }

  'WOMemo'.clear()
  let wos
  if ( output.cast )
    wos = [output.cast]
  else
    wos = await 'WO'.bring({include: 'Yes'})
  let res = []
  for ( var i = 0; i < wos.length; i++ ) {
    let wo = wos[i]
    let woMemo = await createWOMemo(wo)
    res.push(woMemo)
  }
  return res
})

'Configuration'.method('getBOLPdfCasts', async output => {

  let addBOLLineForPallet = async (shipment, pallet) => {
    let bolLine = await 'BOLLine'.create({parentCast: shipment}, {shipment: shipment.reference()})
    bolLine.description = 'Pallet'.translate() + ' ' + pallet.palletSerialNumber
  }

  let addBOLLine = async (shipment, palletLine) => {
    let bolLine = await 'BOLLine'.create({parentCast: shipment}, {shipment: shipment.reference()})
    let product = await palletLine.referee('product')
    let lot = await palletLine.referee('lot')
    bolLine.shippedQuantity = palletLine.quantity
    bolLine.description = product ? product.uniqueName : ''
    bolLine.lotNumber = lot ? lot.lotNumber : ''
  }

  let addPallet = async (pallet, shipment, palletNo) => {
    if ( palletNo !== 0 )
      await 'BOLLine'.create({parentCast: shipment}, {shipment: shipment.reference()}) // blank line
    await addBOLLineForPallet(shipment, pallet)
    let palletLines = await pallet.toLines()
    for ( var i = 0; i < palletLines.length; i++ ) {
      let palletLine = palletLines[i]
      await addBOLLine(shipment, palletLine)
    }
  }

  let createBOLLines = async shipment => {
    let pallets = await shipment.toPallets()
    for ( var i = 0; i < pallets.length; i++ ) {
      let pallet = pallets[i]
      await addPallet(pallet, shipment, i)
    }
  }

  'BOLLine'.clear()
  let ss
  if ( output.cast )
    ss = [output.cast]
  else
    ss = await 'SOShipment'.bring({include: 'Yes'})
  let res = []
  for ( var i = 0; i < ss.length; i++ ) {
    let s = ss[i]
    await createBOLLines(s)
    res.push(s)
  }
  return res
})

'Configuration'.method('condense', async function() {

  let condenseNextBatch = async () => {
    let msg = 'global_condense_pdo'
    let req = {
      methodWithParms: "stocktend_object",
      msgToServer: msg,
      json: {}
    }
    let serverRes = await global.server.doPost(req, null) 
    if ( (! serverRes) || (serverRes === 'error') )
      throw(new Error('The server had problems preparing turbo mode.'.translate()))
    return parseInt(serverRes.data, 10)
  }

  let doIt = async () => {
    let remainingRows = await condenseNextBatch()
    let rowCount = remainingRows + 1000
    let rowsDone = 0
    while ( remainingRows > 0 ) {
      rowsDone += 1000
      await global.updateProgress(rowsDone / rowCount)
      let lastRemainingRows = remainingRows
      remainingRows = await condenseNextBatch()
      if ( remainingRows >= lastRemainingRows )
        throw(new Error('Unexpected error switching to turbo mode'))
    }
  }

  global.startProgress({message: "Preparing Turbo Mode"})
  try {
    try {
      await doIt()
      this.tbs = 'Active'
      global.app.save()
    } finally {
      global.stopProgress()
    }
  } catch(e) {
    global.app.showMessage(e.message)
  }
})
