'CarrierMaint'.maint()
'Add Carrier'.title({when: 'adding'})
'Edit Carrier'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Add another'.action({act: 'add'})
'Carrier'.datatype({plex: true})

'carrierName'.field({key: true})

'CarrierMaint'.makeDestinationFor('Carrier')

