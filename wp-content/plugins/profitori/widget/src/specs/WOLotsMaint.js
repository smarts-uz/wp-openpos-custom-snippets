'WOLotsMaint'.maint({panelStyle: 'titled'})
'Work Order Finished Goods Lots'.title()
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'WO'.datatype()

'Work Order Details'.panel()
'workOrderNumber'.field({readOnly: true})
'product'.field({readOnly: true})
'fgQuantity'.field({readOnly: true, caption: 'Quantity'})

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

'WOLotsMaint'.substituteCast(async (cast, maint) => {
  let res
  if ( ! cast ) {
    res = maint.callerCast({immediateOnly: true})
    if ( res && res.datatype && (res.datatype() === 'WO') )
      return res
    return null
  }
  if ( cast && cast.datatype && (cast.datatype() === 'WO') ) return cast
  return res
})

'Add Lot'.availableWhen(wo => {
  let res = (wo.hasLots === 'Yes') && (wo.lotsAreSerials !== 'Yes')
  return res
})

'Add Serial Number'.availableWhen(wo => {
  let res = (wo.lotsAreSerials === 'Yes')
  return res
})

'Lots'.dynamicTitle(function() {
  let grid = this
  let wo = grid.containerCast()
  if ( wo.lotsAreSerials === 'Yes' )
    return 'Serial Numbers'
  else
    return 'Lots'
})

