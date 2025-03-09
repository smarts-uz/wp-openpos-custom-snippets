'LocationMaint'.maint({icon: 'Cubes'})
'Add Location'.title({when: 'adding'})
'Edit Location'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Add another'.action({act: 'add'})
'Location'.datatype({plex: false}) // turned off plex for now - caused Location to fail to retrieve sometimes
'locationName'.field({key: true, caption: 'Name'})
'parentLocation'.field({refersTo: 'Location'})
'stockOnHandAccount'.field({refersTo: 'Account'})
'bankAccount'.field({refersTo: 'Account'})
'madeCreditAccount'.field({refersTo: 'Account'})
'consumedDebitAccount'.field({refersTo: 'Account'})
'syncCreditAccount'.field({refersTo: 'Account'})
'cogsAccount'.field({refersTo: 'Account'})
'transfersSuspenseAccount'.field({refersTo: 'Account'})
'adjustmentSuspenseAccount'.field({refersTo: 'Account'})
'valueAdjustmentsAccount'.field({refersTo: 'Account'})
'taxDebitAccount'.field({refersTo: 'Account'})
'taxCreditAccount'.field({refersTo: 'Account'})
'salesIncomeAccount'.field({refersTo: 'Account'})
'salesDebitAccount'.field({refersTo: 'Account'})
'suspenseAccount'.field({refersTo: 'Account'})
'salesShippingCreditAccount'.field({refersTo: 'Account'})
'salesFeesCreditAccount'.field({refersTo: 'Account'})
'salesTaxDebitAccount'.field({refersTo: 'Account'})
'salesTaxCreditAccount'.field({refersTo: 'Account'})
'purchaseExpenseAccount'.field({refersTo: 'Account'})
'purchaseTaxDebitAccount'.field({refersTo: 'Account'})
'purchaseTaxCreditAccount'.field({refersTo: 'Account'})
'purchaseShippingDebitAccount'.field({refersTo: 'Account'})
'purchaseFeesDebitAccount'.field({refersTo: 'Account'})
'apAccount'.field({refersTo: 'Account', caption: 'AP Account'})
'arAccount'.field({refersTo: 'Account', caption: 'AR Account'})
'arCreditAccount'.field({refersTo: 'Account', caption: 'AR Credit Account'})

'Location'.method('resetAutoAccounts', async function() {
  this.stockOnHandAccount = null
  this.bankAccount = null
  this.madeCreditAccount = null
  this.consumedDebitAccount = null
  this.syncCreditAccount = null
  this.cogsAccount = null
  this.transfersSuspenseAccount = null
  this.adjustmentSuspenseAccount = null
  this.valueAdjustmentsAccount = null
  this.salesIncomeAccount = null
  this.salesShippingCreditAccount = null
  this.salesFeesCreditAccount = null
  this.salesTaxDebitAccount = null
  this.salesTaxCreditAccount = null
  this.taxDebitAccount = null
  this.taxCreditAccount = null
  this.salesDebitAccount = null
  this.suspenseAccount = null
  this.purchaseExpenseAccount = null
  this.purchaseTaxDebitAccount = null
  this.purchaseTaxCreditAccount = null
  this.purchaseShippingDebitAccount = null
  this.purchaseFeesDebitAccount = null
  this.apAccount = null
  this.arAccount = null
  this.arCreditAccount = null
})

'Location'.method('initAutoAccounts', async function() {

  let codeToAccountRef = async code => {
    let account = await 'Account'.bringSingle({accountCode: code})
    return account.reference()
  }

  this.stockOnHandAccount = await codeToAccountRef('010300')
  this.bankAccount = await codeToAccountRef('01010101')
  this.madeCreditAccount = await codeToAccountRef('02020101') // Accrued Overhead
  this.consumedDebitAccount = await codeToAccountRef('02020101')
  this.syncCreditAccount = await codeToAccountRef('02020102') // Inventory Adjustments
  this.cogsAccount = await codeToAccountRef('050201')
  this.transfersSuspenseAccount = await codeToAccountRef('02020103') // Inventory Transfer Clearing
  this.adjustmentSuspenseAccount = await codeToAccountRef('02020102') // Inventory Adjustments
  this.valueAdjustmentsAccount = await codeToAccountRef('02020104') // Inventory Value Adjustment Clearing
  this.salesIncomeAccount = await codeToAccountRef('040101')
  this.salesShippingCreditAccount = await codeToAccountRef('02020105')
  this.salesFeesCreditAccount = await codeToAccountRef('02020106')
  this.salesTaxDebitAccount = await codeToAccountRef('060300')
  this.salesTaxCreditAccount = await codeToAccountRef('020203')
  this.taxDebitAccount = await codeToAccountRef('020203') // NOTE: reverse of sales, as the DR/CR sense is opposite
  this.taxCreditAccount = await codeToAccountRef('060300')
  this.salesDebitAccount = await codeToAccountRef('01010101')
  this.suspenseAccount = await codeToAccountRef('010400')
  this.purchaseExpenseAccount = await codeToAccountRef('050101')
  this.purchaseTaxDebitAccount = await codeToAccountRef('020203')
  this.purchaseTaxCreditAccount = await codeToAccountRef('060300')
  this.purchaseShippingDebitAccount = await codeToAccountRef('050103')
  this.purchaseFeesDebitAccount = await codeToAccountRef('050103')
  this.apAccount = await codeToAccountRef('020101')
  this.arAccount = await codeToAccountRef('010201')
  this.arCreditAccount = await codeToAccountRef('01010101')
})

'Location'.method('sourceAndDrCrToAccount', async function(source, drcr, domain, entity, inventory) {

  let sourceAndDrCrToFieldName = (source, drcr, domain) => {
    if ( domain === 'Price' ) {
      if ( (source === 'Sale') || (source === 'Serial/Lot Sale') ) {
        if ( drcr === 'DR' )
          return 'salesDebitAccount'
        else
          return 'salesIncomeAccount'
      }
      if ( (source === 'Purchase') || (source === 'Serial/Lot Purchase') ) {
        if ( drcr === 'DR' )
          return 'purchaseExpenseAccount'
        else
          return 'apAccount'
      }
      return
    }
    if ( domain === 'Payment' ) {
      if ( (source === 'Purchase') || (source === 'Serial/Lot Purchase') ) {
        if ( drcr === 'DR' )
          return 'apAccount'
        else
          return 'bankAccount'
      } else if ( source === 'Credit' ) {
        if ( drcr === 'DR' )
          return 'bankAccount'
        else
          return 'arAccount'
      }
      return
    }
    if ( domain === 'Shipping' ) {
      if ( (source === 'Sale') || (source === 'Serial/Lot Sale') ) {
        if ( drcr === 'DR' )
          return 'salesDebitAccount'
        else
          return 'salesShippingCreditAccount'
      }
      if ( (source === 'Purchase') || (source === 'Serial/Lot Purchase') ) {
        if ( drcr === 'DR' )
          return 'purchaseShippingDebitAccount'
        else
          return 'apAccount'
      }
      return
    }
    if ( domain === 'Fees' ) {
      if ( (source === 'Sale') || (source === 'Serial/Lot Sale') ) {
        if ( drcr === 'DR' )
          return 'salesDebitAccount'
        else
          return 'salesFeesCreditAccount'
      }
      if ( (source === 'Purchase') || (source === 'Serial/Lot Purchase') ) {
        if ( drcr === 'DR' )
          return 'purchaseFeesDebitAccount'
        else
          return 'apAccount'
      }
      return
    }
    if ( domain === 'Tax' ) {
      if ( (source === 'Sale') || (source === 'Serial/Lot Sale') ) {
        if ( drcr === 'DR' )
          return 'salesTaxDebitAccount'
        else
          return 'salesTaxCreditAccount'
      } else if ( (source === 'Purchase') || (source === 'Serial/Lot Purchase') ) {
        if ( drcr === 'DR' )
          return 'purchaseTaxDebitAccount'
        else
          return 'purchaseTaxCreditAccount'
      } else {
        if ( drcr === 'DR' )
          return 'taxDebitAccount'
        else
          return 'taxCreditAccount'
      }
    }
    if ( (source === 'Sale') || (source === 'Serial/Lot Sale') || (source === 'Consumed') || (source === 'WO Consumed') )
      drcr = (drcr === 'DR') ? 'CR' : 'DR' // Because the normal sign of these is negative
    if ( source === 'Made' ) {
      if ( drcr === 'DR' )
        return 'stockOnHandAccount'
      else
        return 'madeCreditAccount'
    } else if ( source === 'WO Receipt' ) {
      if ( drcr === 'DR' )
        return 'stockOnHandAccount'
      else
        return 'madeCreditAccount'
    } else if ( source === 'Consumed' ) {
      if ( drcr === 'DR' )
        return 'consumedDebitAccount'
      else
        return 'stockOnHandAccount'
    } else if ( source === 'WO Consumed' ) {
      if ( drcr === 'DR' )
        return 'consumedDebitAccount'
      else
        return 'stockOnHandAccount'
    } else if ( source === 'Adjustment' ) {
      if ( drcr === 'DR' )
        return 'stockOnHandAccount'
      else
        return 'adjustmentSuspenseAccount'
    } else if ( source === 'Sync to WC' ) {
      if ( drcr === 'DR' )
        return 'stockOnHandAccount'
      else
        return 'syncCreditAccount'
    } else if ( (source === 'Sale') || (source === 'Serial/Lot Sale') ) {
      if ( drcr === 'DR' )
        return 'cogsAccount'
      else
        return 'stockOnHandAccount'
    } else if ( (source === 'Purchase') || (source === 'Serial/Lot Purchase') || (source === 'PO Receipt')) {
      if ( drcr === 'DR' ) {
        if ( inventory && (inventory.treatAsExpenseInGL === 'Yes') )
          return 'purchaseExpenseAccount'
        else
          return 'stockOnHandAccount'
      } else
        return 'apAccount'
    } else if ( source === 'Transfer' ) {
      if ( drcr === 'DR' )
        return 'stockOnHandAccount'
      else
        return 'transfersSuspenseAccount'
    } else if ( source === 'Value Adjustment' ) {
      if ( drcr === 'DR' )
        return 'stockOnHandAccount'
      else
        return 'valueAdjustmentsAccount'
    } else if ( source === 'Credit' ) {
      if ( drcr === 'DR' )
        return 'arAccount'
      else
        return 'arCreditAccount'
    }
  }

  let getSuspenseAccount = async () => {
    let res = await this.referee('suspenseAccount')
    if ( ! res )
      res = await 'Account'.bringSingle({accountCode: '030204'})
    return res
  }

  let fieldName = sourceAndDrCrToFieldName(source, drcr, domain)
  let res
  if ( inventory && inventory[fieldName] ) {
    res = await inventory.referee(fieldName)
  }
  if ( (! res) && entity && entity[fieldName] ) {
    res = await entity.referee(fieldName)
  }
  if ( ! res)
    res = await this.referee(fieldName)
  if ( ! res )
    res = await getSuspenseAccount()
  return res
})

'locationName'.readOnlyWhen((maint, location) => {
  return (location.locationName === "General")
})

'parentLocation'.readOnlyWhen((maint, location) => {
  return (location.locationName === "General")
})

/*
'LocationMaint'.readOnly( (maint, location) => {
  if (location.locationName === "General")
    return 'The General location cannot be altered'
})
*/

'parentLocation'.excludeChoiceWhen( async (maint, choice) => maint.id() === choice.id )

'parentLocation'.inception(async () => {
  return 'General'
})

'Location'.allowTrash(async function() {
  if ( this.locationName === "General" ) 
    return 'The "General" location cannot be trashed'
  if ( await this.hasClusters() )
    return 'This location has inventory data and cannot be trashed'
  return null
})

'Location'.method('hasClusters', async function() {
  let cluster = await 'Cluster'.bringFirst({location: this})
  return cluster ? true : false
})

'Location'.beforeSaving(async function() {
  if ( this.locationName === 'General' ) return
  if ( ! this.parentLocation ) 
    throw(new Error('You must enter a parent location'))
})

'Location'.method('toParentLocation', async location => {
  let parentRef = location.parentLocation
  if ( (! parentRef) || (! parentRef.id) ) 
    return await 'Location'.bringSingle({locationName: 'General'})
  let res = await 'Location'.bringSingle({id: parentRef.id})
  return res
})

'LocationMaint'.makeDestinationFor('Location')
