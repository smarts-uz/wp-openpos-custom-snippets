'AllocationLine'.datatype()
'allocation'.field({refersToParent: 'Allocation'})
'soLine'.field({refersTo: 'SOLine', indexed: true})
'shippingNameAndCompany'.field()
'orderDate'.field({date: true})
'unitPriceIncTax'.field({numeric: true, decimals: 2})
'unitPriceExclTax'.field({numeric: true, decimals: 2})
'quantityRemainingToShip'.field({numeric: true})
'allocatePercent'.field({numeric: true, caption: 'Allocate %', decimals: 2})
'allocateQuantity'.field({numeric: true})

'allocateQuantity'.afterUserChange(async (oldInputValue, newInputValue, line) => {
  let allocation = await line.referee('allocation'); if ( ! allocation ) return
  await allocation.refreshPercentages()
})

'unitPriceExclTax'.calculate(async line => {
  let soLine = await line.referee('soLine'); if ( ! soLine ) return 0
  return await soLine.toUnitPriceExclTax()
})

'AllocationLine'.beforeSaving(async function() {
  let allocation = await this.referee('allocation'); if ( ! allocation ) return
  if ( allocation.allocatedPercent > 100 )
    throw(new Error('Allocated % cannot be greater than 100'.translate()))
})
