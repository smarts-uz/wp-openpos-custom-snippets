'WOPdf'.output({rowsPerPage: 10, version: 2})
'WOMemo'.datatype({transient: true})

'Banner'.section({pageHeading: true, rightAlign: true})
'BannerMain'.subsection({captionPosition: "none"})
'businessName'.field()

'Banner2'.section({pageHeading: true, height: 16})
'BannerTitle'.subsection({captionPosition: "none"})
'pdfTitle'.field({fontSize: 28})

'Header'.section({pageHeading: true})
'workOrderNumber'.field({caption: "Order Number"})
'product'.field({caption: 'FG Product', refersTo: 'products'})
'fgQuantity'.field({caption: 'FG Quantity', rightAlign: false, numeric: true})
'fgLocation'.field({caption: 'FG Location', refersTo: 'Location'})
'fgLotsAndQuantities'.field({caption: 'Serial/Lot'})
'orderDate'.field({date: true})
'expectedCompletionDate'.field({date: true, caption: 'Expected Completion'})
'notes'.field({multiLine: true})

/*
'Lines'.manifest()
'WOLine'.datatype()
'descriptionAndSKU'.field({width: 89})
'location'.field({width: 16})
'quantityPerFGUnit'.field({caption: "Qty per FG Unit", width: 16, rightAlign: true})
'quantity'.field({caption: "Qty Required", width: 16, rightAlign: true})
*/

'Lines'.manifest()
'WOMemoLine'.datatype({transient: true})
'descriptionAndSKU'.field({caption: 'Description', width: 89})
'location'.field({caption: 'Location', width: 16})
'quantityPerFGUnit'.field({caption: "Qty per FG Unit", width: 16, rightAlign: true})
'quantity'.field({caption: "Qty Required", width: 16, rightAlign: true})
'woLine'.field({refersTo: 'WOLine', hidden: true})
'woMemo'.field({refersToParent: 'WOMemo', hidden: true})

'WOPdf'.getCasts(async output => {
  let c = await 'Configuration'.bringFirst()
  return await c.getWOPdfCasts(output)
})

/*
'WOPdf'.getCasts(async output => {

  let createWOMemo = async wo => {
    let res = await 'WOMemo'.create()
    res.businessName = wo.businessName
    res.pdfTitle = wo.pdfTitle
    res.workOrderNumber = wo.workOrderNumber
    res.product = wo.product
    res.fgQuantity = wo.fgQuantity
    res.fgLocation = wo.fgLocation
    res.orderDate = wo.orderDate
    res.expectedCompletionDate = wo.expectedCompletionDate
    res.notes = wo.notes
    return res
  }

  let addWOLineToMemo = async (woMemo, woLine) => {
    let memoLine = await 'WOMemoLine'.create({parentCast: woMemo}, {woMemo: woMemo.reference()})
    memoLine.woLine = woLine.reference()
    memoLine.descriptionAndSKU = woLine.descriptionAndSKU
    memoLine.location = woLine.location
    memoLine.quantityPerFGUnit = woLine.quantityPerFGUnit
    memoLine.quantity = woLine.quantity
  }

  let wo = output.cast
  let res = await createWOMemo(wo)
  let woLines = await wo.toWOLines()
  for ( var i = 0; i < woLines.length; i++ ) {
    let woLine = woLines[i]
    await addWOLineToMemo(res, woLine)
  }
  return [res]

})
*/
