'CommissionMaint'.maint()
'Sales Order Commission'.title()
'Back'.action({act: 'cancel'})
'orders'.datatype()

'Sales Order'.panel()
'order_id'.field({caption: "Order Number", readOnly: true})

'Pieces'.manifest()
'Add Agent'.action({act: 'add'})
'Piece'.datatype()
'agent'.field({caption: "Agent", showAsLink: true})
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'PieceMaint.js'.maintSpecname()

'CommissionMaint'.substituteCast(async (cast, maint) => {
  return maint.callerCast()
})

