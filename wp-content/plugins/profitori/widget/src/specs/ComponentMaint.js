'ComponentMaint'.maint()
'Add Component'.title({when: 'adding'})
'Edit Component'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Add another component'.action({act: 'add'})
'Component'.datatype({preventNative: true})

'bundle'.field({refersToParent: 'Bundle', parentIsHeader: true})
'product'.field({refersTo: 'products', caption: 'Component Product'})
'quantity'.field({numeric: true, maxDecimals: 6})
'avgUnitCost'.field({readOnly: true, numeric: true, minDecimals: 2, maxDecimals: 6})
'totalCost'.field({readOnly: true, numeric: true, minDecimals: 2, maxDecimals: 6})
'avgUnitCostExclTax'.field({readOnly: true, numeric: true, minDecimals: 2, maxDecimals: 6, caption: "Avg Unit Cost (Excl Tax)"})
'totalCostExclTax'.field({readOnly: true, numeric: true, minDecimals: 2, maxDecimals: 6, caption: "Total Cost (Excl Tax)"})
'quantityOnHand'.field({numeric: true, readOnly: true})
'quantityPickable'.field({numeric: true, readOnly: true})
'quantityMakeable'.field({numeric: true, readOnly: true})
'quantityReservedForCustomerOrders'.field({numeric: true, readOnly: true})
'componentProductId'.field({hidden: true})

'Component'.afterCreating(async function() {
  // For overriding 
})

'avgUnitCostExclTax'.calculate(async component => {
  let inv = await component.toInventory(); if ( ! inv ) return 0
  if ( inv.avgUnitCostExclTax === global.unknownNumber() )
    return 0
  return inv.avgUnitCostExclTax
})

'totalCostExclTax'.calculate(async component => {
  return component.avgUnitCostExclTax * component.quantity
})

'avgUnitCost'.visibleWhen((maint, bundle) => {
  return global.confVal('mfgIncTax') === 'Yes'
})

'avgUnitCostExclTax'.visibleWhen((maint, bundle) => {
  return global.confVal('mfgIncTax') !== 'Yes'
})

'totalCost'.visibleWhen((maint, bundle) => {
  return global.confVal('mfgIncTax') === 'Yes'
})

'totalCostExclTax'.visibleWhen((maint, bundle) => {
  return global.confVal('mfgIncTax') !== 'Yes'
})

'Component'.beforeSaving(async function () {
  //if ( this.quantity <= 0 && (! this._markedForDeletion) ) throw(new Error('Quantity must be greater than zero'.translate()))
  let bundle = await this.toBundle(); if ( ! bundle ) return
  bundle.lastComponentUpdate = (new Date()).toLocaleTimeString() // To force update of sellableQuantity from server
  this.componentProductId = this.product ? this.product.id : null
  if ( (await this.isCircular()) && (! this._markedForDeletion) )
    throw(new Error('Cannot add this product, as it is already a parent bundle to this component'.translate()))
  let parentInventory = await this.toParentInventory()
  if ( parentInventory )
    await parentInventory.refreshQuantityMakeable({refreshClusters: true})
})

'Component'.method('toBundleProduct', async function() {
  let bundle = await this.toBundle(); if ( ! bundle ) return null
  return await bundle.toProduct()
})

'Component'.method('isCircular', async function() {
  let product = await this.toProduct(); if ( ! product ) return false
  let res = false
  try {
    res = await product.isContainedWithin(product)
  } catch(e) {
  }
  return res
})

'avgUnitCost'.calculate(async component => {
  let inv = await component.toInventory(); if ( ! inv ) return 0
  if ( inv.avgUnitCost === global.unknownNumber() )
    return 0
  return inv.avgUnitCost
})

'totalCost'.calculate(async component => {
  return component.avgUnitCost * component.quantity
})

'ComponentMaint'.makeDestinationFor('Component')

'quantity'.inception(1)

'quantityOnHand'.calculate(async component => {
  let inv = await component.toInventory(); if ( ! inv ) return 0
  return inv.quantityOnHand
})

'quantityMakeable'.calculate(async component => {
  let inv = await component.toInventory(); if ( ! inv ) return 0
  return inv.quantityMakeable
})

'quantityPickable'.calculate(async component => {
  let inv = await component.toInventory(); if ( ! inv ) return 0
  return inv.quantityPickable
})

'quantityReservedForCustomerOrders'.calculate(async component => {
  let inv = await component.toInventory(); if ( ! inv ) return 0
  return inv.quantityReservedForCustomerOrders
})

'Component'.method('toInventory', async function () {
  let product = await this.toProduct(); if ( ! product ) return null
  let res = await product.toInventory({allowCreate: true})
  return res
})

'Component'.method('toParentInventory', async function () {
  let bundle = await this.toBundle(); if ( ! bundle ) return null
  let product = await bundle.toProduct(); if ( ! product ) return null
  let res = await product.toInventory()
  return res
})

'Component'.method('toProduct', async function () {
  let res = await this.referee('product')
  return res
})

'Component'.method('toBundle', async function () {
  let res = await this.referee('bundle')
  return res
})
