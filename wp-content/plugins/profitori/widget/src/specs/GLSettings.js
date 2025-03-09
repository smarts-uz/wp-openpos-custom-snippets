'GLSettings'.maint({icon: 'Cog'})
'General Ledger Settings'.title()
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Locations'.action({spec: 'LocationList.js'})
'Reset General Ledger'.action()
'Configuration'.datatype()

'calendarStartingMonth'.field()
'glEnabled'.field()
'preventPostingBefore'.field()

'Reset General Ledger'.act(async (maint, config) => {

  let resetLocationAccounts = async () => {
    let locations = await 'Location'.bring()
    for ( var i = 0; i < locations.length; i++ ) {
      let location = locations[i]
      await location.resetAutoAccounts()
    }
  }

  let resetSupplierAccounts = async () => {
    let suppliers = await 'Supplier'.bring()
    for ( var i = 0; i < suppliers.length; i++ ) {
      let supplier = suppliers[i]
      supplier.apAccount = null
      supplier.purchaseExpenseAccount = null
    }
  }

  let trashAllCasts = async dt => {
    let casts = await dt.bring()
    await casts.forAllAsync(async cast => {
      await cast.trash({force: true})
    })
  }

  let save = async pct => {
    await global.updateProgress(pct / 100)
    let err = await global.foreman.doSave()
    if ( err )
      throw(new Error(err))
  }

  let doIt = async () => {
    global.startProgress({message: "Deleting GL Data..."})
    try {
      try {
        let config = await 'Configuration'.bringFirst(); if ( ! config ) return
        config.glEnabled = 'No'
        config.mainCalendar = null
        config.calendarCreated = 'No'
        config.standardAccountsCreated = 'No'
        await resetLocationAccounts()
        await resetSupplierAccounts()
        await save(10)
        await trashAllCasts('EntryLine')
        await save(20)
        await trashAllCasts('Entry')
        await save(30)
        await trashAllCasts('PeriodBalance')
        await save(40)
        await trashAllCasts('YearBalance')
        await save(50)
        await trashAllCasts('Year')
        await save(60)
        await trashAllCasts('Period')
        await save(75)
        await trashAllCasts('Account')
        await save(95)
      } finally {
        global.stopProgress()
      }
    } catch(e) {
      global.gApp.showMessage("There was a problem saving your data: " + e.message)
      return
    }
  }

  maint.showMessage("WARNING: This will delete all GL accounts and journals and reset settings to default.  " + 
      "Please do a backup before proceeding.  Are you sure?",
    {yesNo: true, onYes: doIt}
  )
})

'GLSettings'.onSave(async (maint, cast) => {
  await maint.harmonize()
})
