'WOList'.list({expose: true, icon: 'Tools'})
'Work Orders'.title()
'Back'.action({act: 'cancel'})
'Add'.action({act: 'add'})
'PDFs'.action() 
'Select All'.action() 
'Select None'.action() 
'Download PDFs'.action() 
'Receive Work Orders'.action({spec: 'ReceiveWorkOrders.js'})
'Bundles'.action({spec: 'BundleList.js'})
'Refresh'.action({act: "refresh"})
'Download to Excel'.action({act: 'excel'})
'WO'.datatype()
'include'.field({ephemeral: true, tickbox: true, allowInput: true}) 
'workOrderNumber'.field({key: true})
'orderDate'.field()
'scheduledDate'.field()
'priority'.field()
'product'.field({showAsLink: true})
'fgQuantity'.field({caption: 'Quantity'})
'fgLocation'.field({showAsLink: true, caption: 'Location'})
'stage'.field()
'status'.field({translateOnDisplay: true})
'attachmentIcon'.field({icon: true, caption: ''})
'PDF'.action({place: 'row'}) 
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'WOMaint.js'.maintSpecname()

'attachmentIcon'.calculate(async wo => {
  let attachment = await 'Attachment'.bringFirst({theParentId: wo.id})
  if ( attachment )
    return 'Paperclip'
})

'attachmentIcon'.destination(async wo => {
  return 'AttachmentsByParent.js'
})

'include'.visibleWhen(list => { 
  return list.situation()._doingPDFs 
}) 

'PDFs'.availableWhen((cast, list) => { 
  return ! list.situation()._doingPDFs 
}) 

'PDFs'.act(async (list, cast) => { 
  list.situation()._selectedAll = true 
  list.casts().forEach(wo => wo.include = 'Yes') 
  list.dataChanged() 
  list.situation()._doingPDFs = true 
}) 

'Select All'.act(async (list, cast) => { 
  list.situation()._selectedAll = true 
  list.casts().forEach(wo => wo.include = 'Yes') 
  list.dataChanged() 
}) 

'Select All'.availableWhen((cast, list) => { 
  let s = list.situation() 
  return s._doingPDFs && (! s._selectedAll) 
}) 

'Select None'.act(async (list, cast) => { 
  list.situation()._selectedAll = false 
  list.casts().forEach(wo => wo.include = 'No') 
  list.dataChanged() 
}) 

'Select None'.availableWhen((cast, list) => { 
  let s = list.situation() 
  return s._doingPDFs && s._selectedAll 
}) 

'Download PDFs'.availableWhen((cast, list) => { 
  return list.situation()._doingPDFs 
}) 

'Download PDFs'.act(async list => { 
  let wos = await 'WO'.bring({include: 'Yes'}) 
  if ( wos.length === 0 ) { 
    list.showMessage('Please select at least one order to include') 
    return 
  } 
  list.downloadPDF({casts: wos, spec: "WOPdf.js", docName: "Work Orders.PDF"}) 
  list.dataChanged() 
}) 

'PDF'.act(async (list, wo) => { 
  list.downloadPDF({cast: wo, spec: "WOPdf.js", docName: wo.workOrderNumber + ".PDF"}) 
}) 

'WOList'.defaultSort({field: "orderDate", descending: true})

'WO'.allowTrash(async function() {
  let rec = await 'WOReceipt'.bringFirst({workOrder: this}); if ( ! rec ) return null
  return 'Cannot trash this work order as there is a receipt for it:'.translate() + ' ' + rec.receiptNumber
})
