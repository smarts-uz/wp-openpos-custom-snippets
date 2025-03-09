'SOShipmentMaint'.maint({panelStyle: "titled", icon: "Boxes"})
'SOShipment'.datatype()
'Edit Shipment'.title()
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Create Invoice'.action()
'View Invoice'.action()
'Transport'.action()
'Labels'.action({spec: "Labels.js"})
'Shipment Note'.action()
'Attachments'.action({act: 'attachments'})

'Shipment Details'.panel()
'shipmentNumber'.field({readOnly: true})
'order'.field({readOnly: true, showAsLink: true})
'shipmentDate'.field() //{readOnly: true})
'pickedUp'.field()
'invoiced'.field()
'return'.field()
'returnCompleted'.field()

'Transport'.act(async (maint, shipment) => {
  await maint.segue('edit', 'TransportMaint.js', shipment)
})

'returnCompleted'.visibleWhen((maint, shipment) => {
  return shipment.return === 'Yes'
})

'View Invoice'.act(async (maint, shipment) => {
  let credit = await shipment.toCredit()
  if ( ! credit ) {
    await global.foreman.doSave() // in case invoiced was just set to yes - this will generate the invoice
    await shipment.maybeEmailInvoice()
  }
  credit = await shipment.toCredit(); if ( ! credit ) return
  await maint.segue('edit', 'CreditMaint.js', credit)
})

'View Invoice'.availableWhen((shipment, maint) => {
  if ( ! shipment ) return false
  return shipment.invoiced === 'Yes'
})

'Create Invoice'.act(async (maint, shipment) => {
  let customer = await shipment.toCustomer()
  if ( ! customer ) {
    maint.showMessage("This shipment doesn't have a customer - cannot create invoice".translate())
    return
  }
  await shipment.reretrieve()
  shipment.invoiced = 'Yes'
  await global.foreman.doSave()
  await shipment.maybeEmailInvoice()
  let credit = await shipment.toCredit(); if ( ! credit ) return
  await maint.segue('edit', 'CreditMaint.js', credit)
})

'Create Invoice'.availableWhen((shipment, maint) => {
  if ( ! shipment )
    return false
  if ( shipment.invoiced === 'Yes' )
    return false
  if ( (shipment.localPickup === 'Yes') && (shipment.pickedUp !== 'Yes') )
    return false
  return true
})

'invoiced'.visibleWhen((maint, shipment) => {
  return (shipment.localPickup !== 'Yes') || (shipment.pickedUp === 'Yes')
})

'Shipment Note'.act(async (maint, shipment) => {
  maint.downloadPDF({spec: "ShipmentNote.js", docName: shipment.shipmentNumber + ".PDF"})
})

'Lines'.manifest()
'SOShipmentLine'.datatype()
'descriptionAndSKU'.field({caption: 'Product', showAsLink: true})
'quantityShipped'.field()

'SOShipmentMaint'.makeDestinationFor('SOShipment')
