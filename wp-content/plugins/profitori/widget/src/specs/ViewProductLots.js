'ViewProductLots'.list({withHeader: true, icon: 'HashTag'})
'View Product Lots'.title()
'product'.field({readOnly: true})
'Back'.action({act: 'cancel'})
'Download to Excel'.action({act: 'excel'})

'Lots'.manifest()
'Clump'.datatype()
'lot'.field({caption: 'Lot Number', showAsLink: true})
'supplierLotNumber'.field()
'location'.field({showAsLink: true})
'quantityOnPurchaseOrders'.field({caption: 'Quantity on POs', showTotal: true})
'quantityBeingManufactured'.field({caption: 'Quantity Being Manufactured', showTotal: true})
'quantityOnHand'.field({showTotal: true, caption: 'Quantity On Hand'})
'quantityReservedForCustomerOrders'.field({showTotal: true, caption: 'Quantity Sold but not Fulfilled'})
'quantityPickable'.field({showTotal: true, caption: 'Quantity Available to Pick'})
'quantityOnWOLinesNeg'.field({caption: 'Quantity on WO Lines', showTotal: true})
'expiryDate'.field()
'Labels'.action({spec: 'Labels.js', place: 'row'})

'Lots'.filter(async (clump, grid) => {
  if ( 
    (clump.quantityOnPurchaseOrders === 0) &&
    (clump.quantityBeingManufactured === 0) &&
    (clump.quantityOnHand === 0) &&
    (clump.quantityReservedForCustomerOrders === 0) &&
    (clump.quantityPickable === 0) &&
    (clump.quantityOnWOLinesNeg === 0) 
  )
    return false
  return true
})

'lot'.dynamicCaption(list => {
  let inv = list.callerCast(); if ( ! inv ) return null
  if ( inv.lotTracking === 'Serial' )
    return 'Serial Number'
  else
    return 'Lot Number'
})

'ViewProductLots'.dynamicTitle(function() {
  let inv = this.callerCast(); if ( ! inv ) return
  if ( inv.lotTracking === 'Serial' )
    return 'View Product Serial Numbers'
  else
    return 'View Product Lots'
})

'Lots'.defaultSort({field: "lot"})

'Lots'.criteria(async function () {
  let inv = this.callerCast()
  if ( inv.datatype() === 'products' )
    inv = await inv.toInventory()
  let res = {inventory: inv}; if ( ! inv ) return null
  return res
})

'product'.default(list => {
  let inv = list.callerCast(); if ( ! inv ) return null
  return inv.productName
})
