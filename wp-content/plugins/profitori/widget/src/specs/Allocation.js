'Allocation'.datatype()
'cluster'.field({refersTo: 'Cluster', key: true})
'product'.field({refersTo: 'products', indexed: true})
'location'.field({refersTo: 'Location'})
'quantityPickableLessShipped'.field({numeric: true})
'quantityOnPurchaseOrders'.field({numeric: true})
'nextExpectedDeliveryDate'.field({date: true, allowEmpty: true})
'quantityRemainingToShip'.field({numeric: true})
'allocatedPercent'.field({numeric: true, caption: 'Allocated %', decimals: 2})
'averageUnitPriceIncTax'.field({numeric: true, decimals: 2})
'averageUnitPriceExclTax'.field({numeric: true, decimals: 2})
'allocatedByQuantity'.field({yesOrNo: true})

'Allocation'.cruxFields(['product', 'location'])

'Allocation'.method('refreshPercentages', async function() {
  if ( this.allocatedByQuantity !== 'Yes' ) return
  let avail = this.quantityPickableLessShipped + this.quantityOnPurchaseOrders
  if ( ! avail )
    return
  let lines = await 'AllocationLine'.bringChildrenOf(this)
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    line.allocatePercent = (line.allocateQuantity / avail) * 100
  }
})

'Allocation'.beforeSaving(async function() {
  if ( this.allocatedPercent > 100 )
    throw(new Error('Allocated % cannot be greater than 100'.translate()))
})

'averageUnitPriceExclTax'.calculate(async allocation => {
  let lines = await allocation.toLines()
  let res = 0
  let total = 0
  let totalQty = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    total += (line.quantityRemainingToShip * line.unitPriceExclTax)
    totalQty += line.quantityRemainingToShip
  }
  if ( totalQty !== 0 )
    res = total / totalQty
  return res
})

'averageUnitPriceIncTax'.calculate(async allocation => {
  let lines = await allocation.toLines()
  let res = 0
  let total = 0
  let totalQty = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    total += (line.quantityRemainingToShip * line.unitPriceIncTax)
    totalQty += line.quantityRemainingToShip
  }
  if ( totalQty !== 0 )
    res = total / totalQty
  return res
})

'allocatedPercent'.calculate(async allocation => {
  let lines = await allocation.toLines()
  let res = 0
  for ( var i = 0; i < lines.length; i++ ) {
    let line = lines[i]
    res += line.allocatePercent
  }
  return res
})

'Allocation'.method('toLines', async function() {
  let lines = await 'AllocationLine'.bringChildrenOf(this)
  return lines
})

'quantityOnPurchaseOrders'.calculate(async allocation => {
  let cluster = allocation.toClusterFast()
  if ( (! cluster) || (cluster === 'na') )
    cluster = await allocation.toCluster()
  if ( ! cluster )
    return 0
  return cluster.quantityOnPurchaseOrders
})

'nextExpectedDeliveryDate'.calculate(async allocation => {
  let cluster = allocation.toClusterFast()
  if ( (! cluster) || (cluster === 'na') )
    cluster = await allocation.toCluster()
  if ( ! cluster )
    return global.emptyYMD()
  return cluster.nextExpectedDeliveryDate
})

'Allocation'.method('toCluster', async function() {
  return await this.referee('cluster')
})

'Allocation'.method('toClusterFast', function() {
  return this.refereeFast('cluster')
})

'product'.destination(async allocation => {
  let p = await allocation.referee('product')
  return p
})
