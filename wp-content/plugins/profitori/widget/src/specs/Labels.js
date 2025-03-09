'Labels'.page({readOnly: false})
'Labels'.title()
'Back'.action({act: 'cancel'})
//'Layout'.action({spec: 'TemplateMaint.js'})
'Layout'.action()
'Download Labels PDF'.action()
'Print Labels'.action()
'labelType'.field({readOnly: true})
'sourceType'.field({readOnly: true})
'sourceReference'.field({readOnly: true})
'purpose'.field()
'howMany'.field()
'enteredNumberOfLabels'.field({numeric: true, decimals: 0, readOnly: false, caption: "Number of Labels"})

'Layout'.act(async page => {
  let purpose = page.getFieldValue('purpose')
  if ( purpose === 'General Purpose' )
    purpose = ''
  let template = await 'Template'.bringOrCreate({specification: 'LabelsPdf.js', purpose: purpose})
  await page.segue('edit', 'TemplateMaint.js', template)
})

'purpose'.options(['General Purpose', 'Carton', 'Pallet'])

'Labels'.beforeLoading(async page => {
  let cast = page.callerCast()
  if ( cast ) {
    if ( cast.datatype() === 'POReceipt' ) {
      page.setFieldValue('labelType', 'Inventory')
      page.setFieldValue('sourceType', 'Purchase Order Receipt')
      page.setFieldValue('sourceReference', cast.receiptNumber)
      return
    } else if ( cast.datatype() === 'SOShipment' ) {
      page.setFieldValue('labelType', 'Inventory')
      page.setFieldValue('sourceType', 'Shipment')
      page.setFieldValue('sourceReference', cast.shipmentNumber)
      return
    } else if ( cast.datatype() === 'Pallet' ) {
      page.setFieldValue('labelType', 'Pallet')
      page.setFieldValue('sourceType', 'Pallet')
      page.setFieldValue('sourceReference', cast.palletSerialNumber)
      return
    } else if ( (cast.datatype() === 'Clump') || (cast.datatype() === 'Lot') ) {
      page.setFieldValue('labelType', 'Lot')
      page.setFieldValue('sourceType', 'Lot')
      page.setFieldValue('sourceReference', await cast.toLotNumber())
      return
    } else if ( cast.datatype() === 'products' ) {
      page.setFieldValue('labelType', 'Inventory')
      page.setFieldValue('sourceType', 'Product')
      page.setFieldValue('sourceReference', cast.uniqueName)
      return
    } else if ( cast.datatype() === 'Inventory' ) {
      page.setFieldValue('labelType', 'Inventory')
      page.setFieldValue('sourceType', 'Product')
      page.setFieldValue('sourceReference', cast.productName)
      return
    } else if ( cast.datatype() === 'WO' ) {
      page.setFieldValue('labelType', 'Inventory')
      page.setFieldValue('sourceType', 'Work Order')
      page.setFieldValue('sourceReference', cast.workOrderNumber)
      return
    } else if ( cast.datatype() === 'WOReceipt' ) {
      page.setFieldValue('labelType', 'Inventory')
      page.setFieldValue('sourceType', 'Work Order Receipt')
      page.setFieldValue('sourceReference', cast.receiptNumber)
      return
    }
  }
  page.setFieldValue('labelType', 'Inventory')
  page.setFieldValue('sourceType', 'Product')
  page.setFieldValue('sourceReference', 'Selected Products')
})

'Download Labels PDF'.act(async (page, cast) => {
  let numberOfLabels = (cast.howMany === 'Enter Number of Labels') ? cast.enteredNumberOfLabels : -1
  let purpose = page.getFieldValue('purpose')
  if ( purpose === 'General Purpose' )
    purpose = ''
  let prefix = ''
  if ( purpose )
    prefix = purpose.translate() + ' '
  page.downloadPDF({spec: "LabelsPdf.js", 
    docName: prefix + "Labels for " + cast.sourceReference + ".PDF", 
    cast: page.callerCast(), numberOfLabels: numberOfLabels, purpose: purpose})
})

'Print Labels'.act(async (page, cast) => {
  let numberOfLabels = (cast.howMany === 'Enter Number of Labels') ? cast.enteredNumberOfLabels : -1
  let purpose = page.getFieldValue('purpose')
  if ( purpose === 'General Purpose' )
    purpose = ''
  let prefix = ''
  if ( purpose )
    prefix = purpose.translate() + ' '
  page.downloadPDFandPrint({spec: "LabelsPdf.js", 
    docName: prefix + "Labels for " + cast.sourceReference + ".PDF", 
    cast: page.callerCast(), numberOfLabels: numberOfLabels, purpose: purpose})
})

'howMany'.dynamicOptions(async page => {
  let cast = page.callerCast()
  let dt = cast && cast.datatype()
  if ( dt === 'POReceipt' ) 
    return ['Use Quantity Received', 'Enter Number of Labels']
  else if ( dt === 'SOShipment' ) 
    return ['Use Quantity Shipped', 'Enter Number of Labels']
  else if ( dt === 'Pallet' ) 
    return ['Enter Number of Labels']
  else if ( dt === 'WO' )  
    return ['Use Quantity Ordered', 'Enter Number of Labels']
  else if ( dt === 'WOReceipt' )
    return ['Use Quantity Received', 'Enter Number of Labels']
  else
    return ['Use Quantity On Hand', 'Enter Number of Labels']
})

'purpose'.default(page => {
  let res = 'General Purpose'
  let cast = page.callerCast()
  let dt = cast && cast.datatype()
  if ( dt === 'SOShipment' )
    res = 'Carton'
  if ( dt === 'Pallet' )
    res = 'Pallet'
  return res
})

'howMany'.default(page => {
  let cast = page.callerCast()
  let dt = cast && cast.datatype()
  if ( dt === 'POReceipt' ) 
    return 'Use Quantity Received'
  else if ( dt === 'SOShipment' ) 
    return 'Use Quantity Shipped'
  else if ( dt === 'Pallet' ) 
    return 'Enter Number of Labels'
  else if ( dt === 'WO' ) 
    return 'Use Quantity Ordered'
  else if ( dt === 'WOReceipt' ) 
    return 'Use Quantity Received'
  return 'Use Quantity On Hand'
})

'enteredNumberOfLabels'.visibleWhen((page, cast) => {
  return cast.howMany === 'Enter Number of Labels'
})

'enteredNumberOfLabels'.inception(1)
