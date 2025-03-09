'BundleList'.list({icon: 'Bundle'})
'Bundles'.title()
'Back'.action({act: 'cancel'})
'Add'.action({act: 'add'})
'Work Orders'.action({spec: 'WOList.js'})
'Refresh'.action({act: "refresh"})
'Download to Excel'.action({act: 'excel'})
'BundleMaint.js'.maintSpecname()

'Bundle'.datatype()
'bundleNumber'.field()
'product'.field({showAsLink: true, caption: 'Product'})
'overheadCost'.field()
'totalCost'.field()
'totalCostExclTax'.field({caption: 'Total Cost (Excl Tax)'})
'quantityPickable'.field()
'quantityMakeable'.field()
'quantityReservedForCustomerOrders'.field()
'sellableQuantity'.field()
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'BundleMaint.js'.maintSpecname()

'totalCost'.columnVisibleWhen((list, bundle) => {
  return global.confVal('mfgIncTax') === 'Yes'
})

'totalCostExclTax'.columnVisibleWhen((list, bundle) => {
  return global.confVal('mfgIncTax') !== 'Yes'
})

'BundleList'.beforeLoading(async list => {
  await list.harmonize()
})
