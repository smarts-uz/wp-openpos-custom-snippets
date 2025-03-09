'CobundleMaint'.maint({panelStyle: "titled"})
'Add Bundle Co-product'.title()
'Back'.action({act: 'cancel'})
'OK'.action({act: 'okNoSave'})
'Add another'.action({act: 'addNoSave'})

'Cobundle'.datatype()

'Details'.panel()
'bundle'.field({readOnly: true})
'product'.field()
'notes'.field({multiLine: true})

'Product Image'.panel()
'image'.field({postImage: true, postImageType: 'full', postIdField: 'product'})
'thumbnailImage'.field({postImage: true, postImageType: 'thumbnail', postIdField: 'product', caption: 'Image', hidden: true})


