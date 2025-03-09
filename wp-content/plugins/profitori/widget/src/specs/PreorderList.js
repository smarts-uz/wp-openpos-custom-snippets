'PreorderList'.list()
'Preorders'.title()
'Back'.action({act: 'cancel'})
'Add'.action({act: 'add'})
'Refresh'.action({act: "refresh"})
'Download to Excel'.action({act: 'excel'})
'Preorder'.datatype()
'preorderNumber'.field({key: true})
'orderDate'.field()
'customer'.field({showAsLink: true})
'attachmentIcon'.field({icon: true, caption: ''})
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'PreorderMaint.js'.maintSpecname()

'attachmentIcon'.calculate(async preorder => {
  let attachment = await 'Attachment'.bringFirst({theParentId: preorder.id})
  if ( attachment )
    return 'Paperclip'
})

'attachmentIcon'.destination(async preorder => {
  return 'AttachmentsByParent.js'
})

'PreorderList'.defaultSort({field: "orderDate", descending: true})

