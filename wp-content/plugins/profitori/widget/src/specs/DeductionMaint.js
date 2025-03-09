'DeductionMaint'.maint({panelStyle: "titled"})
'Add Deduction'.title({when: 'adding'})
'Edit Deduction'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Add another'.action({act: 'add'})
'Deduction'.datatype({plex: true})

''.panel()
'deductionNumber'.field({key: true})
'active'.field({caption: 'Active', yesOrNo: true})
'customerIds'.field({caption: 'Customer IDs (comma-separated)'})
'productSkus'.field({caption: 'Product SKUs (comma-separated)'})
'productSupplierCodes'.field({caption: 'Product Supplier Codes (comma-separated)'})
'agentWCUserNames'.field({caption: 'Agent WC Usernames (comma-separated)'})
'type'.field({caption: 'Deduction Type'})
'deductionPercent'.field({numeric: true, decimals: 2, caption: 'Deduction %'})
'deductionAmount'.field({numeric: true, decimals: 2, caption: 'Deduction Amount'})

'type'.options(['Percentage', 'Amount'])

'type'.inception('Percentage')

'deductionPercent'.visibleWhen((maint, deduction) => {
  return deduction.type === 'Percentage'
})

'deductionAmount'.visibleWhen((maint, deduction) => {
  return deduction.type === 'Amount'
})

'active'.inception('Yes')

'DeductionMaint'.whenAdding(async function() {

  let defaultNumber = async () => {
    let nextNo = await 'NextNumber'.bringOrCreate({forDatatype: 'Deduction'})
    nextNo.number = nextNo.number + 1
    let noStr = nextNo.number + ""
    let adjNo = "DE" + noStr.padStart(5, '0')
    this.setFieldValue('deductionNumber', adjNo)
  }

  await defaultNumber()

})

'Deduction'.beforeSaving(async function() {

  let validateCustomers = async () => {
    if ( ! this.customerIds ) return
    let ids = this.customerIds.split(',')
    for ( var i = 0; i < ids.length; i++ ) {
      let id = ids[i]
      let c = await 'users'.bringSingle({id: id})
      if ( ! c )
        throw(new Error('Invalid Customer:'.translate() + ' ' + id))
    }
  }

  let validateProducts = async () => {
    if ( ! this.productSkus ) return
    let skus = this.productSkus.split(',')
    for ( var i = 0; i < skus.length; i++ ) {
      let sku = skus[i]
      let p = await 'products'.bringSingle({_sku: sku})
      if ( ! p )
        throw(new Error('Invalid Product SKU:'.translate() + ' ' + sku))
    }
  }

  let validateProductSuppliers = async () => {
    if ( ! this.productSupplierCodes ) return
    let codes = this.productSupplierCodes.split(',')
    for ( var i = 0; i < codes.length; i++ ) {
      let code = codes[i]
      let s = await 'Supplier'.bringSingle({code: code})
      if ( ! s )
        throw(new Error('Invalid Supplier:'.translate() + ' ' + code))
    }
  }

  let validateAgents = async () => {
    if ( ! this.agentWCUserNames ) return
    let ids = this.agentWCUserNames.split(',')
    for ( var i = 0; i < ids.length; i++ ) {
      let id = ids[i]
      let a = await 'Agent'.bringSingle({wcUserName: id})
      if ( ! a )
        throw(new Error('Invalid Agent:'.translate() + ' ' + id))
    }
  }

  let validateDeductionPercent = async () => {
    if ( this.type !== 'Percentage' ) return
    if ( (this.deductionPercent <= 0) || (this.deductionPercent > 100) )
      throw(new Error('Please enter a valid Deduction %'.translate()))
  }

  let validateDeductionAmount = async () => {
    if ( this.type !== 'Amount' ) return
    if (  ! this.deductionAmount ) 
      throw(new Error('Please enter a valid Deduction Amount'.translate()))
  }

  await validateCustomers()
  await validateProducts()
  await validateProductSuppliers()
  await validateAgents()
  await validateDeductionPercent()
  await validateDeductionAmount()
})
