'ClassifierMaint'.maint()
'Add Classifier'.title({when: 'adding'})
'Edit Classifier'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Add another'.action({act: 'add'})
'Classifier'.datatype({plex: true})

'impost'.field({refersToParent: 'Impost', parentIsHeader: true, readOnly: true, indexed: true})
'classifierDescription'.field({allowEmpty: false, key: true, nonUnique: true})
'productMetaFieldName'.field({allowEmpty: false})
'defaultValue'.field()

'Classifications'.manifest()
'Add Classification'.action({act: 'add'})
'Classification'.datatype()
'classificationValue'.field()
'Edit'.action({place: 'row', act: 'edit'})
'Trash'.action({place: 'row', act: 'trash'})
'ClassificationMaint.js'.maintSpecname()

'Classifier'.beforeSaving(async function() {
  let impost = await this.referee('impost'); if ( ! impost ) return
  await impost.refreshScript()
})

'ClassifierMaint'.makeDestinationFor('Classifier')

'Classifier'.validate(async function() {
  if ( /\s/.test(this.productMetaFieldName) )
    throw(new Error('Product Meta Field Name cannot contain spaces'.translate()))
})

