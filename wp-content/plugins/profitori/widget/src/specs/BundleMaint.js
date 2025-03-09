'BundleMaint'.maint({icon: 'Bundle'})
'Add Bundle'.title({when: 'adding'})
'Edit Bundle'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Add another'.action({act: 'add'})
'Co-products'.action({spec: 'BundleCobundlesMaint.js'})

'Bundle'.datatype({preventNative: true, plex: false}) // plex is false because rows need to be "decorated"
'bundleNumber'.field({key: true})
'product'.field({refersTo: 'products', caption: 'Bundle Product', indexed: true, showAsLink: true})
'overheadCost'.field({numeric: true, minDecimals: 2, maxDecimals: 6})
'totalCost'.field({readOnly: true, numeric: true, minDecimals: 2, maxDecimals: 6, caption: 'Total Bundle Cost'})
'stockingUOM'.field({readOnly: true, caption: 'Stocking and Sales UOM'})
'totalCostExclTax'.field({readOnly: true, numeric: true, minDecimals: 2, maxDecimals: 6, caption: 'Total Bundle Cost (Excl Tax)'})
'bundleProductId'.field({numeric: true, hidden: true})
'quantityPickable'.field({numeric: true, readOnly: true, hidden: true})
'quantityMakeable'.field({numeric: true, readOnly: true, hidden: true})
'quantityReservedForCustomerOrders'.field({numeric: true, readOnly: true, hidden: true})
'sellableQuantity'.field({numeric: true, readOnly: true, hidden: true, skipConflictChecks: true})
'lastComponentUpdate'.field({hidden: true, skipConflictChecks: true})
'manageManufacturingWithWorkOrders'.field({yesOrNo: true})
'expectedDaysToManufacture'.field({numeric: true, decimals: 0})
'unbundleWhenReceivingOnPO'.field({yesOrNo: true, caption: 'Unbundle When Receiving on PO'})

'BundleMaint'.makeDestinationFor('Bundle')

'stockingUOM'.calculate(async bundle => {
  let inv = await bundle.toInventory(); if ( ! inv ) return ''
  let uom = await inv.referee('stockingUOM'); if ( ! uom ) return ''
  return uom.uomName
})

'product'.readOnlyWhen((maint, bundle) => {
  return ! ( bundle && bundle.isNew() )
})

'totalCost'.visibleWhen((maint, bundle) => {
  return global.confVal('mfgIncTax') === 'Yes'
})

'totalCostExclTax'.visibleWhen((maint, bundle) => {
  return global.confVal('mfgIncTax') !== 'Yes'
})

'totalCostExclTax'.calculate(async bundle => {
  let res = bundle.overheadCost
  let components = await 'Component'.bringChildrenOf(bundle)
  for ( var i = 0; i < components.length; i++ ) {
    let component = components[i]
    await component.refreshCalculations({force: true, includeDefers: true})
    res += component.totalCostExclTax
  }
  return res
})

'unbundleWhenReceivingOnPO'.visibleWhen((maint, bundle) => {
  return global.confVal('eub') === 'Yes'
})

'expectedDaysToManufacture'.inception(1)

'expectedDaysToManufacture'.visibleWhen((maint, bundle) => {
  return bundle.manageManufacturingWithWorkOrders === 'Yes'
})

'totalCost'.calculate(async bundle => {
  let res = bundle.overheadCost
  let components = await 'Component'.bringChildrenOf(bundle)
  for ( var i = 0; i < components.length; i++ ) {
    let component = components[i]
    await component.refreshCalculations({force: true, includeDefers: true})
    res += component.totalCost
  }
  return res
})

'quantityPickable'.calculate(async bundle => {
  let inv = await bundle.toInventory(); if ( ! inv ) return 0
  return inv.quantityPickable
})

'quantityMakeable'.calculate(async bundle => {
  let inv = await bundle.toInventory(); if ( ! inv ) return 0
  return inv.quantityMakeable
})

'quantityReservedForCustomerOrders'.calculate(async bundle => {
  let inv = await bundle.toInventory(); if ( ! inv ) return 0
  return inv.quantityReservedForCustomerOrders
})

'Components'.manifest()
'Add Component'.action({act: 'add'})

'Component'.datatype()
'product'.field({showAsLink: true})
'quantity'.field()
'stockingUOM'.field({caption: 'UOM'})
'avgUnitCost'.field()
'totalCost'.field()
'avgUnitCostExclTax'.field({caption: 'Avg Unit Cost (Excl Tax)'})
'totalCostExclTax'.field({caption: 'Total Cost (Excl Tax)'})
'quantityPickable'.field()
'quantityMakeable'.field()
'quantityReservedForCustomerOrders'.field()
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'ComponentMaint.js'.maintSpecname()

'stockingUOM'.calculate(async component => {
  let inv = await component.toInventory(); if ( ! inv ) return ''
  let uom = await inv.referee('stockingUOM'); if ( ! uom ) return ''
  return uom.uomName
})

'avgUnitCost'.columnVisibleWhen((maint, bundle) => {
  return global.confVal('mfgIncTax') === 'Yes'
})

'avgUnitCostExclTax'.columnVisibleWhen((maint, bundle) => {
  return global.confVal('mfgIncTax') !== 'Yes'
})

'totalCost'.columnVisibleWhen((maint, bundle) => {
  return global.confVal('mfgIncTax') === 'Yes'
})

'totalCostExclTax'.columnVisibleWhen((maint, bundle) => {
  return global.confVal('mfgIncTax') !== 'Yes'
})

'Bundle'.method('toComponents', async function () {
  let res = await 'Component'.bringChildrenOf(this)
  return res
})

'Bundle'.method('toProduct', async function () {
  let res = await this.referee('product')
  return res
})

'Bundle'.method('toInventory', async function () {
  let product = await this.toProduct(); if ( ! product ) return null
  let res = await product.toInventory()
  return res
})

'Bundle'.beforeSaving(async function() {
  this.bundleProductId = this.product ? this.product.id : null // for query efficiency when determining quantity available for sale in the back end
  let otherBundles = await 'Bundle'.bring({product: this.product})
  for ( var i = 0; i < otherBundles.length; i++ ) {
    let otherBundle = otherBundles[i]
    if ( otherBundle.id === this.id ) continue
    throw(new Error('This product is already a bundle product'.translate()))
  }
})

'BundleMaint'.whenAdding(async function() {

  let defaultNumber = async () => {
    let nextNo = await 'NextNumber'.bringOrCreate({forDatatype: 'Bundle'})
    nextNo.number = nextNo.number + 1
    let noStr = nextNo.number + ""
    let adjNo = "BU" + noStr.padStart(5, '0')
    this.setFieldValue('bundleNumber', adjNo)
  }

  await defaultNumber()

})



