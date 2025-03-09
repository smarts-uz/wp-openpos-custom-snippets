'DriverMaint'.maint()
'Add Driver'.title({when: 'adding'})
'Edit Driver'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Add another'.action({act: 'add'})
'Driver'.datatype({plex: true})

'driverName'.field({key: true})
'driverLicense'.field()
'defaultTruck'.field({refersTo: 'Truck'})

'DriverMaint'.makeDestinationFor('Driver')

