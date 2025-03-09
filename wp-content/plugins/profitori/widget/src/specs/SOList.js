'SOList'.list({icon: 'FileInvoiceDollar'})
'Sales Orders'.title()
'Back'.action({act: 'cancel'})
'Add'.action({act: 'add'})
'Allocate'.action({spec: 'AllocationList.js'})
'Fulfill'.action({spec: 'Fulfillment.js'})
'Refresh'.action({act: "refresh"})
'Download to Excel'.action({act: 'excel'})
'Show Completed Orders'.action()
'Sales Agents'.action({spec: 'AgentList.js'})
'Commission Based on Shipments'.action({caption: 'Commission Report', spec: 'CommissionReport.js'})
'Commission Based on Orders'.action({caption: 'Commission Report', spec: 'CommissionByOrdersReport.js'})
'Shipments'.action({spec: 'SalesShipmentList.js'})
'Preorders'.action({spec: 'PreorderList.js'})

'SO'.datatype()
'order'.field()
'orderDate'.field()
'customer'.field({showAsLink: true})
'orderTotal'.field({caption: "Value"})
'wcNiceStatus'.field({caption: 'Status'})
'financeStatus'.field()
'attachmentIcon'.field({icon: true, caption: ''})
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'SOManageMaint.js'.maintSpecname()

'Commission Based on Shipments'.availableWhen(() => {
  return global.confVal('ccf') !== 'Orders'
})

'Commission Based on Orders'.availableWhen(() => {
  return global.confVal('ccf') === 'Orders'
})

'Show Completed Orders'.act(async (list, cast) => {
  list.situation()._showCompleted = true
  list.refresh()
})

'SOList'.filter(async (so, list) => {
  if ( list.situation()._showCompleted )
    return true
  return so.retired !== 'Yes'
})

'SOList'.beforeTrash(async (list, so) => {
  so.manageInProfitori = 'Yes'
})

'attachmentIcon'.calculate(async so => {
  let attachment = await 'Attachment'.bringFirst({theParentId: so.id})
  if ( attachment )
    return 'Paperclip'
})

'attachmentIcon'.destination(async preorder => {
  return 'AttachmentsByParent.js'
})

'SOList'.defaultSort({field: "orderDate", descending: true})

'SOList'.beforeLoading(async list => {
  await list.harmonize()
})

