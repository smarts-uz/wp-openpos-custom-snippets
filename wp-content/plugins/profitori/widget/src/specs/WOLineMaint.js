'WOLineMaint'.maint({panelStyle: "titled", icon: 'Tools'})
'Add Work Order Line'.title({when: 'adding'})
'Edit Work Order Line'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'WOLine'.datatype()

'Line Details'.panel()
'workOrder'.field({showAsLink: true})
'descriptionAndSKU'.field({showAsLink: true, readOnly: true, caption: 'Product'})
'location'.field()

'Quantity and Cost'.panel()
'quantityPerFGUnit'.field({readOnly: true})
'quantity'.field({readOnly: true, caption: 'Quantity Required'})
'quantityOnHand'.field({readOnly: true})
'unitCostIncTax'.field({readOnly: true})
'unitCostExclTax'.field({readOnly: true})

'Product Image'.panel()
'image'.field({caption: ''})

'Lots'.manifest()
'Add Lot'.action({act: 'add'})
'Add Serial Number'.action({act: 'add'})
'Allotment'.datatype()
'lot'.field({showAsLink: true, caption: 'Number'})
'quantity'.field()
'expiryDate'.field()
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'AllotmentMaint.js'.maintSpecname()

'Add Lot'.availableWhen(woLine => {
  return (woLine.hasLots === 'Yes') && (woLine.lotsAreSerials !== 'Yes')
})

'Add Serial Number'.availableWhen(woLine => {
  return (woLine.lotsAreSerials === 'Yes')
})

'Lots'.dynamicTitle(function() {
  let grid = this
  let woLine = grid.containerCast()
  if ( woLine.lotsAreSerials === 'Yes' )
    return 'Serial Numbers'
  else
    return 'Lots'
})

'Lots'.visibleWhen((maint, woLine) => {
  return woLine.hasLots === 'Yes'
})

'WOLineMaint'.makeDestinationFor('WOLine')

'WOLine'.datatype()

'unitCostExclTax'.visibleWhen((maint, woLine) => {
  return woLine.includeTaxOption === "No"
})

'unitCostIncTax'.visibleWhen((maint, woLine) => {
  return woLine.includeTaxOption !== "No"
})

'WOLineMaint'.warning(async (maint, woLine) => {

  let checkDiscontinued = async () => {
    let inventory = await woLine.toInventory(); if ( ! inventory ) return
    if ( inventory.situation && inventory.situation.startsWith('Discontinued') )
      return 'This product is discontinued'.translate()
  }

  let msg = await checkDiscontinued()
  return msg
})
