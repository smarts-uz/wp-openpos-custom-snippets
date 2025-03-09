'CreditList'.list({withHeader: true})
'AR Account'.title()
'Back'.action({act: 'cancel'})
'Add Entry'.action({act: 'add'})
'Customer AR Details'.action()
'Refresh'.action({act: "refresh"})
'Download to Excel'.action({act: 'excel'})
'CreditMaint.js'.maintSpecname()

'debtor'.field({hidden: true, refersTo: 'Debtor'})
'customer'.field({readOnly: true})
'balance'.field({numeric: true, decimals: 2, readOnly: true})

'Lines'.manifest()
'Credit'.datatype()
'creditNumber'.field()
'date'.field()
'creditType'.field()
'amount'.field()
'order'.field({showAsLink: true})
'notes'.field()
'attachmentIcon'.field({icon: true, caption: ''})
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'CreditMaint.js'.maintSpecname()

'CreditList'.afterTrash(async list => {
  await list.doRefreshAction()
})

'Lines'.criteria(async function(list) {
  let debtorRef = list.getFieldValue('debtor')
  return {debtor: debtorRef}
})

'Customer AR Details'.act(async list => {
  let debtorRef = list.getFieldValue('debtor')
  let debtor = await 'Debtor'.bringSingle({id: debtorRef.id}); if ( ! debtor ) return
  await list.segue('edit', 'DebtorMaint.js', debtor)
})

'CreditList'.beforeLoading(async list => {

  let createDebtor = async () => {
    let cast = list.callerCast(); if ( ! cast ) return
    if ( cast.datatype() !== 'users' )
      return
    let res = await cast.getOrCreateDebtor()
    await global.foreman.doSave()
    return res
  }

  let cast = list.callerCast(); if ( ! cast ) return
  let debtor
  if ( cast.datatype() === 'Debtor' )
    debtor = cast
  else if ( cast.toDebtor ) {
    debtor = await cast.toDebtor()
    if ( ! debtor ) {
      debtor = await createDebtor()
    }
  }
  if ( ! debtor )
    return
  await debtor.reretrieve()
  list.setFieldValue('debtor', debtor.reference())
  list.setFieldValue('customer', debtor.customerName)
  list.setFieldValue('balance', debtor.balance)
})

'attachmentIcon'.calculate(async credit => {
  let attachment = await 'Attachment'.bringFirst({theParentId: credit.id})
  if ( attachment )
    return 'Paperclip'
})

'attachmentIcon'.destination(async po => {
  return 'AttachmentsByParent.js'
})

