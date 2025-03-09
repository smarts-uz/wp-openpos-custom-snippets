'TrailerMaint'.maint()
'Add Trailer'.title({when: 'adding'})
'Edit Trailer'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Add another'.action({act: 'add'})
'Trailer'.datatype({plex: true})

'trailerLicensePlate'.field({key: true})

'TrailerMaint'.makeDestinationFor('Trailer')

