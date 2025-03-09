'WOReceiptLotsMaint'.maint({panelStyle: 'titled'})
'Work Order Receipt Finished Goods Lots'.title()
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'WOReceipt'.datatype()

'Work Order Details'.panel()
'receiptNumber'.field({readOnly: true})
'product'.field({readOnly: true})
'receivedQuantity'.field({readOnly: true, caption: 'Quantity'})

'Lots'.manifest()
'Add Lot'.action({act: 'addNoSave'})
'Add Serial Number'.action({act: 'addNoSave'})
'Allotment'.datatype()
'lot'.field({showAsLink: true, caption: 'Number'})
'quantity'.field()
'expiryDate'.field()
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'AllotmentMaint.js'.maintSpecname()

'WOReceiptLotsMaint'.substituteCast(async (cast, maint) => {
  let res
  if ( ! cast ) {
    res = maint.callerCast({immediateOnly: true})
    if ( res && res.datatype && (res.datatype() === 'WOReceipt') )
      return res
    return null
  }
  if ( cast && cast.datatype && (cast.datatype() === 'WOReceipt') ) return cast
  return res
})

'Add Lot'.availableWhen(woReceipt => {
  let res = (woReceipt.hasLots === 'Yes') && (woReceipt.lotsAreSerials !== 'Yes')
  return res
})

'Add Serial Number'.availableWhen(woReceipt => {
  let res = (woReceipt.lotsAreSerials === 'Yes')
  return res
})

'Lots'.dynamicTitle(function() {
  let grid = this
  let woReceipt = grid.containerCast()
  if ( woReceipt.lotsAreSerials === 'Yes' )
    return 'Serial Numbers'
  else
    return 'Lots'
})

