'ConstituentMaint'.maint()
'Add Constituent Site'.title({when: 'adding'})
'Edit Constituent Site'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Add another site'.action({act: 'add'})
'Constituent'.datatype({plex: true})

'consolidation'.field({refersToParent: 'Consolidation', hidden: true})
'siteName'.field({key: true})
'url'.field({caption: 'URL', allowEmpty: false})
'consumerKey'.field({allowEmpty: false})
'consumerSecret'.field({allowEmpty: false, secret: true})

'Constituent'.beforeSaving(async function() {
  if ( this.url && (! this.url.startsWith('https://')) ) 
    throw(new Error('Url must start with https://'.translate()))
})
