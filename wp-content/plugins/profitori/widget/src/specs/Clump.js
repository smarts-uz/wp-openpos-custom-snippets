'Clump'.datatype({exportable: true})
'cluster'.field({refersToParent: 'Cluster', key: true, nonUnique: true})
'lot'.field({refersTo: 'Lot', indexed: true})
'inventory'.field({refersTo: 'Inventory'})
'location'.field({refersTo: 'Location'})
'uniqueName'.field({caption: 'Product'})
'product_id'.field()
'quantityOnHand'.field({numeric: true})
'quantityPackable'.field({numeric: true})
'quantityOnPurchaseOrders'.field({numeric: true})
'quantityBeingManufactured'.field({numeric: true})
'quantityOnWOLines'.field({numeric: true})
'quantityOnWOLinesNeg'.field({numeric: true})
'productName'.field({caption: 'Product'})
'sku'.field({caption: 'SKU'})
'quantityAllocatedForPicking'.field({numeric: true})
'quantityReservedForCustomerOrders'.field({numeric: true})
'quantityPickable'.field({numeric: true})
'quantityPackable2'.field({numeric: true})
'expiryDate'.field({date: true})
'supplierLotNumber'.field()

/* eslint-disable no-cond-assign */

'Clump'.cruxFields(['cluster', 'lot'])

'Clump'.method('refreshQuantityAllocated', async function() {
  this.quantityAllocatedForPicking = 0
  let clump = this
  if ( ! clump.location ) return 0
  let a 
  let as
  as = this.toAllotmentsFast()
  if ( global.fastFail(as) )
    as = await this.toAllotments()
  while ( a = as.__() ) {
    let isActive
    let soLine = a.__soLine
    if ( soLine ) {
      isActive = soLine.isActiveFast()
      if ( isActive !== 'na' ) {
        if ( ! isActive ) continue
        clump.quantityAllocatedForPicking += a.quantity
        continue
      }
    }
    let par = a.allotmentParent; if ( ! par ) continue
    if ( par.datatype !== 'SOLine' ) continue
    soLine = a.parentFast()
    if ( global.fastFail(soLine) )
      soLine = await a.parent()
    isActive = soLine.isActiveFast()
    if ( isActive === 'na' )
      isActive = await soLine.isActive()
    if ( ! isActive ) continue
    //if ( soLine.quantityShipped > 0 )
      //continue // allotments refer to quantity shipped, not the quantity packable
    let oi = soLine.toOrderItemFast()
    if ( oi === 'na' )
      oi = await soLine.toOrderItem()
    if ( ! oi ) continue
    a.__soLine = soLine
    clump.quantityAllocatedForPicking += a.quantity
  }
  clump.needsRecalc()
})

'Clump'.method('refreshQuantityReservedForCustomerOrders', async function (opt) {
  let clump = this
  clump.quantityReservedForCustomerOrders = 0
  if ( ! clump.location ) return
  let a; let as = await this.toAllotments()
  while ( a = as.__() ) {
    let par = a.allotmentParent; if ( ! par ) continue
    if ( par.datatype !== 'SOLine' ) continue
    let soLine
    soLine = a.parentFast()
    if ( global.fastFail(soLine) )
      soLine = await a.parent()
    let isActive = soLine.isActiveFast()
    if ( isActive === 'na' )
      isActive = await soLine.isActive()
    if ( ! isActive ) continue
    let oi = soLine.toOrderItemFast()
    if ( oi === 'na' )
      oi = await soLine.toOrderItem()
    if ( ! oi ) continue
    if ( oi.isNew() ) continue
    if ( oi.order_status === 'wc-pending' ) continue // these haven't been deducted from WC stock level
    clump.quantityReservedForCustomerOrders += a.quantityUpdatedToOnHand
  }
  clump.needsRecalc()
})

'quantityPickable'.calculate(clump => {
  return clump.quantityOnHand + clump.quantityReservedForCustomerOrders
})

'quantityPackable2'.calculate(clump => {
  return clump.quantityPickable - clump.quantityAllocatedForPicking
})

'quantityOnWOLinesNeg'.calculate(clump => - clump.quantityOnWOLines)

'supplierLotNumber'.calculate(async clump => {
  let lot = await clump.toLot(); if ( ! lot ) return null
  return lot.supplierLotNumber
})

'expiryDate'.calculate(async clump => {
  let lot = await clump.toLot(); if ( ! lot ) return null
  return lot.expiryDate
})

'Clump'.method('isUnspecified', async function() {
  let lot = await this.toLot(); if ( ! lot ) return
  return lot.lotNumber === 'Unspecified'
})

'Clump'.method('toLot', async function() {
  let res = await this.referee('lot')
  return res
})

'Clump'.method('toLotNumber', async function() {
  let lot = await this.toLot(); if ( ! lot ) return null
  return lot.lotNumber
})

'inventory'.inception(async clump => {
  let cluster = await clump.toCluster(); if ( ! cluster ) return null
  let inv = await cluster.toInventory(); if ( ! inv ) return null
  return inv.reference()
})

'location'.calculate(async function(clump) {
  let cluster = await clump.toCluster(); if ( ! cluster ) return null
  return cluster.location
})

'sku'.calculate(async function(clump) {
  let inventory = await clump.toInventory(); if ( ! inventory ) return null
  let product = await inventory.referee('product'); if ( ! product ) return null
  return product._sku
})

'productName'.calculate(async function(clump) {
  let inventory = await clump.toInventory(); if ( ! inventory ) return null
  let product = await inventory.referee('product'); if ( ! product ) return null
  let res = product.uniqueName.stripAfterLast(" (")
  return res
})

'uniqueName'.calculate(async function(clump) {
  let inventory = await clump.toInventory(); if ( ! inventory ) return null
  let product = await inventory.referee('product'); if ( ! product ) return null
  let res = product.uniqueName
  return res
})

'product_id'.calculate(async function(clump) {
  let inventory = await clump.toInventory(); if ( ! inventory ) return null
  let product = await inventory.referee('product'); if ( ! product ) return null
  return product.id
})

'Clump'.method('toCluster', async function() {
  let res = await this.referee('cluster')
  return res
})

'Clump'.method('toClusterFast', function() {
  let res = this.refereeFast('cluster')
  return res
})

'Clump'.method('toInventoryFast', function() {
  let res = this.refereeFast('inventory')
  return res
})

'Clump'.method('toInventory', async function() {
  let res = await this.referee('inventory')
  return res
})

'Clump'.method('toProductUniqueName', async function() {
  let product = await this.toProduct(); if ( ! product ) return null
  return product.uniqueName
})

'Clump'.method('toProductFast', function() {
  let inventory = this.toInventoryFast(); if ( global.fastFail(inventory) ) return 'na'
  let res = inventory.toProductFast()
  return res
})

'Clump'.method('toProduct', async function() {
  let inventory = await this.toInventory()
  let res = await inventory.toProduct()
  return res
})

'Clump'.method('toLocationFast', async function() {
  let cluster = this.toClusterFast(); if ( global.fastFail(cluster) ) return 'na'
  let res = cluster.toLocationFast()
  return res
})

'Clump'.method('toLocation', async function() {
  let cluster = await this.toCluster(); if ( ! cluster ) return null
  let res = await cluster.toLocation()
  return res
})

'Clump'.method('toLocationName', async function() {
  let loc = await this.toLocation(); if ( ! loc ) return 'General'
  let res = loc.locationName
  return res
})

'Clump'.method('toAllotmentsFast', function() {
  let product = this.toProductFast(); if ( global.fastFail(product) ) return 'na'
  let location = this.toLocationFast(); if ( global.fastFail(location) ) return 'na'
  let allotments = 'Allotment'.bringFast({product: product, lot: this.lot}, {useIndexedField: 'lot'})
  if ( global.fastFail(allotments) ) return 'na'
  let res = []
  for ( var i = 0; i < allotments.length; i++ ) {
    let a = allotments[i]
    let aloc = a.toLocationFast(); if ( global.fastFail(aloc) ) return 'na'
    if ( ! aloc ) continue
    if ( aloc.id !== location.id ) continue
    res.push(a)
  }
  return res
})

'Clump'.method('toAllotments', async function() {
  let product = await this.toProduct()
  let location = await this.toLocation()
  let allotments = await 'Allotment'.bring({product: product, lot: this.lot}, {useIndexedField: 'lot'})
  let res = []
  for ( var i = 0; i < allotments.length; i++ ) {
    let a = allotments[i]
    let aloc = await a.toLocation(); if ( ! aloc ) continue
    if ( aloc.id !== location.id ) continue
    res.push(a)
  }
  return res
})

'Clump'.method('refreshQuantityOnPurchaseOrders', async function() {
  let allotments = await this.toAllotments()
  let qty = 0
  for ( var i = 0; i < allotments.length; i++ ) {
    let a = allotments[i]
    let parent = await a.parent(); if ( ! parent ) continue
    let status
    if ( parent.datatype() === 'POLine' ) {
      status = await parent.toStatus()
      if ( status !== 'Received' )
        qty += a.quantity
    } else if ( parent.datatype() === 'POReceiptLine' ) {
      status = await parent.toPOStatus()
      if ( status !== 'Received' ) {
        qty -= a.quantity
      }
    }
  }
  this.quantityOnPurchaseOrders = qty
  this.needsRecalc()
})

'Clump'.method('refreshQuantityOnWOLines', async function() {
  let allotments = await this.toAllotments()
  let qty = 0
  for ( var i = 0; i < allotments.length; i++ ) {
    let a = allotments[i]
    let parent = await a.parent(); if ( ! parent ) continue
    if ( parent.datatype() !== 'WOLine' ) continue
    let woLine = parent
    let wo = await woLine.toWO(); if ( ! wo ) continue
    if ( wo.status === 'Completed' ) continue
    qty += a.quantity
  }
  this.quantityOnWOLines = qty
  this.needsRecalc()
})

'Clump'.method('refreshQuantityBeingManufactured', async function() {

  let quantityOnWOReceiptAllotments = async wo => {
    let res = 0
    let allotments = await this.toAllotments()
    for ( var i = 0; i < allotments.length; i++ ) {
      let a = allotments[i]
      let parent = await a.parent(); if ( ! parent ) continue
      if ( parent.datatype() !== 'WOReceipt' ) continue
      let woReceipt = parent
      let wo2 = await woReceipt.toWO(); if ( ! wo2 ) continue
      if ( wo2.id !== wo.id ) continue
      res += a.quantity
    }
    return res
  }

  let allotments = await this.toAllotments()
  let qty = 0
  for ( var i = 0; i < allotments.length; i++ ) {
    let a = allotments[i]
    let parent = await a.parent(); if ( ! parent ) continue
    if ( parent.datatype() !== 'WO' ) continue
    let wo = parent
    let received = await quantityOnWOReceiptAllotments(wo)
    let outstanding = a.quantity - received
    qty += outstanding
  }
  this.quantityBeingManufactured = qty
  this.needsRecalc()
})
