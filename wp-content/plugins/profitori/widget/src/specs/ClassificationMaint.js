'ClassificationMaint'.maint()
'Add Classification'.title({when: 'adding'})
'Edit Classification'.title({when: 'editing'})
'Back'.action({act: 'cancel'})
'OK'.action({act: 'ok'})
'Save'.action({act: 'save'})
'Add another'.action({act: 'add'})
'Classification'.datatype({plex: true})

'impost'.field({refersTo: 'Impost', readOnly: true})
'classifier'.field({refersToParent: 'Classifier', parentIsHeader: true, readOnly: true, indexed: true})
'classificationValue'.field({allowEmpty: false})

'impost'.calculate(async classification => {
  let classifier = await classification.referee('classifier'); if ( ! classifier ) return null
  let ref = global.copyObj(classifier.impost)
  ref.kind = null
  return ref
})
