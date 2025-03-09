'CoproductMaint'.maint({panelStyle: "titled"})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'okNoSave'})
'Add another'.action({act: 'addNoSave'})

'Coproduct'.datatype()

'Details'.panel()
'woReceipt'.field({readOnly: true, caption: 'WO Receipt'})
'product'.field()
'quantity'.field()
'notes'.field({multiLine: true})

'Product Image'.panel()
'image'.field({postImage: true, postImageType: 'full', postIdField: 'product'})
'thumbnailImage'.field({postImage: true, postImageType: 'thumbnail', postIdField: 'product', caption: 'Image', hidden: true})

/* eslint-disable no-cond-assign */

'CoproductMaint'.dynamicTitle(function() {
  let verb = this.isAdding() ? 'Add'.translate() : 'Edit'.translate()
  return verb + ' ' + 'Co-product'.translate()
})

'quantity'.afterUserChange(async (oldInputValue, newInputValue, coproduct, maint) => {
  if ( newInputValue === (oldInputValue + '') ) return
  await coproduct.refreshUnspecifiedLot()
})

'product'.excludeChoiceWhen(async (maint, product, coproduct) => {
  let woReceipt = await coproduct.referee('woReceipt'); if ( ! woReceipt ) return false
  let bundle = await woReceipt.toBundle(); if ( ! bundle ) return false
  let cbs = await 'Cobundle'.bringChildrenOf(bundle)
  if ( cbs.length === 0 ) return false
  for ( var i = 0; i < cbs.length; i++ ) {
    let cb = cbs[i]
    if ( ! cb.product ) continue
    if ( cb.product.id === product.id ) return false
  }
  return true
})

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

'Lots'.visibleWhen((maint, coproduct) => {
  if ( ! coproduct ) return false
  return coproduct.hasLots === 'Yes'
})

'Add Lot'.availableWhen(coproduct => {
  let res = (coproduct.hasLots === 'Yes') && (coproduct.lotsAreSerials !== 'Yes')
  return res
})

'Add Serial Number'.availableWhen(coproduct => {
  let res = (coproduct.lotsAreSerials === 'Yes')
  return res
})

'Lots'.dynamicTitle(function() {
  let grid = this
  let coproduct = grid.containerCast()
  if ( coproduct.lotsAreSerials === 'Yes' )
    return 'Serial Numbers'
  else
    return 'Lots'
})

