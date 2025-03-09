'AllocationMaint'.maint({panelStyle: "titled"})
'Edit Allocation'.title()
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Allocation'.datatype()

''.panel()
'product'.field({readOnly: true, showAsLink: true})
'location'.field({readOnly: true})
'quantityPickableLessShipped'.field({readOnly: true, caption: 'Quantity Pickable'})
'quantityOnPurchaseOrders'.field({readOnly: true})

'Lines'.manifest()
'Allocate by Quantity'.action()
'Allocate by %'.action()
'AllocationLine'.datatype()
'allocation'.field({hidden: true})
'shippingNameAndCompany'.field({caption: 'Customer'})
'orderDate'.field()
'unitPriceIncTax'.field({caption: 'Sales Order Price'})
'unitPriceExclTax'.field({caption: 'Price Excl Tax'})
'quantityRemainingToShip'.field({caption: 'Remaining To Ship'})
'allocatePercent'.field({readOnly: false, showTotal: true})
'allocateQuantity'.field({readOnly: false, showTotal: true})

'AllocationMaint'.afterInitialising(async allocation => {
  if ( allocation.allocatedByQuantity === 'Yes' )
    await allocation.refreshPercentages()
})

'allocatePercent'.columnVisibleWhen((grid, allocation) => {
  return allocation.allocatedByQuantity !== 'Yes'
})

'allocateQuantity'.columnVisibleWhen((grid, allocation) => {
  return allocation.allocatedByQuantity === 'Yes'
})

'Allocate by Quantity'.act(async (maint, allocation) => {
  allocation.allocatedByQuantity = 'Yes'
})

'Allocate by %'.act(async (maint, allocation) => {
  allocation.allocatedByQuantity = 'No'
})

'Allocate by Quantity'.availableWhen((allocation, maint) => {
  return allocation.allocatedByQuantity !== 'Yes'
})

'Allocate by %'.availableWhen((allocation, maint) => {
  return allocation.allocatedByQuantity === 'Yes'
})

