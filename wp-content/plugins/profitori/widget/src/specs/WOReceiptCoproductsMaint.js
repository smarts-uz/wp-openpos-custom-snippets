'WOReceiptCoproductsMaint'.maint({panelStyle: 'titled'})
'Work Order Receipt Co-products'.title()
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'WOReceipt'.datatype()

'Work Order Details'.panel()
'receiptNumber'.field({readOnly: true})
'product'.field({readOnly: true})

'Co-products'.manifest()
'Add Co-product'.action({act: 'addNoSave'})
'Coproduct'.datatype()
'woReceipt'.field({hidden: true})
'product'.field({showAsLink: true})
'quantity'.field()
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'CoproductMaint.js'.maintSpecname()

'WOReceiptCoproductsMaint'.substituteCast(async (cast, maint) => {
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


