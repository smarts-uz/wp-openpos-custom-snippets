'ReceiveWorkOrders'.list({icon: "Boxes"})
'Receive Work Orders'.title()
'Back'.action({act: 'cancel'})
'Refresh'.action({act: "refresh"})
'View All Receipts'.action({spec: 'ViewWOReceipts.js'})
'WO'.datatype()
'workOrderNumber'.field({key: true, showAsLink: true})
'expectedCompletionDate'.field()
'fgLocation'.field({showAsLink: true, caption: 'Location'})
'status'.field()
'attachmentIcon'.field({icon: true, caption: ''})
'Enter Receipt'.action({place: 'row', act: 'add', autoChild: true, spec: 'WOReceiptMaint.js'});
'View Receipts'.action({place: 'row', spec: 'ViewWOReceipts.js'});

'attachmentIcon'.calculate(async cast => {
  let attachment = await 'Attachment'.bringFirst({theParentId: cast.id})
  if ( attachment )
    return 'Paperclip'
})

'attachmentIcon'.destination(async cast => {
  return 'AttachmentsByParent.js'
})

'ReceiveWorkOrders'.defaultSort({field: "expectedCompletionDate"})

'View Receipts'.availableWhen(wo => ! ['Awaiting Manufacture', 'New'].contains(wo.status))

'ReceiveWorkOrders'.filter(async wo => {
  let isNew = (wo.status === 'New')
  let completed = (wo.status === 'Completed')
  let draft = (wo.stage === 'Draft')
  let res = (! isNew) && (! completed) && (! draft)
  return res
})

