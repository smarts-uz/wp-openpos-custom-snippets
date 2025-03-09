'ViewWOReceipts'.list()
'View Receipts'.title()
'WOReceipt'.datatype()
'Back'.action({act: 'cancel'})
'Refresh'.action({act: "refresh"})
'receiptNumber'.field()
'receivedDate'.field()
'product'.field({showAsLink: true})
'quantity'.field()
'fgLocation'.field({showAsLink: true})
'attachmentIcon'.field({icon: true, caption: ''})
'Edit'.action({place: 'row', act: 'edit'});
'Trash'.action({place: 'row', act: 'trash'});
'WOReceiptMaint.js'.maintSpecname()

'attachmentIcon'.calculate(async cast => {
  let attachment = await 'Attachment'.bringFirst({theParentId: cast.id})
  if ( attachment )
    return 'Paperclip'
})

'attachmentIcon'.destination(async cast => {
  return 'AttachmentsByParent.js'
})

'ViewWOReceipts'.dynamicTitle(function() {
  let wo = this.callerCast({immediateOnly: true}); if ( ! wo ) return
  if ( wo.datatype() !== "WO" ) return 
  let res = "View Receipts for Work Order " + wo.workOrderNumber
  return res
})

'ViewWOReceipts'.criteria(async function () {
  let r = this.callerCast({immediateOnly: true}); if ( ! r ) return null
  if ( r.datatype() !== "WO" ) return null
  let res = {parentId: r.id}
  return res
})
