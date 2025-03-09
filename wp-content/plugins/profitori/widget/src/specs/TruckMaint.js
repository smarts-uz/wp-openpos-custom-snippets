'TruckMaint'.maint()
'Add Truck'.title({when: 'adding'})
'Edit Truck'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Add another'.action({act: 'add'})
'Truck'.datatype({plex: true})

'truckLicensePlate'.field({key: true})
'carrier'.field({refersTo: 'Carrier', allowAny: true})
'defaultTrailerLicensePlate'.field({refersTo: 'Trailer', allowAny: true})

'TruckMaint'.makeDestinationFor('Truck')

