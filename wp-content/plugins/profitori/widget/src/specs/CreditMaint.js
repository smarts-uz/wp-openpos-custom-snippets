'CreditMaint'.maint({panelStyle: 'titled'})
'Add AR Entry'.title({when: 'adding'})
'Edit AR Entry'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Add another'.action({act: 'add'})
'Enter Payment'.action({spec: 'CreditMaint.js'})
'Payments'.action({spec: 'ARPayments.js'})
'Send Via Email'.action()
'Download PDF'.action()
'Attachments'.action({act: 'attachments'})

'Credit'.datatype()

'Entry Details'.panel()
'creditNumber'.field()
'customerName'.field({readOnly: true, showAsLink: true})
'date'.field()
'creditType'.field()
'invoiceCredit'.field({caption: 'Invoice Being Paid'})
'customerReference'.field()
'dueDate'.field()
'absoluteAmount'.field({caption: 'Amount'})
'absoluteAmountOutstanding'.field({caption: 'Amount Outstanding', readOnly: true})
'paid'.field({yesOrNo: true})
'paymentReference'.field()
'notes'.field({multiLine: true})

'Send Status'.panel()
'emailAddress'.field()
'emailed'.field({yesOrNo: true, readOnly: true})
'emailDate'.field({date: true, allowEmpty: true, readOnly: true})

'CreditMaint'.onOK(async (maint, credit) => {
  let invoice = maint.callerCast(); 
  if ( ! invoice ) return
  if ( invoice.datatype() !== 'Credit' ) return
  if ( invoice.creditType !== 'Invoice' ) return
  return 'directNavigate'
})

'Payments'.availableWhen(credit => {
  if ( ! credit ) return false
  return (global.confVal('cph') === 'Multiple Payments Per Invoice') && (credit.creditType === 'Invoice')
})

'Enter Payment'.availableWhen(credit => {
  if ( ! credit ) return false
  return (global.confVal('cph') === 'Multiple Payments Per Invoice') && (credit.creditType === 'Invoice') && 
    (credit.absoluteAmountOutstanding !== 0)
})

'invoiceCredit'.excludeChoiceWhen(async (maint, invoice, credit) => {
  let id1 = invoice.debtor && invoice.debtor.id
  let id2 = credit.debtor && credit.debtor.id
  return (id1 !== id2) || (invoice.creditType !== 'Invoice') || (invoice.paid === 'Yes')
})

'paid'.readOnlyWhen((maint, credit) => {
  return (global.confVal('cph') === 'Multiple Payments Per Invoice')
})

'invoiceCredit'.visibleWhen((maint, credit) => {
  return ((credit.creditType === 'Payment') || (credit.creditType === 'Credit Note')) && 
    (global.confVal('cph') === 'Multiple Payments Per Invoice')
})

'absoluteAmountOutstanding'.visibleWhen((maint, credit) => {
  return (credit.creditType === 'Invoice') && (global.confVal('cph') === 'Multiple Payments Per Invoice')
})

'paid'.visibleWhen((maint, credit) => {
  return credit.creditType === 'Invoice'
})

'paymentReference'.visibleWhen((maint, credit) => {
  return credit.paymentReference ? true : false
})

'Download PDF'.availableWhen((credit, maint) => {
  if ( ! credit ) return false
  return (credit.creditType === 'Invoice') || (credit.creditType === 'Payment')
})

'Download PDF'.act(async (maint, credit) => {
  maint.downloadPDF({spec: credit.getPDFSpecName(), docName: credit.creditNumber + ".PDF"})
})

'Send Status'.visibleWhen((maint, credit) => {
  return credit.creditType === 'Invoice'
})

'Send Via Email'.availableWhen((credit, maint) => {
  if ( ! credit ) return false
  return credit.creditType === 'Invoice'
})

'Send Via Email'.act(async (maint, credit) => {
  try {
    await credit.sendViaEmail()
    maint.showMessage("Email was sent")
  } catch(e) {
    maint.showMessage("There was a problem sending the email:".translate() + " " + e.message)
    await global.foreman.doSave()
  }
})

'dueDate'.visibleWhen((maint, credit) => {
  return credit.creditType === 'Invoice'
})

'CreditMaint'.makeDestinationFor('Credit')

'customerName'.destination(async credit => {
  return await credit.referee('debtor')
})

'CreditMaint'.whenAdding(async function(credit, maint) {

  let defaultCreditNumber = async () => {
    if ( this.getFieldValue('creditNumber') ) return
    let nextNo = await 'NextNumber'.bringOrCreate({forDatatype: 'Credit'})
    nextNo.number = nextNo.number + 1
    let noStr = nextNo.number + ""
    let no = "AR" + noStr.padStart(5, '0')
    maint.setFieldValue('creditNumber', no)
  }

  let getDebtor = async () => {
    let c = this.callerCast(); if ( ! c ) return null
    if ( c.datatype() === 'Debtor' )
      return c
    if ( c.toDebtor )
      return await c.toDebtor()
    return null
  }

  let refreshDebtor = async () => {
    let d = await getDebtor(); if ( ! d ) return
    let debtorRef = d.reference()
    credit.debtor = debtorRef
  }

  let defaultPaymentNumber = async invoice => {

    let paymentToSuffix = payment => {
      let no = payment.creditNumber
      let parts = no.split('-')
      if ( parts.length < 2 ) return "00"
      let res = parts[parts.length - 1]
      res = res.stripLeft('P')
      return res
    }

    let incSuffix = (aSuffix) => {
      let no = Number(aSuffix) + 1 + ""
      return no.padStart(2, '0')
    }

    let paymentsToOneWithMaxPaymentNumber = aPayments => {
      let res
      let max = ''
      aPayments.forAll(payment => {
        let no = payment.creditNumber; if ( ! no ) return 'continue'
        if ( no <= max ) return 'continue'
        max = no
        res = payment
      })
      return res
    }

    let invoiceNo = invoice.creditNumber
    let payments = await 'Credit'.bring({invoiceCredit: invoice})
    let payment = paymentsToOneWithMaxPaymentNumber(payments)
    let suffix = "01"
    if ( payment ) {
      suffix = paymentToSuffix(payment)
      suffix = incSuffix(suffix)
    }
    let paymentNo = invoiceNo + "-P" + suffix
    this.setFieldValue('creditNumber', paymentNo)
  }

  let defaultFromInvoice = async invoice => {
    await invoice.refreshCalculations({force: true})
    credit.customerReference = invoice.customerReference
    credit.amount = - invoice.amountOutstanding
    credit.absoluteAmount = credit.amount
  }

  let maybeDefaultPaymentDetailsFromInvoice = async () => {
    let invoice = this.callerCast(); 
    if ( ! invoice ) return
    if ( invoice.datatype() !== 'Credit' ) return
    if ( invoice.creditType !== 'Invoice' ) return
    credit.creditType = 'Payment'
    credit.invoiceCredit = invoice.reference()
    await defaultPaymentNumber(invoice)
    await defaultFromInvoice(invoice)
    await invoice.refreshAmountOutstanding()
  }

  await refreshDebtor()
  await maybeDefaultPaymentDetailsFromInvoice()
  await defaultCreditNumber()

})


